<?php
require_once __DIR__ . "/../config/database.php";

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

    $checked = true;
}

function girffonAdminFetchMessages(PDO $pdo, int $limit = 0): array
{
    try {
        girffonAdminEnsureContactMessagesTable($pdo);

        $sql = "SELECT id, name, email, subject, message, status, created_at FROM contact_messages ORDER BY created_at DESC, id DESC";
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
        return $statement->rowCount() > 0;
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