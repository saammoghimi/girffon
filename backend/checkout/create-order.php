<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../cart/cart-common.php';
require_once __DIR__ . '/../utils/order-confirmation-mailer.php';
require_once __DIR__ . '/../utils/gift-card-service.php';
require_once __DIR__ . '/../utils/csrf.php';
require_once __DIR__ . '/../admin/dashboard-data.php';

const GIRFFON_GIFT_CARD_CHECKOUT_LOG = __DIR__ . '/../logs/gift-card-checkout.log';

function girffonCheckoutJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonCheckoutLog(string $message, array $context = []): void
{
    $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . $message;
    if ($context) {
        $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($encoded) && $encoded !== '') {
            $line .= ' ' . $encoded;
        }
    }

    error_log($line . PHP_EOL, 3, GIRFFON_GIFT_CARD_CHECKOUT_LOG);
}

function girffonCheckoutRequestData(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function girffonCheckoutPaymentStatus(array $payload, float $amountDue): string
{
    $requestedStatus = strtolower(trim((string) ($payload['payment_status'] ?? '')));
    if (in_array($requestedStatus, ['paid', 'pending'], true)) {
        return $requestedStatus;
    }

    return $amountDue <= 0 ? 'paid' : 'pending';
}

function girffonCheckoutColumnExists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
    $statement->execute([':column' => $column]);
    return (bool) $statement->fetch(PDO::FETCH_ASSOC);
}

function girffonCheckoutEnsureSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $orderColumns = [
        'user_id' => 'ALTER TABLE orders ADD user_id INT NULL AFTER id',
        'phone' => 'ALTER TABLE orders ADD phone VARCHAR(50) NULL AFTER customer_email',
        'address' => 'ALTER TABLE orders ADD address VARCHAR(255) NULL AFTER phone',
        'city' => 'ALTER TABLE orders ADD city VARCHAR(120) NULL AFTER address',
        'country' => 'ALTER TABLE orders ADD country VARCHAR(120) NULL AFTER city',
        'postcode' => 'ALTER TABLE orders ADD postcode VARCHAR(40) NULL AFTER country',
        'subtotal' => 'ALTER TABLE orders ADD subtotal DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER postcode',
        'shipping' => 'ALTER TABLE orders ADD shipping DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER subtotal',
        'payment_method' => 'ALTER TABLE orders ADD payment_method VARCHAR(50) NOT NULL DEFAULT "bank_transfer" AFTER total',
    ];

    foreach ($orderColumns as $column => $sql) {
        if (!girffonCheckoutColumnExists($pdo, 'orders', $column)) {
            $pdo->exec($sql);
        }
    }

    $orderItemColumns = [
        'product_id' => 'ALTER TABLE order_items ADD product_id VARCHAR(100) NULL AFTER order_id',
        'name' => 'ALTER TABLE order_items ADD name VARCHAR(200) NULL AFTER product_id',
        'line_total' => 'ALTER TABLE order_items ADD line_total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER price',
    ];

    foreach ($orderItemColumns as $column => $sql) {
        if (!girffonCheckoutColumnExists($pdo, 'order_items', $column)) {
            $pdo->exec($sql);
        }
    }

    $invoiceColumns = [
        'user_id' => 'ALTER TABLE invoices ADD user_id INT NULL AFTER order_id',
        'subtotal' => 'ALTER TABLE invoices ADD subtotal DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER user_id',
        'tax' => 'ALTER TABLE invoices ADD tax DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER subtotal',
        'shipping' => 'ALTER TABLE invoices ADD shipping DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER tax',
        'total' => 'ALTER TABLE invoices ADD total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER shipping',
        'status' => 'ALTER TABLE invoices ADD status VARCHAR(50) NOT NULL DEFAULT "pending" AFTER total',
    ];

    foreach ($invoiceColumns as $column => $sql) {
        if (!girffonCheckoutColumnExists($pdo, 'invoices', $column)) {
            $pdo->exec($sql);
        }
    }

    $checked = true;
}

