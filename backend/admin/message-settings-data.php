<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminMessageSettingsDefault(): array
{
    return [
        'show_messages_overview' => true,
        'show_summary_cards' => true,
        'show_search_filters' => true,
        'show_message_list' => true,
        'show_subject_column' => true,
        'show_preview_column' => true,
        'show_status_column' => true,
        'show_date_column' => true,
        'show_view_action' => true,
        'show_mark_read_action' => true,
        'show_delete_action' => true,
        'show_contact_tools' => true,
    ];
}

function girffonAdminMessageSettingsColumns(PDO $pdo): array
{
    $columns = [];

    try {
        $statement = $pdo->query("SHOW COLUMNS FROM admin_message_settings");
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

function girffonAdminMessageSettingsKey(int $adminId, string $username): string
{
    if ($adminId > 0) {
        return 'admin:' . $adminId;
    }

    $normalizedUsername = strtolower(trim($username));
    return 'user:' . ($normalizedUsername !== '' ? $normalizedUsername : 'girffon-admin');
}

function girffonAdminNormalizeMessagePreferences(array $preferences): array
{
    $defaults = girffonAdminMessageSettingsDefault();
    $normalized = [];

    foreach ($defaults as $key => $defaultValue) {
        $normalized[$key] = array_key_exists($key, $preferences)
            ? filter_var($preferences[$key], FILTER_VALIDATE_BOOLEAN)
            : $defaultValue;
    }

    return $normalized;
}

function girffonAdminEnsureMessageSettingsTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_message_settings (
                admin_key VARCHAR(191) NOT NULL PRIMARY KEY,
                admin_id INT NOT NULL DEFAULT 0,
                admin_username VARCHAR(191) NOT NULL DEFAULT '',
                show_messages_overview TINYINT(1) NOT NULL DEFAULT 1,
                show_summary_cards TINYINT(1) NOT NULL DEFAULT 1,
                show_search_filters TINYINT(1) NOT NULL DEFAULT 1,
                show_message_list TINYINT(1) NOT NULL DEFAULT 1,
                show_subject_column TINYINT(1) NOT NULL DEFAULT 1,
                show_preview_column TINYINT(1) NOT NULL DEFAULT 1,
                show_status_column TINYINT(1) NOT NULL DEFAULT 1,
                show_date_column TINYINT(1) NOT NULL DEFAULT 1,
                show_view_action TINYINT(1) NOT NULL DEFAULT 1,
                show_mark_read_action TINYINT(1) NOT NULL DEFAULT 1,
                show_delete_action TINYINT(1) NOT NULL DEFAULT 1,
                show_contact_tools TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminMessageSettingsColumns($pdo);
        $requiredColumns = [
            'show_subject_column' => "ALTER TABLE admin_message_settings ADD COLUMN show_subject_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_message_list",
            'show_preview_column' => "ALTER TABLE admin_message_settings ADD COLUMN show_preview_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_subject_column",
            'show_status_column' => "ALTER TABLE admin_message_settings ADD COLUMN show_status_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_preview_column",
            'show_date_column' => "ALTER TABLE admin_message_settings ADD COLUMN show_date_column TINYINT(1) NOT NULL DEFAULT 1 AFTER show_status_column",
            'show_view_action' => "ALTER TABLE admin_message_settings ADD COLUMN show_view_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_date_column",
            'show_mark_read_action' => "ALTER TABLE admin_message_settings ADD COLUMN show_mark_read_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_view_action",
            'show_delete_action' => "ALTER TABLE admin_message_settings ADD COLUMN show_delete_action TINYINT(1) NOT NULL DEFAULT 1 AFTER show_mark_read_action",
            'show_contact_tools' => "ALTER TABLE admin_message_settings ADD COLUMN show_contact_tools TINYINT(1) NOT NULL DEFAULT 1 AFTER show_delete_action",
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

function girffonAdminFetchMessagePreferences(PDO $pdo, int $adminId, string $username): array
{
    $defaults = girffonAdminMessageSettingsDefault();
    if (!girffonAdminEnsureMessageSettingsTable($pdo)) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                show_messages_overview,
                show_summary_cards,
                show_search_filters,
                show_message_list,
                show_subject_column,
                show_preview_column,
                show_status_column,
                show_date_column,
                show_view_action,
                show_mark_read_action,
                show_delete_action,
                show_contact_tools
             FROM admin_message_settings
             WHERE admin_key = :admin_key
             LIMIT 1"
        );
        $statement->execute([
            'admin_key' => girffonAdminMessageSettingsKey($adminId, $username),
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $defaults;
        }

        return girffonAdminNormalizeMessagePreferences($row);
    } catch (PDOException $exception) {
        return $defaults;
    }
}

function girffonAdminSaveMessagePreferences(PDO $pdo, int $adminId, string $username, array $preferences): bool
{
    if (!girffonAdminEnsureMessageSettingsTable($pdo)) {
        return false;
    }

    $normalizedPreferences = girffonAdminNormalizeMessagePreferences($preferences);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO admin_message_settings (
                admin_key,
                admin_id,
                admin_username,
                show_messages_overview,
                show_summary_cards,
                show_search_filters,
                show_message_list,
                show_subject_column,
                show_preview_column,
                show_status_column,
                show_date_column,
                show_view_action,
                show_mark_read_action,
                show_delete_action,
                show_contact_tools
            ) VALUES (
                :admin_key,
                :admin_id,
                :admin_username,
                :show_messages_overview,
                :show_summary_cards,
                :show_search_filters,
                :show_message_list,
                :show_subject_column,
                :show_preview_column,
                :show_status_column,
                :show_date_column,
                :show_view_action,
                :show_mark_read_action,
                :show_delete_action,
                :show_contact_tools
            ) ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                admin_username = VALUES(admin_username),
                show_messages_overview = VALUES(show_messages_overview),
                show_summary_cards = VALUES(show_summary_cards),
                show_search_filters = VALUES(show_search_filters),
                show_message_list = VALUES(show_message_list),
                show_subject_column = VALUES(show_subject_column),
                show_preview_column = VALUES(show_preview_column),
                show_status_column = VALUES(show_status_column),
                show_date_column = VALUES(show_date_column),
                show_view_action = VALUES(show_view_action),
                show_mark_read_action = VALUES(show_mark_read_action),
                show_delete_action = VALUES(show_delete_action),
                show_contact_tools = VALUES(show_contact_tools)"
        );

        return $statement->execute([
            'admin_key' => girffonAdminMessageSettingsKey($adminId, $username),
            'admin_id' => max(0, $adminId),
            'admin_username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
            'show_messages_overview' => $normalizedPreferences['show_messages_overview'] ? 1 : 0,
            'show_summary_cards' => $normalizedPreferences['show_summary_cards'] ? 1 : 0,
            'show_search_filters' => $normalizedPreferences['show_search_filters'] ? 1 : 0,
            'show_message_list' => $normalizedPreferences['show_message_list'] ? 1 : 0,
            'show_subject_column' => $normalizedPreferences['show_subject_column'] ? 1 : 0,
            'show_preview_column' => $normalizedPreferences['show_preview_column'] ? 1 : 0,
            'show_status_column' => $normalizedPreferences['show_status_column'] ? 1 : 0,
            'show_date_column' => $normalizedPreferences['show_date_column'] ? 1 : 0,
            'show_view_action' => $normalizedPreferences['show_view_action'] ? 1 : 0,
            'show_mark_read_action' => $normalizedPreferences['show_mark_read_action'] ? 1 : 0,
            'show_delete_action' => $normalizedPreferences['show_delete_action'] ? 1 : 0,
            'show_contact_tools' => $normalizedPreferences['show_contact_tools'] ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}