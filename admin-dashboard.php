<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/orders-data.php";
require_once __DIR__ . "/backend/admin/invoices-data.php";
require_once __DIR__ . "/backend/admin/products-data.php";
require_once __DIR__ . "/backend/admin/messages-data.php";
require_once __DIR__ . "/backend/admin/dashboard-data.php";

$adminProductCount = girffonAdminCountProducts($pdo);
$adminMemberCount = girffonAdminCountMembers($pdo);
$adminOrderCount = girffonAdminCountOrders($pdo);
$adminInvoiceCount = girffonAdminCountInvoices($pdo);
$adminUnreadMessageCount = girffonAdminCountUnreadMessages($pdo);
$adminRecentMembers = girffonAdminFetchRecentMembers($pdo, 5);
$adminRecentProducts = girffonAdminFetchLowStockProducts($pdo, 4);
$adminRecentMessages = girffonAdminFetchMessages($pdo, 4);
$adminRecentOrders = girffonAdminFetchOrders($pdo, 4);
$adminTodayOrders = girffonAdminFetchTodayOrders($pdo, 4);
$adminRecentInvoices = girffonAdminFetchInvoices($pdo, 4);
$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
girffonAdminTrackDashboardVisit($adminCurrentId, $adminCurrentUsername);
$adminOrdersTodayCount = girffonAdminCountOrdersToday($pdo);
$adminRevenueThisMonth = girffonAdminRevenueThisMonth($pdo);
$adminLastLoginTime = girffonAdminFetchLastLoginTime($adminCurrentId, $adminCurrentUsername);
$adminPeriodStats = girffonAdminFetchPeriodStats($pdo);
$adminLoginActivity = girffonAdminFetchRecentLoginActivity(6);
$adminActiveAdmins = girffonAdminFetchActiveAdmins(30, 2);
$adminVisitorAnalytics = girffonAdminFetchVisitorAnalytics();
$adminCurrentAdminProfile = girffonAdminFetchAdminProfile($pdo, $adminCurrentId);
$adminWeatherCity = trim((string) ($adminCurrentAdminProfile['city'] ?? '')) ?: 'Milan';
$adminWeatherCountry = trim((string) ($adminCurrentAdminProfile['country'] ?? '')) ?: 'Italy';
$escapeAdminDashboard = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$formatAdminDashboardCurrency = static function ($value) {
  return "EUR " . number_format((float) $value, 2, ".", ",");
};
$formatAdminDashboardLabel = static function ($value) use ($escapeAdminDashboard) {
  return $escapeAdminDashboard(ucwords(str_replace("_", " ", (string) $value)));
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Dashboard</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r1">
</head>
<body class="admin-page" data-admin-page="dashboard" data-admin-dashboard-source="database" data-admin-orders-source="database" data-admin-invoices-source="database" data-admin-weather-city="<?php echo $escapeAdminDashboard($adminWeatherCity); ?>" data-admin-weather-country="<?php echo $escapeAdminDashboard($adminWeatherCountry); ?>">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Admin panel for products, orders, invoices, and customer messages.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link is-active" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link is-active" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Mode</strong>
          <p class="admin-panel-note">Orders and invoices are loaded from the database.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Dashboard</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-card-grid" aria-label="Dashboard totals">
        <article class="admin-stat-card">
          <span>Total Products</span>
          <strong id="adminTotalProducts"><?php echo $escapeAdminDashboard($adminProductCount); ?></strong>
          <p class="admin-status">Products currently available in the database.</p>
        </article>
        <article class="admin-stat-card">
          <span>Total Members</span>
          <strong id="adminTotalMembers"><?php echo $escapeAdminDashboard($adminMemberCount); ?></strong>
          <p class="admin-status">Registered customer accounts in the database.</p>
        </article>
        <article class="admin-stat-card">
          <span>Total Orders</span>
          <strong id="adminTotalOrders"><?php echo $escapeAdminDashboard($adminOrderCount); ?></strong>
          <p class="admin-status">Orders currently available in the database.</p>
        </article>
        <article class="admin-stat-card">
          <span>Total Invoices</span>
          <strong id="adminTotalInvoices"><?php echo $escapeAdminDashboard($adminInvoiceCount); ?></strong>
          <p class="admin-status">Invoices currently available in the database.</p>
        </article>
        <article class="admin-stat-card">
          <span>Unread Messages</span>
          <strong id="adminUnreadMessages"><?php echo $escapeAdminDashboard($adminUnreadMessageCount); ?></strong>
          <p class="admin-status">Customer messages waiting for review.</p>
        </article>
        <article class="admin-stat-card">
          <span>Orders Today</span>
          <strong id="adminOrdersTodayCount"><?php echo $escapeAdminDashboard($adminOrdersTodayCount); ?></strong>
          <p class="admin-status">New orders created since midnight.</p>
        </article>
        <article class="admin-stat-card">
          <span>Revenue This Month</span>
          <strong id="adminRevenueThisMonth"><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($adminRevenueThisMonth)); ?></strong>
          <p class="admin-status">Gross order revenue booked this month.</p>
        </article>
        <article class="admin-stat-card">
          <span>Last Login Time</span>
          <strong id="adminLastLoginTime"><?php echo $escapeAdminDashboard($adminLastLoginTime !== '' ? date('Y-m-d H:i', strtotime($adminLastLoginTime)) : 'No data'); ?></strong>
          <p class="admin-status">Most recent admin login captured for this account.</p>
        </article>
      </section>

      <section class="admin-content-grid">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Orders</h2>
              <p class="admin-panel-note">Quick order status summary.</p>
            </div>
          </div>
          <div id="adminRecentOrders" class="admin-mini-list">
            <?php if ($adminRecentOrders): ?>
              <?php foreach ($adminRecentOrders as $order): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard(($order["order_number"] ?? "") . " - " . ($order["customer_name"] ?? "")); ?></span><strong><?php echo $formatAdminDashboardLabel($order["order_status"] ?? "new"); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No orders yet.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Latest Members</h2>
              <p class="admin-panel-note">Newest registered customer accounts.</p>
            </div>
          </div>
          <div id="adminRecentMembers" class="admin-mini-list">
            <?php if ($adminRecentMembers): ?>
              <?php foreach ($adminRecentMembers as $member): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard(trim((string) (($member["first_name"] ?? "") . " " . ($member["last_name"] ?? ""))) ?: ($member["email"] ?? "")); ?><br><small><?php echo $escapeAdminDashboard($member["email"] ?? ""); ?></small></span><strong><?php echo $escapeAdminDashboard($member["created_at"] ?? ""); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No members yet.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Messages</h2>
              <p class="admin-panel-note">Latest customer contact activity.</p>
            </div>
          </div>
          <div id="adminRecentMessages" class="admin-mini-list">
            <?php if ($adminRecentMessages): ?>
              <?php foreach ($adminRecentMessages as $message): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard($message["customer_name"] ?? ""); ?></span><strong><?php echo $formatAdminDashboardLabel($message["status"] ?? "unread"); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No messages yet.</p>
            <?php endif; ?>
          </div>
        </article>
      </section>

      <section class="admin-dashboard-subgrid">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Low Stock Products</h2>
              <p class="admin-panel-note">Products that need restocking soon.</p>
            </div>
          </div>
          <div id="adminLowStockProducts" class="admin-mini-list">
            <?php if ($adminRecentProducts): ?>
              <?php foreach ($adminRecentProducts as $product): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard($product["name"] ?? ""); ?></span><strong><?php echo $escapeAdminDashboard(($product["stock"] ?? 0) . " pcs"); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No low stock products right now.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Today Orders</h2>
              <p class="admin-panel-note">Orders created during the current day.</p>
            </div>
          </div>
          <div id="adminTodayOrders" class="admin-mini-list">
            <?php if ($adminTodayOrders): ?>
              <?php foreach ($adminTodayOrders as $order): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard($order["customer_name"] ?? ""); ?></span><strong><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($order["total"] ?? 0)); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No orders were created today.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Invoices</h2>
              <p class="admin-panel-note">Latest invoice activity from the admin panel.</p>
            </div>
          </div>
          <div id="adminRecentInvoices" class="admin-mini-list">
            <?php if ($adminRecentInvoices): ?>
              <?php foreach ($adminRecentInvoices as $invoice): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard(($invoice["invoice_number"] ?? "") . " - " . ($invoice["order_number"] ?? "")); ?></span><strong><?php echo $formatAdminDashboardLabel($invoice["invoice_status"] ?? "pending"); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No invoices yet.</p>
            <?php endif; ?>
          </div>
        </article>
      </section>

      <section class="admin-dashboard-extras" aria-label="Dashboard insights">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Login Activity</h2>
              <p class="admin-panel-note">Who signed in and when.</p>
            </div>
          </div>
          <div class="admin-mini-list">
            <?php if ($adminLoginActivity): ?>
              <?php foreach ($adminLoginActivity as $activity): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard($activity['username'] ?? 'GirffoN Admin'); ?><br><small><?php echo $escapeAdminDashboard($activity['ip'] ?? ''); ?></small></span><strong><?php echo $escapeAdminDashboard(date('Y-m-d H:i', strtotime((string) ($activity['created_at'] ?? 'now')))); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No login activity captured yet.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Daily / Monthly / Yearly Stats</h2>
              <p class="admin-panel-note">Orders, revenue, invoices, and new members by period.</p>
            </div>
          </div>
          <div class="admin-period-stats">
            <?php foreach ($adminPeriodStats as $period): ?>
              <div class="admin-period-card">
                <span><?php echo $escapeAdminDashboard($period['label'] ?? 'Period'); ?></span>
                <strong><?php echo $escapeAdminDashboard((string) ($period['orders'] ?? 0)); ?> orders</strong>
                <p><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($period['revenue'] ?? 0)); ?> revenue</p>
                <small><?php echo $escapeAdminDashboard((string) ($period['invoices'] ?? 0)); ?> invoices, <?php echo $escapeAdminDashboard((string) ($period['members'] ?? 0)); ?> new members</small>
              </div>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="admin-panel admin-weather-widget" data-admin-weather-widget>
          <div class="admin-panel-head">
            <div>
              <h2>Weather Widget</h2>
              <p class="admin-panel-note">Live weather for the current admin city.</p>
            </div>
          </div>
          <div class="admin-weather-shell">
            <div class="admin-weather-main">
              <strong data-admin-weather-temp>--</strong>
              <span data-admin-weather-condition>Loading live weather...</span>
            </div>
            <div class="admin-weather-meta">
              <div><span>City</span><strong data-admin-weather-city><?php echo $escapeAdminDashboard($adminWeatherCity); ?></strong></div>
              <div><span>Country</span><strong><?php echo $escapeAdminDashboard($adminWeatherCountry); ?></strong></div>
              <div><span>Wind</span><strong data-admin-weather-wind>--</strong></div>
            </div>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Active Admins</h2>
              <p class="admin-panel-note">Admins active in the last 30 minutes.</p>
            </div>
          </div>
          <div class="admin-mini-list">
            <?php if ($adminActiveAdmins): ?>
              <?php foreach ($adminActiveAdmins as $activeAdmin): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard($activeAdmin['username'] ?? 'GirffoN Admin'); ?></span><strong><?php echo $escapeAdminDashboard(date('H:i', strtotime((string) ($activeAdmin['created_at'] ?? 'now')))); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No admins are marked online right now.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Visitors Analytics</h2>
              <p class="admin-panel-note">Dashboard visits tracked on this admin panel.</p>
            </div>
          </div>
          <div class="admin-analytics-grid">
            <div class="admin-analytics-card"><span>Today</span><strong><?php echo $escapeAdminDashboard($adminVisitorAnalytics['today'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>This Month</span><strong><?php echo $escapeAdminDashboard($adminVisitorAnalytics['month'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>This Year</span><strong><?php echo $escapeAdminDashboard($adminVisitorAnalytics['year'] ?? 0); ?></strong></div>
          </div>
          <div class="admin-mini-list admin-mini-list-compact">
            <?php if (!empty($adminVisitorAnalytics['recent'])): ?>
              <?php foreach ($adminVisitorAnalytics['recent'] as $visit): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard($visit['username'] ?? 'Visitor'); ?></span><strong><?php echo $escapeAdminDashboard(date('m-d H:i', strtotime((string) ($visit['created_at'] ?? 'now')))); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No visit analytics yet.</p>
            <?php endif; ?>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r1"></script>
</body>
</html>
