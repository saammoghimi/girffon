<?php
require_once __DIR__ . '/../config/database.php';

function girffonOrderUpdatesTableColumns(PDO $pdo, string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $cache[$table] = [];

    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $column) {
            $name = (string) ($column['Field'] ?? '');
            if ($name !== '') {
                $cache[$table][$name] = true;
            }
        }
    } catch (PDOException $exception) {
        $cache[$table] = [];
    }

    return $cache[$table];
}

function girffonOrderUpdatesTableExists(PDO $pdo, string $table): bool
{
    return girffonOrderUpdatesTableColumns($pdo, $table) !== [];
}

function girffonOrderUpdatesPreferenceTable(PDO $pdo): string
{
    if (girffonOrderUpdatesTableExists($pdo, 'user_preferences')) {
        return 'user_preferences';
    }

    if (girffonOrderUpdatesTableExists($pdo, 'customer_notification_preferences')) {
        return 'customer_notification_preferences';
    }

    return '';
}

function girffonAdminEnsureOrderUpdateColumns(PDO $pdo): array
{
    $orderColumns = girffonOrderUpdatesTableColumns($pdo, 'orders');
    if ($orderColumns === []) {
        return [];
    }

    try {
        $pdo->exec("ALTER TABLE orders MODIFY COLUMN order_status ENUM('new','pending','paid','processing','preparing','printed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    } catch (PDOException $exception) {
    }

    $migrations = [
        'courier_name' => 'ALTER TABLE orders ADD COLUMN courier_name VARCHAR(120) NULL',
        'estimated_delivery_date' => 'ALTER TABLE orders ADD COLUMN estimated_delivery_date DATE NULL',
        'admin_note' => 'ALTER TABLE orders ADD COLUMN admin_note TEXT NULL',
    ];

    foreach ($migrations as $column => $sql) {
        if (isset($orderColumns[$column])) {
            continue;
        }

        try {
            $pdo->exec($sql);
            $orderColumns[$column] = true;
        } catch (PDOException $exception) {
        }
    }

    return $orderColumns;
}

function girffonAdminEnsureOrderUpdateLogsTable(PDO $pdo): bool
{
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS order_update_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                order_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NULL,
                customer_email VARCHAR(190) NOT NULL,
                old_status VARCHAR(40) NOT NULL DEFAULT '',
                new_status VARCHAR(40) NOT NULL DEFAULT '',
                payment_status VARCHAR(40) NOT NULL DEFAULT '',
                tracking_number VARCHAR(120) NOT NULL DEFAULT '',
                courier VARCHAR(120) NOT NULL DEFAULT '',
                estimated_delivery_date DATE NULL,
                admin_note TEXT NULL,
                email_status VARCHAR(30) NOT NULL DEFAULT '',
                transport VARCHAR(40) NOT NULL DEFAULT '',
                error_message TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_order_update_order (order_id),
                KEY idx_order_update_email (customer_email),
                KEY idx_order_update_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminOrderUpdateStatusOptions(): array
{
    return ['new', 'pending', 'paid', 'processing', 'preparing', 'printed', 'shipped', 'delivered', 'cancelled'];
}

function girffonAdminOrderPaymentStatusOptions(): array
{
    return ['pending', 'paid', 'failed', 'refunded'];
}

function girffonAdminNormalizeOrderStatus(string $value): string
{
    $normalized = strtolower(trim($value));
    return in_array($normalized, girffonAdminOrderUpdateStatusOptions(), true) ? $normalized : 'pending';
}

function girffonAdminNormalizePaymentStatus(string $value): string
{
    $normalized = strtolower(trim($value));
    return in_array($normalized, girffonAdminOrderPaymentStatusOptions(), true) ? $normalized : 'pending';
}

function girffonAdminNormalizeEstimatedDeliveryDate(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d', $timestamp);
}

function girffonOrderUpdateStatusLabel(string $value): string
{
    $normalized = strtolower(trim($value));
    $labels = [
        'new' => 'Order Confirmed',
        'pending' => 'Order Confirmed',
        'paid' => 'Payment Received',
        'processing' => 'Preparing',
        'preparing' => 'Preparing',
        'printed' => 'Printed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

    return $labels[$normalized] ?? ucwords(str_replace('_', ' ', $normalized));
}

function girffonOrderUpdateTimeline(string $orderStatus, string $paymentStatus): array
{
    $orderStatus = strtolower(trim($orderStatus));
    $paymentStatus = strtolower(trim($paymentStatus));

    $timeline = [
        ['key' => 'confirmed', 'label' => 'Order Confirmed', 'completed' => true],
        ['key' => 'paid', 'label' => 'Payment Received', 'completed' => in_array($paymentStatus, ['paid', 'refunded'], true) || in_array($orderStatus, ['paid', 'processing', 'preparing', 'printed', 'shipped', 'delivered'], true)],
        ['key' => 'preparing', 'label' => 'Preparing', 'completed' => in_array($orderStatus, ['processing', 'preparing', 'printed', 'shipped', 'delivered'], true)],
        ['key' => 'printed', 'label' => 'Printed', 'completed' => in_array($orderStatus, ['printed', 'shipped', 'delivered'], true)],
        ['key' => 'shipped', 'label' => 'Shipped', 'completed' => in_array($orderStatus, ['shipped', 'delivered'], true)],
        ['key' => 'delivered', 'label' => 'Delivered', 'completed' => $orderStatus === 'delivered'],
    ];

    $currentKey = 'confirmed';
    foreach ($timeline as $step) {
        if (!$step['completed']) {
            $currentKey = (string) $step['key'];
            break;
        }
        $currentKey = (string) $step['key'];
    }

    foreach ($timeline as &$step) {
        $step['current'] = $step['key'] === $currentKey;
    }
    unset($step);

    if ($orderStatus === 'cancelled') {
        foreach ($timeline as &$step) {
            $step['current'] = false;
        }
        unset($step);

        $timeline[] = [
            'key' => 'cancelled',
            'label' => 'Cancelled',
            'completed' => true,
            'current' => true,
        ];
    }

    return $timeline;
}

function girffonAdminFetchOrderForUpdate(PDO $pdo, int $orderId): ?array
{
    $orderColumns = girffonAdminEnsureOrderUpdateColumns($pdo);
    if ($orderColumns === [] || !isset($orderColumns['id'])) {
        return null;
    }

    $courierExpression = isset($orderColumns['courier_name']) ? 'COALESCE(orders.courier_name, "")' : '""';
    $estimatedExpression = isset($orderColumns['estimated_delivery_date']) ? 'orders.estimated_delivery_date' : 'NULL';
    $adminNoteExpression = isset($orderColumns['admin_note']) ? 'COALESCE(orders.admin_note, "")' : '""';

    try {
        $statement = $pdo->prepare(
            'SELECT orders.id, orders.user_id, orders.order_number, orders.customer_name, orders.customer_email, orders.total,
                    orders.payment_status, orders.order_status, COALESCE(orders.tracking_code, "") AS tracking_code,
                    ' . $courierExpression . ' AS courier_name,
                    ' . $estimatedExpression . ' AS estimated_delivery_date,
                    ' . $adminNoteExpression . ' AS admin_note,
                    orders.created_at,
                    invoices.id AS invoice_id, invoices.invoice_number
             FROM orders
             LEFT JOIN invoices ON invoices.order_id = orders.id
             WHERE orders.id = :order_id
             ORDER BY invoices.id DESC
             LIMIT 1'
        );
        $statement->execute([':order_id' => $orderId]);
        $order = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($order) ? $order : null;
    } catch (PDOException $exception) {
        return null;
    }
}

function girffonAdminOrderUpdateEmailEnabled(PDO $pdo, array $order): bool
{
    $userId = (int) ($order['user_id'] ?? 0);
    if ($userId <= 0) {
        return true;
    }

    $preferenceTable = girffonOrderUpdatesPreferenceTable($pdo);
    if ($preferenceTable === '') {
        return true;
    }

    $preferenceColumns = girffonOrderUpdatesTableColumns($pdo, $preferenceTable);
    if (!isset($preferenceColumns['user_id'], $preferenceColumns['order_updates'])) {
        return true;
    }

    try {
        $statement = $pdo->prepare('SELECT COALESCE(order_updates, 1) FROM ' . $preferenceTable . ' WHERE user_id = :user_id LIMIT 1');
        $statement->execute([':user_id' => $userId]);
        $value = $statement->fetchColumn();
        return $value === false ? true : (int) $value === 1;
    } catch (PDOException $exception) {
        return true;
    }
}

function girffonAdminUpdateOrderRecord(PDO $pdo, int $orderId, array $payload): bool
{
    $orderColumns = girffonAdminEnsureOrderUpdateColumns($pdo);
    if ($orderColumns === [] || !isset($orderColumns['id'])) {
        return false;
    }

    $sets = [
        'order_status = :order_status',
        'payment_status = :payment_status',
        'tracking_code = :tracking_code',
    ];
    $params = [
        ':order_id' => $orderId,
        ':order_status' => girffonAdminNormalizeOrderStatus((string) ($payload['order_status'] ?? 'pending')),
        ':payment_status' => girffonAdminNormalizePaymentStatus((string) ($payload['payment_status'] ?? 'pending')),
        ':tracking_code' => trim((string) ($payload['tracking_code'] ?? '')),
    ];

    if (isset($orderColumns['courier_name'])) {
        $sets[] = 'courier_name = :courier_name';
        $params[':courier_name'] = trim((string) ($payload['courier_name'] ?? ''));
    }

    if (isset($orderColumns['estimated_delivery_date'])) {
        $sets[] = 'estimated_delivery_date = :estimated_delivery_date';
        $params[':estimated_delivery_date'] = girffonAdminNormalizeEstimatedDeliveryDate((string) ($payload['estimated_delivery_date'] ?? ''));
    }

    if (isset($orderColumns['admin_note'])) {
        $sets[] = 'admin_note = :admin_note';
        $params[':admin_note'] = trim((string) ($payload['admin_note'] ?? ''));
    }

    try {
        $statement = $pdo->prepare('UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id = :order_id LIMIT 1');
        return $statement->execute($params);
    } catch (PDOException $exception) {
        return false;
    }
}

function girffonAdminLogOrderUpdate(PDO $pdo, array $payload): void
{
    if (!girffonAdminEnsureOrderUpdateLogsTable($pdo)) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO order_update_logs (
                order_id,
                user_id,
                customer_email,
                old_status,
                new_status,
                payment_status,
                tracking_number,
                courier,
                estimated_delivery_date,
                admin_note,
                email_status,
                transport,
                error_message
             ) VALUES (
                :order_id,
                :user_id,
                :customer_email,
                :old_status,
                :new_status,
                :payment_status,
                :tracking_number,
                :courier,
                :estimated_delivery_date,
                :admin_note,
                :email_status,
                :transport,
                :error_message
             )'
        );
        $statement->execute([
            ':order_id' => (int) ($payload['order_id'] ?? 0),
            ':user_id' => !empty($payload['user_id']) ? (int) $payload['user_id'] : null,
            ':customer_email' => strtolower(trim((string) ($payload['customer_email'] ?? ''))),
            ':old_status' => (string) ($payload['old_status'] ?? ''),
            ':new_status' => (string) ($payload['new_status'] ?? ''),
            ':payment_status' => (string) ($payload['payment_status'] ?? ''),
            ':tracking_number' => trim((string) ($payload['tracking_number'] ?? '')),
            ':courier' => trim((string) ($payload['courier'] ?? '')),
            ':estimated_delivery_date' => girffonAdminNormalizeEstimatedDeliveryDate((string) ($payload['estimated_delivery_date'] ?? '')),
            ':admin_note' => ($payload['admin_note'] ?? '') !== '' ? (string) $payload['admin_note'] : null,
            ':email_status' => (string) ($payload['email_status'] ?? ''),
            ':transport' => (string) ($payload['transport'] ?? ''),
            ':error_message' => ($payload['error_message'] ?? '') !== '' ? (string) $payload['error_message'] : null,
        ]);
    } catch (PDOException $exception) {
    }
}

function girffonAdminFetchRecentOrderUpdateLogs(PDO $pdo, int $limit = 30): array
{
    if (!girffonAdminEnsureOrderUpdateLogsTable($pdo)) {
        return [];
    }

    try {
        $sql = 'SELECT order_id, user_id, customer_email, old_status, new_status, payment_status, tracking_number, courier, estimated_delivery_date, admin_note, email_status, transport, error_message, created_at
                FROM order_update_logs
                ORDER BY created_at DESC, id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $statement = $pdo->query($sql);
        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminOrderUpdatesDebugSummary(PDO $pdo): array
{
    $orderColumns = girffonAdminEnsureOrderUpdateColumns($pdo);
    $summary = [
        'total_orders' => 0,
        'orders_with_email' => 0,
        'orders_with_tracking' => 0,
        'order_update_email_enabled_count' => 0,
    ];

    if ($orderColumns === []) {
        return $summary;
    }

    try {
        $statement = $pdo->query('SELECT COUNT(*) FROM orders');
        $summary['total_orders'] = (int) ($statement ? $statement->fetchColumn() : 0);
    } catch (PDOException $exception) {
    }

    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(TRIM(COALESCE(customer_email, ''))) <> ''");
        $summary['orders_with_email'] = (int) ($statement ? $statement->fetchColumn() : 0);
    } catch (PDOException $exception) {
    }

    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(TRIM(COALESCE(tracking_code, ''))) <> ''");
        $summary['orders_with_tracking'] = (int) ($statement ? $statement->fetchColumn() : 0);
    } catch (PDOException $exception) {
    }

    $preferenceTable = girffonOrderUpdatesPreferenceTable($pdo);
    $preferenceColumns = $preferenceTable !== '' ? girffonOrderUpdatesTableColumns($pdo, $preferenceTable) : [];
    if ($preferenceTable !== '' && isset($preferenceColumns['order_updates'])) {
        try {
            $statement = $pdo->query('SELECT COUNT(*) FROM ' . $preferenceTable . ' WHERE COALESCE(order_updates, 0) = 1');
            $summary['order_update_email_enabled_count'] = (int) ($statement ? $statement->fetchColumn() : 0);
        } catch (PDOException $exception) {
        }
    }

    return $summary;
}