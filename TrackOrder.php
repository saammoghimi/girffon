<?php
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/orders/track-order.php';

function girffonTrackEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function girffonTrackFormatDate(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d M Y, H:i', $timestamp);
}

function girffonTrackFormatMoney($value): string
{
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
}

function girffonTrackFormatShortDate(?string $value): string
{
  if (!$value) {
    return '-';
  }

  $timestamp = strtotime($value);
  if ($timestamp === false) {
    return $value;
  }

  return date('d M Y', $timestamp);
}

function girffonTrackRenderTimeline(array $timeline): string
{
  if (!$timeline) {
    return '';
  }

  $items = '';
  foreach ($timeline as $step) {
    $classes = ['gf-track-timeline-step'];
    if (!empty($step['completed'])) {
      $classes[] = 'is-complete';
    }
    if (!empty($step['current'])) {
      $classes[] = 'is-current';
    }

    $items .= '<article class="' . implode(' ', $classes) . '">'
      . '<span class="gf-track-timeline-dot" aria-hidden="true"></span>'
      . '<div><strong>' . girffonTrackEscape((string) ($step['label'] ?? 'Update')) . '</strong>'
      . '<p>' . girffonTrackEscape(!empty($step['current']) ? 'Current stage' : (!empty($step['completed']) ? 'Completed' : 'Pending')) . '</p></div>'
      . '</article>';
  }

  return '<div class="gf-track-timeline">' . $items . '</div>';
}

$searchedOrderNumber = strtoupper(trim((string) ($_GET['order_number'] ?? '')));
$trackState = '';
$trackMessage = '';
$order = null;
$orderItems = [];

if ($searchedOrderNumber !== '') {
  $trackResult = girffonTrackOrderLookup($pdo, $searchedOrderNumber);

  if ($trackResult) {
    $order = $trackResult['order'];
    $orderItems = $trackResult['items'];
        $trackState = 'success';
        $trackMessage = 'Order details found.';
    } else {
        $trackState = 'error';
        $trackMessage = 'Order not found. Please check your order number and try again.';
    }
}

$itemsMarkup = '';
if ($orderItems) {
    foreach ($orderItems as $item) {
        $imagePath = trim((string) ($item['image'] ?? ''));
        $imageUrl = $imagePath !== '' ? girffonTrackEscape($imagePath) : 'Image/Logo/Logo.png';
        $itemsMarkup .= '<article class="gf-track-order-item">'
      . '<div class="gf-track-order-thumb"><img src="' . $imageUrl . '" alt="' . girffonTrackEscape((string) ($item['product_name'] ?? 'Product image')) . '"></div>'
            . '<div class="gf-track-order-copy">'
      . '<h4>' . girffonTrackEscape((string) ($item['product_name'] ?? 'GirffoN Product')) . '</h4>'
            . '<div class="gf-track-order-copy-grid">'
            . '<p><span>SKU</span><strong>' . girffonTrackEscape((string) ($item['sku'] ?? '-')) . '</strong></p>'
            . '<p><span>Size</span><strong>' . girffonTrackEscape((string) ($item['size'] ?? '-')) . '</strong></p>'
            . '<p><span>Color</span><strong>' . girffonTrackEscape((string) ($item['color'] ?? '-')) . '</strong></p>'
            . '<p><span>Quantity</span><strong>' . girffonTrackEscape((string) ($item['quantity'] ?? '1')) . '</strong></p>'
            . '<p><span>Price</span><strong>' . girffonTrackEscape(girffonTrackFormatMoney($item['price'] ?? 0)) . '</strong></p>'
            . '</div></div></article>';
    }
}

