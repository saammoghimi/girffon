<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminHomepageTableColumns(PDO $pdo, string $table): array
{
    $columns = [];

    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
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

function girffonAdminHomepageIndexExists(PDO $pdo, string $table, string $indexName): bool
{
    try {
        $statement = $pdo->prepare('SHOW INDEX FROM `' . str_replace('`', '``', $table) . '` WHERE Key_name = :index_name');
        $statement->execute([':index_name' => $indexName]);
        return (bool) $statement->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminHomepageForeignKeyExists(PDO $pdo, string $table, string $constraintName): bool
{
    try {
        $statement = $pdo->prepare(
            'SELECT 1
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND CONSTRAINT_NAME = :constraint_name
               AND CONSTRAINT_TYPE = :constraint_type
             LIMIT 1'
        );
        $statement->execute([
            ':table_name' => $table,
            ':constraint_name' => $constraintName,
            ':constraint_type' => 'FOREIGN KEY',
        ]);
        return (bool) $statement->fetchColumn();
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminEnsureHomepageSiteStateTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS homepage_site_state (
                id TINYINT UNSIGNED NOT NULL,
                site_status VARCHAR(20) NOT NULL DEFAULT 'normal',
                maintenance_enabled TINYINT(1) NOT NULL DEFAULT 0,
                maintenance_title VARCHAR(120) NOT NULL DEFAULT '',
                maintenance_message TEXT NULL,
                maintenance_eta VARCHAR(120) NOT NULL DEFAULT '',
                maintenance_starts_at DATETIME NULL,
                maintenance_ends_at DATETIME NULL,
                admin_bypass_enabled TINYINT(1) NOT NULL DEFAULT 1,
                updated_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
                updated_by_username VARCHAR(191) NOT NULL DEFAULT '',
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminHomepageTableColumns($pdo, 'homepage_site_state');
        $requiredColumns = [
            'site_status' => "ALTER TABLE homepage_site_state ADD COLUMN site_status VARCHAR(20) NOT NULL DEFAULT 'normal' AFTER id",
            'maintenance_enabled' => "ALTER TABLE homepage_site_state ADD COLUMN maintenance_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER site_status",
            'maintenance_title' => "ALTER TABLE homepage_site_state ADD COLUMN maintenance_title VARCHAR(120) NOT NULL DEFAULT '' AFTER maintenance_enabled",
            'maintenance_message' => "ALTER TABLE homepage_site_state ADD COLUMN maintenance_message TEXT NULL AFTER maintenance_title",
            'maintenance_eta' => "ALTER TABLE homepage_site_state ADD COLUMN maintenance_eta VARCHAR(120) NOT NULL DEFAULT '' AFTER maintenance_message",
            'maintenance_starts_at' => "ALTER TABLE homepage_site_state ADD COLUMN maintenance_starts_at DATETIME NULL AFTER maintenance_eta",
            'maintenance_ends_at' => "ALTER TABLE homepage_site_state ADD COLUMN maintenance_ends_at DATETIME NULL AFTER maintenance_starts_at",
            'admin_bypass_enabled' => "ALTER TABLE homepage_site_state ADD COLUMN admin_bypass_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER maintenance_ends_at",
            'updated_by_admin_id' => "ALTER TABLE homepage_site_state ADD COLUMN updated_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER admin_bypass_enabled",
            'updated_by_username' => "ALTER TABLE homepage_site_state ADD COLUMN updated_by_username VARCHAR(191) NOT NULL DEFAULT '' AFTER updated_by_admin_id",
            'updated_at' => "ALTER TABLE homepage_site_state ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by_username",
        ];

        foreach ($requiredColumns as $columnName => $sql) {
            if (!isset($columns[$columnName])) {
                $pdo->exec($sql);
            }
        }

        $statement = $pdo->prepare(
            "INSERT INTO homepage_site_state (
                id,
                site_status,
                maintenance_enabled,
                maintenance_title,
                maintenance_message,
                maintenance_eta,
                maintenance_starts_at,
                maintenance_ends_at,
                admin_bypass_enabled,
                updated_by_admin_id,
                updated_by_username
            )
            SELECT
                1,
                'normal',
                0,
                '',
                NULL,
                '',
                NULL,
                NULL,
                1,
                0,
                'system'
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1
                FROM homepage_site_state
                WHERE id = 1
            )"
        );
        $statement->execute();

        $checked = true;
        return true;
    } catch (PDOException $exception) {
        $checked = false;
        return false;
    }
}

function girffonAdminEnsureHomepageContentItemsTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS homepage_content_items (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                item_type VARCHAR(40) NOT NULL DEFAULT 'announcement_bar',
                title VARCHAR(120) NOT NULL DEFAULT '',
                message TEXT NOT NULL,
                cta_label VARCHAR(50) NOT NULL DEFAULT '',
                cta_url VARCHAR(255) NOT NULL DEFAULT '',
                severity VARCHAR(20) NOT NULL DEFAULT 'info',
                event_key VARCHAR(40) NOT NULL DEFAULT 'none',
                display_mode VARCHAR(40) NOT NULL DEFAULT 'promotion_only',
                display_percent DECIMAL(5,2) UNSIGNED NULL DEFAULT NULL,
                coupon_code VARCHAR(50) NOT NULL DEFAULT '',
                related_product_scope LONGTEXT NULL,
                target_surface VARCHAR(40) NOT NULL DEFAULT 'above_hero',
                audience_scope VARCHAR(40) NOT NULL DEFAULT 'all_visitors',
                start_at DATETIME NULL,
                end_at DATETIME NULL,
                auto_expire TINYINT(1) NOT NULL DEFAULT 1,
                priority SMALLINT UNSIGNED NOT NULL DEFAULT 50,
                workflow_status VARCHAR(20) NOT NULL DEFAULT 'draft',
                is_enabled TINYINT(1) NOT NULL DEFAULT 0,
                published_at DATETIME NULL,
                internal_notes TEXT NULL,
                created_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
                created_by_username VARCHAR(191) NOT NULL DEFAULT '',
                updated_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
                updated_by_username VARCHAR(191) NOT NULL DEFAULT '',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminHomepageTableColumns($pdo, 'homepage_content_items');
        $requiredColumns = [
            'item_type' => "ALTER TABLE homepage_content_items ADD COLUMN item_type VARCHAR(40) NOT NULL DEFAULT 'announcement_bar' AFTER id",
            'title' => "ALTER TABLE homepage_content_items ADD COLUMN title VARCHAR(120) NOT NULL DEFAULT '' AFTER item_type",
            'message' => "ALTER TABLE homepage_content_items ADD COLUMN message TEXT NOT NULL AFTER title",
            'cta_label' => "ALTER TABLE homepage_content_items ADD COLUMN cta_label VARCHAR(50) NOT NULL DEFAULT '' AFTER message",
            'cta_url' => "ALTER TABLE homepage_content_items ADD COLUMN cta_url VARCHAR(255) NOT NULL DEFAULT '' AFTER cta_label",
            'severity' => "ALTER TABLE homepage_content_items ADD COLUMN severity VARCHAR(20) NOT NULL DEFAULT 'info' AFTER cta_url",
            'event_key' => "ALTER TABLE homepage_content_items ADD COLUMN event_key VARCHAR(40) NOT NULL DEFAULT 'none' AFTER severity",
            'display_mode' => "ALTER TABLE homepage_content_items ADD COLUMN display_mode VARCHAR(40) NOT NULL DEFAULT 'promotion_only' AFTER event_key",
            'display_percent' => "ALTER TABLE homepage_content_items ADD COLUMN display_percent DECIMAL(5,2) UNSIGNED NULL DEFAULT NULL AFTER display_mode",
            'coupon_code' => "ALTER TABLE homepage_content_items ADD COLUMN coupon_code VARCHAR(50) NOT NULL DEFAULT '' AFTER display_percent",
            'related_product_scope' => "ALTER TABLE homepage_content_items ADD COLUMN related_product_scope LONGTEXT NULL AFTER coupon_code",
            'target_surface' => "ALTER TABLE homepage_content_items ADD COLUMN target_surface VARCHAR(40) NOT NULL DEFAULT 'above_hero' AFTER related_product_scope",
            'audience_scope' => "ALTER TABLE homepage_content_items ADD COLUMN audience_scope VARCHAR(40) NOT NULL DEFAULT 'all_visitors' AFTER target_surface",
            'start_at' => "ALTER TABLE homepage_content_items ADD COLUMN start_at DATETIME NULL AFTER audience_scope",
            'end_at' => "ALTER TABLE homepage_content_items ADD COLUMN end_at DATETIME NULL AFTER start_at",
            'auto_expire' => "ALTER TABLE homepage_content_items ADD COLUMN auto_expire TINYINT(1) NOT NULL DEFAULT 1 AFTER end_at",
            'priority' => "ALTER TABLE homepage_content_items ADD COLUMN priority SMALLINT UNSIGNED NOT NULL DEFAULT 50 AFTER auto_expire",
            'workflow_status' => "ALTER TABLE homepage_content_items ADD COLUMN workflow_status VARCHAR(20) NOT NULL DEFAULT 'draft' AFTER priority",
            'is_enabled' => "ALTER TABLE homepage_content_items ADD COLUMN is_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER workflow_status",
            'published_at' => "ALTER TABLE homepage_content_items ADD COLUMN published_at DATETIME NULL AFTER is_enabled",
            'internal_notes' => "ALTER TABLE homepage_content_items ADD COLUMN internal_notes TEXT NULL AFTER published_at",
            'created_by_admin_id' => "ALTER TABLE homepage_content_items ADD COLUMN created_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER internal_notes",
            'created_by_username' => "ALTER TABLE homepage_content_items ADD COLUMN created_by_username VARCHAR(191) NOT NULL DEFAULT '' AFTER created_by_admin_id",
            'updated_by_admin_id' => "ALTER TABLE homepage_content_items ADD COLUMN updated_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER created_by_username",
            'updated_by_username' => "ALTER TABLE homepage_content_items ADD COLUMN updated_by_username VARCHAR(191) NOT NULL DEFAULT '' AFTER updated_by_admin_id",
            'created_at' => "ALTER TABLE homepage_content_items ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER updated_by_username",
            'updated_at' => "ALTER TABLE homepage_content_items ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        ];

        foreach ($requiredColumns as $columnName => $sql) {
            if (!isset($columns[$columnName])) {
                $pdo->exec($sql);
            }
        }

        $requiredIndexes = [
            'idx_hci_conflict' => 'ALTER TABLE homepage_content_items ADD KEY idx_hci_conflict (item_type, target_surface, workflow_status, is_enabled, start_at, end_at)',
            'idx_hci_public_surface' => 'ALTER TABLE homepage_content_items ADD KEY idx_hci_public_surface (workflow_status, is_enabled, target_surface, priority, updated_at)',
            'idx_hci_public_type' => 'ALTER TABLE homepage_content_items ADD KEY idx_hci_public_type (item_type, workflow_status, is_enabled, priority, updated_at)',
            'idx_hci_workflow_updated' => 'ALTER TABLE homepage_content_items ADD KEY idx_hci_workflow_updated (workflow_status, updated_at)',
            'idx_hci_event_key' => 'ALTER TABLE homepage_content_items ADD KEY idx_hci_event_key (event_key)',
            'idx_hci_published_at' => 'ALTER TABLE homepage_content_items ADD KEY idx_hci_published_at (published_at)',
        ];

        foreach ($requiredIndexes as $indexName => $sql) {
            if (!girffonAdminHomepageIndexExists($pdo, 'homepage_content_items', $indexName)) {
                $pdo->exec($sql);
            }
        }

        $checked = true;
        return true;
    } catch (PDOException $exception) {
        $checked = false;
        return false;
    }
}

function girffonAdminEnsureHomepageContentHistoryTable(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS homepage_content_history (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                content_item_id INT UNSIGNED NOT NULL,
                action_type VARCHAR(30) NOT NULL,
                snapshot_json LONGTEXT NOT NULL,
                changed_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
                changed_by_username VARCHAR(191) NOT NULL DEFAULT '',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_hch_content_created (content_item_id, created_at),
                KEY idx_hch_action_created (action_type, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminHomepageTableColumns($pdo, 'homepage_content_history');
        $requiredColumns = [
            'content_item_id' => 'ALTER TABLE homepage_content_history ADD COLUMN content_item_id INT UNSIGNED NOT NULL AFTER id',
            'action_type' => 'ALTER TABLE homepage_content_history ADD COLUMN action_type VARCHAR(30) NOT NULL AFTER content_item_id',
            'snapshot_json' => 'ALTER TABLE homepage_content_history ADD COLUMN snapshot_json LONGTEXT NOT NULL AFTER action_type',
            'changed_by_admin_id' => 'ALTER TABLE homepage_content_history ADD COLUMN changed_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER snapshot_json',
            'changed_by_username' => "ALTER TABLE homepage_content_history ADD COLUMN changed_by_username VARCHAR(191) NOT NULL DEFAULT '' AFTER changed_by_admin_id",
            'created_at' => 'ALTER TABLE homepage_content_history ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER changed_by_username',
        ];

        foreach ($requiredColumns as $columnName => $sql) {
            if (!isset($columns[$columnName])) {
                $pdo->exec($sql);
            }
        }

        $requiredIndexes = [
            'idx_hch_content_created' => 'ALTER TABLE homepage_content_history ADD KEY idx_hch_content_created (content_item_id, created_at)',
            'idx_hch_action_created' => 'ALTER TABLE homepage_content_history ADD KEY idx_hch_action_created (action_type, created_at)',
        ];

        foreach ($requiredIndexes as $indexName => $sql) {
            if (!girffonAdminHomepageIndexExists($pdo, 'homepage_content_history', $indexName)) {
                $pdo->exec($sql);
            }
        }

        if (!girffonAdminHomepageForeignKeyExists($pdo, 'homepage_content_history', 'fk_homepage_content_history_item')) {
            $pdo->exec(
                'ALTER TABLE homepage_content_history
                 ADD CONSTRAINT fk_homepage_content_history_item
                 FOREIGN KEY (content_item_id)
                 REFERENCES homepage_content_items(id)
                 ON DELETE CASCADE'
            );
        }

        $checked = true;
        return true;
    } catch (PDOException $exception) {
        $checked = false;
        return false;
    }
}

function girffonAdminEnsureHomepageTables(PDO $pdo): bool
{
    if (!girffonAdminEnsureHomepageSiteStateTable($pdo)) {
        return false;
    }

    if (!girffonAdminEnsureHomepageContentItemsTable($pdo)) {
        return false;
    }

    if (!girffonAdminEnsureHomepageContentHistoryTable($pdo)) {
        return false;
    }

    return true;
}

function girffonAdminHomepageSiteStatusOptions(): array
{
    return ['normal', 'notice', 'maintenance'];
}

function girffonAdminHomepageItemTypeOptions(): array
{
    return ['announcement_bar', 'homepage_campaign', 'technical_alert', 'app_announcement'];
}

function girffonAdminHomepageSeverityOptions(): array
{
    return ['info', 'warning', 'critical'];
}

function girffonAdminHomepageEventKeyOptions(): array
{
    return ['none', 'mothers_day', 'fathers_day', 'valentines_day', 'christmas', 'black_friday', 'cyber_monday', 'new_year', 'custom'];
}

function girffonAdminHomepageDisplayModeOptions(): array
{
    return ['promotion_only', 'linked_product_discounts'];
}

function girffonAdminHomepageTargetSurfaceOptions(): array
{
    return ['top_bar', 'above_hero', 'below_hero'];
}

function girffonAdminHomepageAudienceScopeOptions(): array
{
    return ['all_visitors', 'logged_in'];
}

function girffonAdminHomepageWorkflowStatusOptions(): array
{
    return ['draft', 'published', 'archived'];
}

function girffonAdminHomepageHistoryActionOptions(): array
{
    return ['created', 'updated', 'published', 'unpublished', 'scheduled', 'expired', 'archived', 'cloned', 'restored'];
}

function girffonAdminHomepageAllowedExternalHosts(): array
{
    return ['play.google.com', 'apps.apple.com'];
}

function girffonAdminHomepageNormalizeOption($value, array $allowed, string $fieldName, ?string $default = null): string
{
    $normalized = strtolower(trim((string) $value));
    if ($normalized !== '' && in_array($normalized, $allowed, true)) {
        return $normalized;
    }

    if ($normalized === '' && $default !== null && in_array($default, $allowed, true)) {
        return $default;
    }

    throw new InvalidArgumentException($fieldName . ' is invalid.');
}

function girffonAdminHomepageNormalizeBoolean($value, bool $default = false): bool
{
    if ($value === null || $value === '') {
        return $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

function girffonAdminHomepageNormalizeString($value, string $fieldName, int $maxLength, bool $allowEmpty = true): string
{
    $normalized = trim((string) $value);
    if (!$allowEmpty && $normalized === '') {
        throw new InvalidArgumentException($fieldName . ' is required.');
    }

    if (function_exists('mb_strlen')) {
        $length = mb_strlen($normalized, 'UTF-8');
    } else {
        $length = strlen($normalized);
    }

    if ($length > $maxLength) {
        throw new InvalidArgumentException($fieldName . ' exceeds the maximum length of ' . $maxLength . ' characters.');
    }

    return $normalized;
}

function girffonAdminHomepageNormalizeText($value, string $fieldName, int $maxLength, bool $allowEmpty = true): string
{
    return girffonAdminHomepageNormalizeString($value, $fieldName, $maxLength, $allowEmpty);
}

function girffonAdminHomepageNormalizeUtcDateTimeValue($value): ?string
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }

    try {
        $date = new DateTimeImmutable($raw, new DateTimeZone('UTC'));
    } catch (Exception $exception) {
        throw new InvalidArgumentException('Date value is invalid.');
    }

    return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function girffonAdminHomepageNormalizeDateRange(?string $startAt, ?string $endAt): array
{
    if ($startAt !== null && $endAt !== null) {
        if (girffonAdminHomepageUtcTimestamp($endAt) <= girffonAdminHomepageUtcTimestamp($startAt)) {
            throw new InvalidArgumentException('End date must be after the start date.');
        }
    }

    return [$startAt, $endAt];
}

function girffonAdminHomepageNormalizePercent($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        throw new InvalidArgumentException('Display percent is invalid.');
    }

    $percent = round((float) $value, 2);
    if ($percent < 0 || $percent > 100) {
        throw new InvalidArgumentException('Display percent must be between 0 and 100.');
    }

    return $percent;
}

function girffonAdminHomepageNormalizePriority($value): int
{
    if ($value === null || $value === '') {
        return 50;
    }

    if (!is_numeric($value)) {
        throw new InvalidArgumentException('Priority is invalid.');
    }

    $priority = (int) round((float) $value);
    if ($priority < 0 || $priority > 1000) {
        throw new InvalidArgumentException('Priority must be between 0 and 1000.');
    }

    return $priority;
}

function girffonAdminHomepageNormalizeRelatedProductScope($value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_array($value)) {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new InvalidArgumentException('Related product scope could not be encoded.');
        }

        return $encoded;
    }

    $normalized = trim((string) $value);
    return $normalized !== '' ? $normalized : null;
}

function girffonAdminHomepageValidateUrl($value): string
{
    $url = trim((string) $value);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    if ($parts === false) {
        throw new InvalidArgumentException('CTA URL is invalid.');
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (in_array($scheme, ['javascript', 'data', 'vbscript'], true)) {
        throw new InvalidArgumentException('CTA URL scheme is not allowed.');
    }

    $host = strtolower((string) ($parts['host'] ?? ''));
    if ($host === '') {
        if (str_starts_with($url, '//') || str_starts_with($url, '\\')) {
            throw new InvalidArgumentException('CTA URL must be an internal GIRFFON path or an approved external URL.');
        }

        return girffonAdminHomepageNormalizeString($url, 'CTA URL', 255, true);
    }

    if ($scheme !== 'https') {
        throw new InvalidArgumentException('External CTA URLs must use HTTPS.');
    }

    $approvedHosts = girffonAdminHomepageAllowedExternalHosts();
    if (!in_array($host, $approvedHosts, true)) {
        throw new InvalidArgumentException('CTA URL host is not approved.');
    }

    return girffonAdminHomepageNormalizeString($url, 'CTA URL', 255, true);
}

function girffonAdminHomepageNormalizeActor(int $adminId = 0, string $adminUsername = ''): array
{
    $normalizedId = max(0, $adminId);
    $normalizedUsername = trim($adminUsername) !== '' ? trim($adminUsername) : 'GirffoN Admin';

    return [
        'admin_id' => $normalizedId,
        'admin_username' => $normalizedUsername,
    ];
}

function girffonAdminHomepageUtcNow(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

function girffonAdminHomepageRomeTimezone(): DateTimeZone
{
    static $timezone = null;
    if ($timezone instanceof DateTimeZone) {
        return $timezone;
    }

    $timezone = new DateTimeZone('Europe/Rome');
    return $timezone;
}

function girffonAdminHomepageUtcTimezone(): DateTimeZone
{
    static $timezone = null;
    if ($timezone instanceof DateTimeZone) {
        return $timezone;
    }

    $timezone = new DateTimeZone('UTC');
    return $timezone;
}

function girffonAdminHomepageRomeCurrent(string $format = 'd M Y · H:i'): string
{
    return (new DateTimeImmutable('now', girffonAdminHomepageRomeTimezone()))->format($format);
}

function girffonAdminHomepageFormatRome(?string $value, string $format = 'd M Y · H:i'): string
{
    $normalized = trim((string) $value);
    if ($normalized === '') {
        return '-';
    }

    try {
        $date = new DateTimeImmutable($normalized, girffonAdminHomepageUtcTimezone());
        return $date->setTimezone(girffonAdminHomepageRomeTimezone())->format($format);
    } catch (Exception $exception) {
        return $normalized;
    }
}

function girffonAdminHomepageUtcToRomeInputValue(?string $value): string
{
    $normalized = trim((string) $value);
    if ($normalized === '') {
        return '';
    }

    try {
        $date = new DateTimeImmutable($normalized, girffonAdminHomepageUtcTimezone());
        return $date->setTimezone(girffonAdminHomepageRomeTimezone())->format('Y-m-d\TH:i');
    } catch (Exception $exception) {
        return '';
    }
}

function girffonAdminHomepageRomeInputToUtcValue($value): ?string
{
    $normalized = trim((string) $value);
    if ($normalized === '') {
        return null;
    }

    try {
        $date = new DateTimeImmutable($normalized, girffonAdminHomepageRomeTimezone());
    } catch (Exception $exception) {
        throw new InvalidArgumentException('Date value is invalid.');
    }

    return $date->setTimezone(girffonAdminHomepageUtcTimezone())->format('Y-m-d H:i:s');
}

function girffonAdminHomepageUtcTimestamp(?string $value): ?int
{
    $normalized = trim((string) $value);
    if ($normalized === '') {
        return null;
    }

    return (new DateTimeImmutable($normalized, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('UTC'))
        ->getTimestamp();
}

function girffonAdminHomepageComputePublicState(array $item, ?DateTimeImmutable $now = null): string
{
    $now = $now ?: girffonAdminHomepageUtcNow();
    $workflowStatus = girffonAdminHomepageNormalizeOption($item['workflow_status'] ?? 'draft', girffonAdminHomepageWorkflowStatusOptions(), 'workflow_status', 'draft');
    $isEnabled = !empty($item['is_enabled']);

    if ($workflowStatus !== 'published' || !$isEnabled) {
        return 'inactive';
    }

    $startAt = trim((string) ($item['start_at'] ?? ''));
    $endAt = trim((string) ($item['end_at'] ?? ''));
    $autoExpire = !empty($item['auto_expire']);

    $startTimestamp = girffonAdminHomepageUtcTimestamp($startAt);
    $endTimestamp = girffonAdminHomepageUtcTimestamp($endAt);

    if ($startTimestamp !== null && $startTimestamp > $now->getTimestamp()) {
        return 'scheduled';
    }

    if ($autoExpire && $endTimestamp !== null && $endTimestamp < $now->getTimestamp()) {
        return 'expired';
    }

    return 'active';
}

function girffonAdminHomepageComputeSiteMaintenanceState(array $siteState, ?DateTimeImmutable $now = null): string
{
    $now = $now ?: girffonAdminHomepageUtcNow();
    $siteStatus = girffonAdminHomepageNormalizeOption($siteState['site_status'] ?? 'normal', girffonAdminHomepageSiteStatusOptions(), 'site_status', 'normal');
    $maintenanceEnabled = !empty($siteState['maintenance_enabled']);

    if ($siteStatus !== 'maintenance' || !$maintenanceEnabled) {
        return 'inactive';
    }

    $startAt = trim((string) ($siteState['maintenance_starts_at'] ?? ''));
    $endAt = trim((string) ($siteState['maintenance_ends_at'] ?? ''));
    $startTimestamp = girffonAdminHomepageUtcTimestamp($startAt);
    $endTimestamp = girffonAdminHomepageUtcTimestamp($endAt);

    if ($startTimestamp !== null && $startTimestamp > $now->getTimestamp()) {
        return 'scheduled';
    }

    if ($endTimestamp !== null && $endTimestamp < $now->getTimestamp()) {
        return 'expired';
    }

    return 'active';
}

function girffonAdminHomepageFetchSiteState(PDO $pdo): array
{
    if (!girffonAdminEnsureHomepageTables($pdo)) {
        throw new RuntimeException('Unable to ensure Homepage tables.');
    }

    $siteStateCount = (int) $pdo->query('SELECT COUNT(*) FROM homepage_site_state')->fetchColumn();
    if ($siteStateCount > 1) {
        throw new RuntimeException('Homepage site state integrity error: multiple site-state rows detected.');
    }

    $statement = $pdo->prepare('SELECT * FROM homepage_site_state WHERE id = 1 LIMIT 1');
    $statement->execute();
    $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    if (!$row) {
        throw new RuntimeException('Homepage site state is unavailable.');
    }

    $row['maintenance_public_state'] = girffonAdminHomepageComputeSiteMaintenanceState($row);
    return $row;
}

function girffonAdminHomepageNormalizeSiteStatePayload(array $input, ?array $existing = null): array
{
    $existing = $existing ?: [];
    $siteStatus = girffonAdminHomepageNormalizeOption($input['site_status'] ?? ($existing['site_status'] ?? 'normal'), girffonAdminHomepageSiteStatusOptions(), 'site_status', 'normal');
    $maintenanceEnabled = girffonAdminHomepageNormalizeBoolean($input['maintenance_enabled'] ?? ($existing['maintenance_enabled'] ?? false));
    $maintenanceTitle = girffonAdminHomepageNormalizeString($input['maintenance_title'] ?? ($existing['maintenance_title'] ?? ''), 'Maintenance title', 120, true);
    $maintenanceMessage = girffonAdminHomepageNormalizeText($input['maintenance_message'] ?? ($existing['maintenance_message'] ?? ''), 'Maintenance message', 1000, true);
    $maintenanceEta = girffonAdminHomepageNormalizeString($input['maintenance_eta'] ?? ($existing['maintenance_eta'] ?? ''), 'Maintenance ETA', 120, true);
    $maintenanceStartsAt = girffonAdminHomepageNormalizeUtcDateTimeValue($input['maintenance_starts_at'] ?? ($existing['maintenance_starts_at'] ?? ''));
    $maintenanceEndsAt = girffonAdminHomepageNormalizeUtcDateTimeValue($input['maintenance_ends_at'] ?? ($existing['maintenance_ends_at'] ?? ''));
    [$maintenanceStartsAt, $maintenanceEndsAt] = girffonAdminHomepageNormalizeDateRange($maintenanceStartsAt, $maintenanceEndsAt);
    $adminBypassEnabled = girffonAdminHomepageNormalizeBoolean($input['admin_bypass_enabled'] ?? ($existing['admin_bypass_enabled'] ?? true), true);

    if ($siteStatus !== 'maintenance') {
        $maintenanceEnabled = false;
    }

    return [
        'id' => 1,
        'site_status' => $siteStatus,
        'maintenance_enabled' => $maintenanceEnabled ? 1 : 0,
        'maintenance_title' => $maintenanceTitle,
        'maintenance_message' => $maintenanceMessage !== '' ? $maintenanceMessage : null,
        'maintenance_eta' => $maintenanceEta,
        'maintenance_starts_at' => $maintenanceStartsAt,
        'maintenance_ends_at' => $maintenanceEndsAt,
        'admin_bypass_enabled' => $adminBypassEnabled ? 1 : 0,
    ];
}

function girffonAdminHomepageUpdateSiteState(PDO $pdo, array $input, int $adminId = 0, string $adminUsername = ''): array
{
    $existing = girffonAdminHomepageFetchSiteState($pdo);
    $normalized = girffonAdminHomepageNormalizeSiteStatePayload($input, $existing);
    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);

    $statement = $pdo->prepare(
        'UPDATE homepage_site_state
         SET site_status = :site_status,
             maintenance_enabled = :maintenance_enabled,
             maintenance_title = :maintenance_title,
             maintenance_message = :maintenance_message,
             maintenance_eta = :maintenance_eta,
             maintenance_starts_at = :maintenance_starts_at,
             maintenance_ends_at = :maintenance_ends_at,
             admin_bypass_enabled = :admin_bypass_enabled,
             updated_by_admin_id = :updated_by_admin_id,
             updated_by_username = :updated_by_username
         WHERE id = 1'
    );
    $statement->execute([
        ':site_status' => $normalized['site_status'],
        ':maintenance_enabled' => $normalized['maintenance_enabled'],
        ':maintenance_title' => $normalized['maintenance_title'],
        ':maintenance_message' => $normalized['maintenance_message'],
        ':maintenance_eta' => $normalized['maintenance_eta'],
        ':maintenance_starts_at' => $normalized['maintenance_starts_at'],
        ':maintenance_ends_at' => $normalized['maintenance_ends_at'],
        ':admin_bypass_enabled' => $normalized['admin_bypass_enabled'],
        ':updated_by_admin_id' => $actor['admin_id'],
        ':updated_by_username' => $actor['admin_username'],
    ]);

    return girffonAdminHomepageFetchSiteState($pdo);
}

function girffonAdminHomepageNormalizeContentPayload(array $input, ?array $existing = null): array
{
    $existing = $existing ?: [];

    $itemType = girffonAdminHomepageNormalizeOption($input['item_type'] ?? ($existing['item_type'] ?? 'announcement_bar'), girffonAdminHomepageItemTypeOptions(), 'item_type', 'announcement_bar');
    $title = girffonAdminHomepageNormalizeString($input['title'] ?? ($existing['title'] ?? ''), 'Title', 120, true);
    $message = girffonAdminHomepageNormalizeText($input['message'] ?? ($existing['message'] ?? ''), 'Message', 1000, false);
    $ctaLabel = girffonAdminHomepageNormalizeString($input['cta_label'] ?? ($existing['cta_label'] ?? ''), 'CTA label', 50, true);
    $ctaUrl = girffonAdminHomepageValidateUrl($input['cta_url'] ?? ($existing['cta_url'] ?? ''));
    $severity = girffonAdminHomepageNormalizeOption($input['severity'] ?? ($existing['severity'] ?? 'info'), girffonAdminHomepageSeverityOptions(), 'severity', 'info');
    $eventKey = girffonAdminHomepageNormalizeOption($input['event_key'] ?? ($existing['event_key'] ?? 'none'), girffonAdminHomepageEventKeyOptions(), 'event_key', 'none');
    $displayMode = girffonAdminHomepageNormalizeOption($input['display_mode'] ?? ($existing['display_mode'] ?? 'promotion_only'), girffonAdminHomepageDisplayModeOptions(), 'display_mode', 'promotion_only');
    $displayPercent = girffonAdminHomepageNormalizePercent($input['display_percent'] ?? ($existing['display_percent'] ?? null));
    $couponCode = girffonAdminHomepageNormalizeString($input['coupon_code'] ?? ($existing['coupon_code'] ?? ''), 'Coupon code', 50, true);
    $relatedProductScope = girffonAdminHomepageNormalizeRelatedProductScope($input['related_product_scope'] ?? ($existing['related_product_scope'] ?? null));
    $targetSurface = girffonAdminHomepageNormalizeOption($input['target_surface'] ?? ($existing['target_surface'] ?? 'above_hero'), girffonAdminHomepageTargetSurfaceOptions(), 'target_surface', 'above_hero');
    $audienceScope = girffonAdminHomepageNormalizeOption($input['audience_scope'] ?? ($existing['audience_scope'] ?? 'all_visitors'), girffonAdminHomepageAudienceScopeOptions(), 'audience_scope', 'all_visitors');
    $startAt = girffonAdminHomepageNormalizeUtcDateTimeValue($input['start_at'] ?? ($existing['start_at'] ?? ''));
    $endAt = girffonAdminHomepageNormalizeUtcDateTimeValue($input['end_at'] ?? ($existing['end_at'] ?? ''));
    [$startAt, $endAt] = girffonAdminHomepageNormalizeDateRange($startAt, $endAt);
    $autoExpire = girffonAdminHomepageNormalizeBoolean($input['auto_expire'] ?? ($existing['auto_expire'] ?? true), true);
    $priority = girffonAdminHomepageNormalizePriority($input['priority'] ?? ($existing['priority'] ?? 50));
    $workflowStatus = girffonAdminHomepageNormalizeOption($input['workflow_status'] ?? ($existing['workflow_status'] ?? 'draft'), girffonAdminHomepageWorkflowStatusOptions(), 'workflow_status', 'draft');
    $isEnabled = girffonAdminHomepageNormalizeBoolean($input['is_enabled'] ?? ($existing['is_enabled'] ?? false));
    $publishedAt = girffonAdminHomepageNormalizeUtcDateTimeValue($input['published_at'] ?? ($existing['published_at'] ?? ''));
    $internalNotes = girffonAdminHomepageNormalizeText($input['internal_notes'] ?? ($existing['internal_notes'] ?? ''), 'Internal notes', 2000, true);

    return [
        'item_type' => $itemType,
        'title' => $title,
        'message' => $message,
        'cta_label' => $ctaLabel,
        'cta_url' => $ctaUrl,
        'severity' => $severity,
        'event_key' => $eventKey,
        'display_mode' => $displayMode,
        'display_percent' => $displayPercent,
        'coupon_code' => $couponCode,
        'related_product_scope' => $relatedProductScope,
        'target_surface' => $targetSurface,
        'audience_scope' => $audienceScope,
        'start_at' => $startAt,
        'end_at' => $endAt,
        'auto_expire' => $autoExpire ? 1 : 0,
        'priority' => $priority,
        'workflow_status' => $workflowStatus,
        'is_enabled' => $isEnabled ? 1 : 0,
        'published_at' => $publishedAt,
        'internal_notes' => $internalNotes !== '' ? $internalNotes : null,
    ];
}

function girffonAdminHomepageFetchContentItemById(PDO $pdo, int $itemId): ?array
{
    if ($itemId <= 0) {
        return null;
    }

    if (!girffonAdminEnsureHomepageTables($pdo)) {
        throw new RuntimeException('Unable to ensure Homepage tables.');
    }

    $statement = $pdo->prepare('SELECT * FROM homepage_content_items WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $itemId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
        return null;
    }

    $row['public_state'] = girffonAdminHomepageComputePublicState($row);
    return $row;
}

function girffonAdminHomepageHydrateContentItems(array $items, ?DateTimeImmutable $now = null): array
{
    $now = $now ?: girffonAdminHomepageUtcNow();

    return array_map(static function (array $item) use ($now): array {
        $item['public_state'] = girffonAdminHomepageComputePublicState($item, $now);
        return $item;
    }, $items);
}

function girffonAdminHomepageListContentItems(PDO $pdo, array $filters = []): array
{
    if (!girffonAdminEnsureHomepageTables($pdo)) {
        throw new RuntimeException('Unable to ensure Homepage tables.');
    }

    $sql = 'SELECT * FROM homepage_content_items WHERE 1=1';
    $params = [];

    if (!empty($filters['item_type'])) {
        $sql .= ' AND item_type = :item_type';
        $params[':item_type'] = girffonAdminHomepageNormalizeOption($filters['item_type'], girffonAdminHomepageItemTypeOptions(), 'item_type');
    }

    if (!empty($filters['workflow_status'])) {
        $sql .= ' AND workflow_status = :workflow_status';
        $params[':workflow_status'] = girffonAdminHomepageNormalizeOption($filters['workflow_status'], girffonAdminHomepageWorkflowStatusOptions(), 'workflow_status');
    }

    if (!empty($filters['target_surface'])) {
        $sql .= ' AND target_surface = :target_surface';
        $params[':target_surface'] = girffonAdminHomepageNormalizeOption($filters['target_surface'], girffonAdminHomepageTargetSurfaceOptions(), 'target_surface');
    }

    if (!empty($filters['audience_scope'])) {
        $sql .= ' AND audience_scope = :audience_scope';
        $params[':audience_scope'] = girffonAdminHomepageNormalizeOption($filters['audience_scope'], girffonAdminHomepageAudienceScopeOptions(), 'audience_scope');
    }

    if (empty($filters['include_archived'])) {
        $sql .= " AND workflow_status <> 'archived'";
    }

    $sql .= ' ORDER BY priority DESC, updated_at DESC, id DESC';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $items = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $items = girffonAdminHomepageHydrateContentItems($items);

    if (!empty($filters['public_state'])) {
        $publicState = strtolower(trim((string) $filters['public_state']));
        $items = array_values(array_filter($items, static function (array $item) use ($publicState): bool {
            return strtolower((string) ($item['public_state'] ?? 'inactive')) === $publicState;
        }));
    }

    return $items;
}

function girffonAdminHomepageTimeWindowsOverlap(?string $candidateStartAt, ?string $candidateEndAt, ?string $existingStartAt, ?string $existingEndAt): bool
{
    $candidateStart = girffonAdminHomepageUtcTimestamp($candidateStartAt);
    $candidateEnd = girffonAdminHomepageUtcTimestamp($candidateEndAt);
    $existingStart = girffonAdminHomepageUtcTimestamp($existingStartAt);
    $existingEnd = girffonAdminHomepageUtcTimestamp($existingEndAt);

    $candidateStartValue = $candidateStart ?? PHP_INT_MIN;
    $candidateEndValue = $candidateEnd ?? PHP_INT_MAX;
    $existingStartValue = $existingStart ?? PHP_INT_MIN;
    $existingEndValue = $existingEnd ?? PHP_INT_MAX;

    return max($candidateStartValue, $existingStartValue) < min($candidateEndValue, $existingEndValue);
}

function girffonAdminHomepageDetectContentConflicts(PDO $pdo, array $candidate, ?int $excludeId = null, ?DateTimeImmutable $now = null): array
{
    if (!girffonAdminEnsureHomepageTables($pdo)) {
        throw new RuntimeException('Unable to ensure Homepage tables.');
    }

    $now = $now ?: girffonAdminHomepageUtcNow();
    $itemType = girffonAdminHomepageNormalizeOption($candidate['item_type'] ?? 'announcement_bar', girffonAdminHomepageItemTypeOptions(), 'item_type', 'announcement_bar');
    $targetSurface = girffonAdminHomepageNormalizeOption($candidate['target_surface'] ?? 'above_hero', girffonAdminHomepageTargetSurfaceOptions(), 'target_surface', 'above_hero');

    $sql = "SELECT *
            FROM homepage_content_items
            WHERE item_type = :item_type
              AND target_surface = :target_surface
              AND workflow_status = 'published'
              AND is_enabled = 1";
    $params = [
        ':item_type' => $itemType,
        ':target_surface' => $targetSurface,
    ];

    if ($excludeId !== null && $excludeId > 0) {
        $sql .= ' AND id <> :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }

    $sql .= ' ORDER BY priority DESC, updated_at DESC, id DESC';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $candidateStartAt = $candidate['start_at'] ?? null;
    $candidateEndAt = $candidate['end_at'] ?? null;
    $conflicts = [];

    foreach ($rows as $row) {
        $existingState = girffonAdminHomepageComputePublicState($row, $now);
        if (!in_array($existingState, ['scheduled', 'active'], true)) {
            continue;
        }

        $existingStartAt = trim((string) ($row['start_at'] ?? '')) !== '' ? (string) $row['start_at'] : null;
        $existingEndAt = trim((string) ($row['end_at'] ?? '')) !== '' ? (string) $row['end_at'] : null;

        if (!girffonAdminHomepageTimeWindowsOverlap($candidateStartAt, $candidateEndAt, $existingStartAt, $existingEndAt)) {
            continue;
        }

        $conflicts[] = [
            'id' => (int) ($row['id'] ?? 0),
            'title' => (string) ($row['title'] ?? ''),
            'item_type' => (string) ($row['item_type'] ?? ''),
            'target_surface' => (string) ($row['target_surface'] ?? ''),
            'workflow_status' => (string) ($row['workflow_status'] ?? ''),
            'public_state' => $existingState,
            'start_at' => $existingStartAt,
            'end_at' => $existingEndAt,
            'priority' => (int) ($row['priority'] ?? 0),
        ];
    }

    return $conflicts;
}

function girffonAdminHomepageBuildConflictResult(array $conflicts, bool $blocking): array
{
    return [
        'has_conflicts' => !empty($conflicts),
        'blocking' => $blocking && !empty($conflicts),
        'requires_confirmation' => !$blocking && !empty($conflicts),
        'conflicts' => $conflicts,
    ];
}

function girffonAdminHomepageBuildSnapshot(array $item, ?DateTimeImmutable $now = null): array
{
    $snapshot = $item;
    $snapshot['public_state'] = girffonAdminHomepageComputePublicState($item, $now ?: girffonAdminHomepageUtcNow());
    return $snapshot;
}

function girffonAdminHomepageWriteHistory(PDO $pdo, int $contentItemId, string $actionType, array $snapshot, int $adminId = 0, string $adminUsername = ''): bool
{
    $normalizedAction = girffonAdminHomepageNormalizeOption($actionType, girffonAdminHomepageHistoryActionOptions(), 'history action type');
    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);
    $encodedSnapshot = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encodedSnapshot === false) {
        throw new RuntimeException('Unable to encode Homepage history snapshot.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO homepage_content_history (
            content_item_id,
            action_type,
            snapshot_json,
            changed_by_admin_id,
            changed_by_username
         ) VALUES (
            :content_item_id,
            :action_type,
            :snapshot_json,
            :changed_by_admin_id,
            :changed_by_username
         )'
    );

    return $statement->execute([
        ':content_item_id' => $contentItemId,
        ':action_type' => $normalizedAction,
        ':snapshot_json' => $encodedSnapshot,
        ':changed_by_admin_id' => $actor['admin_id'],
        ':changed_by_username' => $actor['admin_username'],
    ]);
}

function girffonAdminHomepageFetchHistory(PDO $pdo, int $contentItemId, int $limit = 100): array
{
    if ($contentItemId <= 0) {
        return [];
    }

    if (!girffonAdminEnsureHomepageTables($pdo)) {
        throw new RuntimeException('Unable to ensure Homepage tables.');
    }

    $sql = 'SELECT * FROM homepage_content_history WHERE content_item_id = :content_item_id ORDER BY created_at DESC, id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([':content_item_id' => $contentItemId]);
    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function girffonAdminHomepageCreateDraft(PDO $pdo, array $input, int $adminId = 0, string $adminUsername = ''): array
{
    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);
    $normalized = girffonAdminHomepageNormalizeContentPayload(array_merge($input, [
        'workflow_status' => 'draft',
        'is_enabled' => 0,
        'published_at' => null,
    ]));
    $conflicts = girffonAdminHomepageDetectContentConflicts($pdo, $normalized);

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'INSERT INTO homepage_content_items (
                item_type,
                title,
                message,
                cta_label,
                cta_url,
                severity,
                event_key,
                display_mode,
                display_percent,
                coupon_code,
                related_product_scope,
                target_surface,
                audience_scope,
                start_at,
                end_at,
                auto_expire,
                priority,
                workflow_status,
                is_enabled,
                published_at,
                internal_notes,
                created_by_admin_id,
                created_by_username,
                updated_by_admin_id,
                updated_by_username
             ) VALUES (
                :item_type,
                :title,
                :message,
                :cta_label,
                :cta_url,
                :severity,
                :event_key,
                :display_mode,
                :display_percent,
                :coupon_code,
                :related_product_scope,
                :target_surface,
                :audience_scope,
                :start_at,
                :end_at,
                :auto_expire,
                :priority,
                :workflow_status,
                :is_enabled,
                :published_at,
                :internal_notes,
                :created_by_admin_id,
                :created_by_username,
                :updated_by_admin_id,
                :updated_by_username
             )'
        );
        $statement->execute([
            ':item_type' => $normalized['item_type'],
            ':title' => $normalized['title'],
            ':message' => $normalized['message'],
            ':cta_label' => $normalized['cta_label'],
            ':cta_url' => $normalized['cta_url'],
            ':severity' => $normalized['severity'],
            ':event_key' => $normalized['event_key'],
            ':display_mode' => $normalized['display_mode'],
            ':display_percent' => $normalized['display_percent'],
            ':coupon_code' => $normalized['coupon_code'],
            ':related_product_scope' => $normalized['related_product_scope'],
            ':target_surface' => $normalized['target_surface'],
            ':audience_scope' => $normalized['audience_scope'],
            ':start_at' => $normalized['start_at'],
            ':end_at' => $normalized['end_at'],
            ':auto_expire' => $normalized['auto_expire'],
            ':priority' => $normalized['priority'],
            ':workflow_status' => 'draft',
            ':is_enabled' => 0,
            ':published_at' => null,
            ':internal_notes' => $normalized['internal_notes'],
            ':created_by_admin_id' => $actor['admin_id'],
            ':created_by_username' => $actor['admin_username'],
            ':updated_by_admin_id' => $actor['admin_id'],
            ':updated_by_username' => $actor['admin_username'],
        ]);

        $itemId = (int) $pdo->lastInsertId();
        $item = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
        if (!$item) {
            throw new RuntimeException('Unable to load the saved Homepage content item.');
        }

        girffonAdminHomepageWriteHistory($pdo, $itemId, 'created', girffonAdminHomepageBuildSnapshot($item), $actor['admin_id'], $actor['admin_username']);
        $pdo->commit();

        return [
            'item' => $item,
            'conflict_result' => girffonAdminHomepageBuildConflictResult($conflicts, false),
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function girffonAdminHomepageUpdateContentItem(PDO $pdo, int $itemId, array $input, int $adminId = 0, string $adminUsername = ''): array
{
    $existing = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
    if (!$existing) {
        throw new InvalidArgumentException('Homepage content item not found.');
    }

    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);
    $normalized = girffonAdminHomepageNormalizeContentPayload(array_merge($existing, $input), $existing);
    $normalized['workflow_status'] = (string) ($existing['workflow_status'] ?? 'draft');
    $normalized['is_enabled'] = !empty($existing['is_enabled']) ? 1 : 0;
    $normalized['published_at'] = trim((string) ($existing['published_at'] ?? '')) !== '' ? (string) $existing['published_at'] : null;
    $conflicts = girffonAdminHomepageDetectContentConflicts($pdo, $normalized, $itemId);

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'UPDATE homepage_content_items
             SET item_type = :item_type,
                 title = :title,
                 message = :message,
                 cta_label = :cta_label,
                 cta_url = :cta_url,
                 severity = :severity,
                 event_key = :event_key,
                 display_mode = :display_mode,
                 display_percent = :display_percent,
                 coupon_code = :coupon_code,
                 related_product_scope = :related_product_scope,
                 target_surface = :target_surface,
                 audience_scope = :audience_scope,
                 start_at = :start_at,
                 end_at = :end_at,
                 auto_expire = :auto_expire,
                 priority = :priority,
                 internal_notes = :internal_notes,
                 updated_by_admin_id = :updated_by_admin_id,
                 updated_by_username = :updated_by_username
             WHERE id = :id'
        );
        $statement->execute([
            ':item_type' => $normalized['item_type'],
            ':title' => $normalized['title'],
            ':message' => $normalized['message'],
            ':cta_label' => $normalized['cta_label'],
            ':cta_url' => $normalized['cta_url'],
            ':severity' => $normalized['severity'],
            ':event_key' => $normalized['event_key'],
            ':display_mode' => $normalized['display_mode'],
            ':display_percent' => $normalized['display_percent'],
            ':coupon_code' => $normalized['coupon_code'],
            ':related_product_scope' => $normalized['related_product_scope'],
            ':target_surface' => $normalized['target_surface'],
            ':audience_scope' => $normalized['audience_scope'],
            ':start_at' => $normalized['start_at'],
            ':end_at' => $normalized['end_at'],
            ':auto_expire' => $normalized['auto_expire'],
            ':priority' => $normalized['priority'],
            ':internal_notes' => $normalized['internal_notes'],
            ':updated_by_admin_id' => $actor['admin_id'],
            ':updated_by_username' => $actor['admin_username'],
            ':id' => $itemId,
        ]);

        $item = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
        if (!$item) {
            throw new RuntimeException('Unable to load the updated Homepage content item.');
        }

        girffonAdminHomepageWriteHistory($pdo, $itemId, 'updated', girffonAdminHomepageBuildSnapshot($item), $actor['admin_id'], $actor['admin_username']);
        $pdo->commit();

        return [
            'item' => $item,
            'conflict_result' => girffonAdminHomepageBuildConflictResult($conflicts, false),
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function girffonAdminHomepagePublishNow(PDO $pdo, int $itemId, array $input = [], int $adminId = 0, string $adminUsername = '', bool $force = false): array
{
    $existing = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
    if (!$existing) {
        throw new InvalidArgumentException('Homepage content item not found.');
    }

    $now = girffonAdminHomepageUtcNow();
    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);
    $merged = array_merge($existing, $input, [
        'workflow_status' => 'published',
        'is_enabled' => 1,
        'published_at' => $now->format('Y-m-d H:i:s'),
        'start_at' => $input['start_at'] ?? $now->format('Y-m-d H:i:s'),
    ]);
    $normalized = girffonAdminHomepageNormalizeContentPayload($merged, $existing);
    $conflicts = girffonAdminHomepageDetectContentConflicts($pdo, $normalized, $itemId, $now);

    if ($conflicts && !$force) {
        return [
            'item' => $existing,
            'conflict_result' => girffonAdminHomepageBuildConflictResult($conflicts, false),
        ];
    }

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'UPDATE homepage_content_items
             SET item_type = :item_type,
                 title = :title,
                 message = :message,
                 cta_label = :cta_label,
                 cta_url = :cta_url,
                 severity = :severity,
                 event_key = :event_key,
                 display_mode = :display_mode,
                 display_percent = :display_percent,
                 coupon_code = :coupon_code,
                 related_product_scope = :related_product_scope,
                 target_surface = :target_surface,
                 audience_scope = :audience_scope,
                 start_at = :start_at,
                 end_at = :end_at,
                 auto_expire = :auto_expire,
                 priority = :priority,
                 workflow_status = :workflow_status,
                 is_enabled = :is_enabled,
                 published_at = :published_at,
                 internal_notes = :internal_notes,
                 updated_by_admin_id = :updated_by_admin_id,
                 updated_by_username = :updated_by_username
             WHERE id = :id'
        );
        $statement->execute([
            ':item_type' => $normalized['item_type'],
            ':title' => $normalized['title'],
            ':message' => $normalized['message'],
            ':cta_label' => $normalized['cta_label'],
            ':cta_url' => $normalized['cta_url'],
            ':severity' => $normalized['severity'],
            ':event_key' => $normalized['event_key'],
            ':display_mode' => $normalized['display_mode'],
            ':display_percent' => $normalized['display_percent'],
            ':coupon_code' => $normalized['coupon_code'],
            ':related_product_scope' => $normalized['related_product_scope'],
            ':target_surface' => $normalized['target_surface'],
            ':audience_scope' => $normalized['audience_scope'],
            ':start_at' => $normalized['start_at'],
            ':end_at' => $normalized['end_at'],
            ':auto_expire' => $normalized['auto_expire'],
            ':priority' => $normalized['priority'],
            ':workflow_status' => 'published',
            ':is_enabled' => 1,
            ':published_at' => $normalized['published_at'],
            ':internal_notes' => $normalized['internal_notes'],
            ':updated_by_admin_id' => $actor['admin_id'],
            ':updated_by_username' => $actor['admin_username'],
            ':id' => $itemId,
        ]);

        $item = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
        if (!$item) {
            throw new RuntimeException('Unable to load the published Homepage content item.');
        }

        girffonAdminHomepageWriteHistory($pdo, $itemId, 'published', girffonAdminHomepageBuildSnapshot($item, $now), $actor['admin_id'], $actor['admin_username']);
        $pdo->commit();

        return [
            'item' => $item,
            'conflict_result' => girffonAdminHomepageBuildConflictResult($conflicts, false),
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function girffonAdminHomepageSchedulePublish(PDO $pdo, int $itemId, array $input, int $adminId = 0, string $adminUsername = '', bool $force = false): array
{
    $existing = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
    if (!$existing) {
        throw new InvalidArgumentException('Homepage content item not found.');
    }

    $now = girffonAdminHomepageUtcNow();
    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);
    $merged = array_merge($existing, $input, [
        'workflow_status' => 'published',
        'is_enabled' => 1,
        'published_at' => $input['published_at'] ?? $now->format('Y-m-d H:i:s'),
    ]);
    $normalized = girffonAdminHomepageNormalizeContentPayload($merged, $existing);

    if (empty($normalized['start_at'])) {
        throw new InvalidArgumentException('Schedule publish requires a future start date.');
    }

    if (girffonAdminHomepageUtcTimestamp((string) $normalized['start_at']) <= $now->getTimestamp()) {
        throw new InvalidArgumentException('Schedule publish requires a future start date.');
    }

    $conflicts = girffonAdminHomepageDetectContentConflicts($pdo, $normalized, $itemId, $now);
    if ($conflicts && !$force) {
        return [
            'item' => $existing,
            'conflict_result' => girffonAdminHomepageBuildConflictResult($conflicts, false),
        ];
    }

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'UPDATE homepage_content_items
             SET item_type = :item_type,
                 title = :title,
                 message = :message,
                 cta_label = :cta_label,
                 cta_url = :cta_url,
                 severity = :severity,
                 event_key = :event_key,
                 display_mode = :display_mode,
                 display_percent = :display_percent,
                 coupon_code = :coupon_code,
                 related_product_scope = :related_product_scope,
                 target_surface = :target_surface,
                 audience_scope = :audience_scope,
                 start_at = :start_at,
                 end_at = :end_at,
                 auto_expire = :auto_expire,
                 priority = :priority,
                 workflow_status = :workflow_status,
                 is_enabled = :is_enabled,
                 published_at = :published_at,
                 internal_notes = :internal_notes,
                 updated_by_admin_id = :updated_by_admin_id,
                 updated_by_username = :updated_by_username
             WHERE id = :id'
        );
        $statement->execute([
            ':item_type' => $normalized['item_type'],
            ':title' => $normalized['title'],
            ':message' => $normalized['message'],
            ':cta_label' => $normalized['cta_label'],
            ':cta_url' => $normalized['cta_url'],
            ':severity' => $normalized['severity'],
            ':event_key' => $normalized['event_key'],
            ':display_mode' => $normalized['display_mode'],
            ':display_percent' => $normalized['display_percent'],
            ':coupon_code' => $normalized['coupon_code'],
            ':related_product_scope' => $normalized['related_product_scope'],
            ':target_surface' => $normalized['target_surface'],
            ':audience_scope' => $normalized['audience_scope'],
            ':start_at' => $normalized['start_at'],
            ':end_at' => $normalized['end_at'],
            ':auto_expire' => $normalized['auto_expire'],
            ':priority' => $normalized['priority'],
            ':workflow_status' => 'published',
            ':is_enabled' => 1,
            ':published_at' => $normalized['published_at'],
            ':internal_notes' => $normalized['internal_notes'],
            ':updated_by_admin_id' => $actor['admin_id'],
            ':updated_by_username' => $actor['admin_username'],
            ':id' => $itemId,
        ]);

        $item = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
        if (!$item) {
            throw new RuntimeException('Unable to load the scheduled Homepage content item.');
        }

        girffonAdminHomepageWriteHistory($pdo, $itemId, 'scheduled', girffonAdminHomepageBuildSnapshot($item, $now), $actor['admin_id'], $actor['admin_username']);
        $pdo->commit();

        return [
            'item' => $item,
            'conflict_result' => girffonAdminHomepageBuildConflictResult($conflicts, false),
        ];
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function girffonAdminHomepageUnpublishContentItem(PDO $pdo, int $itemId, int $adminId = 0, string $adminUsername = ''): array
{
    $existing = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
    if (!$existing) {
        throw new InvalidArgumentException('Homepage content item not found.');
    }

    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'UPDATE homepage_content_items
             SET is_enabled = 0,
                 updated_by_admin_id = :updated_by_admin_id,
                 updated_by_username = :updated_by_username
             WHERE id = :id'
        );
        $statement->execute([
            ':updated_by_admin_id' => $actor['admin_id'],
            ':updated_by_username' => $actor['admin_username'],
            ':id' => $itemId,
        ]);

        $item = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
        if (!$item) {
            throw new RuntimeException('Unable to load the unpublished Homepage content item.');
        }

        girffonAdminHomepageWriteHistory($pdo, $itemId, 'unpublished', girffonAdminHomepageBuildSnapshot($item), $actor['admin_id'], $actor['admin_username']);
        $pdo->commit();

        return $item;
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function girffonAdminHomepageArchiveContentItem(PDO $pdo, int $itemId, int $adminId = 0, string $adminUsername = ''): array
{
    $existing = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
    if (!$existing) {
        throw new InvalidArgumentException('Homepage content item not found.');
    }

    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'UPDATE homepage_content_items
             SET workflow_status = :workflow_status,
                 is_enabled = 0,
                 updated_by_admin_id = :updated_by_admin_id,
                 updated_by_username = :updated_by_username
             WHERE id = :id'
        );
        $statement->execute([
            ':workflow_status' => 'archived',
            ':updated_by_admin_id' => $actor['admin_id'],
            ':updated_by_username' => $actor['admin_username'],
            ':id' => $itemId,
        ]);

        $item = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
        if (!$item) {
            throw new RuntimeException('Unable to load the archived Homepage content item.');
        }

        girffonAdminHomepageWriteHistory($pdo, $itemId, 'archived', girffonAdminHomepageBuildSnapshot($item), $actor['admin_id'], $actor['admin_username']);
        $pdo->commit();

        return $item;
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function girffonAdminHomepageCloneContentItem(PDO $pdo, int $itemId, int $adminId = 0, string $adminUsername = ''): array
{
    $existing = girffonAdminHomepageFetchContentItemById($pdo, $itemId);
    if (!$existing) {
        throw new InvalidArgumentException('Homepage content item not found.');
    }

    $actor = girffonAdminHomepageNormalizeActor($adminId, $adminUsername);

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'INSERT INTO homepage_content_items (
                item_type,
                title,
                message,
                cta_label,
                cta_url,
                severity,
                event_key,
                display_mode,
                display_percent,
                coupon_code,
                related_product_scope,
                target_surface,
                audience_scope,
                start_at,
                end_at,
                auto_expire,
                priority,
                workflow_status,
                is_enabled,
                published_at,
                internal_notes,
                created_by_admin_id,
                created_by_username,
                updated_by_admin_id,
                updated_by_username
             ) VALUES (
                :item_type,
                :title,
                :message,
                :cta_label,
                :cta_url,
                :severity,
                :event_key,
                :display_mode,
                :display_percent,
                :coupon_code,
                :related_product_scope,
                :target_surface,
                :audience_scope,
                :start_at,
                :end_at,
                :auto_expire,
                :priority,
                :workflow_status,
                :is_enabled,
                :published_at,
                :internal_notes,
                :created_by_admin_id,
                :created_by_username,
                :updated_by_admin_id,
                :updated_by_username
             )'
        );
        $statement->execute([
            ':item_type' => $existing['item_type'],
            ':title' => $existing['title'],
            ':message' => $existing['message'],
            ':cta_label' => $existing['cta_label'],
            ':cta_url' => $existing['cta_url'],
            ':severity' => $existing['severity'],
            ':event_key' => $existing['event_key'],
            ':display_mode' => $existing['display_mode'],
            ':display_percent' => $existing['display_percent'] !== '' ? $existing['display_percent'] : null,
            ':coupon_code' => $existing['coupon_code'],
            ':related_product_scope' => $existing['related_product_scope'],
            ':target_surface' => $existing['target_surface'],
            ':audience_scope' => $existing['audience_scope'],
            ':start_at' => $existing['start_at'] !== '' ? $existing['start_at'] : null,
            ':end_at' => $existing['end_at'] !== '' ? $existing['end_at'] : null,
            ':auto_expire' => !empty($existing['auto_expire']) ? 1 : 0,
            ':priority' => (int) ($existing['priority'] ?? 50),
            ':workflow_status' => 'draft',
            ':is_enabled' => 0,
            ':published_at' => null,
            ':internal_notes' => $existing['internal_notes'],
            ':created_by_admin_id' => $actor['admin_id'],
            ':created_by_username' => $actor['admin_username'],
            ':updated_by_admin_id' => $actor['admin_id'],
            ':updated_by_username' => $actor['admin_username'],
        ]);

        $newItemId = (int) $pdo->lastInsertId();
        $newItem = girffonAdminHomepageFetchContentItemById($pdo, $newItemId);
        if (!$newItem) {
            throw new RuntimeException('Unable to load the cloned Homepage content item.');
        }

        girffonAdminHomepageWriteHistory($pdo, $newItemId, 'cloned', girffonAdminHomepageBuildSnapshot($newItem), $actor['admin_id'], $actor['admin_username']);
        $pdo->commit();

        return $newItem;
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}