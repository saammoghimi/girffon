<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/order-updates-data.php';

function girffonTrackOrderJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonTrackOrderLookup(PDO $pdo, string $orderNumber): ?array
{
    $normalizedOrderNumber = strtoupper(trim($orderNumber));
    if ($normalizedOrderNumber === '') {
        return null;
    }

    try {
        girffonAdminEnsureOrderUpdateColumns($pdo);
        $orderStatement = $pdo->prepare(
            'SELECT orders.id, orders.user_id, orders.order_number, orders.customer_name, orders.customer_email, orders.total,
                    orders.payment_status, orders.order_status, orders.tracking_code,
                    COALESCE(orders.courier_name, "") AS courier_name,
                    orders.estimated_delivery_date,
                    COALESCE(orders.admin_note, "") AS admin_note,
                    orders.created_at,
                    invoices.id AS invoice_id, invoices.invoice_number,
                    COALESCE(invoices.total, invoices.invoice_total, 0) AS invoice_total,
                    COALESCE(invoices.status, invoices.invoice_status, "pending") AS invoice_status
             FROM orders
             LEFT JOIN invoices ON invoices.order_id = orders.id
             WHERE orders.order_number = :order_number
             ORDER BY invoices.id DESC
             LIMIT 1'
        );
        $orderStatement->execute([':order_number' => $normalizedOrderNumber]);
        $order = $orderStatement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($order)) {
            return null;
        }

        $itemsStatement = $pdo->prepare(
            'SELECT product_id, name, product_name, sku, size, color, quantity, price, line_total, image
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC'
        );
        $itemsStatement->execute([':order_id' => (int) $order['id']]);
        $items = $itemsStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'order' => [
                'id' => (int) ($order['id'] ?? 0),
                'user_id' => isset($order['user_id']) ? (int) $order['user_id'] : null,
                'order_number' => (string) ($order['order_number'] ?? ''),
                'customer_name' => (string) ($order['customer_name'] ?? ''),
                'customer_email' => (string) ($order['customer_email'] ?? ''),
                'total' => (float) ($order['total'] ?? 0),
                'payment_status' => (string) ($order['payment_status'] ?? ''),
                'payment_status_label' => girffonOrderUpdateStatusLabel((string) ($order['payment_status'] ?? 'pending')),
                'order_status' => (string) ($order['order_status'] ?? ''),
                'order_status_label' => girffonOrderUpdateStatusLabel((string) ($order['order_status'] ?? 'new')),
                'tracking_code' => (string) ($order['tracking_code'] ?? ''),
                'courier_name' => (string) ($order['courier_name'] ?? ''),
                'estimated_delivery_date' => (string) ($order['estimated_delivery_date'] ?? ''),
                'admin_note' => (string) ($order['admin_note'] ?? ''),
                'created_at' => (string) ($order['created_at'] ?? ''),
                'invoice_id' => isset($order['invoice_id']) ? (int) $order['invoice_id'] : 0,
                'invoice_number' => (string) ($order['invoice_number'] ?? ''),
                'invoice_total' => (float) ($order['invoice_total'] ?? 0),
                'invoice_status' => (string) ($order['invoice_status'] ?? ''),
                'timeline' => girffonOrderUpdateTimeline((string) ($order['order_status'] ?? ''), (string) ($order['payment_status'] ?? '')),
            ],
            'items' => array_map(static function (array $item): array {
                return [
                    'product_id' => (string) ($item['product_id'] ?? ''),
                    'product_name' => (string) (($item['name'] ?? '') !== '' ? $item['name'] : ($item['product_name'] ?? 'GirffoN Product')),
                    'sku' => (string) ($item['sku'] ?? ''),
                    'size' => (string) ($item['size'] ?? ''),
                    'color' => (string) ($item['color'] ?? ''),
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'price' => (float) ($item['price'] ?? 0),
                    'line_total' => (float) ($item['line_total'] ?? 0),
                    'image' => (string) ($item['image'] ?? ''),
                ];
            }, $items),
        ];
    } catch (PDOException $exception) {
        return null;
    }
}

if (basename(__FILE__) === basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    $orderNumber = strtoupper(trim((string) ($_GET['order_number'] ?? '')));
    if ($orderNumber === '') {
        girffonTrackOrderJson(422, [
            'ok' => false,
            'message' => 'Order number is required.',
        ]);
    }

    $result = girffonTrackOrderLookup($pdo, $orderNumber);
    if (!$result) {
        girffonTrackOrderJson(404, [
            'ok' => false,
            'message' => 'Order not found.',
        ]);
    }

    girffonTrackOrderJson(200, [
        'ok' => true,
        'message' => 'Order details found.',
        'order' => $result['order'],
        'items' => $result['items'],
    ]);
}