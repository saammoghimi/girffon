<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/../admin/products-data.php';

function girffonProfileWishlistNormalizePath(?string $path): string
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
    if (strpos($path, $workspaceRoot) === 0) {
        $normalizedPath = ltrim(substr($path, strlen($workspaceRoot)), '/');
    }

    return $normalizedPath;
}

function girffonProfileWishlistBuildViewUrl(array $item, array $product = []): string
{
    foreach (['product_url', 'product_page', 'url', 'href'] as $field) {
        $value = trim((string) ($item[$field] ?? $product[$field] ?? ''));
        if ($value !== '') {
            return girffonProfileWishlistNormalizePath($value);
        }
    }

    $sku = trim((string) (($item['sku'] ?? '') !== '' ? $item['sku'] : ($product['sku'] ?? '')));
    if ($sku !== '') {
        return '/GirffoN/ProductDetails.html?sku=' . rawurlencode($sku);
    }

    return '/GirffoN/ProductDetails.html';
}

function girffonProfileWishlistEmpty(string $message = 'No saved items yet.'): void
{
    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => $message,
        'available' => false,
        'items' => [],
    ]);
}

$userId = girffonProfileRequireUserId();

if (!girffonProfileTableExists($pdo, 'wishlist_items')) {
    girffonProfileWishlistEmpty();
}

$wishlistColumns = girffonProfileTableColumns($pdo, 'wishlist_items');
$productsAvailable = girffonProfileTableExists($pdo, 'products');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $payload = girffonProfileRequestData();
    $action = trim((string) ($payload['action'] ?? ''));
    $itemId = (int) ($payload['id'] ?? 0);

    if ($action === 'remove' && $itemId > 0) {
        $statement = $pdo->prepare('DELETE FROM wishlist_items WHERE id = :id AND user_id = :user_id');
        $statement->execute([':id' => $itemId, ':user_id' => $userId]);
    } else {
        girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Unknown wishlist action.']);
    }
}

$selectColumns = array_intersect_key([
    'id' => true,
    'user_id' => true,
    'product_id' => true,
    'sku' => true,
    'name' => true,
    'price' => true,
    'image_path' => true,
    'product_url' => true,
    'product_page' => true,
    'url' => true,
    'href' => true,
    'size' => true,
    'color' => true,
    'created_at' => true,
], $wishlistColumns);

$statement = $pdo->prepare(
    'SELECT ' . implode(', ', array_keys($selectColumns)) . '
     FROM wishlist_items
     WHERE user_id = :user_id
     ORDER BY id DESC'
);
$statement->execute([':user_id' => $userId]);
$wishlistItems = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

$productsById = [];
if ($productsAvailable) {
    $productColumns = girffonProfileTableColumns($pdo, 'products');
    $productIds = array_values(array_filter(array_map(static function (array $item): int {
        return (int) ($item['product_id'] ?? 0);
    }, $wishlistItems)));

    if ($productIds) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $productSelectColumns = array_keys(array_intersect_key([
            'id' => true,
            'name' => true,
            'sku' => true,
            'price' => true,
            'sale_price' => true,
            'discount_enabled' => true,
            'discount_percent' => true,
            'discount_label' => true,
            'discount_start_at' => true,
            'discount_end_at' => true,
            'stock' => true,
            'status' => true,
            'image_path' => true,
            'product_url' => true,
            'product_page' => true,
            'url' => true,
            'href' => true,
        ], $productColumns));
        if (!$productSelectColumns) {
            $productSelectColumns = ['id'];
        }
        $productStatement = $pdo->prepare(
            'SELECT ' . implode(', ', $productSelectColumns) . '
             FROM products
             WHERE id IN (' . $placeholders . ')'
        );
        $productStatement->execute($productIds);
        foreach ($productStatement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $product) {
            $product = girffonAdminHydrateProductPricing($product);
            $productsById[(int) ($product['id'] ?? 0)] = $product;
        }
    }
}

$items = array_map(static function (array $item) use ($productsById): array {
    $product = $productsById[(int) ($item['product_id'] ?? 0)] ?? [];
    $name = (string) (($item['name'] ?? '') !== '' ? $item['name'] : ($product['name'] ?? 'Saved Product'));
    $sku = (string) (($item['sku'] ?? '') !== '' ? $item['sku'] : ($product['sku'] ?? ''));
    $price = (float) (($product['effective_price'] ?? '') !== ''
        ? $product['effective_price']
        : (($item['price'] ?? '') !== '' ? $item['price'] : ($product['price'] ?? 0)));
    $originalPrice = (float) (($product['price'] ?? '') !== '' ? $product['price'] : $price);
    $isOnSale = !empty($product['is_on_sale']) && $originalPrice > $price;
    $image = girffonProfileWishlistNormalizePath((string) (($item['image_path'] ?? '') !== '' ? $item['image_path'] : ($product['image_path'] ?? '')));
    $stock = (int) ($product['stock'] ?? 0);
    $status = strtolower((string) ($product['status'] ?? 'active'));
    $viewUrl = girffonProfileWishlistBuildViewUrl($item, $product);

    return [
        'id' => (int) ($item['id'] ?? 0),
        'product_id' => (int) ($item['product_id'] ?? 0),
        'name' => $name,
        'sku' => $sku,
        'price' => $price,
        'price_label' => 'EUR ' . number_format($price, 2, '.', ''),
        'original_price' => $originalPrice,
        'original_price_label' => 'EUR ' . number_format($originalPrice, 2, '.', ''),
        'sale_price' => $isOnSale ? $price : null,
        'sale_price_label' => $isOnSale ? ('EUR ' . number_format($price, 2, '.', '')) : '',
        'is_on_sale' => $isOnSale,
        'sale_badge' => (string) ($product['sale_badge'] ?? ''),
        'sale_caption' => (string) ($product['sale_caption'] ?? ''),
        'display_discount_percent' => (int) ($product['display_discount_percent'] ?? 0),
        'image' => $image,
        'size' => (string) ($item['size'] ?? ''),
        'color' => (string) ($item['color'] ?? ''),
        'view_url' => $viewUrl,
        'stock_label' => $stock > 5 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock'),
        'stock_class' => $stock > 5 ? 'is-in-stock' : ($stock > 0 ? 'is-low-stock' : 'is-out-of-stock'),
        'can_add_to_cart' => $price > 0 && $status === 'active',
        'source' => 'backend',
    ];
}, $wishlistItems);

girffonProfileJsonResponse(200, [
    'success' => true,
    'message' => $items ? 'Wishlist loaded successfully.' : 'No saved items yet.',
    'available' => true,
    'items' => $items,
]);