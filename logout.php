<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
    gf_json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

gf_start_session();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

gf_json_response(200, [
    'ok' => true,
    'message' => 'Logged out successfully.',
]);