function girffonNextSequenceNumber(PDO $pdo, string $table, string $column, string $prefix, int $year): string
{
    $pattern = $prefix . '-' . $year . '-%';
    $statement = $pdo->prepare("SELECT {$column} FROM {$table} WHERE {$column} LIKE :pattern ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $statement->execute([':pattern' => $pattern]);
    $lastValue = (string) ($statement->fetchColumn() ?: '');
    $lastNumber = 0;

    if ($lastValue !== '' && preg_match('/-(\d{4})$/', $lastValue, $matches)) {
        $lastNumber = (int) $matches[1];
    }

    return sprintf('%s-%d-%04d', $prefix, $year, $lastNumber + 1);
}

function girffonNormalizeSessionCartItem(array $item): array
{
    $quantity = max(1, (int) ($item['quantity'] ?? $item['qty'] ?? 1));
    $price = round((float) ($item['priceNumber'] ?? $item['price'] ?? 0), 2);
    $lineTotal = round((float) ($item['line_total'] ?? $item['total_price'] ?? ($price * $quantity)), 2);
    $sku = trim((string) ($item['sku'] ?? $item['id'] ?? $item['code'] ?? ''));
    $name = trim((string) ($item['name'] ?? $item['title'] ?? 'GirffoN Product'));
    $itemType = girffonCartNormalizeItemType($item['item_type'] ?? '', $sku);

    return [
        'product_id' => $sku,
        'sku' => $sku,
        'name' => $name !== '' ? $name : 'GirffoN Product',
        'size' => trim((string) ($item['size'] ?? '')),
        'color' => trim((string) ($item['color'] ?? '')),
        'quantity' => $quantity,
        'price' => $price,
        'line_total' => $lineTotal,
        'image' => trim((string) ($item['image'] ?? $item['img'] ?? '')),
        'line_key' => trim((string) ($item['line_key'] ?? '')),
        'item_type' => $itemType,
        'delivery_type' => trim((string) ($item['delivery_type'] ?? '')),
        'gift_card_amount' => round((float) ($item['gift_card_amount'] ?? 0), 2),
        'buyer_name' => trim((string) ($item['buyer_name'] ?? '')),
        'buyer_email' => strtolower(trim((string) ($item['buyer_email'] ?? ''))),
        'recipient_name' => trim((string) ($item['recipient_name'] ?? '')),
        'recipient_email' => strtolower(trim((string) ($item['recipient_email'] ?? ''))),
        'gift_message' => trim((string) ($item['gift_message'] ?? '')),
        'expires_at' => trim((string) ($item['expires_at'] ?? '')),
    ];
}

function girffonCheckoutUser(PDO $pdo, int $userId): ?array
{
    if ($userId <= 0) {
        return null;
    }

    $statement = $pdo->prepare('SELECT id, username, first_name, last_name, email, phone, address, city, country FROM users WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $userId]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    return is_array($user) ? $user : null;
}

function girffonCheckoutField(array $shipping, ?array $user, string $shippingKey, string $userKey = ''): string
{
    $value = trim((string) ($shipping[$shippingKey] ?? ''));
    if ($value !== '') {
        return $value;
    }

    if ($user && $userKey !== '') {
        return trim((string) ($user[$userKey] ?? ''));
    }

    return '';
}

function girffonCheckoutRequiresShipping(array $items): bool
{
    foreach ($items as $item) {
        $itemType = strtolower(trim((string) ($item['item_type'] ?? 'product')));
        $deliveryType = strtolower(trim((string) ($item['delivery_type'] ?? '')));
        if ($itemType !== 'gift_card' || $deliveryType === 'physical') {
            return true;
        }
    }

    return false;
}

function girffonCheckoutShippingAmount(array $items): float
{
    $shipping = 0.0;
    $physicalShipping = (float) (girffonGiftCardConfig()['physical_shipping'] ?? 0);
    foreach ($items as $item) {
        if (strtolower(trim((string) ($item['item_type'] ?? 'product'))) === 'gift_card' && strtolower(trim((string) ($item['delivery_type'] ?? ''))) === 'physical') {
            $shipping += $physicalShipping * max(1, (int) ($item['quantity'] ?? 1));
        }
    }

    return round($shipping, 2);
}

function girffonCheckoutFingerprint(array $items, array $shipping, string $giftCardCode): string
{
    return hash('sha256', json_encode([
        'items' => $items,
        'shipping' => $shipping,
        'gift_card_code' => strtoupper(trim($giftCardCode)),
        'user_id' => (int) ($_SESSION['user_id'] ?? $_SESSION['girffon_user_id'] ?? 0),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonCheckoutJson(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
    girffonCheckoutJson(419, ['ok' => false, 'message' => 'Security token mismatch.']);
}

$payload = girffonCheckoutRequestData();
$shipping = is_array($payload['shipping'] ?? null) ? $payload['shipping'] : [];
$analyticsPayload = is_array($payload['analytics'] ?? null) ? $payload['analytics'] : [];
$giftCardCode = strtoupper(trim((string) ($payload['gift_card_code'] ?? '')));
$userId = (int) ($_SESSION['user_id'] ?? $_SESSION['girffon_user_id'] ?? 0);
$user = girffonCheckoutUser($pdo, $userId);

$sessionItems = girffonCartSessionItems();
$normalizedItems = array_values(array_filter(array_map('girffonNormalizeSessionCartItem', $sessionItems), static function (array $item): bool {
    return $item['name'] !== '' && $item['quantity'] > 0 && $item['price'] >= 0;
}));

if (!$normalizedItems) {
    girffonCheckoutJson(422, ['ok' => false, 'message' => 'Your cart is empty.']);
}

$customerName = girffonCheckoutField($shipping, $user, 'fullName');
if ($customerName === '' && $user) {
    $customerName = trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? '')));
}
if ($customerName === '' && $user) {
    $customerName = trim((string) ($user['username'] ?? ''));
}

$customerEmail = trim(girffonCheckoutField($shipping, $user, 'email', 'email'));
$customerPhone = girffonCheckoutField($shipping, $user, 'phone', 'phone');
$customerAddress = girffonCheckoutField($shipping, $user, 'address', 'address');
$customerCity = girffonCheckoutField($shipping, $user, 'city', 'city');
$customerCountry = girffonCheckoutField($shipping, $user, 'country', 'country');
$customerPostcode = trim((string) ($shipping['postalCode'] ?? ''));
$requiresShipping = girffonCheckoutRequiresShipping($normalizedItems);

if ($customerName === '') {
    girffonCheckoutJson(422, ['ok' => false, 'message' => 'Customer name is required.']);
}

if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    girffonCheckoutJson(422, ['ok' => false, 'message' => 'A valid email is required.']);
}

if ($requiresShipping && ($customerPhone === '' || $customerAddress === '' || $customerCity === '' || $customerCountry === '')) {
    girffonCheckoutJson(422, ['ok' => false, 'message' => 'Phone, address, city, and country are required.']);
}

$subtotal = round(array_reduce($normalizedItems, static function (float $sum, array $item): float {
    return $sum + $item['line_total'];
}, 0.0), 2);
$shippingAmount = girffonCheckoutShippingAmount($normalizedItems);
$taxAmount = 0.0;
$grossTotal = round($subtotal + $shippingAmount + $taxAmount, 2);

$checkoutFingerprint = girffonCheckoutFingerprint($normalizedItems, $shipping, $giftCardCode);
$lastCheckout = $_SESSION['girffon_last_checkout'] ?? null;
if (is_array($lastCheckout)
    && ($lastCheckout['fingerprint'] ?? '') === $checkoutFingerprint
    && !empty($lastCheckout['response'])
    && (time() - (int) ($lastCheckout['created_at'] ?? 0)) <= 120
) {
    girffonCheckoutJson(200, $lastCheckout['response']);
}

$giftCardAppliedAmount = 0.0;
$amountDue = $grossTotal;

if ($grossTotal <= 0) {
    girffonCheckoutJson(422, ['ok' => false, 'message' => 'Order total must be greater than zero.']);
}

$year = (int) date('Y');
$trackingCode = sprintf('TRK-%d-%06d', $year, random_int(1, 999999));
$paymentMethod = trim((string) ($payload['payment_method'] ?? 'bank_transfer')) ?: 'bank_transfer';
$paymentStatus = girffonCheckoutPaymentStatus($payload, $grossTotal);
$orderStatus = 'processing';
$invoiceStatus = $paymentStatus === 'paid' ? 'paid' : 'pending';

try {
    girffonCheckoutEnsureSchema($pdo);
    girffonGiftCardEnsureSchema($pdo);
    $pdo->beginTransaction();

    $orderNumber = girffonNextSequenceNumber($pdo, 'orders', 'order_number', 'GF', $year);
    if ($giftCardCode !== '') {
        girffonGiftCardCheckRateLimit((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . $giftCardCode);
        $giftCardPreview = girffonGiftCardValidateForUse($pdo, $giftCardCode);
        $giftCardAppliedAmount = min($grossTotal, (float) ($giftCardPreview['remaining_balance'] ?? 0));
        $amountDue = round(max(0, $grossTotal - $giftCardAppliedAmount), 2);
        if ($amountDue <= 0) {
            $paymentMethod = 'gift_card';
            $paymentStatus = 'paid';
            $invoiceStatus = 'paid';
        }
    }

    girffonCheckoutLog('Checkout request received', [
        'payment_status' => $paymentStatus,
        'payment_method' => $paymentMethod,
        'item_count' => count($normalizedItems),
        'contains_gift_card' => (bool) array_filter($normalizedItems, static function (array $item): bool {
            return ($item['item_type'] ?? 'product') === 'gift_card';
        }),
    ]);

    $insertOrder = $pdo->prepare(
        'INSERT INTO orders (user_id, order_number, customer_name, customer_email, phone, address, city, country, postcode, subtotal, shipping, total, payment_method, payment_status, order_status, tracking_code, gift_card_code, gift_card_amount, amount_due)
         VALUES (:user_id, :order_number, :customer_name, :customer_email, :phone, :address, :city, :country, :postcode, :subtotal, :shipping, :total, :payment_method, :payment_status, :order_status, :tracking_code, :gift_card_code, :gift_card_amount, :amount_due)'
    );
    $insertOrder->execute([
        ':user_id' => $user ? (int) $user['id'] : null,
        ':order_number' => $orderNumber,
        ':customer_name' => $customerName,
        ':customer_email' => $customerEmail,
        ':phone' => $customerPhone,
        ':address' => $customerAddress,
        ':city' => $customerCity,
        ':country' => $customerCountry,
        ':postcode' => $customerPostcode !== '' ? $customerPostcode : null,
        ':subtotal' => $subtotal,
        ':shipping' => $shippingAmount,
        ':total' => $amountDue,
        ':payment_method' => $paymentMethod,
        ':payment_status' => $paymentStatus,
        ':order_status' => $orderStatus,
        ':tracking_code' => $trackingCode,
        ':gift_card_code' => $giftCardCode !== '' ? $giftCardCode : null,
        ':gift_card_amount' => $giftCardAppliedAmount,
        ':amount_due' => $amountDue,
    ]);

    $orderId = (int) $pdo->lastInsertId();
    $giftCardOrdersToSend = [];

    if ($giftCardCode !== '' && $giftCardAppliedAmount > 0) {
        $fingerprint = hash('sha256', $checkoutFingerprint . '|order|' . $orderId);
        $giftCardRedemption = girffonGiftCardApplyRedemption($pdo, $giftCardCode, $orderId, $grossTotal, $fingerprint);
        $giftCardAppliedAmount = (float) ($giftCardRedemption['applied_amount'] ?? $giftCardAppliedAmount);
        $amountDue = round(max(0, $grossTotal - $giftCardAppliedAmount), 2);
        if ($amountDue <= 0) {
            $paymentMethod = 'gift_card';
            $paymentStatus = 'paid';
        }
        $updateOrderGiftCard = $pdo->prepare('UPDATE orders SET total = :total, payment_method = :payment_method, payment_status = :payment_status, gift_card_amount = :gift_card_amount, amount_due = :amount_due WHERE id = :id LIMIT 1');
        $updateOrderGiftCard->execute([
            ':total' => $amountDue,
            ':payment_method' => $amountDue <= 0 ? 'gift_card' : $paymentMethod,
            ':payment_status' => $amountDue <= 0 ? 'paid' : $paymentStatus,
            ':gift_card_amount' => $giftCardAppliedAmount,
            ':amount_due' => $amountDue,
            ':id' => $orderId,
        ]);
        if ($amountDue <= 0) {
            $invoiceStatus = 'paid';
        }
    }

    $insertItem = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, item_type, name, product_name, sku, size, color, quantity, price, line_total, image, metadata_json)
         VALUES (:order_id, :product_id, :item_type, :name, :product_name, :sku, :size, :color, :quantity, :price, :line_total, :image, :metadata_json)'
    );

    foreach ($normalizedItems as $item) {
        girffonCheckoutLog('Normalized checkout item', [
            'sku' => $item['sku'] ?? '',
            'name' => $item['name'] ?? '',
            'item_type' => $item['item_type'] ?? '',
            'gift_card_amount' => $item['gift_card_amount'] ?? 0,
            'line_key' => $item['line_key'] ?? '',
        ]);

        $itemMetadata = [
            'line_key' => $item['line_key'] ?? '',
            'delivery_type' => $item['delivery_type'] ?? '',
            'gift_card_amount' => $item['gift_card_amount'] ?? 0,
            'buyer_name' => $item['buyer_name'] ?? '',
            'buyer_email' => $item['buyer_email'] ?? '',
            'recipient_name' => $item['recipient_name'] ?? '',
            'recipient_email' => $item['recipient_email'] ?? '',
            'gift_message' => $item['gift_message'] ?? '',
            'expires_at' => $item['expires_at'] ?? '',
        ];
        $insertItem->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'] !== '' ? $item['product_id'] : null,
            ':item_type' => $item['item_type'] ?? 'product',
            ':name' => $item['name'],
            ':product_name' => $item['name'],
            ':sku' => $item['sku'] !== '' ? $item['sku'] : null,
            ':size' => $item['size'] !== '' ? $item['size'] : null,
            ':color' => $item['color'] !== '' ? $item['color'] : null,
            ':quantity' => $item['quantity'],
            ':price' => $item['price'],
            ':line_total' => $item['line_total'],
            ':image' => $item['image'] !== '' ? $item['image'] : null,
            ':metadata_json' => json_encode($itemMetadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if (($item['item_type'] ?? 'product') === 'gift_card') {
            $giftCardStatus = $paymentStatus === 'paid' ? 'active' : 'pending';
            try {
                $giftCardOrder = girffonGiftCardCreate($pdo, [
                    'initial_amount' => $item['gift_card_amount'] ?? $item['price'] ?? 0,
                    'delivery_type' => $item['delivery_type'] ?? 'digital',
                    'buyer_name' => $item['buyer_name'] ?? $customerName,
                    'buyer_email' => $item['buyer_email'] ?? $customerEmail,
                    'recipient_name' => $item['recipient_name'] ?? '',
                    'recipient_email' => $item['recipient_email'] ?? '',
                    'gift_message' => $item['gift_message'] ?? '',
                    'expires_at' => '',
                    'status' => $giftCardStatus,
                    'order_id' => $orderId,
                    'source_line_key' => $item['line_key'] ?? '',
                ]);
            } catch (Throwable $throwable) {
                girffonCheckoutLog('Gift card creation failed', [
                    'order_id' => $orderId,
                    'sku' => $item['sku'] ?? '',
                    'line_key' => $item['line_key'] ?? '',
                    'item_type' => $item['item_type'] ?? '',
                    'message' => $throwable->getMessage(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                ]);
                throw $throwable;
            }

            girffonCheckoutLog('Gift card database insert completed', [
                'order_id' => $orderId,
                'gift_card_id' => $giftCardOrder['id'] ?? 0,
                'gift_code' => $giftCardOrder['gift_code'] ?? '',
            ]);

            girffonCheckoutLog('Gift card record created from finalized order', [
                'order_id' => $orderId,
                'line_key' => $item['line_key'] ?? '',
                'gift_code' => $giftCardOrder['gift_code'] ?? '',
                'initial_amount' => $giftCardOrder['initial_amount'] ?? 0,
                'remaining_balance' => $giftCardOrder['remaining_balance'] ?? 0,
                'status' => $giftCardOrder['status'] ?? '',
                'payment_status' => $paymentStatus,
            ]);

            if (($giftCardOrder['delivery_type'] ?? '') === 'digital' && ($giftCardOrder['status'] ?? '') === 'active') {
                $giftCardOrdersToSend[] = $giftCardOrder;
            }
        }
    }

    $invoiceNumber = girffonNextSequenceNumber($pdo, 'invoices', 'invoice_number', 'INV', $year);
    $insertInvoice = $pdo->prepare(
        'INSERT INTO invoices (order_id, user_id, invoice_number, subtotal, tax, shipping, gift_card_amount, total, status, invoice_status, invoice_total)
         VALUES (:order_id, :user_id, :invoice_number, :subtotal, :tax, :shipping, :gift_card_amount, :total, :status, :invoice_status, :invoice_total)'
    );
    $insertInvoice->execute([
        ':order_id' => $orderId,
        ':user_id' => $user ? (int) $user['id'] : null,
        ':invoice_number' => $invoiceNumber,
        ':subtotal' => $subtotal,
        ':tax' => $taxAmount,
        ':shipping' => $shippingAmount,
        ':gift_card_amount' => $giftCardAppliedAmount,
        ':total' => $amountDue,
        ':status' => $invoiceStatus,
        ':invoice_status' => $invoiceStatus,
        ':invoice_total' => $amountDue,
    ]);

    $invoiceId = (int) $pdo->lastInsertId();
    girffonCartSaveSessionItems([]);
    $pdo->commit();

    $shippingAddressParts = array_filter([
        $customerAddress,
        $customerCity,
        $customerPostcode,
        $customerCountry,
    ], static function ($value): bool {
        return trim((string) $value) !== '';
    });

    $emailSent = false;
    $emailError = '';
    try {
        $emailSent = girffonSendOrderConfirmationEmail([
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'order_number' => $orderNumber,
            'invoice_number' => $invoiceNumber,
            'invoice_id' => $invoiceId,
            'tracking_code' => $trackingCode,
            'items' => $normalizedItems,
            'total' => $amountDue,
            'shipping_address' => implode(', ', $shippingAddressParts),
        ]);
    } catch (Throwable $throwable) {
        $emailSent = false;
        $emailError = $throwable->getMessage();
        error_log('[GirffoN Checkout Mail] ' . $emailError);
    }

    foreach ($giftCardOrdersToSend as $giftCardOrder) {
        try {
            girffonSendGiftCardEmail($giftCardOrder);
        } catch (Throwable $throwable) {
            error_log('[GirffoN Gift Card Mail] ' . $throwable->getMessage());
        }
    }

    if (($analyticsPayload['visitor_id'] ?? '') !== '' && ($analyticsPayload['session_id'] ?? '') !== '') {
        $completedOrderTracked = girffonAdminTrackWebsiteVisitor($pdo, [
            'event_type' => 'completed_order',
            'visitor_id' => (string) $analyticsPayload['visitor_id'],
            'session_id' => (string) $analyticsPayload['session_id'],
            'page_path' => '/GirffoN/CartTest.html',
            'page_title' => 'Checkout Success',
            'referrer' => (string) ($analyticsPayload['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? '')),
            'traffic_source' => (string) ($analyticsPayload['traffic_source'] ?? ''),
            'meta' => [
                'order_number' => $orderNumber,
                'payment_status' => $paymentStatus,
                'item_count' => count($normalizedItems),
                'order_total' => $amountDue,
            ],
        ]);

        girffonAdminAnalyticsDebugLog([
            'timestamp' => gmdate('c'),
            'tracker_version' => 'server',
            'event_type' => 'completed_order',
            'page_url' => '/GirffoN/CartTest.html?checkout=success',
            'page_path' => '/GirffoN/CartTest.html',
            'action_result' => $completedOrderTracked ? 'server_order_tracking_completed' : 'server_order_tracking_failed',
            'tracked' => $completedOrderTracked,
            'database_insert_result' => (string) ((girffonAdminAnalyticsGetLastTrackDebug()['database_insert_result'] ?? ($completedOrderTracked ? 'recorded' : 'not_recorded'))),
            'order_number' => $orderNumber,
            'payment_status' => $paymentStatus,
        ]);
    }

    $response = [
        'ok' => true,
        'message' => 'Order placed successfully.',
        'redirectUrl' => '/GirffoN/CartTest.html?checkout=success',
        'order' => [
            'id' => $orderId,
            'order_number' => $orderNumber,
            'user_id' => $user ? (int) $user['id'] : null,
            'customer_name' => $customerName,
            'email' => $customerEmail,
            'phone' => $customerPhone,
            'address' => $customerAddress,
            'city' => $customerCity,
            'country' => $customerCountry,
            'postcode' => $customerPostcode,
            'subtotal' => $subtotal,
            'shipping' => $shippingAmount,
            'gift_card_amount' => $giftCardAppliedAmount,
            'total' => $amountDue,
            'gross_total' => $grossTotal,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
            'created_at' => date('Y-m-d H:i:s'),
            'tracking_code' => $trackingCode,
            'gift_card_code' => $giftCardCode,
        ],
        'invoice' => [
            'id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'order_id' => $orderId,
            'user_id' => $user ? (int) $user['id'] : null,
            'subtotal' => $subtotal,
            'tax' => $taxAmount,
            'shipping' => $shippingAmount,
            'gift_card_amount' => $giftCardAppliedAmount,
            'total' => $amountDue,
            'status' => $invoiceStatus,
            'created_at' => date('Y-m-d H:i:s'),
        ],
        'gift_card' => [
            'applied_amount' => $giftCardAppliedAmount,
            'code' => $giftCardCode,
            'amount_due' => $amountDue,
        ],
        'email_confirmation' => [
            'sent' => $emailSent,
            'error' => $emailError,
        ],
    ];

    $_SESSION['girffon_last_checkout'] = [
        'fingerprint' => $checkoutFingerprint,
        'created_at' => time(),
        'response' => $response,
    ];

    girffonCheckoutJson(200, $response);
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    girffonCheckoutLog('Checkout exception', [
        'message' => $throwable->getMessage(),
        'file' => $throwable->getFile(),
        'line' => $throwable->getLine(),
    ]);

    girffonCheckoutJson(500, [
        'ok' => false,
        'message' => 'Unable to create the order right now.',
    ]);
}
