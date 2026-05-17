<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/order-updates-data.php';
require_once __DIR__ . '/orders-data.php';
require_once __DIR__ . '/../utils/order-confirmation-mailer.php';

function girffonAdminOrderUpdateRedirect(string $type, string $message): void
{
    header('Location: /GirffoN/admin-orders.php?' . $type . '=' . rawurlencode($message));
    exit;
}

function girffonAdminOrderUpdateLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', trim($value)));
}

function girffonAdminOrderUpdateTrackUrl(array $mailConfig, string $orderNumber): string
{
    return rtrim(girffonOrderEmailBuildAppUrl($mailConfig), '/') . '/TrackOrder.php?order_number=' . rawurlencode($orderNumber);
}

function girffonAdminOrderUpdateInvoiceUrl(array $mailConfig, int $invoiceId): string
{
    if ($invoiceId <= 0) {
        return '';
    }

    return rtrim(girffonOrderEmailBuildAppUrl($mailConfig), '/') . '/invoice-pdf.php?id=' . rawurlencode((string) $invoiceId);
}

function girffonAdminOrderUpdateEmailContent(array $order, array $items, string $oldStatus, array $mailConfig): array
{
    $customerName = trim((string) ($order['customer_name'] ?? 'GirffoN Customer'));
    if ($customerName === '') {
        $customerName = 'GirffoN Customer';
    }

    $orderNumber = (string) ($order['order_number'] ?? '');
    $newStatus = (string) ($order['order_status'] ?? 'pending');
    $paymentStatus = (string) ($order['payment_status'] ?? 'pending');
    $trackingNumber = trim((string) ($order['tracking_code'] ?? ''));
    $courier = trim((string) ($order['courier_name'] ?? ''));
    $estimatedDeliveryDate = trim((string) ($order['estimated_delivery_date'] ?? ''));
    $adminNote = trim((string) ($order['admin_note'] ?? ''));
    $invoiceId = (int) ($order['invoice_id'] ?? 0);
    $invoiceNumber = trim((string) ($order['invoice_number'] ?? ''));
    $trackOrderUrl = girffonAdminOrderUpdateTrackUrl($mailConfig, $orderNumber);
    $invoiceUrl = girffonAdminOrderUpdateInvoiceUrl($mailConfig, $invoiceId);
    $statusLabel = girffonAdminOrderUpdateLabel($newStatus);
    $paymentLabel = girffonAdminOrderUpdateLabel($paymentStatus);

    $subjects = [
        'pending' => 'GirffoN Order Confirmed - ' . $orderNumber,
        'paid' => 'GirffoN Payment Received - ' . $orderNumber,
        'preparing' => 'GirffoN Preparing Your Order - ' . $orderNumber,
        'printed' => 'GirffoN Order Printed - ' . $orderNumber,
        'shipped' => 'GirffoN Order Shipped - ' . $orderNumber,
        'delivered' => 'GirffoN Order Delivered - ' . $orderNumber,
        'cancelled' => 'GirffoN Order Cancelled - ' . $orderNumber,
    ];
    $subject = $subjects[$newStatus] ?? ('GirffoN Order Update - ' . $orderNumber);

    $statusCopy = [
        'pending' => 'Your order has been confirmed and is now recorded in the GirffoN system.',
        'paid' => 'Your payment has been received successfully and your order is now approved for processing.',
        'preparing' => 'Your order is now being prepared carefully by the GirffoN team.',
        'printed' => 'Your order has completed the print stage and is moving toward shipment.',
        'shipped' => 'Your order has been shipped and is now on the way.',
        'delivered' => 'Your order has been marked as delivered. We hope you enjoy your GirffoN order.',
        'cancelled' => 'Your order has been cancelled. If you need help, please contact GirffoN support.',
    ];
    $intro = $statusCopy[$newStatus] ?? 'Your order has been updated in the GirffoN system.';

    $itemsHtml = girffonOrderEmailRenderItemsHtml($items);
    $itemsText = girffonOrderEmailRenderItemsText($items);
    $estimatedLabel = '-';
    if ($estimatedDeliveryDate !== '') {
        $estimatedTimestamp = strtotime($estimatedDeliveryDate);
        $estimatedLabel = $estimatedTimestamp ? date('d M Y', $estimatedTimestamp) : $estimatedDeliveryDate;
    }

    $trackingBlock = '';
    $trackingText = '';
    if ($trackingNumber !== '' || $courier !== '' || $estimatedLabel !== '-') {
        $trackingBlock .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Tracking Number</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($trackingNumber !== '' ? $trackingNumber : '-', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $trackingBlock .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Courier</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($courier !== '' ? $courier : '-', ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $trackingBlock .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Estimated Delivery</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($estimatedLabel, ENT_QUOTES, 'UTF-8') . '</td></tr>';

        $trackingText = "Tracking Number: " . ($trackingNumber !== '' ? $trackingNumber : '-') . "\n"
            . "Courier: " . ($courier !== '' ? $courier : '-') . "\n"
            . "Estimated Delivery: {$estimatedLabel}\n";
    }

    $adminNoteBlock = '';
    $adminNoteText = '';
    if ($adminNote !== '') {
        $adminNoteBlock = '<p style="margin:18px 0 0;padding:14px 16px;border:1px solid #e5ddd0;background:#faf6ee;color:#3d342b;font-size:14px;line-height:1.7;"><strong>Admin Note:</strong> ' . nl2br(htmlspecialchars($adminNote, ENT_QUOTES, 'UTF-8')) . '</p>';
        $adminNoteText = "Admin Note: {$adminNote}\n";
    }

    $invoiceButton = '';
    $invoiceLinksText = '';
    if ($invoiceUrl !== '') {
        $invoiceLabel = $invoiceNumber !== '' ? 'Invoice ' . $invoiceNumber : 'Invoice';
        $invoiceButton = '<td><a href="' . htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:14px 22px;background:#c9a56a;color:#1f1812;text-decoration:none;font-weight:600;">' . htmlspecialchars($invoiceLabel, ENT_QUOTES, 'UTF-8') . '</a></td>';
        $invoiceLinksText = "Invoice: {$invoiceUrl}\n";
    }

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'
        . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8')
        . '</title></head><body style="margin:0;padding:0;background:#f5f1ea;font-family:Georgia, Times New Roman, serif;color:#1f1812;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f1ea;padding:24px 0;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;background:#fffdf9;border:1px solid #e5ddd0;">'
        . '<tr><td style="padding:32px 36px;background:#1f1812;color:#f4ebdf;"><div style="font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#d1a12b;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;font-weight:600;">' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</h1></td></tr>'
        . '<tr><td style="padding:32px 36px;">'
        . '<p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Dear ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="margin:0 0 16px;font-size:15px;line-height:1.8;color:#2b241b;">' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;border-collapse:collapse;">'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;width:32%;">Order Number</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Previous Status</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars(girffonAdminOrderUpdateLabel($oldStatus), ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Current Status</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Payment Status</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . $trackingBlock
        . '</table>'
        . '<h2 style="margin:0 0 12px;font-size:18px;">Items</h2>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 24px;">'
        . '<thead><tr><th align="left" style="padding:12px;border-bottom:1px solid #cdb79e;color:#7a6a58;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Product</th><th align="center" style="padding:12px;border-bottom:1px solid #cdb79e;color:#7a6a58;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Qty</th><th align="right" style="padding:12px;border-bottom:1px solid #cdb79e;color:#7a6a58;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Price</th><th align="right" style="padding:12px;border-bottom:1px solid #cdb79e;color:#7a6a58;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Total</th></tr></thead>'
        . '<tbody>' . $itemsHtml . '</tbody></table>'
        . $adminNoteBlock
        . '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px 0 18px;"><tr>'
        . '<td style="padding-right:12px;"><a href="' . htmlspecialchars($trackOrderUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:14px 22px;background:#1f1812;color:#f4ebdf;text-decoration:none;font-weight:600;">Track Order</a></td>'
        . $invoiceButton
        . '</tr></table>'
        . '<p style="margin:0;color:#7a6a58;font-size:13px;line-height:1.7;">If the buttons above do not open directly, copy these links into your browser:</p>'
        . '<p style="margin:8px 0 0;font-size:13px;line-height:1.7;"><a href="' . htmlspecialchars($trackOrderUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($trackOrderUrl, ENT_QUOTES, 'UTF-8') . '</a>' . ($invoiceUrl !== '' ? '<br><a href="' . htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') . '</a>' : '') . '</p>'
        . '</td></tr></table></td></tr></table></body></html>';

    $text = $subject . "\n\n"
        . "Customer: {$customerName}\n"
        . "Order Number: {$orderNumber}\n"
        . "Previous Status: " . girffonAdminOrderUpdateLabel($oldStatus) . "\n"
        . "Current Status: {$statusLabel}\n"
        . "Payment Status: {$paymentLabel}\n"
        . $trackingText
        . $adminNoteText
        . "\nItems:\n{$itemsText}\n\n"
        . "Track Order: {$trackOrderUrl}\n"
        . $invoiceLinksText;

    return [
        'to_email' => (string) ($order['customer_email'] ?? ''),
        'to_name' => $customerName,
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminOrderUpdateRedirect('error', 'Invalid request method.');
}

$orderId = max(0, (int) ($_POST['order_id'] ?? 0));
if ($orderId <= 0) {
    girffonAdminOrderUpdateRedirect('error', 'Order ID is required.');
}

$existingOrder = girffonAdminFetchOrderForUpdate($pdo, $orderId);
if (!$existingOrder) {
    girffonAdminOrderUpdateRedirect('error', 'Order not found.');
}

$payload = [
    'order_status' => (string) ($_POST['order_status'] ?? ''),
    'payment_status' => (string) ($_POST['payment_status'] ?? ''),
    'tracking_code' => (string) ($_POST['tracking_number'] ?? ''),
    'courier_name' => (string) ($_POST['courier_name'] ?? ''),
    'estimated_delivery_date' => (string) ($_POST['estimated_delivery_date'] ?? ''),
    'admin_note' => (string) ($_POST['admin_note'] ?? ''),
];

if (!girffonAdminUpdateOrderRecord($pdo, $orderId, $payload)) {
    girffonAdminOrderUpdateRedirect('error', 'Unable to update the order right now.');
}

$updatedOrder = girffonAdminFetchOrderForUpdate($pdo, $orderId);
if (!$updatedOrder) {
    girffonAdminOrderUpdateRedirect('error', 'Order updated but could not be reloaded.');
}

$oldStatus = (string) ($existingOrder['order_status'] ?? 'pending');
$items = girffonAdminFetchOrderItems($pdo, $orderId);
$customerEmail = strtolower(trim((string) ($updatedOrder['customer_email'] ?? '')));
$emailEnabled = girffonAdminOrderUpdateEmailEnabled($pdo, $updatedOrder);
$emailStatus = 'skipped';
$transport = '';
$errorMessage = '';

if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    $errorMessage = 'Customer email is missing or invalid.';
} elseif (!$emailEnabled) {
    $errorMessage = 'Order update emails are disabled for this customer.';
} else {
    $mailConfig = girffonMailConfig();
    $message = girffonAdminOrderUpdateEmailContent($updatedOrder, $items, $oldStatus, $mailConfig);
    $transport = (string) ($mailConfig['transport'] ?? 'mail');
    $sent = false;

    try {
        if ($transport === 'smtp') {
            try {
                $sent = girffonSendMailWithPhpMailer($mailConfig, $message);
                $transport = 'smtp-phpmailer';
            } catch (Throwable $throwable) {
                $errorMessage = $throwable->getMessage();
                $sent = girffonSendMailWithSocketSmtp($mailConfig, $message);
                $transport = 'smtp-socket';
                $errorMessage = '';
            }
        } else {
            $sent = girffonSendMailWithPhpMail($mailConfig, $message);
            $transport = 'php-mail';
        }
    } catch (Throwable $throwable) {
        $errorMessage = $throwable->getMessage();
        $sent = false;
    }

    $emailStatus = $sent ? 'sent' : 'failed';
    if ($sent) {
        $errorMessage = '';
    }
}

girffonAdminLogOrderUpdate($pdo, [
    'order_id' => $orderId,
    'user_id' => (int) ($updatedOrder['user_id'] ?? 0),
    'customer_email' => $customerEmail,
    'old_status' => $oldStatus,
    'new_status' => (string) ($updatedOrder['order_status'] ?? ''),
    'payment_status' => (string) ($updatedOrder['payment_status'] ?? ''),
    'tracking_number' => (string) ($updatedOrder['tracking_code'] ?? ''),
    'courier' => (string) ($updatedOrder['courier_name'] ?? ''),
    'estimated_delivery_date' => (string) ($updatedOrder['estimated_delivery_date'] ?? ''),
    'admin_note' => (string) ($updatedOrder['admin_note'] ?? ''),
    'email_status' => $emailStatus,
    'transport' => $transport,
    'error_message' => $errorMessage,
]);

$statusMessage = 'Order updated successfully.';
if ($emailStatus === 'sent') {
    $statusMessage .= ' Update email sent.';
} elseif ($errorMessage !== '') {
    $statusMessage .= ' Email status: ' . $errorMessage;
}

girffonAdminOrderUpdateRedirect('status', $statusMessage);