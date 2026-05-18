<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminProductSettingsDefault(): array
{
    return [
        'show_product_form' => true,
        'show_product_list' => true,
        'show_barcode_input' => true,
        'show_description_input' => true,
        'show_sale_price_input' => true,
        'show_image_input' => true,
        'show_barcode_column' => true,
        'show_sale_price_column' => true,
        'show_variant_column' => true,
        'show_status_column' => true,
        'show_edit_action' => true,
        'show_print_action' => true,
        'show_delete_action' => true,
    ];
}

function girffonAdminProductSettingsColumns(PDO $pdo): array
{
    $columns = [];

    try {
        $statement = $pdo->query("SHOW COLUMNS FROM admin_product_settings");
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

function girffonAdminProductSettingsKey(int $adminId, string $username): string
{
    if ($adminId > 0) {
        return 'admin:' . $adminId;
    }

    $normalizedUsername = strtolower(trim($username));
    return 'user:' . ($normalizedUsername !== '' ? $normalizedUsername : 'girffon-admin');
}

function girffonAdminNormalizeProductPreferences(array $preferences): array
{
    $defaults = girffonAdminProductSettingsDefault();
    $normalized = [];

    foreach ($defaults as $key => $defaultValue) {
        $normalized[$key] = array_key_exists($key, $preferences)
            ? filter_var($preferences[$key], FILTER_VALIDATE_BOOLEAN)
            : $defaultValue;
    }

    return $normalized;
}

function girffonAdminEnsureProductSettingsTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_product_settings (
                admin_key VARCHAR(191) NOT NULL PRIMARY KEY,
                admin_id INT NOT NULL DEFAULT 0,
                admin_username VARCHAR(191) NOT NULL DEFAULT '',
                show_product_form TINYINT(1) NOT NULL DEFAULT 1,
                show_product_list TINYINT(1) NOT NULL DEFAULT 1,
                show_barcode_input TINYINT(1) NOT NULL DEFAULT 1,
                show_description_input TINYINT(1) NOT NULL DEFAULT 1,
                show_sale_price_input TINYINT(1) NOT NULL DEFAULT 1,
                show_image_input TINYINT(1) NOT NULL DEFAULT 1,
                show_barcode_column TINYINT(1) NOT NULL DEFAULT 1,
                show_sale_price_column TINYINT(1) NOT NULL DEFAULT 1,
                show_variant_column TINYINT(1) NOT NULL DEFAULT 1,
                show_status_column TINYINT(1) NOT NULL DEFAULT 1,
                show_edit_action TINYINT(1) NOT NULL DEFAULT 1,
                show_print_action TINYINT(1) NOT NULL DEFAULT 1,
                show_delete_action TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminProductSettingsColumns($pdo);
        $requiredColumns = [
            'show_barcode_input' => "ALTER TABLE admin_product_settings ADD COLUMN show_barcode_input TINYINT(1) NOT NULL DEFAULT 1 AFTER show_product_list",
            'show_description_input' => "ALTER TABLE admin_product_settings ADD COLUMN show_description_input TINYINT(1) NOT NULL DEFAULT 1 AFTER show_barcode_input",
            'show_sale_price_input' => "ALTER TABLE admin_product_settings ADD COLUMN show_sale_price_input TINYINT(1) NOT NULL DEFAULT 1 AFTER show_description_input",
            'show_image_input' => "ALTER TABLE admin_product_settings ADD COLUMN show_image_input TINYINT(1) NOT NULL DEFAULT 1 AFTER show_sale_price_input",
            'show_edit_action' => "ALTER TABLE admin_product_settings ADD COLUMN show_edit_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_status_column",
            'show_print_action' => "ALTER TABLE admin_product_settings ADD COLUMN show_print_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_edit_action",
            'show_delete_action' => "ALTER TABLE admin_product_settings ADD COLUMN show_delete_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_print_action",
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

function girffonAdminFetchProductPreferences(PDO $pdo, int $adminId, string $username): array
{
    $defaults = girffonAdminProductSettingsDefault();
    if (!girffonAdminEnsureProductSettingsTable($pdo)) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                show_product_form,
                show_product_list,
                show_barcode_input,
                show_description_input,
                show_sale_price_input,
                show_image_input,
                show_barcode_column,
                show_sale_price_column,
                show_variant_column,
                show_status_column,
                show_edit_action,
                show_print_action,
                show_delete_action
             FROM admin_product_settings
             WHERE admin_key = :admin_key
             LIMIT 1"
        );
        $statement->execute([
            'admin_key' => girffonAdminProductSettingsKey($adminId, $username),
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $defaults;
        }

        return girffonAdminNormalizeProductPreferences($row);
    } catch (PDOException $exception) {
        return $defaults;
    }
}

function girffonAdminSaveProductPreferences(PDO $pdo, int $adminId, string $username, array $preferences): bool
{
    if (!girffonAdminEnsureProductSettingsTable($pdo)) {
        return false;
    }

    $normalizedPreferences = girffonAdminNormalizeProductPreferences($preferences);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO admin_product_settings (
                admin_key,
                admin_id,
                admin_username,
                show_product_form,
                show_product_list,
                show_barcode_input,
                show_description_input,
                show_sale_price_input,
                show_image_input,
                show_barcode_column,
                show_sale_price_column,
                show_variant_column,
                show_status_column,
                show_edit_action,
                show_print_action,
                show_delete_action
            ) VALUES (
                :admin_key,
                :admin_id,
                :admin_username,
                :show_product_form,
                :show_product_list,
                :show_barcode_input,
                :show_description_input,
                :show_sale_price_input,
                :show_image_input,
                :show_barcode_column,
                :show_sale_price_column,
                :show_variant_column,
                :show_status_column,
                :show_edit_action,
                :show_print_action,
                :show_delete_action
            ) ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                admin_username = VALUES(admin_username),
                show_product_form = VALUES(show_product_form),
                show_product_list = VALUES(show_product_list),
                show_barcode_input = VALUES(show_barcode_input),
                show_description_input = VALUES(show_description_input),
                show_sale_price_input = VALUES(show_sale_price_input),
                show_image_input = VALUES(show_image_input),
                show_barcode_column = VALUES(show_barcode_column),
                show_sale_price_column = VALUES(show_sale_price_column),
                show_variant_column = VALUES(show_variant_column),
                show_status_column = VALUES(show_status_column),
                show_edit_action = VALUES(show_edit_action),
                show_print_action = VALUES(show_print_action),
                show_delete_action = VALUES(show_delete_action)"
        );

        return $statement->execute([
            'admin_key' => girffonAdminProductSettingsKey($adminId, $username),
            'admin_id' => max(0, $adminId),
            'admin_username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
            'show_product_form' => $normalizedPreferences['show_product_form'] ? 1 : 0,
            'show_product_list' => $normalizedPreferences['show_product_list'] ? 1 : 0,
            'show_barcode_input' => $normalizedPreferences['show_barcode_input'] ? 1 : 0,
            'show_description_input' => $normalizedPreferences['show_description_input'] ? 1 : 0,
            'show_sale_price_input' => $normalizedPreferences['show_sale_price_input'] ? 1 : 0,
            'show_image_input' => $normalizedPreferences['show_image_input'] ? 1 : 0,
            'show_barcode_column' => $normalizedPreferences['show_barcode_column'] ? 1 : 0,
            'show_sale_price_column' => $normalizedPreferences['show_sale_price_column'] ? 1 : 0,
            'show_variant_column' => $normalizedPreferences['show_variant_column'] ? 1 : 0,
            'show_status_column' => $normalizedPreferences['show_status_column'] ? 1 : 0,
            'show_edit_action' => $normalizedPreferences['show_edit_action'] ? 1 : 0,
            'show_print_action' => $normalizedPreferences['show_print_action'] ? 1 : 0,
            'show_delete_action' => $normalizedPreferences['show_delete_action'] ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}