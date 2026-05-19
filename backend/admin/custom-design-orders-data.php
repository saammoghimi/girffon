<?php
require_once __DIR__ . '/../config/database.php';

function girffonAdminCustomDesignOrderStatuses(): array
{
    return ['new', 'pending_payment', 'paid', 'paid_review', 'paid_reviewing', 'reviewing', 'approved', 'rejected', 'in_production', 'completed'];
}

function girffonAdminCustomDesignTableColumns(PDO $pdo, string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $cache[$table] = [];

    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $column) {
            $name = (string) ($column['Field'] ?? '');
            if ($name !== '') {
                $cache[$table][$name] = true;
            }
        }
    } catch (PDOException $exception) {
        $cache[$table] = [];
    }

    return $cache[$table];
}

function girffonAdminCustomDesignOrderBaseUploadDirectory(): string
{
    $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'custom-design-orders';
    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    return $directory;
}

function girffonAdminCustomDesignDecodeJson($value): array
{
    if (is_array($value)) {
        return $value;
    }

    if (!is_string($value) || trim($value) === '') {
        return [];
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function girffonAdminCustomDesignValueAt(array $data, array $paths, $default = '')
{
    foreach ($paths as $path) {
        $current = $data;
        $segments = explode('.', (string) $path);
        $found = true;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                $found = false;
                break;
            }
            $current = $current[$segment];
        }

        if ($found && $current !== null && $current !== '') {
            return $current;
        }
    }

    return $default;
}

function girffonAdminCustomDesignNormalizePath(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    $path = str_replace('\\', '/', $path);
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    $workspaceRoot = str_replace('\\', '/', dirname(__DIR__, 2));
    $normalizedPath = ltrim($path, '/');
    if (strpos(str_replace('\\', '/', $path), $workspaceRoot) === 0) {
        $normalizedPath = ltrim(substr(str_replace('\\', '/', $path), strlen($workspaceRoot)), '/');
    }

    return $normalizedPath;
}

function girffonAdminCustomDesignFormatRomeDate(string $value, string $format = 'd M Y · H:i'): string
{
    $value = trim($value);
    if ($value === '') {
        return '-';
    }

    try {
        $date = new DateTimeImmutable($value, new DateTimeZone('UTC'));
        return $date->setTimezone(new DateTimeZone('Europe/Rome'))->format($format);
    } catch (Throwable $exception) {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        try {
            $fallbackDate = new DateTimeImmutable('@' . $timestamp);
            return $fallbackDate->setTimezone(new DateTimeZone('Europe/Rome'))->format($format);
        } catch (Throwable $innerException) {
            return '-';
        }
    }
}

function girffonAdminCustomDesignFormatBytes($value): string
{
    $bytes = (float) $value;
    if ($bytes <= 0) {
        return '-';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }

    return number_format($bytes, $unitIndex === 0 ? 0 : 1, '.', ',') . ' ' . $units[$unitIndex];
}

function girffonAdminCustomDesignSanitizeFileSegment(string $value, string $fallback = 'asset'): string
{
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: $fallback;
    $value = trim($value, '-.');

    return $value !== '' ? $value : $fallback;
}

function girffonAdminCustomDesignMimeToExtension(string $mime): string
{
    $map = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];

    $mime = strtolower(trim($mime));
    return $map[$mime] ?? 'png';
}

function girffonAdminCustomDesignWriteDataUrlAsset(string $directory, string $baseName, string $dataUrl): array
{
    $matches = [];
    if (!preg_match('/^data:([^;,]+);base64,(.+)$/', $dataUrl, $matches)) {
        return ['path' => '', 'size' => 0, 'mime' => ''];
    }

    $mime = strtolower(trim((string) ($matches[1] ?? 'image/png')));
    $binary = base64_decode((string) ($matches[2] ?? ''), true);
    if ($binary === false) {
        return ['path' => '', 'size' => 0, 'mime' => $mime];
    }

    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    $extension = girffonAdminCustomDesignMimeToExtension($mime);
    $fileName = girffonAdminCustomDesignSanitizeFileSegment($baseName, 'asset') . '.' . $extension;
    $absolutePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
    file_put_contents($absolutePath, $binary);

    return [
        'path' => girffonAdminCustomDesignNormalizePath($absolutePath),
        'size' => strlen($binary),
        'mime' => $mime,
    ];
}

function girffonAdminCustomDesignStoreAsset(string $directory, string $baseName, string $source, string $mime = 'image/png'): array
{
    $source = trim($source);
    if ($source === '') {
        return ['path' => '', 'size' => 0, 'mime' => trim($mime) !== '' ? $mime : 'image/png'];
    }

    if (stripos($source, 'data:') === 0) {
        return girffonAdminCustomDesignWriteDataUrlAsset($directory, $baseName, $source);
    }

    return [
        'path' => girffonAdminCustomDesignNormalizePath($source),
        'size' => 0,
        'mime' => trim($mime) !== '' ? $mime : 'image/png',
    ];
}

function girffonAdminCustomDesignGenerateOrderCode(): string
{
    try {
        $suffix = (string) random_int(1000, 9999);
    } catch (Throwable $exception) {
        $suffix = substr((string) mt_rand(), 0, 4);
    }

    return 'CDO-' . date('YmdHis') . '-' . $suffix;
}

