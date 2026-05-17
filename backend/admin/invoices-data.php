<?php
require_once __DIR__ . "/../config/database.php";

function girffonAdminFetchInvoices(PDO $pdo, int $limit = 0, array $filters = []): array
{
    try {
        $params = [];
    $sql = "SELECT invoices.id, invoices.order_id, invoices.user_id, invoices.invoice_number, invoices.subtotal, invoices.tax, invoices.shipping, invoices.total, invoices.status, invoices.invoice_status, invoices.invoice_total, invoices.pdf_path, invoices.created_at, orders.order_number, orders.customer_name, orders.customer_email
                FROM invoices
                LEFT JOIN orders ON orders.id = invoices.order_id";
        $where = [];
        $userEmail = trim((string) ($filters['customer_email'] ?? ''));
        if ($userEmail !== '') {
            $where[] = 'orders.customer_email = :customer_email';
            $params[':customer_email'] = $userEmail;
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(invoices.invoice_number LIKE :search OR orders.order_number LIKE :search OR orders.customer_name LIKE :search OR orders.customer_email LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = '(invoices.status = :status OR invoices.invoice_status = :status)';
            $params[':status'] = $status;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY invoices.created_at DESC";
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

function girffonAdminCountInvoices(PDO $pdo): int
{
    try {
        $statement = $pdo->query("SELECT COUNT(*) FROM invoices");
        return $statement ? (int) $statement->fetchColumn() : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminFetchInvoiceDetail(PDO $pdo, int $invoiceId)
{
    try {
        $invoiceStatement = $pdo->prepare(
            "SELECT invoices.id, invoices.order_id, invoices.user_id, invoices.invoice_number, invoices.subtotal, invoices.tax, invoices.shipping, invoices.total, invoices.status, invoices.invoice_status, invoices.invoice_total, invoices.pdf_path, invoices.created_at,
                    orders.order_number, orders.customer_name, orders.customer_email, orders.payment_status AS order_payment_status,
                    orders.order_status, orders.tracking_code
             FROM invoices
             LEFT JOIN orders ON orders.id = invoices.order_id
             WHERE invoices.id = :invoice_id
             LIMIT 1"
        );
        $invoiceStatement->execute([":invoice_id" => $invoiceId]);
        $invoice = $invoiceStatement->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            return null;
        }

        $itemStatement = $pdo->prepare(
              "SELECT product_id, name, product_name, sku, size, color, quantity, price, line_total, image
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC"
        );
        $itemStatement->execute([":order_id" => (int) $invoice["order_id"]]);

        $invoice["items"] = $itemStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $invoice;
    } catch (PDOException $exception) {
        return null;
    }
}
