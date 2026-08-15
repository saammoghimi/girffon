<?php
require_once __DIR__ . '/backend/admin/session.php';
require_once __DIR__ . '/backend/admin/gift-cards-data.php';
require_once __DIR__ . '/backend/utils/csrf.php';

$adminGiftCardSummary = girffonAdminFetchGiftCardSummary($pdo);
$adminGiftCards = girffonAdminFetchGiftCards($pdo);
$adminGiftCardViewCode = strtoupper(trim((string) ($_GET['view'] ?? '')));
$adminGiftCardEditId = max(0, (int) ($_GET['edit'] ?? 0));
$adminGiftCardHistoryCode = strtoupper(trim((string) ($_GET['history'] ?? '')));
$adminGiftCardStatusMessage = trim((string) ($_GET['status'] ?? ''));
$adminGiftCardErrorMessage = trim((string) ($_GET['error'] ?? ''));

$adminViewedGiftCard = $adminGiftCardViewCode !== '' ? girffonAdminFetchGiftCardByCode($pdo, $adminGiftCardViewCode) : null;
$adminEditedGiftCard = $adminGiftCardEditId > 0 ? girffonAdminFetchGiftCardById($pdo, $adminGiftCardEditId) : null;
$adminHistoryGiftCard = $adminGiftCardHistoryCode !== '' ? girffonAdminFetchGiftCardByCode($pdo, $adminGiftCardHistoryCode) : null;
$adminGiftCardTransactions = $adminHistoryGiftCard ? girffonAdminFetchGiftCardTransactions($pdo, (int) ($adminHistoryGiftCard['id'] ?? 0)) : [];
$adminGiftCardCsrf = girffonCsrfToken();

