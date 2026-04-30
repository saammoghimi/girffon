<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && isset($_SESSION['girffon_user_id'])) {
    $_SESSION['user_id'] = (int) $_SESSION['girffon_user_id'];
}

$isDirectRequest = isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__;

if ($isDirectRequest) {
    require_once __DIR__ . '/../config/database.php';

    header('Content-Type: application/json; charset=utf-8');

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        echo json_encode([
            'authenticated' => false,
            'user' => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $statement = $pdo->prepare('SELECT id, username, first_name, last_name, email, phone, country, city, address FROM users WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $userId]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'authenticated' => false,
            'user' => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode([
        'authenticated' => true,
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
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: /GirffoN/Index.html');
    exit;
}