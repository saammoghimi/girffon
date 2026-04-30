<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    gf_json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

gf_start_session();

$pdo = gf_get_pdo();
$payload = gf_read_json_input();

$identifier = trim((string)($payload['identifier'] ?? ''));
$password = (string)($payload['password'] ?? '');

if ($identifier === '' || $password === '') {
    gf_json_response(422, ['ok' => false, 'message' => 'Identifier and password are required.']);
}

$user = gf_find_user_by_identifier($pdo, strtolower($identifier));
if (!is_array($user) && $identifier !== strtolower($identifier)) {
    $user = gf_find_user_by_identifier($pdo, $identifier);
}

if (!is_array($user) || !password_verify($password, (string)($user['password_hash'] ?? ''))) {
    gf_json_response(401, ['ok' => false, 'message' => 'Invalid login credentials.']);
}

$pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => (int)$user['id']]);
$_SESSION['girffon_user_id'] = (int)$user['id'];

$freshUser = gf_require_logged_in_user($pdo);

gf_json_response(200, [
    'ok' => true,
    'message' => 'Login successful.',
    'user' => gf_normalize_user_row($freshUser),
]);