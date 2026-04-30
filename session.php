<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    gf_json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

$pdo = gf_get_pdo();
gf_start_session();

$userId = isset($_SESSION['girffon_user_id']) ? (int)$_SESSION['girffon_user_id'] : 0;
if ($userId <= 0) {
    gf_json_response(200, [
        'ok' => true,
        'authenticated' => false,
        'user' => null,
    ]);
}

$statement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$statement->execute(['id' => $userId]);
$user = $statement->fetch();

if (!is_array($user)) {
    unset($_SESSION['girffon_user_id']);
    gf_json_response(200, [
        'ok' => true,
        'authenticated' => false,
        'user' => null,
    ]);
}

gf_json_response(200, [
    'ok' => true,
    'authenticated' => true,
    'user' => gf_normalize_user_row($user),
]);