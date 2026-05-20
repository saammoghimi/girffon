<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require_once __DIR__ . '/backend/config/mail.php';
require_once __DIR__ . '/backend/profile/communication-common.php';
require_once __DIR__ . '/backend/utils/order-confirmation-mailer.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    gf_json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

gf_start_session();

$pdo = gf_get_pdo();
$payload = gf_read_json_input();

$fullName = trim((string)($payload['name'] ?? ''));
$email = strtolower(trim((string)($payload['email'] ?? '')));
$phone = trim((string)($payload['phone'] ?? ''));
$password = (string)($payload['password'] ?? '');
$acceptsPromotionalEmails = filter_var($payload['accepts_promotional_emails'] ?? false, FILTER_VALIDATE_BOOLEAN);
$acceptsCatalogEmails = filter_var($payload['accepts_catalog_emails'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($fullName === '') {
    gf_json_response(422, ['ok' => false, 'message' => 'Full name is required.']);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    gf_json_response(422, ['ok' => false, 'message' => 'A valid email is required.']);
}

if (mb_strlen($password) < 6) {
    gf_json_response(422, ['ok' => false, 'message' => 'Password must be at least 6 characters.']);
}

$existingUser = gf_find_user_by_identifier($pdo, $email);
if (is_array($existingUser)) {
    gf_json_response(409, ['ok' => false, 'message' => 'An account with this email already exists.']);
}

$nameParts = preg_split('/\s+/', $fullName) ?: [];
$firstName = trim((string)array_shift($nameParts));
$lastName = trim(implode(' ', $nameParts));
$usernameBase = strtolower((string)preg_replace('/[^a-z0-9]+/i', '', strstr($email, '@', true) ?: $email));
$username = $usernameBase !== '' ? $usernameBase : 'girffonmember';

$counter = 1;
while (gf_find_user_by_identifier($pdo, $username) !== null) {
    $counter++;
    $username = $usernameBase . $counter;
}

function gf_send_registration_confirmation_email(string $email, string $firstName, string $lastName): bool
{
    $normalizedEmail = strtolower(trim($email));
    if ($normalizedEmail === '' || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $mailConfig = girffonMailConfig();
    $recipientName = trim($firstName . ' ' . $lastName);
    if ($recipientName === '') {
        $recipientName = 'GirffoN Member';
    }

    $message = [
        'to_email' => $normalizedEmail,
        'to_name' => $recipientName,
        'subject' => 'Welcome to GirffoN',
        'html' => '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:24px;background:#f5f1ea;font-family:Georgia,serif;color:#1f1812;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:700px;margin:0 auto;background:#fffdf9;border:1px solid #e5ddd0;">'
            . '<tr><td style="padding:28px 32px;background:#17181c;color:#f4ebdf;"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#d1a12b;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;">Your account is ready</h1></td></tr>'
            . '<tr><td style="padding:28px 32px;">'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Hello ' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Congratulations! Your GirffoN account has been created successfully.</p>'
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.7;">You can now sign in, manage your account, and receive the email updates you selected during registration.</p>'
            . '</td></tr></table></body></html>',
        'text' => "Welcome to GirffoN\n\nHello {$recipientName},\n\nCongratulations! Your GirffoN account has been created successfully.\n",
    ];

    try {
        if ((string) ($mailConfig['transport'] ?? 'mail') === 'smtp') {
            try {
                return girffonSendMailWithPhpMailer($mailConfig, $message);
            } catch (Throwable $throwable) {
                girffonOrderMailDebugLog($mailConfig, 'Registration confirmation PHPMailer SMTP failed: ' . $throwable->getMessage());
                return girffonSendMailWithSocketSmtp($mailConfig, $message);
            }
        }

        return girffonSendMailWithPhpMail($mailConfig, $message);
    } catch (Throwable $throwable) {
        girffonOrderMailDebugLog($mailConfig, 'Registration confirmation email failed: ' . $throwable->getMessage());
        return false;
    }
}

$statement = $pdo->prepare(
    'INSERT INTO users (username, email, phone, password_hash, first_name, last_name) VALUES (:username, :email, :phone, :password_hash, :first_name, :last_name)'
);

$statement->execute([
    'username' => $username,
    'email' => $email,
    'phone' => $phone,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'first_name' => $firstName,
    'last_name' => $lastName,
]);

$userId = (int)$pdo->lastInsertId();

if (girffonEnsureUserPreferencesTable($pdo)) {
    try {
        $preferenceStatement = $pdo->prepare(
            'INSERT INTO user_preferences (
                user_id,
                promotional_emails,
                catalog_emails,
                birthday_discount_emails,
                order_updates,
                two_factor_enabled
             ) VALUES (
                :user_id,
                :promotional_emails,
                :catalog_emails,
                :birthday_discount_emails,
                :order_updates,
                :two_factor_enabled
             )
             ON DUPLICATE KEY UPDATE
                promotional_emails = VALUES(promotional_emails),
                catalog_emails = VALUES(catalog_emails),
                updated_at = CURRENT_TIMESTAMP'
        );
        $preferenceStatement->execute([
            ':user_id' => $userId,
            ':promotional_emails' => $acceptsPromotionalEmails ? 1 : 0,
            ':catalog_emails' => $acceptsCatalogEmails ? 1 : 0,
            ':birthday_discount_emails' => 1,
            ':order_updates' => 1,
            ':two_factor_enabled' => 1,
        ]);
    } catch (PDOException $exception) {
    }
}

if ($acceptsPromotionalEmails || $acceptsCatalogEmails) {
    girffonCommunicationSaveNewsletterSubscriber($pdo, $userId, $email, 'registration', [
        'accepts_promotional_emails' => $acceptsPromotionalEmails,
        'accepts_catalog_emails' => $acceptsCatalogEmails,
    ]);
}

gf_send_registration_confirmation_email($email, $firstName, $lastName);

$_SESSION['girffon_user_id'] = $userId;

$userStatement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$userStatement->execute(['id' => $userId]);
$user = $userStatement->fetch() ?: [];

gf_json_response(201, [
    'ok' => true,
    'message' => 'Congratulations! Your GirffoN account has been created. Please check your email.',
    'user' => gf_normalize_user_row($user),
    'preferences' => [
        'accepts_promotional_emails' => $acceptsPromotionalEmails,
        'accepts_catalog_emails' => $acceptsCatalogEmails,
    ],
]);