function girffonAdminCreateCustomDesignOrder(PDO $pdo, array $customer, array $payload): array
{
    if (!girffonAdminEnsureCustomDesignOrderTables($pdo)) {
        return [
            'success' => false,
            'message' => 'Custom design order tables are not available.',
        ];
    }

    $snapshot = is_array($payload['snapshot'] ?? null) ? $payload['snapshot'] : [];
    $previews = is_array($payload['previews'] ?? null) ? $payload['previews'] : [];
    $uploads = is_array($payload['uploads'] ?? null) ? array_values($payload['uploads']) : [];
    $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
    $project = is_array($payload['project'] ?? null) ? $payload['project'] : [];

    $textItems = is_array($items['text'] ?? null) ? array_values($items['text']) : [];
    $flagItems = is_array($items['flag'] ?? null) ? array_values($items['flag']) : [];
    $shapeItems = is_array($items['shape'] ?? null) ? array_values($items['shape']) : [];
    $iconItems = is_array($items['icon'] ?? null) ? array_values($items['icon']) : [];
    $fillItems = is_array($items['fill'] ?? null) ? array_values($items['fill']) : [];
    $sizeLineItems = is_array($items['size_line'] ?? null)
        ? array_values($items['size_line'])
        : (is_array($payload['size_requests'] ?? null)
            ? array_values($payload['size_requests'])
            : (is_array($snapshot['sizeRequests'] ?? null) ? array_values($snapshot['sizeRequests']) : []));
    $addDesignItems = is_array($items['add_design'] ?? null)
        ? array_values($items['add_design'])
        : (is_array($payload['designSelections'] ?? null) ? array_values($payload['designSelections']) : []);

    $primaryFlag = $flagItems[0] ?? [];
    $primaryFill = $fillItems[0] ?? [];
    $primaryDesign = $addDesignItems[0] ?? [];
    $productName = trim((string) girffonAdminCustomDesignValueAt($payload, ['product_name', 'snapshot.productName', 'product.name'], 'Custom Product'));
    $customerNote = trim((string) girffonAdminCustomDesignValueAt($payload, ['customer_note', 'note'], ''));
    $customerName = trim((string) ($customer['name'] ?? ''));
    $customerEmail = trim((string) ($customer['email'] ?? ''));
    $customerPhone = trim((string) ($customer['phone'] ?? ''));
    $orderCode = girffonAdminCustomDesignGenerateOrderCode();
    $initialStatus = strtolower(trim((string) ($payload['status'] ?? 'new')));
    if (!in_array($initialStatus, girffonAdminCustomDesignOrderStatuses(), true)) {
        $initialStatus = 'new';
    }
    $initialPaymentStatus = in_array($initialStatus, ['paid', 'paid_review', 'paid_reviewing'], true) ? 'paid' : 'pending';

    $designPayload = $payload;
    $designPayload['size_requests'] = $sizeLineItems;
    $designPayload['designSelections'] = $addDesignItems;
    $designPayload['customer'] = [
        'id' => (int) ($customer['id'] ?? 0),
        'name' => $customerName,
        'email' => $customerEmail,
        'phone' => $customerPhone,
        'address' => (string) ($customer['address'] ?? ''),
        'city' => (string) ($customer['city'] ?? ''),
        'country' => (string) ($customer['country'] ?? ''),
        'postcode' => (string) ($customer['postcode'] ?? ''),
    ];

    try {
        $pdo->beginTransaction();

        $insertOrder = $pdo->prepare(
            'INSERT INTO custom_design_orders (
                order_code, user_id, customer_name, customer_email, customer_phone, product_name, status, customer_note, admin_note,
                payment_status, paid_at, preview_front, preview_back, preview_right, preview_left, flag_name, flag_code, flag_image, fill_name, fill_value,
                design_folder_name, design_file_name, design_image_path, design_payload_json
            ) VALUES (
                :order_code, :user_id, :customer_name, :customer_email, :customer_phone, :product_name, :status, :customer_note, :admin_note,
                :payment_status, :paid_at, :preview_front, :preview_back, :preview_right, :preview_left, :flag_name, :flag_code, :flag_image, :fill_name, :fill_value,
                :design_folder_name, :design_file_name, :design_image_path, :design_payload_json
            )'
        );

        $insertOrder->execute([
            ':order_code' => $orderCode,
            ':user_id' => (int) ($customer['id'] ?? 0) ?: null,
            ':customer_name' => $customerName,
            ':customer_email' => $customerEmail,
            ':customer_phone' => $customerPhone,
            ':product_name' => $productName,
            ':status' => $initialStatus,
            ':customer_note' => $customerNote,
            ':admin_note' => '',
            ':payment_status' => $initialPaymentStatus,
            ':paid_at' => $initialPaymentStatus === 'paid' ? date('Y-m-d H:i:s') : null,
            ':preview_front' => '',
            ':preview_back' => '',
            ':preview_right' => '',
            ':preview_left' => '',
            ':flag_name' => (string) ($primaryFlag['name'] ?? ''),
            ':flag_code' => (string) ($primaryFlag['code'] ?? ''),
            ':flag_image' => girffonAdminCustomDesignNormalizePath((string) ($primaryFlag['image'] ?? '')),
            ':fill_name' => (string) ($primaryFill['name'] ?? ''),
            ':fill_value' => (string) ($primaryFill['value'] ?? ''),
            ':design_folder_name' => (string) ($primaryDesign['folder_name'] ?? ($project['folder'] ?? '')),
            ':design_file_name' => (string) ($primaryDesign['file_name'] ?? ($project['file'] ?? '')),
            ':design_image_path' => girffonAdminCustomDesignNormalizePath((string) ($primaryDesign['image'] ?? '')),
            ':design_payload_json' => json_encode($designPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $orderId = (int) $pdo->lastInsertId();
        $orderDirectory = girffonAdminCustomDesignOrderBaseUploadDirectory() . DIRECTORY_SEPARATOR . 'order-' . $orderId;
        $previewDirectory = $orderDirectory . DIRECTORY_SEPARATOR . 'previews';
        $uploadDirectory = $orderDirectory . DIRECTORY_SEPARATOR . 'uploads';

        $storedPreviews = [];
        foreach (['front', 'back', 'right', 'left'] as $view) {
            $storedPreviews[$view] = girffonAdminCustomDesignStoreAsset(
                $previewDirectory,
                'preview-' . $view,
                (string) ($previews[$view] ?? ''),
                'image/png'
            );
        }

        $insertUpload = $pdo->prepare(
            'INSERT INTO custom_design_uploads (order_id, original_name, stored_name, file_path, mime_type, file_size, sort_order)
             VALUES (:order_id, :original_name, :stored_name, :file_path, :mime_type, :file_size, :sort_order)'
        );

        foreach ($uploads as $index => $upload) {
            if (!is_array($upload)) {
                continue;
            }

            $originalName = trim((string) ($upload['name'] ?? 'Uploaded image'));
            $mimeType = trim((string) ($upload['type'] ?? 'image/png'));
            $source = (string) ($upload['originalSrc'] ?? $upload['optimizedSrc'] ?? $upload['dataUrl'] ?? '');
            $stored = girffonAdminCustomDesignStoreAsset(
                $uploadDirectory,
                'upload-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '-' . $originalName,
                $source,
                $mimeType
            );

            if (($stored['path'] ?? '') === '') {
                continue;
            }

            $insertUpload->execute([
                ':order_id' => $orderId,
                ':original_name' => $originalName,
                ':stored_name' => basename((string) ($stored['path'] ?? '')),
                ':file_path' => (string) ($stored['path'] ?? ''),
                ':mime_type' => (string) ($stored['mime'] ?? $mimeType),
                ':file_size' => (int) ($stored['size'] ?? 0),
                ':sort_order' => $index,
            ]);
        }

        $insertItem = $pdo->prepare(
            'INSERT INTO custom_design_items (order_id, item_type, item_name, item_label, item_value_json, sort_order)
             VALUES (:order_id, :item_type, :item_name, :item_label, :item_value_json, :sort_order)'
        );

        $itemSets = [
            'text' => $textItems,
            'flag' => $flagItems,
            'shape' => $shapeItems,
            'icon' => $iconItems,
            'fill' => $fillItems,
            'size_line' => $sizeLineItems,
            'add_design' => $addDesignItems,
        ];

        foreach ($itemSets as $type => $entries) {
            foreach ($entries as $index => $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $name = trim((string) girffonAdminCustomDesignValueAt($entry, ['name', 'file_name', 'content'], ucfirst($type)));
                $label = trim((string) girffonAdminCustomDesignValueAt($entry, ['label', 'content', 'file_name', 'name'], $name));
                $insertItem->execute([
                    ':order_id' => $orderId,
                    ':item_type' => $type,
                    ':item_name' => $name,
                    ':item_label' => $label,
                    ':item_value_json' => json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ':sort_order' => $index,
                ]);
            }
        }

        $updateOrder = $pdo->prepare(
            'UPDATE custom_design_orders
             SET preview_front = :preview_front,
                 preview_back = :preview_back,
                 preview_right = :preview_right,
                 preview_left = :preview_left,
                 flag_name = :flag_name,
                 flag_code = :flag_code,
                 flag_image = :flag_image,
                 fill_name = :fill_name,
                 fill_value = :fill_value,
                 design_folder_name = :design_folder_name,
                 design_file_name = :design_file_name,
                 design_image_path = :design_image_path,
                 design_payload_json = :design_payload_json
             WHERE id = :id'
        );

        $designPayload['stored_previews'] = array_map(static function (array $entry): string {
            return (string) ($entry['path'] ?? '');
        }, $storedPreviews);

        $updateOrder->execute([
            ':preview_front' => (string) ($storedPreviews['front']['path'] ?? ''),
            ':preview_back' => (string) ($storedPreviews['back']['path'] ?? ''),
            ':preview_right' => (string) ($storedPreviews['right']['path'] ?? ''),
            ':preview_left' => (string) ($storedPreviews['left']['path'] ?? ''),
            ':flag_name' => (string) ($primaryFlag['name'] ?? ''),
            ':flag_code' => (string) ($primaryFlag['code'] ?? ''),
            ':flag_image' => girffonAdminCustomDesignNormalizePath((string) ($primaryFlag['image'] ?? '')),
            ':fill_name' => (string) ($primaryFill['name'] ?? ''),
            ':fill_value' => (string) ($primaryFill['value'] ?? ''),
            ':design_folder_name' => (string) ($primaryDesign['folder_name'] ?? ($project['folder'] ?? '')),
            ':design_file_name' => (string) ($primaryDesign['file_name'] ?? ($project['file'] ?? '')),
            ':design_image_path' => girffonAdminCustomDesignNormalizePath((string) ($primaryDesign['image'] ?? '')),
            ':design_payload_json' => json_encode($designPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':id' => $orderId,
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'order_id' => $orderId,
            'order_code' => $orderCode,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'message' => 'Unable to save the custom design order.',
        ];
    }
}

function girffonAdminEnsureCustomDesignOrderTables(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    girffonAdminCustomDesignOrderBaseUploadDirectory();

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS custom_design_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_code VARCHAR(64) NOT NULL UNIQUE,
                user_id INT NULL,
                customer_name VARCHAR(150) NOT NULL DEFAULT '',
                customer_email VARCHAR(190) NOT NULL DEFAULT '',
                customer_phone VARCHAR(60) NOT NULL DEFAULT '',
                product_name VARCHAR(190) NOT NULL DEFAULT '',
                status VARCHAR(40) NOT NULL DEFAULT 'new',
                payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',
                paid_at TIMESTAMP NULL DEFAULT NULL,
                customer_note TEXT NULL,
                admin_note TEXT NULL,
                preview_front VARCHAR(255) NOT NULL DEFAULT '',
                preview_back VARCHAR(255) NOT NULL DEFAULT '',
                preview_right VARCHAR(255) NOT NULL DEFAULT '',
                preview_left VARCHAR(255) NOT NULL DEFAULT '',
                flag_name VARCHAR(150) NOT NULL DEFAULT '',
                flag_code VARCHAR(40) NOT NULL DEFAULT '',
                flag_image VARCHAR(255) NOT NULL DEFAULT '',
                fill_name VARCHAR(100) NOT NULL DEFAULT '',
                fill_value VARCHAR(100) NOT NULL DEFAULT '',
                design_folder_name VARCHAR(190) NOT NULL DEFAULT '',
                design_file_name VARCHAR(190) NOT NULL DEFAULT '',
                design_image_path VARCHAR(255) NOT NULL DEFAULT '',
                design_payload_json LONGTEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = girffonAdminCustomDesignTableColumns($pdo, 'custom_design_orders');
        if (!isset($columns['payment_status'])) {
            $pdo->exec("ALTER TABLE custom_design_orders ADD COLUMN payment_status VARCHAR(40) NOT NULL DEFAULT 'pending' AFTER status");
        }
        if (!isset($columns['paid_at'])) {
            $pdo->exec("ALTER TABLE custom_design_orders ADD COLUMN paid_at TIMESTAMP NULL DEFAULT NULL AFTER payment_status");
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS custom_design_uploads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                original_name VARCHAR(255) NOT NULL DEFAULT '',
                stored_name VARCHAR(255) NOT NULL DEFAULT '',
                file_path VARCHAR(255) NOT NULL DEFAULT '',
                mime_type VARCHAR(120) NOT NULL DEFAULT '',
                file_size BIGINT NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_custom_design_uploads_order_id (order_id),
                CONSTRAINT fk_custom_design_uploads_order_id FOREIGN KEY (order_id) REFERENCES custom_design_orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS custom_design_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                item_type VARCHAR(40) NOT NULL DEFAULT 'meta',
                item_name VARCHAR(190) NOT NULL DEFAULT '',
                item_label VARCHAR(190) NOT NULL DEFAULT '',
                item_value_json LONGTEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_custom_design_items_order_id (order_id),
                INDEX idx_custom_design_items_type (item_type),
                CONSTRAINT fk_custom_design_items_order_id FOREIGN KEY (order_id) REFERENCES custom_design_orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $checked = true;
        return true;
    } catch (PDOException $exception) {
        $checked = false;
        return false;
    }
}

function girffonAdminCustomDesignDemoOrderSummary(): array
{
    return [
        'id' => 0,
        'order_code' => 'DEMO-CUSTOM-0001',
        'customer_name' => 'Demo Customer',
        'customer_email' => 'demo.custom@example.com',
        'product_name' => "Basic Men's T-Shirt",
        'upload_count' => 4,
        'text_count' => 2,
        'status' => 'reviewing',
        'payment_status' => 'paid',
        'paid_at' => '2026-05-18 11:45:00',
        'preview_front' => 'Image/Custom Design Pro/images/Products/Men/Basic Men\'s T-Shirt/arancione/1.png',
        'order_total' => 48.00,
        'created_at' => '2026-05-18 11:30:00',
        'is_demo' => true,
    ];
}

    function girffonAdminFetchCustomDesignOrderSummaries(PDO $pdo, int $limit = 100, array $filters = []): array
{
    if (!girffonAdminEnsureCustomDesignOrderTables($pdo)) {
        return [girffonAdminCustomDesignDemoOrderSummary()];
    }

    try {
        $conditions = [];
        $params = [];
        $statuses = array_values(array_filter(array_map(static function ($value): string {
            return strtolower(trim((string) $value));
        }, is_array($filters['statuses'] ?? null) ? $filters['statuses'] : [])));
        if ($statuses) {
            $placeholders = [];
            foreach ($statuses as $index => $status) {
                $placeholder = ':status_' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $status;
            }
            $conditions[] = 'orders.status IN (' . implode(', ', $placeholders) . ')';
        }

        $paymentStatus = strtolower(trim((string) ($filters['payment_status'] ?? '')));
        if ($paymentStatus !== '') {
            $conditions[] = 'orders.payment_status = :payment_status';
            $params[':payment_status'] = $paymentStatus;
        }

        $paidAfter = trim((string) ($filters['paid_after'] ?? ''));
        if ($paidAfter !== '') {
            $conditions[] = 'orders.paid_at >= :paid_after';
            $params[':paid_after'] = $paidAfter;
        }

        $sql = "SELECT orders.id, orders.order_code, orders.customer_name, orders.customer_email, orders.product_name, orders.status,
                       orders.payment_status, orders.paid_at, orders.preview_front, orders.design_payload_json, orders.created_at,
                       (SELECT COUNT(*) FROM custom_design_uploads uploads WHERE uploads.order_id = orders.id) AS upload_count,
                       (SELECT COUNT(*) FROM custom_design_items items WHERE items.order_id = orders.id AND items.item_type = 'text') AS text_count
                FROM custom_design_orders orders";
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= "
                ORDER BY orders.created_at DESC, orders.id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) {
            return [girffonAdminCustomDesignDemoOrderSummary()];
        }

        return array_map(static function (array $row): array {
            $payload = girffonAdminCustomDesignDecodeJson($row['design_payload_json'] ?? null);
            $summaryTotal = (float) girffonAdminCustomDesignValueAt($payload, ['product.order_total', 'snapshot.orderTotal', 'order_total', 'snapshot.total'], 0);
            return [
                'id' => (int) ($row['id'] ?? 0),
                'order_code' => (string) ($row['order_code'] ?? ''),
                'customer_name' => (string) ($row['customer_name'] ?? ''),
                'customer_email' => (string) ($row['customer_email'] ?? ''),
                'product_name' => (string) ($row['product_name'] ?? ''),
                'upload_count' => (int) ($row['upload_count'] ?? 0),
                'text_count' => (int) ($row['text_count'] ?? 0),
                'status' => (string) ($row['status'] ?? 'new'),
                'payment_status' => (string) ($row['payment_status'] ?? 'pending'),
                'paid_at' => (string) ($row['paid_at'] ?? ''),
                'preview_front' => girffonAdminCustomDesignNormalizePath((string) ($row['preview_front'] ?? '')),
                'order_total' => $summaryTotal,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'is_demo' => false,
            ];
        }, $rows);
    } catch (PDOException $exception) {
        return [girffonAdminCustomDesignDemoOrderSummary()];
    }
}

function girffonAdminCustomDesignItemGroupsFromRows(array $itemRows): array
{
    $groups = [
        'text' => [],
        'flag' => [],
        'shape' => [],
        'icon' => [],
        'fill' => [],
        'size_line' => [],
        'add_design' => [],
        'upload' => [],
    ];

    foreach ($itemRows as $row) {
        $type = strtolower(trim((string) ($row['item_type'] ?? 'meta')));
        $payload = girffonAdminCustomDesignDecodeJson($row['item_value_json'] ?? null);
        if (!isset($groups[$type])) {
            $groups[$type] = [];
        }
        $groups[$type][] = [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['item_name'] ?? ''),
            'label' => (string) ($row['item_label'] ?? ''),
            'payload' => $payload,
        ];
    }

    return $groups;
}

function girffonAdminCustomDesignGuessSectionsFromPayload(array $payload): array
{
    $sections = [
        'texts' => [],
        'flags' => [],
        'shapes' => [],
        'icons' => [],
        'fill' => [],
        'size_lines' => [],
        'add_design' => [],
    ];

    $sizeRequests = is_array($payload['size_requests'] ?? null)
        ? $payload['size_requests']
        : (is_array($payload['snapshot']['sizeRequests'] ?? null) ? $payload['snapshot']['sizeRequests'] : []);
    foreach ($sizeRequests as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $sections['size_lines'][] = [
            'fit' => (string) girffonAdminCustomDesignValueAt($entry, ['fit_label', 'fitLabel', 'fit'], ''),
            'size' => (string) girffonAdminCustomDesignValueAt($entry, ['size'], ''),
            'color' => (string) girffonAdminCustomDesignValueAt($entry, ['color'], ''),
            'quantity' => (int) girffonAdminCustomDesignValueAt($entry, ['quantity'], 1),
        ];
    }

    $layersByView = is_array($payload['layersByView'] ?? null) ? $payload['layersByView'] : [];
    foreach ($layersByView as $view => $layers) {
        if (!is_array($layers)) {
            continue;
        }

        foreach ($layers as $layer) {
            if (!is_array($layer)) {
                continue;
            }

            $type = strtolower((string) ($layer['type'] ?? ''));
            $name = (string) ($layer['name'] ?? $layer['label'] ?? '');
            $position = trim((string) girffonAdminCustomDesignValueAt($layer, ['position', 'style.position', 'coords'], ''));
            $size = trim((string) girffonAdminCustomDesignValueAt($layer, ['size', 'fontSize', 'dimensions', 'style.size'], ''));

            if ($type === 'text' || stripos($name, 'text') !== false) {
                $sections['texts'][] = [
                    'content' => (string) girffonAdminCustomDesignValueAt($layer, ['text', 'content', 'value'], $name !== '' ? $name : 'Custom text'),
                    'font_name' => (string) girffonAdminCustomDesignValueAt($layer, ['fontName', 'font_family', 'font'], ''),
                    'font_size' => (string) girffonAdminCustomDesignValueAt($layer, ['fontSize', 'size'], ''),
                    'text_color' => (string) girffonAdminCustomDesignValueAt($layer, ['color', 'textColor'], ''),
                    'text_position' => $position !== '' ? $position : ucfirst((string) $view),
                    'text_style' => (string) girffonAdminCustomDesignValueAt($layer, ['styleLabel', 'fontStyle', 'style'], ''),
                ];
                continue;
            }

            if ($type === 'flag' || stripos($name, 'flag') !== false) {
                $sections['flags'][] = [
                    'name' => (string) girffonAdminCustomDesignValueAt($layer, ['flagName', 'name', 'label'], $name),
                    'code' => (string) girffonAdminCustomDesignValueAt($layer, ['flagCode', 'code'], ''),
                    'image' => girffonAdminCustomDesignNormalizePath((string) girffonAdminCustomDesignValueAt($layer, ['flagImage', 'src', 'image'], '')),
                    'position' => $position,
                    'size' => $size,
                ];
                continue;
            }

            if ($type === 'shape' || stripos($name, 'shape') !== false) {
                $sections['shapes'][] = [
                    'name' => (string) girffonAdminCustomDesignValueAt($layer, ['shapeName', 'name', 'label'], $name),
                    'color' => (string) girffonAdminCustomDesignValueAt($layer, ['color', 'fillColor', 'shapeColor'], ''),
                    'position' => $position,
                    'size' => $size,
                ];
                continue;
            }

            if ($type === 'icon' || $type === 'emoji' || stripos($name, 'icon') !== false || stripos($name, 'emoji') !== false) {
                $sections['icons'][] = [
                    'name' => (string) girffonAdminCustomDesignValueAt($layer, ['iconName', 'emoji', 'name', 'label'], $name),
                    'position' => $position,
                    'size' => $size,
                ];
                continue;
            }

            if ($type === 'add_design' || $type === 'design' || $type === 'image') {
                $sections['add_design'][] = [
                    'id' => (string) girffonAdminCustomDesignValueAt($layer, ['id', 'layerId'], ''),
                    'name' => (string) girffonAdminCustomDesignValueAt($layer, ['name'], ''),
                    'view' => (string) girffonAdminCustomDesignValueAt($layer, ['view'], ''),
                    'folder_name' => (string) girffonAdminCustomDesignValueAt($layer, ['folderName', 'folder'], ''),
                    'file_name' => (string) girffonAdminCustomDesignValueAt($layer, ['fileName', 'name', 'label'], $name),
                    'image' => girffonAdminCustomDesignNormalizePath((string) girffonAdminCustomDesignValueAt($layer, ['image', 'src'], '')),
                    'position' => $position,
                ];
            }
        }
    }

    $designSelections = is_array($payload['designSelections'] ?? null) ? $payload['designSelections'] : [];
    foreach ($designSelections as $designEntry) {
        if (!is_array($designEntry)) {
            continue;
        }

        $sections['add_design'][] = [
            'id' => (string) girffonAdminCustomDesignValueAt($designEntry, ['id'], ''),
            'name' => (string) girffonAdminCustomDesignValueAt($designEntry, ['name'], ''),
            'view' => (string) girffonAdminCustomDesignValueAt($designEntry, ['view'], ''),
            'folder_name' => (string) girffonAdminCustomDesignValueAt($designEntry, ['folder_name', 'folderName', 'folder'], ''),
            'file_name' => (string) girffonAdminCustomDesignValueAt($designEntry, ['file_name', 'fileName', 'name'], ''),
            'image' => girffonAdminCustomDesignNormalizePath((string) girffonAdminCustomDesignValueAt($designEntry, ['image', 'src'], '')),
            'position' => (string) girffonAdminCustomDesignValueAt($designEntry, ['position'], ''),
        ];
    }

    $fillName = (string) girffonAdminCustomDesignValueAt($payload, ['fill.name', 'product.colorName', 'product.color', 'selectedColorName'], '');
    $fillValue = (string) girffonAdminCustomDesignValueAt($payload, ['fill.value', 'fill.color', 'selectedColor', 'product.colorHex'], '');
    if ($fillName !== '' || $fillValue !== '') {
        $sections['fill'][] = [
            'name' => $fillName,
            'value' => $fillValue,
            'style' => (string) girffonAdminCustomDesignValueAt($payload, ['fill.style', 'fill.type'], ''),
        ];
    }

    return $sections;
}

function girffonAdminCustomDesignBuildDetail(array $orderRow, array $uploadRows, array $itemRows): array
{
    $payload = girffonAdminCustomDesignDecodeJson($orderRow['design_payload_json'] ?? null);
    $itemGroups = girffonAdminCustomDesignItemGroupsFromRows($itemRows);
    $guessed = girffonAdminCustomDesignGuessSectionsFromPayload($payload);

    $previewViews = [];
    foreach (['front' => 'Front image', 'back' => 'Back image', 'right' => 'Right sleeve image', 'left' => 'Left sleeve image'] as $key => $label) {
        $previewViews[$key] = [
            'label' => $label,
            'path' => girffonAdminCustomDesignNormalizePath((string) ($orderRow['preview_' . $key] ?? '')),
        ];
    }

    $uploads = array_map(static function (array $row): array {
        $filePath = girffonAdminCustomDesignNormalizePath((string) ($row['file_path'] ?? ''));
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) (($row['original_name'] ?? '') !== '' ? $row['original_name'] : ($row['stored_name'] ?? 'Uploaded image')),
            'path' => $filePath,
            'download_url' => $filePath,
            'size_label' => girffonAdminCustomDesignFormatBytes($row['file_size'] ?? 0),
        ];
    }, $uploadRows);

    $texts = [];
    foreach ($itemGroups['text'] ?? [] as $item) {
        $payloadData = $item['payload'];
        $texts[] = [
            'content' => (string) girffonAdminCustomDesignValueAt($payloadData, ['content', 'text', 'value'], $item['label'] !== '' ? $item['label'] : $item['name']),
            'font_name' => (string) girffonAdminCustomDesignValueAt($payloadData, ['font_name', 'fontName', 'font'], ''),
            'font_size' => (string) girffonAdminCustomDesignValueAt($payloadData, ['font_size', 'fontSize', 'size'], ''),
            'text_color' => (string) girffonAdminCustomDesignValueAt($payloadData, ['text_color', 'color'], ''),
            'text_position' => (string) girffonAdminCustomDesignValueAt($payloadData, ['text_position', 'position', 'view'], ''),
            'text_style' => (string) girffonAdminCustomDesignValueAt($payloadData, ['text_style', 'fontStyle', 'style'], ''),
        ];
    }
    $texts = array_merge($texts, $guessed['texts']);

    $flags = [];
    foreach ($itemGroups['flag'] ?? [] as $item) {
        $payloadData = $item['payload'];
        $flags[] = [
            'name' => (string) girffonAdminCustomDesignValueAt($payloadData, ['name', 'flag_name'], $item['label'] !== '' ? $item['label'] : $item['name']),
            'code' => (string) girffonAdminCustomDesignValueAt($payloadData, ['code', 'flag_code'], ''),
            'image' => girffonAdminCustomDesignNormalizePath((string) girffonAdminCustomDesignValueAt($payloadData, ['image', 'flag_image'], '')),
            'position' => (string) girffonAdminCustomDesignValueAt($payloadData, ['position'], ''),
            'size' => (string) girffonAdminCustomDesignValueAt($payloadData, ['size'], ''),
        ];
    }
    if (!$flags && (($orderRow['flag_name'] ?? '') !== '' || ($orderRow['flag_code'] ?? '') !== '' || ($orderRow['flag_image'] ?? '') !== '')) {
        $flags[] = [
            'name' => (string) ($orderRow['flag_name'] ?? ''),
            'code' => (string) ($orderRow['flag_code'] ?? ''),
            'image' => girffonAdminCustomDesignNormalizePath((string) ($orderRow['flag_image'] ?? '')),
            'position' => '',
            'size' => '',
        ];
    }
    $flags = array_merge($flags, $guessed['flags']);

    $shapes = [];
    foreach ($itemGroups['shape'] ?? [] as $item) {
        $payloadData = $item['payload'];
        $shapes[] = [
            'name' => (string) girffonAdminCustomDesignValueAt($payloadData, ['name', 'shape_name'], $item['label'] !== '' ? $item['label'] : $item['name']),
            'color' => (string) girffonAdminCustomDesignValueAt($payloadData, ['color', 'shape_color'], ''),
            'position' => (string) girffonAdminCustomDesignValueAt($payloadData, ['position'], ''),
            'size' => (string) girffonAdminCustomDesignValueAt($payloadData, ['size'], ''),
        ];
    }
    $shapes = array_merge($shapes, $guessed['shapes']);

    $icons = [];
    foreach ($itemGroups['icon'] ?? [] as $item) {
        $payloadData = $item['payload'];
        $icons[] = [
            'name' => (string) girffonAdminCustomDesignValueAt($payloadData, ['name', 'emoji', 'icon_name'], $item['label'] !== '' ? $item['label'] : $item['name']),
            'position' => (string) girffonAdminCustomDesignValueAt($payloadData, ['position'], ''),
            'size' => (string) girffonAdminCustomDesignValueAt($payloadData, ['size'], ''),
        ];
    }
    $icons = array_merge($icons, $guessed['icons']);

    $fill = [];
    foreach ($itemGroups['fill'] ?? [] as $item) {
        $payloadData = $item['payload'];
        $fill[] = [
            'name' => (string) girffonAdminCustomDesignValueAt($payloadData, ['name'], $item['label'] !== '' ? $item['label'] : $item['name']),
            'value' => (string) girffonAdminCustomDesignValueAt($payloadData, ['value', 'color'], ''),
            'style' => (string) girffonAdminCustomDesignValueAt($payloadData, ['style', 'type'], ''),
        ];
    }
    if (!$fill && (($orderRow['fill_name'] ?? '') !== '' || ($orderRow['fill_value'] ?? '') !== '')) {
        $fill[] = [
            'name' => (string) ($orderRow['fill_name'] ?? ''),
            'value' => (string) ($orderRow['fill_value'] ?? ''),
            'style' => '',
        ];
    }
    $fill = array_merge($fill, $guessed['fill']);

    $sizeLines = [];
    foreach ($itemGroups['size_line'] ?? [] as $item) {
        $payloadData = $item['payload'];
        $sizeLines[] = [
            'fit' => (string) girffonAdminCustomDesignValueAt($payloadData, ['fit', 'fit_label', 'fitLabel'], ''),
            'size' => (string) girffonAdminCustomDesignValueAt($payloadData, ['size'], ''),
            'color' => (string) girffonAdminCustomDesignValueAt($payloadData, ['color'], ''),
            'quantity' => (int) girffonAdminCustomDesignValueAt($payloadData, ['quantity'], 1),
        ];
    }
    $sizeLines = array_merge($sizeLines, $guessed['size_lines']);

    $addDesign = [];
    foreach ($itemGroups['add_design'] ?? [] as $item) {
        $payloadData = $item['payload'];
        $addDesign[] = [
            'id' => (string) girffonAdminCustomDesignValueAt($payloadData, ['id'], ''),
            'name' => (string) girffonAdminCustomDesignValueAt($payloadData, ['name'], ''),
            'view' => (string) girffonAdminCustomDesignValueAt($payloadData, ['view'], ''),
            'folder_name' => (string) girffonAdminCustomDesignValueAt($payloadData, ['folder_name', 'folderName', 'folder'], ''),
            'file_name' => (string) girffonAdminCustomDesignValueAt($payloadData, ['file_name', 'fileName', 'name'], $item['label'] !== '' ? $item['label'] : $item['name']),
            'image' => girffonAdminCustomDesignNormalizePath((string) girffonAdminCustomDesignValueAt($payloadData, ['image', 'src'], '')),
            'position' => (string) girffonAdminCustomDesignValueAt($payloadData, ['position'], ''),
        ];
    }
    if (!$addDesign && (($orderRow['design_folder_name'] ?? '') !== '' || ($orderRow['design_file_name'] ?? '') !== '' || ($orderRow['design_image_path'] ?? '') !== '')) {
        $addDesign[] = [
            'folder_name' => (string) ($orderRow['design_folder_name'] ?? ''),
            'file_name' => (string) ($orderRow['design_file_name'] ?? ''),
            'image' => girffonAdminCustomDesignNormalizePath((string) ($orderRow['design_image_path'] ?? '')),
            'position' => '',
        ];
    }
    $addDesign = array_merge($addDesign, $guessed['add_design']);

    $fallbackPreview = '';
    foreach (['front', 'back', 'right', 'left'] as $viewKey) {
        $candidate = (string) ($previewViews[$viewKey]['path'] ?? '');
        if ($candidate !== '') {
            $fallbackPreview = $candidate;
            break;
        }
    }

    $summaryQuantity = (int) girffonAdminCustomDesignValueAt($payload, ['product.quantity', 'snapshot.quantity', 'quantity'], 0);
    if ($summaryQuantity <= 0 && $sizeLines) {
        $summaryQuantity = array_reduce($sizeLines, static function (int $carry, array $entry): int {
            return $carry + max(1, (int) ($entry['quantity'] ?? 1));
        }, 0);
    }
    if ($summaryQuantity <= 0) {
        $summaryQuantity = 1;
    }

    $summaryUnitTotal = (float) girffonAdminCustomDesignValueAt($payload, ['product.unit_total', 'snapshot.unitTotal', 'unit_total', 'snapshot.total'], 0);
    $summaryOrderTotal = (float) girffonAdminCustomDesignValueAt($payload, ['product.order_total', 'snapshot.orderTotal', 'order_total', 'snapshot.total'], 0);
    $summaryColor = (string) girffonAdminCustomDesignValueAt($payload, ['product.color', 'snapshot.color', 'selectedColorName', 'fill.name'], '');
    $summarySize = (string) girffonAdminCustomDesignValueAt($payload, ['product.size', 'snapshot.size'], '');

    return [
        'id' => (int) ($orderRow['id'] ?? 0),
        'user_id' => (int) ($orderRow['user_id'] ?? 0),
        'order_code' => (string) ($orderRow['order_code'] ?? ''),
        'customer_name' => (string) ($orderRow['customer_name'] ?? ''),
        'customer_email' => (string) ($orderRow['customer_email'] ?? ''),
        'customer_phone' => (string) ($orderRow['customer_phone'] ?? ''),
        'product_name' => (string) ($orderRow['product_name'] ?? ''),
        'status' => (string) ($orderRow['status'] ?? 'new'),
        'payment_status' => (string) ($orderRow['payment_status'] ?? 'pending'),
        'paid_at' => (string) ($orderRow['paid_at'] ?? ''),
        'customer_note' => (string) ($orderRow['customer_note'] ?? ''),
        'admin_note' => (string) ($orderRow['admin_note'] ?? ''),
        'created_at' => (string) ($orderRow['created_at'] ?? ''),
        'preview_views' => $previewViews,
        'front_preview' => (string) ($previewViews['front']['path'] ?? $fallbackPreview),
        'uploads' => $uploads,
        'texts' => $texts,
        'flags' => $flags,
        'shapes' => $shapes,
        'icons' => $icons,
        'fill' => $fill,
        'size_lines' => $sizeLines,
        'add_design' => $addDesign,
        'checkout_summary' => [
            'quantity' => $summaryQuantity,
            'unit_total' => $summaryUnitTotal,
            'order_total' => $summaryOrderTotal,
            'color' => $summaryColor,
            'size' => $summarySize,
        ],
    ];
}

