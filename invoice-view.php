<?php
require_once __DIR__ . '/backend/invoices/invoice-data.php';

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = girffonInvoiceFetchById($pdo, $invoiceId);

if (!$invoice) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

$autoPrint = !isset($_GET['autoprint']) || $_GET['autoprint'] !== '0';
$showToolbar = !isset($_GET['toolbar']) || $_GET['toolbar'] !== '0';
$viewMode = trim((string) ($_GET['view'] ?? 'screen'));
$invoiceIdQuery = rawurlencode((string) ($invoice['id'] ?? '0'));
$invoiceViewUrl = girffonInvoiceUrl('invoice-view.php?id=' . $invoiceIdQuery . '&autoprint=0');
$invoicePrintUrl = girffonInvoiceUrl('invoice-print.php?id=' . $invoiceIdQuery);
$invoicePdfUrl = girffonInvoiceUrl('invoice-pdf.php?id=' . $invoiceIdQuery);
$company = is_array($invoice['company'] ?? null) ? $invoice['company'] : girffonInvoiceCompanyProfile();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo girffonInvoiceEscape((string) ($invoice['invoice_number'] ?? 'Invoice')); ?></title>
  <style>
    @page {
      size: A4 portrait;
      margin: 9mm;
    }
    :root {
      color-scheme: light;
      --invoice-black: #17181c;
      --invoice-gold: #d1a12b;
      --invoice-gold-soft: #f6ecd0;
      --invoice-border: #e6c981;
      --invoice-text: #24262b;
      --invoice-muted: #77736a;
      --invoice-surface: #fffdf8;
      --invoice-page: #f5efe2;
      --invoice-toolbar: rgba(23, 24, 28, 0.96);
    }
    * { box-sizing: border-box; }
    html { background: var(--invoice-page); }
    body {
      margin: 0;
      padding: 18px;
      font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
      background: radial-gradient(circle at top left, rgba(209, 161, 43, 0.16), transparent 28%), var(--invoice-page);
      color: var(--invoice-text);
      font-size: 13px;
      line-height: 1.35;
    }
    .invoice-toolbar {
      width: min(100%, 860px);
      margin: 0 auto 14px;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 12px;
      align-items: center;
      padding: 12px 14px;
      border-radius: 14px;
      background: var(--invoice-toolbar);
      color: #fff;
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.18);
    }
    .invoice-toolbar-copy strong {
      display: block;
      font-size: 0.95rem;
      margin-bottom: 3px;
    }
    .invoice-toolbar-copy span {
      color: rgba(255,255,255,0.76);
      font-size: 0.76rem;
    }
    .invoice-toolbar-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .invoice-toolbar-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 40px;
      padding: 0 16px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.16);
      background: rgba(255,255,255,0.08);
      color: #fff;
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 700;
      letter-spacing: 0.02em;
    }
    .invoice-toolbar-button.is-gold {
      background: linear-gradient(135deg, #d1a12b 0%, #e7bc57 100%);
      color: #1f1a10;
      border-color: rgba(209,161,43,0.55);
    }
    .invoice-print-shell {
      width: min(100%, 860px);
      margin: 0 auto;
      background: var(--invoice-surface);
      border: 1px solid rgba(209, 161, 43, 0.42);
      border-radius: 18px;
      padding: 18px;
      box-shadow: 0 18px 42px rgba(75, 57, 18, 0.1);
      overflow: hidden;
    }
    .invoice-print-head {
      display: grid;
      grid-template-columns: minmax(0, 1.08fr) minmax(280px, 0.92fr);
      gap: 14px;
      align-items: start;
      margin-bottom: 14px;
      break-inside: avoid;
      page-break-inside: avoid;
    }
    .invoice-brand-card {
      display: grid;
      grid-template-columns: 1fr;
      gap: 10px;
      padding: 6px 2px 4px;
      background: transparent;
    }
    .invoice-brand-logo {
      width: 220px;
      max-width: 100%;
      height: auto;
      object-fit: contain;
      display: block;
      image-rendering: -webkit-optimize-contrast;
      image-rendering: high-quality;
    }
    .invoice-brand-copy { display: grid; gap: 4px; max-width: 280px; }
    .invoice-brand-copy h1 {
      margin: 0;
      font-family: Georgia, "Times New Roman", serif;
      font-size: clamp(1.2rem, 1.8vw, 1.7rem);
      line-height: 1.1;
      letter-spacing: -0.02em;
      color: var(--invoice-text);
    }
    .invoice-company-tag {
      margin: 0;
      color: var(--invoice-muted);
      letter-spacing: 0.14em;
      text-transform: uppercase;
      font-size: 0.58rem;
      font-weight: 700;
    }
    .invoice-company-meta { display: grid; gap: 2px; margin-top: 2px; }
    .invoice-company-meta div { color: var(--invoice-muted); font-size: 0.72rem; }
    .invoice-summary-card {
      border-radius: 14px;
      overflow: hidden;
      border: 1px solid rgba(209, 161, 43, 0.5);
      background: #fffdf8;
      display: grid;
      grid-template-rows: auto 1fr;
    }
    .invoice-summary-head {
      background: linear-gradient(135deg, var(--invoice-black) 0%, #2a2d33 100%);
      color: #ffffff;
      padding: 14px 16px 11px;
    }
    .invoice-summary-head p { margin: 0 0 4px; font-size: 0.56rem; letter-spacing: 0.18em; text-transform: uppercase; color: rgba(255,255,255,0.72); }
    .invoice-summary-head h2 { margin: 0; font-size: clamp(1.2rem, 1.8vw, 1.7rem); line-height: 1; }
    .invoice-summary-body {
      padding: 13px 16px 14px;
      display: grid;
      gap: 8px;
      background: linear-gradient(180deg, #fffdfa 0%, #fbf4e6 100%);
    }
    .invoice-summary-row { display: grid; grid-template-columns: 108px 1fr; gap: 10px; align-items: baseline; }
    .invoice-summary-row strong { font-size: 0.58rem; letter-spacing: 0.05em; text-transform: uppercase; color: var(--invoice-muted); }
    .invoice-summary-row span { font-weight: 700; font-size: 0.73rem; }
    .invoice-pill {
      display: inline-flex; align-items: center; justify-content: center; min-height: 22px; padding: 0 9px;
      border-radius: 999px; background: rgba(209, 161, 43, 0.18); color: #7b5b10;
      border: 1px solid rgba(209, 161, 43, 0.42); font-size: 0.68rem;
    }
    .invoice-grid, .invoice-footer-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(250px, 0.86fr);
      gap: 12px;
      margin-bottom: 12px;
      break-inside: avoid;
      page-break-inside: avoid;
    }
    .invoice-panel, .invoice-footer-note, .invoice-footer-thanks {
      border: 1px solid rgba(209, 161, 43, 0.55);
      border-radius: 14px;
      padding: 12px 14px;
      background: #fffefb;
    }
    .invoice-panel h3, .invoice-footer-note h3, .invoice-footer-thanks h3 {
      margin: 0 0 8px; font-size: 0.62rem; letter-spacing: 0.09em; text-transform: uppercase; color: var(--invoice-gold);
    }
    .invoice-panel p, .invoice-footer-note p, .invoice-footer-thanks p { margin: 0 0 5px; color: var(--invoice-muted); font-size: 0.76rem; }
    .invoice-customer-name { font-size: 0.98rem; font-weight: 700; color: var(--invoice-text); margin-bottom: 5px; }
    .invoice-table-wrap {
      overflow: hidden; border-radius: 14px; border: 1px solid rgba(209, 161, 43, 0.4); background: #fffefb;
      break-inside: avoid; page-break-inside: avoid;
    }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    thead th { background: linear-gradient(135deg, var(--invoice-black) 0%, #2d3037 100%); color: #ffffff; border: 0; font-size: 0.56rem; letter-spacing: 0.06em; text-transform: uppercase; padding: 10px 8px; }
    th, td { padding: 8px 7px; text-align: left; border-bottom: 1px solid #eadfca; vertical-align: top; font-size: 0.72rem; line-height: 1.25; }
    tbody tr:last-child td { border-bottom: 0; }
    .invoice-item-image-cell { width: 92px; }
    .invoice-item-thumbnail, .invoice-item-placeholder {
      width: 80px; height: 80px; border-radius: 10px; border: 1px solid rgba(209, 161, 43, 0.38);
      background: linear-gradient(135deg, #fffdfa 0%, #f7efe0 100%); display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .invoice-item-thumbnail img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .invoice-item-placeholder span { color: var(--invoice-muted); font-size: 0.5rem; letter-spacing: 0.08em; text-transform: uppercase; text-align: center; padding: 0 6px; }
    .invoice-item-product strong { display: block; font-size: 0.8rem; margin-bottom: 3px; }
    .invoice-item-product small { color: var(--invoice-muted); display: block; font-size: 0.58rem; line-height: 1.25; }
    .invoice-totals { display: grid; justify-content: end; margin: 10px 0 12px; break-inside: avoid; page-break-inside: avoid; }
    .invoice-totals-card { min-width: 260px; border-top: 1px solid rgba(209, 161, 43, 0.5); padding-top: 8px; }
    .invoice-total-row { display: flex; justify-content: space-between; gap: 12px; padding: 4px 0; color: var(--invoice-muted); font-size: 0.74rem; }
    .invoice-total-row strong { color: var(--invoice-text); }
    .invoice-total-row.is-grand { margin-top: 5px; padding-top: 8px; border-top: 1px solid rgba(209, 161, 43, 0.38); font-size: 1rem; font-weight: 800; color: var(--invoice-gold); }
    .invoice-signature { margin-top: 8px; font-family: Georgia, "Times New Roman", serif; font-size: 1.1rem; font-style: italic; color: #44311c; }
    .invoice-empty { color: var(--invoice-muted); text-align: center; padding: 14px 8px; font-size: 0.72rem; }
    .invoice-print-shell[data-invoice-view="pdf"] .invoice-item-image-cell {
      width: 72px;
    }
    .invoice-print-shell[data-invoice-view="pdf"] .invoice-item-thumbnail,
    .invoice-print-shell[data-invoice-view="pdf"] .invoice-item-placeholder {
      width: 60px;
      height: 60px;
      border-radius: 0;
    }
    @media (max-width: 760px) {
      body { padding: 10px; }
      .invoice-toolbar, .invoice-print-shell { width: 100%; }
      .invoice-toolbar { padding: 10px; }
      .invoice-toolbar-actions { width: 100%; }
      .invoice-toolbar-button { flex: 1 1 100%; }
      .invoice-print-head {
        grid-template-columns: minmax(0, 1.06fr) minmax(250px, 0.94fr);
        gap: 12px;
      }
      .invoice-summary-row { grid-template-columns: 1fr; gap: 4px; }
      .invoice-totals { justify-content: stretch; }
      .invoice-totals-card { min-width: 0; }
      .invoice-table-wrap { overflow-x: auto; }
      table { min-width: 720px; }
    }
    @media (max-width: 640px) {
      .invoice-print-head, .invoice-grid, .invoice-footer-grid { grid-template-columns: 1fr; }
      .invoice-brand-logo { width: 180px; }
    }
    @media print {
      html, body { width: 210mm; min-height: 297mm; background: #ffffff; }
      body { padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .invoice-toolbar { display: none !important; }
      .invoice-print-shell[data-invoice-view="pdf"] .invoice-item-image-cell {
        width: 72px;
      }
      .invoice-print-shell[data-invoice-view="pdf"] .invoice-item-thumbnail,
      .invoice-print-shell[data-invoice-view="pdf"] .invoice-item-placeholder {
        width: 60px;
        height: 60px;
        border-radius: 0;
      }
      .invoice-print-shell {
        box-shadow: none; border: 0; border-radius: 0; max-width: none; width: auto; padding: 0; overflow: visible;
      }
      .invoice-print-head, .invoice-grid, .invoice-table-wrap, .invoice-totals, .invoice-footer-grid, tbody tr {
        break-inside: avoid; page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>
<?php if ($showToolbar): ?>
  <div class="invoice-toolbar">
    <div class="invoice-toolbar-copy">
      <strong><?php echo girffonInvoiceEscape((string) ($invoice['invoice_number'] ?? 'Invoice')); ?></strong>
      <span>Professional invoice view optimized for A4 print and PDF export.</span>
    </div>
    <div class="invoice-toolbar-actions">
      <a class="invoice-toolbar-button" href="<?php echo girffonInvoiceEscape($invoiceViewUrl); ?>">View</a>
      <a class="invoice-toolbar-button" href="<?php echo girffonInvoiceEscape($invoicePrintUrl); ?>">Print</a>
      <a class="invoice-toolbar-button is-gold" href="<?php echo girffonInvoiceEscape($invoicePdfUrl); ?>">Download PDF</a>
    </div>
  </div>
<?php endif; ?>
  <main class="invoice-print-shell" data-invoice-view="<?php echo girffonInvoiceEscape($viewMode); ?>">
    <header class="invoice-print-head">
      <section class="invoice-brand-card">
        <img class="invoice-brand-logo" src="<?php echo girffonInvoiceEscape((string) ($invoice['logo_url'] ?? '')); ?>" alt="GirffoN Premium Clothing Logo">
        <div class="invoice-brand-copy">
          <p class="invoice-company-tag"><?php echo girffonInvoiceEscape((string) ($company['tagline'] ?? 'Premium Clothing')); ?></p>
          <h1><?php echo girffonInvoiceEscape((string) ($company['name'] ?? 'GirffoN Premium Clothing')); ?></h1>
          <div class="invoice-company-meta">
            <div>Email: <?php echo girffonInvoiceEscape((string) ($company['email'] ?? '-')); ?></div>
            <div>Website: <?php echo girffonInvoiceEscape((string) ($company['website'] ?? '-')); ?></div>
            <div>Phone: <?php echo girffonInvoiceEscape((string) ($company['phone'] ?? '-')); ?></div>
            <div>Location: <?php echo girffonInvoiceEscape((string) ($company['location'] ?? '-')); ?></div>
          </div>
        </div>
      </section>

      <section class="invoice-summary-card">
        <div class="invoice-summary-head">
          <p>Invoice</p>
          <h2><?php echo girffonInvoiceEscape((string) ($invoice['invoice_number'] ?? '')); ?></h2>
        </div>
        <div class="invoice-summary-body">
          <div class="invoice-summary-row"><strong>Invoice Date</strong><span><?php echo girffonInvoiceEscape(girffonInvoiceFormatDate($invoice['created_at'] ?? '')); ?></span></div>
          <div class="invoice-summary-row"><strong>Order Number</strong><span><?php echo girffonInvoiceEscape((string) ($invoice['order_number'] ?? '-')); ?></span></div>
          <div class="invoice-summary-row"><strong>Invoice Status</strong><span><span class="invoice-pill"><?php echo girffonInvoiceEscape((string) ($invoice['status_label'] ?? 'Pending')); ?></span></span></div>
          <div class="invoice-summary-row"><strong>Customer Name</strong><span><?php echo girffonInvoiceEscape((string) ($invoice['customer_name'] ?? '-')); ?></span></div>
          <div class="invoice-summary-row"><strong>Customer Email</strong><span><?php echo girffonInvoiceEscape((string) ($invoice['customer_email'] ?? '-')); ?></span></div>
        </div>
      </section>
    </header>

    <section class="invoice-grid">
      <article class="invoice-panel">
        <h3>Bill To</h3>
        <div class="invoice-customer-name"><?php echo girffonInvoiceEscape((string) ($invoice['customer_name'] ?? '-')); ?></div>
        <p><?php echo girffonInvoiceEscape((string) ($invoice['customer_email'] ?? '-')); ?></p>
      </article>

      <article class="invoice-panel">
        <h3>Invoice Block</h3>
        <p><strong>Payment Status:</strong> <?php echo girffonInvoiceEscape((string) ($invoice['payment_status_label'] ?? 'Pending')); ?></p>
        <p><strong>Order Status:</strong> <?php echo girffonInvoiceEscape((string) ($invoice['order_status_label'] ?? '-')); ?></p>
        <p><strong>Tracking Code:</strong> <?php echo girffonInvoiceEscape((string) (($invoice['tracking_code'] ?? '') !== '' ? $invoice['tracking_code'] : '-')); ?></p>
      </article>
    </section>

    <section class="invoice-table-wrap">
      <table>
        <thead>
          <tr>
            <th class="invoice-item-image-cell">Image</th>
            <th>Product</th>
            <th>SKU</th>
            <th>Size</th>
            <th>Color</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($invoice['items'])): ?>
            <?php foreach ($invoice['items'] as $item): ?>
              <tr>
                <td class="invoice-item-image-cell">
                  <?php if (!empty($item['image_url'])): ?>
                    <div class="invoice-item-thumbnail"><img src="<?php echo girffonInvoiceEscape((string) $item['image_url']); ?>" alt="<?php echo girffonInvoiceEscape((string) ($item['product_name'] ?? 'Product image')); ?>"></div>
                  <?php else: ?>
                    <div class="invoice-item-placeholder"><span>No Image</span></div>
                  <?php endif; ?>
                </td>
                <td class="invoice-item-product">
                  <strong><?php echo girffonInvoiceEscape((string) ($item['product_name'] ?? '-')); ?></strong>
                  <small>GirffoN Premium Clothing item</small>
                </td>
                <td><?php echo girffonInvoiceEscape((string) (($item['sku'] ?? '') !== '' ? $item['sku'] : '-')); ?></td>
                <td><?php echo girffonInvoiceEscape((string) (($item['size'] ?? '') !== '' ? $item['size'] : '-')); ?></td>
                <td><?php echo girffonInvoiceEscape((string) (($item['color'] ?? '') !== '' ? $item['color'] : '-')); ?></td>
                <td><?php echo girffonInvoiceEscape((string) ($item['quantity'] ?? 1)); ?></td>
                <td><?php echo girffonInvoiceEscape(girffonInvoiceFormatCurrency($item['price'] ?? 0)); ?></td>
                <td><?php echo girffonInvoiceEscape(girffonInvoiceFormatCurrency($item['line_total'] ?? 0)); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="8" class="invoice-empty">No line items found for this invoice.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <section class="invoice-totals">
      <div class="invoice-totals-card">
        <div class="invoice-total-row"><span>Subtotal</span><strong><?php echo girffonInvoiceEscape(girffonInvoiceFormatCurrency($invoice['subtotal_amount'] ?? 0)); ?></strong></div>
        <div class="invoice-total-row"><span>Shipping</span><strong><?php echo girffonInvoiceEscape(girffonInvoiceFormatCurrency($invoice['shipping_amount'] ?? 0)); ?></strong></div>
        <div class="invoice-total-row"><span>Tax</span><strong><?php echo girffonInvoiceEscape(girffonInvoiceFormatCurrency($invoice['tax_amount'] ?? 0)); ?></strong></div>
        <div class="invoice-total-row is-grand"><span>Grand Total</span><strong><?php echo girffonInvoiceEscape(girffonInvoiceFormatCurrency($invoice['total_amount'] ?? 0)); ?></strong></div>
      </div>
    </section>

    <section class="invoice-footer-grid">
      <article class="invoice-footer-note">
        <h3>Thank You Note</h3>
        <p>Thank you for choosing GirffoN Premium Clothing.</p>
        <p>We appreciate your business and look forward to serving you again.</p>
      </article>
      <article class="invoice-footer-thanks">
        <h3>GirffoN</h3>
        <p>Thank you for your order and for trusting our premium clothing studio.</p>
        <div class="invoice-signature">GirffoN Team</div>
      </article>
    </section>
  </main>
<?php if ($autoPrint): ?>
  <script>
    window.addEventListener('load', function () {
      window.print();
    });
  </script>
<?php endif; ?>
</body>
</html>