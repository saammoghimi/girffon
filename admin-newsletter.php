<?php
require_once __DIR__ . '/backend/admin/session.php';
require_once __DIR__ . '/backend/admin/newsletter-data.php';

$adminNewsletterSettingsFile = __DIR__ . '/backend/admin/newsletter-settings-data.php';
if (is_file($adminNewsletterSettingsFile)) {
  require_once $adminNewsletterSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));

function girffonAdminNewsletterFetchSubscribedRows(PDO $pdo): array
{
  return girffonAdminFetchNewsletterSubscribers($pdo);
}

$adminSubscribers = girffonAdminNewsletterFetchSubscribedRows($pdo);
$adminRecentCampaignLogs = girffonAdminFetchRecentNewsletterCampaignLogs($pdo, 30);
$adminRecentBirthdayEmailLogs = girffonAdminFetchRecentBirthdayEmailLogs($pdo, 30);
$adminPromotionalAudience = girffonAdminFetchPromotionalAudience($pdo);
$adminRecentPromotionalEmailLogs = girffonAdminFetchRecentPromotionalEmailLogs($pdo, 30);
$adminNewsletterStatusMessage = trim((string) ($_GET['status'] ?? ''));
$adminNewsletterErrorMessage = trim((string) ($_GET['error'] ?? ''));
$adminNewsletterPreferences = function_exists('girffonAdminNewsletterSettingsDefault')
    ? girffonAdminNewsletterSettingsDefault()
    : [];
if (function_exists('girffonAdminFetchNewsletterPreferences')) {
  $adminNewsletterPreferences = girffonAdminFetchNewsletterPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}
$showAdminNewsletterSummaryCards = !empty($adminNewsletterPreferences['show_summary_cards']);
$showAdminNewsletterTestEmailPanel = !empty($adminNewsletterPreferences['show_test_email_panel']);
$showAdminNewsletterCatalogPanel = !empty($adminNewsletterPreferences['show_catalog_campaign_panel']);
$showAdminNewsletterBirthdayPanel = !empty($adminNewsletterPreferences['show_birthday_panel']);
$showAdminNewsletterPromotionalPanel = !empty($adminNewsletterPreferences['show_promotional_panel']);
$showAdminNewsletterCampaignLogPanel = !empty($adminNewsletterPreferences['show_campaign_log_panel']);
$showAdminNewsletterBirthdayLogPanel = !empty($adminNewsletterPreferences['show_birthday_log_panel']);
$showAdminNewsletterPromotionalLogPanel = !empty($adminNewsletterPreferences['show_promotional_log_panel']);
$showAdminNewsletterSubscriberPhoneColumn = !empty($adminNewsletterPreferences['show_subscriber_phone_column']);
$showAdminNewsletterSubscriberPromotionalColumn = !empty($adminNewsletterPreferences['show_subscriber_promotional_column']);
$showAdminNewsletterSubscriberBirthdayColumn = !empty($adminNewsletterPreferences['show_subscriber_birthday_column']);
$showAdminNewsletterCampaignTransportColumn = !empty($adminNewsletterPreferences['show_campaign_transport_column']);
$showAdminNewsletterBirthdayTransportColumn = !empty($adminNewsletterPreferences['show_birthday_transport_column']);
$showAdminNewsletterPromotionalTransportColumn = !empty($adminNewsletterPreferences['show_promotional_transport_column']);
$adminNewsletterTotals = [
    'all' => count($adminSubscribers),
    'active' => 0,
    'inactive' => 0,
    'eligible' => 0,
];

$adminNewsletterFlagEnabled = static function ($value): bool {
  if (is_bool($value)) {
    return $value;
  }

  $normalized = strtolower(trim((string) $value));
  return !in_array($normalized, ['', '0', 'false', 'off', 'no', 'disabled', 'inactive'], true);
};

foreach ($adminSubscribers as $subscriber) {
  $isActive = $adminNewsletterFlagEnabled($subscriber['is_active'] ?? null)
    || strtolower(trim((string) ($subscriber['status'] ?? 'inactive'))) === 'active';
  $catalogEnabled = $adminNewsletterFlagEnabled($subscriber['catalog_emails'] ?? 0);

    if ($isActive) {
        $adminNewsletterTotals['active']++;
    } else {
        $adminNewsletterTotals['inactive']++;
    }

  if ($isActive && $catalogEnabled) {
        $adminNewsletterTotals['eligible']++;
    }
}

