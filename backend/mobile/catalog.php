<?php
require_once __DIR__ . '/../admin/products-data.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60, stale-while-revalidate=300');

try {
    $products = girffonAdminFetchProducts($pdo);
    $publicProducts = [];
    foreach ($products as $product) {
        $status = strtolower(trim((string) ($product['status'] ?? 'active')));
        if (!in_array($status, ['', 'active', 'published', 'available'], true)) {
            continue;
        }
        $publicProducts[] = [
            'id' => (int) ($product['id'] ?? 0),
            'sku' => (string) ($product['sku'] ?? ''),
            'name' => (string) ($product['name'] ?? ''),
            'description' => (string) ($product['description'] ?? ''),
            'category' => (string) ($product['category'] ?? ''),
            'price' => (float) ($product['price'] ?? 0),
            'sale_price' => isset($product['effective_price']) ? (float) $product['effective_price'] : (($product['sale_price'] ?? null) !== null ? (float) $product['sale_price'] : null),
            'size' => (string) ($product['size'] ?? ''),
            'color' => (string) ($product['color'] ?? ''),
            'stock' => (int) ($product['stock'] ?? 0),
            'available' => (int) ($product['stock'] ?? 0) > 0,
            'image' => (string) ($product['image'] ?? $product['image_path'] ?? ''),
            'detail_url' => (string) ($product['detail_url'] ?? ''),
            'updated_at' => (string) ($product['updated_at'] ?? ''),
        ];
    }
    echo json_encode([
        'success' => true,
        'source' => 'girffon_shared_products',
        'count' => count($publicProducts),
        'products' => $publicProducts,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log('Mobile catalog API failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Catalog is temporarily unavailable.']);
}
