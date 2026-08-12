<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/products-data.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'products' => [],
        'error' => 'database_unavailable',
    ]);
    exit;
}

try {
    $products = array_values(array_filter(
        girffonAdminFetchProducts($pdo),
        static function (array $product): bool {
            $status = strtolower(trim((string) ($product['status'] ?? 'active')));
            return $status === '' || $status === 'active';
        }
    ));

    $payload = array_map(
        static function (array $product): array {
            $effectiveSalePrice = $product['effective_sale_price'] ?? null;

            return [
                'id' => (int) ($product['id'] ?? 0),
                'sku' => (string) ($product['sku'] ?? ''),
                'barcode' => (string) ($product['barcode'] ?? ''),
                'name' => (string) ($product['name'] ?? ''),
                'category' => (string) ($product['category'] ?? ''),
                'price' => round((float) ($product['price'] ?? 0), 2),
                'effective_price' => round((float) ($product['effective_price'] ?? 0), 2),
                'effective_sale_price' => $effectiveSalePrice !== null ? round((float) $effectiveSalePrice, 2) : null,
                'is_on_sale' => !empty($product['is_on_sale']),
                'sale_badge' => (string) ($product['sale_badge'] ?? ''),
                'sale_caption' => (string) ($product['sale_caption'] ?? ''),
                'display_discount_percent' => (int) ($product['display_discount_percent'] ?? 0),
                'updated_at' => (string) ($product['updated_at'] ?? ''),
            ];
        },
        $products
    );

    echo json_encode([
        'success' => true,
        'products' => $payload,
        'generated_at' => gmdate('c'),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'products' => [],
        'error' => 'pricing_load_failed',
    ]);
}