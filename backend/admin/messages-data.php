<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminContactColumnExists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
    $statement->execute([':column' => $column]);
    return (bool) $statement->fetch(PDO::FETCH_ASSOC);
}

function girffonAdminEnsureContactMessagesTable(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'unread',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (!girffonAdminContactColumnExists($pdo, 'contact_messages', 'subject')) {
        $pdo->exec("ALTER TABLE contact_messages ADD subject VARCHAR(190) NOT NULL DEFAULT '' AFTER email");
    }

    if (!girffonAdminContactColumnExists($pdo, 'contact_messages', 'status')) {
        $pdo->exec("ALTER TABLE contact_messages ADD status VARCHAR(50) NOT NULL DEFAULT 'unread' AFTER message");
    }

    $checked = true;
}

function girffonAdminUsersTableAvailable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $statement = $pdo->query("SHOW TABLES LIKE 'users'");
        $checked = (bool) ($statement && $statement->fetchColumn());
    } catch (PDOException $exception) {
        $checked = false;
    }

    return $checked;
}

function girffonAdminFetchMessages(PDO $pdo, int $limit = 0): array
{
    try {
        girffonAdminEnsureContactMessagesTable($pdo);

        $select = [
            'm.id',
            'm.name',
            'm.email',
            'm.subject',
            'm.message',
            'm.status',
            'm.created_at',
            "'' AS phone",
            "'' AS country",
            "'' AS city",
            "'' AS address",
        ];
        $join = '';

        if (girffonAdminUsersTableAvailable($pdo)) {
            $select = [
                'm.id',
                'm.name',
                'm.email',
                'm.subject',
                'm.message',
                'm.status',
                'm.created_at',
                "COALESCE(NULLIF(u.phone, ''), '') AS phone",
                "COALESCE(NULLIF(u.country, ''), '') AS country",
                "COALESCE(NULLIF(u.city, ''), '') AS city",
                "COALESCE(NULLIF(u.address, ''), '') AS address",
            ];
            $join = ' LEFT JOIN users u ON LOWER(u.email) = LOWER(m.email) ';
        }

        $sql = 'SELECT ' . implode(', ', $select) . ' FROM contact_messages m' . $join . 'ORDER BY m.created_at DESC, m.id DESC';
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->query($sql);
        return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminCountUnreadMessages(PDO $pdo): int
{
    try {
        girffonAdminEnsureContactMessagesTable($pdo);
        $statement = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE LOWER(status) = 'unread'");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminMarkMessageRead(PDO $pdo, int $messageId): bool
{
    try {
        girffonAdminEnsureContactMessagesTable($pdo);
        $statement = $pdo->prepare(
            "UPDATE contact_messages
             SET status = 'read'
             WHERE id = :id
             LIMIT 1"
        );
        $statement->execute([':id' => $messageId]);

        if ($statement->rowCount() > 0) {
            return true;
        }

        $exists = $pdo->prepare("SELECT id FROM contact_messages WHERE id = :id LIMIT 1");
        $exists->execute([':id' => $messageId]);
        return (bool) $exists->fetchColumn();
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminDeleteMessage(PDO $pdo, int $messageId): bool
{
    try {
        girffonAdminEnsureContactMessagesTable($pdo);
        $statement = $pdo->prepare(
            "DELETE FROM contact_messages
             WHERE id = :id
             LIMIT 1"
        );
        $statement->execute([':id' => $messageId]);
        return $statement->rowCount() > 0;
    } catch (PDOException $exception) {
        return false;
    }
}