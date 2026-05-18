<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/promotional-campaign.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

$mailConfig = function_exists('girffonMailConfig') ? girffonMailConfig() : [];
$campaign = girffonAdminPromotionalBuildCampaignConfig($_POST + $_GET, $mailConfig);
$previewRecipient = [
    'name' => trim((string) ($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'GirffoN Admin')),
    'email' => trim((string) ($_SESSION['admin_email'] ?? 'preview@girffon.local')),
];

if ($previewRecipient['name'] === '') {
    $previewRecipient['name'] = 'GirffoN Admin';
}

$previewMessage = girffonAdminPromotionalBuildMessage($previewRecipient, $campaign);

function girffonAdminPromotionalPreviewEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$previewDocument = (string) ($previewMessage['preview_html'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Promotional Preview</title>
  <link rel="stylesheet" href="/GirffoN/CSS/admin-girffon.css?v=20260511r15">
  <style>
    body {
      font-family: Georgia, serif;
      background: #f5f1ea;
      color: #1f1812;
    }

    .preview-shell {
      max-width: 1180px;
      margin: 0 auto;
      width: 100%;
    }

    .preview-card {
      background: #fffdf9;
      border: 1px solid #dccfbf;
      border-radius: 16px;
      padding: 22px;
      margin-bottom: 18px;
      box-shadow: 0 16px 42px rgba(46, 28, 10, 0.08);
    }

    .preview-main {
      padding: 32px 20px 48px;
    }

    .preview-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 14px;
    }

    .preview-meta {
      border: 1px solid #ece2d5;
      border-radius: 12px;
      padding: 14px;
      background: #fff;
      color: #6e5c4a;
      font-size: 0.92rem;
    }

    .preview-meta strong {
      display: block;
      font-size: 1.1rem;
      margin-top: 8px;
      line-height: 1.5;
      color: #1f1812;
      word-break: break-word;
    }

    .preview-frame {
      background: #0f0f10;
      border-radius: 18px;
      padding: 16px;
      overflow: hidden;
      border: 1px solid #2f2516;
    }

    .preview-note {
      margin: 0;
      color: #6e5c4a;
    }

    .preview-iframe {
      display: block;
      width: 100%;
      min-height: 980px;
      border: 0;
      border-radius: 14px;
      background: #ffffff;
    }
  </style>
</head>
<body class="admin-page" data-admin-page="promotional-preview">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="/GirffoN/Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Catalog campaign control for subscribed customers and member email preferences.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="/GirffoN/admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="/GirffoN/admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="/GirffoN/admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="/GirffoN/admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="/GirffoN/admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="/GirffoN/admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link is-active" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="/GirffoN/admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Catalog Campaigns</strong>
          <p class="admin-panel-note">Select subscribers, write the message, and send only on manual approval.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main preview-main">
      <div class="preview-shell">
        <header class="admin-topbar">
          <div>
            <p class="admin-page-subtitle">Admin</p>
            <h1 class="admin-page-title">Newsletter / Catalog Campaign</h1>
          </div>
          <div class="admin-topbar-actions">
            <a class="admin-button admin-button-soft admin-view-shop-button" href="/GirffoN/index.html" aria-label="View Shop" title="View Shop">View Shop</a>
            <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
            <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
            <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
          </div>
        </header>

        <section class="preview-card">
          <div class="admin-panel-head">
            <div>
              <h2>Promotional Email Preview</h2>
              <p class="preview-note">Review the exact promotional email design before sending the campaign.</p>
            </div>
          </div>
        </section>

        <section class="preview-card">
          <div class="preview-grid">
            <div class="preview-meta">
              Subject
              <strong><?php echo girffonAdminPromotionalPreviewEscape($campaign['subject']); ?></strong>
            </div>
            <div class="preview-meta">
              Discount Code
              <strong><?php echo girffonAdminPromotionalPreviewEscape($campaign['discount_code']); ?></strong>
            </div>
            <div class="preview-meta">
              CTA Text
              <strong><?php echo girffonAdminPromotionalPreviewEscape($campaign['cta_text']); ?></strong>
            </div>
            <div class="preview-meta">
              CTA URL
              <strong><?php echo girffonAdminPromotionalPreviewEscape($campaign['cta_url']); ?></strong>
            </div>
            <div class="preview-meta">
              Banner Image URL
              <strong><?php echo girffonAdminPromotionalPreviewEscape($campaign['banner_image_url'] !== '' ? $campaign['banner_image_url'] : 'Not provided'); ?></strong>
            </div>
            <div class="preview-meta">
              Preview Recipient
              <strong><?php echo girffonAdminPromotionalPreviewEscape($previewRecipient['name']); ?></strong>
            </div>
          </div>
        </section>

        <section class="preview-card">
          <div class="preview-frame">
            <iframe
              class="preview-iframe"
              title="Promotional email preview"
              loading="lazy"
              referrerpolicy="no-referrer"
              srcdoc="<?php echo girffonAdminPromotionalPreviewEscape($previewDocument); ?>"
            ></iframe>
          </div>
        </section>
      </div>
    </main>
  </div>
  <script src="/GirffoN/JS/admin-girffon.js?v=20260505r5"></script>
</body>
</html>