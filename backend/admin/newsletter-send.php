<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/newsletter-data.php';
require_once __DIR__ . '/../utils/order-confirmation-mailer.php';

function girffonAdminNewsletterDebugLog(array $context): void
{
    $logDirectory = dirname(__DIR__) . '/logs';
    if (!is_dir($logDirectory)) {
        @mkdir($logDirectory, 0777, true);
    }

    $line = json_encode([
        'timestamp' => date('c'),
        'source' => (string) ($context['source'] ?? ''),
        'attachment_url' => (string) ($context['attachment_url'] ?? ''),
        'upload_error' => isset($context['upload_error']) ? (int) $context['upload_error'] : null,
        'original_name' => (string) ($context['original_name'] ?? ''),
        'error' => (string) ($context['error'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($line === false) {
        $line = '{"timestamp":"' . date('c') . '","error":"Unable to encode newsletter send debug context."}';
    }

    @file_put_contents($logDirectory . '/newsletter-send-debug.log', $line . PHP_EOL, FILE_APPEND);
}

function girffonAdminNewsletterFetchSubscribedRowsByEmails(PDO $pdo, array $emails): array
{
    if (function_exists('girffonEnsureNewsletterSubscribersTable')) {
        girffonEnsureNewsletterSubscribersTable($pdo);
    }
    if (function_exists('girffonEnsureUserPreferencesTable')) {
        girffonEnsureUserPreferencesTable($pdo);
    }

    $normalizedEmails = array_values(array_unique(array_filter(array_map(static function ($value) {
        $email = strtolower(trim((string) $value));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }, $emails))));

    if (!$normalizedEmails) {
        return [];
    }

    $placeholders = [];
    $params = [];
    foreach ($normalizedEmails as $index => $email) {
        $placeholder = ':email_' . $index;
        $placeholders[] = $placeholder;
        $params[$placeholder] = $email;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT
                ns.id AS subscriber_id,
                COALESCE(
                    NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''),
                    NULLIF(u.username, ''),
                    LOWER(TRIM(ns.email))
                ) AS name,
                LOWER(TRIM(ns.email)) AS email,
                COALESCE(NULLIF(u.phone, ''), '') AS phone,
                COALESCE(up.catalog_emails, 1) AS catalog_emails,
                COALESCE(up.promotional_emails, 1) AS promotional_emails,
                COALESCE(up.birthday_discount_emails, 1) AS birthday_discount_emails,
                COALESCE(ns.subscribed_at, ns.updated_at) AS subscribed_at,
                LOWER(TRIM(COALESCE(ns.status, 'subscribed'))) AS status,
                1 AS is_active,
                CASE WHEN COALESCE(up.catalog_emails, 1) <> 0 THEN 1 ELSE 0 END AS is_eligible,
                COALESCE(ns.user_id, u.id, 0) AS user_id
             FROM newsletter_subscribers ns
             LEFT JOIN users u ON u.id = ns.user_id
             LEFT JOIN user_preferences up ON up.user_id = COALESCE(ns.user_id, u.id, 0)
             WHERE LOWER(TRIM(COALESCE(ns.status, 'subscribed'))) = 'subscribed'
               AND LOWER(TRIM(ns.email)) IN (" . implode(', ', $placeholders) . ")
             ORDER BY COALESCE(ns.subscribed_at, ns.updated_at) DESC, ns.id DESC"
        );
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminNewsletterRedirect(string $type, string $message): void
{
    header('Location: /GirffoN/admin-newsletter.php?' . $type . '=' . rawurlencode($message));
    exit;
}

function girffonAdminNewsletterBuildAppUrl(array $mailConfig): string
{
    if (!empty($mailConfig['app_url'])) {
        return rtrim((string) $mailConfig['app_url'], '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/GirffoN/backend/admin/newsletter-send.php'));
    $segments = explode('/', trim($scriptPath, '/'));
    $rootSegment = $segments[0] ?? 'GirffoN';

    return $scheme . '://' . $host . '/' . $rootSegment;
}

function girffonAdminNewsletterPublicUploadUrl(string $targetPath, array $mailConfig): string
{
    $normalized = str_replace('\\', '/', $targetPath);
    $workspaceRoot = str_replace('\\', '/', dirname(__DIR__, 2));
    $relative = ltrim(str_replace($workspaceRoot, '', $normalized), '/');

    return girffonAdminNewsletterBuildAppUrl($mailConfig) . '/' . $relative;
}

function girffonAdminNewsletterHandleUpload(array $file, array $mailConfig): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Catalog upload failed.');
    }

    $originalName = (string) ($file['name'] ?? 'catalog-file');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'html'], true)) {
        throw new RuntimeException('Only PDF or HTML catalog files are allowed for upload.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Uploaded catalog file is not valid.');
    }

    $targetDir = dirname(__DIR__, 2) . '/uploads/newsletters';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to create newsletter upload directory.');
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
    $safeBase = trim((string) $safeBase, '-');
    if ($safeBase === '') {
        $safeBase = 'catalog';
    }

    $targetExtension = $extension !== '' ? $extension : 'html';
    $targetPath = $targetDir . '/' . date('Ymd-His') . '-' . $safeBase . '.' . $targetExtension;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Unable to store the uploaded catalog file.');
    }

    return girffonAdminNewsletterPublicUploadUrl($targetPath, $mailConfig);
}

