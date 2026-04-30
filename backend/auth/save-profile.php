<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

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

$updateStatement = $pdo->prepare(
    'UPDATE users SET
        first_name = :first_name,
        last_name = :last_name,
        email = :email,
        phone = :phone,
        country = :country,
        city = :city,
        address = :address
     WHERE id = :id'
);

$updateStatement->execute([
    ':first_name' => $firstName,
    ':last_name' => $lastName,
    ':email' => $email,
    ':phone' => $phone,
    ':country' => $country,
    ':city' => $city,
    ':address' => $address !== '' ? $address : null,
    ':id' => $userId,
]);

$userStatement = $pdo->prepare('SELECT id, username, first_name, last_name, email, phone, country, city, address, created_at FROM users WHERE id = :id LIMIT 1');
$userStatement->execute([':id' => $userId]);
$user = $userStatement->fetch(PDO::FETCH_ASSOC) ?: [];

girffonProfileJsonResponse(200, [
    'ok' => true,
    'message' => 'Profile saved successfully.',
    'user' => $user,
]);