function girffonAdminCustomDesignDemoOrderDetail(): array
{
    return [
        'id' => 0,
        'order_code' => 'DEMO-CUSTOM-0001',
        'customer_name' => 'Demo Customer',
        'customer_email' => 'demo.custom@example.com',
        'customer_phone' => '+39 300 000 0001',
        'product_name' => "Basic Men's T-Shirt",
        'status' => 'reviewing',
        'payment_status' => 'paid',
        'paid_at' => '2026-05-18 11:45:00',
        'customer_note' => 'Customer wants a premium vintage streetwear layout with a flag on the back and strong front typography.',
        'admin_note' => 'Demo placeholder order. Replace with real custom design ingestion later.',
        'created_at' => '2026-05-18 11:30:00',
        'preview_views' => [
            'front' => ['label' => 'Front image', 'path' => 'Image/Custom Design Pro/images/Products/Men/Basic Men\'s T-Shirt/arancione/1.png'],
            'back' => ['label' => 'Back image', 'path' => 'Image/Custom Design Pro/images/Products/Men/Basic Men\'s T-Shirt/arancione/2.png'],
            'right' => ['label' => 'Right sleeve image', 'path' => 'Image/Custom Design Pro/images/Products/Men/Basic Men\'s T-Shirt/arancione/3.png'],
            'left' => ['label' => 'Left sleeve image', 'path' => 'Image/Custom Design Pro/images/Products/Men/Basic Men\'s T-Shirt/arancione/4.png'],
        ],
        'uploads' => [
            ['id' => 0, 'name' => 'street-reference-01.png', 'path' => 'Image/Logo/logo for gif.png', 'download_url' => 'Image/Logo/logo for gif.png', 'size_label' => '128 KB'],
            ['id' => 0, 'name' => 'texture-board-02.png', 'path' => 'Image/Logo/logo for gif.png', 'download_url' => 'Image/Logo/logo for gif.png', 'size_label' => '214 KB'],
            ['id' => 0, 'name' => 'badge-mark-03.png', 'path' => 'Image/Logo/logo for gif.png', 'download_url' => 'Image/Logo/logo for gif.png', 'size_label' => '96 KB'],
            ['id' => 0, 'name' => 'reference-04.png', 'path' => 'Image/Logo/logo for gif.png', 'download_url' => 'Image/Logo/logo for gif.png', 'size_label' => '302 KB'],
        ],
        'texts' => [
            ['content' => 'GirffoN Legacy', 'font_name' => 'Montserrat', 'font_size' => '42 px', 'text_color' => '#f8f1de', 'text_position' => 'Front center', 'text_style' => 'Bold uppercase'],
            ['content' => 'Milano 2026', 'font_name' => 'Cinzel', 'font_size' => '26 px', 'text_color' => '#1f2937', 'text_position' => 'Back shoulder', 'text_style' => 'Serif classic'],
        ],
        'flags' => [
            ['name' => 'Italy', 'code' => 'it', 'image' => 'Image/Custom Design Pro/country-flags-main/country-flags-main/svg/it.svg', 'position' => 'Back upper-right', 'size' => '120 x 80'],
        ],
        'shapes' => [
            ['name' => 'Star', 'color' => '#f59e0b', 'position' => 'Front left chest', 'size' => '140 x 140'],
            ['name' => 'Rounded Square', 'color' => '#111827', 'position' => 'Back center', 'size' => '220 x 220'],
        ],
        'icons' => [
            ['name' => '🔥', 'position' => 'Right sleeve', 'size' => '56 px'],
            ['name' => '⭐', 'position' => 'Left sleeve', 'size' => '48 px'],
        ],
        'fill' => [
            ['name' => 'Sun Yellow', 'value' => '#facc15', 'style' => 'Solid product fill'],
        ],
        'add_design' => [
            ['folder_name' => 'Textures', 'file_name' => 'Gold Texture 01.png', 'image' => 'Image/Logo/logo for gif.png', 'position' => 'Front overlay'],
        ],
    ];
}

