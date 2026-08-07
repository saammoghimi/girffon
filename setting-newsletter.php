<?php
require_once __DIR__ . '/backend/admin/session.php';

$adminNewsletterSettingsFile = __DIR__ . '/backend/admin/newsletter-settings-data.php';
$adminNewsletterSettingsAvailable = is_file($adminNewsletterSettingsFile);
if ($adminNewsletterSettingsAvailable) {
  require_once $adminNewsletterSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$adminNewsletterSettingStatus = trim((string) ($_GET['status'] ?? ''));
$adminNewsletterSettingError = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $newsletterPreset = trim((string) ($_POST['newsletter_preset'] ?? ''));
  $newsletterPreferences = [
    'show_summary_cards' => isset($_POST['show_summary_cards']),
    'show_test_email_panel' => isset($_POST['show_test_email_panel']),
    'show_catalog_campaign_panel' => isset($_POST['show_catalog_campaign_panel']),
    'show_birthday_panel' => isset($_POST['show_birthday_panel']),
    'show_promotional_panel' => isset($_POST['show_promotional_panel']),
    'show_campaign_log_panel' => isset($_POST['show_campaign_log_panel']),
    'show_birthday_log_panel' => isset($_POST['show_birthday_log_panel']),
    'show_promotional_log_panel' => isset($_POST['show_promotional_log_panel']),
    'show_subscriber_phone_column' => isset($_POST['show_subscriber_phone_column']),
    'show_subscriber_promotional_column' => isset($_POST['show_subscriber_promotional_column']),
    'show_subscriber_birthday_column' => isset($_POST['show_subscriber_birthday_column']),
    'show_campaign_transport_column' => isset($_POST['show_campaign_transport_column']),
    'show_birthday_transport_column' => isset($_POST['show_birthday_transport_column']),
    'show_promotional_transport_column' => isset($_POST['show_promotional_transport_column']),
  ];

  $newsletterPresets = [
    'campaign-control' => [
      'show_summary_cards' => true,
      'show_test_email_panel' => true,
      'show_catalog_campaign_panel' => true,
      'show_birthday_panel' => true,
      'show_promotional_panel' => true,
      'show_campaign_log_panel' => true,
      'show_birthday_log_panel' => true,
      'show_promotional_log_panel' => true,
      'show_subscriber_phone_column' => true,
      'show_subscriber_promotional_column' => true,
      'show_subscriber_birthday_column' => true,
      'show_campaign_transport_column' => true,
      'show_birthday_transport_column' => true,
      'show_promotional_transport_column' => true,
    ],
    'delivery-monitor' => [
      'show_summary_cards' => true,
      'show_test_email_panel' => false,
      'show_catalog_campaign_panel' => false,
      'show_birthday_panel' => true,
      'show_promotional_panel' => true,
      'show_campaign_log_panel' => true,
      'show_birthday_log_panel' => true,
      'show_promotional_log_panel' => true,
      'show_subscriber_phone_column' => false,
      'show_subscriber_promotional_column' => false,
      'show_subscriber_birthday_column' => false,
      'show_campaign_transport_column' => true,
      'show_birthday_transport_column' => true,
      'show_promotional_transport_column' => true,
    ],
    'catalog-editor' => [
      'show_summary_cards' => true,
      'show_test_email_panel' => true,
      'show_catalog_campaign_panel' => true,
      'show_birthday_panel' => false,
      'show_promotional_panel' => false,
      'show_campaign_log_panel' => true,
      'show_birthday_log_panel' => false,
      'show_promotional_log_panel' => false,
      'show_subscriber_phone_column' => true,
      'show_subscriber_promotional_column' => true,
      'show_subscriber_birthday_column' => true,
      'show_campaign_transport_column' => true,
      'show_birthday_transport_column' => false,
      'show_promotional_transport_column' => false,
    ],
  ];

  if ($newsletterPreset !== '' && isset($newsletterPresets[$newsletterPreset])) {
    $newsletterPreferences = $newsletterPresets[$newsletterPreset];
  }

  $saved = $adminNewsletterSettingsAvailable && function_exists('girffonAdminSaveNewsletterPreferences')
    ? girffonAdminSaveNewsletterPreferences($pdo, $adminCurrentId, $adminCurrentUsername, $newsletterPreferences)
    : false;
  header('Location: /GirffoN/setting-newsletter.php?' . ($saved ? 'status=' . rawurlencode('Newsletter settings saved for your account.') : 'error=' . rawurlencode('Unable to save newsletter settings right now.')));
  exit;
}

