<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminOrderSettingsDefault(): array
{
    return [
        'show_orders_overview' => true,
        'show_order_list' => true,
        'show_customer_column' => true,
        'show_payment_method_column' => true,
        'show_payment_status_column' => true,
        'show_order_status_column' => true,
        'show_tracking_column' => true,
        'show_courier_column' => true,
        'show_eta_column' => true,
        'show_admin_note_column' => true,
        'show_created_at_column' => true,
        'show_save_action' => true,
        'show_track_action' => true,
        'show_invoice_action' => true,
    ];
}

function girffonAdminOrderSettingsColumns(PDO $pdo): array
{
    $columns = [];

    try {
        $statement = $pdo->query("SHOW COLUMNS FROM admin_order_settings");
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

function girffonAdminOrderSettingsKey(int $adminId, string $username): string
{
    if ($adminId > 0) {
        return 'admin:' . $adminId;
    }

    $normalizedUsername = strtolower(trim($username));
    return 'user:' . ($normalizedUsername !== '' ? $normalizedUsername : 'girffon-admin');
}

function girffonAdminNormalizeOrderPreferences(array $preferences): array
{
    $defaults = girffonAdminOrderSettingsDefault();
    $normalized = [];

    foreach ($defaults as $key => $defaultValue) {
        $normalized[$key] = array_key_exists($key, $preferences)
            ? filter_var($preferences[$key], FILTER_VALIDATE_BOOLEAN)
            : $defaultValue;
    }

    return $normalized;
}

function girffonAdminEnsureOrderSettingsTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_order_settings (
                admin_key VARCHAR(191) NOT NULL PRIMARY KEY,
                admin_id INT NOT NULL DEFAULT 0,
                admin_username VARCHAR(191) NOT NULL DEFAULT '',
                show_orders_overview TINYINT(1) NOT NULL DEFAULT 1,
                show_order_list TINYINT(1) NOT NULL DEFAULT 1,
                show_customer_column TINYINT(1) NOT NULL DEFAULT 1,
                show_payment_method_column TINYINT(1) NOT NULL DEFAULT 1,
                show_payment_status_column TINYINT(1) NOT NULL DEFAULT 1,
                show_order_status_column TINYINT(1) NOT NULL DEFAULT 1,
                show_tracking_column TINYINT(1) NOT NULL DEFAULT 1,
                show_courier_column TINYINT(1) NOT NULL DEFAULT 1,
                show_eta_column TINYINT(1) NOT NULL DEFAULT 1,
                show_admin_note_column TINYINT(1) NOT NULL DEFAULT 1,
                show_created_at_column TINYINT(1) NOT NULL DEFAULT 1,
                show_save_action TINYINT(1) NOT NULL DEFAULT 1,
                show_track_action TINYINT(1) NOT NULL DEFAULT 1,
                show_invoice_action TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminOrderSettingsColumns($pdo);
        $requiredColumns = [
            'show_customer_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_customer_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_order_list",
            'show_payment_method_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_payment_method_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_customer_column",
            'show_payment_status_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_payment_status_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_payment_method_column",
            'show_order_status_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_order_status_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_payment_status_column",
            'show_tracking_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_tracking_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_order_status_column",
            'show_courier_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_courier_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_tracking_column",
            'show_eta_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_eta_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_courier_column",
            'show_admin_note_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_admin_note_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_eta_column",
            'show_created_at_column' => "ALTER TABLE admin_order_settings ADD COLUMN show_created_at_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_admin_note_column",
            'show_save_action' => "ALTER TABLE admin_order_settings ADD COLUMN show_save_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_created_at_column",
            'show_track_action' => "ALTER TABLE admin_order_settings ADD COLUMN show_track_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_save_action",
            'show_invoice_action' => "ALTER TABLE admin_order_settings ADD COLUMN show_invoice_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_track_action",
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

function girffonAdminFetchOrderPreferences(PDO $pdo, int $adminId, string $username): array
{
    $defaults = girffonAdminOrderSettingsDefault();
    if (!girffonAdminEnsureOrderSettingsTable($pdo)) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                show_orders_overview,
                show_order_list,
                show_customer_column,
                show_payment_method_column,
                show_payment_status_column,
                show_order_status_column,
                show_tracking_column,
                show_courier_column,
                show_eta_column,
                show_admin_note_column,
                show_created_at_column,
                show_save_action,
                show_track_action,
                show_invoice_action
             FROM admin_order_settings
             WHERE admin_key = :admin_key
             LIMIT 1"
        );
        $statement->execute([
            'admin_key' => girffonAdminOrderSettingsKey($adminId, $username),
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $defaults;
        }

        return girffonAdminNormalizeOrderPreferences($row);
    } catch (PDOException $exception) {
        return $defaults;
    }
}

function girffonAdminSaveOrderPreferences(PDO $pdo, int $adminId, string $username, array $preferences): bool
{
    if (!girffonAdminEnsureOrderSettingsTable($pdo)) {
        return false;
    }

    $normalizedPreferences = girffonAdminNormalizeOrderPreferences($preferences);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO admin_order_settings (
                admin_key,
                admin_id,
                admin_username,
                show_orders_overview,
                show_order_list,
                show_customer_column,
                show_payment_method_column,
                show_payment_status_column,
                show_order_status_column,
                show_tracking_column,
                show_courier_column,
                show_eta_column,
                show_admin_note_column,
                show_created_at_column,
                show_save_action,
                show_track_action,
                show_invoice_action
            ) VALUES (
                :admin_key,
                :admin_id,
                :admin_username,
                :show_orders_overview,
                :show_order_list,
                :show_customer_column,
                :show_payment_method_column,
                :show_payment_status_column,
                :show_order_status_column,
                :show_tracking_column,
                :show_courier_column,
                :show_eta_column,
                :show_admin_note_column,
                :show_created_at_column,
                :show_save_action,
                :show_track_action,
                :show_invoice_action
            ) ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                admin_username = VALUES(admin_username),
                show_orders_overview = VALUES(show_orders_overview),
                show_order_list = VALUES(show_order_list),
                show_customer_column = VALUES(show_customer_column),
                show_payment_method_column = VALUES(show_payment_method_column),
                show_payment_status_column = VALUES(show_payment_status_column),
                show_order_status_column = VALUES(show_order_status_column),
                show_tracking_column = VALUES(show_tracking_column),
                show_courier_column = VALUES(show_courier_column),
                show_eta_column = VALUES(show_eta_column),
                show_admin_note_column = VALUES(show_admin_note_column),
                show_created_at_column = VALUES(show_created_at_column),
                show_save_action = VALUES(show_save_action),
                show_track_action = VALUES(show_track_action),
                show_invoice_action = VALUES(show_invoice_action)"
        );

        return $statement->execute([
            'admin_key' => girffonAdminOrderSettingsKey($adminId, $username),
            'admin_id' => max(0, $adminId),
            'admin_username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
            'show_orders_overview' => $normalizedPreferences['show_orders_overview'] ? 1 : 0,
            'show_order_list' => $normalizedPreferences['show_order_list'] ? 1 : 0,
            'show_customer_column' => $normalizedPreferences['show_customer_column'] ? 1 : 0,
            'show_payment_method_column' => $normalizedPreferences['show_payment_method_column'] ? 1 : 0,
            'show_payment_status_column' => $normalizedPreferences['show_payment_status_column'] ? 1 : 0,
            'show_order_status_column' => $normalizedPreferences['show_order_status_column'] ? 1 : 0,
            'show_tracking_column' => $normalizedPreferences['show_tracking_column'] ? 1 : 0,
            'show_courier_column' => $normalizedPreferences['show_courier_column'] ? 1 : 0,
            'show_eta_column' => $normalizedPreferences['show_eta_column'] ? 1 : 0,
            'show_admin_note_column' => $normalizedPreferences['show_admin_note_column'] ? 1 : 0,
            'show_created_at_column' => $normalizedPreferences['show_created_at_column'] ? 1 : 0,
            'show_save_action' => $normalizedPreferences['show_save_action'] ? 1 : 0,
            'show_track_action' => $normalizedPreferences['show_track_action'] ? 1 : 0,
            'show_invoice_action' => $normalizedPreferences['show_invoice_action'] ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}