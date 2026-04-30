<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

function girffonNotificationPreferenceResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonNotificationPreferenceResponse(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    girffonNotificationPreferenceResponse(401, ['ok' => false, 'message' => 'Authentication required.']);
}

$rawPayload = file_get_contents('php://input');
$payload = json_decode((string) $rawPayload, true);
$preferences = is_array($payload['preferences'] ?? null) ? $payload['preferences'] : null;

if ($preferences === null) {
    girffonNotificationPreferenceResponse(422, ['ok' => false, 'message' => 'Notification preferences are required.']);
}

$defaults = [
    'promotionalEmails' => true,
    'catalogEmails' => true,
    'birthdayDiscountEmails' => true,
    'orderUpdates' => true,
    'smsNotifications' => false,
    'twoFactorEnabled' => true,
];

$columnMap = [
    'promotionalEmails' => 'promotional_emails',
    'catalogEmails' => 'catalog_emails',
    'birthdayDiscountEmails' => 'birthday_discount_emails',
    'orderUpdates' => 'order_updates',
    'smsNotifications' => 'sms_notifications',
    'twoFactorEnabled' => 'two_factor_enabled',
];

$normalizedPreferences = $defaults;
$existingPreferenceStatement = $pdo->prepare(
    'SELECT promotional_emails, catalog_emails, birthday_discount_emails, order_updates, sms_notifications, two_factor_enabled
     FROM customer_notification_preferences
     WHERE user_id = :user_id
     LIMIT 1'
);
$existingPreferenceStatement->execute([':user_id' => $userId]);
$existingPreferences = $existingPreferenceStatement->fetch(PDO::FETCH_ASSOC) ?: [];

foreach ($columnMap as $requestKey => $columnName) {
    if (array_key_exists($columnName, $existingPreferences)) {
        $normalizedPreferences[$requestKey] = (bool) $existingPreferences[$columnName];
    }
}

foreach ($columnMap as $requestKey => $columnName) {
    if (array_key_exists($requestKey, $preferences)) {
        $normalizedPreferences[$requestKey] = filter_var($preferences[$requestKey], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($normalizedPreferences[$requestKey] === null) {
            girffonNotificationPreferenceResponse(422, ['ok' => false, 'message' => 'Invalid value for ' . $requestKey . '.']);
        }
    }
}

$statement = $pdo->prepare(
    'INSERT INTO customer_notification_preferences (
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
     )
     ON DUPLICATE KEY UPDATE
        promotional_emails = VALUES(promotional_emails),
        catalog_emails = VALUES(catalog_emails),
        birthday_discount_emails = VALUES(birthday_discount_emails),
        order_updates = VALUES(order_updates),
        sms_notifications = VALUES(sms_notifications),
        two_factor_enabled = VALUES(two_factor_enabled)'
);

$statement->execute([
    ':user_id' => $userId,
    ':promotional_emails' => $normalizedPreferences['promotionalEmails'] ? 1 : 0,
    ':catalog_emails' => $normalizedPreferences['catalogEmails'] ? 1 : 0,
    ':birthday_discount_emails' => $normalizedPreferences['birthdayDiscountEmails'] ? 1 : 0,
    ':order_updates' => $normalizedPreferences['orderUpdates'] ? 1 : 0,
    ':sms_notifications' => $normalizedPreferences['smsNotifications'] ? 1 : 0,
    ':two_factor_enabled' => $normalizedPreferences['twoFactorEnabled'] ? 1 : 0,
]);

girffonNotificationPreferenceResponse(200, [
    'ok' => true,
    'message' => 'Notification preferences saved successfully.',
    'preferences' => $normalizedPreferences,
]);