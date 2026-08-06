<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/order-confirmation-mailer.php';

function girffonGiftCardConfig(): array
{
    return [
        'default_expiry_months' => max(1, (int) (getenv('GIRFFON_GIFT_CARD_EXPIRY_MONTHS') ?: 12)),
        'physical_shipping' => round((float) (getenv('GIRFFON_GIFT_CARD_PHYSICAL_SHIPPING') ?: 7.50), 2),
        'custom_amount_min' => 10.0,
        'custom_amount_max' => 1000.0,
        'amounts' => [25.0, 50.0, 100.0],
        'redeem_attempt_limit' => max(3, (int) (getenv('GIRFFON_GIFT_CARD_RATE_LIMIT') ?: 12)),
        'redeem_attempt_window' => max(60, (int) (getenv('GIRFFON_GIFT_CARD_RATE_WINDOW') ?: 900)),
    ];
}

function girffonGiftCardStatuses(): array
{
    return ['pending', 'active', 'used', 'expired', 'cancelled'];
}

function girffonGiftCardDeliveryTypes(): array
{
    return ['digital', 'physical'];
}

function girffonGiftCardColumnExists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
    $statement->execute([':column' => $column]);
    return (bool) $statement->fetch(PDO::FETCH_ASSOC);
}

function girffonGiftCardIndexExists(PDO $pdo, string $table, string $indexName): bool
{
    $statement = $pdo->prepare("SHOW INDEX FROM {$table} WHERE Key_name = :index_name");
    $statement->execute([':index_name' => $indexName]);
    return (bool) $statement->fetch(PDO::FETCH_ASSOC);
}

function girffonGiftCardEnsureSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS gift_cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gift_code VARCHAR(32) NOT NULL,
            initial_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            remaining_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            buyer_name VARCHAR(190) NULL,
            buyer_email VARCHAR(190) NULL,
            recipient_name VARCHAR(190) NULL,
            recipient_email VARCHAR(190) NULL,
            gift_message TEXT NULL,
            delivery_type VARCHAR(20) NOT NULL DEFAULT 'digital',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            order_id INT NULL,
            source_line_key CHAR(40) NULL,
            qr_payload TEXT NULL,
            barcode_value VARCHAR(64) NULL,
            public_reference CHAR(32) NOT NULL,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_gift_cards_gift_code (gift_code),
            UNIQUE KEY uq_gift_cards_public_reference (public_reference),
            UNIQUE KEY uq_gift_cards_order_line (order_id, source_line_key),
            KEY idx_gift_cards_status (status),
            KEY idx_gift_cards_order_id (order_id),
            KEY idx_gift_cards_recipient_email (recipient_email),
            KEY idx_gift_cards_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS gift_card_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gift_card_id INT NOT NULL,
            order_id INT NULL,
            transaction_type VARCHAR(30) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            balance_before DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            balance_after DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            notes TEXT NULL,
            request_fingerprint CHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_gift_card_transactions_card_id (gift_card_id),
            KEY idx_gift_card_transactions_order_id (order_id),
            KEY idx_gift_card_transactions_type (transaction_type),
            UNIQUE KEY uq_gift_card_transactions_fingerprint (request_fingerprint),
            UNIQUE KEY uq_gift_card_transactions_order_type (gift_card_id, order_id, transaction_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $orderColumns = [
        'gift_card_code' => "ALTER TABLE orders ADD gift_card_code VARCHAR(32) NULL AFTER tracking_code",
        'gift_card_amount' => "ALTER TABLE orders ADD gift_card_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER gift_card_code",
        'amount_due' => "ALTER TABLE orders ADD amount_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER gift_card_amount",
    ];

    foreach ($orderColumns as $column => $sql) {
        if (!girffonGiftCardColumnExists($pdo, 'orders', $column)) {
            $pdo->exec($sql);
        }
    }

    $invoiceColumns = [
        'gift_card_amount' => "ALTER TABLE invoices ADD gift_card_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER shipping",
    ];

    foreach ($invoiceColumns as $column => $sql) {
        if (!girffonGiftCardColumnExists($pdo, 'invoices', $column)) {
            $pdo->exec($sql);
        }
    }

    $orderItemColumns = [
        'item_type' => "ALTER TABLE order_items ADD item_type VARCHAR(40) NOT NULL DEFAULT 'product' AFTER product_id",
        'metadata_json' => "ALTER TABLE order_items ADD metadata_json LONGTEXT NULL AFTER image",
    ];

    foreach ($orderItemColumns as $column => $sql) {
        if (!girffonGiftCardColumnExists($pdo, 'order_items', $column)) {
            $pdo->exec($sql);
        }
    }

    if (!girffonGiftCardColumnExists($pdo, 'gift_cards', 'source_line_key')) {
        $pdo->exec("ALTER TABLE gift_cards ADD source_line_key CHAR(40) NULL AFTER order_id");
    }

    if (!girffonGiftCardIndexExists($pdo, 'gift_cards', 'uq_gift_cards_order_line')) {
        $pdo->exec("ALTER TABLE gift_cards ADD UNIQUE KEY uq_gift_cards_order_line (order_id, source_line_key)");
    }

    $checked = true;
}

