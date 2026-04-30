<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminFetchOrders(PDO $pdo, int $limit = 0, array $filters = []): array
{
    try {
        $params = [];
        $sql = "SELECT orders.id, orders.user_id, orders.order_number, orders.customer_name, orders.customer_email, orders.phone, orders.address, orders.city, orders.country, orders.postcode, orders.subtotal, orders.shipping, orders.total, orders.payment_method, orders.payment_status, orders.order_status, orders.tracking_code, orders.created_at,
                       (SELECT COUNT(*) FROM order_items WHERE order_items.order_id = orders.id) AS item_count,
                       (SELECT order_items.image
                        FROM order_items
                        WHERE order_items.order_id = orders.id
                        ORDER BY order_items.id ASC
                        LIMIT 1) AS item_image
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
        $sql = "SELECT orders.id, orders.user_id, orders.order_number, orders.customer_name, orders.customer_email, orders.phone, orders.address, orders.city, orders.country, orders.postcode, orders.subtotal, orders.shipping, orders.total, orders.payment_method, orders.payment_status, orders.order_status, orders.tracking_code, orders.created_at,
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
