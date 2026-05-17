<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/order-updates-data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function girffonProfileOrdersJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonProfileOrderFormatCurrency($value): string
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function girffonProfileOrderFormatLabel($value): string
{
    return ucwords(str_replace('_', ' ', (string) $value));
}

function girffonProfileFetchOrders(PDO $pdo, int $userId, string $email = ''): array
{
    if ($userId <= 0 && $email === '') {
        return [];
    }

    try {
        girffonAdminEnsureOrderUpdateColumns($pdo);
        $params = [':user_id' => $userId];
        $where = 'orders.user_id = :user_id';
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail !== '') {
            $where .= ' OR (orders.user_id IS NULL AND LOWER(orders.customer_email) = :customer_email)';
            $params[':customer_email'] = $normalizedEmail;
        }

        $orderStatement = $pdo->prepare(
            'SELECT orders.id, orders.order_number, orders.total, orders.payment_status, orders.order_status, orders.tracking_code,
                    COALESCE(orders.courier_name, "") AS courier_name,
                    orders.estimated_delivery_date,
                    COALESCE(orders.admin_note, "") AS admin_note,
                    orders.created_at
             FROM orders
             WHERE ' . $where . '
             ORDER BY orders.created_at DESC, orders.id DESC'
        );
        $orderStatement->execute($params);
        $orders = $orderStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (!$orders) {
            return [];
        }

        $orderIds = array_values(array_filter(array_map(static function (array $order): int {
            return (int) ($order['id'] ?? 0);
        }, $orders)));

        $invoicesByOrder = [];
        if ($orderIds) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $invoiceStatement = $pdo->prepare(
                'SELECT id, order_id, invoice_number, COALESCE(total, invoice_total, 0) AS invoice_total_amount,
                        COALESCE(status, invoice_status, "pending") AS invoice_status_value, created_at
                 FROM invoices
                 WHERE order_id IN (' . $placeholders . ')
                 ORDER BY created_at DESC, id DESC'
            );
            $invoiceStatement->execute($orderIds);
            foreach ($invoiceStatement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $invoice) {
                $orderId = (int) ($invoice['order_id'] ?? 0);
                if (!isset($invoicesByOrder[$orderId])) {
                    $invoicesByOrder[$orderId] = [];
                }
                $invoicesByOrder[$orderId][] = [
                    'id' => (int) ($invoice['id'] ?? 0),
                    'invoice_number' => (string) ($invoice['invoice_number'] ?? ''),
                    'invoice_total' => girffonProfileOrderFormatCurrency($invoice['invoice_total_amount'] ?? 0),
                    'invoice_status' => girffonProfileOrderFormatLabel($invoice['invoice_status_value'] ?? 'pending'),
                    'created_at' => (string) ($invoice['created_at'] ?? ''),
                ];
            }
        }

        return array_map(static function (array $order) use ($invoicesByOrder): array {
            $orderId = (int) ($order['id'] ?? 0);
            return [
                'id' => $orderId,
                'order_number' => (string) ($order['order_number'] ?? ''),
                'total_amount' => girffonProfileOrderFormatCurrency($order['total'] ?? 0),
                'payment_status' => (string) ($order['payment_status'] ?? ''),
                'payment_status_label' => girffonOrderUpdateStatusLabel((string) ($order['payment_status'] ?? 'pending')),
                'order_status' => (string) ($order['order_status'] ?? ''),
                'order_status_label' => girffonOrderUpdateStatusLabel((string) ($order['order_status'] ?? 'new')),
                'tracking_code' => (string) ($order['tracking_code'] ?? ''),
                'courier_name' => (string) ($order['courier_name'] ?? ''),
                'estimated_delivery_date' => (string) ($order['estimated_delivery_date'] ?? ''),
                'admin_note' => (string) ($order['admin_note'] ?? ''),
                'created_at' => (string) ($order['created_at'] ?? ''),
                'timeline' => girffonOrderUpdateTimeline((string) ($order['order_status'] ?? ''), (string) ($order['payment_status'] ?? '')),
                'invoices' => $invoicesByOrder[$orderId] ?? [],
            ];
        }, $orders);
    } catch (PDOException $exception) {
        return [];
    }
}

if (basename(__FILE__) === basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['girffon_user_id'] ?? 0);
    if ($userId <= 0) {
        girffonProfileOrdersJson(401, [
            'ok' => false,
            'message' => 'Please sign in to view your orders.',
            'orders' => [],
        ]);
    }

    $userStatement = $pdo->prepare('SELECT email FROM users WHERE id = :id LIMIT 1');
    $userStatement->execute([':id' => $userId]);
    $user = $userStatement->fetch(PDO::FETCH_ASSOC) ?: [];

    girffonProfileOrdersJson(200, [
        'ok' => true,
        'orders' => girffonProfileFetchOrders($pdo, $userId, (string) ($user['email'] ?? '')),
    ]);
}