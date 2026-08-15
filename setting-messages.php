<?php
require_once __DIR__ . "/backend/admin/session.php";

$adminMessageSettingsFile = __DIR__ . "/backend/admin/message-settings-data.php";
$adminMessageSettingsAvailable = is_file($adminMessageSettingsFile);
if ($adminMessageSettingsAvailable) {
  require_once $adminMessageSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$adminMessageSettingStatus = trim((string) ($_GET['status'] ?? ''));
$adminMessageSettingError = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $messagePreset = trim((string) ($_POST['message_preset'] ?? ''));
  $messagePreferences = [
    'show_messages_overview' => isset($_POST['show_messages_overview']),
    'show_summary_cards' => isset($_POST['show_summary_cards']),
    'show_search_filters' => isset($_POST['show_search_filters']),
    'show_message_list' => isset($_POST['show_message_list']),
    'show_subject_column' => isset($_POST['show_subject_column']),
    'show_preview_column' => isset($_POST['show_preview_column']),
    'show_status_column' => isset($_POST['show_status_column']),
    'show_date_column' => isset($_POST['show_date_column']),
    'show_view_action' => isset($_POST['show_view_action']),
    'show_mark_read_action' => isset($_POST['show_mark_read_action']),
    'show_delete_action' => isset($_POST['show_delete_action']),
    'show_contact_tools' => isset($_POST['show_contact_tools']),
  ];

  $messagePresets = [
    'support-desk' => [
      'show_messages_overview' => true,
      'show_summary_cards' => true,
      'show_search_filters' => true,
      'show_message_list' => true,
      'show_subject_column' => true,
      'show_preview_column' => true,
      'show_status_column' => true,
      'show_date_column' => true,
      'show_view_action' => true,
      'show_mark_read_action' => true,
      'show_delete_action' => true,
      'show_contact_tools' => true,
    ],
    'inbox-review' => [
      'show_messages_overview' => true,
      'show_summary_cards' => true,
      'show_search_filters' => true,
      'show_message_list' => true,
      'show_subject_column' => true,
      'show_preview_column' => true,
      'show_status_column' => true,
      'show_date_column' => true,
      'show_view_action' => true,
      'show_mark_read_action' => true,
      'show_delete_action' => false,
      'show_contact_tools' => false,
    ],
    'minimal-monitor' => [
      'show_messages_overview' => true,
      'show_summary_cards' => false,
      'show_search_filters' => false,
      'show_message_list' => true,
      'show_subject_column' => false,
      'show_preview_column' => true,
      'show_status_column' => true,
      'show_date_column' => true,
      'show_view_action' => true,
      'show_mark_read_action' => false,
      'show_delete_action' => false,
      'show_contact_tools' => false,
    ],
  ];

  if ($messagePreset !== '' && isset($messagePresets[$messagePreset])) {
    $messagePreferences = $messagePresets[$messagePreset];
  }

  $saved = $adminMessageSettingsAvailable && function_exists('girffonAdminSaveMessagePreferences')
    ? girffonAdminSaveMessagePreferences($pdo, $adminCurrentId, $adminCurrentUsername, $messagePreferences)
    : false;
  header('Location: /GirffoN/setting-messages.php?' . ($saved ? 'status=' . rawurlencode('Message settings saved for your account.') : 'error=' . rawurlencode('Unable to save message settings right now.')));
  exit;
}

$adminMessagePreferences = [
  'show_messages_overview' => true,
  'show_summary_cards' => true,
  'show_search_filters' => true,
  'show_message_list' => true,
  'show_subject_column' => true,
  'show_preview_column' => true,
  'show_status_column' => true,
  'show_date_column' => true,
  'show_view_action' => true,
  'show_mark_read_action' => true,
  'show_delete_action' => true,
  'show_contact_tools' => true,
];

