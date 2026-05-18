<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminNewsletterSettingsDefault(): array
{
    return [
        'show_summary_cards' => true,
        'show_test_email_panel' => true,
        'show_catalog_campaign_panel' => true,
        'show_birthday_panel' => true,
        'show_promotional_panel' => true,
        'show_campaign_log_panel' => true,
        'show_birthday_log_panel' => true,
        'show_promotional_log_panel' => true,
        'show_subscriber_phone_column' => true,
        'show_subscriber_promotional_column' => true,
        'show_subscriber_birthday_column' => true,
        'show_campaign_transport_column' => true,
        'show_birthday_transport_column' => true,
        'show_promotional_transport_column' => true,
    ];
}

function girffonAdminNewsletterSettingsColumns(PDO $pdo): array
{
    $columns = [];

    try {
        $statement = $pdo->query("SHOW COLUMNS FROM admin_newsletter_settings");
        foreach (($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : []) as $column) {
            $name = strtolower(trim((string) ($column['Field'] ?? '')));
            if ($name !== '') {
                $columns[$name] = true;
            }
        }
    } catch (PDOException $exception) {
        return [];
    }

    return $columns;
}

function girffonAdminNewsletterSettingsKey(int $adminId, string $username): string
{
    if ($adminId > 0) {
        return 'admin:' . $adminId;
    }

    $normalizedUsername = strtolower(trim($username));
    return 'user:' . ($normalizedUsername !== '' ? $normalizedUsername : 'girffon-admin');
}

function girffonAdminNormalizeNewsletterPreferences(array $preferences): array
{
    $defaults = girffonAdminNewsletterSettingsDefault();
    $normalized = [];

    foreach ($defaults as $key => $defaultValue) {
        $normalized[$key] = array_key_exists($key, $preferences)
            ? filter_var($preferences[$key], FILTER_VALIDATE_BOOLEAN)
            : $defaultValue;
    }

    return $normalized;
}

function girffonAdminEnsureNewsletterSettingsTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_newsletter_settings (
                admin_key VARCHAR(191) NOT NULL PRIMARY KEY,
                admin_id INT NOT NULL DEFAULT 0,
                admin_username VARCHAR(191) NOT NULL DEFAULT '',
                show_summary_cards TINYINT(1) NOT NULL DEFAULT 1,
                show_test_email_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_catalog_campaign_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_birthday_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_promotional_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_campaign_log_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_birthday_log_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_promotional_log_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_subscriber_phone_column TINYINT(1) NOT NULL DEFAULT 1,
                show_subscriber_promotional_column TINYINT(1) NOT NULL DEFAULT 1,
                show_subscriber_birthday_column TINYINT(1) NOT NULL DEFAULT 1,
                show_campaign_transport_column TINYINT(1) NOT NULL DEFAULT 1,
                show_birthday_transport_column TINYINT(1) NOT NULL DEFAULT 1,
                show_promotional_transport_column TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminNewsletterSettingsColumns($pdo);
        $requiredColumns = [
            'show_subscriber_phone_column' => "ALTER TABLE admin_newsletter_settings ADD COLUMN show_subscriber_phone_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_promotional_log_panel",
            'show_subscriber_promotional_column' => "ALTER TABLE admin_newsletter_settings ADD COLUMN show_subscriber_promotional_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_subscriber_phone_column",
            'show_subscriber_birthday_column' => "ALTER TABLE admin_newsletter_settings ADD COLUMN show_subscriber_birthday_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_subscriber_promotional_column",
            'show_campaign_transport_column' => "ALTER TABLE admin_newsletter_settings ADD COLUMN show_campaign_transport_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_subscriber_birthday_column",
            'show_birthday_transport_column' => "ALTER TABLE admin_newsletter_settings ADD COLUMN show_birthday_transport_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_campaign_transport_column",
            'show_promotional_transport_column' => "ALTER TABLE admin_newsletter_settings ADD COLUMN show_promotional_transport_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_birthday_transport_column",
        ];

        foreach ($requiredColumns as $columnName => $sql) {
            if (!isset($columns[$columnName])) {
                $pdo->exec($sql);
            }
        }

        $checked = true;
        return true;
    } catch (PDOException $exception) {
        $checked = false;
        return $checked;
    }
}

function girffonAdminFetchNewsletterPreferences(PDO $pdo, int $adminId, string $username): array
{
    $defaults = girffonAdminNewsletterSettingsDefault();
    if (!girffonAdminEnsureNewsletterSettingsTable($pdo)) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                show_summary_cards,
                show_test_email_panel,
                show_catalog_campaign_panel,
                show_birthday_panel,
                show_promotional_panel,
                show_campaign_log_panel,
                show_birthday_log_panel,
                show_promotional_log_panel,
                show_subscriber_phone_column,
                show_subscriber_promotional_column,
                show_subscriber_birthday_column,
                show_campaign_transport_column,
                show_birthday_transport_column,
                show_promotional_transport_column
             FROM admin_newsletter_settings
             WHERE admin_key = :admin_key
             LIMIT 1"
        );
        $statement->execute([
            'admin_key' => girffonAdminNewsletterSettingsKey($adminId, $username),
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $defaults;
        }

        return girffonAdminNormalizeNewsletterPreferences($row);
    } catch (PDOException $exception) {
        return $defaults;
    }
}

