<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/invoices-data.php";
require_once __DIR__ . "/backend/admin/users-data.php";

$adminInvoiceSettingsFile = __DIR__ . "/backend/admin/invoice-settings-data.php";
if (is_file($adminInvoiceSettingsFile)) {
  require_once $adminInvoiceSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));

$adminSelectedUserId = max(0, (int) ($_GET["user_id"] ?? 0));
$adminSelectedUser = $adminSelectedUserId > 0 ? girffonAdminFetchUserById($pdo, $adminSelectedUserId) : null;
$adminInvoiceFilters = [
  'search' => trim((string) ($_GET['search'] ?? '')),
  'status' => trim((string) ($_GET['status_filter'] ?? '')),
];
if ($adminSelectedUser && !empty($adminSelectedUser["email"])) {
  $adminInvoiceFilters["customer_email"] = (string) $adminSelectedUser["email"];
}

$adminInvoices = girffonAdminFetchInvoices($pdo, 0, $adminInvoiceFilters);
$adminInvoiceStatusMessage = trim((string) ($_GET["status"] ?? ""));
$adminInvoiceErrorMessage = trim((string) ($_GET["error"] ?? ""));
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

if (function_exists('girffonAdminFetchInvoicePreferences')) {
  $adminInvoicePreferences = girffonAdminFetchInvoicePreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$showAdminInvoiceAddPanel = !empty($adminInvoicePreferences['show_add_invoice_panel']);
$showAdminInvoiceSearchFilters = !empty($adminInvoicePreferences['show_search_filters']);
$showAdminInvoiceList = !empty($adminInvoicePreferences['show_invoice_list']);
$showAdminInvoiceCustomerColumn = !empty($adminInvoicePreferences['show_customer_column']);
$showAdminInvoiceTaxColumn = !empty($adminInvoicePreferences['show_tax_column']);
$showAdminInvoiceShippingColumn = !empty($adminInvoicePreferences['show_shipping_column']);
$showAdminInvoiceStatusColumn = !empty($adminInvoicePreferences['show_status_column']);
$showAdminInvoiceCreatedAtColumn = !empty($adminInvoicePreferences['show_created_at_column']);
$showAdminInvoiceViewAction = !empty($adminInvoicePreferences['show_view_action']);
$showAdminInvoicePdfAction = !empty($adminInvoicePreferences['show_pdf_action']);
$showAdminInvoicePrintAction = !empty($adminInvoicePreferences['show_print_action']);
$adminInvoiceVisibleColumnCount = 5
  + ($showAdminInvoiceCustomerColumn ? 1 : 0)
  + ($showAdminInvoiceTaxColumn ? 1 : 0)
  + ($showAdminInvoiceShippingColumn ? 1 : 0)
  + ($showAdminInvoiceStatusColumn ? 1 : 0)
  + ($showAdminInvoiceCreatedAtColumn ? 1 : 0)
  + 1;
$escapeAdminInvoice = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$formatAdminInvoiceCurrency = static function ($value) {
  return "EUR " . number_format((float) $value, 2, ".", ",");
};
$formatAdminInvoiceLabel = static function ($value) use ($escapeAdminInvoice) {
  return $escapeAdminInvoice(ucwords(str_replace("_", " ", (string) $value)));
};
$formatAdminInvoiceDate = static function ($value) use ($escapeAdminInvoice) {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime((string) $value);
  return $timestamp ? $escapeAdminInvoice(date("Y-m-d H:i", $timestamp)) : $escapeAdminInvoice($value);
};
$adminToday = date('Y-m-d');
$adminInvoiceBasePath = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
if ($adminInvoiceBasePath === '/' || $adminInvoiceBasePath === '.') {
  $adminInvoiceBasePath = '';
}
$adminInvoiceUrl = static function (string $path) use ($adminInvoiceBasePath): string {
  return $adminInvoiceBasePath . '/' . ltrim(str_replace('\\', '/', $path), '/');
};
$adminInvoiceActionStyle = 'display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:8px 12px;border-radius:999px;border:1px solid rgba(199,165,75,0.22);background:linear-gradient(180deg,#fffdfa 0%,#f6eedc 100%);color:#2b241b;text-decoration:none;font:inherit;font-size:0.8rem;font-weight:700;line-height:1;box-shadow:0 10px 20px rgba(124,91,37,0.08);white-space:nowrap;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Invoices</title>
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

      .admin-grid-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .admin-table-wrap {
        width: 100%;
        max-width: 100%;
      }
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
      .admin-page-section > .admin-table-panel,
      .admin-grid-form,
      .admin-field,
      .admin-field-wide {
        min-width: 0;
      }

      .admin-main {
        overflow-x: hidden;
      }

      .admin-grid-form {
        gap: 14px;
      }

      .admin-input,
      .admin-select {
        min-width: 0;
        padding: 12px 13px;
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

      .admin-table-actions {
        gap: 6px !important;
        justify-content: flex-start !important;
      }

      .admin-action-button[style] {
        min-height: 34px !important;
        padding: 7px 10px !important;
        font-size: 0.75rem !important;
      }

      .admin-table {
        min-width: 700px;
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
        min-width: 660px;
      }

      .admin-table-wrap {
        border-radius: 16px;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="invoices" data-admin-invoices-source="database">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Invoice management backed by the live billing database.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link is-active" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Invoice Fields</strong>
          <p class="admin-panel-note">Invoice number, customer name, date, amount, and payment status.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Invoices</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-invoices.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if ($adminSelectedUser): ?>
          <p class="admin-inline-note">Showing invoices for <?php echo $escapeAdminInvoice(trim((string) (($adminSelectedUser["first_name"] ?? "") . " " . ($adminSelectedUser["last_name"] ?? ""))) ?: ($adminSelectedUser["email"] ?? "Selected user")); ?>.</p>
        <?php endif; ?>

        <?php if ($showAdminInvoiceAddPanel): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Add Invoice</h2>
              <p class="admin-panel-note">Manual invoice fields restored for the original layout. Live invoices still come from checkout orders and database records.</p>
            </div>
          </div>
          <form class="admin-grid-form" method="POST" action="backend/admin/save-invoice.php">
            <div class="admin-field">
              <label for="adminInvoiceNumber">Invoice Number</label>
              <input class="admin-input" id="adminInvoiceNumber" name="invoiceNumber" type="text" value="" placeholder="Leave empty to auto-generate">
            </div>
            <div class="admin-field">
              <label for="adminOrderNumber">Order Number</label>
              <input class="admin-input" id="adminOrderNumber" name="orderNumber" type="text" value="" placeholder="Optional or auto-generated">
            </div>
            <div class="admin-field">
              <label for="adminInvoiceCustomerName">Customer Name</label>
              <input class="admin-input" id="adminInvoiceCustomerName" name="customerName" type="text" value="" required>
            </div>
            <div class="admin-field">
              <label for="adminInvoiceCustomerEmail">Customer Email</label>
              <input class="admin-input" id="adminInvoiceCustomerEmail" name="customerEmail" type="email" value="" placeholder="Optional">
            </div>
            <div class="admin-field">
              <label for="adminInvoiceDate">Date</label>
              <input class="admin-input" id="adminInvoiceDate" name="date" type="date" value="<?php echo $escapeAdminInvoice($adminToday); ?>">
            </div>
            <div class="admin-field">
              <label for="adminInvoiceAmount">Amount</label>
              <input class="admin-input" id="adminInvoiceAmount" name="amount" type="number" min="0.01" step="0.01" value="" required>
            </div>
            <div class="admin-field">
              <label for="adminInvoiceStatus">Payment Status</label>
              <select class="admin-select" id="adminInvoiceStatus" name="paymentStatus">
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit">Save Invoice</button>
            </div>
          </form>
          <div id="adminInvoicesStatus" class="admin-feedback" role="status" aria-live="polite"<?php if ($adminInvoiceErrorMessage): ?> style="color:#9f2f2f;"<?php endif; ?>><?php echo $escapeAdminInvoice($adminInvoiceErrorMessage ?: $adminInvoiceStatusMessage); ?></div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminInvoiceSearchFilters): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Search & Filter</h2>
              <p class="admin-panel-note">Filter live invoices by invoice number, order number, customer, email, or invoice status.</p>
            </div>
          </div>
          <form class="admin-grid-form" method="GET" action="admin-invoices.php">
            <?php if ($adminSelectedUserId > 0): ?>
              <input type="hidden" name="user_id" value="<?php echo $escapeAdminInvoice((string) $adminSelectedUserId); ?>">
            <?php endif; ?>
            <div class="admin-field admin-field-wide">
              <label for="adminInvoiceSearch">Search</label>
              <input class="admin-input" id="adminInvoiceSearch" name="search" type="text" value="<?php echo $escapeAdminInvoice($adminInvoiceFilters['search']); ?>" placeholder="Invoice number, order number, customer, or email">
            </div>
            <div class="admin-field">
              <label for="adminInvoiceStatusFilter">Status</label>
              <select class="admin-select" id="adminInvoiceStatusFilter" name="status_filter">
                <option value="">All Statuses</option>
                <option value="pending"<?php if ($adminInvoiceFilters['status'] === 'pending'): ?> selected<?php endif; ?>>Pending</option>
                <option value="paid"<?php if ($adminInvoiceFilters['status'] === 'paid'): ?> selected<?php endif; ?>>Paid</option>
                <option value="cancelled"<?php if ($adminInvoiceFilters['status'] === 'cancelled'): ?> selected<?php endif; ?>>Cancelled</option>
              </select>
            </div>
            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit">Apply Filters</button>
              <a class="admin-button admin-button-soft" href="admin-invoices.php<?php echo $adminSelectedUserId > 0 ? '?user_id=' . rawurlencode((string) $adminSelectedUserId) : ''; ?>">Clear</a>
            </div>
          </form>
        </article>
        <?php endif; ?>

        <?php if ($showAdminInvoiceList): ?>
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Invoice List</h2>
              <p class="admin-panel-note">Stored invoice records loaded from the database.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Invoice Number</th>
                  <th>Order Number</th>
                  <?php if ($showAdminInvoiceCustomerColumn): ?><th>Customer Name</th><?php endif; ?>
                  <th>Subtotal</th>
                  <?php if ($showAdminInvoiceTaxColumn): ?><th>Tax</th><?php endif; ?>
                  <?php if ($showAdminInvoiceShippingColumn): ?><th>Shipping</th><?php endif; ?>
                  <th>Total</th>
                  <?php if ($showAdminInvoiceStatusColumn): ?><th>Status</th><?php endif; ?>
                  <?php if ($showAdminInvoiceCreatedAtColumn): ?><th>Created At</th><?php endif; ?>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="adminInvoicesTableBody">
                <?php if ($adminInvoices): ?>
                  <?php foreach ($adminInvoices as $invoice): ?>
                    <tr>
                      <td><strong><?php echo $escapeAdminInvoice($invoice["invoice_number"] ?? ""); ?></strong></td>
                      <td><?php echo $escapeAdminInvoice($invoice["order_number"] ?? "-"); ?></td>
                      <?php if ($showAdminInvoiceCustomerColumn): ?>
                      <td>
                        <strong><?php echo $escapeAdminInvoice($invoice["customer_name"] ?? "-"); ?></strong>
                        <div><?php echo $escapeAdminInvoice($invoice["customer_email"] ?? "-"); ?></div>
                      </td>
                      <?php endif; ?>
                      <td><?php echo $escapeAdminInvoice($formatAdminInvoiceCurrency($invoice["subtotal"] ?? 0)); ?></td>
                      <?php if ($showAdminInvoiceTaxColumn): ?><td><?php echo $escapeAdminInvoice($formatAdminInvoiceCurrency($invoice["tax"] ?? 0)); ?></td><?php endif; ?>
                      <?php if ($showAdminInvoiceShippingColumn): ?><td><?php echo $escapeAdminInvoice($formatAdminInvoiceCurrency($invoice["shipping"] ?? 0)); ?></td><?php endif; ?>
                      <td><?php echo $escapeAdminInvoice($formatAdminInvoiceCurrency(($invoice["total"] ?? $invoice["invoice_total"] ?? 0))); ?></td>
                      <?php if ($showAdminInvoiceStatusColumn): ?><td><?php echo $formatAdminInvoiceLabel($invoice["status"] ?? $invoice["invoice_status"] ?? "pending"); ?></td><?php endif; ?>
                      <?php if ($showAdminInvoiceCreatedAtColumn): ?><td><?php echo $formatAdminInvoiceDate($invoice["created_at"] ?? ""); ?></td><?php endif; ?>
                      <td>
                        <div class="admin-table-actions" style="display:flex;flex-wrap:wrap;gap:8px;">
                          <?php if ($showAdminInvoiceViewAction): ?>
                          <a class="admin-action-button" style="<?php echo $escapeAdminInvoice($adminInvoiceActionStyle); ?>" href="<?php echo $escapeAdminInvoice($adminInvoiceUrl('invoice-view.php?id=' . rawurlencode((string) ($invoice['id'] ?? '0')) . '&autoprint=0')); ?>" target="_blank" rel="noopener">View</a>
                          <?php endif; ?>
                          <?php if ($showAdminInvoicePdfAction): ?>
                          <a class="admin-action-button" style="<?php echo $escapeAdminInvoice($adminInvoiceActionStyle); ?>" href="<?php echo $escapeAdminInvoice($adminInvoiceUrl('invoice-pdf.php?id=' . rawurlencode((string) ($invoice['id'] ?? '0')))); ?>" target="_blank" rel="noopener">PDF</a>
                          <?php endif; ?>
                          <?php if ($showAdminInvoicePrintAction): ?>
                          <a class="admin-action-button" style="<?php echo $escapeAdminInvoice($adminInvoiceActionStyle); ?>" href="<?php echo $escapeAdminInvoice($adminInvoiceUrl('invoice-print.php?id=' . rawurlencode((string) ($invoice['id'] ?? '0')))); ?>" target="_blank" rel="noopener">Print</a>
                          <?php endif; ?>
                          <?php if (!$showAdminInvoiceViewAction && !$showAdminInvoicePdfAction && !$showAdminInvoicePrintAction): ?>
                            <span class="admin-panel-note">Locked</span>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="<?php echo $escapeAdminInvoice($adminInvoiceVisibleColumnCount); ?>" class="admin-empty">No invoices found in the database yet.</td>
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
