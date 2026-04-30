<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/invoices-data.php";
require_once __DIR__ . "/backend/admin/users-data.php";

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
  <link rel="stylesheet" href="CSS/admin-girffon.css">
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
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if ($adminSelectedUser): ?>
          <p class="admin-inline-note">Showing invoices for <?php echo $escapeAdminInvoice(trim((string) (($adminSelectedUser["first_name"] ?? "") . " " . ($adminSelectedUser["last_name"] ?? ""))) ?: ($adminSelectedUser["email"] ?? "Selected user")); ?>.</p>
        <?php endif; ?>

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
                  <th>Customer Name</th>
                  <th>Subtotal</th>
                  <th>Tax</th>
                  <th>Shipping</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Created At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="adminInvoicesTableBody">
                <?php if ($adminInvoices): ?>
                  <?php foreach ($adminInvoices as $invoice): ?>
                    <tr>
                      <td><strong><?php echo $escapeAdminInvoice($invoice["invoice_number"] ?? ""); ?></strong></td>
                      <td><?php echo $escapeAdminInvoice($invoice["order_number"] ?? "-"); ?></td>
                      <td>
                        <strong><?php echo $escapeAdminInvoice($invoice["customer_name"] ?? "-"); ?></strong>
                        <div><?php echo $escapeAdminInvoice($invoice["customer_email"] ?? "-"); ?></div>
                      </td>
                      <td><?php echo $escapeAdminInvoice($formatAdminInvoiceCurrency($invoice["subtotal"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminInvoice($formatAdminInvoiceCurrency($invoice["tax"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminInvoice($formatAdminInvoiceCurrency($invoice["shipping"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminInvoice($formatAdminInvoiceCurrency(($invoice["total"] ?? $invoice["invoice_total"] ?? 0))); ?></td>
                      <td><?php echo $formatAdminInvoiceLabel($invoice["status"] ?? $invoice["invoice_status"] ?? "pending"); ?></td>
                      <td><?php echo $formatAdminInvoiceDate($invoice["created_at"] ?? ""); ?></td>
                      <td>
                        <div class="admin-table-actions" style="display:flex;flex-wrap:wrap;gap:8px;">
                          <a class="admin-action-button" style="<?php echo $escapeAdminInvoice($adminInvoiceActionStyle); ?>" href="<?php echo $escapeAdminInvoice($adminInvoiceUrl('invoice-view.php?id=' . rawurlencode((string) ($invoice['id'] ?? '0')) . '&autoprint=0')); ?>" target="_blank" rel="noopener">View</a>
                          <a class="admin-action-button" style="<?php echo $escapeAdminInvoice($adminInvoiceActionStyle); ?>" href="<?php echo $escapeAdminInvoice($adminInvoiceUrl('invoice-pdf.php?id=' . rawurlencode((string) ($invoice['id'] ?? '0')))); ?>" target="_blank" rel="noopener">PDF</a>
                          <a class="admin-action-button" style="<?php echo $escapeAdminInvoice($adminInvoiceActionStyle); ?>" href="<?php echo $escapeAdminInvoice($adminInvoiceUrl('invoice-print.php?id=' . rawurlencode((string) ($invoice['id'] ?? '0')))); ?>" target="_blank" rel="noopener">Print</a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="10" class="admin-empty">No invoices found in the database yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js"></script>
</body>
</html>
