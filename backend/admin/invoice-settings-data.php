<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminInvoiceSettingsDefault(): array
{
    return [
        'show_add_invoice_panel' => true,
        'show_search_filters' => true,
        'show_invoice_list' => true,
        'show_customer_column' => true,
        'show_tax_column' => true,
        'show_shipping_column' => true,
        'show_status_column' => true,
        'show_created_at_column' => true,
        'show_view_action' => true,
        'show_pdf_action' => true,
        'show_print_action' => true,
    ];
}

function girffonAdminInvoiceSettingsColumns(PDO $pdo): array
{
    $columns = [];

    try {
        $statement = $pdo->query("SHOW COLUMNS FROM admin_invoice_settings");
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

function girffonAdminInvoiceSettingsKey(int $adminId, string $username): string
{
    if ($adminId > 0) {
        return 'admin:' . $adminId;
    }

    $normalizedUsername = strtolower(trim($username));
    return 'user:' . ($normalizedUsername !== '' ? $normalizedUsername : 'girffon-admin');
}

function girffonAdminNormalizeInvoicePreferences(array $preferences): array
{
    $defaults = girffonAdminInvoiceSettingsDefault();
    $normalized = [];

    foreach ($defaults as $key => $defaultValue) {
        $normalized[$key] = array_key_exists($key, $preferences)
            ? filter_var($preferences[$key], FILTER_VALIDATE_BOOLEAN)
            : $defaultValue;
    }

    return $normalized;
}

function girffonAdminEnsureInvoiceSettingsTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_invoice_settings (
                admin_key VARCHAR(191) NOT NULL PRIMARY KEY,
                admin_id INT NOT NULL DEFAULT 0,
                admin_username VARCHAR(191) NOT NULL DEFAULT '',
                show_add_invoice_panel TINYINT(1) NOT NULL DEFAULT 1,
                show_search_filters TINYINT(1) NOT NULL DEFAULT 1,
                show_invoice_list TINYINT(1) NOT NULL DEFAULT 1,
                show_customer_column TINYINT(1) NOT NULL DEFAULT 1,
                show_tax_column TINYINT(1) NOT NULL DEFAULT 1,
                show_shipping_column TINYINT(1) NOT NULL DEFAULT 1,
                show_status_column TINYINT(1) NOT NULL DEFAULT 1,
                show_created_at_column TINYINT(1) NOT NULL DEFAULT 1,
                show_view_action TINYINT(1) NOT NULL DEFAULT 1,
                show_pdf_action TINYINT(1) NOT NULL DEFAULT 1,
                show_print_action TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminInvoiceSettingsColumns($pdo);
        $requiredColumns = [
            'show_customer_column' => "ALTER TABLE admin_invoice_settings ADD COLUMN show_customer_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_invoice_list",
            'show_tax_column' => "ALTER TABLE admin_invoice_settings ADD COLUMN show_tax_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_customer_column",
            'show_shipping_column' => "ALTER TABLE admin_invoice_settings ADD COLUMN show_shipping_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_tax_column",
            'show_status_column' => "ALTER TABLE admin_invoice_settings ADD COLUMN show_status_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_shipping_column",
            'show_created_at_column' => "ALTER TABLE admin_invoice_settings ADD COLUMN show_created_at_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_status_column",
            'show_view_action' => "ALTER TABLE admin_invoice_settings ADD COLUMN show_view_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_created_at_column",
            'show_pdf_action' => "ALTER TABLE admin_invoice_settings ADD COLUMN show_pdf_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_view_action",
            'show_print_action' => "ALTER TABLE admin_invoice_settings ADD COLUMN show_print_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_pdf_action",
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

function girffonAdminFetchInvoicePreferences(PDO $pdo, int $adminId, string $username): array
{
    $defaults = girffonAdminInvoiceSettingsDefault();
    if (!girffonAdminEnsureInvoiceSettingsTable($pdo)) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                show_add_invoice_panel,
                show_search_filters,
                show_invoice_list,
                show_customer_column,
                show_tax_column,
                show_shipping_column,
                show_status_column,
                show_created_at_column,
                show_view_action,
                show_pdf_action,
                show_print_action
             FROM admin_invoice_settings
             WHERE admin_key = :admin_key
             LIMIT 1"
        );
        $statement->execute([
            'admin_key' => girffonAdminInvoiceSettingsKey($adminId, $username),
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $defaults;
        }

        return girffonAdminNormalizeInvoicePreferences($row);
    } catch (PDOException $exception) {
        return $defaults;
    }
}

function girffonAdminSaveInvoicePreferences(PDO $pdo, int $adminId, string $username, array $preferences): bool
{
    if (!girffonAdminEnsureInvoiceSettingsTable($pdo)) {
        return false;
    }

    $normalizedPreferences = girffonAdminNormalizeInvoicePreferences($preferences);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO admin_invoice_settings (
                admin_key,
                admin_id,
                admin_username,
                show_add_invoice_panel,
                show_search_filters,
                show_invoice_list,
                show_customer_column,
                show_tax_column,
                show_shipping_column,
                show_status_column,
                show_created_at_column,
                show_view_action,
                show_pdf_action,
                show_print_action
            ) VALUES (
                :admin_key,
                :admin_id,
                :admin_username,
                :show_add_invoice_panel,
                :show_search_filters,
                :show_invoice_list,
                :show_customer_column,
                :show_tax_column,
                :show_shipping_column,
                :show_status_column,
                :show_created_at_column,
                :show_view_action,
                :show_pdf_action,
                :show_print_action
            ) ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                admin_username = VALUES(admin_username),
                show_add_invoice_panel = VALUES(show_add_invoice_panel),
                show_search_filters = VALUES(show_search_filters),
                show_invoice_list = VALUES(show_invoice_list),
                show_customer_column = VALUES(show_customer_column),
                show_tax_column = VALUES(show_tax_column),
                show_shipping_column = VALUES(show_shipping_column),
                show_status_column = VALUES(show_status_column),
                show_created_at_column = VALUES(show_created_at_column),
                show_view_action = VALUES(show_view_action),
                show_pdf_action = VALUES(show_pdf_action),
                show_print_action = VALUES(show_print_action)"
        );

        return $statement->execute([
            'admin_key' => girffonAdminInvoiceSettingsKey($adminId, $username),
            'admin_id' => max(0, $adminId),
            'admin_username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
            'show_add_invoice_panel' => $normalizedPreferences['show_add_invoice_panel'] ? 1 : 0,
            'show_search_filters' => $normalizedPreferences['show_search_filters'] ? 1 : 0,
            'show_invoice_list' => $normalizedPreferences['show_invoice_list'] ? 1 : 0,
            'show_customer_column' => $normalizedPreferences['show_customer_column'] ? 1 : 0,
            'show_tax_column' => $normalizedPreferences['show_tax_column'] ? 1 : 0,
            'show_shipping_column' => $normalizedPreferences['show_shipping_column'] ? 1 : 0,
            'show_status_column' => $normalizedPreferences['show_status_column'] ? 1 : 0,
            'show_created_at_column' => $normalizedPreferences['show_created_at_column'] ? 1 : 0,
            'show_view_action' => $normalizedPreferences['show_view_action'] ? 1 : 0,
            'show_pdf_action' => $normalizedPreferences['show_pdf_action'] ? 1 : 0,
            'show_print_action' => $normalizedPreferences['show_print_action'] ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}