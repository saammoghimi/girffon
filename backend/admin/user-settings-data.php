<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminUserSettingsDefault(): array
{
    return [
        'show_summary_cards' => true,
        'show_filter_panel' => true,
        'show_users_directory' => true,
        'show_username_column' => true,
        'show_email_column' => true,
        'show_phone_column' => true,
        'show_country_column' => true,
        'show_city_column' => true,
        'show_address_column' => true,
        'show_role_column' => true,
        'show_status_column' => true,
        'show_created_at_column' => true,
        'show_view_action' => true,
        'show_edit_action' => true,
        'show_orders_action' => true,
        'show_invoices_action' => true,
        'show_email_action' => true,
        'show_sms_action' => true,
        'show_delete_action' => true,
    ];
}

function girffonAdminUserSettingsColumns(PDO $pdo): array
{
    $columns = [];

    try {
        $statement = $pdo->query("SHOW COLUMNS FROM admin_user_settings");
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

function girffonAdminUserSettingsKey(int $adminId, string $username): string
{
    if ($adminId > 0) {
        return 'admin:' . $adminId;
    }

    $normalizedUsername = strtolower(trim($username));
    return 'user:' . ($normalizedUsername !== '' ? $normalizedUsername : 'girffon-admin');
}

function girffonAdminNormalizeUserPreferences(array $preferences): array
{
    $defaults = girffonAdminUserSettingsDefault();
    $normalized = [];

    foreach ($defaults as $key => $defaultValue) {
        $normalized[$key] = array_key_exists($key, $preferences)
            ? filter_var($preferences[$key], FILTER_VALIDATE_BOOLEAN)
            : $defaultValue;
    }

    return $normalized;
}

function girffonAdminEnsureUserSettingsTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_user_settings (
                admin_key VARCHAR(191) NOT NULL PRIMARY KEY,
                admin_id INT NOT NULL DEFAULT 0,
                admin_username VARCHAR(191) NOT NULL DEFAULT '',
                show_summary_cards TINYINT(1) NOT NULL DEFAULT 1,
                show_filter_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_users_directory TINYINT(1) NOT NULL DEFAULT 1,
                show_username_column TINYINT(1) NOT NULL DEFAULT 1,
                show_email_column TINYINT(1) NOT NULL DEFAULT 1,
                show_phone_column TINYINT(1) NOT NULL DEFAULT 1,
                show_country_column TINYINT(1) NOT NULL DEFAULT 1,
                show_city_column TINYINT(1) NOT NULL DEFAULT 1,
                show_address_column TINYINT(1) NOT NULL DEFAULT 1,
                show_role_column TINYINT(1) NOT NULL DEFAULT 1,
                show_status_column TINYINT(1) NOT NULL DEFAULT 1,
                show_created_at_column TINYINT(1) NOT NULL DEFAULT 1,
                show_view_action TINYINT(1) NOT NULL DEFAULT 1,
                show_edit_action TINYINT(1) NOT NULL DEFAULT 1,
                show_orders_action TINYINT(1) NOT NULL DEFAULT 1,
                show_invoices_action TINYINT(1) NOT NULL DEFAULT 1,
                show_email_action TINYINT(1) NOT NULL DEFAULT 1,
                show_sms_action TINYINT(1) NOT NULL DEFAULT 1,
                show_delete_action TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminUserSettingsColumns($pdo);
        $requiredColumns = [
            'show_username_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_username_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_users_directory",
            'show_email_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_email_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_username_column",
            'show_phone_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_phone_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_email_column",
            'show_country_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_country_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_phone_column",
            'show_city_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_city_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_country_column",
            'show_address_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_address_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_city_column",
            'show_role_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_role_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_address_column",
            'show_status_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_status_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_role_column",
            'show_created_at_column' => "ALTER TABLE admin_user_settings ADD COLUMN show_created_at_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_status_column",
            'show_view_action' => "ALTER TABLE admin_user_settings ADD COLUMN show_view_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_created_at_column",
            'show_edit_action' => "ALTER TABLE admin_user_settings ADD COLUMN show_edit_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_view_action",
            'show_orders_action' => "ALTER TABLE admin_user_settings ADD COLUMN show_orders_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_edit_action",
            'show_invoices_action' => "ALTER TABLE admin_user_settings ADD COLUMN show_invoices_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_orders_action",
            'show_email_action' => "ALTER TABLE admin_user_settings ADD COLUMN show_email_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_invoices_action",
            'show_sms_action' => "ALTER TABLE admin_user_settings ADD COLUMN show_sms_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_email_action",
            'show_delete_action' => "ALTER TABLE admin_user_settings ADD COLUMN show_delete_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_sms_action",
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

function girffonAdminFetchUserPreferences(PDO $pdo, int $adminId, string $username): array
{
    $defaults = girffonAdminUserSettingsDefault();
    if (!girffonAdminEnsureUserSettingsTable($pdo)) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                show_summary_cards,
                show_filter_panel,
                show_users_directory,
                show_username_column,
                show_email_column,
                show_phone_column,
                show_country_column,
                show_city_column,
                show_address_column,
                show_role_column,
                show_status_column,
                show_created_at_column,
                show_view_action,
                show_edit_action,
                show_orders_action,
                show_invoices_action,
                show_email_action,
                show_sms_action,
                show_delete_action
             FROM admin_user_settings
             WHERE admin_key = :admin_key
             LIMIT 1"
        );
        $statement->execute([
            'admin_key' => girffonAdminUserSettingsKey($adminId, $username),
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $defaults;
        }

        return girffonAdminNormalizeUserPreferences($row);
    } catch (PDOException $exception) {
        return $defaults;
    }
}

function girffonAdminSaveUserPreferences(PDO $pdo, int $adminId, string $username, array $preferences): bool
{
    if (!girffonAdminEnsureUserSettingsTable($pdo)) {
        return false;
    }

    $normalizedPreferences = girffonAdminNormalizeUserPreferences($preferences);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO admin_user_settings (
                admin_key,
                admin_id,
                admin_username,
                show_summary_cards,
                show_filter_panel,
                show_users_directory,
                show_username_column,
                show_email_column,
                show_phone_column,
                show_country_column,
                show_city_column,
                show_address_column,
                show_role_column,
                show_status_column,
                show_created_at_column,
                show_view_action,
                show_edit_action,
                show_orders_action,
                show_invoices_action,
                show_email_action,
                show_sms_action,
                show_delete_action
            ) VALUES (
                :admin_key,
                :admin_id,
                :admin_username,
                :show_summary_cards,
                :show_filter_panel,
                :show_users_directory,
                :show_username_column,
                :show_email_column,
                :show_phone_column,
                :show_country_column,
                :show_city_column,
                :show_address_column,
                :show_role_column,
                :show_status_column,
                :show_created_at_column,
                :show_view_action,
                :show_edit_action,
                :show_orders_action,
                :show_invoices_action,
                :show_email_action,
                :show_sms_action,
                :show_delete_action
            ) ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                admin_username = VALUES(admin_username),
                show_summary_cards = VALUES(show_summary_cards),
                show_filter_panel = VALUES(show_filter_panel),
                show_users_directory = VALUES(show_users_directory),
                show_username_column = VALUES(show_username_column),
                show_email_column = VALUES(show_email_column),
                show_phone_column = VALUES(show_phone_column),
                show_country_column = VALUES(show_country_column),
                show_city_column = VALUES(show_city_column),
                show_address_column = VALUES(show_address_column),
                show_role_column = VALUES(show_role_column),
                show_status_column = VALUES(show_status_column),
                show_created_at_column = VALUES(show_created_at_column),
                show_view_action = VALUES(show_view_action),
                show_edit_action = VALUES(show_edit_action),
                show_orders_action = VALUES(show_orders_action),
                show_invoices_action = VALUES(show_invoices_action),
                show_email_action = VALUES(show_email_action),
                show_sms_action = VALUES(show_sms_action),
                show_delete_action = VALUES(show_delete_action)"
        );

        return $statement->execute([
            'admin_key' => girffonAdminUserSettingsKey($adminId, $username),
            'admin_id' => max(0, $adminId),
            'admin_username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
            'show_summary_cards' => $normalizedPreferences['show_summary_cards'] ? 1 : 0,
            'show_filter_panel' => $normalizedPreferences['show_filter_panel'] ? 1 : 0,
            'show_users_directory' => $normalizedPreferences['show_users_directory'] ? 1 : 0,
            'show_username_column' => $normalizedPreferences['show_username_column'] ? 1 : 0,
            'show_email_column' => $normalizedPreferences['show_email_column'] ? 1 : 0,
            'show_phone_column' => $normalizedPreferences['show_phone_column'] ? 1 : 0,
            'show_country_column' => $normalizedPreferences['show_country_column'] ? 1 : 0,
            'show_city_column' => $normalizedPreferences['show_city_column'] ? 1 : 0,
            'show_address_column' => $normalizedPreferences['show_address_column'] ? 1 : 0,
            'show_role_column' => $normalizedPreferences['show_role_column'] ? 1 : 0,
            'show_status_column' => $normalizedPreferences['show_status_column'] ? 1 : 0,
            'show_created_at_column' => $normalizedPreferences['show_created_at_column'] ? 1 : 0,
            'show_view_action' => $normalizedPreferences['show_view_action'] ? 1 : 0,
            'show_edit_action' => $normalizedPreferences['show_edit_action'] ? 1 : 0,
            'show_orders_action' => $normalizedPreferences['show_orders_action'] ? 1 : 0,
            'show_invoices_action' => $normalizedPreferences['show_invoices_action'] ? 1 : 0,
            'show_email_action' => $normalizedPreferences['show_email_action'] ? 1 : 0,
            'show_sms_action' => $normalizedPreferences['show_sms_action'] ? 1 : 0,
            'show_delete_action' => $normalizedPreferences['show_delete_action'] ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}