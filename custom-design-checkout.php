<?php
require_once __DIR__ . '/backend/profile/common.php';
require_once __DIR__ . '/backend/admin/custom-design-orders-data.php';
require_once __DIR__ . '/backend/utils/order-confirmation-mailer.php';

function girffonCustomDesignCheckoutEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function girffonCustomDesignCheckoutPrice(float $amount): string
{
    return 'EUR ' . number_format($amount, 2, '.', ',');
}

function girffonCustomDesignCheckoutOwnsOrder(array $order, array $user): bool
{
    $orderUserId = (int) ($order['user_id'] ?? 0);
    $userId = (int) ($user['id'] ?? 0);
    if ($orderUserId > 0 && $userId > 0) {
        return $orderUserId === $userId;
    }

    $orderEmail = strtolower(trim((string) ($order['customer_email'] ?? '')));
    $userEmail = strtolower(trim((string) ($user['email'] ?? '')));

    return $orderEmail !== '' && $orderEmail === $userEmail;
}

function girffonCustomDesignCheckoutSummary(array $order): array
{
    $summary = is_array($order['checkout_summary'] ?? null) ? $order['checkout_summary'] : [];
    $quantity = max(1, (int) ($summary['quantity'] ?? 1));
    $unitTotal = (float) ($summary['unit_total'] ?? 0);
    $orderTotal = (float) ($summary['order_total'] ?? 0);
    if ($orderTotal <= 0 && $unitTotal > 0) {
        $orderTotal = $unitTotal * $quantity;
    }

    return [
        'quantity' => $quantity,
        'unit_total' => $unitTotal,
        'order_total' => $orderTotal,
        'color' => trim((string) ($summary['color'] ?? '')),
        'size' => trim((string) ($summary['size'] ?? '')),
    ];
}

function girffonCustomDesignCheckoutReference(): string
{
    try {
        $suffix = (string) random_int(100000, 999999);
    } catch (Throwable $exception) {
        $suffix = substr((string) mt_rand(), 0, 6);
    }

    return 'CDP-' . date('YmdHis') . '-' . $suffix;
}

$orderId = (int) ($_POST['order'] ?? $_GET['order'] ?? 0);
$redirectUrl = '/GirffoN/custom-design-checkout.php?order=' . $orderId;
$userId = girffonProfileCurrentUserId();

if ($userId <= 0) {
    header('Location: /GirffoN/backend/auth/require-login.php?redirect=' . rawurlencode($redirectUrl));
    exit;
}

$user = girffonProfileFetchUserById($pdo, $userId);
if (!$user) {
    http_response_code(404);
    echo 'Customer account not found.';
    exit;
}

$order = $orderId > 0 ? girffonAdminFetchCustomDesignOrderDetail($pdo, $orderId) : null;
if (!$order || !girffonCustomDesignCheckoutOwnsOrder($order, $user)) {
    http_response_code(404);
    echo 'Custom design order not found.';
    exit;
}

$normalizedUser = girffonProfileNormalizeUserRow($user);
$summary = girffonCustomDesignCheckoutSummary($order);
$errors = [];
$successMessage = '';

