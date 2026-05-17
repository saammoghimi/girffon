<?php
require_once __DIR__ . '/password-reset-common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonPasswordResetJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
}

$payload = girffonPasswordResetRequestData();
$token = trim((string) ($payload['token'] ?? ''));
$newPassword = (string) ($payload['new_password'] ?? '');
$confirmPassword = (string) ($payload['confirm_password'] ?? '');

if ($token === '') {
    girffonPasswordResetJsonResponse(422, [
        'success' => false,
        'message' => 'Reset token is required.',
    ]);
}

if ($newPassword === '' || $confirmPassword === '') {
    girffonPasswordResetJsonResponse(422, [
        'success' => false,
        'message' => 'New password and confirmation are required.',
    ]);
}

if ($newPassword !== $confirmPassword) {
    girffonPasswordResetJsonResponse(422, [
        'success' => false,
        'message' => 'Password confirmation does not match.',
    ]);
}

if (strlen($newPassword) < 6) {
    girffonPasswordResetJsonResponse(422, [
        'success' => false,
        'message' => 'New password must be at least 6 characters.',
    ]);
}

try {
    girffonPasswordResetEnsureTable($pdo);

    $resetRow = girffonPasswordResetFindActiveRow($pdo, $token);
    if (!$resetRow) {
        girffonPasswordResetJsonResponse(422, [
            'success' => false,
            'message' => 'This reset link is invalid or has expired.',
        ]);
    }

    $updateUser = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :user_id');
    $updateUser->execute([
        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':user_id' => (int) ($resetRow['user_id'] ?? 0),
    ]);

    $markUsed = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
    $markUsed->execute([':id' => (int) ($resetRow['id'] ?? 0)]);

    girffonPasswordResetJsonResponse(200, [
        'success' => true,
        'message' => 'Password reset successfully.',
    ]);
} catch (Throwable $throwable) {
    girffonPasswordResetJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to reset password right now.',
    ]);
}