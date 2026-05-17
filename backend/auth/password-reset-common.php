<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../utils/order-confirmation-mailer.php';

function girffonPasswordResetJsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonPasswordResetRequestData(): array
{
    $post = is_array($_POST) ? $_POST : [];
    $rawPayload = file_get_contents('php://input');
    if (!is_string($rawPayload) || trim($rawPayload) === '') {
        return $post;
    }

    $decoded = json_decode($rawPayload, true);
    if (is_array($decoded)) {
        return array_merge($post, $decoded);
    }

    return $post;
}

function girffonPasswordResetEnsureTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_resets_user_id (user_id),
            INDEX idx_password_resets_email (email),
            INDEX idx_password_resets_token_hash (token_hash),
            INDEX idx_password_resets_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function girffonPasswordResetBuildLink(string $token): string
{
    $mailConfig = girffonMailConfig();
    $appUrl = trim((string) ($mailConfig['app_url'] ?? ''));
    if ($appUrl === '') {
        $appUrl = 'https://girffon.shop/GirffoN';
    }

    return rtrim($appUrl, '/') . '/reset-password.php?token=' . rawurlencode($token);
}

function girffonPasswordResetSendMail(string $email, string $name, string $token): bool
{
    $mailConfig = girffonMailConfig();
    $resetLink = girffonPasswordResetBuildLink($token);
    $subject = 'Reset your GirffoN password';
    $recipientName = trim($name) !== '' ? trim($name) : 'GirffoN Member';

    $message = [
        'to_email' => $email,
        'to_name' => $recipientName,
        'subject' => $subject,
        'html' => '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:24px;background:#f5f1ea;font-family:Georgia,serif;color:#1f1812;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:700px;margin:0 auto;background:#fffdf9;border:1px solid #e5ddd0;">'
            . '<tr><td style="padding:28px 32px;background:#17181c;color:#f4ebdf;"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#d1a12b;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;">Reset your password</h1></td></tr>'
            . '<tr><td style="padding:28px 32px;">'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Hello ' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Click the secure link below to reset your password.</p>'
            . '<p style="margin:24px 0;"><a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:14px 22px;background:#1f1812;color:#f4ebdf;text-decoration:none;font-weight:600;">Reset Password</a></p>'
            . '<p style="margin:0 0 14px;font-size:13px;line-height:1.7;color:#7a6a58;">If the button does not open directly, copy this link into your browser:</p>'
            . '<p style="margin:0;font-size:13px;line-height:1.7;"><a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '</td></tr></table></body></html>',
        'text' => "Reset your GirffoN password\n\nHello {$recipientName},\n\nClick the secure link below to reset your password.\n\n{$resetLink}\n",
    ];

    $transport = (string) ($mailConfig['transport'] ?? 'mail');
    if ($transport === 'smtp') {
        try {
            return girffonSendMailWithPhpMailer($mailConfig, $message);
        } catch (Throwable $throwable) {
            girffonOrderMailDebugLog($mailConfig, 'Password reset PHPMailer SMTP failed: ' . $throwable->getMessage());
            return girffonSendMailWithSocketSmtp($mailConfig, $message);
        }
    }

    return girffonSendMailWithPhpMail($mailConfig, $message);
}

function girffonPasswordResetFindActiveRow(PDO $pdo, string $token): ?array
{
    $tokenHash = hash('sha256', $token);
    $statement = $pdo->prepare(
        'SELECT id, user_id, email, token_hash, expires_at, used_at, created_at
         FROM password_resets
         WHERE token_hash = :token_hash
           AND used_at IS NULL
           AND expires_at > NOW()
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute([':token_hash' => $tokenHash]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}