<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function girffonProfileJsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonProfileRequestData(): array
{
    $post = is_array($_POST) ? $_POST : [];
    $rawPayload = file_get_contents('php://input');
    if (!is_string($rawPayload) || trim($rawPayload) === '') {
        return $post;
    }

    $decoded = json_decode($rawPayload, true);
    if (is_array($decoded)) {
        return array_merge($post, $decoded);
    }

    return $post;
}

function girffonProfileCurrentUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? $_SESSION['girffon_user_id'] ?? 0);
}

function girffonProfileRequireUserId(): int
{
    $userId = girffonProfileCurrentUserId();
    if ($userId <= 0) {
        girffonProfileJsonResponse(401, [
            'success' => false,
            'message' => 'Please log in to access your profile.',
        ]);
    }

    return $userId;
}

function girffonProfileTableColumns(PDO $pdo, string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $cache[$table] = [];

    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $column) {
            $name = (string) ($column['Field'] ?? '');
            if ($name !== '') {
                $cache[$table][$name] = true;
            }
        }
    } catch (PDOException $exception) {
        $cache[$table] = [];
    }

    return $cache[$table];
}

function girffonProfileTableExists(PDO $pdo, string $table): bool
{
    return girffonProfileTableColumns($pdo, $table) !== [];
}

function girffonProfileExistingColumns(array $availableColumns, array $preferredColumns): array
{
    $result = [];
    foreach ($preferredColumns as $column) {
        if (isset($availableColumns[$column])) {
            $result[] = $column;
        }
    }

    return $result;
}

function girffonProfileSplitName(string $name): array
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $firstName = trim((string) array_shift($parts));
    $lastName = trim(implode(' ', $parts));

    return [$firstName, $lastName];
}

function girffonProfileNormalizeUserRow(array $user): array
{
    $firstName = trim((string) ($user['first_name'] ?? ''));
    $lastName = trim((string) ($user['last_name'] ?? ''));
    $name = trim((string) ($user['name'] ?? ''));

    if ($name === '') {
        $name = trim($firstName . ' ' . $lastName);
    }

    if ($name === '') {
        $name = (string) ($user['username'] ?? '');
    }

    $postcode = (string) ($user['postcode'] ?? ($user['postal_code'] ?? ''));

    return [
        'id' => (int) ($user['id'] ?? 0),
        'name' => $name,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'address' => (string) ($user['address'] ?? ($user['full_address'] ?? '')),
        'city' => (string) ($user['city'] ?? ''),
        'country' => (string) ($user['country'] ?? ''),
        'postcode' => $postcode,
        'postal_code' => $postcode,
        'preferred_language' => (string) ($user['preferred_language'] ?? ''),
        'date_of_birth' => (string) ($user['date_of_birth'] ?? ''),
        'gender' => (string) ($user['gender'] ?? ''),
        'avatar' => (string) ($user['avatar'] ?? ''),
        'username' => (string) ($user['username'] ?? ''),
        'created_at' => (string) ($user['created_at'] ?? ''),
        'updated_at' => (string) ($user['updated_at'] ?? ''),
        'last_login_at' => (string) ($user['last_login_at'] ?? ''),
    ];
}

function girffonProfileFetchUserById(PDO $pdo, int $userId): ?array
{
    $availableColumns = girffonProfileTableColumns($pdo, 'users');
    if (!$availableColumns) {
        return null;
    }

    $selectColumns = girffonProfileExistingColumns($availableColumns, [
        'id',
        'username',
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'full_address',
        'city',
        'country',
        'postcode',
        'postal_code',
        'preferred_language',
        'date_of_birth',
        'gender',
        'avatar',
        'password_hash',
        'status',
        'created_at',
        'updated_at',
        'last_login_at',
    ]);

    if (!$selectColumns) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT ' . implode(', ', array_unique($selectColumns)) . '
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute([':id' => $userId]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}