<?php
require_once __DIR__ . "/backend/admin/session.php";

$adminUserSettingsFile = __DIR__ . "/backend/admin/user-settings-data.php";
$adminUserSettingsAvailable = is_file($adminUserSettingsFile);
if ($adminUserSettingsAvailable) {
  require_once $adminUserSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$adminUserSettingStatus = trim((string) ($_GET['status'] ?? ''));
$adminUserSettingError = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $userPreset = trim((string) ($_POST['user_preset'] ?? ''));
  $userPreferences = [
    'show_summary_cards' => isset($_POST['show_summary_cards']),
    'show_filter_panel' => isset($_POST['show_filter_panel']),
    'show_users_directory' => isset($_POST['show_users_directory']),
    'show_username_column' => isset($_POST['show_username_column']),
    'show_email_column' => isset($_POST['show_email_column']),
    'show_phone_column' => isset($_POST['show_phone_column']),
    'show_country_column' => isset($_POST['show_country_column']),
    'show_city_column' => isset($_POST['show_city_column']),
    'show_address_column' => isset($_POST['show_address_column']),
    'show_role_column' => isset($_POST['show_role_column']),
    'show_status_column' => isset($_POST['show_status_column']),
    'show_created_at_column' => isset($_POST['show_created_at_column']),
    'show_view_action' => isset($_POST['show_view_action']),
    'show_edit_action' => isset($_POST['show_edit_action']),
    'show_orders_action' => isset($_POST['show_orders_action']),
    'show_invoices_action' => isset($_POST['show_invoices_action']),
    'show_email_action' => isset($_POST['show_email_action']),
    'show_sms_action' => isset($_POST['show_sms_action']),
    'show_delete_action' => isset($_POST['show_delete_action']),
  ];

  $userPresets = [
    'account-admin' => [
      'show_summary_cards' => true,
      'show_filter_panel' => true,
      'show_users_directory' => true,
      'show_username_column' => true,
      'show_email_column' => true,
      'show_phone_column' => true,
      'show_country_column' => true,
      'show_city_column' => true,
      'show_address_column' => true,
      'show_role_column' => true,
      'show_status_column' => true,
      'show_created_at_column' => true,
      'show_view_action' => true,
      'show_edit_action' => true,
      'show_orders_action' => true,
      'show_invoices_action' => true,
      'show_email_action' => true,
      'show_sms_action' => true,
      'show_delete_action' => true,
    ],
    'support-directory' => [
      'show_summary_cards' => true,
      'show_filter_panel' => true,
      'show_users_directory' => true,
      'show_username_column' => true,
      'show_email_column' => true,
      'show_phone_column' => true,
      'show_country_column' => true,
      'show_city_column' => true,
      'show_address_column' => false,
      'show_role_column' => true,
      'show_status_column' => true,
      'show_created_at_column' => true,
      'show_view_action' => true,
      'show_edit_action' => false,
      'show_orders_action' => true,
      'show_invoices_action' => true,
      'show_email_action' => true,
      'show_sms_action' => true,
      'show_delete_action' => false,
    ],
    'read-only-manager' => [
      'show_summary_cards' => true,
      'show_filter_panel' => true,
      'show_users_directory' => true,
      'show_username_column' => true,
      'show_email_column' => true,
      'show_phone_column' => false,
      'show_country_column' => true,
      'show_city_column' => true,
      'show_address_column' => false,
      'show_role_column' => true,
      'show_status_column' => true,
      'show_created_at_column' => true,
      'show_view_action' => true,
      'show_edit_action' => false,
      'show_orders_action' => true,
      'show_invoices_action' => true,
      'show_email_action' => false,
      'show_sms_action' => false,
      'show_delete_action' => false,
    ],
  ];

  if ($userPreset !== '' && isset($userPresets[$userPreset])) {
    $userPreferences = $userPresets[$userPreset];
  }

  $saved = $adminUserSettingsAvailable && function_exists('girffonAdminSaveUserPreferences')
    ? girffonAdminSaveUserPreferences($pdo, $adminCurrentId, $adminCurrentUsername, $userPreferences)
    : false;
  header('Location: /GirffoN/setting-users.php?' . ($saved ? 'status=' . rawurlencode('User settings saved for your account.') : 'error=' . rawurlencode('Unable to save user settings right now.')));
  exit;
}

