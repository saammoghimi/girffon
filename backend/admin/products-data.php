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
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sale_price DECIMAL(10,2) NULL,
            stock INT NOT NULL DEFAULT 0,
            category VARCHAR(100) NOT NULL,
            image VARCHAR(255) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $columns = girffonAdminGetProductsColumns($pdo);

    $requiredColumns = [
        'description' => "ALTER TABLE products ADD COLUMN description TEXT NULL AFTER name",
        'sale_price' => "ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) NULL AFTER price",
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
                name,
                description,
                price,
                sale_price,
                stock,
                category,
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