function girffonAdminFetchCustomDesignOrderDetail(PDO $pdo, int $orderId): ?array
{
    if ($orderId <= 0) {
        return null;
    }

    if (!girffonAdminEnsureCustomDesignOrderTables($pdo)) {
        return null;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT id, order_code, user_id, customer_name, customer_email, customer_phone, product_name, status, payment_status, paid_at, customer_note, admin_note,
                    preview_front, preview_back, preview_right, preview_left, flag_name, flag_code, flag_image, fill_name, fill_value,
                    design_folder_name, design_file_name, design_image_path, design_payload_json, created_at, updated_at
             FROM custom_design_orders
             WHERE id = :id
             LIMIT 1"
        );
        $statement->execute([':id' => $orderId]);
        $order = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return null;
        }

        $uploadStatement = $pdo->prepare(
            "SELECT id, original_name, stored_name, file_path, mime_type, file_size, sort_order, created_at
             FROM custom_design_uploads
             WHERE order_id = :order_id
             ORDER BY sort_order ASC, id ASC"
        );
        $uploadStatement->execute([':order_id' => $orderId]);
        $uploads = $uploadStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $itemStatement = $pdo->prepare(
            "SELECT id, item_type, item_name, item_label, item_value_json, sort_order, created_at
             FROM custom_design_items
             WHERE order_id = :order_id
             ORDER BY sort_order ASC, id ASC"
        );
        $itemStatement->execute([':order_id' => $orderId]);
        $items = $itemStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return girffonAdminCustomDesignBuildDetail($order, $uploads, $items);
    } catch (PDOException $exception) {
        return null;
    }
}

