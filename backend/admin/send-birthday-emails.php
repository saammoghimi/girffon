<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/newsletter-data.php';
require_once __DIR__ . '/../utils/order-confirmation-mailer.php';

function girffonAdminBirthdayDebugLog(array $context): void
{
    $logDirectory = dirname(__DIR__) . '/logs';
    if (!is_dir($logDirectory)) {
        @mkdir($logDirectory, 0777, true);
    }

    $line = json_encode([
        'timestamp' => date('c'),
        'today_month_day' => (string) ($context['today_month_day'] ?? date('m/d')),
        'recipient_candidates_count' => isset($context['recipient_candidates_count']) ? (int) $context['recipient_candidates_count'] : null,
        'user_id' => isset($context['user_id']) ? (int) $context['user_id'] : null,
        'email' => (string) ($context['email'] ?? ''),
        'date_of_birth' => (string) ($context['date_of_birth'] ?? ''),
        'preference_value' => (string) ($context['preference_value'] ?? ''),
        'newsletter_subscribed' => (string) ($context['newsletter_subscribed'] ?? ''),
        'already_sent_today' => (string) ($context['already_sent_today'] ?? ''),
        'final_send' => (string) ($context['final_send'] ?? ''),
        'mail_result' => (string) ($context['mail_result'] ?? ''),
        'error_message' => (string) ($context['error_message'] ?? ''),
        'query_error' => (string) ($context['query_error'] ?? ''),
        'skipped_reason' => (string) ($context['skipped_reason'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($line === false) {
        $line = '{"timestamp":"' . date('c') . '","skipped_reason":"Unable to encode birthday debug context."}';
    }

    @file_put_contents($logDirectory . '/birthday-email-debug.log', $line . PHP_EOL, FILE_APPEND);
}

function girffonAdminBirthdayRedirect(string $type, string $message): void
{
    header('Location: /GirffoN/admin-newsletter.php?' . $type . '=' . rawurlencode($message));
    exit;
}

function girffonAdminBirthdayBuildShopUrl(array $mailConfig): string
{
    if (function_exists('girffonOrderEmailBuildAppUrl')) {
        return rtrim(girffonOrderEmailBuildAppUrl($mailConfig), '/') . '/index.html';
    }

    $appUrl = trim((string) ($mailConfig['app_url'] ?? ''));
    if ($appUrl !== '') {
        return rtrim($appUrl, '/') . '/index.html';
    }

    return 'https://girffon.shop/GirffoN/index.html';
}

function girffonAdminBirthdayBuildMessage(array $recipient, string $shopUrl): array
{
    $recipientName = trim((string) ($recipient['name'] ?? 'GirffoN Member'));
    if ($recipientName === '') {
        $recipientName = 'GirffoN Member';
    }

    $couponCode = 'HAPPY50';
    $subject = 'Happy Birthday from GirffoN — 50% Off';
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $safeShopUrl = htmlspecialchars($shopUrl, ENT_QUOTES, 'UTF-8');

    return [
        'coupon_code' => $couponCode,
        'message' => [
            'to_email' => (string) ($recipient['email'] ?? ''),
            'to_name' => $recipientName,
            'subject' => $subject,
            'html' => '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:24px;background:#f5f1ea;font-family:Georgia,Times New Roman,serif;color:#1f1812;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;margin:0 auto;background:#fffdf9;border:1px solid #e5ddd0;"><tr><td style="padding:30px 34px;background:#1f1812;color:#f4ebdf;"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#d1a12b;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;">Happy Birthday from GirffoN — 50% Off</h1></td></tr><tr><td style="padding:30px 34px;"><p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Hello ' . $safeName . ',</p><p style="margin:0 0 14px;font-size:15px;line-height:1.8;color:#2b241b;">GirffoN is celebrating your birthday with a 50% discount on your next order.</p><p style="margin:0 0 14px;font-size:15px;line-height:1.8;color:#2b241b;">Use coupon code <strong>HAPPY50</strong> at checkout to activate your birthday offer.</p><p style="margin:22px 0 0;"><a href="' . $safeShopUrl . '" style="display:inline-block;padding:12px 20px;background:#c9a56a;color:#1f1812;text-decoration:none;font-weight:600;">Shop Birthday Offer</a></p><p style="margin:26px 0 0;color:#7a6a58;font-size:13px;line-height:1.7;">This birthday offer was sent because Birthday Discount Emails are enabled on your GirffoN account.</p></td></tr></table></body></html>',
            'text' => "Happy Birthday from GirffoN — 50% Off\n\nHello {$recipientName},\n\nGirffoN is celebrating your birthday with a 50% discount on your next order.\nUse coupon code HAPPY50 at checkout to activate your birthday offer.\n\nShop Birthday Offer: {$shopUrl}\n",
        ],
    ];
}

function girffonAdminEnsureBirthdayUserColumns(PDO $pdo): array
{
    $userColumns = girffonAdminTableColumns($pdo, 'users');
    $migrations = [
        'date_of_birth' => 'ALTER TABLE users ADD COLUMN date_of_birth DATE NULL',
    ];

    foreach ($migrations as $column => $sql) {
        if (isset($userColumns[$column])) {
            continue;
        }

        try {
            $pdo->exec($sql);
            $userColumns[$column] = true;
        } catch (PDOException $exception) {
        }
    }

    return $userColumns;
}

function girffonAdminBirthdayFetchRecipients(PDO $pdo): array
{
    $userColumns = girffonAdminEnsureBirthdayUserColumns($pdo);
    if ($userColumns === [] || !isset($userColumns['id'], $userColumns['email'], $userColumns['date_of_birth'])) {
        return [];
    }

    $firstNameExpression = isset($userColumns['first_name']) ? 'COALESCE(u.first_name, \'\')' : "''";
    $lastNameExpression = isset($userColumns['last_name']) ? 'COALESCE(u.last_name, \'\')' : "''";
    $usernameExpression = isset($userColumns['username']) ? 'NULLIF(u.username, \'\')' : "NULL";

    try {
        $statement = $pdo->query(
            "SELECT
                u.id AS user_id,
                LOWER(TRIM(u.email)) AS email,
                COALESCE(CAST(u.date_of_birth AS CHAR), '') AS date_of_birth,
                COALESCE(
                    NULLIF(TRIM(CONCAT({$firstNameExpression}, ' ', {$lastNameExpression})), ''),
                    {$usernameExpression},
                    LOWER(TRIM(u.email))
                ) AS name
             FROM users u
             WHERE LOWER(TRIM(COALESCE(u.email, ''))) <> ''
                             AND u.date_of_birth IS NOT NULL
                             AND DATE_FORMAT(CAST(u.date_of_birth AS DATE), '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
             ORDER BY u.id ASC"
        );

        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        girffonAdminBirthdayDebugLog([
            'recipient_candidates_count' => 0,
            'mail_result' => '',
            'skipped_reason' => '',
            'query_error' => $exception->getMessage(),
        ]);
        return [];
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && (string) ($_GET['run'] ?? '') !== '1') {
    girffonAdminBirthdayRedirect('error', 'Method not allowed.');
}

if (!girffonAdminEnsureBirthdayEmailLogsTable($pdo)) {
    girffonAdminBirthdayRedirect('error', 'Unable to prepare birthday email logs.');
}

$mailConfig = girffonMailConfig();
$shopUrl = girffonAdminBirthdayBuildShopUrl($mailConfig);
$today = date('Y-m-d');
$todayMonthDay = date('m/d');
$candidates = girffonAdminBirthdayFetchRecipients($pdo);
$sentCount = 0;
$skippedCount = 0;
$candidateCount = count($candidates);
$redirectErrorMessage = '';

girffonAdminBirthdayDebugLog([
    'today_month_day' => $todayMonthDay,
    'recipient_candidates_count' => $candidateCount,
]);

foreach ($candidates as $candidate) {
    $userId = (int) ($candidate['user_id'] ?? 0);
    $email = strtolower(trim((string) ($candidate['email'] ?? '')));
    $dateOfBirth = (string) ($candidate['date_of_birth'] ?? '');
    $validEmail = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
    $finalSend = $userId > 0 && $validEmail;
    $skippedReason = '';

    if (!$finalSend) {
        $skippedReason = 'Invalid user_id or email.';
    }

    girffonAdminBirthdayDebugLog([
        'today_month_day' => $todayMonthDay,
        'recipient_candidates_count' => $candidateCount,
        'user_id' => $userId,
        'email' => $email,
        'date_of_birth' => $dateOfBirth,
        'preference_value' => '',
        'newsletter_subscribed' => '',
        'already_sent_today' => '',
        'final_send' => $finalSend ? 'yes' : 'no',
        'mail_result' => '',
        'error_message' => '',
        'skipped_reason' => $skippedReason,
    ]);

    if (!$finalSend) {
        $skippedCount++;
        continue;
    }

    $birthdayPayload = girffonAdminBirthdayBuildMessage($candidate, $shopUrl);
    $message = $birthdayPayload['message'];
    $couponCode = (string) ($birthdayPayload['coupon_code'] ?? 'HAPPY50');
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

    girffonAdminLogBirthdayEmailResult($pdo, [
        'user_id' => $userId,
        'email' => $email,
        'coupon_code' => $couponCode,
        'sent_date' => $today,
        'status' => $sent ? 'sent' : 'failed',
        'transport' => $transport,
        'error_message' => $errorMessage,
    ]);

    girffonAdminBirthdayDebugLog([
        'today_month_day' => $todayMonthDay,
        'recipient_candidates_count' => $candidateCount,
        'user_id' => $userId,
        'email' => $email,
        'date_of_birth' => $dateOfBirth,
        'preference_value' => '',
        'newsletter_subscribed' => '',
        'already_sent_today' => '',
        'final_send' => $sent ? 'yes' : 'no',
        'mail_result' => $sent ? 'sent' : 'failed',
        'error_message' => $errorMessage,
        'skipped_reason' => $sent ? '' : 'Mail send failed.',
    ]);

    if ($sent) {
        $sentCount++;
    } else {
        $skippedCount++;
        if ($redirectErrorMessage === '') {
            $redirectErrorMessage = $errorMessage !== ''
                ? $errorMessage
                : 'Birthday email could not be sent right now.';
        }
    }
}

if ($redirectErrorMessage !== '') {
    girffonAdminBirthdayRedirect('error', $redirectErrorMessage);
}

girffonAdminBirthdayRedirect('status', 'Birthday emails sent: ' . $sentCount . ' | Skipped: ' . $skippedCount);