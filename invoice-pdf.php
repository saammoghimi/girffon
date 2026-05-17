<?php
require_once __DIR__ . '/backend/invoices/invoice-data.php';

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = girffonInvoiceFetchById($pdo, $invoiceId);

if (!$invoice) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

function gf_pdf_escape(string $value): string
{
    return str_replace(["\\", "(", ")", "\r", "\n"], ["\\\\", "\\(", "\\)", ' ', ' '], $value);
}

function gf_pdf_money($value): string
{
    if (function_exists('girffonInvoiceFormatCurrency')) {
        return girffonInvoiceFormatCurrency($value);
    }
    return 'EUR ' . number_format((float) $value, 2);
}

function gf_pdf_date($value): string
{
    if (function_exists('girffonInvoiceFormatDate')) {
        return girffonInvoiceFormatDate($value);
    }
    return (string) $value;
}

function gf_pdf_asset_path(string $path): ?string
{
    $path = trim($path);
    if ($path === '') {
        return null;
    }
    if (function_exists('girffonInvoiceResolveAssetPath')) {
        $resolved = girffonInvoiceResolveAssetPath($path);
        if ($resolved) {
            return $resolved;
        }
    }
    $candidates = [
        __DIR__ . '/' . ltrim($path, '/'),
        dirname(__DIR__) . '/' . ltrim($path, '/'),
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }
    return null;
}

function gf_pdf_load_image(?string $path): ?array
{
    static $cache = [];
    if (!$path || !is_file($path) || !is_readable($path)) {
        return null;
    }
    if (array_key_exists($path, $cache)) {
        return $cache[$path];
    }
    $binary = @file_get_contents($path);
    if (!is_string($binary) || $binary === '' || !function_exists('getimagesizefromstring')) {
        return $cache[$path] = null;
    }
    $info = @getimagesizefromstring($binary);
    if (!$info) {
        return $cache[$path] = null;
    }
    $width = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    $mime = strtolower((string) ($info['mime'] ?? ''));
    if ($width <= 0 || $height <= 0) {
        return $cache[$path] = null;
    }
    if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
        return $cache[$path] = ['width' => $width, 'height' => $height, 'data' => $binary];
    }
    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        return $cache[$path] = null;
    }
    $source = @imagecreatefromstring($binary);
    if (!$source) {
        return $cache[$path] = null;
    }
    $canvas = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);
    ob_start();
    imagejpeg($canvas, null, 88);
    $jpeg = (string) ob_get_clean();
    imagedestroy($source);
    imagedestroy($canvas);
    if ($jpeg === '') {
        return $cache[$path] = null;
    }
    return $cache[$path] = ['width' => $width, 'height' => $height, 'data' => $jpeg];
}

function gf_pdf_fit(float $srcW, float $srcH, float $maxW, float $maxH): array
{
    if ($srcW <= 0 || $srcH <= 0) {
        return [$maxW, $maxH];
    }
    $scale = min($maxW / $srcW, $maxH / $srcH);
    return [$srcW * $scale, $srcH * $scale];
}

function gf_pdf_wrap(string $text, int $maxChars, int $maxLines = 2): array
{
    $text = preg_replace('/\s+/', ' ', trim($text));
    if ($text === '') {
        return ['-'];
    }
    $lines = explode("\n", wordwrap($text, $maxChars, "\n", true));
    $lines = array_values(array_filter(array_map('trim', $lines)));
    return array_slice($lines ?: ['-'], 0, $maxLines);
}