function girffonAdminUpdateCustomDesignOrder(PDO $pdo, int $orderId, string $status, string $adminNote): bool
{
    if ($orderId <= 0 || !girffonAdminEnsureCustomDesignOrderTables($pdo)) {
        return false;
    }

    $status = strtolower(trim($status));
    if (!in_array($status, girffonAdminCustomDesignOrderStatuses(), true)) {
        $status = 'new';
    }

    try {
        $statement = $pdo->prepare(
            "UPDATE custom_design_orders
             SET status = :status,
                 admin_note = :admin_note,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        return $statement->execute([
            ':status' => $status,
            ':admin_note' => trim($adminNote),
            ':id' => $orderId,
        ]);
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminUpdateCustomDesignOrderPayment(PDO $pdo, int $orderId, string $status, array $paymentData = []): bool
{
    if ($orderId <= 0 || !girffonAdminEnsureCustomDesignOrderTables($pdo)) {
        return false;
    }

    $status = strtolower(trim($status));
    if (!in_array($status, girffonAdminCustomDesignOrderStatuses(), true)) {
        $status = 'paid_review';
    }

    try {
        $customerPayload = is_array($paymentData['customer'] ?? null) ? $paymentData['customer'] : [];
        $statement = $pdo->prepare(
            'SELECT design_payload_json, customer_name, customer_email, customer_phone
             FROM custom_design_orders
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $orderId]);
        $paymentRow = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        $currentPayload = girffonAdminCustomDesignDecodeJson($paymentRow['design_payload_json'] ?? null);
        $currentPayload['payment'] = array_merge(
            is_array($currentPayload['payment'] ?? null) ? $currentPayload['payment'] : [],
            $paymentData,
            [
                'status' => $status,
                'payment_status' => 'paid',
                'paid_at' => date('c'),
            ]
        );

        $update = $pdo->prepare(
            'UPDATE custom_design_orders
             SET status = :status,
                 payment_status = :payment_status,
                 paid_at = CURRENT_TIMESTAMP,
                 customer_name = :customer_name,
                 customer_email = :customer_email,
                 customer_phone = :customer_phone,
                 design_payload_json = :design_payload_json,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $update->execute([
            ':status' => $status,
            ':payment_status' => 'paid',
            ':customer_name' => trim((string) ($customerPayload['name'] ?? ($currentPayload['customer']['name'] ?? ($paymentRow['customer_name'] ?? '')))),
            ':customer_email' => trim((string) ($customerPayload['email'] ?? ($currentPayload['customer']['email'] ?? ($paymentRow['customer_email'] ?? '')))),
            ':customer_phone' => trim((string) ($customerPayload['phone'] ?? ($currentPayload['customer']['phone'] ?? ($paymentRow['customer_phone'] ?? '')))),
            ':design_payload_json' => json_encode($currentPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':id' => $orderId,
        ]);
    } catch (Throwable $exception) {
        return false;
    }
}

function girffonAdminCountCustomDesignOrders(PDO $pdo, array $filters = []): int
{
    $rows = girffonAdminFetchCustomDesignOrderSummaries($pdo, 0, $filters);
    return count(array_filter($rows, static function (array $row): bool {
        return empty($row['is_demo']);
    }));
}

function girffonAdminSumCustomDesignRevenue(PDO $pdo, array $filters = []): float
{
    $rows = girffonAdminFetchCustomDesignOrderSummaries($pdo, 0, $filters);
    return array_reduce($rows, static function (float $carry, array $row): float {
        if (!empty($row['is_demo'])) {
            return $carry;
        }
        return $carry + (float) ($row['order_total'] ?? 0);
    }, 0.0);
}