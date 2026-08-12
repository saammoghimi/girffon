<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminGetProductsColumns(PDO $pdo): array
{
    $columns = [];
    $statement = $pdo->query("SHOW COLUMNS FROM products");
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[strtolower((string) ($column['Field'] ?? ''))] = true;
    }

    return $columns;
}

function girffonAdminNormalizeProductSkuValue(string $value): string
{
    $normalized = strtoupper(trim($value));
    $normalized = preg_replace('/\s+/', '-', $normalized);
    $normalized = preg_replace('/[^A-Z0-9\-_]/', '', (string) $normalized);
    return trim((string) $normalized, '-_');
}

function girffonAdminBuildProductBarcode(string $sku, string $barcode = ''): string
{
    $normalizedBarcode = strtoupper(trim($barcode));
    $normalizedBarcode = preg_replace('/[^A-Z0-9\-]/', '', (string) $normalizedBarcode);
    if ($normalizedBarcode !== '') {
        return $normalizedBarcode;
    }

    $normalizedSku = girffonAdminNormalizeProductSkuValue($sku);
    if ($normalizedSku === '') {
        return '';
    }

    return 'GRF-' . str_replace('_', '-', $normalizedSku);
}

function girffonAdminNormalizeCatalogText(string $value): string
{
    $normalized = strtolower(trim($value));
    $normalized = str_replace(['&', '/'], [' and ', ' '], $normalized);
    $normalized = preg_replace('/[^a-z0-9]+/', ' ', (string) $normalized);
    return trim((string) preg_replace('/\s+/', ' ', (string) $normalized));
}

function girffonAdminNormalizeCatalogPath(string $value): string
{
    $normalized = str_replace('\\', '/', trim($value));
    return ltrim($normalized, '/');
}

function girffonAdminBuildGeneratedCatalogSku(string $sourceKey): string
{
    $normalized = strtoupper(trim(str_replace([':', '/', ' '], '-', $sourceKey)));
    $normalized = preg_replace('/[^A-Z0-9\-_]/', '-', (string) $normalized);
    $normalized = preg_replace('/-+/', '-', (string) $normalized);
    $normalized = trim((string) $normalized, '-_');
    if ($normalized === '') {
        $normalized = 'STORE-CATALOG-ITEM';
    }

    return girffonAdminNormalizeProductSkuValue('CAT-' . $normalized);
}

function girffonAdminNormalizeDiscountPercent($value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    $percent = (int) round((float) $value);
    return ($percent >= 5 && $percent <= 50) ? $percent : null;
}

function girffonAdminNormalizeDiscountDateTimeValue($value): ?string
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }

    try {
        $date = new DateTimeImmutable($raw);
    } catch (Exception $exception) {
        return null;
    }

    return $date->format('Y-m-d H:i:s');
}