$escapeAdminGiftCard = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$formatAdminGiftCardCurrency = static function ($value) {
    return 'EUR ' . number_format((float) $value, 2, '.', ',');
};
$formatAdminGiftCardLabel = static function ($value) use ($escapeAdminGiftCard) {
    return $escapeAdminGiftCard(ucwords(str_replace('_', ' ', (string) $value)));
};
$formatAdminGiftCardDate = static function ($value) use ($escapeAdminGiftCard) {
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? $escapeAdminGiftCard(date('Y-m-d H:i', $timestamp)) : $escapeAdminGiftCard($value);
};
$giftCardStatusOptions = girffonAdminGiftCardStatusOptions();
$giftCardDeliveryOptions = girffonAdminGiftCardDeliveryOptions();
$giftCardAmountOptions = girffonGiftCardConfig()['amounts'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Gift Cards</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .admin-gift-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr);
      gap: 24px;
    }

    .admin-gift-form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .admin-gift-form-grid .admin-field-wide {
      grid-column: 1 / -1;
    }

    .admin-gift-form-grid label {
      display: grid;
      gap: 8px;
      color: #433526;
      font-weight: 600;
    }

    .admin-gift-form-grid input,
    .admin-gift-form-grid select,
    .admin-gift-form-grid textarea {
      width: 100%;
      border: 1px solid rgba(55, 43, 30, 0.12);
      border-radius: 14px;
      padding: 12px 14px;
      background: rgba(255, 250, 244, 0.9);
      font: inherit;
      color: #1f1a14;
    }

    .admin-gift-form-grid textarea {
      min-height: 112px;
      resize: vertical;
    }

    .admin-gift-chip-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 8px;
    }

    .admin-gift-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
      border-radius: 999px;
      background: rgba(201, 165, 106, 0.12);
      border: 1px solid rgba(201, 165, 106, 0.22);
      color: #4b3a27;
      font-size: .92rem;
    }

    .admin-gift-detail-card,
    .admin-gift-history-card {
      display: grid;
      gap: 18px;
    }

    .admin-gift-preview-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 180px;
      gap: 20px;
      align-items: center;
      padding: 18px;
      border-radius: 20px;
      background: linear-gradient(135deg, #1f1812, #443221 72%, #c9a56a);
      color: #f7efe3;
    }

    .admin-gift-preview-grid img {
      width: 160px;
      height: 160px;
      background: #fff;
      padding: 8px;
      border-radius: 18px;
      justify-self: center;
    }

    .admin-gift-barcode-box {
      padding: 14px;
      border-radius: 16px;
      background: #fff;
      color: #1f1a14;
    }

    .admin-gift-barcode-box svg {
      width: 100%;
      height: 62px;
      display: block;
    }

    .admin-gift-mini-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .admin-gift-mini-card {
      padding: 14px 16px;
      border-radius: 16px;
      background: rgba(255, 250, 244, 0.76);
      border: 1px solid rgba(55, 43, 30, 0.08);
    }

    .admin-gift-mini-card span {
      display: block;
      font-size: .8rem;
      color: #7a6a58;
      margin-bottom: 6px;
    }

    .admin-gift-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .admin-gift-actions form {
      margin: 0;
    }

    .admin-gift-table-wrap {
      overflow-x: auto;
    }

    .admin-gift-table {
      width: 100%;
      min-width: 1180px;
      border-collapse: collapse;
    }

    .admin-gift-table th,
    .admin-gift-table td {
      padding: 14px 12px;
      border-bottom: 1px solid rgba(55, 43, 30, 0.08);
      text-align: left;
      vertical-align: top;
    }

    .admin-gift-table th:last-child,
    .admin-gift-table td:last-child {
      width: 84px;
      min-width: 84px;
      text-align: center;
      vertical-align: middle;
    }

    .admin-gift-row-actions {
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .admin-gift-row-actions .admin-actions-dropdown {
      right: 0;
      left: auto;
      min-width: 188px;
    }

    .admin-gift-row-actions .admin-actions-menu-link[data-gift-action="view"]::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z'/%3E%3Ccircle cx='12' cy='12' r='2.5'/%3E%3C/svg%3E");
    }

    .admin-gift-row-actions .admin-actions-menu-link[data-gift-action="edit"]::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 20h9'/%3E%3Cpath d='M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z'/%3E%3C/svg%3E");
    }

    .admin-gift-row-actions .admin-actions-menu-link[data-gift-action="print"]::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 8V4.5h10V8'/%3E%3Crect x='5' y='9' width='14' height='7' rx='2'/%3E%3Cpath d='M8 14h8v5.5H8z'/%3E%3C/svg%3E");
    }

    .admin-gift-row-actions .admin-actions-menu-link[data-gift-action="history"]::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 12a9 9 0 1 0 2.64-6.36'/%3E%3Cpath d='M3 4v5h5'/%3E%3Cpath d='M12 7v5l3 2'/%3E%3C/svg%3E");
    }

    .admin-gift-row-actions .admin-actions-menu-link[data-gift-action="resend"]::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='4.5' y='7' width='15' height='10' rx='1.5'/%3E%3Cpath d='m5.5 8 6.5 5 6.5-5'/%3E%3Cpath d='M19 5v4M17 7h4'/%3E%3C/svg%3E");
    }

    .admin-gift-row-actions .admin-actions-menu-link[data-gift-action="cancel"] {
      color: #8f3c2d;
    }

    .admin-gift-row-actions .admin-actions-menu-link[data-gift-action="cancel"]::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23b63a3a' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='8'/%3E%3Cpath d='m9 9 6 6M15 9l-6 6'/%3E%3C/svg%3E");
    }

    .admin-gift-row-actions .admin-actions-menu-link[data-gift-action="delete"]::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23b63a3a' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M8 6V4h8v2'/%3E%3Cpath d='M19 6l-1 14H6L5 6'/%3E%3Cpath d='M10 11v6M14 11v6'/%3E%3C/svg%3E");
    }

    .admin-gift-status {
      display: inline-flex;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(31, 24, 18, 0.08);
      font-size: .78rem;
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    .admin-gift-feedback {
      margin: 0 0 18px;
      padding: 14px 16px;
      border-radius: 16px;
      font-weight: 600;
    }

    .admin-gift-feedback.is-success {
      background: rgba(80, 151, 101, 0.12);
      color: #255f36;
    }

    .admin-gift-feedback.is-error {
      background: rgba(182, 58, 58, 0.12);
      color: #8a2626;
    }

    @media (max-width: 1080px) {
      .admin-gift-grid,
      .admin-gift-preview-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 720px) {
      .admin-gift-form-grid,
      .admin-gift-mini-grid {
        grid-template-columns: 1fr;
      }

      .admin-gift-row-actions .admin-actions-dropdown {
        right: -12px;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="gift-cards">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Admin panel for products, orders, invoices, and customer messages.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
        <a class="admin-nav-link is-active" href="admin-gift-cards.php" aria-label="Gift Cards" title="Gift Cards">10. Gift Cards</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Mode</strong>
          <p class="admin-panel-note">Gift cards are managed from the live database.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title">Gift Cards</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Admin Dashboard" title="View Admin Dashboard">Home</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" onclick="window.location.reload();" aria-label="Refresh" title="Refresh">Refresh</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" aria-label="Logout" data-admin-logout title="Logout">Logout</button>
        </div>
      </header>

      <?php if ($adminGiftCardStatusMessage !== ''): ?>
        <p class="admin-gift-feedback is-success"><?php echo $escapeAdminGiftCard($adminGiftCardStatusMessage); ?></p>
      <?php endif; ?>
      <?php if ($adminGiftCardErrorMessage !== ''): ?>
        <p class="admin-gift-feedback is-error"><?php echo $escapeAdminGiftCard($adminGiftCardErrorMessage); ?></p>
      <?php endif; ?>

      <section class="admin-card-grid" aria-label="Gift card totals">
        <article class="admin-stat-card"><span>Total Gift Cards</span><strong><?php echo $escapeAdminGiftCard($adminGiftCardSummary['total_cards'] ?? 0); ?></strong><p class="admin-status">All gift cards ever issued.</p></article>
        <article class="admin-stat-card"><span>Active Gift Cards</span><strong><?php echo $escapeAdminGiftCard($adminGiftCardSummary['active_cards'] ?? 0); ?></strong><p class="admin-status">Cards available for redemption.</p></article>
        <article class="admin-stat-card"><span>Used Gift Cards</span><strong><?php echo $escapeAdminGiftCard($adminGiftCardSummary['used_cards'] ?? 0); ?></strong><p class="admin-status">Cards fully redeemed.</p></article>
        <article class="admin-stat-card"><span>Expired Gift Cards</span><strong><?php echo $escapeAdminGiftCard($adminGiftCardSummary['expired_cards'] ?? 0); ?></strong><p class="admin-status">Cards no longer valid.</p></article>
        <article class="admin-stat-card"><span>Total Remaining Balance</span><strong><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($adminGiftCardSummary['remaining_balance_total'] ?? 0)); ?></strong><p class="admin-status">Available balance across active cards.</p></article>
      </section>

      <section class="admin-gift-grid admin-page-section">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Create Gift Card</h2>
              <p class="admin-panel-note">English and Italian checkout labels are supported. Gift code, QR code, and barcode are generated automatically.</p>
            </div>
          </div>

          <form class="admin-gift-form-grid" action="backend/admin/save-gift-card.php" method="POST">
            <input type="hidden" name="_csrf" value="<?php echo $escapeAdminGiftCard($adminGiftCardCsrf); ?>">

            <label>
              <span>Gift Card Amount</span>
              <select name="preset_amount" required>
                <?php foreach ($giftCardAmountOptions as $amountOption): ?>
                  <option value="<?php echo $escapeAdminGiftCard($amountOption); ?>"><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($amountOption)); ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <span>Custom Amount</span>
              <input type="number" step="0.01" min="10" max="1000" name="custom_amount" placeholder="Leave empty to use preset amount">
            </label>

            <label>
              <span>Delivery Type</span>
              <select name="delivery_type" required>
                <?php foreach ($giftCardDeliveryOptions as $deliveryOption): ?>
                  <option value="<?php echo $escapeAdminGiftCard($deliveryOption); ?>"><?php echo $formatAdminGiftCardLabel($deliveryOption); ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <span>Status</span>
              <select name="status" required>
                <?php foreach ($giftCardStatusOptions as $statusOption): ?>
                  <option value="<?php echo $escapeAdminGiftCard($statusOption); ?>"><?php echo $formatAdminGiftCardLabel($statusOption); ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <span>Buyer Name</span>
              <input type="text" name="buyer_name" placeholder="Buyer name" required>
            </label>

            <label>
              <span>Buyer Email</span>
              <input type="email" name="buyer_email" placeholder="buyer@example.com" required>
            </label>

            <label>
              <span>Recipient Name</span>
              <input type="text" name="recipient_name" placeholder="Recipient name" required>
            </label>

            <label>
              <span>Recipient Email</span>
              <input type="email" name="recipient_email" placeholder="recipient@example.com" required>
            </label>

            <label class="admin-field-wide">
              <span>Personal Gift Message</span>
              <textarea name="gift_message" placeholder="Write a personal note for the recipient"></textarea>
            </label>

            <label>
              <span>Expiration Date</span>
              <input type="date" name="expires_at" required>
            </label>

            <div class="admin-field-wide">
              <div class="admin-gift-chip-row">
                <span class="admin-gift-chip">Secure code format: GF-7K4P-92MX-5Q8A</span>
                <span class="admin-gift-chip">Digital cards can be emailed instantly</span>
                <span class="admin-gift-chip">Physical cards include QR and barcode printouts</span>
              </div>
            </div>

            <div class="admin-field-wide">
              <button class="admin-button admin-button-primary" type="submit">Create Gift Card</button>
            </div>
          </form>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Gift Card Detail</h2>
              <p class="admin-panel-note">Select View, Edit, or History from the table for deeper controls.</p>
            </div>
          </div>

          <?php if ($adminViewedGiftCard): ?>
            <?php $adminGiftCardQrUrl = girffonGiftCardQrImageUrl((string) ($adminViewedGiftCard['qr_payload'] ?? girffonGiftCardBuildQrPayload($adminViewedGiftCard))); ?>
            <div class="admin-gift-detail-card">
              <div class="admin-gift-preview-grid">
                <div>
                  <span class="admin-page-subtitle">Gift Card Preview</span>
                  <h3 style="margin:8px 0 10px;"><?php echo $escapeAdminGiftCard($adminViewedGiftCard['gift_code'] ?? ''); ?></h3>
                  <p style="margin:0 0 16px;max-width:42ch;line-height:1.7;">Buyer: <?php echo $escapeAdminGiftCard($adminViewedGiftCard['buyer_name'] ?? '-'); ?>. Recipient: <?php echo $escapeAdminGiftCard($adminViewedGiftCard['recipient_name'] ?? '-'); ?>.</p>
                  <div class="admin-gift-barcode-box">
                    <svg data-gift-card-barcode value="<?php echo $escapeAdminGiftCard($adminViewedGiftCard['barcode_value'] ?? ''); ?>"></svg>
                    <strong style="display:block;margin-top:10px;"><?php echo $escapeAdminGiftCard($adminViewedGiftCard['barcode_value'] ?? ''); ?></strong>
                  </div>
                </div>
                <img src="<?php echo $escapeAdminGiftCard($adminGiftCardQrUrl); ?>" alt="Gift card QR code">
              </div>

              <div class="admin-gift-mini-grid">
                <div class="admin-gift-mini-card"><span>Initial Amount</span><strong><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($adminViewedGiftCard['initial_amount'] ?? 0)); ?></strong></div>
                <div class="admin-gift-mini-card"><span>Remaining Balance</span><strong><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($adminViewedGiftCard['remaining_balance'] ?? 0)); ?></strong></div>
                <div class="admin-gift-mini-card"><span>Status</span><strong><?php echo $formatAdminGiftCardLabel($adminViewedGiftCard['status'] ?? 'active'); ?></strong></div>
                <div class="admin-gift-mini-card"><span>Delivery Type</span><strong><?php echo $formatAdminGiftCardLabel($adminViewedGiftCard['delivery_type'] ?? 'digital'); ?></strong></div>
                <div class="admin-gift-mini-card"><span>Created At</span><strong><?php echo $formatAdminGiftCardDate($adminViewedGiftCard['created_at'] ?? ''); ?></strong></div>
                <div class="admin-gift-mini-card"><span>Expires At</span><strong><?php echo $formatAdminGiftCardDate($adminViewedGiftCard['expires_at'] ?? ''); ?></strong></div>
              </div>

              <p class="admin-panel-note" style="margin:0;">Message: <?php echo $escapeAdminGiftCard($adminViewedGiftCard['gift_message'] ?? 'No message supplied.'); ?></p>

              <div class="admin-gift-actions">
                <a class="admin-button admin-button-soft" href="backend/admin/print-gift-card.php?code=<?php echo rawurlencode((string) ($adminViewedGiftCard['gift_code'] ?? '')); ?>" target="_blank" rel="noopener noreferrer">Print Physical Card</a>
                <form action="backend/admin/resend-gift-card.php" method="POST">
                  <input type="hidden" name="_csrf" value="<?php echo $escapeAdminGiftCard($adminGiftCardCsrf); ?>">
                  <input type="hidden" name="gift_code" value="<?php echo $escapeAdminGiftCard($adminViewedGiftCard['gift_code'] ?? ''); ?>">
                  <button class="admin-button admin-button-soft" type="submit">Resend Email</button>
                </form>
                <form action="backend/admin/cancel-gift-card.php" method="POST">
                  <input type="hidden" name="_csrf" value="<?php echo $escapeAdminGiftCard($adminGiftCardCsrf); ?>">
                  <input type="hidden" name="id" value="<?php echo $escapeAdminGiftCard($adminViewedGiftCard['id'] ?? 0); ?>">
                  <input type="hidden" name="gift_code" value="<?php echo $escapeAdminGiftCard($adminViewedGiftCard['gift_code'] ?? ''); ?>">
                  <button class="admin-button admin-button-danger" type="submit">Disable / Cancel</button>
                </form>
              </div>
            </div>
          <?php elseif ($adminEditedGiftCard): ?>
            <form class="admin-gift-form-grid" action="backend/admin/update-gift-card.php" method="POST">
              <input type="hidden" name="_csrf" value="<?php echo $escapeAdminGiftCard($adminGiftCardCsrf); ?>">
              <input type="hidden" name="id" value="<?php echo $escapeAdminGiftCard($adminEditedGiftCard['id'] ?? 0); ?>">
              <label><span>Gift Card Code</span><input type="text" value="<?php echo $escapeAdminGiftCard($adminEditedGiftCard['gift_code'] ?? ''); ?>" disabled></label>
              <label><span>Remaining Balance</span><input type="text" value="<?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($adminEditedGiftCard['remaining_balance'] ?? 0)); ?>" disabled></label>
              <label><span>Buyer Name</span><input type="text" name="buyer_name" value="<?php echo $escapeAdminGiftCard($adminEditedGiftCard['buyer_name'] ?? ''); ?>"></label>
              <label><span>Buyer Email</span><input type="email" name="buyer_email" value="<?php echo $escapeAdminGiftCard($adminEditedGiftCard['buyer_email'] ?? ''); ?>"></label>
              <label><span>Recipient Name</span><input type="text" name="recipient_name" value="<?php echo $escapeAdminGiftCard($adminEditedGiftCard['recipient_name'] ?? ''); ?>"></label>
              <label><span>Recipient Email</span><input type="email" name="recipient_email" value="<?php echo $escapeAdminGiftCard($adminEditedGiftCard['recipient_email'] ?? ''); ?>"></label>
              <label><span>Delivery Type</span><select name="delivery_type"><?php foreach ($giftCardDeliveryOptions as $deliveryOption): ?><option value="<?php echo $escapeAdminGiftCard($deliveryOption); ?>"<?php echo (($adminEditedGiftCard['delivery_type'] ?? '') === $deliveryOption) ? ' selected' : ''; ?>><?php echo $formatAdminGiftCardLabel($deliveryOption); ?></option><?php endforeach; ?></select></label>
              <label><span>Status</span><select name="status"><?php foreach ($giftCardStatusOptions as $statusOption): ?><option value="<?php echo $escapeAdminGiftCard($statusOption); ?>"<?php echo (($adminEditedGiftCard['status'] ?? '') === $statusOption) ? ' selected' : ''; ?>><?php echo $formatAdminGiftCardLabel($statusOption); ?></option><?php endforeach; ?></select></label>
              <label class="admin-field-wide"><span>Gift Message</span><textarea name="gift_message"><?php echo $escapeAdminGiftCard($adminEditedGiftCard['gift_message'] ?? ''); ?></textarea></label>
              <label><span>Expiration Date</span><input type="date" name="expires_at" value="<?php echo $escapeAdminGiftCard(!empty($adminEditedGiftCard['expires_at']) ? date('Y-m-d', strtotime((string) $adminEditedGiftCard['expires_at'])) : ''); ?>"></label>
              <div class="admin-field-wide"><button class="admin-button admin-button-primary" type="submit">Save Gift Card Changes</button></div>
            </form>
          <?php elseif ($adminHistoryGiftCard): ?>
            <div class="admin-gift-history-card">
              <h3 style="margin:0;"><?php echo $escapeAdminGiftCard($adminHistoryGiftCard['gift_code'] ?? ''); ?></h3>
              <?php if ($adminGiftCardTransactions): ?>
                <div class="admin-gift-table-wrap">
                  <table class="admin-gift-table">
                    <thead><tr><th>Type</th><th>Amount</th><th>Before</th><th>After</th><th>Order ID</th><th>Notes</th><th>Date</th></tr></thead>
                    <tbody>
                      <?php foreach ($adminGiftCardTransactions as $transaction): ?>
                        <tr>
                          <td><?php echo $formatAdminGiftCardLabel($transaction['transaction_type'] ?? ''); ?></td>
                          <td><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($transaction['amount'] ?? 0)); ?></td>
                          <td><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($transaction['balance_before'] ?? 0)); ?></td>
                          <td><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($transaction['balance_after'] ?? 0)); ?></td>
                          <td><?php echo $escapeAdminGiftCard($transaction['order_id'] ?? '-'); ?></td>
                          <td><?php echo $escapeAdminGiftCard($transaction['notes'] ?? '-'); ?></td>
                          <td><?php echo $formatAdminGiftCardDate($transaction['created_at'] ?? ''); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="admin-panel-note">No transactions recorded yet.</p>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <p class="admin-panel-note">Open a card from the table to inspect barcode, QR code, email actions, or transaction history.</p>
          <?php endif; ?>
        </article>
      </section>

      <section class="admin-page-section">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Gift Card Table</h2>
              <p class="admin-panel-note">View, edit, print, resend, cancel, delete unused cards, and inspect transaction history.</p>
            </div>
          </div>

          <div class="admin-gift-table-wrap">
            <table class="admin-gift-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Gift Card Code</th>
                  <th>Buyer</th>
                  <th>Recipient</th>
                  <th>Initial Amount</th>
                  <th>Remaining Balance</th>
                  <th>Delivery Type</th>
                  <th>Status</th>
                  <th>Order ID</th>
                  <th>Creation Date</th>
                  <th>Expiration Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($adminGiftCards): ?>
                  <?php foreach ($adminGiftCards as $giftCard): ?>
                    <?php $adminCanDeleteGiftCard = girffonAdminGiftCardDeleteAllowed($pdo, (int) ($giftCard['id'] ?? 0)); ?>
                    <tr>
                      <td><?php echo $escapeAdminGiftCard($giftCard['id'] ?? 0); ?></td>
                      <td><strong><?php echo $escapeAdminGiftCard($giftCard['gift_code'] ?? ''); ?></strong></td>
                      <td><?php echo $escapeAdminGiftCard(trim((string) ($giftCard['buyer_name'] ?? '')) ?: ($giftCard['buyer_email'] ?? '-')); ?></td>
                      <td><?php echo $escapeAdminGiftCard(trim((string) ($giftCard['recipient_name'] ?? '')) ?: ($giftCard['recipient_email'] ?? '-')); ?></td>
                      <td><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($giftCard['initial_amount'] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminGiftCard($formatAdminGiftCardCurrency($giftCard['remaining_balance'] ?? 0)); ?></td>
                      <td><?php echo $formatAdminGiftCardLabel($giftCard['delivery_type'] ?? 'digital'); ?></td>
                      <td><span class="admin-gift-status"><?php echo $formatAdminGiftCardLabel($giftCard['status'] ?? 'active'); ?></span></td>
                      <td><?php echo $escapeAdminGiftCard($giftCard['order_id'] ?? '-'); ?></td>
                      <td><?php echo $formatAdminGiftCardDate($giftCard['created_at'] ?? ''); ?></td>
                      <td><?php echo $formatAdminGiftCardDate($giftCard['expires_at'] ?? ''); ?></td>
                      <td>
                        <div class="admin-table-actions admin-table-actions-menu admin-gift-row-actions" data-admin-gift-menu>
                          <button class="admin-action-button admin-actions-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Open gift card actions" title="Actions">
                            <svg class="admin-actions-toggle-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                              <circle cx="12" cy="12" r="2.4"></circle>
                              <path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.7l-1 1a1.2 1.2 0 0 1-1.7 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9v.2A1.2 1.2 0 0 1 14 21h-4a1.2 1.2 0 0 1-1.2-1.2v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.7 0l-1-1a1.2 1.2 0 0 1 0-1.7l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6h-.2A1.2 1.2 0 0 1 2 14v-4a1.2 1.2 0 0 1 1.2-1.2h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1L4 7a1.2 1.2 0 0 1 0-1.7l1-1a1.2 1.2 0 0 1 1.7 0l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9v-.2A1.2 1.2 0 0 1 10 2h4a1.2 1.2 0 0 1 1.2 1.2v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.7 0l1 1a1.2 1.2 0 0 1 0 1.7l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.2A1.2 1.2 0 0 1 22 10v4a1.2 1.2 0 0 1-1.2 1.2h-.2a1 1 0 0 0-.9.6Z"></path>
                            </svg>
                          </button>
                          <div class="admin-actions-dropdown" hidden>
                            <a class="admin-actions-menu-link" data-gift-action="view" href="admin-gift-cards.php?view=<?php echo rawurlencode((string) ($giftCard['gift_code'] ?? '')); ?>">View</a>
                            <a class="admin-actions-menu-link" data-gift-action="edit" href="admin-gift-cards.php?edit=<?php echo $escapeAdminGiftCard($giftCard['id'] ?? 0); ?>">Edit</a>
                            <a class="admin-actions-menu-link" data-gift-action="print" href="backend/admin/print-gift-card.php?code=<?php echo rawurlencode((string) ($giftCard['gift_code'] ?? '')); ?>" target="_blank" rel="noopener noreferrer">Print</a>
                            <a class="admin-actions-menu-link" data-gift-action="history" href="admin-gift-cards.php?history=<?php echo rawurlencode((string) ($giftCard['gift_code'] ?? '')); ?>">History</a>
                            <form class="admin-actions-delete-form" action="backend/admin/resend-gift-card.php" method="POST">
                              <input type="hidden" name="_csrf" value="<?php echo $escapeAdminGiftCard($adminGiftCardCsrf); ?>">
                              <input type="hidden" name="gift_code" value="<?php echo $escapeAdminGiftCard($giftCard['gift_code'] ?? ''); ?>">
                              <button class="admin-actions-menu-link" data-gift-action="resend" type="submit">Resend</button>
                            </form>
                            <form class="admin-actions-delete-form" action="backend/admin/cancel-gift-card.php" method="POST">
                              <input type="hidden" name="_csrf" value="<?php echo $escapeAdminGiftCard($adminGiftCardCsrf); ?>">
                              <input type="hidden" name="id" value="<?php echo $escapeAdminGiftCard($giftCard['id'] ?? 0); ?>">
                              <input type="hidden" name="gift_code" value="<?php echo $escapeAdminGiftCard($giftCard['gift_code'] ?? ''); ?>">
                              <button class="admin-actions-menu-link is-danger" data-gift-action="cancel" type="submit">Cancel</button>
                            </form>
                            <?php if ($adminCanDeleteGiftCard): ?>
                              <form class="admin-actions-delete-form" action="backend/admin/delete-gift-card.php" method="POST" onsubmit="return confirm('Delete this unused gift card?');">
                                <input type="hidden" name="_csrf" value="<?php echo $escapeAdminGiftCard($adminGiftCardCsrf); ?>">
                                <input type="hidden" name="id" value="<?php echo $escapeAdminGiftCard($giftCard['id'] ?? 0); ?>">
                                <button class="admin-actions-menu-link is-danger" data-gift-action="delete" type="submit">Delete</button>
                              </form>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="12" class="admin-empty">No gift cards found yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
  <script>
    (function () {
      function wireGiftCardActionMenus() {
        var actionMenus = Array.prototype.slice.call(document.querySelectorAll('[data-admin-gift-menu]'));

        if (!actionMenus.length) {
          return;
        }

        function closeMenu(menuWrap) {
          var toggle = menuWrap.querySelector('.admin-actions-toggle');
          var dropdown = menuWrap.querySelector('.admin-actions-dropdown');

          if (!toggle || !dropdown) {
            return;
          }

          toggle.setAttribute('aria-expanded', 'false');
          menuWrap.classList.remove('is-open');
          dropdown.hidden = true;
        }

        function openMenu(menuWrap) {
          actionMenus.forEach(function (item) {
            if (item !== menuWrap) {
              closeMenu(item);
            }
          });

          var toggle = menuWrap.querySelector('.admin-actions-toggle');
          var dropdown = menuWrap.querySelector('.admin-actions-dropdown');

          if (!toggle || !dropdown) {
            return;
          }

          toggle.setAttribute('aria-expanded', 'true');
          menuWrap.classList.add('is-open');
          dropdown.hidden = false;
        }

        actionMenus.forEach(function (menuWrap) {
          var toggle = menuWrap.querySelector('.admin-actions-toggle');
          var dropdown = menuWrap.querySelector('.admin-actions-dropdown');

          if (!toggle || !dropdown) {
            return;
          }

          toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (menuWrap.classList.contains('is-open')) {
              closeMenu(menuWrap);
              return;
            }

            openMenu(menuWrap);
          });

          dropdown.addEventListener('click', function () {
            closeMenu(menuWrap);
          });
        });

        document.addEventListener('click', function (event) {
          actionMenus.forEach(function (menuWrap) {
            if (!menuWrap.contains(event.target)) {
              closeMenu(menuWrap);
            }
          });
        });

        document.addEventListener('keydown', function (event) {
          if (event.key !== 'Escape') {
            return;
          }

          actionMenus.forEach(closeMenu);
        });
      }

      function renderBarcode(svg) {
        if (!svg || !window.JsBarcode) {
          return;
        }

        var value = String(svg.getAttribute('value') || '').trim();
        if (!value) {
          return;
        }

        window.JsBarcode(svg, value, {
          format: 'CODE128',
          displayValue: false,
          margin: 0,
          width: 1.45,
          height: 54,
          background: '#ffffff',
          lineColor: '#1f1a14'
        });
      }

      wireGiftCardActionMenus();
      Array.prototype.forEach.call(document.querySelectorAll('[data-gift-card-barcode]'), renderBarcode);
    }());
  </script>
  <script src="JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>