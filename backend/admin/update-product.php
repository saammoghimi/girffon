<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/products-data.php";

function girffonAdminRedirectUpdatedProduct(string $type, string $message, int $editId = 0): void
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

function girffonAdminNormalizeUpdatedStatus(string $status): string
{
    $normalized = strtolower(trim($status));
    $allowed = ['active', 'draft', 'archived'];
    return in_array($normalized, $allowed, true) ? $normalized : 'active';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    girffonAdminRedirectUpdatedProduct('error', 'Invalid request method.');
}

girffonAdminEnsureProductsTable($pdo);
$productColumns = girffonAdminGetProductsColumns($pdo);

$productId = max(0, (int) ($_POST['id'] ?? 0));
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
$status = girffonAdminNormalizeUpdatedStatus((string) ($_POST['status'] ?? 'active'));
$imagePath = trim((string) ($_POST['image'] ?? $_POST['imageUrl'] ?? ''));

if ($productId <= 0) {
    girffonAdminRedirectUpdatedProduct('error', 'Invalid product selected.');
}

if ($name === '' || $sku === '' || $price <= 0 || $category === '') {
    girffonAdminRedirectUpdatedProduct('error', 'Please complete all product fields.', $productId);
}

if ($salePrice !== null && $salePrice < 0) {
    girffonAdminRedirectUpdatedProduct('error', 'Sale price cannot be negative.', $productId);
}

if (!girffonAdminFetchProductById($pdo, $productId)) {
    girffonAdminRedirectUpdatedProduct('error', 'Product not found.');
}

try {
    $assignments = [
        'sku = :sku',
        'barcode = :barcode',
        'name = :name',
        'description = :description',
        'price = :price',
        'sale_price = :sale_price',
        'stock = :stock',
        'category = :category',
        'size = :size',
        'color = :color',
        'image = :image',
        'status = :status',
    ];

    $params = [
        ':id' => $productId,
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
        $assignments[] = 'image_path = :image_path';
        $params[':image_path'] = $imagePath !== '' ? $imagePath : null;
    }

    $statement = $pdo->prepare(
        'UPDATE products SET ' . implode(', ', $assignments) . ' WHERE id = :id LIMIT 1'
    );
    $statement->execute($params);

    girffonAdminRedirectUpdatedProduct('status', 'Product updated successfully.');
} catch (PDOException $exception) {
    if ((int) $exception->getCode() === 23000) {
        girffonAdminRedirectUpdatedProduct('error', 'That SKU already exists.', $productId);
    }

    girffonAdminRedirectUpdatedProduct('error', 'Unable to update the product right now.', $productId);
}