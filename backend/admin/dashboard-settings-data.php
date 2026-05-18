<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminDashboardSettingsDefault(): array
{
    return [
        'show_summary_cards' => true,
        'show_recent_activity' => true,
        'show_login_activity' => true,
        'show_analytics_explorer' => true,
        'show_weather_widget' => true,
        'show_world_clock' => true,
        'show_active_admins' => true,
        'show_visitor_analytics' => true,
    ];
}

function girffonAdminDashboardSettingsKey(int $adminId, string $username): string
{
    if ($adminId > 0) {
        return 'admin:' . $adminId;
    }

    $normalizedUsername = strtolower(trim($username));
    return 'user:' . ($normalizedUsername !== '' ? $normalizedUsername : 'girffon-admin');
}

function girffonAdminNormalizeDashboardPreferences(array $preferences): array
{
    $defaults = girffonAdminDashboardSettingsDefault();
    $normalized = [];

    foreach ($defaults as $key => $defaultValue) {
        $normalized[$key] = array_key_exists($key, $preferences)
            ? filter_var($preferences[$key], FILTER_VALIDATE_BOOLEAN)
            : $defaultValue;
    }

    return $normalized;
}

function girffonAdminEnsureDashboardSettingsTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_dashboard_settings (
                admin_key VARCHAR(191) NOT NULL PRIMARY KEY,
                admin_id INT NOT NULL DEFAULT 0,
                admin_username VARCHAR(191) NOT NULL DEFAULT '',
                show_summary_cards TINYINT(1) NOT NULL DEFAULT 1,
                show_recent_activity TINYINT(1) NOT NULL DEFAULT 1,
                show_login_activity TINYINT(1) NOT NULL DEFAULT 1,
                show_analytics_explorer TINYINT(1) NOT NULL DEFAULT 1,
                show_weather_widget TINYINT(1) NOT NULL DEFAULT 1,
                show_world_clock TINYINT(1) NOT NULL DEFAULT 1,
                show_active_admins TINYINT(1) NOT NULL DEFAULT 1,
                show_visitor_analytics TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminFetchDashboardPreferences(PDO $pdo, int $adminId, string $username): array
{
    $defaults = girffonAdminDashboardSettingsDefault();
    if (!girffonAdminEnsureDashboardSettingsTable($pdo)) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                show_summary_cards,
                show_recent_activity,
                show_login_activity,
                show_analytics_explorer,
                show_weather_widget,
                show_world_clock,
                show_active_admins,
                show_visitor_analytics
             FROM admin_dashboard_settings
             WHERE admin_key = :admin_key
             LIMIT 1"
        );
        $statement->execute([
            'admin_key' => girffonAdminDashboardSettingsKey($adminId, $username),
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $defaults;
        }

        return girffonAdminNormalizeDashboardPreferences($row);
    } catch (PDOException $exception) {
        return $defaults;
    }
}

function girffonAdminSaveDashboardPreferences(PDO $pdo, int $adminId, string $username, array $preferences): bool
{
    if (!girffonAdminEnsureDashboardSettingsTable($pdo)) {
        return false;
    }

    $normalizedPreferences = girffonAdminNormalizeDashboardPreferences($preferences);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO admin_dashboard_settings (
                admin_key,
                admin_id,
                admin_username,
                show_summary_cards,
                show_recent_activity,
                show_login_activity,
                show_analytics_explorer,
                show_weather_widget,
                show_world_clock,
                show_active_admins,
                show_visitor_analytics
            ) VALUES (
                :admin_key,
                :admin_id,
                :admin_username,
                :show_summary_cards,
                :show_recent_activity,
                :show_login_activity,
                :show_analytics_explorer,
                :show_weather_widget,
                :show_world_clock,
                :show_active_admins,
                :show_visitor_analytics
            ) ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                admin_username = VALUES(admin_username),
                show_summary_cards = VALUES(show_summary_cards),
                show_recent_activity = VALUES(show_recent_activity),
                show_login_activity = VALUES(show_login_activity),
                show_analytics_explorer = VALUES(show_analytics_explorer),
                show_weather_widget = VALUES(show_weather_widget),
                show_world_clock = VALUES(show_world_clock),
                show_active_admins = VALUES(show_active_admins),
                show_visitor_analytics = VALUES(show_visitor_analytics)"
        );

        return $statement->execute([
            'admin_key' => girffonAdminDashboardSettingsKey($adminId, $username),
            'admin_id' => max(0, $adminId),
            'admin_username' => trim($username) !== '' ? trim($username) : 'GirffoN Admin',
            'show_summary_cards' => $normalizedPreferences['show_summary_cards'] ? 1 : 0,
            'show_recent_activity' => $normalizedPreferences['show_recent_activity'] ? 1 : 0,
            'show_login_activity' => $normalizedPreferences['show_login_activity'] ? 1 : 0,
            'show_analytics_explorer' => $normalizedPreferences['show_analytics_explorer'] ? 1 : 0,
            'show_weather_widget' => $normalizedPreferences['show_weather_widget'] ? 1 : 0,
            'show_world_clock' => $normalizedPreferences['show_world_clock'] ? 1 : 0,
            'show_active_admins' => $normalizedPreferences['show_active_admins'] ? 1 : 0,
            'show_visitor_analytics' => $normalizedPreferences['show_visitor_analytics'] ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}