<?php
require_once __DIR__ . '/common.php';

$userId = girffonProfileRequireUserId();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$payload = girffonProfileRequestData();
$currentPassword = (string) ($payload['current_password'] ?? '');
$newPassword = (string) ($payload['new_password'] ?? '');
$confirmPassword = (string) ($payload['confirm_password'] ?? '');

if ($currentPassword === '' || $newPassword === '') {
    girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Current password and new password are required.']);
}

if ($newPassword !== $confirmPassword) {
    girffonProfileJsonResponse(422, ['success' => false, 'message' => 'New password confirmation does not match.']);
}

if (strlen($newPassword) < 6) {
    girffonProfileJsonResponse(422, ['success' => false, 'message' => 'New password must be at least 6 characters.']);
}

try {
    $user = girffonProfileFetchUserById($pdo, $userId);
    if (!$user || !isset($user['password_hash'])) {
        girffonProfileJsonResponse(404, ['success' => false, 'message' => 'Profile not found.']);
    }

    if (!password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
        girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Current password is incorrect.']);
    }

    $availableColumns = girffonProfileTableColumns($pdo, 'users');
    $sql = 'UPDATE users SET password_hash = :password_hash';
    if (isset($availableColumns['updated_at'])) {
        $sql .= ', updated_at = NOW()';
    }
    $sql .= ' WHERE id = :id';

    $statement = $pdo->prepare($sql);
    $statement->execute([
        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);

    girffonProfileJsonResponse(200, ['success' => true, 'message' => 'Password changed successfully.']);
} catch (PDOException $exception) {
    girffonProfileJsonResponse(500, ['success' => false, 'message' => 'Unable to change password right now.']);
}