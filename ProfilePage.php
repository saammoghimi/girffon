<?php
require_once __DIR__ . '/backend/auth/session.php';
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/profile/orders.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

$userStatement = $pdo->prepare(
    'SELECT id, username, first_name, last_name, email, phone, country, city, address, created_at
     FROM users
     WHERE id = :id
     LIMIT 1'
);
$userStatement->execute([':id' => $userId]);
$user = $userStatement->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION = [];
    header('Location: /GirffoN/Index.html');
    exit;
}

$notificationPreferenceDefaults = [
    'promotionalEmails' => true,
    'catalogEmails' => true,
    'birthdayDiscountEmails' => true,
    'orderUpdates' => true,
    'smsNotifications' => false,
    'twoFactorEnabled' => true,
];

$preferenceKeyMap = [
    'promotional_emails' => 'promotionalEmails',
    'catalog_emails' => 'catalogEmails',
    'birthday_discount_emails' => 'birthdayDiscountEmails',
    'order_updates' => 'orderUpdates',
    'sms_notifications' => 'smsNotifications',
    'two_factor_enabled' => 'twoFactorEnabled',
];

$insertPreferenceStatement = $pdo->prepare(
    'INSERT IGNORE INTO customer_notification_preferences (
        user_id,
        promotional_emails,
        catalog_emails,
        birthday_discount_emails,
        order_updates,
        sms_notifications,
        two_factor_enabled
     ) VALUES (
        :user_id,
        :promotional_emails,
        :catalog_emails,
        :birthday_discount_emails,
        :order_updates,
        :sms_notifications,
        :two_factor_enabled
     )'
);
$insertPreferenceStatement->execute([
    ':user_id' => $userId,
    ':promotional_emails' => $notificationPreferenceDefaults['promotionalEmails'] ? 1 : 0,
    ':catalog_emails' => $notificationPreferenceDefaults['catalogEmails'] ? 1 : 0,
    ':birthday_discount_emails' => $notificationPreferenceDefaults['birthdayDiscountEmails'] ? 1 : 0,
    ':order_updates' => $notificationPreferenceDefaults['orderUpdates'] ? 1 : 0,
    ':sms_notifications' => $notificationPreferenceDefaults['smsNotifications'] ? 1 : 0,
    ':two_factor_enabled' => $notificationPreferenceDefaults['twoFactorEnabled'] ? 1 : 0,
]);

$preferenceStatement = $pdo->prepare(
    'SELECT promotional_emails, catalog_emails, birthday_discount_emails, order_updates, sms_notifications, two_factor_enabled
     FROM customer_notification_preferences
     WHERE user_id = :user_id
     LIMIT 1'
);
$preferenceStatement->execute([':user_id' => $userId]);
$preferenceRow = $preferenceStatement->fetch(PDO::FETCH_ASSOC) ?: [];

$notificationPreferences = $notificationPreferenceDefaults;
foreach ($preferenceKeyMap as $databaseKey => $payloadKey) {
    if (array_key_exists($databaseKey, $preferenceRow)) {
        $notificationPreferences[$payloadKey] = (bool) $preferenceRow[$databaseKey];
    }
}

$orders = girffonProfileFetchOrders($pdo, $userId, (string) ($user['email'] ?? ''));

$profilePageData = [
    'user' => [
        'id' => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ''),
        'first_name' => (string) ($user['first_name'] ?? ''),
        'last_name' => (string) ($user['last_name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'country' => (string) ($user['country'] ?? ''),
        'city' => (string) ($user['city'] ?? ''),
        'postal_code' => '',
        'address' => (string) ($user['address'] ?? ''),
        'preferred_language' => '',
        'date_of_birth' => '',
        'gender' => '',
        'created_at' => (string) ($user['created_at'] ?? ''),
        'updated_at' => '',
        'last_login_at' => '',
    ],
    'notificationPreferences' => $notificationPreferences,
    'orders' => $orders,
];

$template = file_get_contents(__DIR__ . '/ProfilePage.html');
if ($template === false) {
    http_response_code(500);
    echo 'Profile page template not found.';
    exit;
}

$profileInjection = '  <script>window.GIRFFON_PROFILE_PAGE_DATA = ' . json_encode($profilePageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>' . PHP_EOL
    . '  <script src="JS/profile-page-server.js"></script>';

$template = str_replace('  <script src="JS/profile-page.js"></script>', $profileInjection, $template, $profileScriptReplaced);
if (!$profileScriptReplaced) {
    $template = str_replace('</body>', $profileInjection . PHP_EOL . '</body>', $template);
}

echo $template;