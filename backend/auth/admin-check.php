<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$adminRole = strtolower(trim((string) ($_SESSION['admin_role'] ?? '')));

if ($adminId <= 0 && !empty($_SESSION['admin_user_id'])) {
    $adminId = (int) $_SESSION['admin_user_id'];
}

if ($adminId <= 0 && !empty($_SESSION['girffon_admin_id'])) {
    $adminId = (int) $_SESSION['girffon_admin_id'];
}

if ($adminId <= 0 && $adminRole === 'admin' && !empty($_SESSION['user_id'])) {
    $adminId = (int) $_SESSION['user_id'];
}

if ($adminId <= 0 && $adminRole === 'admin' && !empty($_SESSION['girffon_user_id'])) {
    $adminId = (int) $_SESSION['girffon_user_id'];
}

if ($adminRole === '' && !empty($_SESSION['role']) && strtolower((string) $_SESSION['role']) === 'admin') {
    $adminRole = 'admin';
}

if ($adminId > 0) {
    $_SESSION['admin_id'] = $adminId;
}

if ($adminRole === 'admin') {
    $_SESSION['admin_role'] = 'admin';
}

if ($adminId <= 0 || $adminRole !== 'admin') {
    header('Location: /GirffoN/admin-login.html');
    exit;
}