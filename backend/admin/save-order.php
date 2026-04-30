<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/../config/database.php";

function girffonAdminRedirectOrder(string $type, string $message): void
{
    header("Location: /GirffoN/admin-orders.php?" . $type . "=" . rawurlencode($message));
    exit;
}

function girffonAdminMapOrderStatus(string $value): string
{
    $normalized = strtolower(trim($value));

    return match ($normalized) {
        "processing" => "processing",
        "shipped" => "shipped",
        "delivered" => "delivered",
        "cancelled" => "cancelled",
        default => "new",
    };
}

function girffonAdminMapPaymentStatus(string $value): string
{
    $normalized = strtolower(trim($value));

    return match ($normalized) {
        "paid" => "paid",
        "failed" => "failed",
        "refunded" => "refunded",
        default => "pending",
    };
}

function girffonAdminStoreOrderImage(array $file): ?string
{
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        girffonAdminRedirectOrder("error", "Image upload failed.");
    }

    $tmpName = (string) ($file["tmp_name"] ?? "");
    if ($tmpName === "" || !is_uploaded_file($tmpName)) {
        girffonAdminRedirectOrder("error", "Invalid uploaded image.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($tmpName);
    $extensionMap = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
        "image/gif" => "gif",
    ];

    if (!isset($extensionMap[$mimeType])) {
        girffonAdminRedirectOrder("error", "Please upload a JPG, PNG, WEBP, or GIF image.");
    }

    $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "orders";
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        girffonAdminRedirectOrder("error", "Unable to create the order uploads folder.");
    }

    $fileName = "order-" . date("Ymd-His") . "-" . bin2hex(random_bytes(4)) . "." . $extensionMap[$mimeType];
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        girffonAdminRedirectOrder("error", "Unable to save the uploaded image.");
    }

    return "/GirffoN/uploads/orders/" . $fileName;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    girffonAdminRedirectOrder("error", "Invalid request method.");
}

$orderNumber = trim((string) ($_POST["orderId"] ?? ""));
$customerName = trim((string) ($_POST["customerName"] ?? ""));
$customerEmail = trim((string) ($_POST["email"] ?? ""));
$productName = trim((string) ($_POST["product"] ?? ""));
$quantity = max(1, (int) ($_POST["quantity"] ?? 1));
$total = (float) ($_POST["totalPrice"] ?? 0);
$orderStatus = girffonAdminMapOrderStatus((string) ($_POST["status"] ?? ""));
$paymentStatus = girffonAdminMapPaymentStatus((string) ($_POST["paymentStatus"] ?? ""));
$orderDate = trim((string) ($_POST["date"] ?? ""));
$createdAt = $orderDate !== "" ? ($orderDate . " " . date("H:i:s")) : date("Y-m-d H:i:s");
$itemPrice = $quantity > 0 ? round($total / $quantity, 2) : $total;

if ($orderNumber === "" || $customerName === "" || $customerEmail === "" || $productName === "" || $total <= 0) {
    girffonAdminRedirectOrder("error", "Please complete all order fields.");
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    girffonAdminRedirectOrder("error", "Please enter a valid customer email.");
}

$storedImagePath = girffonAdminStoreOrderImage($_FILES["productImage"] ?? []);

try {
    $pdo->beginTransaction();

    $orderStatement = $pdo->prepare(
        "INSERT INTO orders (order_number, customer_name, customer_email, total, payment_status, order_status, tracking_code, created_at)
         VALUES (:order_number, :customer_name, :customer_email, :total, :payment_status, :order_status, :tracking_code, :created_at)"
    );
    $orderStatement->execute([
        ":order_number" => $orderNumber,
        ":customer_name" => $customerName,
        ":customer_email" => $customerEmail,
        ":total" => $total,
        ":payment_status" => $paymentStatus,
        ":order_status" => $orderStatus,
        ":tracking_code" => null,
        ":created_at" => $createdAt,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $itemStatement = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_name, sku, size, color, quantity, price, image)
         VALUES (:order_id, :product_name, :sku, :size, :color, :quantity, :price, :image)"
    );
    $itemStatement->execute([
        ":order_id" => $orderId,
        ":product_name" => $productName,
        ":sku" => null,
        ":size" => null,
        ":color" => null,
        ":quantity" => $quantity,
        ":price" => $itemPrice,
        ":image" => $storedImagePath,
    ]);

    $pdo->commit();
    girffonAdminRedirectOrder("status", "Order saved successfully.");
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($storedImagePath) {
        $storedImageFile = dirname(__DIR__, 2) . str_replace('/GirffoN', '', $storedImagePath);
        if (is_file($storedImageFile)) {
            @unlink($storedImageFile);
        }
    }

    if ((int) $exception->getCode() === 23000) {
        girffonAdminRedirectOrder("error", "That order number already exists.");
    }

    girffonAdminRedirectOrder("error", "Unable to save the order right now.");
}
