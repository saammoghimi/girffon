<?php
require_once __DIR__ . '/common.php';

function girffonProfileDesignLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', trim($value) !== '' ? $value : 'new'));
}

function girffonProfileDesignNormalizePath(?string $path): string
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

$userId = girffonProfileRequireUserId();
$user = girffonProfileFetchUserById($pdo, $userId) ?: [];
$userEmail = strtolower(trim((string) ($user['email'] ?? '')));

if (!girffonProfileTableExists($pdo, 'custom_design_orders')) {
    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'No saved designs yet.',
        'available' => false,
        'items' => [],
    ]);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    girffonProfileJsonResponse(405, ['success' => false, 'message' => 'Custom design profile actions are read-only.']);
}

try {
    $params = [':user_id' => $userId];
    $where = 'user_id = :user_id';

    if ($userEmail !== '') {
        $where .= ' OR ((user_id IS NULL OR user_id = 0) AND LOWER(customer_email) = :customer_email)';
        $params[':customer_email'] = $userEmail;
    }

    $statement = $pdo->prepare(
        'SELECT id, order_code, product_name, status, created_at, preview_front, preview_back, preview_right, preview_left
         FROM custom_design_orders
         WHERE ' . $where . '
         ORDER BY created_at DESC, id DESC'
    );
    $statement->execute($params);
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $exception) {
    $rows = [];
}

$items = array_map(static function (array $item): array {
    $frontPreview = girffonProfileDesignNormalizePath((string) ($item['preview_front'] ?? ''));
    $fallbackPreview = $frontPreview !== ''
        ? $frontPreview
        : girffonProfileDesignNormalizePath((string) (($item['preview_back'] ?? '') !== '' ? $item['preview_back'] : (($item['preview_right'] ?? '') !== '' ? $item['preview_right'] : ($item['preview_left'] ?? ''))));
    $orderCode = (string) ($item['order_code'] ?? '');

    return [
        'id' => (int) ($item['id'] ?? 0),
        'order_number' => $orderCode !== '' ? $orderCode : ('CUSTOM-' . (int) ($item['id'] ?? 0)),
        'product_name' => (string) ($item['product_name'] ?? ''),
        'status' => (string) ($item['status'] ?? 'new'),
        'status_label' => girffonProfileDesignLabel((string) ($item['status'] ?? 'new')),
        'created_at' => (string) ($item['created_at'] ?? ''),
        'preview_image' => $fallbackPreview,
        'view_url' => $fallbackPreview,
        'download_url' => $fallbackPreview,
        'download_name' => strtolower(trim($orderCode)) !== ''
            ? preg_replace('/[^a-z0-9._-]+/i', '-', strtolower(trim($orderCode))) . '-front-preview.png'
            : 'custom-order-preview.png',
    ];
}, $rows);

girffonProfileJsonResponse(200, [
    'success' => true,
    'message' => $items ? 'Custom design orders loaded successfully.' : 'No saved designs yet.',
    'available' => true,
    'items' => $items,
]);