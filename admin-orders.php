<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/orders-data.php";
require_once __DIR__ . "/backend/admin/order-updates-data.php";
require_once __DIR__ . "/backend/admin/users-data.php";
require_once __DIR__ . "/backend/admin/custom-design-orders-data.php";

$adminOrderSettingsFile = __DIR__ . "/backend/admin/order-settings-data.php";
if (is_file($adminOrderSettingsFile)) {
  require_once $adminOrderSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));

$adminSelectedUserId = max(0, (int) ($_GET["user_id"] ?? 0));
$adminSelectedUser = $adminSelectedUserId > 0 ? girffonAdminFetchUserById($pdo, $adminSelectedUserId) : null;
$adminOrderFilters = [];
if ($adminSelectedUser && !empty($adminSelectedUser["email"])) {
  $adminOrderFilters["customer_email"] = (string) $adminSelectedUser["email"];
}

$adminOrders = girffonAdminFetchOrders($pdo, 0, $adminOrderFilters);
$adminPaidCustomDesignOrders = array_values(array_filter(
  girffonAdminFetchCustomDesignOrderSummaries($pdo, 20, ['payment_status' => 'paid']),
  static function (array $row): bool {
    return empty($row['is_demo']);
  }
));
$adminOrderStatusMessage = trim((string) ($_GET["status"] ?? ""));
$adminOrderErrorMessage = trim((string) ($_GET["error"] ?? ""));
$adminOrderStatusOptions = girffonAdminOrderUpdateStatusOptions();
$adminPaymentStatusOptions = girffonAdminOrderPaymentStatusOptions();
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

