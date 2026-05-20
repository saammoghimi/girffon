<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminCustomOrderSettingsDefault(): array
{
    return [
        'show_summary_cards' => true,
        'show_order_list' => true,
        'show_order_id_column' => true,
        'show_customer_column' => true,
        'show_product_column' => true,
        'show_upload_count_column' => true,
        'show_text_count_column' => true,
        'show_status_column' => true,
        'show_date_column' => true,
        'show_view_action' => true,
    ];
}

function girffonAdminCustomOrderSettingsColumns(PDO $pdo): array
{
    $columns = [];

    try {
        $statement = $pdo->query("SHOW COLUMNS FROM admin_custom_order_settings");
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

function girffonAdminCustomOrderSettingsKey(int $adminId, string $username): string
{
    if ($adminId > 0) {
        return 'admin:' . $adminId;
    }

    $normalizedUsername = strtolower(trim($username));
    return 'user:' . ($normalizedUsername !== '' ? $normalizedUsername : 'girffon-admin');
}

function girffonAdminNormalizeCustomOrderPreferences(array $preferences): array
{
    $defaults = girffonAdminCustomOrderSettingsDefault();
    $normalized = [];

    foreach ($defaults as $key => $defaultValue) {
        $normalized[$key] = array_key_exists($key, $preferences)
            ? filter_var($preferences[$key], FILTER_VALIDATE_BOOLEAN)
            : $defaultValue;
    }

    return $normalized;
}

function girffonAdminEnsureCustomOrderSettingsTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_custom_order_settings (
                admin_key VARCHAR(191) NOT NULL PRIMARY KEY,
                admin_id INT NOT NULL DEFAULT 0,
                admin_username VARCHAR(191) NOT NULL DEFAULT '',
                show_summary_cards TINYINT(1) NOT NULL DEFAULT 1,
                show_order_list TINYINT(1) NOT NULL DEFAULT 1,
                show_order_id_column TINYINT(1) NOT NULL DEFAULT 1,
                show_customer_column TINYINT(1) NOT NULL DEFAULT 1,
                show_product_column TINYINT(1) NOT NULL DEFAULT 1,
                show_upload_count_column TINYINT(1) NOT NULL DEFAULT 1,
                show_text_count_column TINYINT(1) NOT NULL DEFAULT 1,
                show_status_column TINYINT(1) NOT NULL DEFAULT 1,
                show_date_column TINYINT(1) NOT NULL DEFAULT 1,
                show_view_action TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminCustomOrderSettingsColumns($pdo);
        $requiredColumns = [
            'show_order_id_column' => "ALTER TABLE admin_custom_order_settings ADD COLUMN show_order_id_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_order_list",
            'show_customer_column' => "ALTER TABLE admin_custom_order_settings ADD COLUMN show_customer_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_order_id_column",
            'show_product_column' => "ALTER TABLE admin_custom_order_settings ADD COLUMN show_product_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_customer_column",
            'show_upload_count_column' => "ALTER TABLE admin_custom_order_settings ADD COLUMN show_upload_count_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_product_column",
            'show_text_count_column' => "ALTER TABLE admin_custom_order_settings ADD COLUMN show_text_count_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_upload_count_column",
            'show_status_column' => "ALTER TABLE admin_custom_order_settings ADD COLUMN show_status_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_text_count_column",
            'show_date_column' => "ALTER TABLE admin_custom_order_settings ADD COLUMN show_date_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_status_column",
            'show_view_action' => "ALTER TABLE admin_custom_order_settings ADD COLUMN show_view_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_date_column",
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

function girffonAdminFetchCustomOrderPreferences(PDO $pdo, int $adminId, string $username): array
{
    $defaults = girffonAdminCustomOrderSettingsDefault();
    if (!girffonAdminEnsureCustomOrderSettingsTable($pdo)) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                show_summary_cards,
                show_order_list,
                show_order_id_column,
                show_customer_column,
                show_product_column,
                show_upload_count_column,
                show_text_count_column,
                show_status_column,
                show_date_column,
                show_view_action
             FROM admin_custom_order_settings
             WHERE admin_key = :admin_key
             LIMIT 1"
        );
        $statement->execute([
            'admin_key' => girffonAdminCustomOrderSettingsKey($adminId, $username),
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $defaults;
        }

        return girffonAdminNormalizeCustomOrderPreferences($row);
    } catch (PDOException $exception) {
        return $defaults;
    }
}

function girffonAdminSaveCustomOrderPreferences(PDO $pdo, int $adminId, string $username, array $preferences): bool
{
    if (!girffonAdminEnsureCustomOrderSettingsTable($pdo)) {
        return false;
    }

    $normalizedPreferences = girffonAdminNormalizeCustomOrderPreferences($preferences);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO admin_custom_order_settings (
                admin_key,
                admin_id,
                admin_username,
                show_summary_cards,
                show_order_list,
                show_order_id_column,
                show_customer_column,
                show_product_column,
                show_upload_count_column,
                show_text_count_column,
                show_status_column,
                show_date_column,
                show_view_action
            ) VALUES (
                :admin_key,
                :admin_id,
                :admin_username,
                :show_summary_cards,
                :show_order_list,
                :show_order_id_column,
                :show_customer_column,
                :show_product_column,
                :show_upload_count_column,
                :show_text_count_column,
                :show_status_column,
                :show_date_column,
                :show_view_action
            ) ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                admin_username = VALUES(admin_username),
                show_summary_cards = VALUES(show_summary_cards),
                show_order_list = VALUES(show_order_list),
                show_order_id_column = VALUES(show_order_id_column),
                show_customer_column = VALUES(show_customer_column),
                show_product_column = VALUES(show_product_column),
                show_upload_count_column = VALUES(show_upload_count_column),
                show_text_count_column = VALUES(show_text_count_column),
                show_status_column = VALUES(show_status_column),
                show_date_column = VALUES(show_date_column),
                show_view_action = VALUES(show_view_action)"
        );

        return $statement->execute([
            'admin_key' => girffonAdminCustomOrderSettingsKey($adminId, $username),
            'admin_id' => max(0, $adminId),
            'admin_username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
            'show_summary_cards' => $normalizedPreferences['show_summary_cards'] ? 1 : 0,
            'show_order_list' => $normalizedPreferences['show_order_list'] ? 1 : 0,
            'show_order_id_column' => $normalizedPreferences['show_order_id_column'] ? 1 : 0,
            'show_customer_column' => $normalizedPreferences['show_customer_column'] ? 1 : 0,
            'show_product_column' => $normalizedPreferences['show_product_column'] ? 1 : 0,
            'show_upload_count_column' => $normalizedPreferences['show_upload_count_column'] ? 1 : 0,
            'show_text_count_column' => $normalizedPreferences['show_text_count_column'] ? 1 : 0,
            'show_status_column' => $normalizedPreferences['show_status_column'] ? 1 : 0,
            'show_date_column' => $normalizedPreferences['show_date_column'] ? 1 : 0,
            'show_view_action' => $normalizedPreferences['show_view_action'] ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}