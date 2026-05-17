<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/products-data.php";

function girffonAdminRedirectProduct(string $type, string $message, int $editId = 0): void
{
    $query = [
        $type => $message,
    ];
    if ($editId > 0) {
        $query['edit'] = $editId;
    }

    header("Location: ../../admin-products.php?" . http_build_query($query));
    exit;
}

function girffonAdminNormalizeProductStatus(string $status): string
{
    $normalized = strtolower(trim($status));
    $allowed = ['active', 'draft', 'archived'];
    return in_array($normalized, $allowed, true) ? $normalized : 'active';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    girffonAdminRedirectProduct('error', 'Invalid request method.');
}

girffonAdminEnsureProductsTable($pdo);
$productColumns = girffonAdminGetProductsColumns($pdo);

$name = trim((string) ($_POST['name'] ?? ''));
$sku = girffonAdminNormalizeProductSkuValue((string) ($_POST['sku'] ?? ''));
$barcode = girffonAdminBuildProductBarcode($sku, (string) ($_POST['barcode'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$price = (float) ($_POST['price'] ?? 0);
$salePriceInput = trim((string) ($_POST['sale_price'] ?? ''));
$salePrice = $salePriceInput === '' ? null : (float) $salePriceInput;
$stock = max(0, (int) ($_POST['stock'] ?? 0));
$category = trim((string) ($_POST['category'] ?? ''));
$size = trim((string) ($_POST['size'] ?? ''));
$color = trim((string) ($_POST['color'] ?? ''));
$status = girffonAdminNormalizeProductStatus((string) ($_POST['status'] ?? 'active'));
$imagePath = trim((string) ($_POST['image'] ?? $_POST['imageUrl'] ?? ''));

if ($name === '' || $sku === '' || $price <= 0 || $category === '' || $size === '' || $color === '') {
    girffonAdminRedirectProduct('error', 'Please complete all product fields.');
}

if ($salePrice !== null && $salePrice < 0) {
    girffonAdminRedirectProduct('error', 'Sale price cannot be negative.');
}

try {
    $fields = ['sku', 'barcode', 'name', 'description', 'price', 'sale_price', 'stock', 'category', 'size', 'color', 'image', 'status'];
    $params = [
        ':sku' => $sku,
        ':barcode' => $barcode !== '' ? $barcode : null,
        ':name' => $name,
        ':description' => $description !== '' ? $description : null,
        ':price' => $price,
        ':sale_price' => $salePrice,
        ':stock' => $stock,
        ':category' => $category,
        ':size' => $size,
        ':color' => $color,
        ':image' => $imagePath !== '' ? $imagePath : null,
        ':status' => $status,
    ];

    if (isset($productColumns['image_path'])) {
        $fields[] = 'image_path';
        $params[':image_path'] = $imagePath !== '' ? $imagePath : null;
    }

    $statement = $pdo->prepare(
        'INSERT INTO products (' . implode(', ', $fields) . ') VALUES (' . implode(', ', array_keys($params)) . ')'
    );
    $statement->execute($params);

    girffonAdminRedirectProduct('status', 'Product saved successfully.');
} catch (PDOException $exception) {
    if ((int) $exception->getCode() === 23000) {
        girffonAdminRedirectProduct('error', 'That SKU already exists.');
    }

    girffonAdminRedirectProduct('error', 'Unable to save the product right now.');
}