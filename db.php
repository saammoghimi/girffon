<?php
declare(strict_types=1);

function gf_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function gf_read_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function gf_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function gf_get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('GIRFFON_DB_HOST') ?: '127.0.0.1';
    $port = getenv('GIRFFON_DB_PORT') ?: '3306';
    $database = getenv('GIRFFON_DB_NAME') ?: 'girffon_db';
    $username = getenv('GIRFFON_DB_USER') ?: 'root';
    $password = getenv('GIRFFON_DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        gf_json_response(500, [
            'ok' => false,
            'message' => 'Database connection failed.',
            'details' => $exception->getMessage(),
        ]);
    }

    return $pdo;
}

function gf_normalize_user_row(array $row): array
{
    $firstName = trim((string)($row['first_name'] ?? ''));
    $lastName = trim((string)($row['last_name'] ?? ''));
    $fullName = trim($firstName . ' ' . $lastName);

    return [
        'id' => (int)($row['id'] ?? 0),
        'username' => (string)($row['username'] ?? ''),
        'name' => $fullName !== '' ? $fullName : (string)($row['username'] ?? 'GirffoN Member'),
        'email' => (string)($row['email'] ?? ''),
        'phone' => (string)($row['phone'] ?? ''),
        'provider' => 'email',
        'profile' => [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => (string)($row['email'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'dateOfBirth' => (string)($row['date_of_birth'] ?? ''),
            'gender' => (string)($row['gender'] ?? ''),
            'country' => (string)($row['country'] ?? ''),
            'city' => (string)($row['city'] ?? ''),
            'postalCode' => (string)($row['postal_code'] ?? ''),
            'fullAddress' => (string)($row['full_address'] ?? ''),
            'preferredLanguage' => (string)($row['preferred_language'] ?? ''),
            'birthdayGiftDate' => (string)($row['birthday_gift_date'] ?? ''),
        ],
        'timestamps' => [
            'createdAt' => (string)($row['created_at'] ?? ''),
            'updatedAt' => (string)($row['updated_at'] ?? ''),
            'lastLoginAt' => (string)($row['last_login_at'] ?? ''),
        ],
    ];
}

function gf_find_user_by_identifier(PDO $pdo, string $identifier): ?array
{
    $sql = 'SELECT * FROM users WHERE email = :identifier OR username = :identifier OR phone = :identifier LIMIT 1';
    $statement = $pdo->prepare($sql);
    $statement->execute(['identifier' => $identifier]);
    $user = $statement->fetch();

    return is_array($user) ? $user : null;
}

function gf_require_logged_in_user(PDO $pdo): array
{
    gf_start_session();
    $userId = isset($_SESSION['girffon_user_id']) ? (int)$_SESSION['girffon_user_id'] : 0;
    if ($userId <= 0) {
        gf_json_response(401, [
            'ok' => false,
            'message' => 'Not authenticated.',
        ]);
    }

    $statement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();
    if (!is_array($user)) {
        unset($_SESSION['girffon_user_id']);
        gf_json_response(401, [
            'ok' => false,
            'message' => 'Session is no longer valid.',
        ]);
    }

    return $user;
}