if ($adminMessageSettingsAvailable && function_exists('girffonAdminFetchMessagePreferences')) {
  $adminMessagePreferences = girffonAdminFetchMessagePreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$escapeMessageSetting = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$messageSettingGroups = [
  'workspace' => [
    'title' => 'Workspace Layout',
    'note' => 'Control the high-level message inbox sections.',
    'settings' => [
      'show_messages_overview' => ['label' => 'Messages Overview', 'note' => 'Show the live messages intro and status feedback panel.'],
      'show_summary_cards' => ['label' => 'Summary Cards', 'note' => 'Show total, unread, and read counters above the table.'],
      'show_search_filters' => ['label' => 'Search & Filters', 'note' => 'Show inbox search and status filters.'],
      'show_message_list' => ['label' => 'Message List', 'note' => 'Show the main messages table.'],
    ],
  ],
  'columns' => [
    'title' => 'Table Columns',
    'note' => 'Shape the density of the inbox table for different workflows.',
    'settings' => [
      'show_subject_column' => ['label' => 'Subject Column', 'note' => 'Show message subjects in the table.'],
      'show_preview_column' => ['label' => 'Preview Column', 'note' => 'Show short body previews in the table.'],
      'show_status_column' => ['label' => 'Status Column', 'note' => 'Show read/unread status in the table.'],
      'show_date_column' => ['label' => 'Date Column', 'note' => 'Show message creation date in the table.'],
    ],
  ],
  'actions' => [
    'title' => 'Inbox Actions',
    'note' => 'Choose which message actions remain available in each row and modal.',
    'settings' => [
      'show_view_action' => ['label' => 'View Action', 'note' => 'Show the eye icon and full message modal.'],
      'show_mark_read_action' => ['label' => 'Mark Read Action', 'note' => 'Show the quick mark-as-read action.'],
      'show_delete_action' => ['label' => 'Delete Action', 'note' => 'Show the delete action for inbox cleanup.'],
      'show_contact_tools' => ['label' => 'Contact Tools', 'note' => 'Show Email and Call buttons inside the message modal.'],
    ],
  ],
];

$messagePresetCards = [
  'support-desk' => ['title' => 'Support Desk', 'note' => 'Full inbox workflow with customer details, actions, and contact shortcuts.'],
  'inbox-review' => ['title' => 'Inbox Review', 'note' => 'Read and triage messages with reduced destructive actions.'],
  'minimal-monitor' => ['title' => 'Minimal Monitor', 'note' => 'Lean overview for monitoring message traffic with minimal controls.'],
];

$activeMessageSettingsCount = 0;
foreach ($adminMessagePreferences as $preferenceEnabled) {
  if (!empty($preferenceEnabled)) {
    $activeMessageSettingsCount++;
  }
}

$messageSettingsTotal = count($adminMessagePreferences);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Message Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .message-settings-hero {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.85fr);
      gap: 18px;
      margin-bottom: 18px;
    }

    .message-settings-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .message-settings-section-grid {
      display: grid;
      gap: 18px;
    }

    .message-settings-summary-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .message-settings-metric {
      padding: 16px 18px;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.86);
      border: 1px solid rgba(199, 165, 75, 0.16);
    }

    .message-settings-metric span {
      display: block;
      color: #7d715f;
      font-size: 0.86rem;
      margin-bottom: 6px;
    }

    .message-settings-metric strong {
      display: block;
      color: #2b241b;
      font-size: 1.4rem;
    }

    .message-preset-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .message-preset-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 20px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      background: linear-gradient(180deg, rgba(255, 252, 246, 0.96), rgba(255, 255, 255, 0.82));
    }

    .message-preset-card h3,
    .message-settings-section h3 {
      margin: 0;
      color: #2b241b;
      font-size: 1rem;
    }

    .message-preset-card p,
    .message-settings-section-note {
      margin: 0;
      color: #7d715f;
      line-height: 1.6;
      font-size: 0.92rem;
    }

    .message-preset-card .admin-button {
      justify-self: start;
    }

    .message-settings-section {
      display: grid;
      gap: 16px;
      padding: 20px;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(199, 165, 75, 0.14);
    }

    .message-setting-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(199, 165, 75, 0.16);
      background: rgba(255, 255, 255, 0.82);
    }

    .message-setting-toggle {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
    }

    .message-setting-toggle label {
      display: grid;
      gap: 6px;
      cursor: pointer;
    }

    .message-setting-toggle input[type="checkbox"] {
      margin-top: 4px;
      width: 20px;
      height: 20px;
      accent-color: #c7a54b;
      cursor: pointer;
    }

    .message-setting-title {
      font-size: 1rem;
      font-weight: 700;
      color: #2b241b;
    }

    .message-setting-note {
      color: #7d715f;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      .message-settings-hero,
      .message-settings-summary-grid,
      .message-preset-grid,
      .message-settings-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 980px) {
      .message-settings-hero,
      .message-preset-grid {
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
        <p>Configure your personal message inbox and support workflow.</p>
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
          <strong>Inbox Workspace</strong>
          <p class="admin-panel-note">Tune the message inbox layout, review actions, and contact tools for your admin account.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Message Settings</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <a class="admin-button admin-button-soft admin-refresh-button" href="admin-messages.php" aria-label="Back to Messages" title="Back to Messages">Messages</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-messages.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <section class="message-settings-hero">
          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Inbox Control Center</h2>
                <p class="admin-panel-note">Signed in as <?php echo $escapeMessageSetting($adminCurrentUsername !== '' ? $adminCurrentUsername : 'GirffoN Admin'); ?>. These settings are saved per admin and directly change what you see in the live customer inbox.</p>
              </div>
            </div>
            <div class="message-preset-grid">
              <?php foreach ($messagePresetCards as $presetKey => $presetMeta): ?>
                <article class="message-preset-card">
                  <div>
                    <h3><?php echo $escapeMessageSetting($presetMeta['title']); ?></h3>
                    <p><?php echo $escapeMessageSetting($presetMeta['note']); ?></p>
                  </div>
                  <form method="POST" action="setting-messages.php">
                    <input type="hidden" name="message_preset" value="<?php echo $escapeMessageSetting($presetKey); ?>">
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
                <p class="admin-panel-note">Quick snapshot of how active, lightweight, or review-focused your message workspace currently is.</p>
              </div>
            </div>
            <div class="message-settings-summary-grid">
              <div class="message-settings-metric">
                <span>Enabled Controls</span>
                <strong><?php echo $escapeMessageSetting($activeMessageSettingsCount); ?> / <?php echo $escapeMessageSetting($messageSettingsTotal); ?></strong>
              </div>
              <div class="message-settings-metric">
                <span>Workspace Mode</span>
                <strong><?php echo $escapeMessageSetting(!empty($adminMessagePreferences['show_search_filters']) && !empty($adminMessagePreferences['show_summary_cards']) ? 'Full Inbox' : 'Lean Review'); ?></strong>
              </div>
              <div class="message-settings-metric">
                <span>Response Tools</span>
                <strong><?php echo $escapeMessageSetting(!empty($adminMessagePreferences['show_contact_tools']) ? 'Enabled' : 'Hidden'); ?></strong>
              </div>
              <div class="message-settings-metric">
                <span>Destructive Actions</span>
                <strong><?php echo $escapeMessageSetting(!empty($adminMessagePreferences['show_delete_action']) ? 'Delete Visible' : 'Protected'); ?></strong>
              </div>
            </div>
          </article>
        </section>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Fine-Grained Message Settings</h2>
              <p class="admin-panel-note">Build your own inbox workspace with grouped controls for layout, table columns, and support actions.</p>
            </div>
          </div>

          <form method="POST" action="setting-messages.php" novalidate>
            <div class="message-settings-section-grid">
              <?php foreach ($messageSettingGroups as $groupMeta): ?>
                <section class="message-settings-section">
                  <div>
                    <h3><?php echo $escapeMessageSetting($groupMeta['title']); ?></h3>
                    <p class="message-settings-section-note"><?php echo $escapeMessageSetting($groupMeta['note']); ?></p>
                  </div>
                  <div class="message-settings-grid">
                    <?php foreach ($groupMeta['settings'] as $settingKey => $settingMeta): ?>
                      <section class="message-setting-card">
                        <div class="message-setting-toggle">
                          <label for="<?php echo $escapeMessageSetting($settingKey); ?>">
                            <span class="message-setting-title"><?php echo $escapeMessageSetting($settingMeta['label']); ?></span>
                            <span class="message-setting-note"><?php echo $escapeMessageSetting($settingMeta['note']); ?></span>
                          </label>
                          <input id="<?php echo $escapeMessageSetting($settingKey); ?>" name="<?php echo $escapeMessageSetting($settingKey); ?>" type="checkbox" <?php if (!empty($adminMessagePreferences[$settingKey])): ?>checked<?php endif; ?>>
                        </div>
                      </section>
                    <?php endforeach; ?>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>

            <div class="admin-form-actions" style="margin-top:18px;">
              <button class="admin-button" type="submit">Save Message Settings</button>
              <a class="admin-button admin-button-soft" href="admin-messages.php">Back to Messages</a>
            </div>
          </form>

          <?php if ($adminMessageSettingStatus !== ''): ?>
            <p class="admin-feedback" role="status" aria-live="polite" style="margin-top:16px;"><?php echo $escapeMessageSetting($adminMessageSettingStatus); ?></p>
          <?php elseif ($adminMessageSettingError !== ''): ?>
            <p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;"><?php echo $escapeMessageSetting($adminMessageSettingError); ?></p>
          <?php elseif (!$adminMessageSettingsAvailable): ?>
            <p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:16px;color:#9f2f2f;">Message settings helper is missing on this host. Upload backend/admin/message-settings-data.php first.</p>
          <?php endif; ?>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>