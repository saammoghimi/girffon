<?php
require_once __DIR__ . "/backend/admin/session.php";

$adminInvoiceSettingsFile = __DIR__ . "/backend/admin/invoice-settings-data.php";
$adminInvoiceSettingsAvailable = is_file($adminInvoiceSettingsFile);
if ($adminInvoiceSettingsAvailable) {
  require_once $adminInvoiceSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$adminInvoiceSettingStatus = trim((string) ($_GET['status'] ?? ''));
$adminInvoiceSettingError = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $invoicePreset = trim((string) ($_POST['invoice_preset'] ?? ''));
  $invoicePreferences = [
    'show_add_invoice_panel' => isset($_POST['show_add_invoice_panel']),
    'show_search_filters' => isset($_POST['show_search_filters']),
    'show_invoice_list' => isset($_POST['show_invoice_list']),
    'show_customer_column' => isset($_POST['show_customer_column']),
    'show_tax_column' => isset($_POST['show_tax_column']),
    'show_shipping_column' => isset($_POST['show_shipping_column']),
    'show_status_column' => isset($_POST['show_status_column']),
    'show_created_at_column' => isset($_POST['show_created_at_column']),
    'show_view_action' => isset($_POST['show_view_action']),
    'show_pdf_action' => isset($_POST['show_pdf_action']),
    'show_print_action' => isset($_POST['show_print_action']),
  ];

  $invoicePresets = [
    'billing-desk' => [
      'show_add_invoice_panel' => true,
      'show_search_filters' => true,
      'show_invoice_list' => true,
      'show_customer_column' => true,
      'show_tax_column' => true,
      'show_shipping_column' => true,
      'show_status_column' => true,
      'show_created_at_column' => true,
      'show_view_action' => true,
      'show_pdf_action' => true,
      'show_print_action' => true,
    ],
    'collection-review' => [
      'show_add_invoice_panel' => false,
      'show_search_filters' => true,
      'show_invoice_list' => true,
      'show_customer_column' => true,
      'show_tax_column' => false,
      'show_shipping_column' => false,
      'show_status_column' => true,
      'show_created_at_column' => true,
      'show_view_action' => true,
      'show_pdf_action' => true,
      'show_print_action' => false,
    ],
    'print-station' => [
      'show_add_invoice_panel' => false,
      'show_search_filters' => true,
      'show_invoice_list' => true,
      'show_customer_column' => false,
      'show_tax_column' => false,
      'show_shipping_column' => false,
      'show_status_column' => false,
      'show_created_at_column' => false,
      'show_view_action' => false,
      'show_pdf_action' => true,
      'show_print_action' => true,
    ],
  ];

  if ($invoicePreset !== '' && isset($invoicePresets[$invoicePreset])) {
    $invoicePreferences = $invoicePresets[$invoicePreset];
  }

  $saved = $adminInvoiceSettingsAvailable && function_exists('girffonAdminSaveInvoicePreferences')
    ? girffonAdminSaveInvoicePreferences($pdo, $adminCurrentId, $adminCurrentUsername, $invoicePreferences)
    : false;
  header('Location: /GirffoN/setting-invoices.php?' . ($saved ? 'status=' . rawurlencode('Invoice settings saved for your account.') : 'error=' . rawurlencode('Unable to save invoice settings right now.')));
  exit;
}

$adminInvoicePreferences = [
  'show_add_invoice_panel' => true,
  'show_search_filters' => true,
  'show_invoice_list' => true,
  'show_customer_column' => true,
  'show_tax_column' => true,
  'show_shipping_column' => true,
  'show_status_column' => true,
  'show_created_at_column' => true,
  'show_view_action' => true,
  'show_pdf_action' => true,
  'show_print_action' => true,
];

