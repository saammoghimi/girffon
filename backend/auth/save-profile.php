<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

function girffonProfileAvailableUserColumns(PDO $pdo): array
{
    static $columns = null;

    if (is_array($columns)) {
        return $columns;
    }

    $columns = [];

    try {
        $statement = $pdo->query('SHOW COLUMNS FROM users');
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $column) {
            $name = (string) ($column['Field'] ?? '');
            if ($name !== '') {
                $columns[$name] = true;
            }
        }
    } catch (PDOException $exception) {
        $columns = [];
    }

    return $columns;
}

function girffonProfileBuildUserPayload(array $user): array
{
    return [
        'id' => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ''),
        'first_name' => (string) ($user['first_name'] ?? ''),
        'last_name' => (string) ($user['last_name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'country' => (string) ($user['country'] ?? ''),
        'city' => (string) ($user['city'] ?? ''),
        'postal_code' => (string) ($user['postal_code'] ?? ''),
        'address' => (string) ($user['address'] ?? ''),
        'preferred_language' => (string) ($user['preferred_language'] ?? ''),
        'date_of_birth' => (string) ($user['date_of_birth'] ?? ''),
        'gender' => (string) ($user['gender'] ?? ''),
        'created_at' => (string) ($user['created_at'] ?? ''),
        'updated_at' => (string) ($user['updated_at'] ?? ''),
        'last_login_at' => (string) ($user['last_login_at'] ?? ''),
    ];
}

function girffonProfileEnsureUserColumns(PDO $pdo): void
{
    $columns = girffonProfileAvailableUserColumns($pdo);
    $migrations = [
        'postal_code' => "ALTER TABLE users ADD COLUMN postal_code VARCHAR(32) NOT NULL DEFAULT ''",
        'preferred_language' => "ALTER TABLE users ADD COLUMN preferred_language VARCHAR(32) NOT NULL DEFAULT ''",
        'date_of_birth' => 'ALTER TABLE users ADD COLUMN date_of_birth DATE NULL',
        'gender' => "ALTER TABLE users ADD COLUMN gender VARCHAR(32) NOT NULL DEFAULT ''",
    ];

    foreach ($migrations as $column => $sql) {
        if (isset($columns[$column])) {
            continue;
        }

        try {
            $pdo->exec($sql);
            $columns[$column] = true;
        } catch (PDOException $exception) {
            // Keep the request working even if the live database cannot be migrated.
        }
    }
}

function girffonProfileJsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

$rawPayload = file_get_contents('php://input');
$payload = json_decode((string) $rawPayload, true);
$profile = is_array($payload['profile'] ?? null) ? $payload['profile'] : [];

$userId = (int) ($_SESSION['user_id'] ?? 0);
$firstName = trim((string) ($profile['firstName'] ?? ''));
$lastName = trim((string) ($profile['lastName'] ?? ''));
$email = strtolower(trim((string) ($profile['email'] ?? '')));
$phone = trim((string) ($profile['phone'] ?? ''));
$country = trim((string) ($profile['country'] ?? ''));
$city = trim((string) ($profile['city'] ?? ''));
$address = trim((string) ($profile['fullAddress'] ?? ''));
$postalCode = trim((string) ($profile['postalCode'] ?? ''));
$preferredLanguage = trim((string) ($profile['preferredLanguage'] ?? ''));
$dateOfBirth = trim((string) ($profile['dateOfBirth'] ?? ''));
$gender = trim((string) ($profile['gender'] ?? ''));

if ($userId <= 0) {
    girffonProfileJsonResponse(401, ['ok' => false, 'message' => 'Authentication required.']);
}

if ($firstName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    girffonProfileJsonResponse(422, ['ok' => false, 'message' => 'First name and a valid email are required.']);
}

$duplicateStatement = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
$duplicateStatement->execute([
    ':email' => $email,
    ':id' => $userId,
]);

if ($duplicateStatement->fetch(PDO::FETCH_ASSOC)) {
    girffonProfileJsonResponse(409, ['ok' => false, 'message' => 'This email is already linked to another account.']);
}

girffonProfileEnsureUserColumns($pdo);
$availableUserColumns = girffonProfileAvailableUserColumns($pdo);
$assignments = [
    'first_name = :first_name',
    'last_name = :last_name',
    'email = :email',
    'phone = :phone',
    'country = :country',
    'city = :city',
    'address = :address',
];

$params = [
    ':first_name' => $firstName,
    ':last_name' => $lastName,
    ':email' => $email,
    ':phone' => $phone,
    ':country' => $country,
    ':city' => $city,
    ':address' => $address !== '' ? $address : null,
    ':id' => $userId,
];

if (isset($availableUserColumns['postal_code'])) {
    $assignments[] = 'postal_code = :postal_code';
    $params[':postal_code'] = $postalCode;
}

if (isset($availableUserColumns['preferred_language'])) {
    $assignments[] = 'preferred_language = :preferred_language';
    $params[':preferred_language'] = $preferredLanguage;
}

if (isset($availableUserColumns['date_of_birth'])) {
    $assignments[] = 'date_of_birth = :date_of_birth';
    $params[':date_of_birth'] = $dateOfBirth !== '' ? $dateOfBirth : null;
}

if (isset($availableUserColumns['gender'])) {
    $assignments[] = 'gender = :gender';
    $params[':gender'] = $gender;
}

if (isset($availableUserColumns['updated_at'])) {
    $assignments[] = 'updated_at = NOW()';
}

$updateStatement = $pdo->prepare(
    'UPDATE users SET
        ' . implode(",\n        ", $assignments) . '
     WHERE id = :id'
);

$updateStatement->execute($params);

$selectColumns = ['id', 'username', 'first_name', 'last_name', 'email', 'phone', 'country', 'city', 'address', 'created_at'];
foreach (['postal_code', 'preferred_language', 'date_of_birth', 'gender', 'updated_at', 'last_login_at'] as $column) {
    if (isset($availableUserColumns[$column])) {
        $selectColumns[] = $column;
    }
}

$userStatement = $pdo->prepare('SELECT ' . implode(', ', $selectColumns) . ' FROM users WHERE id = :id LIMIT 1');
$userStatement->execute([':id' => $userId]);
$user = $userStatement->fetch(PDO::FETCH_ASSOC) ?: [];

girffonProfileJsonResponse(200, [
    'ok' => true,
    'message' => 'Profile saved successfully.',
    'user' => girffonProfileBuildUserPayload($user),
]);