function girffonAdminHydrateProductPricing(array $product, ?DateTimeImmutable $now = null): array
{
    $now = $now ?: new DateTimeImmutable('now');
    $basePrice = round((float) ($product['price'] ?? 0), 2);
    $manualSalePrice = ($product['sale_price'] ?? null) !== null && $product['sale_price'] !== ''
        ? round((float) $product['sale_price'], 2)
        : null;
    $discountEnabled = !empty($product['discount_enabled']);
    $discountPercent = girffonAdminNormalizeDiscountPercent($product['discount_percent'] ?? null);
    $discountLabel = trim((string) ($product['discount_label'] ?? ''));
    $discountStartAt = girffonAdminNormalizeDiscountDateTimeValue($product['discount_start_at'] ?? null);
    $discountEndAt = girffonAdminNormalizeDiscountDateTimeValue($product['discount_end_at'] ?? null);

    $startDate = $discountStartAt !== null ? new DateTimeImmutable($discountStartAt) : null;
    $endDate = $discountEndAt !== null ? new DateTimeImmutable($discountEndAt) : null;

    $discountStatus = 'disabled';
    $effectiveSalePrice = null;
    $saleSource = 'none';
    $saleBadge = '';
    $saleCaption = '';

    if ($discountEnabled && $discountPercent !== null) {
        $startsLater = $startDate instanceof DateTimeImmutable && $startDate > $now;
        $endedAlready = $endDate instanceof DateTimeImmutable && $endDate < $now;

        if ($endedAlready) {
            $discountStatus = 'expired';
        } elseif ($startsLater) {
            $discountStatus = 'scheduled';
        } else {
            $discountStatus = 'active';
            $effectiveSalePrice = round(max(0, $basePrice * (1 - ($discountPercent / 100))), 2);
            $saleSource = 'discount';
            $saleBadge = 'SALE';
            $saleCaption = $discountLabel !== '' ? $discountLabel : ($discountPercent . '% OFF');
        }
    }

    if ($effectiveSalePrice === null && $manualSalePrice !== null && $manualSalePrice > 0 && $manualSalePrice < $basePrice) {
        $effectiveSalePrice = $manualSalePrice;
        $saleSource = 'manual';
        $saleBadge = 'SALE';
        $saleCaption = 'Manual Sale';
        if ($discountStatus === 'disabled') {
            $discountStatus = 'manual';
        }
    }

    $effectivePrice = $effectiveSalePrice !== null ? $effectiveSalePrice : $basePrice;
    $isOnSale = $effectiveSalePrice !== null && $effectiveSalePrice < $basePrice;
    $displayDiscountPercent = $isOnSale && $basePrice > 0
        ? (int) round((1 - ($effectivePrice / $basePrice)) * 100)
        : 0;

    $product['discount_enabled'] = $discountEnabled ? 1 : 0;
    $product['discount_percent'] = $discountPercent;
    $product['discount_label'] = $discountLabel;
    $product['discount_start_at'] = $discountStartAt;
    $product['discount_end_at'] = $discountEndAt;
    $product['discount_status'] = $discountStatus;
    $product['is_discount_active'] = ($saleSource === 'discount') ? 1 : 0;
    $product['is_on_sale'] = $isOnSale ? 1 : 0;
    $product['sale_source'] = $saleSource;
    $product['effective_sale_price'] = $effectiveSalePrice;
    $product['effective_price'] = $effectivePrice;
    $product['sale_badge'] = $saleBadge;
    $product['sale_caption'] = $saleCaption;
    $product['display_discount_percent'] = $displayDiscountPercent;

    return $product;
}