$adminNewsletterPreferences = function_exists('girffonAdminNewsletterSettingsDefault')
  ? girffonAdminNewsletterSettingsDefault()
  : [];
if ($adminNewsletterSettingsAvailable && function_exists('girffonAdminFetchNewsletterPreferences')) {
  $adminNewsletterPreferences = girffonAdminFetchNewsletterPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$escapeNewsletterSetting = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$newsletterSettingGroups = [
  'workspace' => [
    'title' => 'Workspace Layout',
    'note' => 'Control the main newsletter operation panels.',
    'settings' => [
      'show_summary_cards' => ['label' => 'Summary Cards', 'note' => 'Show newsletter audience summary cards.'],
      'show_test_email_panel' => ['label' => 'Admin Test Email', 'note' => 'Show the mail transport test panel.'],
      'show_catalog_campaign_panel' => ['label' => 'Catalog Campaign', 'note' => 'Show the manual catalog email composer and subscriber selector.'],
      'show_birthday_panel' => ['label' => 'Birthday Automation', 'note' => 'Show the birthday email automation panel and debug link.'],
      'show_promotional_panel' => ['label' => 'Promotional Campaign', 'note' => 'Show the premium promotional campaign workspace.'],
    ],
  ],
  'logs' => [
    'title' => 'Delivery Logs',
    'note' => 'Control which log panels remain visible for delivery monitoring.',
    'settings' => [
      'show_campaign_log_panel' => ['label' => 'Campaign Log', 'note' => 'Show recent catalog campaign send logs.'],
      'show_birthday_log_panel' => ['label' => 'Birthday Log', 'note' => 'Show recent birthday email logs.'],
      'show_promotional_log_panel' => ['label' => 'Promotional Log', 'note' => 'Show recent promotional email logs.'],
    ],
  ],
  'columns' => [
    'title' => 'Audience And Log Columns',
    'note' => 'Tune how much subscriber and transport detail stays visible.',
    'settings' => [
      'show_subscriber_phone_column' => ['label' => 'Subscriber Phone', 'note' => 'Show phone numbers in the catalog subscriber list.'],
      'show_subscriber_promotional_column' => ['label' => 'Promotional Preference', 'note' => 'Show promotional-email preference in the subscriber list.'],
      'show_subscriber_birthday_column' => ['label' => 'Birthday Preference', 'note' => 'Show birthday-discount preference in the subscriber list.'],
      'show_campaign_transport_column' => ['label' => 'Campaign Transport', 'note' => 'Show delivery transport in campaign logs.'],
      'show_birthday_transport_column' => ['label' => 'Birthday Transport', 'note' => 'Show delivery transport in birthday logs.'],
      'show_promotional_transport_column' => ['label' => 'Promotional Transport', 'note' => 'Show delivery transport in promotional logs.'],
    ],
  ],
];

$newsletterPresetCards = [
  'campaign-control' => ['title' => 'Campaign Control', 'note' => 'Full newsletter operations with campaign panels and logs enabled.'],
  'delivery-monitor' => ['title' => 'Delivery Monitor', 'note' => 'Focus on logs, birthday automation, and promotional delivery health.'],
  'catalog-editor' => ['title' => 'Catalog Editor', 'note' => 'Focused workspace for catalog content and subscriber selection.'],
];

$activeNewsletterSettingsCount = 0;
foreach ($adminNewsletterPreferences as $preferenceEnabled) {
  if (!empty($preferenceEnabled)) {
    $activeNewsletterSettingsCount++;
  }
}

$newsletterSettingsTotal = count($adminNewsletterPreferences);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Newsletter Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .newsletter-settings-hero { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(280px,0.85fr); gap:18px; margin-bottom:18px; }
    .newsletter-settings-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:18px; }
    .newsletter-settings-section-grid { display:grid; gap:18px; }
    .newsletter-settings-summary-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; }
    .newsletter-settings-metric { padding:16px 18px; border-radius:18px; background:rgba(255,255,255,0.86); border:1px solid rgba(199,165,75,0.16); }
    .newsletter-settings-metric span { display:block; color:#7d715f; font-size:0.86rem; margin-bottom:6px; }
    .newsletter-settings-metric strong { display:block; color:#2b241b; font-size:1.4rem; }
    .newsletter-preset-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:14px; }
    .newsletter-preset-card { display:grid; gap:12px; padding:18px; border-radius:20px; border:1px solid rgba(199,165,75,0.18); background:linear-gradient(180deg, rgba(255,252,246,0.96), rgba(255,255,255,0.82)); }
    .newsletter-preset-card h3, .newsletter-settings-section h3 { margin:0; color:#2b241b; font-size:1rem; }
    .newsletter-preset-card p, .newsletter-settings-section-note { margin:0; color:#7d715f; line-height:1.6; font-size:0.92rem; }
    .newsletter-preset-card .admin-button { justify-self:start; }
    .newsletter-settings-section { display:grid; gap:16px; padding:20px; border-radius:24px; background:rgba(255,255,255,0.8); border:1px solid rgba(199,165,75,0.14); }
    .newsletter-setting-card { display:grid; gap:12px; padding:18px; border-radius:22px; border:1px solid rgba(199,165,75,0.16); background:rgba(255,255,255,0.82); }
    .newsletter-setting-toggle { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
    .newsletter-setting-toggle label { display:grid; gap:6px; cursor:pointer; }
    .newsletter-setting-toggle input[type="checkbox"] { margin-top:4px; width:20px; height:20px; accent-color:#c7a54b; cursor:pointer; }
    .newsletter-setting-title { font-size:1rem; font-weight:700; color:#2b241b; }
    .newsletter-setting-note { color:#7d715f; font-size:0.92rem; line-height:1.6; }
    @media (max-width:720px) { .newsletter-settings-hero, .newsletter-settings-summary-grid, .newsletter-preset-grid, .newsletter-settings-grid { grid-template-columns:1fr; } }
    @media (max-width:980px) { .newsletter-settings-hero, .newsletter-preset-grid { grid-template-columns:1fr; } }
  </style>
</head>
<body class="admin-page" data-admin-page="settings">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo"><img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo"></span>
        <p>Configure your personal newsletter workspace and campaign monitoring controls.</p>
      </div>
      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link" href="admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
      </nav>
      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card"><strong>Newsletter Workspace</strong><p class="admin-panel-note">Tune campaign panels, audience detail, and delivery logs for your admin account.</p></section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>
    <main class="admin-main">
      <header class="admin-topbar">
        <div><p class="admin-page-subtitle">Admin</p><h1 class="admin-page-title" id="adminCurrentPage">Newsletter Settings</h1></div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <a class="admin-button admin-button-soft admin-refresh-button" href="admin-newsletter.php" aria-label="Back to Newsletter" title="Back to Newsletter">Newsletter</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-newsletter.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>
      <section class="admin-page-section">
        <section class="newsletter-settings-hero">
          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Newsletter Control Center</h2><p class="admin-panel-note">Signed in as <?php echo $escapeNewsletterSetting($adminCurrentUsername !== '' ? $adminCurrentUsername : 'GirffoN Admin'); ?>. These settings are saved per admin and directly change what you see in the newsletter workspace.</p></div></div>
            <div class="newsletter-preset-grid">
              <?php foreach ($newsletterPresetCards as $presetKey => $presetMeta): ?>
                <article class="newsletter-preset-card"><div><h3><?php echo $escapeNewsletterSetting($presetMeta['title']); ?></h3><p><?php echo $escapeNewsletterSetting($presetMeta['note']); ?></p></div><form method="POST" action="setting-newsletter.php"><input type="hidden" name="newsletter_preset" value="<?php echo $escapeNewsletterSetting($presetKey); ?>"><button class="admin-button admin-button-soft" type="submit">Apply Preset</button></form></article>
              <?php endforeach; ?>
            </div>
          </article>
          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Current View Summary</h2><p class="admin-panel-note">Quick snapshot of how operational or delivery-focused your newsletter workspace currently is.</p></div></div>
            <div class="newsletter-settings-summary-grid">
              <div class="newsletter-settings-metric"><span>Enabled Controls</span><strong><?php echo $escapeNewsletterSetting($activeNewsletterSettingsCount); ?> / <?php echo $escapeNewsletterSetting($newsletterSettingsTotal); ?></strong></div>
              <div class="newsletter-settings-metric"><span>Workspace Mode</span><strong><?php echo $escapeNewsletterSetting(!empty($adminNewsletterPreferences['show_catalog_campaign_panel']) ? 'Campaign Control' : 'Log Monitor'); ?></strong></div>
              <div class="newsletter-settings-metric"><span>Automation Panels</span><strong><?php echo $escapeNewsletterSetting(!empty($adminNewsletterPreferences['show_birthday_panel']) || !empty($adminNewsletterPreferences['show_promotional_panel']) ? 'Enabled' : 'Hidden'); ?></strong></div>
              <div class="newsletter-settings-metric"><span>Delivery Logs</span><strong><?php echo $escapeNewsletterSetting(!empty($adminNewsletterPreferences['show_campaign_log_panel']) || !empty($adminNewsletterPreferences['show_birthday_log_panel']) || !empty($adminNewsletterPreferences['show_promotional_log_panel']) ? 'Visible' : 'Hidden'); ?></strong></div>
            </div>
          </article>
        </section>
        <article class="admin-panel">
          <div class="admin-panel-head"><div><h2>Fine-Grained Newsletter Settings</h2><p class="admin-panel-note">Build your own newsletter workspace with grouped controls for panels, logs, and audience detail.</p></div></div>
          <form method="POST" action="setting-newsletter.php" novalidate>
            <div class="newsletter-settings-section-grid">
              <?php foreach ($newsletterSettingGroups as $groupMeta): ?>
                <section class="newsletter-settings-section"><div><h3><?php echo $escapeNewsletterSetting($groupMeta['title']); ?></h3><p class="newsletter-settings-section-note"><?php echo $escapeNewsletterSetting($groupMeta['note']); ?></p></div><div class="newsletter-settings-grid"><?php foreach ($groupMeta['settings'] as $settingKey => $settingMeta): ?><section class="newsletter-setting-card"><div class="newsletter-setting-toggle"><label for="<?php echo $escapeNewsletterSetting($settingKey); ?>"><span class="newsletter-setting-title"><?php echo $escapeNewsletterSetting($settingMeta['label']); ?></span><span class="newsletter-setting-note"><?php echo $escapeNewsletterSetting($settingMeta['note']); ?></span></label><input id="<?php echo $escapeNewsletterSetting($settingKey); ?>" name="<?php echo $escapeNewsletterSetting($settingKey); ?>" type="checkbox" <?php if (!empty($adminNewsletterPreferences[$settingKey])): ?>checked<?php endif; ?>></div></section><?php endforeach; ?></div></section>
              <?php endforeach; ?>
            </div>
            <div class="admin-form-actions" style="margin-top:18px;"><button class="admin-button" type="submit">Save Newsletter Settings</button><a class="admin-button admin-button-soft" href="admin-newsletter.php">Back to Newsletter</a></div>
          </form>
          <?php if ($adminNewsletterSettingStatus !== ''): ?><p class="admin-feedback" role="status" aria-live="polite" style="margin-top:16px;"><?php echo $escapeNewsletterSetting($adminNewsletterSettingStatus); ?></p><?php elseif ($adminNewsletterSettingError !== ''): ?><p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;"><?php echo $escapeNewsletterSetting($adminNewsletterSettingError); ?></p><?php elseif (!$adminNewsletterSettingsAvailable): ?><p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;">Newsletter settings helper is missing on this host. Upload backend/admin/newsletter-settings-data.php first.</p><?php endif; ?>
        </article>
      </section>
    </main>
  </div>
  <script src="JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>