function girffonAdminNewsletterBuildMessage(array $recipient, string $subject, string $messageBody, string $catalogUrl): array
{
    $recipientName = trim((string) ($recipient['name'] ?? 'GirffoN Member'));
    if ($recipientName === '') {
        $recipientName = 'GirffoN Member';
    }

    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'));
    $catalogCta = '';
    $catalogText = '';
    if ($catalogUrl !== '') {
        $safeUrl = htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8');
        $catalogCta = '<p style="margin:22px 0 0;"><a href="' . $safeUrl . '" style="display:inline-block;padding:12px 20px;background:#c9a56a;color:#1f1812;text-decoration:none;font-weight:600;">View Catalog</a></p>';
        $catalogText = "\n\nCatalog: {$catalogUrl}";
    }

    return [
        'to_email' => (string) ($recipient['email'] ?? ''),
        'to_name' => $recipientName,
        'subject' => $subject,
        'html' => '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:24px;background:#f5f1ea;font-family:Georgia,Times New Roman,serif;color:#1f1812;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;margin:0 auto;background:#fffdf9;border:1px solid #e5ddd0;"><tr><td style="padding:30px 34px;background:#1f1812;color:#f4ebdf;"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#d1a12b;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;">' . $safeSubject . '</h1></td></tr><tr><td style="padding:30px 34px;"><p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Hello ' . $safeName . ',</p><div style="font-size:15px;line-height:1.8;color:#2b241b;">' . $safeMessage . '</div>' . $catalogCta . '<p style="margin:26px 0 0;color:#7a6a58;font-size:13px;line-height:1.7;">You are receiving this catalog message because Catalog Emails are enabled on your GirffoN account or subscription.</p></td></tr></table></body></html>',
        'text' => "{$subject}\n\nHello {$recipientName},\n\n{$messageBody}{$catalogText}\n\nYou are receiving this catalog message because Catalog Emails are enabled on your GirffoN account or subscription.",
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminNewsletterRedirect('error', 'Method not allowed.');
}

$selectedEmails = $_POST['selected_emails'] ?? [];
if (!is_array($selectedEmails)) {
    $selectedEmails = [];
}
$subject = trim((string) ($_POST['subject'] ?? ''));
$messageBody = trim((string) ($_POST['message'] ?? ''));
$pdfUrl = trim((string) ($_POST['catalog_pdf_url'] ?? ''));

if ($subject === '' || $messageBody === '') {
    girffonAdminNewsletterRedirect('error', 'Subject and message are required.');
}

$mailConfig = girffonMailConfig();

$attachmentUrl = '';
$uploadFile = $_FILES['catalog_pdf_file'] ?? $_FILES['catalog_pdf'] ?? [];
$uploadError = isset($uploadFile['error']) ? (int) ($uploadFile['error']) : UPLOAD_ERR_NO_FILE;
$uploadName = (string) ($uploadFile['name'] ?? '');

if ($pdfUrl !== '') {
    $attachmentUrl = $pdfUrl;
    girffonAdminNewsletterDebugLog([
        'source' => 'direct_url',
        'attachment_url' => $attachmentUrl,
        'upload_error' => $uploadError,
        'original_name' => $uploadName,
        'error' => '',
    ]);
} else {
    try {
        if ($uploadError === UPLOAD_ERR_OK) {
            $attachmentUrl = girffonAdminNewsletterHandleUpload($uploadFile, $mailConfig);
            girffonAdminNewsletterDebugLog([
                'source' => 'upload',
                'attachment_url' => $attachmentUrl,
                'upload_error' => $uploadError,
                'original_name' => $uploadName,
                'error' => '',
            ]);
        } elseif ($uploadError === UPLOAD_ERR_NO_FILE) {
            $attachmentUrl = '';
            girffonAdminNewsletterDebugLog([
                'source' => 'upload',
                'attachment_url' => '',
                'upload_error' => $uploadError,
                'original_name' => $uploadName,
                'error' => '',
            ]);
        } else {
            girffonAdminNewsletterDebugLog([
                'source' => 'upload',
                'attachment_url' => '',
                'upload_error' => $uploadError,
                'original_name' => $uploadName,
                'error' => 'Catalog upload failed.',
            ]);
            girffonAdminNewsletterRedirect('error', 'Catalog upload failed.');
        }
    } catch (Throwable $throwable) {
        girffonAdminNewsletterDebugLog([
            'source' => 'upload',
            'attachment_url' => '',
            'upload_error' => $uploadError,
            'original_name' => $uploadName,
            'error' => $throwable->getMessage(),
        ]);
        girffonAdminNewsletterRedirect('error', $throwable->getMessage());
    }
}

if ($attachmentUrl !== '' && !filter_var($attachmentUrl, FILTER_VALIDATE_URL)) {
    girffonAdminNewsletterDebugLog([
        'source' => $pdfUrl !== '' ? 'direct_url' : 'upload',
        'attachment_url' => $attachmentUrl,
        'upload_error' => $uploadError,
        'original_name' => $uploadName,
        'error' => 'Catalog PDF URL must be a valid URL.',
    ]);
    girffonAdminNewsletterRedirect('error', 'Catalog PDF URL must be a valid URL.');
}

$subscribers = array_slice(girffonAdminNewsletterFetchSubscribedRowsByEmails($pdo, $selectedEmails), 0, 250);
if (!$subscribers) {
    girffonAdminNewsletterRedirect('error', 'Select at least one subscribed customer first.');
}

$campaignId = 'catalog-' . date('YmdHis') . '-' . substr(sha1(implode('|', $selectedEmails) . $subject), 0, 10);
$sentCount = 0;
$failedCount = 0;

if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}

