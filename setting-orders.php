<?php
require_once __DIR__ . "/backend/admin/session.php";

$adminOrderSettingsFile = __DIR__ . "/backend/admin/order-settings-data.php";
$adminOrderSettingsAvailable = is_file($adminOrderSettingsFile);
if ($adminOrderSettingsAvailable) {
  require_once $adminOrderSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$adminOrderSettingStatus = trim((string) ($_GET['status'] ?? ''));
$adminOrderSettingError = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $orderPreset = trim((string) ($_POST['order_preset'] ?? ''));
  $orderPreferences = [
    'show_orders_overview' => isset($_POST['show_orders_overview']),
    'show_order_list' => isset($_POST['show_order_list']),
    'show_customer_column' => isset($_POST['show_customer_column']),
    'show_payment_method_column' => isset($_POST['show_payment_method_column']),
    'show_payment_status_column' => isset($_POST['show_payment_status_column']),
    'show_order_status_column' => isset($_POST['show_order_status_column']),
    'show_tracking_column' => isset($_POST['show_tracking_column']),
    'show_courier_column' => isset($_POST['show_courier_column']),
    'show_eta_column' => isset($_POST['show_eta_column']),
    'show_admin_note_column' => isset($_POST['show_admin_note_column']),
    'show_created_at_column' => isset($_POST['show_created_at_column']),
    'show_save_action' => isset($_POST['show_save_action']),
    'show_track_action' => isset($_POST['show_track_action']),
    'show_invoice_action' => isset($_POST['show_invoice_action']),
  ];

  $orderPresets = [
    'operations-center' => [
      'show_orders_overview' => true,
      'show_order_list' => true,
      'show_customer_column' => true,
      'show_payment_method_column' => true,
      'show_payment_status_column' => true,
      'show_order_status_column' => true,
      'show_tracking_column' => true,
      'show_courier_column' => true,
      'show_eta_column' => true,
      'show_admin_note_column' => true,
      'show_created_at_column' => true,
      'show_save_action' => true,
      'show_track_action' => true,
      'show_invoice_action' => true,
    ],
    'fulfillment-desk' => [
      'show_orders_overview' => true,
      'show_order_list' => true,
      'show_customer_column' => false,
      'show_payment_method_column' => false,
      'show_payment_status_column' => true,
      'show_order_status_column' => true,
      'show_tracking_column' => true,
      'show_courier_column' => true,
      'show_eta_column' => true,
      'show_admin_note_column' => true,
      'show_created_at_column' => true,
      'show_save_action' => true,
      'show_track_action' => true,
      'show_invoice_action' => false,
    ],
    'review-mode' => [
      'show_orders_overview' => true,
      'show_order_list' => true,
      'show_customer_column' => true,
      'show_payment_method_column' => true,
      'show_payment_status_column' => true,
      'show_order_status_column' => true,
      'show_tracking_column' => false,
      'show_courier_column' => false,
      'show_eta_column' => false,
      'show_admin_note_column' => false,
      'show_created_at_column' => true,
      'show_save_action' => false,
      'show_track_action' => true,
      'show_invoice_action' => true,
    ],
  ];

  if ($orderPreset !== '' && isset($orderPresets[$orderPreset])) {
    $orderPreferences = $orderPresets[$orderPreset];
  }

  $saved = $adminOrderSettingsAvailable && function_exists('girffonAdminSaveOrderPreferences')
    ? girffonAdminSaveOrderPreferences($pdo, $adminCurrentId, $adminCurrentUsername, $orderPreferences)
    : false;
  header('Location: /GirffoN/setting-orders.php?' . ($saved ? 'status=' . rawurlencode('Order settings saved for your account.') : 'error=' . rawurlencode('Unable to save order settings right now.')));
  exit;
}

$adminOrderPreferences = [
  'show_orders_overview' => true,
  'show_order_list' => true,
  'show_customer_column' => true,
  'show_payment_method_column' => true,
  'show_payment_status_column' => true,
  'show_order_status_column' => true,
  'show_tracking_column' => true,
  'show_courier_column' => true,
  'show_eta_column' => true,
  'show_admin_note_column' => true,
  'show_created_at_column' => true,
  'show_save_action' => true,
  'show_track_action' => true,
  'show_invoice_action' => true,
];