$formValues = [
    'fullName' => trim((string) ($_POST['fullName'] ?? $order['customer_name'] ?? $normalizedUser['name'] ?? '')),
    'email' => trim((string) ($_POST['email'] ?? $order['customer_email'] ?? $normalizedUser['email'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? $order['customer_phone'] ?? $normalizedUser['phone'] ?? '')),
    'address' => trim((string) ($_POST['address'] ?? $normalizedUser['address'] ?? '')),
    'city' => trim((string) ($_POST['city'] ?? $normalizedUser['city'] ?? '')),
    'postalCode' => trim((string) ($_POST['postalCode'] ?? $normalizedUser['postcode'] ?? '')),
    'country' => trim((string) ($_POST['country'] ?? $normalizedUser['country'] ?? '')),
    'number' => trim((string) ($_POST['number'] ?? '')),
    'expiry' => trim((string) ($_POST['expiry'] ?? '')),
    'cvc' => trim((string) ($_POST['cvc'] ?? '')),
];

$currentStatus = strtolower(trim((string) ($order['status'] ?? 'new')));
$alreadyPaid = in_array($currentStatus, ['paid', 'paid_review', 'paid_reviewing', 'reviewing', 'approved', 'in_production', 'completed'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyPaid) {
    foreach (['fullName', 'email', 'phone', 'address', 'city', 'postalCode', 'country', 'number', 'expiry', 'cvc'] as $field) {
        if ($formValues[$field] === '') {
            $errors[] = 'Please complete all payment fields.';
            break;
        }
    }

    $cardDigits = preg_replace('/\D+/', '', $formValues['number']);
    if ($cardDigits === '' || strlen($cardDigits) < 12 || strlen($cardDigits) > 19) {
        $errors[] = 'Please enter a valid card number.';
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $formValues['expiry'])) {
        $errors[] = 'Expiry must use MM/YY format.';
    }

    if (!preg_match('/^[0-9]{3,4}$/', $formValues['cvc'])) {
        $errors[] = 'Please enter a valid CVC.';
    }

    if (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!$errors) {
        $paymentSaved = girffonAdminUpdateCustomDesignOrderPayment($pdo, (int) $order['id'], 'paid_review', [
            'method' => 'card',
            'transaction_reference' => girffonCustomDesignCheckoutReference(),
            'amount' => $summary['order_total'],
            'currency' => 'EUR',
            'last4' => substr($cardDigits, -4),
            'expiry' => $formValues['expiry'],
            'customer' => [
                'name' => $formValues['fullName'],
                'email' => $formValues['email'],
                'phone' => $formValues['phone'],
            ],
            'billing_address' => [
                'address' => $formValues['address'],
                'city' => $formValues['city'],
                'postal_code' => $formValues['postalCode'],
                'country' => $formValues['country'],
            ],
        ]);

        if ($paymentSaved) {
      $paidOrder = girffonAdminFetchCustomDesignOrderDetail($pdo, (int) $order['id']);
      if ($paidOrder) {
        try {
          girffonSendCustomDesignPaymentEmail([
            'customer_name' => (string) ($paidOrder['customer_name'] ?? $formValues['fullName']),
            'customer_email' => (string) ($paidOrder['customer_email'] ?? $formValues['email']),
            'customer_phone' => (string) ($paidOrder['customer_phone'] ?? $formValues['phone']),
            'order_number' => (string) ($paidOrder['order_code'] ?? ''),
            'product_name' => (string) ($paidOrder['product_name'] ?? ''),
            'total' => (float) (($paidOrder['checkout_summary']['order_total'] ?? 0) ?: $summary['order_total']),
            'size_lines' => $paidOrder['size_lines'] ?? [],
            'preview_views' => $paidOrder['preview_views'] ?? [],
            'uploads' => $paidOrder['uploads'] ?? [],
            'add_design' => $paidOrder['add_design'] ?? [],
          ]);
        } catch (Throwable $throwable) {
          $mailConfig = function_exists('girffonMailConfig') ? girffonMailConfig() : [];
          if (function_exists('girffonOrderMailDebugLog')) {
            girffonOrderMailDebugLog($mailConfig, 'Custom design payment email failed: ' . $throwable->getMessage());
          }
        }
      }
            header('Location: /GirffoN/ProfilePage.php?customDesignPayment=success&order=' . (int) $order['id']);
            exit;
        }

        $errors[] = 'Unable to complete the payment for this custom design order.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $alreadyPaid) {
    $successMessage = 'This custom design order has already been paid.';
}

$previewViews = [];
foreach ([
  'front' => 'Front',
  'back' => 'Back',
  'right' => 'Right sleeve',
  'left' => 'Left sleeve',
] as $previewKey => $previewLabel) {
  $previewPath = '';
  if (is_array($order['preview_views'][$previewKey] ?? null)) {
    $previewPath = trim((string) (($order['preview_views'][$previewKey]['path'] ?? '')));
  }

  $previewViews[$previewKey] = [
    'label' => $previewLabel,
    'path' => $previewPath,
  ];
}
$sizeLines = is_array($order['size_lines'] ?? null) ? $order['size_lines'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Custom Design Checkout</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="CSS/style.css">
  <link rel="stylesheet" href="CSS/cart-block.css">
  <style>
    :root {
      --gf-checkout-cream: #f7f1e8;
      --gf-checkout-cream-strong: #f1e8db;
      --gf-checkout-white: #ffffff;
      --gf-checkout-gold: #c79a2b;
      --gf-checkout-gold-strong: #ad8119;
      --gf-checkout-ink: #222222;
      --gf-checkout-muted: #6b675f;
      --gf-checkout-line: rgba(87, 67, 27, 0.12);
      --gf-checkout-shadow: 0 20px 50px rgba(76, 55, 22, 0.10);
    }

    * {
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(180deg, #f7f1e8 0%, #fffdf9 100%);
      color: var(--gf-checkout-ink);
      margin: 0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    .gf-custom-checkout {
      max-width: 1240px;
      margin: 0 auto;
      padding: 48px 24px 72px;
    }

    .gf-custom-checkout-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 20px;
      margin-bottom: 28px;
    }

    .gf-custom-checkout-kicker {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 15px;
      border-radius: 999px;
      background: rgba(255, 196, 58, 0.18);
      color: #8a5f00;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      font-size: 0.78rem;
    }

    .gf-custom-checkout-header h1 {
      margin: 14px 0 10px;
      font-size: clamp(2rem, 4vw, 3rem);
      line-height: 1.05;
    }

    .gf-custom-checkout-header p {
      margin: 0;
      max-width: 700px;
      color: var(--gf-checkout-muted);
      line-height: 1.6;
    }

    .gf-custom-checkout-order {
      padding: 16px 20px;
      border-radius: 20px;
      background: rgba(255, 255, 255, 0.92);
      border: 1px solid var(--gf-checkout-line);
      box-shadow: var(--gf-checkout-shadow);
      min-width: 240px;
      text-align: right;
    }

    .gf-custom-checkout-order strong {
      display: block;
      font-size: 1.05rem;
      margin-bottom: 6px;
    }

    .gf-custom-checkout-grid {
      display: grid;
      grid-template-columns: minmax(340px, 0.92fr) minmax(420px, 1.08fr);
      gap: 28px;
      align-items: start;
    }

    .gf-custom-checkout-card,
    .gf-custom-checkout-payment {
      background: rgba(255, 255, 255, 0.94);
      border: 1px solid var(--gf-checkout-line);
      border-radius: 30px;
      box-shadow: var(--gf-checkout-shadow);
      overflow: hidden;
    }

    .gf-custom-checkout-card {
      padding: 26px;
    }

    .gf-custom-checkout-preview-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 24px;
    }

    .gf-custom-checkout-preview-tile {
      display: flex;
      flex-direction: column;
      gap: 10px;
      min-width: 0;
    }

    .gf-custom-checkout-preview-label {
      display: block;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #6f6246;
      padding-left: 2px;
    }

    .gf-custom-checkout-preview {
      aspect-ratio: 1 / 1;
      width: 100%;
      border-radius: 24px;
      background: linear-gradient(160deg, #f4eee3 0%, #ffffff 100%);
      border: 1px solid rgba(146, 112, 40, 0.12);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      min-height: 148px;
    }

    .gf-custom-checkout-preview img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .gf-custom-checkout-preview-empty {
      color: #8f8473;
      font-weight: 600;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      font-size: 0.85rem;
    }

    .gf-custom-checkout-product-title,
    .gf-custom-checkout-section-title {
      margin: 0;
      color: var(--gf-checkout-ink);
      letter-spacing: -0.02em;
    }

    .gf-custom-checkout-product-title {
      margin-bottom: 12px;
      font-size: 1.6rem;
      line-height: 1.2;
    }

    .gf-custom-checkout-section-title {
      margin-bottom: 14px;
      font-size: 1.08rem;
    }

    .gf-custom-checkout-meta {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
      margin: 20px 0 24px;
    }

    .gf-custom-checkout-meta div,
    .gf-custom-checkout-line-item,
    .gf-custom-checkout-total {
      border: 1px solid rgba(113, 87, 32, 0.12);
      border-radius: 18px;
      padding: 16px 18px;
      background: #fffdfa;
    }

    .gf-custom-checkout-meta span,
    .gf-custom-checkout-line-item span,
    .gf-custom-checkout-total span {
      display: block;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #7a7a7a;
      margin-bottom: 7px;
    }

    .gf-custom-checkout-line-list {
      display: grid;
      gap: 12px;
      margin: 18px 0 0;
    }

    .gf-custom-checkout-line-item strong,
    .gf-custom-checkout-total strong,
    .gf-custom-checkout-meta strong {
      font-size: 1rem;
      color: #202020;
    }

    .gf-custom-checkout-total {
      margin-top: 20px;
      background: linear-gradient(135deg, #28231a 0%, #3a301d 100%);
      color: #fff;
      border-color: transparent;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .gf-custom-checkout-total span,
    .gf-custom-checkout-total strong {
      color: #fff;
    }

    .gf-custom-checkout-payment {
      padding: 30px 30px 32px;
    }

    .gf-custom-checkout-payment h2 {
      margin: 0 0 10px;
      font-size: 1.8rem;
    }

    .gf-custom-checkout-payment p {
      margin: 0 0 20px;
      color: #5f5f5f;
      line-height: 1.6;
    }

    .gf-checkout-form {
      display: block;
    }

    .gf-checkout-form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .gf-checkout-form-grid .gf-checkout-form-span {
      grid-column: 1 / -1;
    }

    .gf-checkout-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 0;
    }

    .gf-checkout-field span {
      display: block;
      font-size: 0.82rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #6f6246;
      padding-left: 2px;
    }

    .gf-checkout-field input {
      appearance: none;
      width: 100%;
      min-height: 56px;
      border: 1px solid rgba(130, 102, 34, 0.16);
      border-radius: 18px;
      background: linear-gradient(180deg, #ffffff 0%, #fffaf1 100%);
      padding: 0 18px;
      font: inherit;
      font-size: 1rem;
      color: var(--gf-checkout-ink);
      outline: none;
      box-shadow: 0 1px 0 rgba(255, 255, 255, 0.8), 0 10px 24px rgba(128, 95, 28, 0.06);
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, transform 0.2s ease;
    }

    .gf-checkout-field input::placeholder {
      color: #b2a38a;
    }

    .gf-checkout-field input:hover {
      border-color: rgba(130, 102, 34, 0.24);
      background: linear-gradient(180deg, #ffffff 0%, #fff8eb 100%);
    }

    .gf-checkout-field input:focus {
      border-color: rgba(199, 154, 43, 0.95);
      box-shadow: 0 0 0 4px rgba(199, 154, 43, 0.14), 0 16px 30px rgba(128, 95, 28, 0.10);
      background: #fffefc;
      transform: translateY(-1px);
    }

    .gf-checkout-field input:-webkit-autofill,
    .gf-checkout-field input:-webkit-autofill:hover,
    .gf-checkout-field input:-webkit-autofill:focus {
      -webkit-text-fill-color: var(--gf-checkout-ink);
      -webkit-box-shadow: 0 0 0 1000px #fffaf1 inset;
      transition: background-color 5000s ease-in-out 0s;
    }

    .gf-checkout-form-section {
      margin-top: 24px;
      padding-top: 22px;
      border-top: 1px solid rgba(87, 67, 27, 0.10);
    }

    .gf-checkout-form-section:first-of-type {
      margin-top: 0;
      padding-top: 0;
      border-top: 0;
    }

    .gf-checkout-form-section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 16px;
    }

    .gf-checkout-form-section-head h3 {
      margin: 0;
      font-size: 1rem;
      color: #2f281e;
    }

    .gf-checkout-form-section-head p {
      margin: 0;
      font-size: 0.92rem;
      color: var(--gf-checkout-muted);
    }

    .gf-checkout-alert {
      border-radius: 16px;
      padding: 14px 16px;
      margin-bottom: 18px;
      font-weight: 600;
    }

    .gf-checkout-alert-error {
      background: #fff0f0;
      color: #b42318;
      border: 1px solid #f3b0b0;
    }

    .gf-checkout-alert-success {
      background: #ecfdf3;
      color: #027a48;
      border: 1px solid #a6f4c5;
    }

    .gf-checkout-actions {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-top: 24px;
      flex-wrap: wrap;
    }

    .gf-checkout-primary,
    .gf-checkout-secondary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 52px;
      padding: 0 22px;
      border-radius: 999px;
      font-size: 0.98rem;
      font-weight: 700;
      text-decoration: none;
      transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .gf-checkout-primary {
      border: 1px solid transparent;
      background: linear-gradient(135deg, var(--gf-checkout-gold) 0%, #e0b751 100%);
      color: #fff;
      box-shadow: 0 18px 30px rgba(199, 154, 43, 0.24);
      min-width: 220px;
      cursor: pointer;
    }

    .gf-checkout-primary:hover,
    .gf-checkout-primary:focus-visible {
      background: linear-gradient(135deg, var(--gf-checkout-gold-strong) 0%, #d3a739 100%);
      transform: translateY(-1px);
      box-shadow: 0 20px 34px rgba(199, 154, 43, 0.30);
    }

    .gf-checkout-primary:disabled {
      cursor: wait;
      opacity: 0.82;
      transform: none;
      box-shadow: 0 10px 24px rgba(128, 95, 28, 0.14);
    }

    .gf-checkout-secondary {
      border: 1px solid rgba(130, 102, 34, 0.16);
      background: rgba(255, 255, 255, 0.92);
      color: #2e271c;
      box-shadow: 0 10px 22px rgba(76, 55, 22, 0.06);
    }

    .gf-checkout-secondary:hover,
    .gf-checkout-secondary:focus-visible {
      border-color: rgba(199, 154, 43, 0.45);
      background: #fff8ea;
      color: #7e5f14;
      transform: translateY(-1px);
    }

    @media (max-width: 960px) {
      .gf-custom-checkout-grid {
        grid-template-columns: 1fr;
      }

      .gf-custom-checkout-header {
        flex-direction: column;
      }

      .gf-custom-checkout-order {
        width: 100%;
        text-align: left;
      }
    }

    @media (max-width: 640px) {
      .gf-custom-checkout {
        padding: 28px 14px 48px;
      }

      .gf-checkout-form-grid,
      .gf-custom-checkout-meta,
      .gf-custom-checkout-preview-grid {
        grid-template-columns: 1fr;
      }

      .gf-custom-checkout-payment,
      .gf-custom-checkout-card {
        border-radius: 22px;
      }

      .gf-custom-checkout-payment {
        padding: 24px 18px 26px;
      }

      .gf-custom-checkout-card {
        padding: 20px;
      }

      .gf-checkout-actions {
        flex-direction: column;
        align-items: stretch;
      }

      .gf-checkout-primary,
      .gf-checkout-secondary {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <main class="gf-custom-checkout">
    <header class="gf-custom-checkout-header">
      <div>
        <span class="gf-custom-checkout-kicker"><i class="fa-solid fa-lock"></i> Custom Design Checkout</span>
        <h1>Complete payment for your custom design order.</h1>
        <p>Your design is already saved as pending payment. Review the saved preview and order lines below, then complete payment to move this order into the paid review queue.</p>
      </div>
      <div class="gf-custom-checkout-order">
        <strong><?php echo girffonCustomDesignCheckoutEscape((string) ($order['order_code'] ?? '')); ?></strong>
        <div>Status: <?php echo girffonCustomDesignCheckoutEscape(str_replace('_', ' ', ucfirst((string) ($order['status'] ?? 'pending_payment')))); ?></div>
      </div>
    </header>

    <section class="gf-custom-checkout-grid">
      <article class="gf-custom-checkout-card">
        <div class="gf-custom-checkout-preview-grid">
          <?php foreach ($previewViews as $previewKey => $preview): ?>
            <div class="gf-custom-checkout-preview-tile">
              <span class="gf-custom-checkout-preview-label"><?php echo girffonCustomDesignCheckoutEscape($preview['label']); ?></span>
              <div class="gf-custom-checkout-preview">
                <?php if ($preview['path'] !== ''): ?>
                  <img src="<?php echo girffonCustomDesignCheckoutEscape($preview['path']); ?>" alt="<?php echo girffonCustomDesignCheckoutEscape($preview['label']); ?> preview of the custom design order">
                <?php else: ?>
                  <div class="gf-custom-checkout-preview-empty">No preview</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <h2 class="gf-custom-checkout-product-title"><?php echo girffonCustomDesignCheckoutEscape((string) ($order['product_name'] ?? 'Custom Product')); ?></h2>
        <div class="gf-custom-checkout-meta">
          <div>
            <span>Quantity</span>
            <strong><?php echo (int) $summary['quantity']; ?></strong>
          </div>
          <div>
            <span>Total</span>
            <strong><?php echo girffonCustomDesignCheckoutEscape(girffonCustomDesignCheckoutPrice((float) $summary['order_total'])); ?></strong>
          </div>
          <div>
            <span>Primary Size</span>
            <strong><?php echo girffonCustomDesignCheckoutEscape($summary['size'] !== '' ? $summary['size'] : 'Custom'); ?></strong>
          </div>
          <div>
            <span>Primary Color</span>
            <strong><?php echo girffonCustomDesignCheckoutEscape($summary['color'] !== '' ? $summary['color'] : 'Custom'); ?></strong>
          </div>
        </div>

        <h3 class="gf-custom-checkout-section-title">Size and color lines</h3>
        <div class="gf-custom-checkout-line-list">
          <?php if ($sizeLines): ?>
            <?php foreach ($sizeLines as $line): ?>
              <div class="gf-custom-checkout-line-item">
                <span>Saved Line</span>
                <strong>
                  <?php
                    $lineSize = trim((string) ($line['size'] ?? 'Custom'));
                    $lineColor = trim((string) ($line['color'] ?? 'Color not set'));
                    $lineQuantity = max(1, (int) ($line['quantity'] ?? 1));
                    echo girffonCustomDesignCheckoutEscape($lineSize . ' / ' . $lineColor . ' / Qty ' . $lineQuantity);
                  ?>
                </strong>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="gf-custom-checkout-line-item">
              <span>Saved Line</span>
              <strong><?php echo girffonCustomDesignCheckoutEscape(($summary['size'] !== '' ? $summary['size'] : 'Custom') . ' / ' . ($summary['color'] !== '' ? $summary['color'] : 'Custom') . ' / Qty ' . (int) $summary['quantity']); ?></strong>
            </div>
          <?php endif; ?>
        </div>

        <div class="gf-custom-checkout-total">
          <span>Amount to pay</span>
          <strong><?php echo girffonCustomDesignCheckoutEscape(girffonCustomDesignCheckoutPrice((float) $summary['order_total'])); ?></strong>
        </div>
      </article>

      <article class="gf-custom-checkout-payment">
        <h2>Secure Payment</h2>
        <p>Use the saved customer details below to complete payment. When payment succeeds, this custom design order will move from pending payment to paid reviewing and remain visible in admin custom orders and your profile.</p>

        <?php if ($errors): ?>
          <div class="gf-checkout-alert gf-checkout-alert-error"><?php echo girffonCustomDesignCheckoutEscape($errors[0]); ?></div>
        <?php endif; ?>

        <?php if ($successMessage !== ''): ?>
          <div class="gf-checkout-alert gf-checkout-alert-success"><?php echo girffonCustomDesignCheckoutEscape($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($alreadyPaid): ?>
          <div class="gf-checkout-actions">
            <a class="gf-checkout-secondary" href="ProfilePage.php"><i class="fa-solid fa-user"></i> Back to Profile</a>
          </div>
        <?php else: ?>
          <form method="post" class="gf-checkout-form" id="gfCustomDesignCheckoutForm" novalidate>
            <input type="hidden" name="order" value="<?php echo (int) $order['id']; ?>">
            <section class="gf-checkout-form-section">
              <div class="gf-checkout-form-section-head">
                <div>
                  <h3>Billing details</h3>
                  <p>Review or update the customer information saved for this order.</p>
                </div>
              </div>
              <div class="gf-checkout-form-grid">
                <label class="gf-checkout-field gf-checkout-form-span"><span>Full Name</span>
                  <input type="text" id="gfBankNameInput" name="fullName" required autocomplete="name" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['fullName']); ?>">
                </label>
                <label class="gf-checkout-field"><span>Email</span>
                  <input type="email" id="gfBankEmailInput" name="email" required autocomplete="email" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['email']); ?>">
                </label>
                <label class="gf-checkout-field"><span>Phone</span>
                  <input type="tel" id="gfBankPhoneInput" name="phone" required autocomplete="tel" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['phone']); ?>">
                </label>
                <label class="gf-checkout-field gf-checkout-form-span"><span>Address</span>
                  <input type="text" id="gfBankAddressInput" name="address" required autocomplete="street-address" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['address']); ?>">
                </label>
                <label class="gf-checkout-field"><span>City</span>
                  <input type="text" id="gfBankCityInput" name="city" required autocomplete="address-level2" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['city']); ?>">
                </label>
                <label class="gf-checkout-field"><span>Postal Code</span>
                  <input type="text" id="gfBankPostalCodeInput" name="postalCode" required autocomplete="postal-code" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['postalCode']); ?>">
                </label>
                <label class="gf-checkout-field gf-checkout-form-span"><span>Country</span>
                  <input type="text" id="gfBankCountryInput" name="country" required autocomplete="country-name" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['country']); ?>">
                </label>
              </div>
            </section>

            <section class="gf-checkout-form-section">
              <div class="gf-checkout-form-section-head">
                <div>
                  <h3>Card details</h3>
                  <p>Complete the secure card fields to finish the custom design payment.</p>
                </div>
              </div>
              <div class="gf-checkout-form-grid">
                <label class="gf-checkout-field gf-checkout-form-span"><span>Card Number</span>
                  <input type="text" id="gfBankNumberInput" name="number" required maxlength="19" autocomplete="cc-number" placeholder="1234 5678 9012 3456" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['number']); ?>">
                </label>
                <label class="gf-checkout-field"><span>Expiry</span>
                  <input type="text" id="gfBankExpiryInput" name="expiry" required maxlength="5" autocomplete="cc-exp" placeholder="MM/YY" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['expiry']); ?>">
                </label>
                <label class="gf-checkout-field"><span>CVC</span>
                  <input type="text" id="gfBankCvcInput" name="cvc" required maxlength="4" autocomplete="cc-csc" placeholder="CVC" value="<?php echo girffonCustomDesignCheckoutEscape($formValues['cvc']); ?>">
                </label>
              </div>
            </section>

            <div class="gf-checkout-actions">
              <button type="submit" class="gf-checkout-primary" id="gfPayNowBtn">
                <span>Pay <?php echo girffonCustomDesignCheckoutEscape(girffonCustomDesignCheckoutPrice((float) $summary['order_total'])); ?></span>
                <i class="fa-solid fa-lock"></i>
              </button>
              <a class="gf-checkout-secondary" href="ProfilePage.php"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            </div>
          </form>
        <?php endif; ?>
      </article>
    </section>
  </main>

  <script>
    (function () {
      const form = document.getElementById('gfCustomDesignCheckoutForm');
      const payButton = document.getElementById('gfPayNowBtn');
      const cardInput = document.getElementById('gfBankNumberInput');
      const expiryInput = document.getElementById('gfBankExpiryInput');
      const cvcInput = document.getElementById('gfBankCvcInput');

      if (cardInput) {
        cardInput.addEventListener('input', function () {
          const digits = (cardInput.value || '').replace(/\D+/g, '').slice(0, 19);
          cardInput.value = digits.replace(/(.{4})/g, '$1 ').trim();
        });
      }

      if (expiryInput) {
        expiryInput.addEventListener('input', function () {
          const digits = (expiryInput.value || '').replace(/\D+/g, '').slice(0, 4);
          if (digits.length >= 3) {
            expiryInput.value = digits.slice(0, 2) + '/' + digits.slice(2);
            return;
          }
          expiryInput.value = digits;
        });
      }

      if (cvcInput) {
        cvcInput.addEventListener('input', function () {
          cvcInput.value = (cvcInput.value || '').replace(/\D+/g, '').slice(0, 4);
        });
      }

      if (form && payButton) {
        form.addEventListener('submit', function () {
          payButton.disabled = true;
          payButton.innerHTML = '<span>Processing payment...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
        });
      }
    }());
  </script>
</body>
</html>