function girffonGiftCardNormalizeAmount($value): float
{
    return round(max(0, (float) $value), 2);
}

function girffonGiftCardAmountAllowed(float $amount): bool
{
    $config = girffonGiftCardConfig();
    $amount = girffonGiftCardNormalizeAmount($amount);

    if (in_array($amount, $config['amounts'], true)) {
        return true;
    }

    return $amount >= $config['custom_amount_min'] && $amount <= $config['custom_amount_max'];
}

function girffonGiftCardGenerateCode(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $segments = [];

    for ($segmentIndex = 0; $segmentIndex < 3; $segmentIndex += 1) {
        $segment = '';
        for ($charIndex = 0; $charIndex < 4; $charIndex += 1) {
            $segment .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $segments[] = $segment;
    }

    return 'GF-' . implode('-', $segments);
}

function girffonGiftCardGenerateReference(): string
{
    return bin2hex(random_bytes(16));
}

function girffonGiftCardBuildBarcodeValue(string $giftCode): string
{
    $normalized = strtoupper(trim($giftCode));
    return preg_replace('/[^A-Z0-9\-]/', '', $normalized) ?: $normalized;
}

function girffonGiftCardBuildQrPayload(array $giftCard): string
{
    $payload = [
        'code' => (string) ($giftCard['gift_code'] ?? ''),
        'amount' => (float) ($giftCard['initial_amount'] ?? 0),
        'balance' => (float) ($giftCard['remaining_balance'] ?? 0),
        'expires_at' => (string) ($giftCard['expires_at'] ?? ''),
    ];

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
}

function girffonGiftCardQrImageUrl(string $payload, int $size = 180): string
{
    $dimension = max(120, min(600, $size));
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $dimension . 'x' . $dimension . '&data=' . rawurlencode($payload);
}

function girffonGiftCardNormalizeStatus(string $status): string
{
    $normalized = strtolower(trim($status));
    return in_array($normalized, girffonGiftCardStatuses(), true) ? $normalized : 'active';
}

function girffonGiftCardNormalizeDeliveryType(string $deliveryType): string
{
    $normalized = strtolower(trim($deliveryType));
    return in_array($normalized, girffonGiftCardDeliveryTypes(), true) ? $normalized : 'digital';
}

function girffonGiftCardExpiryValue(?string $value): ?string
{
    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        $months = (int) girffonGiftCardConfig()['default_expiry_months'];
        $expiry = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        return $expiry->modify('+' . $months . ' months')->format('Y-m-d H:i:s');
    }

    $timestamp = strtotime($trimmed);
    if (!$timestamp) {
        return null;
    }

    return gmdate('Y-m-d 23:59:59', $timestamp);
}

function girffonGiftCardUpdateExpiredStatus(PDO $pdo): void
{
    $pdo->exec("UPDATE gift_cards SET status = 'expired' WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at < UTC_TIMESTAMP() AND remaining_balance > 0");
    $pdo->exec("UPDATE gift_cards SET status = 'used' WHERE remaining_balance <= 0 AND status <> 'cancelled'");
}

