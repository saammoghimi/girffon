<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/users-data.php";

function girffonAdminRedirectUserEdit(int $userId, string $type, string $message): void
{
    header("Location: /GirffoN/admin-user-edit.php?id=" . rawurlencode((string) $userId) . "&" . $type . "=" . rawurlencode($message));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminRedirectUserEdit(0, 'error', 'Invalid request method.');
}

$userId = max(0, (int) ($_POST['id'] ?? 0));
if ($userId <= 0) {
    header("Location: /GirffoN/admin-users.php?error=" . rawurlencode('Invalid user.'));
    exit;
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$country = trim((string) ($_POST['country'] ?? ''));
$city = trim((string) ($_POST['city'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$role = trim((string) ($_POST['role'] ?? 'customer'));
$status = trim((string) ($_POST['status'] ?? 'active'));

if ($firstName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    girffonAdminRedirectUserEdit($userId, 'error', 'First name and a valid email are required.');
}

if (girffonAdminUserEmailExists($pdo, $email, $userId)) {
    girffonAdminRedirectUserEdit($userId, 'error', 'That email is already in use by another user.');
}

try {
    girffonAdminUpdateUser($pdo, $userId, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'country' => $country,
        'city' => $city,
        'address' => $address,
        'role' => $role !== '' ? strtolower($role) : 'customer',
        'status' => $status !== '' ? strtolower($status) : 'active',
    ]);

    header("Location: /GirffoN/admin-user-view.php?id=" . rawurlencode((string) $userId) . "&status=" . rawurlencode('User updated successfully.'));
    exit;
} catch (PDOException $exception) {
    girffonAdminRedirectUserEdit($userId, 'error', 'Unable to save the user right now.');
}