$adminUserPreferences = girffonAdminUserSettingsDefault();
if ($adminUserSettingsAvailable && function_exists('girffonAdminFetchUserPreferences')) {
  $adminUserPreferences = girffonAdminFetchUserPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$escapeUserSetting = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$userSettingGroups = [
  'workspace' => [
    'title' => 'Workspace Layout',
    'note' => 'Control the high-level users dashboard blocks.',
    'settings' => [
      'show_summary_cards' => ['label' => 'Summary Cards', 'note' => 'Show member totals and growth metrics at the top of the page.'],
      'show_filter_panel' => ['label' => 'Filter Panel', 'note' => 'Show the search and filter tools for the users directory.'],
      'show_users_directory' => ['label' => 'Users Directory', 'note' => 'Show the main users table.'],
    ],
  ],
  'columns' => [
    'title' => 'Directory Columns',
    'note' => 'Tune the data density of the user directory for each admin account.',
    'settings' => [
      'show_username_column' => ['label' => 'Username Column', 'note' => 'Show usernames in the table.'],
      'show_email_column' => ['label' => 'Email Column', 'note' => 'Show user email addresses.'],
      'show_phone_column' => ['label' => 'Phone Column', 'note' => 'Show phone numbers in the directory.'],
      'show_country_column' => ['label' => 'Country Column', 'note' => 'Show countries in the directory.'],
      'show_city_column' => ['label' => 'City Column', 'note' => 'Show cities in the directory.'],
      'show_address_column' => ['label' => 'Address Column', 'note' => 'Show address details in the directory.'],
      'show_role_column' => ['label' => 'Role Column', 'note' => 'Show account role badges.'],
      'show_status_column' => ['label' => 'Status Column', 'note' => 'Show account status badges.'],
      'show_created_at_column' => ['label' => 'Created Date Column', 'note' => 'Show account creation dates.'],
    ],
  ],
  'actions' => [
    'title' => 'User Actions',
    'note' => 'Control which actions remain available inside the row menu.',
    'settings' => [
      'show_view_action' => ['label' => 'View Action', 'note' => 'Open the user profile viewer.'],
      'show_edit_action' => ['label' => 'Edit Action', 'note' => 'Open the user edit screen.'],
      'show_orders_action' => ['label' => 'Orders Action', 'note' => 'Jump to the selected user orders.'],
      'show_invoices_action' => ['label' => 'Invoices Action', 'note' => 'Jump to the selected user invoices.'],
      'show_email_action' => ['label' => 'Send Email Action', 'note' => 'Show the mail action when email exists.'],
      'show_sms_action' => ['label' => 'Send SMS Action', 'note' => 'Show the SMS action when phone exists.'],
      'show_delete_action' => ['label' => 'Delete Action', 'note' => 'Show the destructive delete action in the menu.'],
    ],
  ],
];

$userPresetCards = [
  'account-admin' => ['title' => 'Account Admin', 'note' => 'Full user management with edit, contact, and destructive actions available.'],
  'support-directory' => ['title' => 'Support Directory', 'note' => 'Support-friendly customer directory with contact and order shortcuts.'],
  'read-only-manager' => ['title' => 'Read-Only Manager', 'note' => 'Oversight-focused view with review actions but no destructive changes.'],
];

$activeUserSettingsCount = 0;
foreach ($adminUserPreferences as $preferenceEnabled) {
  if (!empty($preferenceEnabled)) {
    $activeUserSettingsCount++;
  }
}

$userSettingsTotal = count($adminUserPreferences);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN User Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .user-settings-hero {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.85fr);
      gap: 18px;
      margin-bottom: 18px;
    }

    .user-settings-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .user-settings-section-grid {
      display: grid;
      gap: 18px;
    }

    .user-settings-summary-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .user-settings-metric {
      padding: 16px 18px;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.86);
      border: 1px solid rgba(199, 165, 75, 0.16);
    }

    .user-settings-metric span {
      display: block;
      color: #7d715f;
      font-size: 0.86rem;
      margin-bottom: 6px;
    }

    .user-settings-metric strong {
      display: block;
      color: #2b241b;
      font-size: 1.4rem;
    }

    .user-preset-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .user-preset-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 20px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      background: linear-gradient(180deg, rgba(255, 252, 246, 0.96), rgba(255, 255, 255, 0.82));
    }

    .user-preset-card h3,
    .user-settings-section h3 {
      margin: 0;
      color: #2b241b;
      font-size: 1rem;
    }

    .user-preset-card p,
    .user-settings-section-note {
      margin: 0;
      color: #7d715f;
      line-height: 1.6;
      font-size: 0.92rem;
    }

    .user-preset-card .admin-button {
      justify-self: start;
    }

    .user-settings-section {
      display: grid;
      gap: 16px;
      padding: 20px;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(199, 165, 75, 0.14);
    }

    .user-setting-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(199, 165, 75, 0.16);
      background: rgba(255, 255, 255, 0.82);
    }

    .user-setting-toggle {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
    }

    .user-setting-toggle label {
      display: grid;
      gap: 6px;
      cursor: pointer;
    }

    .user-setting-toggle input[type="checkbox"] {
      margin-top: 4px;
      width: 20px;
      height: 20px;
      accent-color: #c7a54b;
      cursor: pointer;
    }

    .user-setting-title {
      font-size: 1rem;
      font-weight: 700;
      color: #2b241b;
    }

    .user-setting-note {
      color: #7d715f;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      .user-settings-hero,
      .user-settings-summary-grid,
      .user-preset-grid,
      .user-settings-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 980px) {
      .user-settings-hero,
      .user-preset-grid {
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
        <p>Configure your personal users workspace and account-management controls.</p>
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
          <strong>User Workspace</strong>
          <p class="admin-panel-note">Tune user metrics, directory density, and row actions for your admin account.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">User Settings</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <a class="admin-button admin-button-soft admin-refresh-button" href="admin-users.php" aria-label="Back to Users" title="Back to Users">Users</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-users.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <section class="user-settings-hero">
          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Users Control Center</h2>
                <p class="admin-panel-note">Signed in as <?php echo $escapeUserSetting($adminCurrentUsername !== '' ? $adminCurrentUsername : 'GirffoN Admin'); ?>. These settings are saved per admin and directly change what you see in the users workspace.</p>
              </div>
            </div>
            <div class="user-preset-grid">
              <?php foreach ($userPresetCards as $presetKey => $presetMeta): ?>
                <article class="user-preset-card">
                  <div>
                    <h3><?php echo $escapeUserSetting($presetMeta['title']); ?></h3>
                    <p><?php echo $escapeUserSetting($presetMeta['note']); ?></p>
                  </div>
                  <form method="POST" action="setting-users.php">
                    <input type="hidden" name="user_preset" value="<?php echo $escapeUserSetting($presetKey); ?>">
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
                <p class="admin-panel-note">Quick snapshot of how operational or review-focused your users workspace currently is.</p>
              </div>
            </div>
            <div class="user-settings-summary-grid">
              <div class="user-settings-metric">
                <span>Enabled Controls</span>
                <strong><?php echo $escapeUserSetting($activeUserSettingsCount); ?> / <?php echo $escapeUserSetting($userSettingsTotal); ?></strong>
              </div>
              <div class="user-settings-metric">
                <span>Workspace Mode</span>
                <strong><?php echo $escapeUserSetting(!empty($adminUserPreferences['show_filter_panel']) && !empty($adminUserPreferences['show_edit_action']) ? 'Operational' : 'Review'); ?></strong>
              </div>
              <div class="user-settings-metric">
                <span>Contact Tools</span>
                <strong><?php echo $escapeUserSetting(!empty($adminUserPreferences['show_email_action']) || !empty($adminUserPreferences['show_sms_action']) ? 'Enabled' : 'Hidden'); ?></strong>
              </div>
              <div class="user-settings-metric">
                <span>Destructive Actions</span>
                <strong><?php echo $escapeUserSetting(!empty($adminUserPreferences['show_delete_action']) ? 'Delete Visible' : 'Protected'); ?></strong>
              </div>
            </div>
          </article>
        </section>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Fine-Grained User Settings</h2>
              <p class="admin-panel-note">Build your own users workspace with grouped controls for layout, directory columns, and account actions.</p>
            </div>
          </div>

          <form method="POST" action="setting-users.php" novalidate>
            <div class="user-settings-section-grid">
              <?php foreach ($userSettingGroups as $groupMeta): ?>
                <section class="user-settings-section">
                  <div>
                    <h3><?php echo $escapeUserSetting($groupMeta['title']); ?></h3>
                    <p class="user-settings-section-note"><?php echo $escapeUserSetting($groupMeta['note']); ?></p>
                  </div>
                  <div class="user-settings-grid">
                    <?php foreach ($groupMeta['settings'] as $settingKey => $settingMeta): ?>
                      <section class="user-setting-card">
                        <div class="user-setting-toggle">
                          <label for="<?php echo $escapeUserSetting($settingKey); ?>">
                            <span class="user-setting-title"><?php echo $escapeUserSetting($settingMeta['label']); ?></span>
                            <span class="user-setting-note"><?php echo $escapeUserSetting($settingMeta['note']); ?></span>
                          </label>
                          <input id="<?php echo $escapeUserSetting($settingKey); ?>" name="<?php echo $escapeUserSetting($settingKey); ?>" type="checkbox" <?php if (!empty($adminUserPreferences[$settingKey])): ?>checked<?php endif; ?>>
                        </div>
                      </section>
                    <?php endforeach; ?>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>

            <div class="admin-form-actions" style="margin-top:18px;">
              <button class="admin-button" type="submit">Save User Settings</button>
              <a class="admin-button admin-button-soft" href="admin-users.php">Back to Users</a>
            </div>
          </form>

          <?php if ($adminUserSettingStatus !== ''): ?>
            <p class="admin-feedback" role="status" aria-live="polite" style="margin-top:16px;"><?php echo $escapeUserSetting($adminUserSettingStatus); ?></p>
          <?php elseif ($adminUserSettingError !== ''): ?>
            <p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;"><?php echo $escapeUserSetting($adminUserSettingError); ?></p>
          <?php elseif (!$adminUserSettingsAvailable): ?>
            <p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;">User settings helper is missing on this host. Upload backend/admin/user-settings-data.php first.</p>
          <?php endif; ?>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>