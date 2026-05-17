<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/communication-common.php';
require_once __DIR__ . '/../utils/order-confirmation-mailer.php';

$userId = girffonProfileRequireUserId();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
}

$payload = girffonProfileRequestData();
$email = strtolower(trim((string) ($payload['email'] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'A valid email address is required.',
    ]);
}

$user = girffonProfileFetchUserById($pdo, $userId) ?: [];
$name = trim((string) ($user['name'] ?? trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? '')))));
if ($name === '') {
    $name = trim((string) ($user['username'] ?? 'GirffoN Member'));
}

$mailConfig = girffonMailConfig();
$subject = 'GirffoN Test Email Preview';
$message = [
    'to_email' => $email,
    'to_name' => $name !== '' ? $name : 'GirffoN Member',
    'subject' => $subject,
    'html' => '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:24px;background:#f5f1ea;font-family:Georgia,serif;color:#1f1812;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:700px;margin:0 auto;background:#fffdf9;border:1px solid #e5ddd0;"><tr><td style="padding:28px 32px;background:#17181c;color:#f4ebdf;"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#d1a12b;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;">Communication Test Email</h1></td></tr><tr><td style="padding:28px 32px;"><p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Hello ' . htmlspecialchars($name !== '' ? $name : 'GirffoN Member', ENT_QUOTES, 'UTF-8') . ',</p><p style="margin:0 0 14px;font-size:15px;line-height:1.7;">This is a live test email from your GirffoN profile communication preferences. If you received it, the configured SMTP mail system is working for this address.</p><p style="margin:0;color:#7a6a58;font-size:13px;line-height:1.7;">SMS notifications remain available soon until a real SMS provider is connected.</p></td></tr></table></body></html>',
    'text' => "GirffoN Test Email Preview\n\nHello {$name},\n\nThis is a live test email from your GirffoN profile communication preferences.\n\nSMS notifications remain available soon until a real SMS provider is connected.\n",
];

$transport = (string) ($mailConfig['transport'] ?? 'mail');
$errorMessage = '';
$sent = false;

try {
    if ($transport === 'smtp') {
        try {
            $sent = girffonSendMailWithPhpMailer($mailConfig, $message);
            $transport = 'smtp-phpmailer';
        } catch (Throwable $throwable) {
            $errorMessage = $throwable->getMessage();
            $sent = girffonSendMailWithSocketSmtp($mailConfig, $message);
            $transport = 'smtp-socket';
            $errorMessage = '';
        }
    } else {
        $sent = girffonSendMailWithPhpMail($mailConfig, $message);
        $transport = 'php-mail';
    }
} catch (Throwable $throwable) {
    $errorMessage = $throwable->getMessage();
    $sent = false;
}

girffonCommunicationLogTestEmail($pdo, $userId, $email, $subject, $sent ? 'sent' : 'failed', $transport, $errorMessage);
girffonCommunicationLogAdminMessage(
    $pdo,
    $name,
    $email,
    'Test Email ' . ($sent ? 'Sent' : 'Failed'),
    'Test email ' . ($sent ? 'sent successfully' : 'failed') . ' for ' . $email . '. Transport: ' . $transport . ($errorMessage !== '' ? ' | Error: ' . $errorMessage : ''),
    'unread'
);

if (!$sent) {
    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => 'Test email could not be sent right now.',
        'log' => girffonCommunicationFetchLatestTestEmailLog($pdo, $email),
    ]);
}

girffonProfileJsonResponse(200, [
    'success' => true,
    'message' => 'Test email sent successfully.',
    'log' => girffonCommunicationFetchLatestTestEmailLog($pdo, $email),
]);
