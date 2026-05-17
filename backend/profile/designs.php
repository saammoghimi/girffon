<?php
require_once __DIR__ . '/common.php';

$userId = girffonProfileRequireUserId();

if (!girffonProfileTableExists($pdo, 'customer_designs')) {
    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'No saved designs yet.',
        'available' => false,
        'items' => [],
    ]);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $payload = girffonProfileRequestData();
    $action = trim((string) ($payload['action'] ?? ''));
    $designId = (int) ($payload['id'] ?? 0);

    if ($action === 'delete' && $designId > 0) {
        $statement = $pdo->prepare('DELETE FROM customer_designs WHERE id = :id AND user_id = :user_id');
        $statement->execute([':id' => $designId, ':user_id' => $userId]);
    } elseif ($action === 'duplicate' && $designId > 0) {
        $sourceStatement = $pdo->prepare('SELECT title, product_name, preview_image, project_url, project_data FROM customer_designs WHERE id = :id AND user_id = :user_id LIMIT 1');
        $sourceStatement->execute([':id' => $designId, ':user_id' => $userId]);
        $source = $sourceStatement->fetch(PDO::FETCH_ASSOC);
        if ($source) {
            $insertStatement = $pdo->prepare(
                'INSERT INTO customer_designs (user_id, title, product_name, preview_image, project_url, project_data, created_at, updated_at)
                 VALUES (:user_id, :title, :product_name, :preview_image, :project_url, :project_data, NOW(), NOW())'
            );
            $insertStatement->execute([
                ':user_id' => $userId,
                ':title' => trim((string) ($source['title'] ?? 'Untitled Design')) . ' Copy',
                ':product_name' => (string) ($source['product_name'] ?? ''),
                ':preview_image' => (string) ($source['preview_image'] ?? ''),
                ':project_url' => (string) ($source['project_url'] ?? ''),
                ':project_data' => (string) ($source['project_data'] ?? ''),
            ]);
        }
    } else {
        girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Unknown design action.']);
    }
}

$statement = $pdo->prepare(
    'SELECT id, title, product_name, preview_image, project_url, created_at
     FROM customer_designs
     WHERE user_id = :user_id
     ORDER BY created_at DESC, id DESC'
);
$statement->execute([':user_id' => $userId]);
$items = array_map(static function (array $item): array {
    return [
        'id' => (int) ($item['id'] ?? 0),
        'title' => (string) (($item['title'] ?? '') !== '' ? $item['title'] : 'Saved Design'),
        'product_name' => (string) ($item['product_name'] ?? ''),
        'preview_image' => (string) ($item['preview_image'] ?? ''),
        'project_url' => (string) ($item['project_url'] ?? ''),
        'created_at' => (string) ($item['created_at'] ?? ''),
    ];
}, $statement->fetchAll(PDO::FETCH_ASSOC) ?: []);

girffonProfileJsonResponse(200, [
    'success' => true,
    'message' => $items ? 'Designs loaded successfully.' : 'No saved designs yet.',
    'available' => true,
    'items' => $items,
]);