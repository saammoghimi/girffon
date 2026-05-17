<?php
require_once __DIR__ . '/common.php';

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
    $productIds = array_values(array_filter(array_map(static function (array $item): int {
        return (int) ($item['product_id'] ?? 0);
    }, $wishlistItems)));

    if ($productIds) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $productStatement = $pdo->prepare(
            'SELECT id, name, sku, price, stock, status, image_path
             FROM products
             WHERE id IN (' . $placeholders . ')'
        );
        $productStatement->execute($productIds);
        foreach ($productStatement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $product) {
            $productsById[(int) ($product['id'] ?? 0)] = $product;
        }
    }
}

$items = array_map(static function (array $item) use ($productsById): array {
    $product = $productsById[(int) ($item['product_id'] ?? 0)] ?? [];
    $name = (string) (($item['name'] ?? '') !== '' ? $item['name'] : ($product['name'] ?? 'Saved Product'));
    $sku = (string) (($item['sku'] ?? '') !== '' ? $item['sku'] : ($product['sku'] ?? ''));
    $price = (float) (($item['price'] ?? '') !== '' ? $item['price'] : ($product['price'] ?? 0));
    $image = (string) (($item['image_path'] ?? '') !== '' ? $item['image_path'] : ($product['image_path'] ?? ''));
    $stock = (int) ($product['stock'] ?? 0);
    $status = strtolower((string) ($product['status'] ?? 'active'));

    return [
        'id' => (int) ($item['id'] ?? 0),
        'product_id' => (int) ($item['product_id'] ?? 0),
        'name' => $name,
        'sku' => $sku,
        'price' => $price,
        'price_label' => 'EUR' . number_format($price, 2, '.', ''),
        'image' => $image,
        'size' => (string) ($item['size'] ?? ''),
        'color' => (string) ($item['color'] ?? ''),
        'stock_label' => $stock > 5 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock'),
        'stock_class' => $stock > 5 ? 'is-in-stock' : ($stock > 0 ? 'is-low-stock' : 'is-out-of-stock'),
        'can_add_to_cart' => $price > 0 && $status === 'active',
    ];
}, $wishlistItems);

girffonProfileJsonResponse(200, [
    'success' => true,
    'message' => $items ? 'Wishlist loaded successfully.' : 'No saved items yet.',
    'available' => true,
    'items' => $items,
]);