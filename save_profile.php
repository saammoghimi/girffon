<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    gf_json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

$pdo = gf_get_pdo();
$user = gf_require_logged_in_user($pdo);
$payload = gf_read_json_input();
$profile = is_array($payload['profile'] ?? null) ? $payload['profile'] : [];

$dateOfBirth = trim((string)($profile['dateOfBirth'] ?? ''));
$birthdayGiftDate = trim((string)($profile['birthdayGiftDate'] ?? ''));

$fields = [
    'first_name' => trim((string)($profile['firstName'] ?? '')),
    'last_name' => trim((string)($profile['lastName'] ?? '')),
    'email' => strtolower(trim((string)($profile['email'] ?? ''))),
    'phone' => trim((string)($profile['phone'] ?? '')),
    'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
    'gender' => trim((string)($profile['gender'] ?? '')),
    'country' => trim((string)($profile['country'] ?? '')),
    'city' => trim((string)($profile['city'] ?? '')),
    'postal_code' => trim((string)($profile['postalCode'] ?? '')),
    'full_address' => trim((string)($profile['fullAddress'] ?? '')),
    'preferred_language' => trim((string)($profile['preferredLanguage'] ?? '')),
    'birthday_gift_date' => $birthdayGiftDate !== '' ? $birthdayGiftDate : null,
];

if ($fields['first_name'] === '' || $fields['email'] === '' || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
    gf_json_response(422, [
        'ok' => false,
        'message' => 'First name and a valid email are required.',
    ]);
}

$duplicateStatement = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
$duplicateStatement->execute([
    'email' => $fields['email'],
    'id' => (int)$user['id'],
]);
if ($duplicateStatement->fetch()) {
    gf_json_response(409, [
        'ok' => false,
        'message' => 'This email is already linked to another account.',
    ]);
}

$updateStatement = $pdo->prepare(
    'UPDATE users SET
        email = :email,
        phone = :phone,
        first_name = :first_name,
        last_name = :last_name,
        date_of_birth = :date_of_birth,
        gender = :gender,
        country = :country,
        city = :city,
        postal_code = :postal_code,
        full_address = :full_address,
        preferred_language = :preferred_language,
        birthday_gift_date = :birthday_gift_date,
        updated_at = NOW()
     WHERE id = :id'
);

$updateStatement->execute($fields + ['id' => (int)$user['id']]);

$freshUser = gf_require_logged_in_user($pdo);

gf_json_response(200, [
    'ok' => true,
    'message' => 'Profile saved successfully.',
    'user' => gf_normalize_user_row($freshUser),
]);