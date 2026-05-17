<?php
require_once __DIR__ . '/common.php';

function girffonProfileUpdateDebugLog(array $context): void
{
    $logDirectory = dirname(__DIR__) . '/logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
        return;
    }

    $logFile = $logDirectory . '/profile-save-debug.log';
    $lines = [
        '[' . date('Y-m-d H:i:s') . ']',
        'user_id=' . (string) ($context['user_id'] ?? 0),
        'raw_request_body=' . json_encode((string) ($context['raw_request_body'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'decoded_json=' . json_encode($context['decoded_json'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'received_date_of_birth=' . json_encode((string) ($context['received_date_of_birth'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'received_gender=' . json_encode((string) ($context['received_gender'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'received_preferred_language=' . json_encode((string) ($context['received_preferred_language'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'received_avatar_length=' . (string) ($context['received_avatar_length'] ?? 0),
        'final_sql_fields_updated=' . json_encode(array_values($context['final_sql_fields_updated'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'pdo_error=' . json_encode((string) ($context['pdo_error'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        str_repeat('-', 80),
    ];

    @file_put_contents($logFile, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND);
}

function girffonProfileUpdateRequestPayload(): array
{
    $rawPayload = file_get_contents('php://input');
    $payload = girffonProfileRequestData();
    $decoded = [];

    if (is_string($rawPayload) && trim($rawPayload) !== '') {
        $jsonPayload = json_decode($rawPayload, true);
        if (is_array($jsonPayload)) {
            $decoded = $jsonPayload;
        }
    }

    return [
        'payload' => array_merge(is_array($payload) ? $payload : [], $decoded),
        'raw_request_body' => is_string($rawPayload) ? $rawPayload : '',
        'decoded_json' => $decoded,
    ];
}

function girffonProfileUpdateHasKey(array $payload, array $keys): bool
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $payload)) {
            return true;
        }
    }

    return false;
}

function girffonProfileUpdateValue(array $payload, array $keys, string $default = ''): string
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $payload)) {
            return trim((string) ($payload[$key] ?? ''));
        }
    }

    return $default;
}

function girffonProfileNormalizeDateOfBirth(string $value): ?string
{
    $dateValue = trim($value);
    if ($dateValue === '') {
        return null;
    }

    $ymd = DateTime::createFromFormat('Y-m-d', $dateValue);
    if ($ymd instanceof DateTime && $ymd->format('Y-m-d') === $dateValue) {
        return $ymd->format('Y-m-d');
    }

    $mdy = DateTime::createFromFormat('m/d/Y', $dateValue);
    if ($mdy instanceof DateTime && $mdy->format('m/d/Y') === $dateValue) {
        return $mdy->format('Y-m-d');
    }

    return null;
}

$userId = girffonProfileRequireUserId();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
}

$requestData = girffonProfileUpdateRequestPayload();
$payload = is_array($requestData['payload'] ?? null) ? $requestData['payload'] : [];
$rawRequestBody = (string) ($requestData['raw_request_body'] ?? '');
$decodedJson = is_array($requestData['decoded_json'] ?? null) ? $requestData['decoded_json'] : [];
$name = girffonProfileUpdateValue($payload, ['name']);
$phone = girffonProfileUpdateValue($payload, ['phone']);
$address = girffonProfileUpdateValue($payload, ['address', 'fullAddress']);
$city = girffonProfileUpdateValue($payload, ['city']);
$country = girffonProfileUpdateValue($payload, ['country']);
$postcode = girffonProfileUpdateValue($payload, ['postcode', 'postalCode']);
$preferredLanguage = girffonProfileUpdateValue($payload, ['preferred_language', 'preferredLanguage']);
$dateOfBirth = girffonProfileUpdateValue($payload, ['date_of_birth', 'dateOfBirth']);
$normalizedDateOfBirth = girffonProfileNormalizeDateOfBirth($dateOfBirth);
$gender = girffonProfileUpdateValue($payload, ['gender']);
$avatar = girffonProfileUpdateValue($payload, ['avatar']);

girffonProfileUpdateDebugLog([
    'user_id' => $userId,
    'raw_request_body' => $rawRequestBody,
    'decoded_json' => $decodedJson,
    'received_date_of_birth' => $dateOfBirth,
    'received_gender' => $gender,
    'received_preferred_language' => $preferredLanguage,
    'received_avatar_length' => strlen($avatar),
    'final_sql_fields_updated' => [],
    'pdo_error' => '',
]);

if ($name === '') {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'Name is required.',
    ]);
}

if (girffonProfileUpdateHasKey($payload, ['date_of_birth', 'dateOfBirth']) && $dateOfBirth !== '' && $normalizedDateOfBirth === null) {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'Date of birth must use MM/DD/YYYY or YYYY-MM-DD.',
    ]);
}

try {
    $availableColumns = girffonProfileTableColumns($pdo, 'users');
    $existingUser = girffonProfileFetchUserById($pdo, $userId);

    if (!$existingUser) {
        girffonProfileJsonResponse(404, [
            'success' => false,
            'message' => 'Profile not found.',
        ]);
    }

    $assignments = [];
    $params = [':id' => $userId];

    if (isset($availableColumns['name'])) {
        $assignments[] = 'name = :name';
        $params[':name'] = $name;
    }

    if (isset($availableColumns['first_name']) || isset($availableColumns['last_name'])) {
        [$firstName, $lastName] = girffonProfileSplitName($name);

        if (isset($availableColumns['first_name'])) {
            $assignments[] = 'first_name = :first_name';
            $params[':first_name'] = $firstName;
        }

        if (isset($availableColumns['last_name'])) {
            $assignments[] = 'last_name = :last_name';
            $params[':last_name'] = $lastName;
        }
    }

    foreach ([
        'phone' => ['value' => $phone, 'present' => girffonProfileUpdateHasKey($payload, ['phone'])],
        'address' => ['value' => $address, 'present' => girffonProfileUpdateHasKey($payload, ['address', 'fullAddress'])],
        'city' => ['value' => $city, 'present' => girffonProfileUpdateHasKey($payload, ['city'])],
        'country' => ['value' => $country, 'present' => girffonProfileUpdateHasKey($payload, ['country'])],
        'preferred_language' => ['value' => $preferredLanguage, 'present' => girffonProfileUpdateHasKey($payload, ['preferred_language', 'preferredLanguage'])],
        'gender' => ['value' => $gender, 'present' => girffonProfileUpdateHasKey($payload, ['gender'])],
        'avatar' => ['value' => $avatar, 'present' => girffonProfileUpdateHasKey($payload, ['avatar'])],
    ] as $column => $field) {
        if (empty($field['present'])) {
            continue;
        }

        if (isset($availableColumns[$column])) {
            $assignments[] = $column . ' = :' . $column;
            $params[':' . $column] = $field['value'];
        }
    }

    if (girffonProfileUpdateHasKey($payload, ['postcode', 'postalCode']) && isset($availableColumns['postcode'])) {
        $assignments[] = 'postcode = :postcode';
        $params[':postcode'] = $postcode;
    } elseif (girffonProfileUpdateHasKey($payload, ['postcode', 'postalCode']) && isset($availableColumns['postal_code'])) {
        $assignments[] = 'postal_code = :postcode';
        $params[':postcode'] = $postcode;
    }

    if (girffonProfileUpdateHasKey($payload, ['address', 'fullAddress']) && isset($availableColumns['full_address'])) {
        $assignments[] = 'full_address = :full_address';
        $params[':full_address'] = $address !== '' ? $address : null;
    }

    if (girffonProfileUpdateHasKey($payload, ['date_of_birth', 'dateOfBirth']) && isset($availableColumns['date_of_birth'])) {
        $assignments[] = 'date_of_birth = :date_of_birth';
        $params[':date_of_birth'] = $normalizedDateOfBirth;
    }

    if (isset($availableColumns['updated_at'])) {
        $assignments[] = 'updated_at = NOW()';
    }

    if (!$assignments) {
        girffonProfileJsonResponse(500, [
            'success' => false,
            'message' => 'Profile columns are not available for update.',
        ]);
    }

    $updateStatement = $pdo->prepare('UPDATE users SET ' . implode(', ', $assignments) . ' WHERE id = :id');
    $updateStatement->execute($params);

    $updatedFields = [];
    foreach ($assignments as $assignment) {
        $updatedFields[] = trim((string) preg_replace('/\s*=.*$/', '', $assignment));
    }

    girffonProfileUpdateDebugLog([
        'user_id' => $userId,
        'raw_request_body' => $rawRequestBody,
        'decoded_json' => $decodedJson,
        'received_date_of_birth' => $dateOfBirth,
        'received_gender' => $gender,
        'received_preferred_language' => $preferredLanguage,
        'received_avatar_length' => strlen($avatar),
        'final_sql_fields_updated' => $updatedFields,
        'pdo_error' => '',
    ]);

    $freshUser = girffonProfileFetchUserById($pdo, $userId) ?: [];
    $normalizedUser = girffonProfileNormalizeUserRow($freshUser);

    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'Profile updated successfully',
        'saved_date_of_birth' => (string) ($normalizedUser['date_of_birth'] ?? ''),
        'saved_gender' => (string) ($normalizedUser['gender'] ?? ''),
        'saved_preferred_language' => (string) ($normalizedUser['preferred_language'] ?? ''),
        'saved_avatar' => (string) ($normalizedUser['avatar'] ?? ''),
        'user' => $normalizedUser,
    ]);
} catch (PDOException $exception) {
    girffonProfileUpdateDebugLog([
        'user_id' => $userId,
        'raw_request_body' => $rawRequestBody,
        'decoded_json' => $decodedJson,
        'received_date_of_birth' => $dateOfBirth,
        'received_gender' => $gender,
        'received_preferred_language' => $preferredLanguage,
        'received_avatar_length' => strlen($avatar),
        'final_sql_fields_updated' => isset($assignments) && is_array($assignments) ? $assignments : [],
        'pdo_error' => $exception->getMessage(),
    ]);
    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to update profile right now.',
    ]);
}