if ($adminInvoiceSettingsAvailable && function_exists('girffonAdminFetchInvoicePreferences')) {
  $adminInvoicePreferences = girffonAdminFetchInvoicePreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$escapeInvoiceSetting = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$invoiceSettingGroups = [
  'workspace' => [
    'title' => 'Workspace Layout',
    'note' => 'Control the high-level billing panels in the invoice workspace.',
    'settings' => [
      'show_add_invoice_panel' => ['label' => 'Add Invoice Panel', 'note' => 'Show the manual invoice entry form at the top of the page.'],
      'show_search_filters' => ['label' => 'Search & Filter', 'note' => 'Show the invoice filtering and search tools.'],
      'show_invoice_list' => ['label' => 'Invoice List', 'note' => 'Show the main database invoice table.'],
    ],
  ],
  'columns' => [
    'title' => 'Table Columns',
    'note' => 'Adjust the density of invoice information per admin account.',
    'settings' => [
      'show_customer_column' => ['label' => 'Customer Column', 'note' => 'Show customer name and email inside the invoice list.'],
      'show_tax_column' => ['label' => 'Tax Column', 'note' => 'Show tax values inside the table.'],
      'show_shipping_column' => ['label' => 'Shipping Column', 'note' => 'Show shipping amounts for each invoice.'],
      'show_status_column' => ['label' => 'Status Column', 'note' => 'Show invoice/payment status inside the list.'],
      'show_created_at_column' => ['label' => 'Created At Column', 'note' => 'Show invoice creation timestamps.'],
    ],
  ],
  'actions' => [
    'title' => 'Document Actions',
    'note' => 'Choose which invoice document actions stay available in each row.',
    'settings' => [
      'show_view_action' => ['label' => 'View Action', 'note' => 'Open invoice details in a new tab.'],
      'show_pdf_action' => ['label' => 'PDF Action', 'note' => 'Open or download the invoice PDF output.'],
      'show_print_action' => ['label' => 'Print Action', 'note' => 'Open the print-ready invoice page.'],
    ],
  ],
];

$invoicePresetCards = [
  'billing-desk' => ['title' => 'Billing Desk', 'note' => 'Full invoice creation, review, and document access for accounting work.'],
  'collection-review' => ['title' => 'Collection Review', 'note' => 'Cleaner review layout focused on status and customer visibility.'],
  'print-station' => ['title' => 'Print Station', 'note' => 'Minimal table optimized for PDF and print workflows.'],
];

$activeInvoiceSettingsCount = 0;
foreach ($adminInvoicePreferences as $preferenceEnabled) {
  if (!empty($preferenceEnabled)) {
    $activeInvoiceSettingsCount++;
  }
}

$invoiceSettingsTotal = count($adminInvoicePreferences);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Invoice Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .invoice-settings-hero {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.85fr);
      gap: 18px;
      margin-bottom: 18px;
    }

    .invoice-settings-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .invoice-settings-section-grid {
      display: grid;
      gap: 18px;
    }

    .invoice-settings-summary-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .invoice-settings-metric {
      padding: 16px 18px;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.86);
      border: 1px solid rgba(199, 165, 75, 0.16);
    }

    .invoice-settings-metric span {
      display: block;
      color: #7d715f;
      font-size: 0.86rem;
      margin-bottom: 6px;
    }

    .invoice-settings-metric strong {
      display: block;
      color: #2b241b;
      font-size: 1.4rem;
    }

    .invoice-preset-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .invoice-preset-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 20px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      background: linear-gradient(180deg, rgba(255, 252, 246, 0.96), rgba(255, 255, 255, 0.82));
    }

    .invoice-preset-card h3,
    .invoice-settings-section h3 {
      margin: 0;
      color: #2b241b;
      font-size: 1rem;
    }

    .invoice-preset-card p,
    .invoice-settings-section-note {
      margin: 0;
      color: #7d715f;
      line-height: 1.6;
      font-size: 0.92rem;
    }

    .invoice-preset-card .admin-button {
      justify-self: start;
    }

    .invoice-settings-section {
      display: grid;
      gap: 16px;
      padding: 20px;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(199, 165, 75, 0.14);
    }

    .invoice-setting-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(199, 165, 75, 0.16);
      background: rgba(255, 255, 255, 0.82);
    }

    .invoice-setting-toggle {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
    }

    .invoice-setting-toggle label {
      display: grid;
      gap: 6px;
      cursor: pointer;
    }

    .invoice-setting-toggle input[type="checkbox"] {
      margin-top: 4px;
      width: 20px;
      height: 20px;
      accent-color: #c7a54b;
      cursor: pointer;
    }

    .invoice-setting-title {
      font-size: 1rem;
      font-weight: 700;
      color: #2b241b;
    }

    .invoice-setting-note {
      color: #7d715f;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      .invoice-settings-hero,
      .invoice-settings-summary-grid,
      .invoice-preset-grid,
      .invoice-settings-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 980px) {
      .invoice-settings-hero,
      .invoice-preset-grid {
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
        <p>Configure your personal invoice workspace and document actions.</p>
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
          <strong>Invoice Workspace</strong>
          <p class="admin-panel-note">Tune billing panels, table density, and document actions for your admin account.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Invoice Settings</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <a class="admin-button admin-button-soft admin-refresh-button" href="admin-invoices.php" aria-label="Back to Invoices" title="Back to Invoices">Invoices</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-invoices.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <section class="invoice-settings-hero">
          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Invoice Control Center</h2>
                <p class="admin-panel-note">Signed in as <?php echo $escapeInvoiceSetting($adminCurrentUsername !== '' ? $adminCurrentUsername : 'GirffoN Admin'); ?>. These settings are saved per admin and directly change what you see in the invoice workspace.</p>
              </div>
            </div>
            <div class="invoice-preset-grid">
              <?php foreach ($invoicePresetCards as $presetKey => $presetMeta): ?>
                <article class="invoice-preset-card">
                  <div>
                    <h3><?php echo $escapeInvoiceSetting($presetMeta['title']); ?></h3>
                    <p><?php echo $escapeInvoiceSetting($presetMeta['note']); ?></p>
                  </div>
                  <form method="POST" action="setting-invoices.php">
                    <input type="hidden" name="invoice_preset" value="<?php echo $escapeInvoiceSetting($presetKey); ?>">
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
                <p class="admin-panel-note">Quick snapshot of how complete or print-focused your invoice workspace currently is.</p>
              </div>
            </div>
            <div class="invoice-settings-summary-grid">
              <div class="invoice-settings-metric">
                <span>Enabled Controls</span>
                <strong><?php echo $escapeInvoiceSetting($activeInvoiceSettingsCount); ?> / <?php echo $escapeInvoiceSetting($invoiceSettingsTotal); ?></strong>
              </div>
              <div class="invoice-settings-metric">
                <span>Workspace Mode</span>
                <strong><?php echo $escapeInvoiceSetting(!empty($adminInvoicePreferences['show_add_invoice_panel']) ? 'Full Billing' : 'Review Focus'); ?></strong>
              </div>
              <div class="invoice-settings-metric">
                <span>Document Access</span>
                <strong><?php echo $escapeInvoiceSetting(!empty($adminInvoicePreferences['show_pdf_action']) || !empty($adminInvoicePreferences['show_print_action']) ? 'Enabled' : 'Restricted'); ?></strong>
              </div>
              <div class="invoice-settings-metric">
                <span>Table Density</span>
                <strong><?php echo $escapeInvoiceSetting(!empty($adminInvoicePreferences['show_customer_column']) && !empty($adminInvoicePreferences['show_tax_column']) && !empty($adminInvoicePreferences['show_shipping_column']) ? 'Detailed' : 'Lean'); ?></strong>
              </div>
            </div>
          </article>
        </section>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Fine-Grained Invoice Settings</h2>
              <p class="admin-panel-note">Build your own invoice workspace with grouped controls for layout, table columns, and document actions.</p>
            </div>
          </div>

          <form method="POST" action="setting-invoices.php" novalidate>
            <div class="invoice-settings-section-grid">
              <?php foreach ($invoiceSettingGroups as $groupMeta): ?>
                <section class="invoice-settings-section">
                  <div>
                    <h3><?php echo $escapeInvoiceSetting($groupMeta['title']); ?></h3>
                    <p class="invoice-settings-section-note"><?php echo $escapeInvoiceSetting($groupMeta['note']); ?></p>
                  </div>
                  <div class="invoice-settings-grid">
                    <?php foreach ($groupMeta['settings'] as $settingKey => $settingMeta): ?>
                      <section class="invoice-setting-card">
                        <div class="invoice-setting-toggle">
                          <label for="<?php echo $escapeInvoiceSetting($settingKey); ?>">
                            <span class="invoice-setting-title"><?php echo $escapeInvoiceSetting($settingMeta['label']); ?></span>
                            <span class="invoice-setting-note"><?php echo $escapeInvoiceSetting($settingMeta['note']); ?></span>
                          </label>
                          <input id="<?php echo $escapeInvoiceSetting($settingKey); ?>" name="<?php echo $escapeInvoiceSetting($settingKey); ?>" type="checkbox" <?php if (!empty($adminInvoicePreferences[$settingKey])): ?>checked<?php endif; ?>>
                        </div>
                      </section>
                    <?php endforeach; ?>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>

            <div class="admin-form-actions" style="margin-top:18px;">
              <button class="admin-button" type="submit">Save Invoice Settings</button>
              <a class="admin-button admin-button-soft" href="admin-invoices.php">Back to Invoices</a>
            </div>
          </form>

          <?php if ($adminInvoiceSettingStatus !== ''): ?>
            <p class="admin-feedback" role="status" aria-live="polite" style="margin-top:16px;"><?php echo $escapeInvoiceSetting($adminInvoiceSettingStatus); ?></p>
          <?php elseif ($adminInvoiceSettingError !== ''): ?>
            <p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;"><?php echo $escapeInvoiceSetting($adminInvoiceSettingError); ?></p>
          <?php elseif (!$adminInvoiceSettingsAvailable): ?>
            <p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;">Invoice settings helper is missing on this host. Upload backend/admin/invoice-settings-data.php first.</p>
          <?php endif; ?>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>