function girffonAdminEnsureProductsTable(PDO $pdo): array
{
    static $cachedColumns = null;

    if (is_array($cachedColumns)) {
        return $cachedColumns;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sku VARCHAR(100) NOT NULL UNIQUE,
            barcode VARCHAR(120) NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sale_price DECIMAL(10,2) NULL,
            discount_enabled TINYINT(1) NOT NULL DEFAULT 0,
            discount_percent TINYINT UNSIGNED NULL,
            discount_label VARCHAR(120) NULL,
            discount_start_at DATETIME NULL,
            discount_end_at DATETIME NULL,
            stock INT NOT NULL DEFAULT 0,
            category VARCHAR(100) NOT NULL,
            size VARCHAR(120) NULL,
            color VARCHAR(120) NULL,
            image VARCHAR(255) NULL,
            detail_url VARCHAR(255) NULL,
            source_key VARCHAR(190) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $columns = girffonAdminGetProductsColumns($pdo);

    $requiredColumns = [
        'barcode' => "ALTER TABLE products ADD COLUMN barcode VARCHAR(120) NULL AFTER sku",
        'description' => "ALTER TABLE products ADD COLUMN description TEXT NULL AFTER name",
        'sale_price' => "ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) NULL AFTER price",
        'discount_enabled' => "ALTER TABLE products ADD COLUMN discount_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER sale_price",
        'discount_percent' => "ALTER TABLE products ADD COLUMN discount_percent TINYINT UNSIGNED NULL AFTER discount_enabled",
        'discount_label' => "ALTER TABLE products ADD COLUMN discount_label VARCHAR(120) NULL AFTER discount_percent",
        'discount_start_at' => "ALTER TABLE products ADD COLUMN discount_start_at DATETIME NULL AFTER discount_label",
        'discount_end_at' => "ALTER TABLE products ADD COLUMN discount_end_at DATETIME NULL AFTER discount_start_at",
        'size' => "ALTER TABLE products ADD COLUMN size VARCHAR(120) NULL AFTER category",
        'color' => "ALTER TABLE products ADD COLUMN color VARCHAR(120) NULL AFTER size",
        'image' => "ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL AFTER category",
        'detail_url' => "ALTER TABLE products ADD COLUMN detail_url VARCHAR(255) NULL AFTER image",
        'source_key' => "ALTER TABLE products ADD COLUMN source_key VARCHAR(190) NULL AFTER detail_url",
        'updated_at' => "ALTER TABLE products ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];

    foreach ($requiredColumns as $columnName => $sql) {
        if (!isset($columns[$columnName])) {
            $pdo->exec($sql);
        }
    }

    if (!isset($columns['image']) && isset($columns['image_path'])) {
        $pdo->exec("UPDATE products SET image = image_path WHERE (image IS NULL OR image = '') AND image_path IS NOT NULL AND image_path <> ''");
    }

    if (!isset($columns['barcode'])) {
        $columns = girffonAdminGetProductsColumns($pdo);
    }

    if (isset($columns['barcode'])) {
        $pdo->exec("UPDATE products SET barcode = CONCAT('GRF-', REPLACE(REPLACE(UPPER(TRIM(sku)), ' ', '-'), '_', '-')) WHERE (barcode IS NULL OR TRIM(barcode) = '') AND sku IS NOT NULL AND TRIM(sku) <> ''");
    }

    $columns = girffonAdminGetProductsColumns($pdo);
    if (isset($columns['source_key'])) {
        try {
            $pdo->exec("CREATE UNIQUE INDEX products_source_key_unique ON products (source_key)");
        } catch (PDOException $exception) {
            // Ignore when the index already exists or existing data prevents creation.
        }
    }

    $cachedColumns = girffonAdminGetProductsColumns($pdo);
    return $cachedColumns;
}

function girffonAdminBuildProductSelect(array $columns): string
{
    $imageSelect = isset($columns['image_path'])
        ? "COALESCE(NULLIF(image, ''), NULLIF(image_path, '')) AS image"
        : "image";

    return "SELECT
                id,
                sku,
                COALESCE(NULLIF(barcode, ''), CONCAT('GRF-', REPLACE(REPLACE(UPPER(TRIM(sku)), ' ', '-'), '_', '-'))) AS barcode,
                name,
                description,
                price,
                sale_price,
                discount_enabled,
                discount_percent,
                discount_label,
                discount_start_at,
                discount_end_at,
                stock,
                category,
                COALESCE(NULLIF(size, ''), '') AS size,
                COALESCE(NULLIF(color, ''), '') AS color,
                " . $imageSelect . ",
                COALESCE(NULLIF(detail_url, ''), '') AS detail_url,
                COALESCE(NULLIF(source_key, ''), '') AS source_key,
                status,
                created_at,
                updated_at
            FROM products";
}

function girffonAdminFetchProducts(PDO $pdo, int $limit = 0): array
{
    try {
        $columns = girffonAdminEnsureProductsTable($pdo);

        $sql = girffonAdminBuildProductSelect($columns) . " ORDER BY updated_at DESC, created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->query($sql);
        $products = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
        return array_map('girffonAdminHydrateProductPricing', $products);
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminFetchProductById(PDO $pdo, int $productId): ?array
{
    if ($productId <= 0) {
        return null;
    }

    try {
        $columns = girffonAdminEnsureProductsTable($pdo);
        $statement = $pdo->prepare(girffonAdminBuildProductSelect($columns) . " WHERE id = :id LIMIT 1");
        $statement->execute([':id' => $productId]);
        $product = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($product) ? girffonAdminHydrateProductPricing($product) : null;
    } catch (PDOException $exception) {
        return null;
    }
}

function girffonAdminCountProducts(PDO $pdo): int
{
    try {
        girffonAdminEnsureProductsTable($pdo);
        $statement = $pdo->query("SELECT COUNT(*) FROM products");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminFetchLowStockProducts(PDO $pdo, int $limit = 0, int $threshold = 15): array
{
    try {
        $columns = girffonAdminEnsureProductsTable($pdo);

        $sql = girffonAdminBuildProductSelect($columns) . "
                WHERE stock <= :threshold
                ORDER BY stock ASC, updated_at DESC, created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->prepare($sql);
        $statement->execute([':threshold' => $threshold]);
        $products = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map('girffonAdminHydrateProductPricing', $products);
    } catch (PDOException $exception) {
        return [];
    }
}