$invoiceButtonMarkup = '';
if (!empty($order['invoice_id'])) {
  $invoiceNumberLabel = trim((string) ($order['invoice_number'] ?? ''));
  $invoiceButtonMarkup = '<a class="gf-track-submit gf-track-invoice-button" href="/GirffoN/invoice-pdf.php?id=' . rawurlencode((string) $order['invoice_id']) . '">Download Invoice' . ($invoiceNumberLabel !== '' ? ' (' . girffonTrackEscape($invoiceNumberLabel) . ')' : '') . '</a>';
}

$resultMarkup = '';
if ($order) {
    $timelineMarkup = girffonTrackRenderTimeline($order['timeline'] ?? []);
    $estimatedDeliveryLabel = girffonTrackFormatShortDate((string) ($order['estimated_delivery_date'] ?? ''));
    $updateNoteMarkup = '';
    if (!empty($order['admin_note'])) {
        $updateNoteMarkup = '<div class="gf-track-order-note"><span>Latest Update</span><strong>' . nl2br(girffonTrackEscape((string) $order['admin_note'])) . '</strong></div>';
    }

    $resultMarkup = '<section class="gf-track-order-panel">'
        . '<div class="gf-track-order-card">'
        . '<div class="gf-track-order-card-head"><div><p class="gf-track-order-kicker">Order Overview</p><h3>' . girffonTrackEscape((string) $order['order_number']) . '</h3></div>' . $invoiceButtonMarkup . '</div>'
        . '<div class="gf-track-order-meta-grid">'
        . '<article><span>Customer Name</span><strong>' . girffonTrackEscape((string) ($order['customer_name'] ?? '-')) . '</strong></article>'
        . '<article><span>Created At</span><strong>' . girffonTrackEscape(girffonTrackFormatDate((string) ($order['created_at'] ?? ''))) . '</strong></article>'
        . '<article><span>Order Status</span><strong>' . girffonTrackEscape((string) ($order['order_status_label'] ?? '-')) . '</strong></article>'
        . '<article><span>Payment Status</span><strong>' . girffonTrackEscape((string) ($order['payment_status_label'] ?? '-')) . '</strong></article>'
        . '<article><span>Tracking Number</span><strong>' . girffonTrackEscape((string) ($order['tracking_code'] ?: '-')) . '</strong></article>'
        . '<article><span>Courier</span><strong>' . girffonTrackEscape((string) (($order['courier_name'] ?? '') !== '' ? $order['courier_name'] : '-')) . '</strong></article>'
        . '<article><span>Estimated Delivery</span><strong>' . girffonTrackEscape($estimatedDeliveryLabel) . '</strong></article>'
        . '<article><span>Invoice Number</span><strong>' . girffonTrackEscape((string) (($order['invoice_number'] ?? '') !== '' ? $order['invoice_number'] : 'Not issued yet')) . '</strong></article>'
        . '<article><span>Total</span><strong>' . girffonTrackEscape(girffonTrackFormatMoney($order['total'] ?? 0)) . '</strong></article>'
        . '</div>' . $updateNoteMarkup . '</div>'
        . '<div class="gf-track-order-card gf-track-timeline-card"><div class="gf-track-items-head"><div><p class="gf-track-order-kicker">Order Progress</p><h3>Timeline</h3></div><p>Follow each stage from confirmation to delivery.</p></div>' . $timelineMarkup . '</div>'
        . '<div class="gf-track-items-panel"><div class="gf-track-items-head"><h3>Order Items</h3><p>All products saved for this order are shown below.</p></div>'
        . '<div class="gf-track-items-grid">' . $itemsMarkup . '</div></div></section>';
}

