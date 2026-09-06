<?php
require_once __DIR__ . '/../config/database.php';

function girffonMobileAppContentDefinitions(): array
{
    $definitions = [
        'home-banner-1' => ['home', 'banner', 'Banner 1'],
        'home-banner-2' => ['home', 'banner', 'Banner 2'],
        'home-banner-3' => ['home', 'banner', 'Banner 3'],
        'home-banner-4' => ['home', 'banner', 'Banner 4'],
        'home-banner-5' => ['home', 'banner', 'Banner 5'],
        'home-category-men' => ['home', 'category-buttons', 'Men'],
        'home-category-women' => ['home', 'category-buttons', 'Women'],
        'home-category-boys' => ['home', 'category-buttons', 'Boys'],
        'home-category-girls' => ['home', 'category-buttons', 'Girls'],
        'home-category-accessories' => ['home', 'category-buttons', 'Accessories'],
        'home-category-custom' => ['home', 'category-buttons', 'Custom Design'],
        'home-shopping-cart' => ['home', 'shopping-cart', 'Shopping Cart'],
        'home-custom-design' => ['home', 'custom-design', 'Custom Design'],
        'home-catalog' => ['home', 'catalog', 'Catalog'],
        'home-gift-cards' => ['home', 'gift-cards', 'Gift Cards'],
        'home-bundles' => ['home', 'bundles', 'Bundles'],
        'shop-category-men' => ['shop', 'category-buttons', 'Men'],
        'shop-category-women' => ['shop', 'category-buttons', 'Women'],
        'shop-category-boys' => ['shop', 'category-buttons', 'Boys'],
        'shop-category-girls' => ['shop', 'category-buttons', 'Girls'],
        'shop-category-accessories' => ['shop', 'category-buttons', 'Accessories'],
        'shop-category-new' => ['shop', 'category-buttons', 'New Arrivals'],
        'shop-by-product' => ['shop', 'shop-by-product', 'Shop By Product'],
        'shop-shopping-cart' => ['shop', 'shopping-cart', 'Shopping Cart'],
        'shop-make-it-yours' => ['shop', 'make-it-yours', 'Make It Yours'],
        'shop-gift-cards' => ['shop', 'gift-cards', 'Gift Cards'],
        'shop-bundle' => ['shop', 'bundle', 'Bundle'],
    ];

    return array_map(static fn(array $definition): array => [
        'group' => $definition[0],
        'area' => $definition[1],
        'label' => $definition[2],
    ], $definitions);
}

function girffonMobileAppHomeDefaults(): array
{
    $sections = [];
    foreach (girffonMobileAppContentDefinitions() as $key => $definition) {
        $sections[$key] = $definition['label'];
    }
    return $sections;
}

function girffonAdminEnsureMobileAppHomeTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS mobile_app_home_sections (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                section_key VARCHAR(80) NOT NULL,
                content_group VARCHAR(40) NOT NULL DEFAULT 'home',
                section_name VARCHAR(120) NOT NULL,
                title VARCHAR(180) NOT NULL DEFAULT '',
                subtitle TEXT NULL,
                image_url VARCHAR(500) NOT NULL DEFAULT '',
                mobile_image_url VARCHAR(500) NOT NULL DEFAULT '',
                tablet_image_url VARCHAR(500) NOT NULL DEFAULT '',
                button_text VARCHAR(80) NOT NULL DEFAULT '',
                button_destination VARCHAR(500) NOT NULL DEFAULT '',
                is_enabled TINYINT(1) NOT NULL DEFAULT 0,
                display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                start_at DATETIME NULL,
                end_at DATETIME NULL,
                workflow_status VARCHAR(20) NOT NULL DEFAULT 'draft',
                published_at DATETIME NULL,
                draft_payload LONGTEXT NULL,
                published_payload LONGTEXT NULL,
                revision INT UNSIGNED NOT NULL DEFAULT 0,
                draft_updated_at DATETIME NULL,
                published_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
                published_by_username VARCHAR(191) NOT NULL DEFAULT '',
                updated_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
                updated_by_username VARCHAR(191) NOT NULL DEFAULT '',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_mobile_app_home_section_key (section_key),
                KEY idx_mobile_app_home_delivery (workflow_status, is_enabled, display_order, start_at, end_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = [];
        $columnStatement = $pdo->query('SHOW COLUMNS FROM mobile_app_home_sections');
        foreach ($columnStatement ? $columnStatement->fetchAll(PDO::FETCH_ASSOC) : [] as $column) {
            $columns[(string) $column['Field']] = true;
        }
        $missingColumns = [
            'content_group' => "ALTER TABLE mobile_app_home_sections ADD COLUMN content_group VARCHAR(40) NOT NULL DEFAULT 'home' AFTER section_key",
            'draft_payload' => 'ALTER TABLE mobile_app_home_sections ADD COLUMN draft_payload LONGTEXT NULL AFTER published_at',
            'published_payload' => 'ALTER TABLE mobile_app_home_sections ADD COLUMN published_payload LONGTEXT NULL AFTER draft_payload',
            'revision' => 'ALTER TABLE mobile_app_home_sections ADD COLUMN revision INT UNSIGNED NOT NULL DEFAULT 0 AFTER published_payload',
            'draft_updated_at' => 'ALTER TABLE mobile_app_home_sections ADD COLUMN draft_updated_at DATETIME NULL AFTER revision',
            'published_by_admin_id' => 'ALTER TABLE mobile_app_home_sections ADD COLUMN published_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER draft_updated_at',
            'published_by_username' => "ALTER TABLE mobile_app_home_sections ADD COLUMN published_by_username VARCHAR(191) NOT NULL DEFAULT '' AFTER published_by_admin_id",
        ];
        foreach ($missingColumns as $column => $sql) {
            if (!isset($columns[$column])) {
                $pdo->exec($sql);
            }
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS mobile_app_content_history (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                section_key VARCHAR(80) NOT NULL,
                revision INT UNSIGNED NOT NULL,
                payload LONGTEXT NOT NULL,
                published_at DATETIME NOT NULL,
                published_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
                published_by_username VARCHAR(191) NOT NULL DEFAULT '',
                PRIMARY KEY (id),
                UNIQUE KEY uq_mobile_history_revision (section_key, revision),
                KEY idx_mobile_history_section (section_key, published_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $insert = $pdo->prepare(
            "INSERT IGNORE INTO mobile_app_home_sections
                (section_key, content_group, section_name, display_order, workflow_status, updated_by_username)
             VALUES (:section_key, :content_group, :section_name, :display_order, 'draft', 'system')"
        );
        $updateGroup = $pdo->prepare('UPDATE mobile_app_home_sections SET content_group = :content_group WHERE section_key = :section_key');
        $displayOrder = 10;
        foreach (girffonMobileAppContentDefinitions() as $sectionKey => $definition) {
            $insert->execute([
                ':section_key' => $sectionKey,
                ':content_group' => $definition['group'],
                ':section_name' => $definition['label'],
                ':display_order' => $displayOrder,
            ]);
            $updateGroup->execute([':content_group' => $definition['group'], ':section_key' => $sectionKey]);
            $displayOrder += 10;
        }
        return true;
    } catch (PDOException $exception) {
        error_log('Mobile App Admin storage initialization failed: ' . $exception->getMessage());
        return false;
    }
}

function girffonAdminMobileDecodePayload($payload): array
{
    if (!is_string($payload) || trim($payload) === '') {
        return [];
    }
    $decoded = json_decode($payload, true);
    return is_array($decoded) ? $decoded : [];
}

function girffonAdminMobileNormalizePayload(array $input): array
{
    $fields = ['section_key', 'section_name', 'title', 'subtitle', 'description', 'image_url', 'mobile_image_url', 'tablet_image_url', 'button_text', 'button_destination', 'start_at', 'end_at', 'price', 'promotional_price', 'free_shipping_message', 'discount_message', 'bundle_message'];
    $payload = [];
    foreach ($fields as $field) {
        $payload[$field] = trim((string) ($input[$field] ?? ''));
    }
    $payload['is_enabled'] = !empty($input['is_enabled']) ? 1 : 0;
    $payload['display_order'] = max(0, min(65535, (int) ($input['display_order'] ?? 0)));
    $payload['settings'] = is_array($input['settings'] ?? null) ? $input['settings'] : [];
    return $payload;
}

function girffonAdminFetchMobileContent(PDO $pdo): array
{
    if (!girffonAdminEnsureMobileAppHomeTable($pdo)) {
        throw new RuntimeException('Mobile App storage is unavailable.');
    }
    $statement = $pdo->query('SELECT * FROM mobile_app_home_sections ORDER BY content_group, display_order, id');
    $rows = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rows as &$row) {
        $draft = girffonAdminMobileDecodePayload($row['draft_payload'] ?? null);
        if ($draft) {
            $row = array_merge($row, $draft);
        }
        $row['has_published'] = trim((string) ($row['published_payload'] ?? '')) !== '';
    }
    unset($row);
    return $rows;
}

function girffonAdminFetchMobileAppHomeSections(PDO $pdo): array
{
    return girffonAdminFetchMobileContent($pdo);
}

function girffonAdminSaveMobileContent(PDO $pdo, array $input, int $adminId, string $adminUsername, bool $publish): array
{
    if (!girffonAdminEnsureMobileAppHomeTable($pdo)) {
        throw new RuntimeException('Mobile App storage is unavailable.');
    }
    $definitions = girffonMobileAppContentDefinitions();
    $payload = girffonAdminMobileNormalizePayload($input);
    $sectionKey = $payload['section_key'];
    if (!isset($definitions[$sectionKey])) {
        throw new InvalidArgumentException('Unknown Mobile App content section.');
    }
    $payload['content_area'] = $definitions[$sectionKey]['area'];
    if ($payload['section_name'] === '') {
        throw new InvalidArgumentException('Section name is required.');
    }
    foreach (['start_at', 'end_at'] as $dateField) {
        if ($payload[$dateField] !== '' && strtotime($payload[$dateField]) === false) {
            throw new InvalidArgumentException('Invalid campaign date or time.');
        }
    }
    if ($payload['start_at'] !== '' && $payload['end_at'] !== '' && strtotime($payload['end_at']) < strtotime($payload['start_at'])) {
        throw new InvalidArgumentException('End date must be after the start date.');
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $pdo->beginTransaction();
    try {
        $select = $pdo->prepare('SELECT * FROM mobile_app_home_sections WHERE section_key = :section_key FOR UPDATE');
        $select->execute([':section_key' => $sectionKey]);
        $existing = $select->fetch(PDO::FETCH_ASSOC);
        if (!is_array($existing)) {
            throw new RuntimeException('Mobile App content row is missing.');
        }
        $revision = (int) ($existing['revision'] ?? 0);
        if ($publish && !empty($existing['published_payload']) && $revision > 0) {
            $history = $pdo->prepare(
                'INSERT IGNORE INTO mobile_app_content_history (section_key, revision, payload, published_at, published_by_admin_id, published_by_username)
                 VALUES (:section_key, :revision, :payload, :published_at, :admin_id, :username)'
            );
            $history->execute([
                ':section_key' => $sectionKey,
                ':revision' => $revision,
                ':payload' => $existing['published_payload'],
                ':published_at' => $existing['published_at'] ?: date('Y-m-d H:i:s'),
                ':admin_id' => (int) ($existing['published_by_admin_id'] ?? 0),
                ':username' => (string) ($existing['published_by_username'] ?? ''),
            ]);
        }

        $sql = "UPDATE mobile_app_home_sections SET
                    content_group = :content_group, section_name = :section_name, title = :title, subtitle = :subtitle,
                    image_url = :image_url, mobile_image_url = :mobile_image_url, tablet_image_url = :tablet_image_url,
                    button_text = :button_text, button_destination = :button_destination, is_enabled = :is_enabled,
                    display_order = :display_order, start_at = :start_at, end_at = :end_at,
                    draft_payload = :draft_payload, draft_updated_at = CURRENT_TIMESTAMP,
                    updated_by_admin_id = :updated_by_admin_id, updated_by_username = :updated_by_username";
        if ($publish) {
            $sql .= ", workflow_status = 'published', published_payload = :published_payload,
                      published_at = CURRENT_TIMESTAMP, revision = revision + 1,
                      published_by_admin_id = :published_by_admin_id, published_by_username = :published_by_username";
        }
        $sql .= ' WHERE section_key = :section_key';
        $statement = $pdo->prepare($sql);
        $parameters = [
            ':content_group' => $definitions[$sectionKey]['group'],
            ':section_name' => $payload['section_name'],
            ':title' => $payload['title'],
            ':subtitle' => $payload['subtitle'] !== '' ? $payload['subtitle'] : null,
            ':image_url' => $payload['image_url'],
            ':mobile_image_url' => $payload['mobile_image_url'],
            ':tablet_image_url' => $payload['tablet_image_url'],
            ':button_text' => $payload['button_text'],
            ':button_destination' => $payload['button_destination'],
            ':is_enabled' => $payload['is_enabled'],
            ':display_order' => $payload['display_order'],
            ':start_at' => $payload['start_at'] !== '' ? date('Y-m-d H:i:s', strtotime($payload['start_at'])) : null,
            ':end_at' => $payload['end_at'] !== '' ? date('Y-m-d H:i:s', strtotime($payload['end_at'])) : null,
            ':draft_payload' => $json,
            ':updated_by_admin_id' => $adminId,
            ':updated_by_username' => $adminUsername,
            ':section_key' => $sectionKey,
        ];
        if ($publish) {
            $parameters[':published_payload'] = $json;
            $parameters[':published_by_admin_id'] = $adminId;
            $parameters[':published_by_username'] = $adminUsername;
        }
        $statement->execute($parameters);
        $pdo->commit();
        return ['section_key' => $sectionKey, 'published' => $publish, 'revision' => $revision + ($publish ? 1 : 0)];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Mobile App content save failed: ' . $exception->getMessage());
        throw $exception;
    }
}

function girffonAdminSaveMobileAppHomeSection(PDO $pdo, array $input, int $adminId, string $adminUsername, bool $publish): bool
{
    girffonAdminSaveMobileContent($pdo, $input, $adminId, $adminUsername, $publish);
    return true;
}

function girffonAdminMobileContentHistory(PDO $pdo, string $sectionKey): array
{
    girffonAdminEnsureMobileAppHomeTable($pdo);
    $statement = $pdo->prepare('SELECT * FROM mobile_app_content_history WHERE section_key = :section_key ORDER BY revision DESC LIMIT 20');
    $statement->execute([':section_key' => $sectionKey]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function girffonAdminRollbackMobileContent(PDO $pdo, string $sectionKey, int $revision, int $adminId, string $adminUsername): array
{
    girffonAdminEnsureMobileAppHomeTable($pdo);
    $statement = $pdo->prepare('SELECT payload FROM mobile_app_content_history WHERE section_key = :section_key AND revision = :revision LIMIT 1');
    $statement->execute([':section_key' => $sectionKey, ':revision' => $revision]);
    $payload = girffonAdminMobileDecodePayload($statement->fetchColumn());
    if (!$payload) {
        throw new InvalidArgumentException('The requested published revision was not found.');
    }
    return girffonAdminSaveMobileContent($pdo, $payload, $adminId, $adminUsername, true);
}

function girffonAdminMobileUploadImage(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('The image upload did not complete successfully.');
    }
    if ((int) ($file['size'] ?? 0) > 40 * 1024 * 1024) {
        throw new InvalidArgumentException('Media files must be 40 MB or smaller.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'video/mp4' => 'mp4'];
    if (!isset($extensions[$mime])) {
        throw new InvalidArgumentException('Only JPG, JPEG, PNG, WebP, GIF, and MP4 media are allowed.');
    }
    $relativeDirectory = 'uploads/mobile-app/' . date('Y/m');
    $absoluteDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
        throw new RuntimeException('The Mobile App media directory could not be created.');
    }
    $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file((string) $file['tmp_name'], $absoluteDirectory . DIRECTORY_SEPARATOR . $fileName)) {
        throw new RuntimeException('The uploaded image could not be stored.');
    }
    return $relativeDirectory . '/' . $fileName;
}

function girffonMobilePublishedConfiguration(PDO $pdo): array
{
    if (!girffonAdminEnsureMobileAppHomeTable($pdo)) {
        throw new RuntimeException('Mobile App storage is unavailable.');
    }
    $statement = $pdo->query(
        "SELECT section_key, content_group, published_payload, revision, published_at
         FROM mobile_app_home_sections
         WHERE published_payload IS NOT NULL AND published_payload <> ''"
    );
    $groups = [];
    $latestPublishedAt = null;
    $now = time();
    $definitions = girffonMobileAppContentDefinitions();
    foreach ($statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
        if (!isset($definitions[$row['section_key']])) {
            continue;
        }
        $payload = girffonAdminMobileDecodePayload($row['published_payload']);
        if (trim((string) ($payload['content_area'] ?? '')) === '') {
            $payload['content_area'] = $definitions[$row['section_key']]['area'];
        }
        $startsAt = trim((string) ($payload['start_at'] ?? ''));
        $endsAt = trim((string) ($payload['end_at'] ?? ''));
        if (empty($payload['is_enabled']) || ($startsAt !== '' && strtotime($startsAt) > $now) || ($endsAt !== '' && strtotime($endsAt) < $now)) {
            continue;
        }
        $payload['key'] = $row['section_key'];
        $payload['revision'] = (int) $row['revision'];
        $payload['published_at'] = $row['published_at'];
        $groups[$row['content_group']][] = $payload;
        if ($latestPublishedAt === null || $row['published_at'] > $latestPublishedAt) {
            $latestPublishedAt = $row['published_at'];
        }
    }
    foreach ($groups as &$items) {
        usort($items, static fn(array $left, array $right): int => ((int) ($left['display_order'] ?? 0)) <=> ((int) ($right['display_order'] ?? 0)));
    }
    unset($items);

    $home = [
        'banner' => [],
        'category-buttons' => [],
        'shopping-cart' => [],
        'custom-design' => [],
        'catalog' => [],
        'gift-cards' => [],
        'bundles' => [],
    ];
    foreach ($groups['home'] ?? [] as $item) {
        $area = (string) ($item['content_area'] ?? '');
        if (array_key_exists($area, $home)) {
            $home[$area][] = $item;
        }
    }

    return [
        'version' => 1,
        'published_at' => $latestPublishedAt,
        'home' => $home,
        'groups' => $groups,
    ];
}

function girffonAdminMobileSharedSystems(PDO $pdo): array
{
    $tables = ['products', 'users', 'orders', 'user_addresses', 'wishlist_items', 'gift_cards', 'customer_designs', 'custom_design_orders', 'user_preferences'];
    $result = [];
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
    foreach ($tables as $table) {
        $statement->execute([':table_name' => $table]);
        $exists = (bool) $statement->fetchColumn();
        $count = null;
        if ($exists) {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        }
        $result[$table] = ['exists' => $exists, 'count' => $count];
    }
    return $result;
}
