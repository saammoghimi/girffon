<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/../config/database.php";

function girffonAdminRedirectInvoice(string $type, string $message): void
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = str_replace('\\', '/', dirname(dirname($scriptName)));
    if ($basePath === '/' || $basePath === '.') {
        $basePath = '';
    }

    header("Location: " . rtrim($basePath, '/') . "/admin-invoices.php?" . $type . "=" . rawurlencode($message));
    exit;
}

function girffonAdminRedirectInvoiceWithRollback(PDO $pdo, string $type, string $message): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    girffonAdminRedirectInvoice($type, $message);
}

function girffonAdminColumnExists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
    $statement->execute([':column' => $column]);
    return (bool) $statement->fetch(PDO::FETCH_ASSOC);
}

function girffonAdminEnsureInvoiceSchema(PDO $pdo): void
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
        if (!girffonAdminColumnExists($pdo, 'orders', $column)) {
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
        if (!girffonAdminColumnExists($pdo, 'invoices', $column)) {
            $pdo->exec($sql);
        }
    }

    $checked = true;
}

function girffonAdminNextSequenceNumber(PDO $pdo, string $table, string $column, string $prefix, int $year): string
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

function girffonAdminMapInvoiceStatus(string $value): string
{
    $normalized = strtolower(trim($value));

    return match ($normalized) {
        "paid" => "paid",
        "cancelled" => "cancelled",
        default => "pending",
    };
}

function girffonAdminMapOrderPaymentStatus(string $invoiceStatus): string
{
    return match ($invoiceStatus) {
        'paid' => 'paid',
        'cancelled' => 'failed',
        default => 'pending',
    };
}

function girffonAdminMapOrderStatus(string $invoiceStatus): string
{
    return $invoiceStatus === 'cancelled' ? 'cancelled' : 'new';
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    girffonAdminRedirectInvoice("error", "Invalid request method.");
}

$invoiceNumber = trim((string) ($_POST["invoiceNumber"] ?? ""));
$orderNumber = trim((string) ($_POST["orderNumber"] ?? ""));
$customerName = trim((string) ($_POST["customerName"] ?? ""));
$customerEmail = strtolower(trim((string) ($_POST["customerEmail"] ?? "")));
$invoiceDate = trim((string) ($_POST["date"] ?? ""));
$invoiceTotal = (float) ($_POST["amount"] ?? 0);
$invoiceStatus = girffonAdminMapInvoiceStatus((string) ($_POST["paymentStatus"] ?? ""));
$createdAt = date("Y-m-d H:i:s");

if ($invoiceDate !== '') {
    $timestamp = strtotime($invoiceDate);
    if ($timestamp === false) {
        girffonAdminRedirectInvoice("error", "Please enter a valid invoice date.");
    }
    $createdAt = date('Y-m-d H:i:s', $timestamp);
}

if ($customerName === "" || $invoiceTotal <= 0) {
    girffonAdminRedirectInvoice("error", "Customer name and amount are required.");
}

try {
    girffonAdminEnsureInvoiceSchema($pdo);
    $year = (int) date('Y', strtotime($createdAt));

    $pdo->beginTransaction();

    if ($invoiceNumber === '') {
        $invoiceNumber = girffonAdminNextSequenceNumber($pdo, 'invoices', 'invoice_number', 'INV', $year);
    }

    if ($orderNumber === '') {
        $orderNumber = girffonAdminNextSequenceNumber($pdo, 'orders', 'order_number', 'GF', $year);
    }

    $existingOrderStatement = $pdo->prepare(
        "SELECT id
         FROM orders
         WHERE order_number = :order_number
         LIMIT 1"
    );
    $existingOrderStatement->execute([
        ':order_number' => $orderNumber,
    ]);
    $existingOrderId = (int) ($existingOrderStatement->fetchColumn() ?: 0);

    if ($existingOrderId > 0) {
        girffonAdminRedirectInvoiceWithRollback($pdo, "error", "That order number already exists. Leave it empty to auto-generate a new one.");
    }

    $insertOrder = $pdo->prepare(
        'INSERT INTO orders (user_id, order_number, customer_name, customer_email, subtotal, shipping, total, payment_method, payment_status, order_status, tracking_code, created_at)
         VALUES (:user_id, :order_number, :customer_name, :customer_email, :subtotal, :shipping, :total, :payment_method, :payment_status, :order_status, :tracking_code, :created_at)'
    );
    $insertOrder->execute([
        ':user_id' => null,
        ':order_number' => $orderNumber,
        ':customer_name' => $customerName,
        ':customer_email' => $customerEmail !== '' ? $customerEmail : null,
        ':subtotal' => $invoiceTotal,
        ':shipping' => 0,
        ':total' => $invoiceTotal,
        ':payment_method' => 'manual_invoice',
        ':payment_status' => girffonAdminMapOrderPaymentStatus($invoiceStatus),
        ':order_status' => girffonAdminMapOrderStatus($invoiceStatus),
        ':tracking_code' => null,
        ':created_at' => $createdAt,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $invoiceStatement = $pdo->prepare(
        "INSERT INTO invoices (order_id, user_id, subtotal, tax, shipping, total, status, invoice_number, invoice_status, invoice_total, pdf_path, created_at)
         VALUES (:order_id, :user_id, :subtotal, :tax, :shipping, :total, :status, :invoice_number, :invoice_status, :invoice_total, :pdf_path, :created_at)"
    );
    $invoiceStatement->execute([
        ":order_id" => (int) $orderId,
        ':user_id' => null,
        ':subtotal' => $invoiceTotal,
        ':tax' => 0,
        ':shipping' => 0,
        ':total' => $invoiceTotal,
        ':status' => $invoiceStatus,
        ":invoice_number" => $invoiceNumber,
        ":invoice_status" => $invoiceStatus,
        ":invoice_total" => $invoiceTotal,
        ":pdf_path" => null,
        ":created_at" => $createdAt,
    ]);

    $pdo->commit();

    girffonAdminRedirectInvoice("status", "Invoice saved successfully.");
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ((int) $exception->getCode() === 23000) {
        girffonAdminRedirectInvoice("error", "That invoice number already exists.");
    }

    girffonAdminRedirectInvoice("error", "Unable to save the invoice right now.");
}