if ($adminOrderSettingsAvailable && function_exists('girffonAdminFetchOrderPreferences')) {
  $adminOrderPreferences = girffonAdminFetchOrderPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$escapeOrderSetting = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$orderSettingGroups = [
  'workspace' => [
    'title' => 'Workspace Layout',
    'note' => 'Control the high-level blocks of the Orders admin screen.',
    'settings' => [
      'show_orders_overview' => ['label' => 'Orders Overview', 'note' => 'Show the live orders intro panel and status feedback area.'],
      'show_order_list' => ['label' => 'Order List', 'note' => 'Show the main database orders table for this admin account.'],
    ],
  ],
  'columns' => [
    'title' => 'Operational Columns',
    'note' => 'Shape the order table for customer support, fulfillment, or audit workflows.',
    'settings' => [
      'show_customer_column' => ['label' => 'Customer Column', 'note' => 'Show customer identity, contacts, address, and email-permission note.'],
      'show_payment_method_column' => ['label' => 'Payment Method', 'note' => 'Show how each order was paid.'],
      'show_payment_status_column' => ['label' => 'Payment Status', 'note' => 'Show editable payment status in the table.'],
      'show_order_status_column' => ['label' => 'Order Status', 'note' => 'Show editable order-status controls in the table.'],
      'show_tracking_column' => ['label' => 'Tracking Number', 'note' => 'Show the tracking input column.'],
      'show_courier_column' => ['label' => 'Courier', 'note' => 'Show the courier input column.'],
      'show_eta_column' => ['label' => 'Estimated Delivery', 'note' => 'Show the ETA date field.'],
      'show_admin_note_column' => ['label' => 'Admin Note', 'note' => 'Show the note textarea used for internal or customer-facing updates.'],
      'show_created_at_column' => ['label' => 'Created At', 'note' => 'Show the order creation timestamp in the table.'],
    ],
  ],
  'actions' => [
    'title' => 'Action Stack',
    'note' => 'Decide which order actions stay available on each row.',
    'settings' => [
      'show_save_action' => ['label' => 'Save Update', 'note' => 'Keep the save button visible to submit order changes.'],
      'show_track_action' => ['label' => 'Track Order', 'note' => 'Keep the customer tracking-page shortcut visible.'],
      'show_invoice_action' => ['label' => 'View Invoice', 'note' => 'Keep invoice quick-access links visible when an invoice exists.'],
    ],
  ],
];

$orderPresetCards = [
  'operations-center' => ['title' => 'Operations Center', 'note' => 'Everything visible for support, payment review, and shipment updates.'],
  'fulfillment-desk' => ['title' => 'Fulfillment Desk', 'note' => 'Focus on shipping progress, ETA, and courier coordination.'],
  'review-mode' => ['title' => 'Review Mode', 'note' => 'Cleaner read-first layout for management and invoice review.'],
];

$activeOrderSettingsCount = 0;
foreach ($adminOrderPreferences as $preferenceEnabled) {
  if (!empty($preferenceEnabled)) {
    $activeOrderSettingsCount++;
  }
}

$orderSettingsTotal = count($adminOrderPreferences);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Order Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .order-settings-hero {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.85fr);
      gap: 18px;
      margin-bottom: 18px;
    }

    .order-settings-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .order-settings-section-grid {
      display: grid;
      gap: 18px;
    }

    .order-settings-summary-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .order-settings-metric {
      padding: 16px 18px;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.86);
      border: 1px solid rgba(199, 165, 75, 0.16);
    }

    .order-settings-metric span {
      display: block;
      color: #7d715f;
      font-size: 0.86rem;
      margin-bottom: 6px;
    }

    .order-settings-metric strong {
      display: block;
      color: #2b241b;
      font-size: 1.4rem;
    }

    .order-preset-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .order-preset-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 20px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      background: linear-gradient(180deg, rgba(255, 252, 246, 0.96), rgba(255, 255, 255, 0.82));
    }

    .order-preset-card h3,
    .order-settings-section h3 {
      margin: 0;
      color: #2b241b;
      font-size: 1rem;
    }

    .order-preset-card p,
    .order-settings-section-note {
      margin: 0;
      color: #7d715f;
      line-height: 1.6;
      font-size: 0.92rem;
    }

    .order-preset-card .admin-button {
      justify-self: start;
    }

    .order-settings-section {
      display: grid;
      gap: 16px;
      padding: 20px;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(199, 165, 75, 0.14);
    }

    .order-setting-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(199, 165, 75, 0.16);
      background: rgba(255, 255, 255, 0.82);
    }

    .order-setting-toggle {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
    }

    .order-setting-toggle label {
      display: grid;
      gap: 6px;
      cursor: pointer;
    }

    .order-setting-toggle input[type="checkbox"] {
      margin-top: 4px;
      width: 20px;
      height: 20px;
      accent-color: #c7a54b;
      cursor: pointer;
    }

    .order-setting-title {
      font-size: 1rem;
      font-weight: 700;
      color: #2b241b;
    }

    .order-setting-note {
      color: #7d715f;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      .order-settings-hero,
      .order-settings-summary-grid,
      .order-preset-grid,
      .order-settings-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 980px) {
      .order-settings-hero,
      .order-preset-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="settings">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Configure your personal order workspace and operational controls.</p>
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
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Order Workspace</strong>
          <p class="admin-panel-note">Tune visibility for updates, shipping fields, invoice access, and order review.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Order Settings</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <a class="admin-button admin-button-soft admin-refresh-button" href="admin-orders.php" aria-label="Back to Orders" title="Back to Orders">Orders</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-orders.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <section class="order-settings-hero">
          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Orders Control Center</h2>
                <p class="admin-panel-note">Signed in as <?php echo $escapeOrderSetting($adminCurrentUsername !== '' ? $adminCurrentUsername : 'GirffoN Admin'); ?>. These settings are saved per admin and directly change what you see in the live orders table.</p>
              </div>
            </div>
            <div class="order-preset-grid">
              <?php foreach ($orderPresetCards as $presetKey => $presetMeta): ?>
                <article class="order-preset-card">
                  <div>
                    <h3><?php echo $escapeOrderSetting($presetMeta['title']); ?></h3>
                    <p><?php echo $escapeOrderSetting($presetMeta['note']); ?></p>
                  </div>
                  <form method="POST" action="setting-orders.php">
                    <input type="hidden" name="order_preset" value="<?php echo $escapeOrderSetting($presetKey); ?>">
                    <button class="admin-button admin-button-soft" type="submit">Apply Preset</button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Current View Summary</h2>
                <p class="admin-panel-note">Quick snapshot of how operational or review-focused your Orders workspace currently is.</p>
              </div>
            </div>
            <div class="order-settings-summary-grid">
              <div class="order-settings-metric">
                <span>Enabled Controls</span>
                <strong><?php echo $escapeOrderSetting($activeOrderSettingsCount); ?> / <?php echo $escapeOrderSetting($orderSettingsTotal); ?></strong>
              </div>
              <div class="order-settings-metric">
                <span>Workspace Mode</span>
                <strong><?php echo $escapeOrderSetting(!empty($adminOrderPreferences['show_order_list']) && !empty($adminOrderPreferences['show_save_action']) ? 'Operational' : (!empty($adminOrderPreferences['show_order_list']) ? 'Review' : 'Minimal')); ?></strong>
              </div>
              <div class="order-settings-metric">
                <span>Shipment Tools</span>
                <strong><?php echo $escapeOrderSetting(!empty($adminOrderPreferences['show_tracking_column']) || !empty($adminOrderPreferences['show_courier_column']) || !empty($adminOrderPreferences['show_eta_column']) ? 'Enabled' : 'Hidden'); ?></strong>
              </div>
              <div class="order-settings-metric">
                <span>Invoice Access</span>
                <strong><?php echo $escapeOrderSetting(!empty($adminOrderPreferences['show_invoice_action']) ? 'Visible' : 'Restricted'); ?></strong>
              </div>
            </div>
          </article>
        </section>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Fine-Grained Order Settings</h2>
              <p class="admin-panel-note">Build your own Orders workspace with grouped controls for layout, operational columns, and row actions.</p>
            </div>
          </div>

          <form method="POST" action="setting-orders.php" novalidate>
            <div class="order-settings-section-grid">
              <?php foreach ($orderSettingGroups as $groupMeta): ?>
                <section class="order-settings-section">
                  <div>
                    <h3><?php echo $escapeOrderSetting($groupMeta['title']); ?></h3>
                    <p class="order-settings-section-note"><?php echo $escapeOrderSetting($groupMeta['note']); ?></p>
                  </div>
                  <div class="order-settings-grid">
                    <?php foreach ($groupMeta['settings'] as $settingKey => $settingMeta): ?>
                      <section class="order-setting-card">
                        <div class="order-setting-toggle">
                          <label for="<?php echo $escapeOrderSetting($settingKey); ?>">
                            <span class="order-setting-title"><?php echo $escapeOrderSetting($settingMeta['label']); ?></span>
                            <span class="order-setting-note"><?php echo $escapeOrderSetting($settingMeta['note']); ?></span>
                          </label>
                          <input id="<?php echo $escapeOrderSetting($settingKey); ?>" name="<?php echo $escapeOrderSetting($settingKey); ?>" type="checkbox" <?php if (!empty($adminOrderPreferences[$settingKey])): ?>checked<?php endif; ?>>
                        </div>
                      </section>
                    <?php endforeach; ?>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>

            <div class="admin-form-actions" style="margin-top:18px;">
              <button class="admin-button" type="submit">Save Order Settings</button>
              <a class="admin-button admin-button-soft" href="admin-orders.php">Back to Orders</a>
            </div>
          </form>

          <?php if ($adminOrderSettingStatus !== ''): ?>
            <p class="admin-feedback" role="status" aria-live="polite" style="margin-top:16px;"><?php echo $escapeOrderSetting($adminOrderSettingStatus); ?></p>
          <?php elseif ($adminOrderSettingError !== ''): ?>
            <p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;"><?php echo $escapeOrderSetting($adminOrderSettingError); ?></p>
          <?php elseif (!$adminOrderSettingsAvailable): ?>
            <p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;">Order settings helper is missing on this host. Upload backend/admin/order-settings-data.php first.</p>
          <?php endif; ?>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>