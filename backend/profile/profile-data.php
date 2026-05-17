<?php
require_once __DIR__ . '/common.php';

$userId = girffonProfileRequireUserId();

try {
    $user = girffonProfileFetchUserById($pdo, $userId);
    if (!$user) {
        girffonProfileJsonResponse(404, [
            'success' => false,
            'message' => 'Profile not found.',
        ]);
    }

    $normalizedUser = girffonProfileNormalizeUserRow($user);
    $normalizedUser['gender'] = (string) ($normalizedUser['gender'] ?? ($user['gender'] ?? ''));
    $normalizedUser['preferred_language'] = (string) ($normalizedUser['preferred_language'] ?? ($user['preferred_language'] ?? ''));
    $normalizedUser['avatar'] = (string) ($normalizedUser['avatar'] ?? ($user['avatar'] ?? ''));

    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'Profile loaded successfully.',
        'user' => $normalizedUser,
    ]);
} catch (PDOException $exception) {
    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to load profile data right now.',
    ]);
}