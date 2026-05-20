<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../profile/communication-common.php';

function girffonNotificationPreferenceResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonNotificationPreferenceTable(PDO $pdo): string
{
    if (girffonEnsureUserPreferencesTable($pdo)) {
        return 'user_preferences';
    }

    foreach (['user_preferences', 'customer_notification_preferences'] as $table) {
        try {
            $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
            return $table;
        } catch (PDOException $exception) {
            continue;
        }
    }

    return 'customer_notification_preferences';
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
    'twoFactorEnabled' => true,
];

$columnMap = [
    'promotionalEmails' => 'promotional_emails',
    'catalogEmails' => 'catalog_emails',
    'birthdayDiscountEmails' => 'birthday_discount_emails',
    'orderUpdates' => 'order_updates',
    'twoFactorEnabled' => 'two_factor_enabled',
];

$normalizedPreferences = $defaults;
$preferenceTable = girffonNotificationPreferenceTable($pdo);
$existingPreferenceStatement = $pdo->prepare(
    'SELECT promotional_emails, catalog_emails, birthday_discount_emails, order_updates, two_factor_enabled
    FROM ' . $preferenceTable . '
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
    'INSERT INTO ' . $preferenceTable . ' (
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
        birthday_discount_emails = VALUES(birthday_discount_emails),
        order_updates = VALUES(order_updates),
        two_factor_enabled = VALUES(two_factor_enabled)'
);

$statement->execute([
    ':user_id' => $userId,
    ':promotional_emails' => $normalizedPreferences['promotionalEmails'] ? 1 : 0,
    ':catalog_emails' => $normalizedPreferences['catalogEmails'] ? 1 : 0,
    ':birthday_discount_emails' => $normalizedPreferences['birthdayDiscountEmails'] ? 1 : 0,
    ':order_updates' => $normalizedPreferences['orderUpdates'] ? 1 : 0,
    ':two_factor_enabled' => $normalizedPreferences['twoFactorEnabled'] ? 1 : 0,
]);

$userStatement = $pdo->prepare(
    'SELECT username, first_name, last_name, email
     FROM users
     WHERE id = :id
     LIMIT 1'
);
$userStatement->execute([':id' => $userId]);
$user = $userStatement->fetch(PDO::FETCH_ASSOC) ?: [];

$userName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
if ($userName === '') {
    $userName = trim((string) ($user['username'] ?? 'GirffoN Member'));
}

$userEmail = strtolower(trim((string) ($user['email'] ?? '')));
if ($userEmail !== '') {
    girffonCommunicationSaveNewsletterSubscriber($pdo, $userId, $userEmail, 'preferences', [
        'accepts_promotional_emails' => $normalizedPreferences['promotionalEmails'],
        'accepts_catalog_emails' => $normalizedPreferences['catalogEmails'],
    ]);
}

$preferenceSummary = sprintf(
    'Preferences updated. Promotional: %s | Catalog: %s | Birthday: %s | Orders: %s | Two-factor: %s',
    $normalizedPreferences['promotionalEmails'] ? 'On' : 'Off',
    $normalizedPreferences['catalogEmails'] ? 'On' : 'Off',
    $normalizedPreferences['birthdayDiscountEmails'] ? 'On' : 'Off',
    $normalizedPreferences['orderUpdates'] ? 'On' : 'Off',
    $normalizedPreferences['twoFactorEnabled'] ? 'On' : 'Off'
);

girffonCommunicationLogAdminMessage(
    $pdo,
    $userName,
    $userEmail !== '' ? $userEmail : 'unknown@girffon.local',
    'Communication Preferences Updated',
    $preferenceSummary,
    'unread'
);

girffonNotificationPreferenceResponse(200, [
    'ok' => true,
    'message' => 'Notification preferences saved successfully.',
    'preferences' => $normalizedPreferences,
    'smsStatus' => 'Available soon',
]);