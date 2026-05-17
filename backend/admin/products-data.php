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
            stock INT NOT NULL DEFAULT 0,
            category VARCHAR(100) NOT NULL,
            size VARCHAR(120) NULL,
            color VARCHAR(120) NULL,
            image VARCHAR(255) NULL,
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
        'size' => "ALTER TABLE products ADD COLUMN size VARCHAR(120) NULL AFTER category",
        'color' => "ALTER TABLE products ADD COLUMN color VARCHAR(120) NULL AFTER size",
        'image' => "ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL AFTER category",
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
                stock,
                category,
                COALESCE(NULLIF(size, ''), '') AS size,
                COALESCE(NULLIF(color, ''), '') AS color,
                " . $imageSelect . ",
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
        return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
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
        return is_array($product) ? $product : null;
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
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}