foreach ($subscribers as $subscriber) {
    $email = strtolower(trim((string) ($subscriber['email'] ?? '')));
    $recipientName = (string) ($subscriber['name'] ?? 'GirffoN Member');
    $catalogValue = strtolower(trim((string) ($subscriber['catalog_emails'] ?? '0')));
    $catalogEnabled = !in_array($catalogValue, ['', '0', 'false', 'off', 'no', 'disabled'], true);
    $status = strtolower(trim((string) ($subscriber['status'] ?? 'inactive')));
    $isActive = $status === 'active'
        || in_array(strtolower(trim((string) ($subscriber['is_active'] ?? '0'))), ['1', 'true', 'yes', 'on'], true);

    if (!$catalogEnabled || !$isActive || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        girffonAdminLogNewsletterCampaignResult($pdo, [
            'campaign_id' => $campaignId,
            'user_id' => (int) ($subscriber['user_id'] ?? 0),
            'recipient_name' => $recipientName,
            'email' => $email,
            'subject' => $subject,
            'message' => $messageBody,
            'attachment_url' => $attachmentUrl,
            'status' => 'skipped',
            'transport' => 'preference-check',
            'error_message' => 'Catalog Emails disabled or subscriber inactive.',
        ]);
        continue;
    }

    $message = girffonAdminNewsletterBuildMessage($subscriber, $subject, $messageBody, $attachmentUrl);
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

    girffonAdminLogNewsletterCampaignResult($pdo, [
        'campaign_id' => $campaignId,
        'user_id' => (int) ($subscriber['user_id'] ?? 0),
        'recipient_name' => $recipientName,
        'email' => $email,
        'subject' => $subject,
        'message' => $messageBody,
        'attachment_url' => $attachmentUrl,
        'status' => $sent ? 'sent' : 'failed',
        'transport' => $transport,
        'error_message' => $errorMessage,
    ]);

    if ($sent) {
        $sentCount++;
    } else {
        $failedCount++;
    }
}

girffonAdminLogNewsletterCampaignSummary($pdo, $campaignId, $sentCount, $failedCount);
girffonAdminNewsletterRedirect('status', 'Catalog campaign sent to ' . $sentCount . ' users, failed ' . $failedCount . '.');