function gf_pdf_build(array $invoice): string
{
    $company = is_array($invoice['company'] ?? null) ? $invoice['company'] : (function_exists('girffonInvoiceCompanyProfile') ? girffonInvoiceCompanyProfile() : []);
    $content = [];
    $images = [];

    $line = static function (float $x, float $y, string $font, float $size, string $text, string $color = '0.10 0.10 0.11 rg') use (&$content): void {
        $content[] = sprintf('%s BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET', $color, $font, $size, $x, $y, gf_pdf_escape($text));
    };
    $rect = static function (float $x, float $y, float $w, float $h, string $fill, ?string $stroke = null, float $strokeWidth = 0.7) use (&$content): void {
        $content[] = sprintf('%s %.2F %.2F %.2F %.2F re f', $fill, $x, $y, $w, $h);
        if ($stroke) {
            $content[] = sprintf('%s %.2F w %.2F %.2F %.2F %.2F re S', $stroke, $strokeWidth, $x, $y, $w, $h);
        }
    };
    $image = static function (?string $path, float $x, float $y, float $maxW, float $maxH) use (&$content, &$images): bool {
        $img = gf_pdf_load_image($path);
        if (!$img) {
            return false;
        }
        [$w, $h] = gf_pdf_fit((float) $img['width'], (float) $img['height'], $maxW, $maxH);
        $key = sha1((string) $path . $x . $y);
        $images[$key] = $img;
        $content[] = sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /Im%s Do Q', $w, $h, $x, $y + (($maxH - $h) / 2), $key);
        return true;
    };

    // Page background and header
    $rect(0, 0, 595, 842, '1 1 1 rg');
    $rect(32, 740, 531, 70, '0.10 0.10 0.11 rg');
    $rect(32, 726, 531, 14, '0.77 0.58 0.16 rg');

    $logoPath = gf_pdf_asset_path((string) ($invoice['logo_path'] ?? 'Image/Logo/Logo -PDF.png'));
    $image($logoPath, 50, 765, 90, 28);
    $line(50, 748, 'F2', 19, (string) ($company['name'] ?? 'GirffoN Premium Clothing'), '1 1 1 rg');
    $line(50, 731, 'F1', 9.5, (string) ($company['tagline'] ?? 'Premium Clothing'), '0.90 0.85 0.72 rg');

    $line(392, 786, 'F1', 8.0, 'INVOICE', '0.88 0.72 0.25 rg');
    $line(392, 764, 'F2', 20, (string) ($invoice['invoice_number'] ?? '-'), '1 1 1 rg');
    $line(392, 744, 'F1', 9.0, 'Date: ' . gf_pdf_date($invoice['created_at'] ?? ''), '0.88 0.88 0.88 rg');

    // Company meta
    $line(50, 704, 'F1', 8.8, 'Email: ' . (string) ($company['email'] ?? 'info@girffon.com'), '0.38 0.38 0.38 rg');
    $line(50, 690, 'F1', 8.8, 'Website: ' . (string) ($company['website'] ?? 'www.girffon.com'), '0.38 0.38 0.38 rg');
    $line(50, 676, 'F1', 8.8, 'Phone: ' . (string) ($company['phone'] ?? '-'), '0.38 0.38 0.38 rg');

    // Bill/order blocks
    $rect(32, 600, 250, 58, '0.99 0.98 0.95 rg', '0.86 0.75 0.48 RG');
    $line(48, 638, 'F1', 8.0, 'BILL TO', '0.62 0.46 0.10 rg');
    $line(48, 620, 'F2', 12.2, (string) ($invoice['customer_name'] ?? '-'), '0.10 0.10 0.11 rg');
    $line(48, 604, 'F1', 9.0, (string) ($invoice['customer_email'] ?? '-'), '0.36 0.36 0.36 rg');

    $rect(313, 600, 250, 58, '0.99 0.98 0.95 rg', '0.86 0.75 0.48 RG');
    $line(329, 638, 'F1', 8.0, 'ORDER DETAILS', '0.62 0.46 0.10 rg');
    $line(329, 620, 'F1', 9.0, 'Order: ' . (string) ($invoice['order_number'] ?? '-'), '0.16 0.16 0.16 rg');
    $line(329, 605, 'F1', 9.0, 'Tracking: ' . (string) (($invoice['tracking_code'] ?? '') ?: '-'), '0.16 0.16 0.16 rg');

    // Items header
    $line(32, 568, 'F2', 14.0, 'Items', '0.10 0.10 0.11 rg');
    $rect(32, 536, 531, 24, '0.10 0.10 0.11 rg');
    $line(48, 544, 'F1', 8.0, 'PRODUCT', '1 1 1 rg');
    $line(372, 544, 'F1', 8.0, 'QTY', '1 1 1 rg');
    $line(423, 544, 'F1', 8.0, 'UNIT', '1 1 1 rg');
    $line(502, 544, 'F1', 8.0, 'TOTAL', '1 1 1 rg');

    $y = 506;
    $items = is_array($invoice['items'] ?? null) ? $invoice['items'] : [];
    if (!$items) {
        $rect(32, $y - 42, 531, 52, '0.99 0.98 0.95 rg', '0.90 0.84 0.67 RG');
        $line(48, $y - 12, 'F1', 10.0, 'No items found for this invoice.', '0.35 0.35 0.35 rg');
        $y -= 70;
    }

    foreach (array_slice($items, 0, 5) as $item) {
        $rowH = 74;
        $rect(32, $y - $rowH + 12, 531, $rowH, '0.99 0.98 0.95 rg', '0.90 0.84 0.67 RG');
        $itemImage = gf_pdf_asset_path((string) ($item['image'] ?? ''));
        if (!$itemImage && !empty($item['image_url'])) {
            $itemImage = gf_pdf_asset_path((string) $item['image_url']);
        }
        if (!$image($itemImage, 48, $y - 49, 48, 48)) {
            $rect(48, $y - 49, 48, 48, '0.94 0.93 0.89 rg', '0.80 0.74 0.61 RG');
            $line(57, $y - 24, 'F1', 7.2, 'PHOTO', '0.50 0.50 0.50 rg');
        }
        $nameLines = gf_pdf_wrap((string) ($item['product_name'] ?? '-'), 34, 2);
        foreach ($nameLines as $i => $nameLine) {
            $line(112, $y - 5 - ($i * 12), 'F2', 9.8, $nameLine, '0.10 0.10 0.11 rg');
        }
        $line(112, $y - 36, 'F1', 8.0, 'SKU: ' . (string) (($item['sku'] ?? '') ?: '-'), '0.38 0.38 0.38 rg');
        $line(112, $y - 49, 'F1', 8.0, 'Size: ' . (string) (($item['size'] ?? '') ?: 'One Size') . '    Color: ' . (string) (($item['color'] ?? '') ?: '-'), '0.38 0.38 0.38 rg');

        $line(374, $y - 18, 'F2', 9.2, (string) ($item['quantity'] ?? 1), '0.10 0.10 0.11 rg');
        $line(418, $y - 18, 'F1', 8.8, gf_pdf_money($item['price'] ?? 0), '0.10 0.10 0.11 rg');
        $line(500, $y - 18, 'F2', 8.8, gf_pdf_money($item['line_total'] ?? 0), '0.10 0.10 0.11 rg');
        $y -= 86;
    }

    // Summary and footer
    $summaryY = min(110, $y - 118);
    if ($summaryY < 44) {
        $summaryY = 44;
    }
    $rect(350, $summaryY, 213, 104, '0.10 0.10 0.11 rg');
    $line(368, $summaryY + 78, 'F1', 9.0, 'Subtotal', '0.88 0.88 0.88 rg');
    $line(472, $summaryY + 78, 'F2', 9.0, gf_pdf_money($invoice['subtotal_amount'] ?? 0), '1 1 1 rg');
    $line(368, $summaryY + 58, 'F1', 9.0, 'Shipping', '0.88 0.88 0.88 rg');
    $line(472, $summaryY + 58, 'F2', 9.0, gf_pdf_money($invoice['shipping_amount'] ?? 0), '1 1 1 rg');
    $line(368, $summaryY + 38, 'F1', 9.0, 'Tax', '0.88 0.88 0.88 rg');
    $line(472, $summaryY + 38, 'F2', 9.0, gf_pdf_money($invoice['tax_amount'] ?? 0), '1 1 1 rg');
    $content[] = sprintf('0.77 0.58 0.16 RG 1 w 368 %.2F 176 0 l S', $summaryY + 27);
    $line(368, $summaryY + 12, 'F2', 11.0, 'Grand Total', '0.88 0.72 0.25 rg');
    $line(472, $summaryY + 12, 'F2', 11.0, gf_pdf_money($invoice['total_amount'] ?? 0), '0.88 0.72 0.25 rg');

    $line(32, 40, 'F2', 10.0, 'Thank you for choosing GirffoN.', '0.10 0.10 0.11 rg');
    $line(32, 24, 'F1', 8.4, 'This invoice was generated automatically from your GirffoN order.', '0.38 0.38 0.38 rg');
    $content[] = '0.77 0.58 0.16 rg 32 10 531 3 re f';

    $objects = [];
    $objectNumber = 1;
    $catalog = $objectNumber++;
    $pagesRoot = $objectNumber++;
    $page = $objectNumber++;
    $contentObj = $objectNumber++;
    $fontRegular = $objectNumber++;
    $fontBold = $objectNumber++;
    $imageObjects = [];

    foreach ($images as $key => $img) {
        $imageObjects[$key] = $objectNumber++;
        $objects[$imageObjects[$key]] = '<< /Type /XObject /Subtype /Image /Width ' . (int) $img['width'] . ' /Height ' . (int) $img['height'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen((string) $img['data']) . " >>\nstream\n" . $img['data'] . "\nendstream";
    }

    $stream = implode("\n", $content);
    $objects[$contentObj] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
    $xObj = [];
    foreach ($imageObjects as $key => $objNo) {
        $xObj[] = '/Im' . $key . ' ' . $objNo . ' 0 R';
    }
    $resources = '<< /Font << /F1 ' . $fontRegular . ' 0 R /F2 ' . $fontBold . ' 0 R >>';
    if ($xObj) {
        $resources .= ' /XObject << ' . implode(' ', $xObj) . ' >>';
    }
    $resources .= ' >>';

    $objects[$page] = '<< /Type /Page /Parent ' . $pagesRoot . ' 0 R /MediaBox [0 0 595 842] /Contents ' . $contentObj . ' 0 R /Resources ' . $resources . ' >>';
    $objects[$pagesRoot] = '<< /Type /Pages /Kids [' . $page . ' 0 R] /Count 1 >>';
    $objects[$catalog] = '<< /Type /Catalog /Pages ' . $pagesRoot . ' 0 R >>';
    $objects[$fontRegular] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $objects[$fontBold] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

    ksort($objects);
    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $num => $obj) {
        $offsets[$num] = strlen($pdf);
        $pdf .= $num . " 0 obj\n" . $obj . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . $objectNumber . "\n0000000000 65535 f \n";
    for ($i = 1; $i < $objectNumber; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
    }
    $pdf .= "trailer\n<< /Size " . $objectNumber . " /Root " . $catalog . " 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    return $pdf;
}

$pdf = gf_pdf_build($invoice);
$filename = function_exists('girffonInvoicePdfFilename') ? girffonInvoicePdfFilename($invoice) : ('invoice-' . ($invoice['invoice_number'] ?? $invoiceId) . '.pdf');

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=0, must-revalidate');

echo $pdf;
exit;
