<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../profile/communication-common.php';
require_once __DIR__ . '/../utils/order-confirmation-mailer.php';

function girffonAdminTestEmailRedirect(string $type, string $message): void
{
    header('Location: /GirffoN/admin-newsletter.php?' . $type . '=' . rawurlencode($message));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminTestEmailRedirect('error', 'Method not allowed.');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    girffonAdminTestEmailRedirect('error', 'A valid email address is required for the admin test email.');
}

$adminLabel = trim((string) ($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'GirffoN Admin'));
if ($adminLabel === '') {
    $adminLabel = 'GirffoN Admin';
}

$mailConfig = girffonMailConfig();
$subject = 'GirffoN Admin Test Email Preview';
$message = [
    'to_email' => $email,
    'to_name' => $adminLabel,
    'subject' => $subject,
    'html' => '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:24px;background:#f5f1ea;font-family:Georgia,serif;color:#1f1812;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:700px;margin:0 auto;background:#fffdf9;border:1px solid #e5ddd0;"><tr><td style="padding:28px 32px;background:#17181c;color:#f4ebdf;"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#d1a12b;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;">Admin Test Email</h1></td></tr><tr><td style="padding:28px 32px;"><p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Hello ' . htmlspecialchars($adminLabel, ENT_QUOTES, 'UTF-8') . ',</p><p style="margin:0 0 14px;font-size:15px;line-height:1.7;">This is an admin-only email test from the GirffoN newsletter panel. If you received it, the configured mail transport is working for this address.</p><p style="margin:0;color:#7a6a58;font-size:13px;line-height:1.7;">Catalog campaigns are still sent only from the admin newsletter campaign form after selecting subscribers.</p></td></tr></table></body></html>',
    'text' => "GirffoN Admin Test Email Preview\n\nHello {$adminLabel},\n\nThis is an admin-only email test from the GirffoN newsletter panel. If you received it, the configured mail transport is working for this address.\n\nCatalog campaigns are still sent only from the admin newsletter campaign form after selecting subscribers.\n",
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

girffonCommunicationLogTestEmail($pdo, 0, $email, $subject, $sent ? 'sent' : 'failed', $transport, $errorMessage);
girffonCommunicationLogAdminMessage(
    $pdo,
    $adminLabel,
    $email,
    'Admin Test Email ' . ($sent ? 'Sent' : 'Failed'),
    'Admin test email ' . ($sent ? 'sent successfully' : 'failed') . ' for ' . $email . '. Transport: ' . $transport . ($errorMessage !== '' ? ' | Error: ' . $errorMessage : ''),
    'unread'
);

if (!$sent) {
    girffonAdminTestEmailRedirect('error', 'Admin test email could not be sent right now.');
}

girffonAdminTestEmailRedirect('status', 'Admin test email sent successfully to ' . $email . '.');