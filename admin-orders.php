<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/orders-data.php";
require_once __DIR__ . "/backend/admin/users-data.php";

$adminSelectedUserId = max(0, (int) ($_GET["user_id"] ?? 0));
$adminSelectedUser = $adminSelectedUserId > 0 ? girffonAdminFetchUserById($pdo, $adminSelectedUserId) : null;
$adminOrderFilters = [];
if ($adminSelectedUser && !empty($adminSelectedUser["email"])) {
  $adminOrderFilters["customer_email"] = (string) $adminSelectedUser["email"];
}

$adminOrders = girffonAdminFetchOrders($pdo, 0, $adminOrderFilters);
$adminOrderStatusMessage = trim((string) ($_GET["status"] ?? ""));
$adminOrderErrorMessage = trim((string) ($_GET["error"] ?? ""));
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Orders</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css">
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
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if ($adminSelectedUser): ?>
          <p class="admin-inline-note">Showing orders for <?php echo $escapeAdminOrder(trim((string) (($adminSelectedUser["first_name"] ?? "") . " " . ($adminSelectedUser["last_name"] ?? ""))) ?: ($adminSelectedUser["email"] ?? "Selected user")); ?>.</p>
        <?php endif; ?>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Live Orders</h2>
              <p class="admin-panel-note">Orders below are created from the Stage 4 checkout flow and stored in the database.</p>
            </div>
          </div>
          <div id="adminOrdersStatus" class="admin-feedback" role="status" aria-live="polite"<?php if ($adminOrderErrorMessage): ?> style="color:#9f2f2f;"<?php endif; ?>><?php echo $escapeAdminOrder($adminOrderErrorMessage ?: $adminOrderStatusMessage); ?></div>
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
                  <th>Customer Name</th>
                  <th>Items</th>
                  <th>Subtotal</th>
                  <th>Shipping</th>
                  <th>Total</th>
                  <th>Payment Method</th>
                  <th>Payment Status</th>
                  <th>Order Status</th>
                  <th>Tracking Code</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody id="adminOrdersTableBody">
                <?php if ($adminOrders): ?>
                  <?php foreach ($adminOrders as $order): ?>
                    <?php $orderImage = $resolveAdminOrderImage($order["item_image"] ?? ""); ?>
                    <tr>
                      <td>
                        <?php if ($orderImage): ?>
                          <img class="admin-order-thumb" src="<?php echo $escapeAdminOrder($orderImage); ?>" alt="<?php echo $escapeAdminOrder($order["order_number"] ?? "Order image"); ?>">
                        <?php else: ?>
                          <div class="admin-order-thumb admin-order-thumb-placeholder">No image</div>
                        <?php endif; ?>
                      </td>
                      <td><strong><?php echo $escapeAdminOrder($order["order_number"] ?? ""); ?></strong></td>
                      <td>
                        <strong><?php echo $escapeAdminOrder($order["customer_name"] ?? ""); ?></strong>
                        <div><?php echo $escapeAdminOrder($order["customer_email"] ?? "-"); ?></div>
                        <div><?php echo $escapeAdminOrder($order["phone"] ?? "-"); ?></div>
                        <div><?php echo $escapeAdminOrder(trim((string) (($order['address'] ?? '') . ', ' . ($order['city'] ?? '') . ', ' . ($order['country'] ?? '') . ' ' . ($order['postcode'] ?? ''))) ?: '-'); ?></div>
                      </td>
                      <td><?php echo $escapeAdminOrder((string) ((int) ($order["item_count"] ?? 0))); ?></td>
                      <td><?php echo $escapeAdminOrder($formatAdminOrderCurrency($order["subtotal"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminOrder($formatAdminOrderCurrency($order["shipping"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminOrder($formatAdminOrderCurrency($order["total"] ?? 0)); ?></td>
                      <td><?php echo $formatAdminOrderLabel($order["payment_method"] ?? "bank_transfer"); ?></td>
                      <td><?php echo $formatAdminOrderLabel($order["payment_status"] ?? "pending"); ?></td>
                      <td><?php echo $formatAdminOrderLabel($order["order_status"] ?? "new"); ?></td>
                      <td><?php echo $escapeAdminOrder($order["tracking_code"] ?? "-"); ?></td>
                      <td><?php echo $formatAdminOrderDate($order["created_at"] ?? ""); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="12" class="admin-empty">No orders found in the database yet.</td>
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
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const imageInput = document.getElementById("adminOrderImage");
      const preview = document.getElementById("adminOrderImagePreview");
      const form = document.getElementById("adminOrdersForm");

      if (!imageInput || !preview || !form) {
        return;
      }

      const renderEmptyPreview = function () {
        preview.innerHTML = "<span>No image selected</span>";
      };

      const renderPreview = function () {
        const file = imageInput.files && imageInput.files[0];
        if (!file) {
          renderEmptyPreview();
          return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
          const result = typeof event.target?.result === "string" ? event.target.result : "";
          preview.innerHTML = result !== "" ? '<img src="' + result + '" alt="Order image preview">' : "<span>Preview unavailable</span>";
        };
        reader.readAsDataURL(file);
      };

      imageInput.addEventListener("change", renderPreview);
      form.addEventListener("reset", function () {
        window.setTimeout(renderEmptyPreview, 0);
      });
      renderEmptyPreview();
    });
  </script>
</body>
</html>
