<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

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
$_SESSION['girffon_user_id'] = $userId;

$userStatement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$userStatement->execute(['id' => $userId]);
$user = $userStatement->fetch() ?: [];

gf_json_response(201, [
    'ok' => true,
    'message' => 'Account created successfully.',
    'user' => gf_normalize_user_row($user),
]);