$adminPromotionalTotals = [
    'total' => 0,
    'active' => 0,
    'ready' => 0,
];

foreach ($adminPromotionalAudience as $recipient) {
  $promotionalEnabled = $adminNewsletterFlagEnabled($recipient['promotional_emails'] ?? 0);
  $isActive = $adminNewsletterFlagEnabled($recipient['is_active'] ?? 0);
  $readyToSend = $adminNewsletterFlagEnabled($recipient['ready_to_send'] ?? 0);

  if ($promotionalEnabled) {
    $adminPromotionalTotals['total']++;
  }

  if ($promotionalEnabled && $isActive) {
    $adminPromotionalTotals['active']++;
  }

  if ($readyToSend) {
    $adminPromotionalTotals['ready']++;
  }
}

$escapeAdminNewsletter = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$formatAdminNewsletterLabel = static function ($value) use ($escapeAdminNewsletter) {
    $text = trim((string) $value);
    return $text === '' ? '-' : $escapeAdminNewsletter(ucwords(str_replace('_', ' ', $text)));
};
$formatAdminNewsletterDate = static function ($value) use ($escapeAdminNewsletter) {
    $text = trim((string) $value);
    if ($text === '') {
        return '-';
    }

    $timestamp = strtotime($text);
    return $timestamp ? $escapeAdminNewsletter(date('Y-m-d H:i', $timestamp)) : $escapeAdminNewsletter($text);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Newsletter</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    @media (max-width: 1280px) and (min-width: 721px) {
      .admin-main,
      .admin-page-section,
      .admin-page-section > .admin-panel,
      .admin-page-section > .admin-table-panel,
      .admin-grid-form,
      .admin-field,
      .admin-field-wide,
      .admin-card-grid,
      .admin-table-wrap {
        min-width: 0;
      }

      .admin-main {
        max-width: 100%;
        overflow-x: hidden;
      }

      .admin-topbar {
        gap: 14px;
      }

      .admin-topbar > div:first-child {
        min-width: 0;
        flex: 1 1 auto;
      }

      .admin-topbar-actions {
        flex: 0 0 auto;
        flex-wrap: nowrap;
        margin-left: auto;
        max-width: none;
      }

      .admin-card-grid,
      .admin-grid-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="newsletter" data-admin-newsletter-source="database">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Catalog campaign control for subscribed customers and member email preferences.</p>
      </div>

      <?php
      $adminNavCurrentPage = 'newsletter';
      $adminNavBasePath = '';
      require __DIR__ . '/includes/admin-nav.php';
      ?>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Catalog Campaigns</strong>
          <p class="admin-panel-note">Select subscribers, write the message, and send only on manual approval.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title">Newsletter / Catalog Campaign</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-newsletter.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <?php if ($showAdminNewsletterSummaryCards): ?>
      <section class="admin-card-grid" aria-label="Newsletter summary cards">
        <article class="admin-stat-card">
          <span>Total Subscribers</span>
          <strong><?php echo $escapeAdminNewsletter($adminNewsletterTotals['all']); ?></strong>
          <p class="admin-status">All saved records from newsletter subscriptions.</p>
        </article>
        <article class="admin-stat-card">
          <span>Active</span>
          <strong><?php echo $escapeAdminNewsletter($adminNewsletterTotals['active']); ?></strong>
          <p class="admin-status">Subscribers who still allow catalog emails.</p>
        </article>
        <article class="admin-stat-card">
          <span>Inactive</span>
          <strong><?php echo $escapeAdminNewsletter($adminNewsletterTotals['inactive']); ?></strong>
          <p class="admin-status">Inactive or preference-disabled subscribers.</p>
        </article>
        <article class="admin-stat-card">
          <span>Eligible To Send</span>
          <strong><?php echo $escapeAdminNewsletter($adminNewsletterTotals['eligible']); ?></strong>
          <p class="admin-status">Recipients who will receive catalog campaigns right now.</p>
        </article>
      </section>
      <?php endif; ?>

      <section class="admin-page-section">
        <?php if ($adminNewsletterStatusMessage || $adminNewsletterErrorMessage): ?>
          <div class="admin-feedback<?php if ($adminNewsletterErrorMessage): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
            <?php echo $escapeAdminNewsletter($adminNewsletterErrorMessage ?: $adminNewsletterStatusMessage); ?>
          </div>
        <?php endif; ?>

        <?php if ($showAdminNewsletterTestEmailPanel): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Admin Test Email</h2>
              <p class="admin-panel-note">Admin-only mail transport check. This does not send any catalog campaign and is not visible to customers.</p>
            </div>
          </div>

          <form class="admin-grid-form" method="POST" action="backend/admin/test-email.php" novalidate>
            <div class="admin-field admin-field-wide">
              <label for="adminTestEmailAddress">Email Address</label>
              <input class="admin-input" id="adminTestEmailAddress" name="email" type="email" placeholder="admin@example.com" required>
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-soft" type="submit">Send Test Email</button>
            </div>
          </form>
        </article>
        <?php endif; ?>

        <?php if ($showAdminNewsletterCatalogPanel): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Send Catalog Email</h2>
              <p class="admin-panel-note">Select subscribed customers manually. Catalog emails are sent only after clicking Send Catalog Email.</p>
            </div>
          </div>

          <form class="admin-grid-form" method="POST" action="backend/admin/newsletter-send.php" enctype="multipart/form-data" novalidate>
            <div class="admin-field admin-field-wide">
              <label for="adminNewsletterSubject">Subject</label>
              <input class="admin-input" id="adminNewsletterSubject" name="subject" type="text" placeholder="New GirffoN catalog release" required>
            </div>

            <div class="admin-field admin-field-wide">
              <label for="adminNewsletterMessage">Message</label>
              <textarea class="admin-input" id="adminNewsletterMessage" name="message" rows="8" placeholder="Write the catalog campaign message for selected customers." required></textarea>
            </div>

            <div class="admin-field">
              <label for="adminNewsletterPdfUrl">Catalog URL</label>
              <input class="admin-input" id="adminNewsletterPdfUrl" name="catalog_pdf_url" type="url" placeholder="https://girffon.shop/GirffoN/Catalog/June2025/Catalog.html">
            </div>

            <div class="admin-field">
              <label for="adminNewsletterPdfFile">Or Upload Catalog PDF</label>
              <input class="admin-input" id="adminNewsletterPdfFile" name="catalog_pdf_file" type="file" accept="application/pdf">
            </div>

            <div class="admin-field admin-field-wide">
              <label>
                <input id="adminNewsletterSelectAll" type="checkbox"> Select all visible subscribers
              </label>
            </div>

            <div class="admin-table-wrap admin-field admin-field-wide">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Select</th>
                    <th>Name</th>
                    <th>Email</th>
                    <?php if ($showAdminNewsletterSubscriberPhoneColumn): ?><th>Phone</th><?php endif; ?>
                    <th>Catalog Emails</th>
                    <?php if ($showAdminNewsletterSubscriberPromotionalColumn): ?><th>Promotional Emails</th><?php endif; ?>
                    <?php if ($showAdminNewsletterSubscriberBirthdayColumn): ?><th>Birthday Discount Emails</th><?php endif; ?>
                    <th>Subscribed At</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($adminSubscribers): ?>
                    <?php foreach ($adminSubscribers as $subscriber): ?>
                      <?php
                        $email = strtolower(trim((string) ($subscriber['email'] ?? '')));
                        $subscriberIsActive = $adminNewsletterFlagEnabled($subscriber['is_active'] ?? null)
                            || strtolower(trim((string) ($subscriber['status'] ?? 'inactive'))) === 'active';
                        $subscriberStatus = $subscriberIsActive ? 'active' : 'inactive';
                        $catalogEnabled = $adminNewsletterFlagEnabled($subscriber['catalog_emails'] ?? 0);
                        $promotionalEnabled = $adminNewsletterFlagEnabled($subscriber['promotional_emails'] ?? 0);
                        $birthdayEnabled = $adminNewsletterFlagEnabled($subscriber['birthday_discount_emails'] ?? 0);
                        $canReceive = $subscriberIsActive && $catalogEnabled;
                      ?>
                      <tr data-newsletter-status="<?php echo $escapeAdminNewsletter($subscriberStatus); ?>">
                        <td><input class="admin-newsletter-checkbox" type="checkbox" name="selected_emails[]" value="<?php echo $escapeAdminNewsletter($email); ?>"<?php if (!$canReceive): ?> data-disabled-reason="Catalog Emails disabled or inactive subscriber"<?php endif; ?>></td>
                        <td><?php echo $escapeAdminNewsletter(($subscriber['name'] ?? '') !== '' ? $subscriber['name'] : '-'); ?></td>
                        <td><?php echo $escapeAdminNewsletter($email !== '' ? $email : '-'); ?></td>
                        <?php if ($showAdminNewsletterSubscriberPhoneColumn): ?><td><?php echo $escapeAdminNewsletter(($subscriber['phone'] ?? '') !== '' ? $subscriber['phone'] : '-'); ?></td><?php endif; ?>
                        <td><?php echo $formatAdminNewsletterLabel($catalogEnabled ? 'enabled' : 'disabled'); ?></td>
                        <?php if ($showAdminNewsletterSubscriberPromotionalColumn): ?><td><?php echo $formatAdminNewsletterLabel($promotionalEnabled ? 'enabled' : 'disabled'); ?></td><?php endif; ?>
                        <?php if ($showAdminNewsletterSubscriberBirthdayColumn): ?><td><?php echo $formatAdminNewsletterLabel($birthdayEnabled ? 'enabled' : 'disabled'); ?></td><?php endif; ?>
                        <td><?php echo $formatAdminNewsletterDate($subscriber['subscribed_at'] ?? ''); ?></td>
                        <td><?php echo $formatAdminNewsletterLabel($subscriberStatus); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="<?php echo $escapeAdminNewsletter(6 + ($showAdminNewsletterSubscriberPhoneColumn ? 1 : 0) + ($showAdminNewsletterSubscriberPromotionalColumn ? 1 : 0) + ($showAdminNewsletterSubscriberBirthdayColumn ? 1 : 0)); ?>" class="admin-empty">No subscribers found. Go to Profile → Catalog Subscription and subscribe first.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit">Send Catalog Email</button>
            </div>
          </form>
        </article>
        <?php endif; ?>

        <?php if ($showAdminNewsletterBirthdayPanel): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Birthday Discount Email Automation</h2>
              <p class="admin-panel-note">Automatically finds all customers whose birthday is today and sends them a 50% birthday discount email. No manual selection is required.</p>
            </div>
          </div>

          <?php if ($adminNewsletterStatusMessage || $adminNewsletterErrorMessage): ?>
            <div class="admin-feedback<?php if ($adminNewsletterErrorMessage): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
              <?php echo $escapeAdminNewsletter($adminNewsletterErrorMessage ?: $adminNewsletterStatusMessage); ?>
            </div>
          <?php endif; ?>

          <div class="admin-form-actions">
            <a href="/GirffoN/backend/admin/send-birthday-emails.php?run=1" class="admin-button admin-button-primary">
              Send Today Birthday Emails
            </a>
              <a href="https://girffon.shop/GirffoN/backend/admin/birthday-debug.php" class="admin-button admin-button-soft">
                Open Birthday Debug
              </a>
          </div>
          <p class="admin-panel-note">The system checks every customer by Date of Birth and sends only to today&apos;s birthday customers.</p>
        </article>
        <?php endif; ?>

        <?php if ($showAdminNewsletterPromotionalPanel): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Promotional Email Campaign</h2>
              <p class="admin-panel-note">Send a premium GirffoN promotional campaign to all eligible customers and subscribers with Promotional Emails enabled. No manual customer selection is required.</p>
            </div>
          </div>

          <?php if ($adminNewsletterStatusMessage || $adminNewsletterErrorMessage): ?>
            <div class="admin-feedback<?php if ($adminNewsletterErrorMessage): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
              <?php echo $escapeAdminNewsletter($adminNewsletterErrorMessage ?: $adminNewsletterStatusMessage); ?>
            </div>
          <?php endif; ?>

          <div class="admin-card-grid" aria-label="Promotional email audience summary">
            <article class="admin-stat-card">
              <span>Total Promotional Subscribers</span>
              <strong><?php echo $escapeAdminNewsletter($adminPromotionalTotals['total']); ?></strong>
              <p class="admin-status">Audience with Promotional Emails enabled.</p>
            </article>
            <article class="admin-stat-card">
              <span>Active Promotional Members</span>
              <strong><?php echo $escapeAdminNewsletter($adminPromotionalTotals['active']); ?></strong>
              <p class="admin-status">Enabled recipients that are still active.</p>
            </article>
            <article class="admin-stat-card">
              <span>Ready To Send</span>
              <strong><?php echo $escapeAdminNewsletter($adminPromotionalTotals['ready']); ?></strong>
              <p class="admin-status">Recipients that will receive this campaign right now.</p>
            </article>
          </div>

          <form class="admin-grid-form" method="POST" action="backend/admin/send-promotional-emails.php" novalidate>
            <div class="admin-field admin-field-wide">
              <label for="adminPromotionalSubject">Campaign Title</label>
              <input class="admin-input" id="adminPromotionalSubject" name="subject" type="text" value="GirffoN Promotional Campaign - Discover the Collection" placeholder="GirffoN Promotional Campaign - Discover the Collection">
            </div>

            <div class="admin-field admin-field-wide">
              <label for="adminPromotionalMessage">Campaign Message</label>
              <textarea class="admin-input" id="adminPromotionalMessage" name="message" rows="7" placeholder="Write a premium GirffoN promotional campaign message, or leave empty to use the default message.">Discover the latest GirffoN arrivals, premium seasonal picks, and refined pieces selected for customers who want the newest collection first.</textarea>
            </div>

            <div class="admin-field">
              <label for="adminPromotionalBannerImageUrl">Banner Image URL</label>
              <input class="admin-input" id="adminPromotionalBannerImageUrl" name="banner_image_url" type="url" placeholder="https://girffon.shop/GirffoN/Image/Banner/promo-banner.jpg">
            </div>

            <div class="admin-field">
              <label for="adminPromotionalDiscountCode">Discount Code</label>
              <input class="admin-input" id="adminPromotionalDiscountCode" name="discount_code" type="text" value="GIRFFON20" placeholder="GIRFFON20">
            </div>

            <div class="admin-field">
              <label for="adminPromotionalCtaText">CTA Text</label>
              <input class="admin-input" id="adminPromotionalCtaText" name="cta_text" type="text" value="Shop Collection" placeholder="Shop Collection">
            </div>

            <div class="admin-field">
              <label for="adminPromotionalCtaUrl">CTA URL</label>
              <input class="admin-input" id="adminPromotionalCtaUrl" name="cta_url" type="url" value="https://girffon.shop/GirffoN/index.html" placeholder="https://girffon.shop/GirffoN/index.html">
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-primary" type="submit">Send Promotional Email</button>
              <button class="admin-button admin-button-soft" type="submit" formaction="backend/admin/promotional-preview.php" formtarget="_blank">Preview Promotional Email</button>
              <a href="/GirffoN/backend/admin/promotional-debug.php" class="admin-button admin-button-soft">Open Promotional Debug</a>
            </div>
          </form>
        </article>
        <?php endif; ?>

        <?php if ($showAdminNewsletterCampaignLogPanel): ?>
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Campaign Log</h2>
              <p class="admin-panel-note">Per-recipient send result for the latest catalog campaigns.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Campaign</th>
                  <th>Recipient</th>
                  <th>Email</th>
                  <th>Subject</th>
                  <th>Attachment URL</th>
                  <th>Status</th>
                  <?php if ($showAdminNewsletterCampaignTransportColumn): ?><th>Transport</th><?php endif; ?>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($adminRecentCampaignLogs): ?>
                  <?php foreach ($adminRecentCampaignLogs as $log): ?>
                    <tr>
                      <td><?php echo $escapeAdminNewsletter($log['campaign_id'] ?? '-'); ?></td>
                      <td><?php echo $escapeAdminNewsletter(($log['recipient_name'] ?? '') !== '' ? $log['recipient_name'] : '-'); ?></td>
                      <td><?php echo $escapeAdminNewsletter(($log['email'] ?? '') !== '' ? $log['email'] : '-'); ?></td>
                      <td><?php echo $escapeAdminNewsletter(($log['subject'] ?? '') !== '' ? $log['subject'] : '-'); ?></td>
                      <td><?php if (!empty($log['attachment_url'])): ?><a href="<?php echo $escapeAdminNewsletter($log['attachment_url']); ?>" target="_blank" rel="noreferrer">Open PDF</a><?php else: ?>-<?php endif; ?></td>
                      <td><?php echo $formatAdminNewsletterLabel($log['status'] ?? '-'); ?></td>
                      <?php if ($showAdminNewsletterCampaignTransportColumn): ?><td><?php echo $escapeAdminNewsletter(($log['transport'] ?? '') !== '' ? $log['transport'] : '-'); ?></td><?php endif; ?>
                      <td><?php echo $formatAdminNewsletterDate($log['created_at'] ?? ''); ?></td>
                    </tr>
                    <?php if (!empty($log['error_message'])): ?>
                      <tr>
                        <td colspan="<?php echo $escapeAdminNewsletter(7 + ($showAdminNewsletterCampaignTransportColumn ? 1 : 0)); ?>" class="admin-empty" style="text-align:left;">Error: <?php echo $escapeAdminNewsletter($log['error_message']); ?></td>
                      </tr>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="<?php echo $escapeAdminNewsletter(7 + ($showAdminNewsletterCampaignTransportColumn ? 1 : 0)); ?>" class="admin-empty">No catalog campaign logs found yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminNewsletterBirthdayLogPanel): ?>
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Birthday Email Log</h2>
              <p class="admin-panel-note">Latest birthday discount send attempts and delivery results.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>User ID</th>
                  <th>Email</th>
                  <th>Coupon Code</th>
                  <th>Sent Date</th>
                  <th>Status</th>
                  <?php if ($showAdminNewsletterBirthdayTransportColumn): ?><th>Transport</th><?php endif; ?>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($adminRecentBirthdayEmailLogs): ?>
                  <?php foreach ($adminRecentBirthdayEmailLogs as $log): ?>
                    <tr>
                      <td><?php echo $escapeAdminNewsletter((string) ($log['user_id'] ?? '-')); ?></td>
                      <td><?php echo $escapeAdminNewsletter(($log['email'] ?? '') !== '' ? $log['email'] : '-'); ?></td>
                      <td><?php echo $escapeAdminNewsletter(($log['coupon_code'] ?? '') !== '' ? $log['coupon_code'] : '-'); ?></td>
                      <td><?php echo $formatAdminNewsletterDate($log['sent_date'] ?? ''); ?></td>
                      <td><?php echo $formatAdminNewsletterLabel($log['status'] ?? '-'); ?></td>
                      <?php if ($showAdminNewsletterBirthdayTransportColumn): ?><td><?php echo $escapeAdminNewsletter(($log['transport'] ?? '') !== '' ? $log['transport'] : '-'); ?></td><?php endif; ?>
                      <td><?php echo $formatAdminNewsletterDate($log['created_at'] ?? ''); ?></td>
                    </tr>
                    <?php if (!empty($log['error_message'])): ?>
                      <tr>
                        <td colspan="<?php echo $escapeAdminNewsletter(6 + ($showAdminNewsletterBirthdayTransportColumn ? 1 : 0)); ?>" class="admin-empty" style="text-align:left;">Error: <?php echo $escapeAdminNewsletter($log['error_message']); ?></td>
                      </tr>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="<?php echo $escapeAdminNewsletter(6 + ($showAdminNewsletterBirthdayTransportColumn ? 1 : 0)); ?>" class="admin-empty">No birthday email logs found yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminNewsletterPromotionalLogPanel): ?>
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Promotional Email Log</h2>
              <p class="admin-panel-note">Latest promotional audience sends, failures, and skips.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Campaign Code</th>
                  <th>Email</th>
                  <th>Subject</th>
                  <th>Status</th>
                  <?php if ($showAdminNewsletterPromotionalTransportColumn): ?><th>Transport</th><?php endif; ?>
                  <th>Error Message</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($adminRecentPromotionalEmailLogs): ?>
                  <?php foreach ($adminRecentPromotionalEmailLogs as $log): ?>
                    <tr>
                      <td><?php echo $escapeAdminNewsletter(($log['campaign_id'] ?? '') !== '' ? $log['campaign_id'] : '-'); ?></td>
                      <td><?php echo $escapeAdminNewsletter(($log['email'] ?? '') !== '' ? $log['email'] : '-'); ?></td>
                      <td><?php echo $escapeAdminNewsletter(($log['subject'] ?? '') !== '' ? $log['subject'] : '-'); ?></td>
                      <td><?php echo $formatAdminNewsletterLabel($log['status'] ?? '-'); ?></td>
                      <?php if ($showAdminNewsletterPromotionalTransportColumn): ?><td><?php echo $escapeAdminNewsletter(($log['transport'] ?? '') !== '' ? $log['transport'] : '-'); ?></td><?php endif; ?>
                      <td><?php echo $escapeAdminNewsletter(($log['error_message'] ?? '') !== '' ? $log['error_message'] : '-'); ?></td>
                      <td><?php echo $formatAdminNewsletterDate($log['created_at'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="<?php echo $escapeAdminNewsletter(6 + ($showAdminNewsletterPromotionalTransportColumn ? 1 : 0)); ?>" class="admin-empty">No promotional email logs found yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const selectAll = document.getElementById('adminNewsletterSelectAll');
      const checkboxes = Array.from(document.querySelectorAll('.admin-newsletter-checkbox'));

      if (selectAll) {
        selectAll.addEventListener('change', function () {
          checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
          });
        });
      }
    });
  </script>
</body>
</html>
