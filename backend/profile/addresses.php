<?php
require_once __DIR__ . '/common.php';

$userId = girffonProfileRequireUserId();

function girffonProfileEnsureAddressTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_addresses (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                recipient_name VARCHAR(150) NOT NULL DEFAULT \'\',
                phone VARCHAR(50) NOT NULL DEFAULT \'\',
                country VARCHAR(100) NOT NULL DEFAULT \'\',
                city VARCHAR(100) NOT NULL DEFAULT \'\',
                postcode VARCHAR(30) NOT NULL DEFAULT \'\',
                address_line VARCHAR(255) NOT NULL DEFAULT \'\',
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_user_addresses_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonProfileAddressRow(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'recipient_name' => trim((string) ($row['recipient_name'] ?? '')),
        'phone' => trim((string) ($row['phone'] ?? '')),
        'country' => trim((string) ($row['country'] ?? '')),
        'city' => trim((string) ($row['city'] ?? '')),
        'postcode' => trim((string) ($row['postcode'] ?? '')),
        'address_line' => trim((string) ($row['address_line'] ?? '')),
        'is_primary' => (int) ($row['is_primary'] ?? 0) === 1,
        'label' => ((int) ($row['is_primary'] ?? 0) === 1) ? 'Default' : 'Saved',
    ];
}

function girffonProfileFetchAddresses(PDO $pdo, int $userId): array
{
    $user = girffonProfileFetchUserById($pdo, $userId) ?: [];
    $addresses = [];

    $primaryName = trim((string) ($user['name'] ?? trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? '')))));
    $primaryAddress = trim((string) ($user['address'] ?? ($user['full_address'] ?? '')));
    if ($primaryName !== '' || $primaryAddress !== '' || trim((string) ($user['city'] ?? '')) !== '' || trim((string) ($user['country'] ?? '')) !== '') {
        $addresses[] = [
            'id' => 0,
            'recipient_name' => $primaryName !== '' ? $primaryName : 'Primary Recipient',
            'phone' => trim((string) ($user['phone'] ?? '')),
            'country' => trim((string) ($user['country'] ?? '')),
            'city' => trim((string) ($user['city'] ?? '')),
            'postcode' => trim((string) ($user['postcode'] ?? ($user['postal_code'] ?? ''))),
            'address_line' => $primaryAddress,
            'is_primary' => true,
            'label' => 'Default',
        ];
    }

    if (!girffonProfileEnsureAddressTable($pdo)) {
        return $addresses;
    }

    $statement = $pdo->prepare(
        'SELECT id, recipient_name, phone, country, city, postcode, address_line, is_primary
         FROM user_addresses
         WHERE user_id = :user_id
         ORDER BY is_primary DESC, id ASC'
    );
    $statement->execute([':user_id' => $userId]);

    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $addresses[] = girffonProfileAddressRow($row);
    }

    return $addresses;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    girffonProfileJsonResponse(200, [
        'success' => true,
        'addresses' => girffonProfileFetchAddresses($pdo, $userId),
    ]);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
}

if (!girffonProfileEnsureAddressTable($pdo)) {
    girffonProfileJsonResponse(500, [
        'success' => false,
        'message' => 'Unable to prepare address storage right now.',
    ]);
}

$payload = girffonProfileRequestData();
$action = trim((string) ($payload['action'] ?? 'save'));
$id = (int) ($payload['id'] ?? 0);

if ($action === 'delete') {
    if ($id <= 0) {
        girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Address not found.']);
    }

    $statement = $pdo->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id');
    $statement->execute([':id' => $id, ':user_id' => $userId]);

    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'Address removed.',
        'addresses' => girffonProfileFetchAddresses($pdo, $userId),
    ]);
}

if ($action === 'duplicate') {
    if ($id <= 0) {
        girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Address not found.']);
    }

    $statement = $pdo->prepare('SELECT recipient_name, phone, country, city, postcode, address_line FROM user_addresses WHERE id = :id AND user_id = :user_id LIMIT 1');
    $statement->execute([':id' => $id, ':user_id' => $userId]);
    $source = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$source) {
        girffonProfileJsonResponse(404, ['success' => false, 'message' => 'Address not found.']);
    }

    $insert = $pdo->prepare('INSERT INTO user_addresses (user_id, recipient_name, phone, country, city, postcode, address_line, is_primary) VALUES (:user_id, :recipient_name, :phone, :country, :city, :postcode, :address_line, 0)');
    $insert->execute([
        ':user_id' => $userId,
        ':recipient_name' => (string) $source['recipient_name'],
        ':phone' => (string) $source['phone'],
        ':country' => (string) $source['country'],
        ':city' => (string) $source['city'],
        ':postcode' => (string) $source['postcode'],
        ':address_line' => (string) $source['address_line'],
    ]);

    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'Address duplicated.',
        'addresses' => girffonProfileFetchAddresses($pdo, $userId),
    ]);
}

$recipientName = trim((string) ($payload['recipient_name'] ?? ($payload['recipientName'] ?? '')));
$phone = trim((string) ($payload['phone'] ?? ''));
$country = trim((string) ($payload['country'] ?? ''));
$city = trim((string) ($payload['city'] ?? ''));
$postcode = trim((string) ($payload['postcode'] ?? ''));
$addressLine = trim((string) ($payload['address_line'] ?? ($payload['addressLine'] ?? '')));
$isPrimary = !empty($payload['is_primary']);

if ($recipientName === '' || $addressLine === '') {
    girffonProfileJsonResponse(422, [
        'success' => false,
        'message' => 'Recipient name and address are required.',
    ]);
}

if ($isPrimary) {
    $pdo->prepare('UPDATE user_addresses SET is_primary = 0 WHERE user_id = :user_id')->execute([':user_id' => $userId]);
}

if ($id > 0) {
    $statement = $pdo->prepare(
        'UPDATE user_addresses
         SET recipient_name = :recipient_name,
             phone = :phone,
             country = :country,
             city = :city,
             postcode = :postcode,
             address_line = :address_line,
             is_primary = :is_primary
         WHERE id = :id AND user_id = :user_id'
    );
    $statement->execute([
        ':recipient_name' => $recipientName,
        ':phone' => $phone,
        ':country' => $country,
        ':city' => $city,
        ':postcode' => $postcode,
        ':address_line' => $addressLine,
        ':is_primary' => $isPrimary ? 1 : 0,
        ':id' => $id,
        ':user_id' => $userId,
    ]);
} else {
    $statement = $pdo->prepare(
        'INSERT INTO user_addresses (user_id, recipient_name, phone, country, city, postcode, address_line, is_primary)
         VALUES (:user_id, :recipient_name, :phone, :country, :city, :postcode, :address_line, :is_primary)'
    );
    $statement->execute([
        ':user_id' => $userId,
        ':recipient_name' => $recipientName,
        ':phone' => $phone,
        ':country' => $country,
        ':city' => $city,
        ':postcode' => $postcode,
        ':address_line' => $addressLine,
        ':is_primary' => $isPrimary ? 1 : 0,
    ]);
}

girffonProfileJsonResponse(200, [
    'success' => true,
    'message' => $id > 0 ? 'Address updated.' : 'Address added successfully.',
    'addresses' => girffonProfileFetchAddresses($pdo, $userId),
]);