if (function_exists('girffonAdminFetchOrderPreferences')) {
  $adminOrderPreferences = girffonAdminFetchOrderPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$showAdminOrdersOverview = !empty($adminOrderPreferences['show_orders_overview']);
$showAdminOrderList = !empty($adminOrderPreferences['show_order_list']);
$showAdminOrderCustomerColumn = !empty($adminOrderPreferences['show_customer_column']);
$showAdminOrderPaymentMethodColumn = !empty($adminOrderPreferences['show_payment_method_column']);
$showAdminOrderPaymentStatusColumn = !empty($adminOrderPreferences['show_payment_status_column']);
$showAdminOrderStatusColumn = !empty($adminOrderPreferences['show_order_status_column']);
$showAdminOrderTrackingColumn = !empty($adminOrderPreferences['show_tracking_column']);
$showAdminOrderCourierColumn = !empty($adminOrderPreferences['show_courier_column']);
$showAdminOrderEtaColumn = !empty($adminOrderPreferences['show_eta_column']);
$showAdminOrderAdminNoteColumn = !empty($adminOrderPreferences['show_admin_note_column']);
$showAdminOrderCreatedAtColumn = !empty($adminOrderPreferences['show_created_at_column']);
$showAdminOrderSaveAction = !empty($adminOrderPreferences['show_save_action']);
$showAdminOrderTrackAction = !empty($adminOrderPreferences['show_track_action']);
$showAdminOrderInvoiceAction = !empty($adminOrderPreferences['show_invoice_action']);
$adminOrderVisibleColumnCount = 6
  + ($showAdminOrderCustomerColumn ? 1 : 0)
  + ($showAdminOrderPaymentMethodColumn ? 1 : 0)
  + ($showAdminOrderPaymentStatusColumn ? 1 : 0)
  + ($showAdminOrderStatusColumn ? 1 : 0)
  + ($showAdminOrderTrackingColumn ? 1 : 0)
  + ($showAdminOrderCourierColumn ? 1 : 0)
  + ($showAdminOrderEtaColumn ? 1 : 0)
  + ($showAdminOrderAdminNoteColumn ? 1 : 0)
  + ($showAdminOrderCreatedAtColumn ? 1 : 0)
  + 1;
$escapeAdminOrder = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$formatAdminOrderCurrency = static function ($value) {
  return "EUR " . number_format((float) $value, 2, ".", ",");
};
$formatAdminOrderLabel = static function ($value) use ($escapeAdminOrder) {
  return $escapeAdminOrder(ucwords(str_replace("_", " ", (string) $value)));
};
$formatAdminOrderDate = static function ($value) use ($escapeAdminOrder) {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime((string) $value);
  return $timestamp ? $escapeAdminOrder(date("Y-m-d H:i", $timestamp)) : $escapeAdminOrder($value);
};
$formatAdminCustomDesignDate = static function ($value) use ($escapeAdminOrder) {
  return $escapeAdminOrder(girffonAdminCustomDesignFormatRomeDate((string) $value));
};
$formatAdminOrderInputDate = static function ($value) {
  if (!$value) {
    return "";
  }

  $timestamp = strtotime((string) $value);
  return $timestamp ? date("Y-m-d", $timestamp) : trim((string) $value);
};
$resolveAdminOrderImage = static function ($path) {
  $value = trim((string) $path);
  if ($value === "") {
    return null;
  }

  if (preg_match('/^https?:\/\//i', $value)) {
    return $value;
  }

  if (str_starts_with($value, '/GirffoN/')) {
    return $value;
  }

  if (str_starts_with($value, '/')) {
    return '/GirffoN' . $value;
  }

  return '/GirffoN/' . ltrim(str_replace('\\', '/', $value), '/');
};
$renderAdminOrderOptionList = static function (array $options, $selected) use ($escapeAdminOrder, $formatAdminOrderLabel) {
  $selected = strtolower(trim((string) $selected));
  $html = "";
  foreach ($options as $option) {
    $optionValue = strtolower(trim((string) $option));
    $html .= '<option value="' . $escapeAdminOrder($optionValue) . '"' . ($optionValue === $selected ? ' selected' : '') . '>' . $formatAdminOrderLabel($optionValue) . '</option>';
  }

  return $html;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Orders</title>
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

    @media (max-width: 720px) {
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

      .admin-inline-note,
      .admin-panel-note,
      .admin-feedback,
      .admin-table td,
      .admin-table td strong {
        overflow-wrap: anywhere;
      }

      .admin-order-thumb {
        width: 44px;
        height: 44px;
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

      .admin-order-update-actions {
        min-width: 210px;
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

    .admin-panel-head-actions {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }

    .admin-order-update-cell {
      min-width: 132px;
      vertical-align: top;
    }

    .admin-order-update-cell textarea,
    .admin-order-update-cell input,
    .admin-order-update-cell select {
      width: 100%;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(196, 159, 96, 0.3);
      background: rgba(20, 16, 12, 0.9);
      color: #f5efe6;
      box-sizing: border-box;
      font: inherit;
    }

    .admin-order-update-cell textarea {
      min-height: 84px;
      resize: vertical;
    }

    .admin-order-update-cell input[type="date"] {
      color-scheme: dark;
    }

    .admin-order-update-note {
      display: block;
      margin-top: 8px;
      color: rgba(245, 239, 230, 0.66);
      font-size: 0.76rem;
      line-height: 1.5;
    }

    .admin-order-update-actions {
      min-width: 176px;
    }

    .admin-order-action-stack {
      display: grid;
      gap: 10px;
    }

    .admin-order-action-stack .admin-button,
    .admin-order-action-stack a.admin-button {
      width: 100%;
      justify-content: center;
      text-align: center;
      box-sizing: border-box;
    }

    .admin-order-customer-note {
      display: block;
      margin-top: 8px;
      color: #c9a56a;
      font-size: 0.78rem;
    }

    .admin-order-update-cell--note {
      min-width: 168px;
    }

    .admin-order-update-cell--status {
      min-width: 120px;
    }

    .admin-order-update-cell--tracking {
      min-width: 112px;
    }

    .admin-order-update-note {
      display: none;
    }
  </style>
</head>
<body class="admin-page" data-admin-page="orders" data-admin-orders-source="database">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Order overview from the live order database.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link is-active" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Order Fields</strong>
          <p class="admin-panel-note">Customer name, email, product, quantity, total price, and order status.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Orders</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-orders.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if ($adminSelectedUser): ?>
          <p class="admin-inline-note">Showing orders for <?php echo $escapeAdminOrder(trim((string) (($adminSelectedUser["first_name"] ?? "") . " " . ($adminSelectedUser["last_name"] ?? ""))) ?: ($adminSelectedUser["email"] ?? "Selected user")); ?>.</p>
        <?php endif; ?>

        <?php if ($showAdminOrdersOverview): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Live Orders</h2>
              <p class="admin-panel-note">Orders below are created from the live checkout flow. Save Update writes status, payment, tracking, courier, ETA, and admin note, then sends an order update email when the customer allows it.</p>
            </div>
            <div class="admin-panel-head-actions">
              <a class="admin-button admin-button-soft" href="/GirffoN/backend/admin/order-updates-debug.php">Check Order Updates</a>
            </div>
          </div>
          <div id="adminOrdersStatus" class="admin-feedback" role="status" aria-live="polite"<?php if ($adminOrderErrorMessage): ?> style="color:#9f2f2f;"<?php endif; ?>><?php echo $escapeAdminOrder($adminOrderErrorMessage ?: $adminOrderStatusMessage); ?></div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminOrderList): ?>
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Paid Custom Design Orders</h2>
              <p class="admin-panel-note">Separate paid custom design orders section to keep the main live order workflow unchanged.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Preview</th>
                  <th>Order Number</th>
                  <th>Customer</th>
                  <th>Product</th>
                  <th>Payment Status</th>
                  <th>Total</th>
                  <th>Paid At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($adminPaidCustomDesignOrders): ?>
                  <?php foreach ($adminPaidCustomDesignOrders as $customOrder): ?>
                    <?php $customPreview = $resolveAdminOrderImage($customOrder['preview_front'] ?? ''); ?>
                    <tr>
                      <td>
                        <?php if ($customPreview): ?>
                          <img class="admin-order-thumb" src="<?php echo $escapeAdminOrder($customPreview); ?>" alt="<?php echo $escapeAdminOrder($customOrder['order_code'] ?? 'Custom design preview'); ?>">
                        <?php else: ?>
                          <div class="admin-order-thumb admin-order-thumb-placeholder">No image</div>
                        <?php endif; ?>
                      </td>
                      <td><strong><?php echo $escapeAdminOrder($customOrder['order_code'] ?? ''); ?></strong></td>
                      <td>
                        <strong><?php echo $escapeAdminOrder($customOrder['customer_name'] ?? ''); ?></strong>
                        <div><?php echo $escapeAdminOrder($customOrder['customer_email'] ?? '-'); ?></div>
                      </td>
                      <td><?php echo $escapeAdminOrder($customOrder['product_name'] ?? 'Custom Product'); ?></td>
                      <td>
                        <strong><?php echo $formatAdminOrderLabel($customOrder['payment_status'] ?? 'paid'); ?></strong>
                        <div><?php echo $formatAdminOrderLabel($customOrder['status'] ?? 'paid_review'); ?></div>
                      </td>
                      <td><?php echo $escapeAdminOrder($formatAdminOrderCurrency($customOrder['order_total'] ?? 0)); ?></td>
                      <td><?php echo $formatAdminCustomDesignDate($customOrder['paid_at'] ?? ($customOrder['created_at'] ?? '')); ?></td>
                      <td class="admin-order-update-actions">
                        <div class="admin-order-action-stack">
                          <a class="admin-button admin-button-soft" href="/GirffoN/admin-custom-order-view.php?id=<?php echo $escapeAdminOrder($customOrder['id'] ?? 0); ?>">View Custom Order</a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="admin-empty">No paid custom design orders yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>

        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Order List</h2>
              <p class="admin-panel-note">All orders currently available in the database.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Order Number</th>
                  <?php if ($showAdminOrderCustomerColumn): ?><th>Customer Name</th><?php endif; ?>
                  <th>Items</th>
                  <th>Subtotal</th>
                  <th>Shipping</th>
                  <th>Total</th>
                  <?php if ($showAdminOrderPaymentMethodColumn): ?><th>Payment Method</th><?php endif; ?>
                  <?php if ($showAdminOrderPaymentStatusColumn): ?><th>Payment Status</th><?php endif; ?>
                  <?php if ($showAdminOrderStatusColumn): ?><th>Order Status</th><?php endif; ?>
                  <?php if ($showAdminOrderTrackingColumn): ?><th>Tracking Number</th><?php endif; ?>
                  <?php if ($showAdminOrderCourierColumn): ?><th>Courier</th><?php endif; ?>
                  <?php if ($showAdminOrderEtaColumn): ?><th>Estimated Delivery</th><?php endif; ?>
                  <?php if ($showAdminOrderAdminNoteColumn): ?><th>Admin Note</th><?php endif; ?>
                  <?php if ($showAdminOrderCreatedAtColumn): ?><th>Created At</th><?php endif; ?>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="adminOrdersTableBody">
                <?php if ($adminOrders): ?>
                  <?php foreach ($adminOrders as $order): ?>
                    <?php $orderImage = $resolveAdminOrderImage($order["item_image"] ?? ""); ?>
                    <?php $formId = "adminOrderUpdateForm" . (int) ($order["id"] ?? 0); ?>
                    <?php $trackOrderUrl = "/GirffoN/TrackOrder.php?order_number=" . rawurlencode((string) ($order["order_number"] ?? "")); ?>
                    <?php $invoiceUrl = !empty($order["invoice_id"]) ? "/GirffoN/invoice-view.php?id=" . rawurlencode((string) $order["invoice_id"]) : ""; ?>
                    <?php $orderUpdateEmailEnabled = girffonAdminOrderUpdateEmailEnabled($pdo, $order); ?>
                    <tr>
                      <td>
                        <?php if ($orderImage): ?>
                          <img class="admin-order-thumb" src="<?php echo $escapeAdminOrder($orderImage); ?>" alt="<?php echo $escapeAdminOrder($order["order_number"] ?? "Order image"); ?>">
                        <?php else: ?>
                          <div class="admin-order-thumb admin-order-thumb-placeholder">No image</div>
                        <?php endif; ?>
                      </td>
                      <td><strong><?php echo $escapeAdminOrder($order["order_number"] ?? ""); ?></strong></td>
                      <?php if ($showAdminOrderCustomerColumn): ?>
                      <td>
                        <strong><?php echo $escapeAdminOrder($order["customer_name"] ?? ""); ?></strong>
                        <div><?php echo $escapeAdminOrder($order["customer_email"] ?? "-"); ?></div>
                        <div><?php echo $escapeAdminOrder($order["phone"] ?? "-"); ?></div>
                        <div><?php echo $escapeAdminOrder(trim((string) (($order['address'] ?? '') . ', ' . ($order['city'] ?? '') . ', ' . ($order['country'] ?? '') . ' ' . ($order['postcode'] ?? ''))) ?: '-'); ?></div>
                        <span class="admin-order-customer-note">Order update emails <?php echo $escapeAdminOrder($orderUpdateEmailEnabled ? 'enabled' : 'disabled'); ?></span>
                      </td>
                      <?php endif; ?>
                      <td><?php echo $escapeAdminOrder((string) ((int) ($order["item_count"] ?? 0))); ?></td>
                      <td><?php echo $escapeAdminOrder($formatAdminOrderCurrency($order["subtotal"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminOrder($formatAdminOrderCurrency($order["shipping"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminOrder($formatAdminOrderCurrency($order["total"] ?? 0)); ?></td>
                      <?php if ($showAdminOrderPaymentMethodColumn): ?>
                      <td><?php echo $formatAdminOrderLabel($order["payment_method"] ?? "bank_transfer"); ?></td>
                      <?php endif; ?>
                      <?php if ($showAdminOrderPaymentStatusColumn): ?>
                      <td class="admin-order-update-cell admin-order-update-cell--status">
                        <select name="payment_status" form="<?php echo $escapeAdminOrder($formId); ?>">
                          <?php echo $renderAdminOrderOptionList($adminPaymentStatusOptions, $order["payment_status"] ?? "pending"); ?>
                        </select>
                      </td>
                      <?php else: ?>
                      <input type="hidden" name="payment_status" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder(strtolower(trim((string) ($order['payment_status'] ?? 'pending')))); ?>">
                      <?php endif; ?>
                      <?php if ($showAdminOrderStatusColumn): ?>
                      <td class="admin-order-update-cell admin-order-update-cell--status">
                        <select name="order_status" form="<?php echo $escapeAdminOrder($formId); ?>">
                          <?php echo $renderAdminOrderOptionList($adminOrderStatusOptions, $order["order_status"] ?? "pending"); ?>
                        </select>
                        <span class="admin-order-update-note">Pending, Paid, Preparing, Printed, Shipped, Delivered, Cancelled.</span>
                      </td>
                      <?php else: ?>
                      <input type="hidden" name="order_status" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder(strtolower(trim((string) ($order['order_status'] ?? 'pending')))); ?>">
                      <?php endif; ?>
                      <?php if ($showAdminOrderTrackingColumn): ?>
                      <td class="admin-order-update-cell admin-order-update-cell--tracking">
                        <input type="text" name="tracking_number" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder($order["tracking_code"] ?? ""); ?>" placeholder="Tracking number">
                      </td>
                      <?php else: ?>
                      <input type="hidden" name="tracking_number" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder($order['tracking_code'] ?? ''); ?>">
                      <?php endif; ?>
                      <?php if ($showAdminOrderCourierColumn): ?>
                      <td class="admin-order-update-cell">
                        <input type="text" name="courier_name" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder($order["courier_name"] ?? ""); ?>" placeholder="Courier name">
                      </td>
                      <?php else: ?>
                      <input type="hidden" name="courier_name" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder($order['courier_name'] ?? ''); ?>">
                      <?php endif; ?>
                      <?php if ($showAdminOrderEtaColumn): ?>
                      <td class="admin-order-update-cell">
                        <input type="date" name="estimated_delivery_date" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder($formatAdminOrderInputDate($order["estimated_delivery_date"] ?? "")); ?>">
                      </td>
                      <?php else: ?>
                      <input type="hidden" name="estimated_delivery_date" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder($formatAdminOrderInputDate($order['estimated_delivery_date'] ?? '')); ?>">
                      <?php endif; ?>
                      <?php if ($showAdminOrderAdminNoteColumn): ?>
                      <td class="admin-order-update-cell admin-order-update-cell--note">
                        <textarea name="admin_note" form="<?php echo $escapeAdminOrder($formId); ?>" placeholder="Internal note or customer-facing update message"><?php echo $escapeAdminOrder($order["admin_note"] ?? ""); ?></textarea>
                      </td>
                      <?php else: ?>
                      <input type="hidden" name="admin_note" form="<?php echo $escapeAdminOrder($formId); ?>" value="<?php echo $escapeAdminOrder($order['admin_note'] ?? ''); ?>">
                      <?php endif; ?>
                      <?php if ($showAdminOrderCreatedAtColumn): ?><td><?php echo $formatAdminOrderDate($order["created_at"] ?? ""); ?></td><?php endif; ?>
                      <td class="admin-order-update-actions">
                        <div class="admin-order-action-stack">
                          <form id="<?php echo $escapeAdminOrder($formId); ?>" method="post" action="/GirffoN/backend/admin/update-order-status.php">
                            <input type="hidden" name="order_id" value="<?php echo $escapeAdminOrder($order["id"] ?? 0); ?>">
                          </form>
                          <?php if ($showAdminOrderSaveAction): ?>
                          <button class="admin-button" type="submit" form="<?php echo $escapeAdminOrder($formId); ?>">Save Update</button>
                          <?php endif; ?>
                          <?php if ($showAdminOrderTrackAction): ?>
                          <a class="admin-button admin-button-soft" href="<?php echo $escapeAdminOrder($trackOrderUrl); ?>" target="_blank" rel="noopener">Track Order</a>
                          <?php endif; ?>
                          <?php if ($showAdminOrderInvoiceAction && $invoiceUrl !== ""): ?>
                            <a class="admin-button admin-button-soft" href="<?php echo $escapeAdminOrder($invoiceUrl); ?>" target="_blank" rel="noopener">View Invoice</a>
                          <?php endif; ?>
                          <?php if (!$showAdminOrderSaveAction && !$showAdminOrderTrackAction && !($showAdminOrderInvoiceAction && $invoiceUrl !== '')): ?>
                            <span class="admin-panel-note">Locked</span>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="<?php echo $escapeAdminOrder($adminOrderVisibleColumnCount); ?>" class="admin-empty">No orders found in the database yet.</td>
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
</body>
</html>