$trackBlock = <<<HTML
  <!-- Track Order Block -->
  <div class="cart-container gf-track-page-shell">
    <div class="cart-title gf-track-page-title">
      <i class="fa-solid fa-box"></i> <span>Track Order</span>
    </div>

    <section class="gf-track-page-intro">
      <p>Enter your GirffoN order number to view live order status, payment details, tracking code, saved items, and invoice access.</p>
    </section>

    <section class="gf-track-page-panel">
      <form id="gfTrackPageForm" class="gf-track-form gf-track-page-form" action="TrackOrder.php" method="GET" novalidate>
        <p class="gf-track-help">Use your order number exactly as it appears on your confirmation.</p>

        <label class="gf-track-field" for="gfTrackPageOrderNumber">
          <span>Order Number</span>
          <input type="text" id="gfTrackPageOrderNumber" name="order_number" value="%s" required>
        </label>

        <button type="submit" class="gf-track-submit">Track Order</button>
        <p class="gf-track-status %s">%s</p>
      </form>

      %s
    </section>
  </div>

  <style>
    .gf-track-page-shell {
      width: min(1180px, calc(100%% - 48px));
      display: grid;
      gap: 24px;
    }
    .gf-track-page-title {
      margin-bottom: 0;
    }
    .gf-track-page-intro {
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: linear-gradient(180deg, rgba(255,255,255,0.96) 0%%, rgba(249,250,251,0.94) 100%%);
      border-radius: 18px;
      padding: 24px 28px;
      box-shadow: 0 18px 42px rgba(15, 23, 42, 0.06);
      color: #4b5563;
      font-weight: 500;
    }
    .gf-track-page-panel {
      display: grid;
      gap: 24px;
    }
    .gf-track-page-form {
      position: static;
      inset: auto;
      width: 100%%;
      max-width: none;
      opacity: 1;
      transform: none;
      visibility: visible;
      pointer-events: auto;
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }
    .gf-track-order-panel {
      display: grid;
      gap: 24px;
    }
    .gf-track-order-card,
    .gf-track-items-panel {
      border-radius: 20px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: #ffffff;
      box-shadow: 0 22px 54px rgba(15, 23, 42, 0.07);
      padding: 28px;
    }
    .gf-track-order-card-head,
    .gf-track-items-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
    }
    .gf-track-order-kicker {
      margin: 0 0 6px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      font-size: 0.78rem;
      color: #b45309;
      font-weight: 700;
    }
    .gf-track-order-card-head h3,
    .gf-track-items-head h3 {
      margin: 0;
      color: #111827;
      font-size: 1.45rem;
    }
    .gf-track-items-head p {
      margin: 6px 0 0;
      color: #6b7280;
    }
    .gf-track-order-meta-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
    }
    .gf-track-order-meta-grid article {
      border-radius: 16px;
      background: #f9fafb;
      border: 1px solid rgba(15, 23, 42, 0.06);
      padding: 16px;
      display: grid;
      gap: 8px;
    }
    .gf-track-order-meta-grid span,
    .gf-track-order-copy-grid span {
      color: #6b7280;
      font-size: 0.85rem;
      display: block;
    }
    .gf-track-order-meta-grid strong,
    .gf-track-order-copy-grid strong {
      color: #111827;
      font-size: 1rem;
    }
    .gf-track-items-grid {
      display: grid;
      gap: 16px;
    }
    .gf-track-order-item {
      display: grid;
      grid-template-columns: 88px minmax(0, 1fr);
      gap: 18px;
      align-items: center;
      border-radius: 18px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: #fcfcfd;
      padding: 18px;
    }
    .gf-track-order-thumb {
      width: 88px;
      height: 88px;
      border-radius: 16px;
      overflow: hidden;
      background: #f3f4f6;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .gf-track-order-thumb img {
      width: 100%%;
      height: 100%%;
      object-fit: cover;
      display: block;
    }
    .gf-track-order-copy h4 {
      margin: 0 0 12px;
      color: #111827;
      font-size: 1.05rem;
    }
    .gf-track-order-copy-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
      gap: 12px;
    }
    .gf-track-order-copy-grid p {
      margin: 0;
    }
    .gf-track-invoice-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      width: auto;
      min-width: 190px;
    }
    .gf-track-order-note {
      margin-top: 18px;
      border-radius: 16px;
      padding: 16px 18px;
      background: #fff7ed;
      border: 1px solid rgba(180, 83, 9, 0.14);
      display: grid;
      gap: 8px;
    }
    .gf-track-order-note span {
      color: #b45309;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .gf-track-order-note strong {
      color: #7c2d12;
      font-size: 0.96rem;
      line-height: 1.7;
      font-weight: 600;
    }
    .gf-track-timeline {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 14px;
    }
    .gf-track-timeline-step {
      display: grid;
      gap: 10px;
      padding: 18px;
      border-radius: 18px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: #f8fafc;
    }
    .gf-track-timeline-step.is-complete {
      background: #ecfdf5;
      border-color: rgba(5, 150, 105, 0.18);
    }
    .gf-track-timeline-step.is-current {
      background: #fff7ed;
      border-color: rgba(180, 83, 9, 0.2);
    }
    .gf-track-timeline-dot {
      width: 14px;
      height: 14px;
      border-radius: 999px;
      background: #cbd5e1;
      box-shadow: 0 0 0 5px rgba(203, 213, 225, 0.22);
    }
    .gf-track-timeline-step.is-complete .gf-track-timeline-dot {
      background: #059669;
      box-shadow: 0 0 0 5px rgba(5, 150, 105, 0.16);
    }
    .gf-track-timeline-step.is-current .gf-track-timeline-dot {
      background: #b45309;
      box-shadow: 0 0 0 5px rgba(180, 83, 9, 0.14);
    }
    .gf-track-timeline-step strong {
      color: #111827;
      display: block;
      margin-bottom: 6px;
    }
    .gf-track-timeline-step p {
      margin: 0;
      color: #6b7280;
      font-size: 0.88rem;
    }
    @media (max-width: 720px) {
      .gf-track-page-shell {
        width: min(100%% - 24px, 100%%);
      }
      .gf-track-order-item {
        grid-template-columns: 1fr;
      }
      .gf-track-order-thumb {
        width: 72px;
        height: 72px;
      }
      .gf-track-order-card-head,
      .gf-track-items-head {
        flex-direction: column;
      }
      .gf-track-invoice-button {
        width: 100%%;
      }
    }
  </style>