function girffonGiftCardCreate(PDO $pdo, array $input): array
{
    girffonGiftCardEnsureSchema($pdo);

    $amount = girffonGiftCardNormalizeAmount($input['initial_amount'] ?? 0);
    if (!girffonGiftCardAmountAllowed($amount)) {
        throw new InvalidArgumentException('Gift card amount is invalid.');
    }

    $status = girffonGiftCardNormalizeStatus((string) ($input['status'] ?? 'active'));
    $deliveryType = girffonGiftCardNormalizeDeliveryType((string) ($input['delivery_type'] ?? 'digital'));
    $expiresAt = girffonGiftCardExpiryValue((string) ($input['expires_at'] ?? ''));
    if ($expiresAt === null) {
        throw new InvalidArgumentException('Gift card expiration date is invalid.');
    }

    $buyerEmail = strtolower(trim((string) ($input['buyer_email'] ?? '')));
    $recipientEmail = strtolower(trim((string) ($input['recipient_email'] ?? '')));
    if ($buyerEmail !== '' && !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Buyer email is invalid.');
    }
    if ($recipientEmail !== '' && !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Recipient email is invalid.');
    }

    $orderId = !empty($input['order_id']) ? (int) $input['order_id'] : null;
    $sourceLineKey = trim((string) ($input['source_line_key'] ?? ''));
    if ($orderId && $sourceLineKey !== '') {
        $existingStatement = $pdo->prepare('SELECT id FROM gift_cards WHERE order_id = :order_id AND source_line_key = :source_line_key LIMIT 1');
        $existingStatement->execute([
            ':order_id' => $orderId,
            ':source_line_key' => $sourceLineKey,
        ]);
        $existingId = (int) ($existingStatement->fetchColumn() ?: 0);
        if ($existingId > 0) {
            $existingGiftCard = girffonGiftCardFetchById($pdo, $existingId);
            if ($existingGiftCard) {
                return $existingGiftCard;
            }
        }
    }

    $giftCode = '';
    do {
        $giftCode = girffonGiftCardGenerateCode();
        $statement = $pdo->prepare('SELECT id FROM gift_cards WHERE gift_code = :gift_code LIMIT 1');
        $statement->execute([':gift_code' => $giftCode]);
    } while ($statement->fetch(PDO::FETCH_ASSOC));

    $giftCard = [
        'gift_code' => $giftCode,
        'initial_amount' => $amount,
        'remaining_balance' => $amount,
        'buyer_name' => trim((string) ($input['buyer_name'] ?? '')),
        'buyer_email' => $buyerEmail,
        'recipient_name' => trim((string) ($input['recipient_name'] ?? '')),
        'recipient_email' => $recipientEmail,
        'gift_message' => trim((string) ($input['gift_message'] ?? '')),
        'delivery_type' => $deliveryType,
        'status' => $status,
        'order_id' => $orderId,
        'source_line_key' => $sourceLineKey !== '' ? $sourceLineKey : null,
        'expires_at' => $expiresAt,
    ];

    $giftCard['barcode_value'] = girffonGiftCardBuildBarcodeValue($giftCode);
    $giftCard['public_reference'] = girffonGiftCardGenerateReference();
    $giftCard['qr_payload'] = girffonGiftCardBuildQrPayload($giftCard);

    $statement = $pdo->prepare(
        'INSERT INTO gift_cards (
            gift_code, initial_amount, remaining_balance, buyer_name, buyer_email,
            recipient_name, recipient_email, gift_message, delivery_type, status,
            order_id, source_line_key, qr_payload, barcode_value, public_reference, expires_at
         ) VALUES (
            :gift_code, :initial_amount, :remaining_balance, :buyer_name, :buyer_email,
            :recipient_name, :recipient_email, :gift_message, :delivery_type, :status,
            :order_id, :source_line_key, :qr_payload, :barcode_value, :public_reference, :expires_at
         )'
    );
    $statement->execute([
        ':gift_code' => $giftCard['gift_code'],
        ':initial_amount' => $giftCard['initial_amount'],
        ':remaining_balance' => $giftCard['remaining_balance'],
        ':buyer_name' => $giftCard['buyer_name'] !== '' ? $giftCard['buyer_name'] : null,
        ':buyer_email' => $giftCard['buyer_email'] !== '' ? $giftCard['buyer_email'] : null,
        ':recipient_name' => $giftCard['recipient_name'] !== '' ? $giftCard['recipient_name'] : null,
        ':recipient_email' => $giftCard['recipient_email'] !== '' ? $giftCard['recipient_email'] : null,
        ':gift_message' => $giftCard['gift_message'] !== '' ? $giftCard['gift_message'] : null,
        ':delivery_type' => $giftCard['delivery_type'],
        ':status' => $giftCard['status'],
        ':order_id' => $giftCard['order_id'],
        ':source_line_key' => $giftCard['source_line_key'],
        ':qr_payload' => $giftCard['qr_payload'] !== '' ? $giftCard['qr_payload'] : null,
        ':barcode_value' => $giftCard['barcode_value'] !== '' ? $giftCard['barcode_value'] : null,
        ':public_reference' => $giftCard['public_reference'],
        ':expires_at' => $giftCard['expires_at'],
    ]);

    $giftCard['id'] = (int) $pdo->lastInsertId();
    girffonGiftCardInsertTransaction($pdo, $giftCard['id'], [
        'order_id' => $giftCard['order_id'],
        'transaction_type' => 'issue',
        'amount' => $amount,
        'balance_before' => 0,
        'balance_after' => $amount,
        'notes' => 'Gift card issued.',
        'request_fingerprint' => hash('sha256', 'issue|' . $giftCard['gift_code'] . '|' . $giftCard['public_reference']),
    ]);

    return girffonGiftCardFetchById($pdo, $giftCard['id']) ?? $giftCard;
}

function girffonGiftCardInsertTransaction(PDO $pdo, int $giftCardId, array $transaction): void
{
    $statement = $pdo->prepare(
        'INSERT INTO gift_card_transactions (
            gift_card_id, order_id, transaction_type, amount,
            balance_before, balance_after, notes, request_fingerprint
         ) VALUES (
            :gift_card_id, :order_id, :transaction_type, :amount,
            :balance_before, :balance_after, :notes, :request_fingerprint
         )'
    );
    $statement->execute([
        ':gift_card_id' => $giftCardId,
        ':order_id' => !empty($transaction['order_id']) ? (int) $transaction['order_id'] : null,
        ':transaction_type' => trim((string) ($transaction['transaction_type'] ?? 'adjustment')),
        ':amount' => girffonGiftCardNormalizeAmount($transaction['amount'] ?? 0),
        ':balance_before' => girffonGiftCardNormalizeAmount($transaction['balance_before'] ?? 0),
        ':balance_after' => girffonGiftCardNormalizeAmount($transaction['balance_after'] ?? 0),
        ':notes' => trim((string) ($transaction['notes'] ?? '')) ?: null,
        ':request_fingerprint' => trim((string) ($transaction['request_fingerprint'] ?? '')) ?: null,
    ]);
}

