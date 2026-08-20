<?php
require_once __DIR__ . '/../config/database.php';

function girffonMobileAppHomeDefaults(): array
{
    return [
        'hero-slider' => 'Hero / Slider',
        'men' => 'Men',
        'women' => 'Women',
        'kids' => 'Kids',
        'animation' => 'Animation',
        'animal-designs' => 'Animal Designs',
        'accessories' => 'Accessories',
        'new-arrivals' => 'New Arrivals',
        'custom-design-promotion' => 'Custom Design Promotion',
        'gift-cards' => 'Gift Cards',
        'bundles' => 'Bundles',
        'future-sections' => 'Future Sections',
    ];
}

function girffonAdminEnsureMobileAppHomeTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS mobile_app_home_sections (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                section_key VARCHAR(80) NOT NULL,
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
                updated_by_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
                updated_by_username VARCHAR(191) NOT NULL DEFAULT '',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_mobile_app_home_section_key (section_key),
                KEY idx_mobile_app_home_delivery (workflow_status, is_enabled, display_order, start_at, end_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $statement = $pdo->prepare(
            "INSERT IGNORE INTO mobile_app_home_sections
                (section_key, section_name, display_order, workflow_status, updated_by_username)
             VALUES (:section_key, :section_name, :display_order, 'draft', 'system')"
        );
        $displayOrder = 10;
        foreach (girffonMobileAppHomeDefaults() as $sectionKey => $sectionName) {
            $statement->execute([
                ':section_key' => $sectionKey,
                ':section_name' => $sectionName,
                ':display_order' => $displayOrder,
            ]);
            $displayOrder += 10;
        }

        return true;
    } catch (PDOException $exception) {
        error_log('Mobile App Admin storage initialization failed: ' . $exception->getMessage());
        return false;
    }
}

function girffonAdminFetchMobileAppHomeSections(PDO $pdo): array
{
    if (!girffonAdminEnsureMobileAppHomeTable($pdo)) {
        return [];
    }

    $statement = $pdo->query('SELECT * FROM mobile_app_home_sections ORDER BY display_order ASC, id ASC');
    return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
}

function girffonAdminSaveMobileAppHomeSection(PDO $pdo, array $input, int $adminId, string $adminUsername, bool $publish): bool
{
    if (!girffonAdminEnsureMobileAppHomeTable($pdo)) {
        return false;
    }

    $statement = $pdo->prepare(
        "INSERT INTO mobile_app_home_sections (
            section_key, section_name, title, subtitle, image_url, mobile_image_url,
            tablet_image_url, button_text, button_destination, is_enabled, display_order,
            start_at, end_at, workflow_status, published_at, updated_by_admin_id, updated_by_username
        ) VALUES (
            :section_key, :section_name, :title, :subtitle, :image_url, :mobile_image_url,
            :tablet_image_url, :button_text, :button_destination, :is_enabled, :display_order,
            :start_at, :end_at, :workflow_status, :published_at, :updated_by_admin_id, :updated_by_username
        ) ON DUPLICATE KEY UPDATE
            section_name = VALUES(section_name), title = VALUES(title), subtitle = VALUES(subtitle),
            image_url = VALUES(image_url), mobile_image_url = VALUES(mobile_image_url),
            tablet_image_url = VALUES(tablet_image_url), button_text = VALUES(button_text),
            button_destination = VALUES(button_destination), is_enabled = VALUES(is_enabled),
            display_order = VALUES(display_order), start_at = VALUES(start_at), end_at = VALUES(end_at),
            workflow_status = VALUES(workflow_status),
            published_at = IF(VALUES(workflow_status) = 'published', COALESCE(published_at, CURRENT_TIMESTAMP), NULL),
            updated_by_admin_id = VALUES(updated_by_admin_id), updated_by_username = VALUES(updated_by_username)"
    );

    return $statement->execute([
        ':section_key' => $input['section_key'],
        ':section_name' => $input['section_name'],
        ':title' => $input['title'],
        ':subtitle' => $input['subtitle'] !== '' ? $input['subtitle'] : null,
        ':image_url' => $input['image_url'],
        ':mobile_image_url' => $input['mobile_image_url'],
        ':tablet_image_url' => $input['tablet_image_url'],
        ':button_text' => $input['button_text'],
        ':button_destination' => $input['button_destination'],
        ':is_enabled' => $input['is_enabled'],
        ':display_order' => $input['display_order'],
        ':start_at' => $input['start_at'] !== '' ? str_replace('T', ' ', $input['start_at']) . ':00' : null,
        ':end_at' => $input['end_at'] !== '' ? str_replace('T', ' ', $input['end_at']) . ':00' : null,
        ':workflow_status' => $publish ? 'published' : 'draft',
        ':published_at' => $publish ? date('Y-m-d H:i:s') : null,
        ':updated_by_admin_id' => $adminId,
        ':updated_by_username' => $adminUsername,
    ]);
}