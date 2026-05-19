<?php

require_once __DIR__ . '/../config/mail.php';

function girffonLoadPhpMailer(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $exceptionPath = __DIR__ . '/../../vendor/PHPMailer/src/Exception.php';
    $phpMailerPath = __DIR__ . '/../../vendor/PHPMailer/src/PHPMailer.php';
    $smtpPath = __DIR__ . '/../../vendor/PHPMailer/src/SMTP.php';

    if (!is_file($exceptionPath) || !is_file($phpMailerPath) || !is_file($smtpPath)) {
        throw new RuntimeException('PHPMailer source files were not found under vendor/PHPMailer/src.');
    }

    require_once $exceptionPath;
    require_once $phpMailerPath;
    require_once $smtpPath;

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') || !class_exists('PHPMailer\\PHPMailer\\SMTP') || !class_exists('PHPMailer\\PHPMailer\\Exception')) {
        throw new RuntimeException('PHPMailer classes could not be loaded from vendor/PHPMailer/src.');
    }

    $loaded = true;
}

function girffonOrderEmailFormatCurrency(float $amount): string
{
    return 'EUR ' . number_format($amount, 2, '.', ',');
}

function girffonOrderEmailBuildAppUrl(array $mailConfig): string
{
    if (!empty($mailConfig['app_url'])) {
        return rtrim((string) $mailConfig['app_url'], '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/GirffoN/backend/checkout/place-order.php'));
    $segments = explode('/', trim($scriptPath, '/'));
    $rootSegment = $segments[0] ?? 'GirffoN';

    return $scheme . '://' . $host . '/' . $rootSegment;
}

function girffonOrderEmailPublicUrl(string $path, array $mailConfig): string
{
    $value = trim(str_replace('\\', '/', $path));
    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    $relative = ltrim($value, '/');
    if (stripos($relative, 'GirffoN/') === 0) {
        $relative = substr($relative, 8);
    }

    return girffonOrderEmailBuildAppUrl($mailConfig) . '/' . $relative;
}

