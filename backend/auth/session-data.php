<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$userId = (int) ($_SESSION['user_id'] ?? $_SESSION['girffon_user_id'] ?? 0);
$redirectUrl = '/GirffoN/backend/auth/require-login.php?redirect=' . rawurlencode('/GirffoN/CartTest.html');

if ($userId <= 0) {
    http_response_code(200);
    echo json_encode([
        'authenticated' => false,
        'redirectUrl' => $redirectUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$statement = $pdo->prepare('SELECT id, username, first_name, last_name, email, phone, country, city, address FROM users WHERE id = :id LIMIT 1');
$statement->execute([':id' => $userId]);
$user = $statement->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(200);
    echo json_encode([
        'authenticated' => false,
        'redirectUrl' => $redirectUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'redirectUrl' => $redirectUrl,
    'user' => [
        'id' => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ''),
        'first_name' => (string) ($user['first_name'] ?? ''),
        'last_name' => (string) ($user['last_name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'country' => (string) ($user['country'] ?? ''),
        'city' => (string) ($user['city'] ?? ''),
        'address' => (string) ($user['address'] ?? ''),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);