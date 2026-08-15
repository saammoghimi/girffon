<?php
require_once __DIR__ . "/backend/admin/session.php";

$adminDashboardSettingsFile = __DIR__ . "/backend/admin/dashboard-settings-data.php";
$adminDashboardSettingsAvailable = is_file($adminDashboardSettingsFile);
if ($adminDashboardSettingsAvailable) {
  require_once $adminDashboardSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$adminDashboardSettingStatus = trim((string) ($_GET['status'] ?? ''));
$adminDashboardSettingError = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $dashboardPreferences = [
    'show_summary_cards' => isset($_POST['show_summary_cards']),
    'show_daily_stats' => isset($_POST['show_daily_stats']),
    'show_monthly_stats' => isset($_POST['show_monthly_stats']),
    'show_yearly_stats' => isset($_POST['show_yearly_stats']),
    'show_recent_activity' => isset($_POST['show_recent_activity']),
    'show_login_activity' => isset($_POST['show_login_activity']),
    'show_analytics_explorer' => isset($_POST['show_analytics_explorer']),
    'show_weather_widget' => isset($_POST['show_weather_widget']),
    'show_world_clock' => isset($_POST['show_world_clock']),
    'show_active_admins' => isset($_POST['show_active_admins']),
    'show_visitor_analytics' => isset($_POST['show_visitor_analytics']),
  ];

  $saved = $adminDashboardSettingsAvailable && function_exists('girffonAdminSaveDashboardPreferences')
    ? girffonAdminSaveDashboardPreferences($pdo, $adminCurrentId, $adminCurrentUsername, $dashboardPreferences)
    : false;
  header('Location: /GirffoN/dashboard-setting.php?' . ($saved ? 'status=' . rawurlencode('Dashboard settings saved for your account.') : 'error=' . rawurlencode('Unable to save dashboard settings right now.')));
  exit;
}

$adminDashboardPreferences = [
  'show_summary_cards' => true,
  'show_daily_stats' => true,
  'show_monthly_stats' => true,
  'show_yearly_stats' => true,
  'show_recent_activity' => true,
  'show_login_activity' => true,
  'show_analytics_explorer' => true,
  'show_weather_widget' => true,
  'show_world_clock' => true,
  'show_active_admins' => true,
  'show_visitor_analytics' => true,
];

if ($adminDashboardSettingsAvailable && function_exists('girffonAdminFetchDashboardPreferences')) {
  $adminDashboardPreferences = girffonAdminFetchDashboardPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}
$escapeDashboardSetting = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$dashboardSettingToggles = [
  'show_summary_cards' => ['label' => 'Overview Cards', 'note' => 'Top KPI cards like products, members, orders, invoices, and revenue.'],
  'show_daily_stats' => ['label' => 'Daily Stats', 'note' => 'Show the Today statistics panel on admin-dashboard.php.'],
  'show_monthly_stats' => ['label' => 'Monthly Stats', 'note' => 'Show the This Month statistics panel on admin-dashboard.php.'],
  'show_yearly_stats' => ['label' => 'Yearly Stats', 'note' => 'Show the This Year statistics panel on admin-dashboard.php.'],
  'show_recent_activity' => ['label' => 'Recent Activity Panels', 'note' => 'Recent orders, members, messages, low stock, today orders, and invoices.'],
  'show_login_activity' => ['label' => 'Login Activity', 'note' => 'Recent admin sign-in history on the dashboard.'],
  'show_analytics_explorer' => ['label' => 'Analytics Explorer', 'note' => 'Daily, monthly, yearly analytics and PDF export block.'],
  'show_weather_widget' => ['label' => 'Weather Widget', 'note' => 'Live weather and forecast cards for your dashboard only.'],
  'show_world_clock' => ['label' => 'World Clock', 'note' => 'Clock, timezone status, and quick city comparison.'],
  'show_active_admins' => ['label' => 'Active Admins', 'note' => 'Admins active in the last 30 minutes.'],
  'show_visitor_analytics' => ['label' => 'Visitors Analytics', 'note' => 'Public website visitor, source, page, cart, checkout, browser, and device analytics.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Dashboard Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .dashboard-settings-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .dashboard-setting-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(199, 165, 75, 0.16);
      background: rgba(255, 255, 255, 0.82);
    }

    .dashboard-setting-toggle {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
    }

    .dashboard-setting-toggle label {
      display: grid;
      gap: 6px;
      cursor: pointer;
    }

    .dashboard-setting-toggle input[type="checkbox"] {
      margin-top: 4px;
      width: 20px;
      height: 20px;
      accent-color: #c7a54b;
      cursor: pointer;
    }

    .dashboard-setting-title {
      font-size: 1rem;
      font-weight: 700;
      color: #2b241b;
    }

    .dashboard-setting-note {
      color: #7d715f;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      .dashboard-settings-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="dashboard-settings">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Personal dashboard widget visibility for each admin account.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link is-active" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
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
          <strong>Dashboard Settings</strong>
          <p class="admin-panel-note">These switches affect only your own dashboard login.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title">Dashboard Settings</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="dashboard-setting.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if (!$adminDashboardSettingsAvailable): ?>
          <div class="admin-feedback is-error" role="status" aria-live="polite">
            Dashboard settings storage file is missing on this server. The dashboard will use default widgets until the deployment is complete.
          </div>
        <?php endif; ?>

        <?php if ($adminDashboardSettingStatus || $adminDashboardSettingError): ?>
          <div class="admin-feedback<?php if ($adminDashboardSettingError): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
            <?php echo $escapeDashboardSetting($adminDashboardSettingError ?: $adminDashboardSettingStatus); ?>
          </div>
        <?php endif; ?>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Personal Dashboard Visibility</h2>
              <p class="admin-panel-note">Signed in as <?php echo $escapeDashboardSetting($adminCurrentUsername !== '' ? $adminCurrentUsername : 'GirffoN Admin'); ?>. These switches are stored separately for each admin account.</p>
            </div>
          </div>

          <form method="POST" action="dashboard-setting.php" novalidate>
            <div class="dashboard-settings-grid">
              <?php foreach ($dashboardSettingToggles as $settingKey => $settingMeta): ?>
                <section class="dashboard-setting-card">
                  <div class="dashboard-setting-toggle">
                    <label for="<?php echo $escapeDashboardSetting($settingKey); ?>">
                      <span class="dashboard-setting-title"><?php echo $escapeDashboardSetting($settingMeta['label']); ?></span>
                      <span class="dashboard-setting-note"><?php echo $escapeDashboardSetting($settingMeta['note']); ?></span>
                    </label>
                    <input id="<?php echo $escapeDashboardSetting($settingKey); ?>" name="<?php echo $escapeDashboardSetting($settingKey); ?>" type="checkbox" <?php if (!empty($adminDashboardPreferences[$settingKey])): ?>checked<?php endif; ?>>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>

            <div class="admin-form-actions" style="margin-top: 22px;">
              <button class="admin-button admin-button-accent" type="submit">Save Dashboard Settings</button>
              <a class="admin-button admin-button-soft" href="admin-dashboard.php">Back To Dashboard</a>
            </div>
          </form>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260804r13"></script>
</body>
</html>