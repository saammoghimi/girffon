<?php
require_once __DIR__ . '/password-reset-common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonPasswordResetJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
}

$payload = girffonPasswordResetRequestData();
$email = strtolower(trim((string) ($payload['email'] ?? '')));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    girffonPasswordResetJsonResponse(422, [
        'success' => false,
        'message' => 'Enter a valid email address.',
    ]);
}

try {
    girffonPasswordResetEnsureTable($pdo);

    $statement = $pdo->prepare(
        'SELECT id, first_name, last_name, email
         FROM users
         WHERE LOWER(email) = LOWER(:email)
         LIMIT 1'
    );
    $statement->execute([':email' => $email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$user) {
        girffonPasswordResetJsonResponse(200, [
            'success' => true,
            'message' => 'If that email address exists, a reset link has been sent.',
        ]);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $invalidateStatement = $pdo->prepare(
        'UPDATE password_resets
         SET used_at = NOW()
         WHERE user_id = :user_id
           AND used_at IS NULL'
    );
    $invalidateStatement->execute([':user_id' => (int) ($user['id'] ?? 0)]);

    $insertStatement = $pdo->prepare(
        'INSERT INTO password_resets (user_id, email, token_hash, expires_at)
         VALUES (:user_id, :email, :token_hash, :expires_at)'
    );
    $insertStatement->execute([
        ':user_id' => (int) ($user['id'] ?? 0),
        ':email' => (string) ($user['email'] ?? $email),
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
    ]);

    $recipientName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
    girffonPasswordResetSendMail((string) ($user['email'] ?? $email), $recipientName, $token);

    girffonPasswordResetJsonResponse(200, [
        'success' => true,
        'message' => 'If that email address exists, a reset link has been sent.',
    ]);
} catch (Throwable $throwable) {
    girffonPasswordResetJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to send a reset link right now.',
    ]);
}