function girffonAdminSaveNewsletterPreferences(PDO $pdo, int $adminId, string $username, array $preferences): bool
{
    if (!girffonAdminEnsureNewsletterSettingsTable($pdo)) {
        return false;
    }

    $normalizedPreferences = girffonAdminNormalizeNewsletterPreferences($preferences);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO admin_newsletter_settings (
                admin_key,
                admin_id,
                admin_username,
                show_summary_cards,
                show_test_email_panel,
                show_catalog_campaign_panel,
                show_birthday_panel,
                show_promotional_panel,
                show_campaign_log_panel,
                show_birthday_log_panel,
                show_promotional_log_panel,
                show_subscriber_phone_column,
                show_subscriber_promotional_column,
                show_subscriber_birthday_column,
                show_campaign_transport_column,
                show_birthday_transport_column,
                show_promotional_transport_column
            ) VALUES (
                :admin_key,
                :admin_id,
                :admin_username,
                :show_summary_cards,
                :show_test_email_panel,
                :show_catalog_campaign_panel,
                :show_birthday_panel,
                :show_promotional_panel,
                :show_campaign_log_panel,
                :show_birthday_log_panel,
                :show_promotional_log_panel,
                :show_subscriber_phone_column,
                :show_subscriber_promotional_column,
                :show_subscriber_birthday_column,
                :show_campaign_transport_column,
                :show_birthday_transport_column,
                :show_promotional_transport_column
            ) ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                admin_username = VALUES(admin_username),
                show_summary_cards = VALUES(show_summary_cards),
                show_test_email_panel = VALUES(show_test_email_panel),
                show_catalog_campaign_panel = VALUES(show_catalog_campaign_panel),
                show_birthday_panel = VALUES(show_birthday_panel),
                show_promotional_panel = VALUES(show_promotional_panel),
                show_campaign_log_panel = VALUES(show_campaign_log_panel),
                show_birthday_log_panel = VALUES(show_birthday_log_panel),
                show_promotional_log_panel = VALUES(show_promotional_log_panel),
                show_subscriber_phone_column = VALUES(show_subscriber_phone_column),
                show_subscriber_promotional_column = VALUES(show_subscriber_promotional_column),
                show_subscriber_birthday_column = VALUES(show_subscriber_birthday_column),
                show_campaign_transport_column = VALUES(show_campaign_transport_column),
                show_birthday_transport_column = VALUES(show_birthday_transport_column),
                show_promotional_transport_column = VALUES(show_promotional_transport_column)"
        );

        return $statement->execute([
            'admin_key' => girffonAdminNewsletterSettingsKey($adminId, $username),
            'admin_id' => max(0, $adminId),
            'admin_username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
            'show_summary_cards' => $normalizedPreferences['show_summary_cards'] ? 1 : 0,
            'show_test_email_panel' => $normalizedPreferences['show_test_email_panel'] ? 1 : 0,
            'show_catalog_campaign_panel' => $normalizedPreferences['show_catalog_campaign_panel'] ? 1 : 0,
            'show_birthday_panel' => $normalizedPreferences['show_birthday_panel'] ? 1 : 0,
            'show_promotional_panel' => $normalizedPreferences['show_promotional_panel'] ? 1 : 0,
            'show_campaign_log_panel' => $normalizedPreferences['show_campaign_log_panel'] ? 1 : 0,
            'show_birthday_log_panel' => $normalizedPreferences['show_birthday_log_panel'] ? 1 : 0,
            'show_promotional_log_panel' => $normalizedPreferences['show_promotional_log_panel'] ? 1 : 0,
            'show_subscriber_phone_column' => $normalizedPreferences['show_subscriber_phone_column'] ? 1 : 0,
            'show_subscriber_promotional_column' => $normalizedPreferences['show_subscriber_promotional_column'] ? 1 : 0,
            'show_subscriber_birthday_column' => $normalizedPreferences['show_subscriber_birthday_column'] ? 1 : 0,
            'show_campaign_transport_column' => $normalizedPreferences['show_campaign_transport_column'] ? 1 : 0,
            'show_birthday_transport_column' => $normalizedPreferences['show_birthday_transport_column'] ? 1 : 0,
            'show_promotional_transport_column' => $normalizedPreferences['show_promotional_transport_column'] ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}