<?php
require_once __DIR__ . '/common.php';

$userId = girffonProfileRequireUserId();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$payload = girffonProfileRequestData();
$confirmation = trim((string) ($payload['confirmation'] ?? ''));

if (strtoupper($confirmation) !== 'DELETE') {
    girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Please confirm account deletion.']);
}

$availableColumns = girffonProfileTableColumns($pdo, 'users');
$assignments = [];

if (isset($availableColumns['status'])) {
    $assignments[] = "status = 'disabled'";
}

if (isset($availableColumns['updated_at'])) {
    $assignments[] = 'updated_at = NOW()';
}

if (!$assignments) {
    girffonProfileJsonResponse(500, ['success' => false, 'message' => 'Account disable is not available for this database schema.']);
}

try {
    $statement = $pdo->prepare('UPDATE users SET ' . implode(', ', $assignments) . ' WHERE id = :id');
    $statement->execute([':id' => $userId]);

    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    girffonProfileJsonResponse(200, ['success' => true, 'message' => 'Account disabled successfully.']);
} catch (PDOException $exception) {
    girffonProfileJsonResponse(500, ['success' => false, 'message' => 'Unable to disable account right now.']);
}