HTML;

$trackBlock = sprintf(
    $trackBlock,
    girffonTrackEscape($searchedOrderNumber),
    $trackState === 'success' ? 'is-success' : ($trackState === 'error' ? 'is-error' : ''),
    girffonTrackEscape($trackMessage),
    $resultMarkup
);

$template = file_get_contents(__DIR__ . '/CartTest.html');
if ($template === false) {
    http_response_code(500);
    echo 'Track order template not found.';
    exit;
}

$template = str_replace('<title>GirffoN - Custom T-Shirt Design & Online Store</title>', '<title>GirffoN - Track Order</title>', $template);
$template = str_replace('<body class="cart-test-page">', '<body class="track-order-page">', $template);

$blockStartMarker = '  <!-- Cart Test Block -->';
$blockEndMarker = '<!-- Settings Panel -->';
$startPosition = strpos($template, $blockStartMarker);
$endPosition = strpos($template, $blockEndMarker);

if ($startPosition === false || $endPosition === false || $endPosition <= $startPosition) {
    http_response_code(500);
    echo 'Unable to build track order page template.';
    exit;
}

$template = substr($template, 0, $startPosition) . $trackBlock . PHP_EOL . PHP_EOL . substr($template, $endPosition);

$cartOnlyScriptMarker = '  <!-- Modal logic for Add Product -->';
$cartOnlyScriptPosition = strpos($template, $cartOnlyScriptMarker);
if ($cartOnlyScriptPosition !== false) {
    $bodyClosePosition = strpos($template, '</body>', $cartOnlyScriptPosition);
    if ($bodyClosePosition !== false) {
        $template = substr($template, 0, $cartOnlyScriptPosition) . substr($template, $bodyClosePosition);
    }
}

echo $template;