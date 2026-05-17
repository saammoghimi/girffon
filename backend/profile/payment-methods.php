<?php
require_once __DIR__ . '/common.php';

function girffonProfilePaymentMethodsMissingTable(): void
{
    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'Payment methods are not available until user_payment_methods is created.',
        'available' => false,
        'methods' => [],
    ]);
}

function girffonProfilePaymentNormalizeRow(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'cardholder_name' => (string) ($row['cardholder_name'] ?? ''),
        'card_brand' => (string) ($row['card_brand'] ?? ''),
        'last4' => (string) ($row['last4'] ?? ''),
        'expiry_month' => (string) ($row['expiry_month'] ?? ''),
        'expiry_year' => (string) ($row['expiry_year'] ?? ''),
        'billing_method' => (string) ($row['billing_method'] ?? ''),
        'is_primary' => (bool) ($row['is_primary'] ?? false),
    ];
}

$userId = girffonProfileRequireUserId();

if (!girffonProfileTableExists($pdo, 'user_payment_methods')) {
    girffonProfilePaymentMethodsMissingTable();
}

$tableColumns = girffonProfileTableColumns($pdo, 'user_payment_methods');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $statement = $pdo->prepare(
        'SELECT id, cardholder_name, card_brand, last4, expiry_month, expiry_year, billing_method, is_primary
         FROM user_payment_methods
         WHERE user_id = :user_id
         ORDER BY is_primary DESC, id DESC'
    );
    $statement->execute([':user_id' => $userId]);

    girffonProfileJsonResponse(200, [
        'success' => true,
        'message' => 'Payment methods loaded successfully.',
        'available' => true,
        'methods' => array_map('girffonProfilePaymentNormalizeRow', $statement->fetchAll(PDO::FETCH_ASSOC) ?: []),
    ]);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonProfileJsonResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$payload = girffonProfileRequestData();
$action = trim((string) ($payload['action'] ?? ''));
$methodId = (int) ($payload['id'] ?? 0);

if ($action === 'delete') {
    $statement = $pdo->prepare('DELETE FROM user_payment_methods WHERE id = :id AND user_id = :user_id');
    $statement->execute([':id' => $methodId, ':user_id' => $userId]);
} elseif ($action === 'set-primary') {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE user_payment_methods SET is_primary = 0 WHERE user_id = :user_id')->execute([':user_id' => $userId]);
    $pdo->prepare('UPDATE user_payment_methods SET is_primary = 1 WHERE id = :id AND user_id = :user_id')->execute([':id' => $methodId, ':user_id' => $userId]);
    $pdo->commit();
} elseif ($action === 'save') {
    $cardholderName = trim((string) ($payload['cardholder_name'] ?? ''));
    $cardBrand = trim((string) ($payload['card_brand'] ?? ''));
    $cardNumber = preg_replace('/\D+/', '', (string) ($payload['card_number'] ?? ''));
    $expiryMonth = trim((string) ($payload['expiry_month'] ?? ''));
    $expiryYear = trim((string) ($payload['expiry_year'] ?? ''));
    $billingMethod = trim((string) ($payload['billing_method'] ?? ''));
    $isPrimary = filter_var($payload['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($cardholderName === '' || $cardBrand === '' || $expiryMonth === '' || $expiryYear === '' || $billingMethod === '') {
        girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Cardholder, brand, expiry, and billing method are required.']);
    }

    $last4 = '';
    if ($cardNumber !== '') {
        if (strlen($cardNumber) < 4) {
            girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Card number must include at least 4 digits.']);
        }
        $last4 = substr($cardNumber, -4);
    }

    if ($methodId > 0) {
        $existingStatement = $pdo->prepare('SELECT id, last4 FROM user_payment_methods WHERE id = :id AND user_id = :user_id LIMIT 1');
        $existingStatement->execute([':id' => $methodId, ':user_id' => $userId]);
        $existingMethod = $existingStatement->fetch(PDO::FETCH_ASSOC);
        if (!$existingMethod) {
            girffonProfileJsonResponse(404, ['success' => false, 'message' => 'Payment method not found.']);
        }
        if ($last4 === '') {
            $last4 = (string) ($existingMethod['last4'] ?? '');
        }
    }

    if ($last4 === '') {
        girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Card number is required for a new payment method.']);
    }

    if ($isPrimary) {
        $pdo->prepare('UPDATE user_payment_methods SET is_primary = 0 WHERE user_id = :user_id')->execute([':user_id' => $userId]);
    }

    if ($methodId > 0) {
        $statement = $pdo->prepare(
            'UPDATE user_payment_methods
             SET cardholder_name = :cardholder_name,
                 card_brand = :card_brand,
                 last4 = :last4,
                 expiry_month = :expiry_month,
                 expiry_year = :expiry_year,
                 billing_method = :billing_method,
                 is_primary = :is_primary,
                 updated_at = NOW()
             WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute([
            ':cardholder_name' => $cardholderName,
            ':card_brand' => $cardBrand,
            ':last4' => $last4,
            ':expiry_month' => $expiryMonth,
            ':expiry_year' => $expiryYear,
            ':billing_method' => $billingMethod,
            ':is_primary' => $isPrimary ? 1 : 0,
            ':id' => $methodId,
            ':user_id' => $userId,
        ]);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO user_payment_methods (
                user_id,
                cardholder_name,
                card_brand,
                last4,
                expiry_month,
                expiry_year,
                billing_method,
                is_primary,
                created_at,
                updated_at
             ) VALUES (
                :user_id,
                :cardholder_name,
                :card_brand,
                :last4,
                :expiry_month,
                :expiry_year,
                :billing_method,
                :is_primary,
                NOW(),
                NOW()
             )'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':cardholder_name' => $cardholderName,
            ':card_brand' => $cardBrand,
            ':last4' => $last4,
            ':expiry_month' => $expiryMonth,
            ':expiry_year' => $expiryYear,
            ':billing_method' => $billingMethod,
            ':is_primary' => $isPrimary ? 1 : 0,
        ]);
    }
} else {
    girffonProfileJsonResponse(422, ['success' => false, 'message' => 'Unknown payment method action.']);
}

$listStatement = $pdo->prepare(
    'SELECT id, cardholder_name, card_brand, last4, expiry_month, expiry_year, billing_method, is_primary
     FROM user_payment_methods
     WHERE user_id = :user_id
     ORDER BY is_primary DESC, id DESC'
);
$listStatement->execute([':user_id' => $userId]);

girffonProfileJsonResponse(200, [
    'success' => true,
    'message' => 'Payment methods updated successfully.',
    'available' => true,
    'methods' => array_map('girffonProfilePaymentNormalizeRow', $listStatement->fetchAll(PDO::FETCH_ASSOC) ?: []),
]);