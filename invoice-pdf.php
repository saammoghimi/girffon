<?php
require_once __DIR__ . '/backend/invoices/invoice-data.php';

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = girffonInvoiceFetchById($pdo, $invoiceId);

if (!$invoice) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

function girffonInvoicePdfEscape(string $value): string
{
    return str_replace(["\\", "(", ")", "\r", "\n"], ["\\\\", "\\(", "\\)", ' ', ' '], $value);
}

function girffonInvoiceBuildTextPdf(array $lines): string
{
    $content = "BT\n/F1 12 Tf\n16 TL\n50 790 Td\n";
    foreach ($lines as $index => $line) {
        if ($index > 0) {
            $content .= "T*\n";
        }
        $content .= '(' . girffonInvoicePdfEscape($line) . ") Tj\n";
    }
    $content .= 'ET';

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        4 => '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
        5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];
    foreach ($objects as $number => $object) {
        $offsets[$number] = strlen($pdf);
        $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    for ($index = 1; $index <= 5; $index += 1) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
    }
    $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";
    return $pdf;
}

function girffonInvoiceFindHeadlessBrowser(): ?string
{
    $candidates = [
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return null;
}

$pdf = '';
$headlessBrowser = girffonInvoiceFindHeadlessBrowser();
if ($headlessBrowser) {
    $query = http_build_query([
        'id' => (int) ($invoice['id'] ?? 0),
        'autoprint' => '0',
        'toolbar' => '0',
        'view' => 'pdf',
    ]);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $url = $scheme . '://' . $host . girffonInvoiceUrl('invoice-view.php') . '?' . $query;

    $tempBase = tempnam(sys_get_temp_dir(), 'girffon-invoice-');
    if ($tempBase !== false) {
        $tempPdf = $tempBase . '.pdf';
        @unlink($tempBase);

        $command = '"' . $headlessBrowser . '" --headless=new --disable-gpu --run-all-compositor-stages-before-draw --print-to-pdf="' . $tempPdf . '" --print-to-pdf-no-header "' . $url . '"';
        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);

        if ($exitCode === 0 && is_file($tempPdf) && filesize($tempPdf) > 1024) {
            $pdf = (string) file_get_contents($tempPdf);
        }

        if (is_file($tempPdf)) {
            @unlink($tempPdf);
        }
    }
}

if ($pdf === '') {
    $fallbackQuery = http_build_query([
        'id' => (int) ($invoice['id'] ?? 0),
        'toolbar' => '0',
        'autoprint' => '1',
        'view' => 'pdf',
    ]);
    header('Location: ' . girffonInvoiceUrl('invoice-view.php') . '?' . $fallbackQuery, true, 302);
    exit;
}

$filename = girffonInvoicePdfFilename($invoice);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=0, must-revalidate');

echo $pdf;
exit;