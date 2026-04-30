<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/invoices-data.php';

function girffonInvoiceEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function girffonInvoiceFormatCurrency($value): string
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function girffonInvoiceFormatLabel($value): string
{
    return ucwords(str_replace('_', ' ', (string) $value));
}

function girffonInvoiceFormatDate($value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date('Y-m-d H:i', $timestamp) : (string) $value;
}

function girffonInvoiceAppBasePath(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = str_replace('\\', '/', dirname($scriptName));
    if ($basePath === '/' || $basePath === '.') {
        return '';
    }

    return rtrim($basePath, '/');
}

function girffonInvoiceUrl(string $path): string
{
    $cleanPath = '/' . ltrim(str_replace('\\', '/', $path), '/');
    return girffonInvoiceAppBasePath() . $cleanPath;
}

function girffonInvoiceResolveAssetUrl(?string $path): ?string
{
    $value = trim((string) $path);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $value)) {
        return $value;
    }

    if (str_starts_with($value, '/GirffoN/')) {
        return girffonInvoiceUrl(substr($value, strlen('/GirffoN/')));
    }

    if (str_starts_with($value, '/')) {
        return girffonInvoiceUrl(ltrim($value, '/'));
    }

    return girffonInvoiceUrl($value);
}

function girffonInvoiceResolveAssetPath(?string $path): ?string
{
    $value = trim((string) $path);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $value)) {
        return $value;
    }

    $normalized = str_replace('\\', '/', $value);
    if (str_starts_with($normalized, '/GirffoN/')) {
        return dirname(__DIR__, 2) . '/' . ltrim(substr($normalized, strlen('/GirffoN/')), '/');
    }

    if (str_starts_with($normalized, '/')) {
        return dirname(__DIR__, 2) . '/' . ltrim($normalized, '/');
    }

    return dirname(__DIR__, 2) . '/' . ltrim($normalized, '/');
}

function girffonInvoiceResolveItemImage(array $item): ?string
{
    $directImage = girffonInvoiceResolveAssetUrl($item['image'] ?? '');
    if ($directImage) {
        return $directImage;
    }

    $sku = strtoupper(trim((string) ($item['sku'] ?? '')));
    $fallbackBySku = [
        'GF-BLK-101' => girffonInvoiceUrl('Cart/products/tshirt-men/47/160.png'),
        'GF-HOOD-201' => girffonInvoiceUrl('Cart/products/tshirt-men/48/160.png'),
        'GF-SWT-220' => girffonInvoiceUrl('Cart/products/tshirt-men/49/160.png'),
    ];

    if (isset($fallbackBySku[$sku])) {
        return $fallbackBySku[$sku];
    }

    $productName = strtolower(trim((string) (($item['product_name'] ?? '') !== '' ? $item['product_name'] : ($item['name'] ?? ''))));
    if (str_contains($productName, 'hoodie')) {
        return girffonInvoiceUrl('Cart/products/tshirt-men/48/160.png');
    }
    if (str_contains($productName, 'sweatshirt')) {
        return girffonInvoiceUrl('Cart/products/tshirt-men/49/160.png');
    }
    if (str_contains($productName, 'shirt') || str_contains($productName, 't-shirt') || str_contains($productName, 'tee')) {
        return girffonInvoiceUrl('Cart/products/tshirt-men/47/160.png');
    }

    return null;
}

function girffonInvoiceFetchById(PDO $pdo, int $invoiceId): ?array
{
    $invoice = $invoiceId > 0 ? girffonAdminFetchInvoiceDetail($pdo, $invoiceId) : null;
    if (!$invoice) {
        return null;
    }

    $normalizedItems = array_map(static function (array $item): array {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $price = (float) ($item['price'] ?? 0);
        $lineTotal = (float) ($item['line_total'] ?? 0);
        if ($lineTotal <= 0 && $price > 0 && $quantity > 0) {
            $lineTotal = $price * $quantity;
        }
        return [
            'product_id' => (string) ($item['product_id'] ?? ''),
            'product_name' => (string) (($item['name'] ?? '') !== '' ? $item['name'] : ($item['product_name'] ?? 'GirffoN Product')),
            'sku' => (string) ($item['sku'] ?? ''),
            'size' => (string) ($item['size'] ?? ''),
            'color' => (string) ($item['color'] ?? ''),
            'quantity' => $quantity,
            'price' => $price,
            'line_total' => $lineTotal,
            'image' => (string) ($item['image'] ?? ''),
            'image_url' => girffonInvoiceResolveItemImage($item),
        ];
    }, $invoice['items'] ?? []);

    $computedSubtotal = array_reduce($normalizedItems, static function (float $sum, array $item): float {
        return $sum + (float) $item['line_total'];
    }, 0.0);

    $subtotal = (float) ($invoice['subtotal'] ?? 0);
    if ($subtotal <= 0 && $computedSubtotal > 0) {
        $subtotal = $computedSubtotal;
    }

    $shipping = (float) ($invoice['shipping'] ?? 0);
    $tax = (float) ($invoice['tax'] ?? 0);
    $total = (float) ($invoice['total'] ?? $invoice['invoice_total'] ?? 0);
    if ($total <= 0) {
        $total = $subtotal + $shipping + $tax;
    }

    $invoice['items'] = $normalizedItems;
    $invoice['subtotal_amount'] = $subtotal;
    $invoice['shipping_amount'] = $shipping;
    $invoice['tax_amount'] = $tax;
    $invoice['total_amount'] = $total;
    $invoice['status_label'] = girffonInvoiceFormatLabel($invoice['status'] ?? $invoice['invoice_status'] ?? 'pending');
    $invoice['order_status_label'] = girffonInvoiceFormatLabel($invoice['order_status'] ?? '-');
    $invoice['payment_status_label'] = girffonInvoiceFormatLabel($invoice['order_payment_status'] ?? $invoice['invoice_status'] ?? 'pending');
    $invoice['logo_url'] = girffonInvoiceUrl('Image/Logo/Logo -PDF.png');
    $invoice['logo_path'] = dirname(__DIR__, 2) . '/Image/Logo/Logo -PDF.png';

    return $invoice;
}

function girffonInvoicePdfFilename(array $invoice): string
{
    $invoiceNumber = (string) ($invoice['invoice_number'] ?? 'invoice');
    return preg_replace('/[^A-Za-z0-9._-]/', '-', $invoiceNumber) . '.pdf';
}