function girffonOrderEmailRenderItemsHtml(array $items): string
{
    $rows = array_map(static function (array $item): string {
        $details = array_filter([
            $item['sku'] ? 'SKU: ' . $item['sku'] : '',
            $item['size'] ? 'Size: ' . $item['size'] : '',
            $item['color'] ? 'Color: ' . $item['color'] : '',
        ]);

        return '<tr>'
            . '<td style="padding:12px;border-bottom:1px solid #e5ddd0;vertical-align:top;">' . htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') . '<br><span style="color:#7a6a58;font-size:12px;">' . htmlspecialchars(implode(' | ', $details), ENT_QUOTES, 'UTF-8') . '</span></td>'
            . '<td style="padding:12px;border-bottom:1px solid #e5ddd0;text-align:center;vertical-align:top;">' . (int) $item['quantity'] . '</td>'
            . '<td style="padding:12px;border-bottom:1px solid #e5ddd0;text-align:right;vertical-align:top;">' . htmlspecialchars(girffonOrderEmailFormatCurrency((float) $item['price']), ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:12px;border-bottom:1px solid #e5ddd0;text-align:right;vertical-align:top;">' . htmlspecialchars(girffonOrderEmailFormatCurrency((float) $item['line_total']), ENT_QUOTES, 'UTF-8') . '</td>'
            . '</tr>';
    }, $items);

    return implode('', $rows);
}

function girffonOrderEmailRenderItemsText(array $items): string
{
    $lines = array_map(static function (array $item): string {
        $details = array_filter([
            $item['sku'] ? 'SKU ' . $item['sku'] : '',
            $item['size'] ? 'Size ' . $item['size'] : '',
            $item['color'] ? 'Color ' . $item['color'] : '',
        ]);

        return '- ' . $item['product_name']
            . ' | Qty: ' . (int) $item['quantity']
            . ' | Price: ' . girffonOrderEmailFormatCurrency((float) $item['price'])
            . ' | Line Total: ' . girffonOrderEmailFormatCurrency((float) $item['line_total'])
            . ($details ? ' | ' . implode(' | ', $details) : '');
    }, $items);

    return implode("\n", $lines);
}

function girffonRenderOrderConfirmationEmail(array $mailData): array
{
    $customerName = (string) ($mailData['customer_name'] ?? 'GirffoN Customer');
    $orderNumber = (string) ($mailData['order_number'] ?? '');
    $invoiceNumber = (string) ($mailData['invoice_number'] ?? '');
    $trackingCode = (string) ($mailData['tracking_code'] ?? '');
    $items = array_values($mailData['items'] ?? []);
    $total = (float) ($mailData['total'] ?? 0);
    $shippingAddress = trim((string) ($mailData['shipping_address'] ?? ''));
    $trackOrderUrl = (string) ($mailData['track_order_url'] ?? '');
    $downloadInvoiceUrl = (string) ($mailData['download_invoice_url'] ?? '');

    $subject = 'GirffoN Order Confirmation - ' . $orderNumber;
    $itemsHtml = girffonOrderEmailRenderItemsHtml($items);
    $itemsText = girffonOrderEmailRenderItemsText($items);
    $totalFormatted = girffonOrderEmailFormatCurrency($total);

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'
        . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8')
        . '</title></head><body style="margin:0;padding:0;background:#f5f1ea;font-family:Georgia, Times New Roman, serif;color:#1f1812;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f1ea;padding:24px 0;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;background:#fffdf9;border:1px solid #e5ddd0;">'
        . '<tr><td style="padding:32px 36px;background:#1f1812;color:#f4ebdf;"><div style="font-size:13px;letter-spacing:2px;text-transform:uppercase;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;font-weight:600;">Order Confirmed</h1></td></tr>'
        . '<tr><td style="padding:32px 36px;">'
        . '<p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Dear ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ', your order has been placed successfully. We have saved your order details and prepared your invoice for download.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;border-collapse:collapse;">'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;width:32%;">Order Number</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Invoice Number</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($invoiceNumber, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Tracking Code</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($trackingCode, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Shipping Address</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . nl2br(htmlspecialchars($shippingAddress !== '' ? $shippingAddress : '-', ENT_QUOTES, 'UTF-8')) . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Total Price</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($totalFormatted, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>'
        . '<h2 style="margin:0 0 12px;font-size:18px;">Items</h2>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 24px;">'
        . '<thead><tr><th align="left" style="padding:12px;border-bottom:1px solid #cdb79e;color:#7a6a58;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Product</th><th align="center" style="padding:12px;border-bottom:1px solid #cdb79e;color:#7a6a58;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Qty</th><th align="right" style="padding:12px;border-bottom:1px solid #cdb79e;color:#7a6a58;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Price</th><th align="right" style="padding:12px;border-bottom:1px solid #cdb79e;color:#7a6a58;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Total</th></tr></thead>'
        . '<tbody>' . $itemsHtml . '</tbody></table>'
        . '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 18px;"><tr>'
        . '<td style="padding-right:12px;"><a href="' . htmlspecialchars($trackOrderUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:14px 22px;background:#1f1812;color:#f4ebdf;text-decoration:none;font-weight:600;">Track Order</a></td>'
        . '<td><a href="' . htmlspecialchars($downloadInvoiceUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:14px 22px;background:#c9a56a;color:#1f1812;text-decoration:none;font-weight:600;">Download Invoice</a></td>'
        . '</tr></table>'
        . '<p style="margin:0;color:#7a6a58;font-size:13px;line-height:1.7;">If the buttons above do not open directly, copy these links into your browser:</p>'
        . '<p style="margin:8px 0 0;font-size:13px;line-height:1.7;"><a href="' . htmlspecialchars($trackOrderUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($trackOrderUrl, ENT_QUOTES, 'UTF-8') . '</a><br><a href="' . htmlspecialchars($downloadInvoiceUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($downloadInvoiceUrl, ENT_QUOTES, 'UTF-8') . '</a></p>'
        . '</td></tr></table></td></tr></table></body></html>';

    $text = "GirffoN Order Confirmation\n"
        . "Customer: {$customerName}\n"
        . "Order Number: {$orderNumber}\n"
        . "Invoice Number: {$invoiceNumber}\n"
        . "Tracking Code: {$trackingCode}\n"
        . "Shipping Address: {$shippingAddress}\n"
        . "Total Price: {$totalFormatted}\n\n"
        . "Items:\n{$itemsText}\n\n"
        . "Track Order: {$trackOrderUrl}\n"
        . "Download Invoice: {$downloadInvoiceUrl}\n";

    return [
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ];
}

function girffonSendMailWithPhpMailer(array $mailConfig, array $message): bool
{
    girffonLoadPhpMailer();

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = (string) ($mailConfig['smtp']['host'] ?? '');
    $mailer->Port = (int) ($mailConfig['smtp']['port'] ?? 587);
    $mailer->SMTPAuth = (bool) ($mailConfig['smtp']['auth'] ?? true);
    $mailer->Username = (string) ($mailConfig['smtp']['username'] ?? '');
    $mailer->Password = (string) ($mailConfig['smtp']['password'] ?? '');
    $mailer->Timeout = max(5, (int) ($mailConfig['smtp']['timeout'] ?? 20));
    $mailer->CharSet = 'UTF-8';

    $encryption = (string) ($mailConfig['smtp']['encryption'] ?? 'tls');
    if ($encryption !== '') {
        $mailer->SMTPSecure = $encryption;
    }

    $mailer->setFrom((string) $mailConfig['from_email'], (string) $mailConfig['from_name']);
    $mailer->addReplyTo((string) $mailConfig['reply_to_email'], (string) $mailConfig['reply_to_name']);
    $mailer->addAddress((string) $message['to_email'], (string) $message['to_name']);
    $mailer->Subject = (string) $message['subject'];
    $mailer->isHTML(true);
    $mailer->Body = (string) $message['html'];
    $mailer->AltBody = (string) $message['text'];

    return $mailer->send();
}

function girffonOrderMailDebugLog(array $mailConfig, string $message): void
{
    $target = trim((string) ($mailConfig['debug_log'] ?? ''));
    if ($target === '') {
        error_log('[GirffoN Mail] ' . $message);
        return;
    }

    error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, 3, $target);
}

function girffonSmtpReadResponse($socket, array $expectedCodes): string
{
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
          break;
        }
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP unexpected response: ' . trim($response));
    }

    return $response;
}

function girffonSmtpSendCommand($socket, string $command, array $expectedCodes): string
{
    if (fwrite($socket, $command . "\r\n") === false) {
        throw new RuntimeException('SMTP write failed.');
    }

    return girffonSmtpReadResponse($socket, $expectedCodes);
}

function girffonSendMailWithSocketSmtp(array $mailConfig, array $message): bool
{
    $smtp = $mailConfig['smtp'] ?? [];
    $host = trim((string) ($smtp['host'] ?? ''));
    if ($host === '') {
        throw new RuntimeException('SMTP host is required.');
    }

    $port = (int) ($smtp['port'] ?? 587);
    $timeout = max(5, (int) ($smtp['timeout'] ?? 20));
    $encryption = strtolower(trim((string) ($smtp['encryption'] ?? 'tls')));
    $remoteHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
    $socket = @stream_socket_client($remoteHost . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!is_resource($socket)) {
        throw new RuntimeException('SMTP connection failed: ' . $errstr . ' (' . $errno . ')');
    }

    stream_set_timeout($socket, $timeout);

    try {
        girffonSmtpReadResponse($socket, [220]);
        girffonSmtpSendCommand($socket, 'EHLO girffon.shop', [250]);

        if ($encryption === 'tls') {
            girffonSmtpSendCommand($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP STARTTLS failed.');
            }
            girffonSmtpSendCommand($socket, 'EHLO girffon.shop', [250]);
        }

        $authEnabled = (bool) ($smtp['auth'] ?? true);
        $username = (string) ($smtp['username'] ?? '');
        $password = (string) ($smtp['password'] ?? '');
        if ($authEnabled && $username !== '') {
            girffonSmtpSendCommand($socket, 'AUTH LOGIN', [334]);
            girffonSmtpSendCommand($socket, base64_encode($username), [334]);
            girffonSmtpSendCommand($socket, base64_encode($password), [235]);
        }

        girffonSmtpSendCommand($socket, 'MAIL FROM:<' . (string) $mailConfig['from_email'] . '>', [250]);
        girffonSmtpSendCommand($socket, 'RCPT TO:<' . (string) $message['to_email'] . '>', [250, 251]);
        girffonSmtpSendCommand($socket, 'DATA', [354]);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $mailConfig['from_name'] . ' <' . $mailConfig['from_email'] . '>',
            'Reply-To: ' . $mailConfig['reply_to_name'] . ' <' . $mailConfig['reply_to_email'] . '>',
            'To: ' . $message['to_name'] . ' <' . $message['to_email'] . '>',
            'Subject: ' . $message['subject'],
        ];

        $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], (string) $message['html']) . "\r\n.";
        if (fwrite($socket, $payload . "\r\n") === false) {
            throw new RuntimeException('SMTP DATA payload write failed.');
        }
        girffonSmtpReadResponse($socket, [250]);
        girffonSmtpSendCommand($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }

    return true;
}

function girffonSendMailWithPhpMail(array $mailConfig, array $message): bool
{
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $mailConfig['from_name'] . ' <' . $mailConfig['from_email'] . '>',
        'Reply-To: ' . $mailConfig['reply_to_name'] . ' <' . $mailConfig['reply_to_email'] . '>',
    ];

    $warningMessage = '';
    set_error_handler(static function (int $severity, string $errorMessage) use (&$warningMessage): bool {
        $warningMessage = $errorMessage;
        return true;
    });

    try {
        $result = mail((string) $message['to_email'], (string) $message['subject'], (string) $message['html'], implode("\r\n", $headers));
    } finally {
        restore_error_handler();
    }

    if ($warningMessage !== '') {
        throw new RuntimeException($warningMessage);
    }

    return $result;
}

function girffonSendOrderConfirmationEmail(array $mailData): bool
{
    $mailConfig = girffonMailConfig();
    $appUrl = girffonOrderEmailBuildAppUrl($mailConfig);
    $rendered = girffonRenderOrderConfirmationEmail([
        'customer_name' => $mailData['customer_name'] ?? 'GirffoN Customer',
        'order_number' => $mailData['order_number'] ?? '',
        'invoice_number' => $mailData['invoice_number'] ?? '',
        'tracking_code' => $mailData['tracking_code'] ?? '',
        'shipping_address' => $mailData['shipping_address'] ?? '',
        'items' => $mailData['items'] ?? [],
        'total' => $mailData['total'] ?? 0,
        'track_order_url' => $appUrl . '/TrackOrder.php?order_number=' . rawurlencode((string) ($mailData['order_number'] ?? '')),
        'download_invoice_url' => $appUrl . '/invoice-pdf.php?id=' . rawurlencode((string) ($mailData['invoice_id'] ?? '')),
    ]);

    $message = [
        'to_email' => (string) ($mailData['customer_email'] ?? ''),
        'to_name' => (string) ($mailData['customer_name'] ?? 'GirffoN Customer'),
        'subject' => $rendered['subject'],
        'html' => $rendered['html'],
        'text' => $rendered['text'],
    ];

    if (($mailConfig['transport'] ?? 'mail') === 'smtp') {
        try {
            return girffonSendMailWithPhpMailer($mailConfig, $message);
        } catch (Throwable $throwable) {
            girffonOrderMailDebugLog($mailConfig, 'PHPMailer SMTP failed: ' . $throwable->getMessage());
            return girffonSendMailWithSocketSmtp($mailConfig, $message);
        }
    }

    return girffonSendMailWithPhpMail($mailConfig, $message);
}

function girffonRenderCustomDesignPaymentEmail(array $mailData, array $mailConfig): array
{
    $customerName = (string) ($mailData['customer_name'] ?? 'GirffoN Customer');
    $customerEmail = (string) ($mailData['customer_email'] ?? '');
    $customerPhone = (string) ($mailData['customer_phone'] ?? '');
    $orderNumber = (string) ($mailData['order_number'] ?? '');
    $productName = (string) ($mailData['product_name'] ?? 'Custom Product');
    $total = girffonOrderEmailFormatCurrency((float) ($mailData['total'] ?? 0));
    $sizeLines = is_array($mailData['size_lines'] ?? null) ? $mailData['size_lines'] : [];
    $uploads = is_array($mailData['uploads'] ?? null) ? $mailData['uploads'] : [];
    $addDesign = is_array($mailData['add_design'] ?? null) ? $mailData['add_design'] : [];
    $previewViews = is_array($mailData['preview_views'] ?? null) ? $mailData['preview_views'] : [];

    $previewRows = [];
    foreach (['front' => 'Front Preview', 'back' => 'Back Preview', 'right' => 'Right Sleeve Preview', 'left' => 'Left Sleeve Preview'] as $key => $label) {
        $path = (string) (($previewViews[$key]['path'] ?? '') ?: ($mailData['preview_' . $key] ?? ''));
        if ($path === '') {
            continue;
        }
        $url = girffonOrderEmailPublicUrl($path, $mailConfig);
        $previewRows[] = '<div style="margin:0 0 18px;">'
            . '<div style="margin:0 0 8px;font-size:13px;font-weight:700;color:#7a6a58;letter-spacing:0.08em;text-transform:uppercase;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" style="display:block;width:100%;max-width:280px;border-radius:16px;border:1px solid #e5ddd0;background:#fff;">'
            . '</div>';
    }

    $sizeLinesHtml = '';
    $sizeLinesText = '';
    foreach ($sizeLines as $line) {
        $lineSize = trim((string) ($line['size'] ?? 'Custom'));
        $lineColor = trim((string) ($line['color'] ?? 'Custom'));
        $lineQuantity = max(1, (int) ($line['quantity'] ?? 1));
        $lineText = $lineSize . ' / ' . $lineColor . ' / Qty ' . $lineQuantity;
        $sizeLinesHtml .= '<li style="margin:0 0 8px;">' . htmlspecialchars($lineText, ENT_QUOTES, 'UTF-8') . '</li>';
        $sizeLinesText .= '- ' . $lineText . "\n";
    }

    $uploadsHtml = '';
    $uploadsText = '';
    foreach ($uploads as $upload) {
        $name = trim((string) ($upload['name'] ?? $upload['original_name'] ?? 'Uploaded photo'));
        $url = girffonOrderEmailPublicUrl((string) ($upload['path'] ?? $upload['file_path'] ?? ''), $mailConfig);
        $uploadsHtml .= '<li style="margin:0 0 8px;">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ($url !== '' ? ' - <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Open file</a>' : '') . '</li>';
        $uploadsText .= '- ' . $name . ($url !== '' ? ' - ' . $url : '') . "\n";
    }

    $addDesignHtml = '';
    $addDesignText = '';
    foreach ($addDesign as $design) {
        $fileName = trim((string) ($design['file_name'] ?? $design['name'] ?? 'Design file'));
        $folderName = trim((string) ($design['folder_name'] ?? ''));
        $imageUrl = girffonOrderEmailPublicUrl((string) ($design['image'] ?? ''), $mailConfig);
        $label = $folderName !== '' ? $folderName . ' / ' . $fileName : $fileName;
        $addDesignHtml .= '<li style="margin:0 0 8px;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ($imageUrl !== '' ? ' - <a href="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '">Open file</a>' : '') . '</li>';
        $addDesignText .= '- ' . $label . ($imageUrl !== '' ? ' - ' . $imageUrl : '') . "\n";
    }

    $subject = 'GirffoN Custom Design Payment Received - ' . $orderNumber;
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'
        . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8')
        . '</title></head><body style="margin:0;padding:0;background:#f5f1ea;font-family:Georgia, Times New Roman, serif;color:#1f1812;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f1ea;padding:24px 0;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:820px;background:#fffdf9;border:1px solid #e5ddd0;">'
        . '<tr><td style="padding:32px 36px;background:#1f1812;color:#f4ebdf;"><div style="font-size:13px;letter-spacing:2px;text-transform:uppercase;">GirffoN</div><h1 style="margin:10px 0 0;font-size:28px;font-weight:600;">Custom Design Payment Received</h1></td></tr>'
        . '<tr><td style="padding:32px 36px;">'
        . '<p style="margin:0 0 18px;font-size:16px;line-height:1.7;">Your custom design payment has been received successfully. The order is now in the paid review queue.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;border-collapse:collapse;">'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;width:35%;">Order Number</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Customer</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Email</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Phone</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($customerPhone !== '' ? $customerPhone : '-', ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Product</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;color:#7a6a58;">Total Price</td><td style="padding:10px 0;border-bottom:1px solid #e5ddd0;font-weight:600;">' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>'
        . '<h2 style="margin:0 0 12px;font-size:18px;">Size and Color Lines</h2>'
        . '<ul style="margin:0 0 24px 20px;padding:0;line-height:1.7;">' . ($sizeLinesHtml !== '' ? $sizeLinesHtml : '<li>No saved size and color lines.</li>') . '</ul>'
        . '<h2 style="margin:0 0 12px;font-size:18px;">Preview Images</h2>'
        . ($previewRows ? implode('', $previewRows) : '<p style="margin:0 0 24px;color:#7a6a58;">No preview images were available.</p>')
        . '<h2 style="margin:12px 0 12px;font-size:18px;">Uploaded Photos</h2>'
        . '<ul style="margin:0 0 24px 20px;padding:0;line-height:1.7;">' . ($uploadsHtml !== '' ? $uploadsHtml : '<li>No uploaded photos were attached.</li>') . '</ul>'
        . '<h2 style="margin:0 0 12px;font-size:18px;">Add Design Files</h2>'
        . '<ul style="margin:0 0 12px 20px;padding:0;line-height:1.7;">' . ($addDesignHtml !== '' ? $addDesignHtml : '<li>No add design files were selected.</li>') . '</ul>'
        . '</td></tr></table></td></tr></table></body></html>';

    $text = "GirffoN Custom Design Payment Received\n"
        . "Order Number: {$orderNumber}\n"
        . "Customer: {$customerName}\n"
        . "Email: {$customerEmail}\n"
        . "Phone: " . ($customerPhone !== '' ? $customerPhone : '-') . "\n"
        . "Product: {$productName}\n"
        . "Total Price: {$total}\n\n"
        . "Size and Color Lines:\n" . ($sizeLinesText !== '' ? $sizeLinesText : "- No saved size and color lines.\n") . "\n"
        . "Uploaded Photos:\n" . ($uploadsText !== '' ? $uploadsText : "- No uploaded photos were attached.\n") . "\n"
        . "Add Design Files:\n" . ($addDesignText !== '' ? $addDesignText : "- No add design files were selected.\n");

    return [
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ];
}

function girffonSendCustomDesignPaymentEmail(array $mailData): bool
{
    $mailConfig = girffonMailConfig();
    $rendered = girffonRenderCustomDesignPaymentEmail($mailData, $mailConfig);
    $message = [
        'to_email' => (string) ($mailData['customer_email'] ?? ''),
        'to_name' => (string) ($mailData['customer_name'] ?? 'GirffoN Customer'),
        'subject' => $rendered['subject'],
        'html' => $rendered['html'],
        'text' => $rendered['text'],
    ];

    if (($mailConfig['transport'] ?? 'mail') === 'smtp') {
        try {
            return girffonSendMailWithPhpMailer($mailConfig, $message);
        } catch (Throwable $throwable) {
            girffonOrderMailDebugLog($mailConfig, 'Custom design payment PHPMailer SMTP failed: ' . $throwable->getMessage());
            return girffonSendMailWithSocketSmtp($mailConfig, $message);
        }
    }

    return girffonSendMailWithPhpMail($mailConfig, $message);
}