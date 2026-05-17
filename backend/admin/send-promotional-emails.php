<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/newsletter-data.php';
require_once __DIR__ . '/promotional-campaign.php';

function girffonAdminPromotionalRedirect(string $type, string $message): void
{
    header('Location: /GirffoN/admin-newsletter.php?' . $type . '=' . rawurlencode($message));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && (string) ($_GET['run'] ?? '') !== '1') {
    girffonAdminPromotionalRedirect('error', 'Method not allowed.');
}

if (!girffonAdminEnsurePromotionalEmailLogsTable($pdo)) {
    girffonAdminPromotionalRedirect('error', 'Unable to prepare promotional email logs.');
}

$mailConfig = girffonMailConfig();
$campaign = girffonAdminPromotionalBuildCampaignConfig($_POST + $_GET, $mailConfig);
$subject = (string) $campaign['subject'];

$campaignId = 'PROMO-' . date('Ymd-His') . '-' . substr(strtoupper(md5((string) microtime(true))), 0, 6);
$audience = girffonAdminFetchPromotionalAudience($pdo);
$sentCount = 0;
$skippedCount = 0;

foreach ($audience as $recipient) {
    $userId = !empty($recipient['user_id']) ? (int) $recipient['user_id'] : null;
    $email = strtolower(trim((string) ($recipient['email'] ?? '')));
    $readyToSend = !empty($recipient['ready_to_send']);
    $skipReason = trim((string) ($recipient['skipped_reason'] ?? ''));

    if (!$readyToSend) {
        $skippedCount++;
        girffonAdminLogPromotionalEmailResult($pdo, [
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'email' => $email,
            'subject' => $subject,
            'status' => 'skipped',
            'transport' => '',
            'error_message' => $skipReason !== '' ? $skipReason : 'Skipped.',
        ]);
        continue;
    }

    $message = girffonAdminPromotionalBuildMessage($recipient, $campaign);
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

    girffonAdminLogPromotionalEmailResult($pdo, [
        'campaign_id' => $campaignId,
        'user_id' => $userId,
        'email' => $email,
        'subject' => $subject,
        'status' => $sent ? 'sent' : 'failed',
        'transport' => $transport,
        'error_message' => $errorMessage,
    ]);

    if ($sent) {
        $sentCount++;
    } else {
        $skippedCount++;
    }
}

girffonAdminPromotionalRedirect('status', 'Promotional emails sent: ' . $sentCount . ' | Skipped: ' . $skippedCount);