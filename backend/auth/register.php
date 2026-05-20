<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once "../config/database.php";
require_once "../config/mail.php";
require_once "../profile/communication-common.php";
require_once "../utils/order-confirmation-mailer.php";

function girffonRegisterWantsJson(): bool
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    return str_contains($accept, 'application/json');
}

function girffonRegisterRespond(int $statusCode, bool $ok, string $message, ?array $user = null): void
{
    if (girffonRegisterWantsJson()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => $ok,
            'message' => $message,
            'user' => $user,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($ok) {
        echo $message;
        exit;
    }

    http_response_code($statusCode);
    exit($message);
}

function girffonGenerateUsername(PDO $pdo, string $email, string $firstName, string $lastName): string
{
    $seed = trim($firstName . '.' . $lastName);
    if ($seed === '.') {
        $seed = strstr($email, '@', true) ?: $email;
    }

    $base = strtolower($seed);
    $base = (string) preg_replace('/[^a-z0-9]+/', '.', $base);
    $base = trim($base, '.');
    if ($base === '') {
        $base = 'girffon.user';
    }

    $candidate = $base;
    $counter = 1;
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');

    while (true) {
        $check->execute([$candidate]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            return $candidate;
        }

        $counter += 1;
        $candidate = $base . $counter;
    }
}

function girffonRegisterReadBoolean(string $key): bool
{
    if (!array_key_exists($key, $_POST)) {
        return false;
    }

    return filter_var($_POST[$key], FILTER_VALIDATE_BOOLEAN);
}

function girffonRegisterSaveEmailPreferences(PDO $pdo, int $userId, string $email, bool $promotionalEmails, bool $catalogEmails): void
{
    if ($userId <= 0 || $email === '' || !girffonEnsureUserPreferencesTable($pdo)) {
        return;
    }

    try {
        $statement = $pdo->prepare(
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
        $statement->execute([
            ':user_id' => $userId,
            ':promotional_emails' => $promotionalEmails ? 1 : 0,
            ':catalog_emails' => $catalogEmails ? 1 : 0,
            ':birthday_discount_emails' => 1,
            ':order_updates' => 1,
            ':two_factor_enabled' => 1,
        ]);
    } catch (PDOException $exception) {
        return;
    }

    if ($promotionalEmails || $catalogEmails) {
        girffonCommunicationSaveNewsletterSubscriber($pdo, $userId, $email, 'registration', [
            'accepts_promotional_emails' => $promotionalEmails,
            'accepts_catalog_emails' => $catalogEmails,
        ]);
    }
}

function girffonRegisterSendConfirmationEmail(string $email, string $firstName, string $lastName): bool
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
            . '<p style="margin:18px 0 0;font-size:13px;line-height:1.7;color:#7a6a58;">If you did not create this account, please contact GirffoN support immediately.</p>'
            . '</td></tr></table></body></html>',
        'text' => "Welcome to GirffoN\n\nHello {$recipientName},\n\nCongratulations! Your GirffoN account has been created successfully.\n\nYou can now sign in, manage your account, and receive the email updates you selected during registration.\n",
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name  = trim($_POST["last_name"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $phone      = trim($_POST["phone"] ?? "");
    $password   = $_POST["password"] ?? "";
    $acceptsPromotionalEmails = girffonRegisterReadBoolean('accepts_promotional_emails');
    $acceptsCatalogEmails = girffonRegisterReadBoolean('accepts_catalog_emails');

    if (!$first_name || !$last_name || !$email || !$password) {
        girffonRegisterRespond(422, false, 'All fields required.');
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        girffonRegisterRespond(409, false, 'Email already exists.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $username = girffonGenerateUsername($pdo, $email, $first_name, $last_name);

    $stmt = $pdo->prepare("
        INSERT INTO users
        (username, first_name, last_name, email, password_hash, role, phone, status)
        VALUES (?, ?, ?, ?, ?, 'customer', ?, 'active')
    ");

    $stmt->execute([
        $username,
        $first_name,
        $last_name,
        $email,
        $hash,
        $phone !== '' ? $phone : null
    ]);

    $userId = (int) $pdo->lastInsertId();
    girffonRegisterSaveEmailPreferences($pdo, $userId, strtolower($email), $acceptsPromotionalEmails, $acceptsCatalogEmails);
    girffonRegisterSendConfirmationEmail($email, $first_name, $last_name);

    $_SESSION['user_id'] = $userId;
    $_SESSION['girffon_user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'customer';

    girffonRegisterRespond(200, true, 'Congratulations! Your GirffoN account has been created. Please check your email.', [
        'id' => $userId,
        'username' => $username,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'accepts_promotional_emails' => $acceptsPromotionalEmails,
        'accepts_catalog_emails' => $acceptsCatalogEmails,
    ]);
}
?>