<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/order-updates-data.php";

function girffonAdminFetchOrders(PDO $pdo, int $limit = 0, array $filters = []): array
{
    try {
        $orderColumns = girffonAdminEnsureOrderUpdateColumns($pdo);
        $courierExpression = isset($orderColumns['courier_name']) ? "COALESCE(orders.courier_name, '')" : "''";
        $estimatedExpression = isset($orderColumns['estimated_delivery_date']) ? "orders.estimated_delivery_date" : "NULL";
        $adminNoteExpression = isset($orderColumns['admin_note']) ? "COALESCE(orders.admin_note, '')" : "''";
        $params = [];
        $sql = "SELECT orders.id, orders.user_id, orders.order_number, orders.customer_name, orders.customer_email, orders.phone, orders.address, orders.city, orders.country, orders.postcode, orders.subtotal, orders.shipping, orders.total, orders.payment_method, orders.payment_status, orders.order_status, orders.tracking_code,
                       {$courierExpression} AS courier_name,
                       {$estimatedExpression} AS estimated_delivery_date,
                       {$adminNoteExpression} AS admin_note,
                       orders.created_at,
                       (SELECT COUNT(*) FROM order_items WHERE order_items.order_id = orders.id) AS item_count,
                       (SELECT order_items.image
                        FROM order_items
                        WHERE order_items.order_id = orders.id
                        ORDER BY order_items.id ASC
                        LIMIT 1) AS item_image,
                       (SELECT invoices.id FROM invoices WHERE invoices.order_id = orders.id ORDER BY invoices.id DESC LIMIT 1) AS invoice_id,
                       (SELECT invoices.invoice_number FROM invoices WHERE invoices.order_id = orders.id ORDER BY invoices.id DESC LIMIT 1) AS invoice_number
                FROM orders";
        $userEmail = trim((string) ($filters['customer_email'] ?? ''));
        if ($userEmail !== '') {
            $sql .= " WHERE orders.customer_email = :customer_email";
            $params[':customer_email'] = $userEmail;
        }
        $sql .= " ORDER BY orders.created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminCountOrders(PDO $pdo): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM orders");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminFetchTodayOrders(PDO $pdo, int $limit = 0): array
{
    try {
        $orderColumns = girffonAdminEnsureOrderUpdateColumns($pdo);
        $courierExpression = isset($orderColumns['courier_name']) ? "COALESCE(orders.courier_name, '')" : "''";
        $estimatedExpression = isset($orderColumns['estimated_delivery_date']) ? "orders.estimated_delivery_date" : "NULL";
        $adminNoteExpression = isset($orderColumns['admin_note']) ? "COALESCE(orders.admin_note, '')" : "''";
        $sql = "SELECT orders.id, orders.user_id, orders.order_number, orders.customer_name, orders.customer_email, orders.phone, orders.address, orders.city, orders.country, orders.postcode, orders.subtotal, orders.shipping, orders.total, orders.payment_method, orders.payment_status, orders.order_status, orders.tracking_code,
                       {$courierExpression} AS courier_name,
                       {$estimatedExpression} AS estimated_delivery_date,
                       {$adminNoteExpression} AS admin_note,
                       orders.created_at,
                       (SELECT COUNT(*) FROM order_items WHERE order_items.order_id = orders.id) AS item_count,
                       (SELECT order_items.image
                        FROM order_items
                        WHERE order_items.order_id = orders.id
                        ORDER BY order_items.id ASC
                        LIMIT 1) AS item_image
                FROM orders
                WHERE DATE(orders.created_at) = CURDATE()
                ORDER BY orders.created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $statement = $pdo->query($sql);
        return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminFetchOrderItems(PDO $pdo, int $orderId): array
{
    try {
        $statement = $pdo->prepare(
            "SELECT id, product_id, name, product_name, sku, size, color, quantity, price, line_total, image
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC"
        );
        $statement->execute([':order_id' => $orderId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        return [];
    }
}