function girffonGiftCardFetchById(PDO $pdo, int $giftCardId): ?array
{
    if ($giftCardId <= 0) {
        return null;
    }

    girffonGiftCardEnsureSchema($pdo);
    girffonGiftCardUpdateExpiredStatus($pdo);
    $statement = $pdo->prepare('SELECT * FROM gift_cards WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $giftCardId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function girffonGiftCardFetchByCode(PDO $pdo, string $giftCode, bool $lock = false): ?array
{
    girffonGiftCardEnsureSchema($pdo);
    girffonGiftCardUpdateExpiredStatus($pdo);
    $sql = 'SELECT * FROM gift_cards WHERE gift_code = :gift_code LIMIT 1';
    if ($lock) {
        $sql .= ' FOR UPDATE';
    }
    $statement = $pdo->prepare($sql);
    $statement->execute([':gift_code' => strtoupper(trim($giftCode))]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function girffonGiftCardFetchAll(PDO $pdo): array
{
    girffonGiftCardEnsureSchema($pdo);
    girffonGiftCardUpdateExpiredStatus($pdo);
    $statement = $pdo->query('SELECT * FROM gift_cards ORDER BY created_at DESC, id DESC');
    return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
}

function girffonGiftCardFetchTransactions(PDO $pdo, int $giftCardId): array
{
    if ($giftCardId <= 0) {
        return [];
    }

    girffonGiftCardEnsureSchema($pdo);
    $statement = $pdo->prepare('SELECT * FROM gift_card_transactions WHERE gift_card_id = :gift_card_id ORDER BY created_at DESC, id DESC');
    $statement->execute([':gift_card_id' => $giftCardId]);
    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function girffonGiftCardSummary(PDO $pdo): array
{
    girffonGiftCardEnsureSchema($pdo);
    girffonGiftCardUpdateExpiredStatus($pdo);
    $statement = $pdo->query(
        "SELECT
            COUNT(*) AS total_cards,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_cards,
            SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) AS used_cards,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) AS expired_cards,
            SUM(CASE WHEN status = 'active' THEN remaining_balance ELSE 0 END) AS remaining_balance_total
         FROM gift_cards"
    );
    $row = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : [];
    return [
        'total_cards' => (int) ($row['total_cards'] ?? 0),
        'active_cards' => (int) ($row['active_cards'] ?? 0),
        'used_cards' => (int) ($row['used_cards'] ?? 0),
        'expired_cards' => (int) ($row['expired_cards'] ?? 0),
        'remaining_balance_total' => (float) ($row['remaining_balance_total'] ?? 0),
    ];
}

function girffonGiftCardValidateForUse(PDO $pdo, string $giftCode): array
{
    $giftCard = girffonGiftCardFetchByCode($pdo, $giftCode, false);
    if (!$giftCard) {
        throw new RuntimeException('Gift card code was not found.');
    }

    if (($giftCard['status'] ?? '') !== 'active') {
        throw new RuntimeException('Gift card is not active.');
    }

    $expiresAt = trim((string) ($giftCard['expires_at'] ?? ''));
    if ($expiresAt !== '' && strtotime($expiresAt) !== false && strtotime($expiresAt) < time()) {
        throw new RuntimeException('Gift card has expired.');
    }

    $remaining = girffonGiftCardNormalizeAmount($giftCard['remaining_balance'] ?? 0);
    if ($remaining <= 0) {
        throw new RuntimeException('Gift card has no remaining balance.');
    }

    return $giftCard;
}

function girffonGiftCardApplyRedemption(PDO $pdo, string $giftCode, int $orderId, float $orderTotal, string $fingerprint): array
{
    $giftCard = girffonGiftCardFetchByCode($pdo, $giftCode, true);
    if (!$giftCard) {
        throw new RuntimeException('Gift card code was not found.');
    }

    $trimmedFingerprint = trim($fingerprint);
    if ($trimmedFingerprint !== '') {
        $existingByFingerprint = $pdo->prepare('SELECT amount, balance_after FROM gift_card_transactions WHERE request_fingerprint = :request_fingerprint LIMIT 1');
        $existingByFingerprint->execute([':request_fingerprint' => $trimmedFingerprint]);
        $existingTransaction = $existingByFingerprint->fetch(PDO::FETCH_ASSOC);
        if (is_array($existingTransaction)) {
            return [
                'applied_amount' => girffonGiftCardNormalizeAmount($existingTransaction['amount'] ?? 0),
                'remaining_balance' => girffonGiftCardNormalizeAmount($existingTransaction['balance_after'] ?? $giftCard['remaining_balance'] ?? 0),
                'gift_card' => $giftCard,
            ];
        }
    }

    if ($orderId > 0) {
        $existingByOrder = $pdo->prepare("SELECT amount, balance_after FROM gift_card_transactions WHERE gift_card_id = :gift_card_id AND order_id = :order_id AND transaction_type = 'redeem' LIMIT 1");
        $existingByOrder->execute([
            ':gift_card_id' => (int) $giftCard['id'],
            ':order_id' => $orderId,
        ]);
        $existingOrderTransaction = $existingByOrder->fetch(PDO::FETCH_ASSOC);
        if (is_array($existingOrderTransaction)) {
            return [
                'applied_amount' => girffonGiftCardNormalizeAmount($existingOrderTransaction['amount'] ?? 0),
                'remaining_balance' => girffonGiftCardNormalizeAmount($existingOrderTransaction['balance_after'] ?? $giftCard['remaining_balance'] ?? 0),
                'gift_card' => $giftCard,
            ];
        }
    }

    if (($giftCard['status'] ?? '') !== 'active') {
        throw new RuntimeException('Gift card is not active.');
    }

    $expiresAt = trim((string) ($giftCard['expires_at'] ?? ''));
    if ($expiresAt !== '' && strtotime($expiresAt) !== false && strtotime($expiresAt) < time()) {
        throw new RuntimeException('Gift card has expired.');
    }

    $remainingBefore = girffonGiftCardNormalizeAmount($giftCard['remaining_balance'] ?? 0);
    $requestedAmount = girffonGiftCardNormalizeAmount($orderTotal);
    if ($requestedAmount <= 0 || $remainingBefore <= 0) {
        return [
            'applied_amount' => 0.0,
            'remaining_balance' => $remainingBefore,
            'gift_card' => $giftCard,
        ];
    }

    $appliedAmount = min($requestedAmount, $remainingBefore);
    $remainingAfter = girffonGiftCardNormalizeAmount($remainingBefore - $appliedAmount);
    $status = $remainingAfter <= 0 ? 'used' : 'active';

    $update = $pdo->prepare('UPDATE gift_cards SET remaining_balance = :remaining_balance, status = :status WHERE id = :id LIMIT 1');
    $update->execute([
        ':remaining_balance' => $remainingAfter,
        ':status' => $status,
        ':id' => (int) $giftCard['id'],
    ]);

    girffonGiftCardInsertTransaction($pdo, (int) $giftCard['id'], [
        'order_id' => $orderId,
        'transaction_type' => 'redeem',
        'amount' => $appliedAmount,
        'balance_before' => $remainingBefore,
        'balance_after' => $remainingAfter,
        'notes' => 'Gift card redeemed at checkout.',
        'request_fingerprint' => $trimmedFingerprint,
    ]);

    $giftCard['remaining_balance'] = $remainingAfter;
    $giftCard['status'] = $status;

    return [
        'applied_amount' => $appliedAmount,
        'remaining_balance' => $remainingAfter,
        'gift_card' => $giftCard,
    ];
}

function girffonGiftCardUsageCount(PDO $pdo, int $giftCardId): int
{
    if ($giftCardId <= 0) {
        return 0;
    }

    $statement = $pdo->prepare("SELECT COUNT(*) FROM gift_card_transactions WHERE gift_card_id = :gift_card_id AND transaction_type = 'redeem'");
    $statement->execute([':gift_card_id' => $giftCardId]);
    return (int) $statement->fetchColumn();
}

function girffonGiftCardDeleteAllowed(PDO $pdo, int $giftCardId): bool
{
    $giftCard = girffonGiftCardFetchById($pdo, $giftCardId);
    if (!$giftCard) {
        return false;
    }

    return girffonGiftCardUsageCount($pdo, $giftCardId) === 0
        && girffonGiftCardNormalizeAmount($giftCard['remaining_balance'] ?? 0) === girffonGiftCardNormalizeAmount($giftCard['initial_amount'] ?? 0);
}

function girffonGiftCardUpdate(PDO $pdo, int $giftCardId, array $input): void
{
    $giftCard = girffonGiftCardFetchById($pdo, $giftCardId);
    if (!$giftCard) {
        throw new RuntimeException('Gift card not found.');
    }

    $status = girffonGiftCardNormalizeStatus((string) ($input['status'] ?? $giftCard['status'] ?? 'active'));
    $deliveryType = girffonGiftCardNormalizeDeliveryType((string) ($input['delivery_type'] ?? $giftCard['delivery_type'] ?? 'digital'));
    $expiresAt = girffonGiftCardExpiryValue((string) ($input['expires_at'] ?? $giftCard['expires_at'] ?? ''));
    if ($expiresAt === null) {
        throw new InvalidArgumentException('Gift card expiration date is invalid.');
    }

    $statement = $pdo->prepare(
        'UPDATE gift_cards
         SET buyer_name = :buyer_name,
             buyer_email = :buyer_email,
             recipient_name = :recipient_name,
             recipient_email = :recipient_email,
             gift_message = :gift_message,
             delivery_type = :delivery_type,
             status = :status,
             expires_at = :expires_at
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute([
        ':buyer_name' => trim((string) ($input['buyer_name'] ?? $giftCard['buyer_name'] ?? '')) ?: null,
        ':buyer_email' => strtolower(trim((string) ($input['buyer_email'] ?? $giftCard['buyer_email'] ?? ''))) ?: null,
        ':recipient_name' => trim((string) ($input['recipient_name'] ?? $giftCard['recipient_name'] ?? '')) ?: null,
        ':recipient_email' => strtolower(trim((string) ($input['recipient_email'] ?? $giftCard['recipient_email'] ?? ''))) ?: null,
        ':gift_message' => trim((string) ($input['gift_message'] ?? $giftCard['gift_message'] ?? '')) ?: null,
        ':delivery_type' => $deliveryType,
        ':status' => $status,
        ':expires_at' => $expiresAt,
        ':id' => $giftCardId,
    ]);
}

function girffonGiftCardDelete(PDO $pdo, int $giftCardId): void
{
    if (!girffonGiftCardDeleteAllowed($pdo, $giftCardId)) {
        throw new RuntimeException('Only unused gift cards can be deleted.');
    }

    $deleteTransactions = $pdo->prepare('DELETE FROM gift_card_transactions WHERE gift_card_id = :gift_card_id');
    $deleteTransactions->execute([':gift_card_id' => $giftCardId]);

    $deleteGiftCard = $pdo->prepare('DELETE FROM gift_cards WHERE id = :id LIMIT 1');
    $deleteGiftCard->execute([':id' => $giftCardId]);
}

function girffonGiftCardBuildPrintHtml(array $giftCard): string
{
    $giftCode = htmlspecialchars((string) ($giftCard['gift_code'] ?? ''), ENT_QUOTES, 'UTF-8');
    $amount = htmlspecialchars('EUR ' . number_format((float) ($giftCard['initial_amount'] ?? 0), 2, '.', ','), ENT_QUOTES, 'UTF-8');
    $recipientName = htmlspecialchars((string) ($giftCard['recipient_name'] ?? 'Gift Recipient'), ENT_QUOTES, 'UTF-8');
    $message = nl2br(htmlspecialchars((string) ($giftCard['gift_message'] ?? ''), ENT_QUOTES, 'UTF-8'));
    $expiresAt = htmlspecialchars((string) ($giftCard['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $barcode = htmlspecialchars((string) (($giftCard['barcode_value'] ?? '') !== '' ? $giftCard['barcode_value'] : ($giftCard['gift_code'] ?? '')), ENT_QUOTES, 'UTF-8');
    $qrUrl = htmlspecialchars(girffonGiftCardQrImageUrl((string) ($giftCard['qr_payload'] ?? girffonGiftCardBuildQrPayload($giftCard))), ENT_QUOTES, 'UTF-8');
        $logoUrl = htmlspecialchars('/GirffoN/Image/Logo/Logo.png', ENT_QUOTES, 'UTF-8');
        $printedAt = htmlspecialchars(date('n/j/y, g:i A'), ENT_QUOTES, 'UTF-8');
        $giftNote = $message !== '' ? $message : 'Enjoy your GirffoN gift card.';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GirffoN Gift Card</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 18px;
            background: #efe9df;
            color: #2a2118;
            font-family: Georgia, "Times New Roman", serif;
        }

        .gift-card-sheet {
            width: 190mm;
            min-height: 275mm;
            margin: 0 auto;
            background: linear-gradient(180deg, #fffdfa 0%, #fbf6ed 100%);
            border: 1px solid #ead7b0;
            border-radius: 18px;
            box-shadow: 0 12px 38px rgba(60, 42, 20, 0.14);
            padding: 16mm 15mm 14mm;
            position: relative;
            overflow: hidden;
        }

        .gift-card-sheet::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(215, 181, 111, 0.22), transparent 30%),
                linear-gradient(135deg, rgba(28, 25, 22, 0.03), transparent 42%);
            pointer-events: none;
        }

        .gift-card-frame {
            position: relative;
            z-index: 1;
            min-height: calc(275mm - 30mm);
            border: 1px solid rgba(214, 180, 110, 0.72);
            border-radius: 14px;
            padding: 14mm 13mm 12mm;
            display: grid;
            grid-template-rows: auto auto 1fr auto;
            gap: 12mm;
        }

        .gift-card-meta-bar,
        .gift-card-footer-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #7d6a4e;
        }

        .gift-card-meta-bar strong,
        .gift-card-footer-bar strong {
            color: #3b2c1d;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 10px;
        }

        .gift-card-header {
            display: grid;
            grid-template-columns: 1.25fr .75fr;
            gap: 18mm;
            align-items: start;
        }

        .gift-card-brand-stack {
            display: grid;
            gap: 8mm;
        }

        .gift-card-logo-row {
            display: block;
        }

        .gift-card-logo-row img {
            width: 272px;
            height: auto;
            display: block;
        }

        .gift-card-kicker {
            margin: 0;
            font-size: 11px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: #b59357;
        }

        .gift-card-title {
            margin: 0;
            font-size: 30px;
            line-height: 1.06;
            color: #a8977d;
            max-width: 420px;
        }

        .gift-card-amount-box {
            justify-self: end;
            text-align: right;
            color: #b19b79;
        }

        .gift-card-amount-box span {
            display: block;
            font-size: 17px;
            letter-spacing: 0.32em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .gift-card-amount-box strong {
            display: block;
            font-size: 54px;
            line-height: 0.94;
            font-weight: 700;
        }

        .gift-card-body {
            display: grid;
            grid-template-columns: 1fr 220px;
            gap: 18mm;
            align-items: end;
        }

        .gift-card-message-area {
            align-self: end;
            display: grid;
            gap: 12px;
            color: #af9f88;
        }

        .gift-card-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #c1b19a;
        }

        .gift-card-recipient {
            font-size: 35px;
            line-height: 1;
            color: #8f7e67;
            font-weight: 700;
            text-transform: lowercase;
        }

        .gift-card-note {
            margin: 0;
            font-size: 18px;
            line-height: 1.6;
            color: #b0a08a;
            max-width: 420px;
        }

        .gift-card-details {
            display: grid;
            gap: 7px;
            margin-top: 8px;
            font-size: 17px;
            color: #8b7761;
        }

        .gift-card-details strong {
            color: #7f6745;
        }

        .gift-card-code-panel {
            display: grid;
            gap: 14px;
            align-self: end;
        }

        .gift-card-qr-box,
        .gift-card-barcode-box {
            background: #fffdf9;
            border: 1px solid #ead9b5;
            border-radius: 12px;
            padding: 12px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6);
        }

        .gift-card-qr-box {
            width: 190px;
            justify-self: center;
        }

        .gift-card-qr-box img {
            display: block;
            width: 166px;
            height: 166px;
            margin: 0 auto;
            background: #fff;
        }

        .gift-card-barcode-box {
            text-align: center;
            color: #201912;
            padding-top: 14px;
        }

        #giftCardBarcode {
            min-height: 62px;
        }

        #giftCardBarcode svg {
            width: 100%;
            height: 64px;
            display: block;
        }

        .gift-card-code {
            margin-top: 10px;
            font-size: 24px;
            letter-spacing: 0.22em;
            color: #463320;
        }

        @media screen and (max-width: 860px) {
            body {
                padding: 0;
            }

            .gift-card-sheet {
                width: 100%;
                min-height: auto;
                border-radius: 0;
                border: 0;
                box-shadow: none;
                padding: 24px;
            }

            .gift-card-frame {
                min-height: auto;
                padding: 24px;
            }

            .gift-card-header,
            .gift-card-body {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .gift-card-amount-box,
            .gift-card-code-panel {
                justify-self: start;
                text-align: left;
            }

            .gift-card-recipient {
                font-size: 28px;
            }

            .gift-card-title {
                font-size: 24px;
            }

            .gift-card-code {
                font-size: 18px;
            }
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .gift-card-sheet {
                width: auto;
                min-height: 0;
                margin: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
                background: #fff;
            }

            .gift-card-frame {
                min-height: 0;
                border-radius: 0;
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <section class="gift-card-sheet">
        <div class="gift-card-frame">
            <div class="gift-card-meta-bar">
                <span>{$printedAt}</span>
                <strong>GirffoN Gift Card</strong>
            </div>

            <div class="gift-card-header">
                <div class="gift-card-brand-stack">
                    <div class="gift-card-logo-row">
                        <img src="{$logoUrl}" alt="GirffoN Logo">
                    </div>
                    <div>
                        <p class="gift-card-kicker">GirffoN Gift Card</p>
                        <h1 class="gift-card-title">Luxury credit for premium custom fashion</h1>
                    </div>
                </div>
                <div class="gift-card-amount-box">
                    <span>Value</span>
                    <strong>{$amount}</strong>
                </div>
            </div>

            <div class="gift-card-body">
                <div class="gift-card-message-area">
                    <div class="gift-card-label">To</div>
                    <div class="gift-card-recipient">{$recipientName}</div>
                    <p class="gift-card-note">{$giftNote}</p>
                    <div class="gift-card-details">
                        <span><strong>Code:</strong> {$giftCode}</span>
                        <span><strong>Expires:</strong> {$expiresAt}</span>
                    </div>
                </div>

                <div class="gift-card-code-panel">
                    <div class="gift-card-qr-box">
                        <img src="{$qrUrl}" alt="Gift card QR code">
                    </div>
                    <div class="gift-card-barcode-box">
                        <div id="giftCardBarcode" data-barcode="{$barcode}"></div>
                        <div class="gift-card-code">{$giftCode}</div>
                    </div>
                </div>
            </div>

            <div class="gift-card-footer-bar">
                <span>Redeem online during checkout with the printed code or QR.</span>
                <strong>Premium Clothing</strong>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        var target = document.getElementById('giftCardBarcode');
        if (target && window.JsBarcode) {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            target.appendChild(svg);
            window.JsBarcode(svg, target.getAttribute('data-barcode') || '{$barcode}', {
                format: 'CODE128',
                displayValue: false,
                height: 58,
                margin: 0,
                width: 1.55,
                background: '#fffdf9',
                lineColor: '#201912'
            });
        }
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
HTML;
}

function girffonSendGiftCardEmail(array $giftCard): bool
{
    $recipientEmail = strtolower(trim((string) ($giftCard['recipient_email'] ?? '')));
    if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Recipient email is invalid.');
    }

    $mailConfig = girffonMailConfig();
    $giftCode = (string) ($giftCard['gift_code'] ?? '');
    $recipientName = trim((string) ($giftCard['recipient_name'] ?? 'GirffoN Customer'));
    $buyerName = trim((string) ($giftCard['buyer_name'] ?? 'GirffoN'));
    $amount = 'EUR ' . number_format((float) ($giftCard['initial_amount'] ?? 0), 2, '.', ',');
    $expiresAt = trim((string) ($giftCard['expires_at'] ?? ''));
    $message = trim((string) ($giftCard['gift_message'] ?? ''));
    $qrUrl = girffonGiftCardQrImageUrl((string) ($giftCard['qr_payload'] ?? girffonGiftCardBuildQrPayload($giftCard)));

    $subject = 'GirffoN Gift Card - ' . $giftCode;
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'
        . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8')
        . '</title></head><body style="margin:0;padding:0;background:#f5f1ea;font-family:Georgia,Times New Roman,serif;color:#1f1812;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 0;background:#f5f1ea;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:720px;background:#fffdf9;border:1px solid #e5ddd0;">'
        . '<tr><td style="padding:30px 34px;background:#1f1812;color:#f7efe3;"><div style="font-size:13px;letter-spacing:2px;text-transform:uppercase;">GirffoN</div><h1 style="margin:10px 0 0;font-size:30px;">Gift Card Delivery</h1></td></tr>'
        . '<tr><td style="padding:30px 34px;"><p style="margin:0 0 14px;font-size:16px;line-height:1.7;">Hello ' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars($buyerName, ENT_QUOTES, 'UTF-8') . ' has sent you a GirffoN gift card.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;border-collapse:collapse;">'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;width:34%;">Gift Card Code</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;letter-spacing:1.5px;">' . htmlspecialchars($giftCode, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Amount</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Expires</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>'
        . ($message !== '' ? '<p style="margin:0 0 18px;padding:18px;background:#f8f2e9;border-left:4px solid #c9a56a;line-height:1.7;">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>' : '')
        . '<p style="margin:0 0 12px;">Use this code at checkout inside the field <strong>Gift Card Code / Codice carta regalo</strong>.</p>'
        . '<p style="margin:0 0 22px;"><img src="' . htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') . '" alt="Gift card QR code" width="180" height="180" style="display:block;background:#fff;padding:8px;border:1px solid #e5ddd0;"></p>'
        . '<p style="margin:0;color:#7a6a58;font-size:13px;line-height:1.7;">If you prefer, you can redeem the card by entering the gift card code manually during checkout.</p>'
        . '</td></tr></table></td></tr></table></body></html>';
    $text = "GirffoN Gift Card\nGift Card Code: {$giftCode}\nAmount: {$amount}\nExpires: {$expiresAt}\n\n{$message}";

    return girffonSendMail([
        'to_email' => $recipientEmail,
        'to_name' => $recipientName,
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ], $mailConfig);
}

function girffonSendMail(array $message, array $mailConfig): bool
{
    $transport = strtolower(trim((string) ($mailConfig['transport'] ?? 'smtp')));
    if ($transport === 'phpmailer') {
        return girffonSendMailWithPhpMailer($mailConfig, $message);
    }
    if ($transport === 'mail') {
        return girffonSendMailWithPhpMail($mailConfig, $message);
    }

    try {
        return girffonSendMailWithSocketSmtp($mailConfig, $message);
    } catch (Throwable $throwable) {
        girffonOrderMailDebugLog($mailConfig, 'Gift card SMTP transport failed, falling back to PHPMailer: ' . $throwable->getMessage());
        return girffonSendMailWithPhpMailer($mailConfig, $message);
    }
}

function girffonGiftCardCheckRateLimit(string $bucket): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $config = girffonGiftCardConfig();
    $limit = (int) $config['redeem_attempt_limit'];
    $window = (int) $config['redeem_attempt_window'];
    $key = 'girffon_gift_card_rate_' . md5($bucket);
    $timestamps = $_SESSION[$key] ?? [];
    $now = time();

    if (!is_array($timestamps)) {
        $timestamps = [];
    }

    $timestamps = array_values(array_filter($timestamps, static function ($timestamp) use ($now, $window): bool {
        return is_int($timestamp) && $timestamp >= ($now - $window);
    }));

    if (count($timestamps) >= $limit) {
        throw new RuntimeException('Too many gift card attempts. Please wait and try again.');
    }

    $timestamps[] = $now;
    $_SESSION[$key] = $timestamps;
}
