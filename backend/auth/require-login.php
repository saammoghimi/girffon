<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$redirect = trim((string) ($_GET['redirect'] ?? ''));
$defaultRedirect = '/GirffoN/ProfilePage.php';

if ($redirect === '' || strncmp($redirect, '/GirffoN/', 9) !== 0) {
    $redirect = $defaultRedirect;
}

if (!empty($_SESSION['user_id']) || !empty($_SESSION['girffon_user_id'])) {
    header('Location: ' . $redirect);
    exit;
}

$_SESSION['post_login_redirect'] = $redirect;
header('Location: /GirffoN/index.html');
exit;