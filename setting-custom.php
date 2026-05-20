<?php
require_once __DIR__ . "/backend/admin/session.php";

$customOrderSettingsFile = __DIR__ . "/backend/admin/custom-order-settings-data.php";
$customOrderSettingsAvailable = is_file($customOrderSettingsFile);
if ($customOrderSettingsAvailable) {
  require_once $customOrderSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$customOrderSettingStatus = trim((string) ($_GET['status'] ?? ''));
$customOrderSettingError = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $customOrderPreferences = [
    'show_summary_cards' => isset($_POST['show_summary_cards']),
    'show_order_list' => isset($_POST['show_order_list']),
    'show_order_id_column' => isset($_POST['show_order_id_column']),
    'show_customer_column' => isset($_POST['show_customer_column']),
    'show_product_column' => isset($_POST['show_product_column']),
    'show_upload_count_column' => isset($_POST['show_upload_count_column']),
    'show_text_count_column' => isset($_POST['show_text_count_column']),
    'show_status_column' => isset($_POST['show_status_column']),
    'show_date_column' => isset($_POST['show_date_column']),
    'show_view_action' => isset($_POST['show_view_action']),
  ];

  $saved = $customOrderSettingsAvailable && function_exists('girffonAdminSaveCustomOrderPreferences')
    ? girffonAdminSaveCustomOrderPreferences($pdo, $adminCurrentId, $adminCurrentUsername, $customOrderPreferences)
    : false;

  header('Location: /GirffoN/setting-custom.php?' . ($saved ? 'status=' . rawurlencode('Custom design order settings saved for your account.') : 'error=' . rawurlencode('Unable to save custom design order settings right now.')));
  exit;
}

$customOrderPreferences = [
  'show_summary_cards' => true,
  'show_order_list' => true,
  'show_order_id_column' => true,
  'show_customer_column' => true,
  'show_product_column' => true,
  'show_upload_count_column' => true,
  'show_text_count_column' => true,
  'show_status_column' => true,
  'show_date_column' => true,
  'show_view_action' => true,
];

if ($customOrderSettingsAvailable && function_exists('girffonAdminFetchCustomOrderPreferences')) {
  $customOrderPreferences = girffonAdminFetchCustomOrderPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$escapeCustomSetting = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$customOrderSettingToggles = [
  'show_summary_cards' => ['label' => 'Summary Cards', 'note' => 'Top custom design order totals for total, pending, paid review, and production states.'],
  'show_order_list' => ['label' => 'Order List Panel', 'note' => 'Show or hide the main custom design orders table.'],
  'show_order_id_column' => ['label' => 'Order ID Column', 'note' => 'Display the custom order code column.'],
  'show_customer_column' => ['label' => 'Customer Column', 'note' => 'Display customer name and email in the list.'],
  'show_product_column' => ['label' => 'Product Column', 'note' => 'Display the selected custom product name.'],
  'show_upload_count_column' => ['label' => 'Upload Count Column', 'note' => 'Display the number of uploaded design assets.'],
  'show_text_count_column' => ['label' => 'Text Count Column', 'note' => 'Display the number of saved text layers.'],
  'show_status_column' => ['label' => 'Status Column', 'note' => 'Display the current custom order workflow status.'],
  'show_date_column' => ['label' => 'Date Column', 'note' => 'Display the Rome-formatted created date.'],
  'show_view_action' => ['label' => 'View Action', 'note' => 'Keep the quick View button in each row.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Custom Design Order Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260520r1">
  <style>
    .custom-settings-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .custom-setting-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(199, 165, 75, 0.16);
      background: rgba(255, 255, 255, 0.82);
    }

    .custom-setting-toggle {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
    }

    .custom-setting-toggle label {
      display: grid;
      gap: 6px;
      cursor: pointer;
    }

    .custom-setting-toggle input[type="checkbox"] {
      margin-top: 4px;
      width: 20px;
      height: 20px;
      accent-color: #c7a54b;
      cursor: pointer;
    }

    .custom-setting-title {
      font-size: 1rem;
      font-weight: 700;
      color: #2b241b;
    }

    .custom-setting-note {
      color: #7d715f;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      .custom-settings-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="custom-order-settings">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Personal visibility controls for the custom design order workspace.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard"><span class="admin-nav-link-index">1. </span><span class="admin-nav-link-label">Dashboard</span></a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products"><span class="admin-nav-link-index">2. </span><span class="admin-nav-link-label">Products</span></a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders"><span class="admin-nav-link-index">3. </span><span class="admin-nav-link-label">Orders</span></a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices"><span class="admin-nav-link-index">4. </span><span class="admin-nav-link-label">Invoices</span></a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages"><span class="admin-nav-link-index">5. </span><span class="admin-nav-link-label">Messages</span></a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users"><span class="admin-nav-link-index">6. </span><span class="admin-nav-link-label">Users</span></a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter"><span class="admin-nav-link-index">7. </span><span class="admin-nav-link-label">Newsletter</span></a>
        <a class="admin-nav-link is-active" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders"><span class="admin-nav-link-index">8. </span><span class="admin-nav-link-label">Custom Design Orders</span></a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings"><span class="admin-nav-link-index">9. </span><span class="admin-nav-link-label">Settings</span></a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Custom Order Settings</strong>
          <p class="admin-panel-note">These switches affect only your own custom design orders workspace.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title">Custom Design Order Settings</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-custom.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if (!$customOrderSettingsAvailable): ?>
          <div class="admin-feedback is-error" role="status" aria-live="polite">
            Custom design order settings storage file is missing on this server. Default visibility will be used until deployment is complete.
          </div>
        <?php endif; ?>

        <?php if ($customOrderSettingStatus || $customOrderSettingError): ?>
          <div class="admin-feedback<?php if ($customOrderSettingError): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
            <?php echo $escapeCustomSetting($customOrderSettingError ?: $customOrderSettingStatus); ?>
          </div>
        <?php endif; ?>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Custom Orders Workspace Visibility</h2>
              <p class="admin-panel-note">Signed in as <?php echo $escapeCustomSetting($adminCurrentUsername !== '' ? $adminCurrentUsername : 'GirffoN Admin'); ?>. These switches are stored separately for each admin account.</p>
            </div>
          </div>

          <form method="POST" action="setting-custom.php" novalidate>
            <div class="custom-settings-grid">
              <?php foreach ($customOrderSettingToggles as $settingKey => $settingMeta): ?>
                <section class="custom-setting-card">
                  <div class="custom-setting-toggle">
                    <label for="<?php echo $escapeCustomSetting($settingKey); ?>">
                      <span class="custom-setting-title"><?php echo $escapeCustomSetting($settingMeta['label']); ?></span>
                      <span class="custom-setting-note"><?php echo $escapeCustomSetting($settingMeta['note']); ?></span>
                    </label>
                    <input id="<?php echo $escapeCustomSetting($settingKey); ?>" name="<?php echo $escapeCustomSetting($settingKey); ?>" type="checkbox" <?php if (!empty($customOrderPreferences[$settingKey])): ?>checked<?php endif; ?>>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>

            <div class="admin-form-actions" style="margin-top: 22px;">
              <button class="admin-button admin-button-accent" type="submit">Save Custom Order Settings</button>
              <a class="admin-button admin-button-soft" href="admin-custom-orders.php">Back To Custom Orders</a>
            </div>
          </form>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260520r1"></script>
</body>
</html>