<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/orders-data.php';
require_once __DIR__ . '/order-updates-data.php';

$summary = girffonAdminOrderUpdatesDebugSummary($pdo);
$recentLogs = girffonAdminFetchRecentOrderUpdateLogs($pdo, 30);
$recentOrders = girffonAdminFetchOrders($pdo, 40);

$escapeDebug = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$formatDebugDate = static function ($value) use ($escapeDebug): string {
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? $escapeDebug(date('Y-m-d H:i', $timestamp)) : $escapeDebug((string) $value);
};
$formatDebugLabel = static function ($value) use ($escapeDebug): string {
    return $escapeDebug(girffonOrderUpdateStatusLabel((string) $value));
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Order Updates Debug</title>
  <link rel="stylesheet" href="/GirffoN/CSS/admin-girffon.css?v=20260511r15">
  <style>
    .admin-debug-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
      gap: 18px;
    }

    .admin-debug-stat {
      border-radius: 18px;
      border: 1px solid rgba(201, 165, 106, 0.18);
      background: linear-gradient(180deg, rgba(20, 16, 12, 0.95) 0%, rgba(32, 25, 19, 0.92) 100%);
      padding: 20px;
      display: grid;
      gap: 10px;
    }

    .admin-debug-stat span {
      color: rgba(245, 239, 230, 0.72);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .admin-debug-stat strong {
      color: #f7f1e7;
      font-size: 1.7rem;
      line-height: 1;
    }

    .admin-debug-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .admin-debug-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 94px;
      padding: 8px 12px;
      border-radius: 999px;
      background: rgba(201, 165, 106, 0.14);
      color: #e9c68c;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .admin-debug-badge.is-off {
      background: rgba(159, 47, 47, 0.18);
      color: #f4b6b6;
    }

    .admin-debug-note {
      display: block;
      margin-top: 6px;
      color: rgba(245, 239, 230, 0.66);
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      .admin-debug-actions {
        width: 100%;
      }

      .admin-debug-actions .admin-button {
        flex: 1 1 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="orders">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="/GirffoN/Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Order update readiness, delivery metadata, and email delivery logs.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="/GirffoN/admin-dashboard.php">1. Dashboard</a>
        <a class="admin-nav-link" href="/GirffoN/admin-products.php">2. Products</a>
        <a class="admin-nav-link is-active" href="/GirffoN/admin-orders.php">3. Orders</a>
        <a class="admin-nav-link" href="/GirffoN/admin-invoices.php">4. Invoices</a>
        <a class="admin-nav-link" href="/GirffoN/admin-messages.php">5. Messages</a>
        <a class="admin-nav-link" href="/GirffoN/admin-users.php">6. Users</a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php">7. Newsletter</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Order Update Debug</strong>
          <p class="admin-panel-note">Use this page to confirm order emails, tracking fields, and update history are all connected.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title">Order Updates Debug</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft" href="/GirffoN/index.html">View Shop</a>
          <button class="admin-button admin-button-soft" type="button" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft" type="button" data-admin-settings>Settings</button>
          <button class="admin-button admin-button-danger" type="button" data-admin-logout>Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Order Update Health</h2>
              <p class="admin-panel-note">Summary counters for email reachability and update metadata across the current order database.</p>
            </div>
            <div class="admin-debug-actions">
              <a class="admin-button admin-button-soft" href="/GirffoN/admin-orders.php">Back to Orders Admin</a>
              <a class="admin-button admin-button-soft" href="/GirffoN/admin-dashboard.php">Dashboard</a>
              <a class="admin-button" href="/GirffoN/backend/admin/order-updates-debug.php">Check Order Updates</a>
            </div>
          </div>

          <div class="admin-debug-grid">
            <article class="admin-debug-stat"><span>Total Orders</span><strong><?php echo $escapeDebug($summary['total_orders'] ?? 0); ?></strong></article>
            <article class="admin-debug-stat"><span>Orders With Email</span><strong><?php echo $escapeDebug($summary['orders_with_email'] ?? 0); ?></strong></article>
            <article class="admin-debug-stat"><span>Orders With Tracking</span><strong><?php echo $escapeDebug($summary['orders_with_tracking'] ?? 0); ?></strong></article>
            <article class="admin-debug-stat"><span>Order Updates Enabled</span><strong><?php echo $escapeDebug($summary['order_update_email_enabled_count'] ?? 0); ?></strong></article>
          </div>
        </article>

        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Orders Ready For Updates</h2>
              <p class="admin-panel-note">Quick audit of customer email availability, tracking state, and order update eligibility.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Customer</th>
                  <th>Status</th>
                  <th>Tracking</th>
                  <th>Courier</th>
                  <th>Estimated Delivery</th>
                  <th>Order Updates</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($recentOrders): ?>
                  <?php foreach ($recentOrders as $order): ?>
                    <?php $enabled = girffonAdminOrderUpdateEmailEnabled($pdo, $order); ?>
                    <tr>
                      <td><strong><?php echo $escapeDebug($order['order_number'] ?? ''); ?></strong></td>
                      <td>
                        <?php echo $escapeDebug($order['customer_name'] ?? '-'); ?><br>
                        <span class="admin-debug-note"><?php echo $escapeDebug($order['customer_email'] ?? '-'); ?></span>
                      </td>
                      <td>
                        <strong><?php echo $formatDebugLabel($order['order_status'] ?? ''); ?></strong><br>
                        <span class="admin-debug-note">Payment: <?php echo $formatDebugLabel($order['payment_status'] ?? ''); ?></span>
                      </td>
                      <td><?php echo $escapeDebug(($order['tracking_code'] ?? '') !== '' ? $order['tracking_code'] : '-'); ?></td>
                      <td><?php echo $escapeDebug(($order['courier_name'] ?? '') !== '' ? $order['courier_name'] : '-'); ?></td>
                      <td><?php echo $formatDebugDate($order['estimated_delivery_date'] ?? ''); ?></td>
                      <td><span class="admin-debug-badge<?php echo $enabled ? '' : ' is-off'; ?>"><?php echo $escapeDebug($enabled ? 'Enabled' : 'Disabled'); ?></span></td>
                      <td><?php echo $formatDebugDate($order['created_at'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="admin-empty">No orders are available yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>

        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Update Logs</h2>
              <p class="admin-panel-note">Latest saved updates and email delivery outcomes from the order update endpoint.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Email</th>
                  <th>Old Status</th>
                  <th>New Status</th>
                  <th>Payment</th>
                  <th>Email Status</th>
                  <th>Transport</th>
                  <th>Error</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($recentLogs): ?>
                  <?php foreach ($recentLogs as $log): ?>
                    <tr>
                      <td><strong>#<?php echo $escapeDebug($log['order_id'] ?? 0); ?></strong></td>
                      <td><?php echo $escapeDebug($log['customer_email'] ?? '-'); ?></td>
                      <td><?php echo $formatDebugLabel($log['old_status'] ?? ''); ?></td>
                      <td><?php echo $formatDebugLabel($log['new_status'] ?? ''); ?></td>
                      <td><?php echo $formatDebugLabel($log['payment_status'] ?? ''); ?></td>
                      <td><?php echo $escapeDebug(($log['email_status'] ?? '') !== '' ? $log['email_status'] : '-'); ?></td>
                      <td><?php echo $escapeDebug(($log['transport'] ?? '') !== '' ? $log['transport'] : '-'); ?></td>
                      <td><?php echo $escapeDebug(($log['error_message'] ?? '') !== '' ? $log['error_message'] : '-'); ?></td>
                      <td><?php echo $formatDebugDate($log['created_at'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="admin-empty">No order update logs yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="/GirffoN/JS/admin-girffon.js?v=20260505r5"></script>
</body>
</html>