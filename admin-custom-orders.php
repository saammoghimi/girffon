<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/custom-design-orders-data.php";

$customOrderSettingsFile = __DIR__ . "/backend/admin/custom-order-settings-data.php";
if (is_file($customOrderSettingsFile)) {
  require_once $customOrderSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));

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

if (function_exists('girffonAdminFetchCustomOrderPreferences')) {
  $customOrderPreferences = girffonAdminFetchCustomOrderPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$showCustomOrderSummaryCards = !empty($customOrderPreferences['show_summary_cards']);
$showCustomOrderList = !empty($customOrderPreferences['show_order_list']);
$showCustomOrderIdColumn = !empty($customOrderPreferences['show_order_id_column']);
$showCustomOrderCustomerColumn = !empty($customOrderPreferences['show_customer_column']);
$showCustomOrderProductColumn = !empty($customOrderPreferences['show_product_column']);
$showCustomOrderUploadCountColumn = !empty($customOrderPreferences['show_upload_count_column']);
$showCustomOrderTextCountColumn = !empty($customOrderPreferences['show_text_count_column']);
$showCustomOrderStatusColumn = !empty($customOrderPreferences['show_status_column']);
$showCustomOrderDateColumn = !empty($customOrderPreferences['show_date_column']);
$showCustomOrderViewAction = !empty($customOrderPreferences['show_view_action']);

$customDesignOrders = girffonAdminFetchCustomDesignOrderSummaries($pdo, 120);
$customDesignOrderStatusCounts = [
  'new' => 0,
  'pending_payment' => 0,
  'reviewing' => 0,
  'paid' => 0,
  'paid_review' => 0,
  'paid_reviewing' => 0,
  'in_production' => 0,
  'completed' => 0,
];

foreach ($customDesignOrders as $customDesignOrder) {
  $statusKey = strtolower((string) ($customDesignOrder['status'] ?? 'new'));
  if (isset($customDesignOrderStatusCounts[$statusKey])) {
    $customDesignOrderStatusCounts[$statusKey]++;
  }
}

$escapeCustomOrder = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatCustomOrderStatus = static function ($value) {
  return ucwords(str_replace('_', ' ', (string) $value));
};

$formatCustomOrderDate = static function ($value) {
  return girffonAdminCustomDesignFormatRomeDate((string) $value);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Custom Design Orders</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    body.admin-page {
      overflow-x: hidden;
    }

    .admin-main,
    .admin-page-section,
    .admin-page-section > .admin-panel,
    .admin-page-section > .admin-table-panel,
    .admin-table-wrap {
      min-width: 0;
    }

    .admin-main {
      max-width: 100%;
      overflow-x: hidden;
    }

    .admin-table-panel {
      overflow: hidden;
    }

    .admin-table-wrap {
      width: 100%;
      max-width: 100%;
      overflow-x: auto;
      overflow-y: hidden;
      overscroll-behavior-x: contain;
      -webkit-overflow-scrolling: touch;
      scrollbar-gutter: stable both-edges;
    }

    .admin-table {
      width: max-content;
      min-width: 100%;
    }

    .admin-custom-summary-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
    }

    .admin-custom-summary-card {
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(199, 165, 75, 0.15);
      background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(251,247,239,0.88));
    }

    .admin-custom-summary-card span {
      display: block;
      color: #8a7753;
      font-size: 0.82rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .admin-custom-summary-card strong {
      display: block;
      margin-top: 8px;
      color: #2b241b;
      font-size: 1.6rem;
    }

    .admin-custom-meta {
      display: grid;
      gap: 4px;
    }

    .admin-custom-meta small {
      color: #7d715f;
      font-size: 0.85rem;
    }

    .admin-custom-view-link {
      white-space: nowrap;
    }

    @media (max-width: 720px) {
      .admin-custom-summary-grid {
        grid-template-columns: 1fr;
      }

      .admin-topbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 14px;
      }

      .admin-topbar-actions {
        width: auto !important;
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 10px !important;
        justify-content: flex-end !important;
        align-self: flex-end !important;
        margin-left: auto !important;
      }

      .admin-topbar-actions .admin-button {
        position: relative;
        flex: 0 0 48px;
        width: 48px !important;
        min-width: 48px !important;
        height: 48px;
        min-height: 48px;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        color: transparent !important;
        font-size: 0 !important;
        line-height: 0;
        overflow: visible;
        white-space: nowrap;
      }

      .admin-topbar-actions .admin-button::before {
        content: "";
        width: 18px;
        height: 18px;
        background-repeat: no-repeat;
        background-position: center;
        background-size: 18px 18px;
      }

      .admin-view-shop-button::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 11.5 12 5l8.5 6.5'/%3E%3Cpath d='M6.5 10.5V19h11v-8.5'/%3E%3Cpath d='M10 19v-4.5h4V19'/%3E%3C/svg%3E");
      }

      .admin-refresh-button::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 2v6h-6'/%3E%3Cpath d='M3 22v-6h6'/%3E%3Cpath d='M20.49 9A9 9 0 0 0 5.64 5.64L3 8'/%3E%3Cpath d='M3.51 15A9 9 0 0 0 18.36 18.36L21 16'/%3E%3C/svg%3E");
      }

      .admin-settings-button::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='3.1'/%3E%3Cpath d='M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.7l-1 1a1.2 1.2 0 0 1-1.7 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9v.3a1.2 1.2 0 0 1-1.2 1.2h-1.4a1.2 1.2 0 0 1-1.2-1.2v-.2a1 1 0 0 0-.7-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.7 0l-1-1a1.2 1.2 0 0 1 0-1.7l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6h-.3A1.2 1.2 0 0 1 3 13.4V12a1.2 1.2 0 0 1 1.2-1.2h.2a1 1 0 0 0 .9-.7 1 1 0 0 0-.2-1.1L5 8.9a1.2 1.2 0 0 1 0-1.7l1-1a1.2 1.2 0 0 1 1.7 0l.1.1a1 1 0 0 0 1.1.2h.1a1 1 0 0 0 .6-.9v-.3A1.2 1.2 0 0 1 10.8 4h1.4a1.2 1.2 0 0 1 1.2 1.2v.2a1 1 0 0 0 .7.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.7 0l1 1a1.2 1.2 0 0 1 0 1.7l-.1.1a1 1 0 0 0-.2 1.1v.1a1 1 0 0 0 .9.6h.3A1.2 1.2 0 0 1 21 12v1.4a1.2 1.2 0 0 1-1.2 1.2h-.2a1 1 0 0 0-.9.4Z'/%3E%3C/svg%3E");
      }

      .admin-topbar-logout-button::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23b63a3a' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M10 6H7.5A1.5 1.5 0 0 0 6 7.5v9A1.5 1.5 0 0 0 7.5 18H10'/%3E%3Cpath d='M14 8l4 4-4 4'/%3E%3Cpath d='M18 12H10'/%3E%3C/svg%3E");
      }
    }

    @media (max-width: 520px) {
      .admin-main,
      .admin-page-section,
      .admin-page-section > .admin-panel,
      .admin-page-section > .admin-table-panel {
        min-width: 0;
      }

      .admin-main {
        overflow-x: hidden;
      }

      .admin-panel-head h2 {
        font-size: 1rem;
      }

      .admin-panel-note,
      .admin-table td,
      .admin-table td strong {
        overflow-wrap: anywhere;
      }

      .admin-table {
        min-width: 640px;
      }

      .admin-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
      }
    }

    @media (max-width: 420px) {
      .admin-main {
        padding-left: 10px !important;
        padding-right: 10px !important;
      }

      .admin-panel,
      .admin-table-panel {
        padding: 14px 12px !important;
      }

      .admin-topbar-actions {
        gap: 8px !important;
      }

      .admin-topbar-actions .admin-button {
        flex: 0 0 44px;
        width: 44px !important;
        min-width: 44px !important;
        height: 44px;
        min-height: 44px;
      }

      .admin-table {
        min-width: 600px;
      }

      .admin-table-wrap {
        border-radius: 16px;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="custom-orders">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Custom design orders dashboard shell ready for future live order integration.</p>
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
        <a class="admin-nav-link" href="admin-gift-cards.php" aria-label="Gift Cards" title="Gift Cards"><span class="admin-nav-link-index">10. </span><span class="admin-nav-link-label">Gift Cards</span></a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Custom Design Orders</strong>
          <p class="admin-panel-note">This page is ready for future custom design order records and file review.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Custom Design Orders</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-custom.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if ($showCustomOrderSummaryCards): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Custom Design Orders</h2>
              <p class="admin-panel-note">Standalone intake for custom design review. Custom design payment runs through its own checkout flow and stays disconnected from CartTest and invoice-before-payment.</p>
            </div>
          </div>
          <div class="admin-custom-summary-grid" aria-label="Custom design order summary">
            <article class="admin-custom-summary-card"><span>Total Orders</span><strong><?php echo $escapeCustomOrder(count($customDesignOrders)); ?></strong></article>
            <article class="admin-custom-summary-card"><span>New / Pending Payment</span><strong><?php echo $escapeCustomOrder($customDesignOrderStatusCounts['new'] + $customDesignOrderStatusCounts['pending_payment']); ?></strong></article>
            <article class="admin-custom-summary-card"><span>Paid / Reviewing</span><strong><?php echo $escapeCustomOrder($customDesignOrderStatusCounts['paid'] + $customDesignOrderStatusCounts['paid_review'] + $customDesignOrderStatusCounts['paid_reviewing'] + $customDesignOrderStatusCounts['reviewing']); ?></strong></article>
            <article class="admin-custom-summary-card"><span>In Production / Completed</span><strong><?php echo $escapeCustomOrder($customDesignOrderStatusCounts['in_production'] + $customDesignOrderStatusCounts['completed']); ?></strong></article>
          </div>
        </article>
        <?php endif; ?>

        <?php if ($showCustomOrderList): ?>
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Order List</h2>
              <p class="admin-panel-note">Each row is a receiving record for one custom design order. Open View to inspect previews, uploads, text, flag, shapes, icons, fill, and selected design assets.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <?php if ($showCustomOrderIdColumn): ?><th>Order ID</th><?php endif; ?>
                  <?php if ($showCustomOrderCustomerColumn): ?><th>Customer</th><?php endif; ?>
                  <?php if ($showCustomOrderProductColumn): ?><th>Product</th><?php endif; ?>
                  <?php if ($showCustomOrderUploadCountColumn): ?><th>Upload Count</th><?php endif; ?>
                  <?php if ($showCustomOrderTextCountColumn): ?><th>Text Count</th><?php endif; ?>
                  <?php if ($showCustomOrderStatusColumn): ?><th>Status</th><?php endif; ?>
                  <?php if ($showCustomOrderDateColumn): ?><th>Date</th><?php endif; ?>
                  <?php if ($showCustomOrderViewAction): ?><th>Actions</th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($customDesignOrders as $customDesignOrder): ?>
                  <?php $viewHref = !empty($customDesignOrder['is_demo']) ? 'admin-custom-order-view.php?demo=1' : 'admin-custom-order-view.php?id=' . urlencode((string) ($customDesignOrder['id'] ?? 0)); ?>
                  <tr>
                    <?php if ($showCustomOrderIdColumn): ?><td><strong><?php echo $escapeCustomOrder($customDesignOrder['order_code'] ?? '-'); ?></strong></td><?php endif; ?>
                    <?php if ($showCustomOrderCustomerColumn): ?><td>
                      <div class="admin-custom-meta">
                        <strong><?php echo $escapeCustomOrder($customDesignOrder['customer_name'] ?? '-'); ?></strong>
                        <small><?php echo $escapeCustomOrder($customDesignOrder['customer_email'] ?? '-'); ?></small>
                      </div>
                    </td><?php endif; ?>
                    <?php if ($showCustomOrderProductColumn): ?><td><?php echo $escapeCustomOrder($customDesignOrder['product_name'] ?? '-'); ?></td><?php endif; ?>
                    <?php if ($showCustomOrderUploadCountColumn): ?><td><?php echo $escapeCustomOrder($customDesignOrder['upload_count'] ?? 0); ?></td><?php endif; ?>
                    <?php if ($showCustomOrderTextCountColumn): ?><td><?php echo $escapeCustomOrder($customDesignOrder['text_count'] ?? 0); ?></td><?php endif; ?>
                    <?php if ($showCustomOrderStatusColumn): ?><td><?php echo $escapeCustomOrder($formatCustomOrderStatus($customDesignOrder['status'] ?? 'new')); ?></td><?php endif; ?>
                    <?php if ($showCustomOrderDateColumn): ?><td><?php echo $escapeCustomOrder($formatCustomOrderDate($customDesignOrder['created_at'] ?? '')); ?></td><?php endif; ?>
                    <?php if ($showCustomOrderViewAction): ?><td><a class="admin-button admin-button-soft admin-custom-view-link" href="<?php echo $escapeCustomOrder($viewHref); ?>">View</a></td><?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </article>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>