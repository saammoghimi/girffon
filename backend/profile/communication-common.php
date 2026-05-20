<?php
require_once __DIR__ . '/../config/database.php';

function girffonCommunicationTableColumns(PDO $pdo, string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $cache[$table] = [];

    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        foreach (($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : []) as $column) {
            $name = strtolower(trim((string) ($column['Field'] ?? '')));
            if ($name !== '') {
                $cache[$table][$name] = true;
            }
        }
    } catch (PDOException $exception) {
        $cache[$table] = [];
    }

    return $cache[$table];
}

function girffonCommunicationTableExists(PDO $pdo, string $table): bool
{
    try {
        $statement = $pdo->query("SHOW TABLES LIKE '" . str_replace("'", "''", $table) . "'");
        return (bool) ($statement && $statement->fetchColumn());
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonEnsureUserPreferencesTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_preferences (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                promotional_emails TINYINT(1) NOT NULL DEFAULT 1,
                catalog_emails TINYINT(1) NOT NULL DEFAULT 1,
                birthday_discount_emails TINYINT(1) NOT NULL DEFAULT 1,
                order_updates TINYINT(1) NOT NULL DEFAULT 1,
                sms_notifications TINYINT(1) NOT NULL DEFAULT 0,
                two_factor_enabled TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_user_preferences_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonEnsureNewsletterSubscribersTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NULL,
                email VARCHAR(190) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'subscribed',
                source VARCHAR(60) NOT NULL DEFAULT 'profile',
                accepts_promotional_emails TINYINT(1) NOT NULL DEFAULT 0,
                accepts_catalog_emails TINYINT(1) NOT NULL DEFAULT 1,
                subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_newsletter_email (email),
                KEY idx_newsletter_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $columns = girffonCommunicationTableColumns($pdo, 'newsletter_subscribers');
        if (!isset($columns['accepts_promotional_emails'])) {
            $pdo->exec("ALTER TABLE newsletter_subscribers ADD COLUMN accepts_promotional_emails TINYINT(1) NOT NULL DEFAULT 0 AFTER source");
        }
        if (!isset($columns['accepts_catalog_emails'])) {
            $pdo->exec("ALTER TABLE newsletter_subscribers ADD COLUMN accepts_catalog_emails TINYINT(1) NOT NULL DEFAULT 1 AFTER accepts_promotional_emails");
        }

        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonEnsureTestEmailLogsTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS test_email_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NULL,
                email VARCHAR(190) NOT NULL,
                subject VARCHAR(190) NOT NULL DEFAULT '',
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                transport VARCHAR(30) NOT NULL DEFAULT '',
                error_message TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_test_email_user (user_id),
                KEY idx_test_email_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonEnsureCommunicationMessagesTable(PDO $pdo): bool
{
    try {
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
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonCommunicationLogAdminMessage(PDO $pdo, string $name, string $email, string $subject, string $message, string $status = 'unread'): void
{
    if (!girffonEnsureCommunicationMessagesTable($pdo)) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, subject, message, status)
             VALUES (:name, :email, :subject, :message, :status)'
        );
        $statement->execute([
            ':name' => trim($name) !== '' ? $name : 'GirffoN Member',
            ':email' => trim($email) !== '' ? $email : 'unknown@girffon.local',
            ':subject' => $subject,
            ':message' => $message,
            ':status' => $status,
        ]);
    } catch (PDOException $exception) {
    }
}

function girffonCommunicationSaveNewsletterSubscriber(PDO $pdo, int $userId, string $email, string $source = 'profile', ?array $preferences = null): bool
{
    if (!girffonEnsureNewsletterSubscribersTable($pdo)) {
        return false;
    }

    $normalizedEmail = strtolower(trim($email));
    if ($normalizedEmail === '') {
        return false;
    }

    $columns = girffonCommunicationTableColumns($pdo, 'newsletter_subscribers');
    $hasPromotionalColumn = isset($columns['accepts_promotional_emails']);
    $hasCatalogColumn = isset($columns['accepts_catalog_emails']);

    $promotionalPreference = is_array($preferences) && array_key_exists('accepts_promotional_emails', $preferences)
        ? (filter_var($preferences['accepts_promotional_emails'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0)
        : null;
    $catalogPreference = is_array($preferences) && array_key_exists('accepts_catalog_emails', $preferences)
        ? (filter_var($preferences['accepts_catalog_emails'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0)
        : null;

    $status = 'subscribed';
    if ($promotionalPreference !== null || $catalogPreference !== null) {
        $hasAnyConsent = ($promotionalPreference === 1) || ($catalogPreference === 1);
        $status = $hasAnyConsent ? 'subscribed' : 'unsubscribed';
    }

    try {
        $fields = ['user_id', 'email', 'status', 'source'];
        $placeholders = [':user_id', ':email', ':status', ':source'];
        $updates = [
            'user_id = VALUES(user_id)',
            'status = VALUES(status)',
            'source = VALUES(source)',
            'updated_at = CURRENT_TIMESTAMP',
        ];
        $params = [
            ':user_id' => $userId > 0 ? $userId : null,
            ':email' => $normalizedEmail,
            ':status' => $status,
            ':source' => $source,
        ];

        if ($hasPromotionalColumn) {
            $fields[] = 'accepts_promotional_emails';
            $placeholders[] = ':accepts_promotional_emails';
            $params[':accepts_promotional_emails'] = $promotionalPreference;
            $updates[] = 'accepts_promotional_emails = COALESCE(VALUES(accepts_promotional_emails), accepts_promotional_emails)';
        }

        if ($hasCatalogColumn) {
            $fields[] = 'accepts_catalog_emails';
            $placeholders[] = ':accepts_catalog_emails';
            $params[':accepts_catalog_emails'] = $catalogPreference;
            $updates[] = 'accepts_catalog_emails = COALESCE(VALUES(accepts_catalog_emails), accepts_catalog_emails)';
        }

        $statement = $pdo->prepare(
            'INSERT INTO newsletter_subscribers (' . implode(', ', $fields) . ')
             VALUES (' . implode(', ', $placeholders) . ')
             ON DUPLICATE KEY UPDATE ' . implode(', ', $updates)
        );

        return $statement->execute($params);
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonCommunicationLogTestEmail(PDO $pdo, int $userId, string $email, string $subject, string $status, string $transport, string $errorMessage = ''): void
{
    if (!girffonEnsureTestEmailLogsTable($pdo)) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO test_email_logs (user_id, email, subject, status, transport, error_message)
             VALUES (:user_id, :email, :subject, :status, :transport, :error_message)'
        );
        $statement->execute([
            ':user_id' => $userId > 0 ? $userId : null,
            ':email' => strtolower(trim($email)),
            ':subject' => $subject,
            ':status' => $status,
            ':transport' => $transport,
            ':error_message' => $errorMessage !== '' ? $errorMessage : null,
        ]);
    } catch (PDOException $exception) {
    }
}

function girffonCommunicationFetchUserPreferences(PDO $pdo, int $userId): array
{
    if ($userId <= 0 || !girffonEnsureUserPreferencesTable($pdo)) {
        return [];
    }

    try {
        $statement = $pdo->prepare(
            'SELECT promotional_emails, catalog_emails, birthday_discount_emails, order_updates, two_factor_enabled, updated_at
             FROM user_preferences
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([':user_id' => $userId]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonCommunicationFetchNewsletterSubscriber(PDO $pdo, string $email): array
{
    $normalizedEmail = strtolower(trim($email));
    if ($normalizedEmail === '' || !girffonEnsureNewsletterSubscribersTable($pdo)) {
        return [];
    }

    try {
        $statement = $pdo->prepare(
            'SELECT email, status, source, accepts_promotional_emails, accepts_catalog_emails, subscribed_at, updated_at
             FROM newsletter_subscribers
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute([':email' => $normalizedEmail]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonCommunicationFetchLatestTestEmailLog(PDO $pdo, string $email): array
{
    $normalizedEmail = strtolower(trim($email));
    if ($normalizedEmail === '' || !girffonEnsureTestEmailLogsTable($pdo)) {
        return [];
    }

    try {
        $statement = $pdo->prepare(
            'SELECT email, subject, status, transport, error_message, created_at
             FROM test_email_logs
             WHERE email = :email
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
        );
        $statement->execute([':email' => $normalizedEmail]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}
