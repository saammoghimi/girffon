<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/orders-data.php";
require_once __DIR__ . "/backend/admin/invoices-data.php";
require_once __DIR__ . "/backend/admin/products-data.php";
require_once __DIR__ . "/backend/admin/messages-data.php";
require_once __DIR__ . "/backend/admin/dashboard-data.php";
require_once __DIR__ . "/backend/admin/custom-design-orders-data.php";

$adminDashboardSettingsFile = __DIR__ . "/backend/admin/dashboard-settings-data.php";
if (is_file($adminDashboardSettingsFile)) {
  require_once $adminDashboardSettingsFile;
}

$adminProductCount = girffonAdminCountProducts($pdo);
$adminMemberCount = girffonAdminCountMembers($pdo);
$adminGiftCardsSoldCount = girffonAdminCountSoldGiftCards($pdo);
$adminOrderCount = girffonAdminCountOrders($pdo);
$adminInvoiceCount = girffonAdminCountInvoices($pdo);
$adminUnreadMessageCount = girffonAdminCountUnreadMessages($pdo);
$adminRecentMembers = girffonAdminFetchRecentMembers($pdo, 5);
$adminRecentProducts = girffonAdminFetchLowStockProducts($pdo, 4);
$adminRecentMessages = girffonAdminFetchMessages($pdo, 4);
$adminRecentOrders = girffonAdminFetchOrders($pdo, 4);
$adminTodayOrders = girffonAdminFetchTodayOrders($pdo, 4);
$adminRecentInvoices = girffonAdminFetchInvoices($pdo, 4);
$adminPaidCustomDesignCount = girffonAdminCountCustomDesignOrders($pdo, ['payment_status' => 'paid']);
$adminPendingCustomDesignCount = girffonAdminCountCustomDesignOrders($pdo, ['statuses' => ['pending_payment']]);
$adminCustomDesignRevenueThisMonth = girffonAdminSumCustomDesignRevenue($pdo, [
  'payment_status' => 'paid',
  'paid_after' => girffonAdminDashboardRomeNow()->modify('first day of this month')->setTime(0, 0, 0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
]);
$adminRecentPaidCustomDesignOrders = array_values(array_filter(
  girffonAdminFetchCustomDesignOrderSummaries($pdo, 4, ['payment_status' => 'paid']),
  static function (array $row): bool {
    return empty($row['is_demo']);
  }
));
$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
girffonAdminTrackDashboardVisit($adminCurrentId, $adminCurrentUsername);
$adminOrdersTodayCount = girffonAdminCountOrdersToday($pdo);
$adminRevenueThisMonth = girffonAdminRevenueThisMonth($pdo);
$adminLastLoginTime = girffonAdminFetchLastLoginTime($adminCurrentId, $adminCurrentUsername);
$adminPeriodStats = girffonAdminFetchPeriodStats($pdo);
$adminLoginActivity = girffonAdminFetchRecentLoginActivity(6);
$adminActiveAdmins = girffonAdminFetchActiveAdmins(30, 2);
$adminVisitorAnalytics = girffonAdminFetchVisitorAnalytics($pdo, ['range' => '30days']);
$adminCurrentAdminProfile = girffonAdminFetchAdminProfile($pdo, $adminCurrentId);
$adminWeatherCity = trim((string) ($adminCurrentAdminProfile['city'] ?? '')) ?: 'Milan';
$adminWeatherCountry = trim((string) ($adminCurrentAdminProfile['country'] ?? '')) ?: 'Italy';
$adminAnalyticsExplorer = girffonAdminFetchAnalyticsExplorer($pdo);
$adminRomeCurrentTime = girffonAdminDashboardRomeCurrent('d M Y · H:i');
$adminRomeCurrentYear = (int) girffonAdminDashboardRomeNow()->format('Y');
$adminRomeCurrentMonth = (int) girffonAdminDashboardRomeNow()->format('n');
$adminDashboardPreferences = [
  'show_summary_cards' => true,
  'show_daily_stats' => true,
  'show_monthly_stats' => true,
  'show_yearly_stats' => true,
  'show_recent_activity' => true,
  'show_login_activity' => true,
  'show_analytics_explorer' => true,
  'show_weather_widget' => true,
  'show_world_clock' => true,
  'show_active_admins' => true,
  'show_visitor_analytics' => true,
];

if (function_exists('girffonAdminFetchDashboardPreferences')) {
  $adminDashboardPreferences = girffonAdminFetchDashboardPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}
$showAdminSummaryCards = !empty($adminDashboardPreferences['show_summary_cards']);
$showAdminDailyStats = !empty($adminDashboardPreferences['show_daily_stats']);
$showAdminMonthlyStats = !empty($adminDashboardPreferences['show_monthly_stats']);
$showAdminYearlyStats = !empty($adminDashboardPreferences['show_yearly_stats']);
$showAdminPeriodStats = $showAdminDailyStats || $showAdminMonthlyStats || $showAdminYearlyStats;
$showAdminRecentActivity = !empty($adminDashboardPreferences['show_recent_activity']);
$showAdminLoginActivity = !empty($adminDashboardPreferences['show_login_activity']);
$showAdminAnalyticsExplorer = !empty($adminDashboardPreferences['show_analytics_explorer']);
$showAdminWeatherWidget = !empty($adminDashboardPreferences['show_weather_widget']);
$showAdminWorldClock = !empty($adminDashboardPreferences['show_world_clock']);
$showAdminActiveAdmins = !empty($adminDashboardPreferences['show_active_admins']);
$showAdminVisitorAnalytics = !empty($adminDashboardPreferences['show_visitor_analytics']);
$showAdminExtrasSection = $showAdminLoginActivity || $showAdminAnalyticsExplorer || $showAdminWeatherWidget || $showAdminWorldClock || $showAdminActiveAdmins || $showAdminVisitorAnalytics;
$escapeAdminDashboard = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$escapeAdminDashboardJson = static function ($value) {
  return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8");
};
$formatAdminDashboardCurrency = static function ($value) {
  return "EUR " . number_format((float) $value, 2, ".", ",");
};
$formatAdminDashboardLabel = static function ($value) use ($escapeAdminDashboard) {
  return $escapeAdminDashboard(ucwords(str_replace("_", " ", (string) $value)));
};
$formatAdminDashboardPercent = static function ($value) {
  return number_format((float) $value, 1, ".", ",") . "%";
};
$formatAdminDashboardDate = static function ($value) {
  return girffonAdminDashboardFormatRome((string) $value, 'Y-m-d');
};
$formatAdminDashboardDateTime = static function ($value) {
  return girffonAdminDashboardFormatRome((string) $value, 'd M Y · H:i');
};
$formatAdminDashboardCustomDesignDate = static function ($value) use ($escapeAdminDashboard) {
  return $escapeAdminDashboard(girffonAdminCustomDesignFormatRomeDate((string) $value));
};
$formatAdminDashboardPreview = static function ($value, $fallback, $limit = 88) {
  $text = trim(preg_replace('/\s+/', ' ', (string) $value));
  if ($text === '') {
    $text = $fallback;
  }
  if (strlen($text) > $limit) {
    $text = rtrim(substr($text, 0, $limit - 3)) . "...";
  }
  return $text;
};
$computeAdminAnalyticsMax = static function (array $rows): int {
  $max = 0;
  foreach ($rows as $row) {
    $max = max($max, (int) ($row['count'] ?? 0));
  }
  return max(1, $max);
};
$adminVisitorCountries = is_array($adminVisitorAnalytics['countries'] ?? null) ? $adminVisitorAnalytics['countries'] : [];
$adminVisitorPages = is_array($adminVisitorAnalytics['pages'] ?? null) ? $adminVisitorAnalytics['pages'] : [];
$adminVisitorSources = is_array($adminVisitorAnalytics['sources'] ?? null) ? $adminVisitorAnalytics['sources'] : [];
$adminVisitorBrowsers = is_array($adminVisitorAnalytics['browsers'] ?? null) ? $adminVisitorAnalytics['browsers'] : [];
$adminVisitorDevices = is_array($adminVisitorAnalytics['devices'] ?? null) ? $adminVisitorAnalytics['devices'] : [];
$adminVisitorCountryMax = $computeAdminAnalyticsMax($adminVisitorCountries);
$adminVisitorSourceMax = $computeAdminAnalyticsMax($adminVisitorSources);
$adminVisitorBrowserMax = $computeAdminAnalyticsMax($adminVisitorBrowsers);
$adminVisitorDeviceMax = $computeAdminAnalyticsMax($adminVisitorDevices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Dashboard</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260804r12">
</head>
<body class="admin-page" data-admin-page="dashboard" data-admin-dashboard-source="database" data-admin-orders-source="database" data-admin-invoices-source="database" data-admin-weather-city="<?php echo $escapeAdminDashboard($adminWeatherCity); ?>" data-admin-weather-country="<?php echo $escapeAdminDashboard($adminWeatherCountry); ?>" data-admin-analytics="<?php echo $escapeAdminDashboardJson($adminAnalyticsExplorer); ?>">
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
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
        <a class="admin-nav-link" href="admin-gift-cards.php" aria-label="Gift Cards" title="Gift Cards">10. Gift Cards</a>
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
          <a class="admin-button admin-button-soft admin-view-shop-button" href="index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="dashboard-setting.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <?php if ($showAdminSummaryCards): ?>
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
          <span>Gift Cards Sold</span>
          <strong id="adminGiftCardsSold"><?php echo $escapeAdminDashboard($adminGiftCardsSoldCount); ?></strong>
          <p class="admin-status">Gift cards successfully paid and issued.</p>
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
          <p class="admin-status">Combined new orders from shop and custom design since Rome midnight.</p>
        </article>
        <article class="admin-stat-card">
          <span>Revenue This Month</span>
          <strong id="adminRevenueThisMonth"><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($adminRevenueThisMonth)); ?></strong>
          <p class="admin-status">Combined paid revenue from shop and custom design this Rome month.</p>
        </article>
        <article class="admin-stat-card">
          <span>Custom Design Paid</span>
          <strong id="adminPaidCustomDesignOrders"><?php echo $escapeAdminDashboard($adminPaidCustomDesignCount); ?></strong>
          <p class="admin-status">Paid custom design orders now waiting in review.</p>
        </article>
        <article class="admin-stat-card">
          <span>Custom Design Revenue</span>
          <strong id="adminCustomDesignRevenueThisMonth"><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($adminCustomDesignRevenueThisMonth)); ?></strong>
          <p class="admin-status">Paid custom design revenue booked this month.</p>
        </article>
        <article class="admin-stat-card">
          <span>Last Login Time</span>
          <strong id="adminLastLoginTime"><?php echo $escapeAdminDashboard($adminLastLoginTime !== '' ? $formatAdminDashboardDateTime($adminLastLoginTime) : 'No data'); ?></strong>
          <p class="admin-status">Exact latest admin sign-in recorded for this account.</p>
        </article>
        <article class="admin-stat-card">
          <span>Rome Time</span>
          <strong id="adminRomeCurrentTime"><?php echo $escapeAdminDashboard($adminRomeCurrentTime); ?></strong>
          <p class="admin-status">Current dashboard reference time in Europe/Rome.</p>
        </article>
      </section>
      <?php endif; ?>

      <?php if ($showAdminPeriodStats): ?>
      <section class="admin-content-grid">
        <?php foreach ($adminPeriodStats as $periodKey => $periodStat): ?>
        <?php
        $isVisiblePeriod =
          ($periodKey === 'daily' && $showAdminDailyStats) ||
          ($periodKey === 'monthly' && $showAdminMonthlyStats) ||
          ($periodKey === 'yearly' && $showAdminYearlyStats);
        if (!$isVisiblePeriod) {
          continue;
        }
        ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2><?php echo $escapeAdminDashboard($periodStat['label'] ?? 'Stats'); ?></h2>
              <p class="admin-panel-note">Exact Rome-based totals with shop and custom design combined.</p>
            </div>
          </div>
          <div class="admin-analytics-summary-grid">
            <div class="admin-analytics-summary-card"><span>Orders</span><strong><?php echo $escapeAdminDashboard($periodStat['orders'] ?? 0); ?></strong></div>
            <div class="admin-analytics-summary-card"><span>Revenue</span><strong><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($periodStat['revenue'] ?? 0)); ?></strong></div>
            <div class="admin-analytics-summary-card"><span>Invoices</span><strong><?php echo $escapeAdminDashboard($periodStat['invoices'] ?? 0); ?></strong></div>
            <div class="admin-analytics-summary-card"><span>Members</span><strong><?php echo $escapeAdminDashboard($periodStat['members'] ?? 0); ?></strong></div>
            <div class="admin-analytics-summary-card"><span>Shop Orders</span><strong><?php echo $escapeAdminDashboard($periodStat['shop_orders'] ?? 0); ?></strong></div>
            <div class="admin-analytics-summary-card"><span>Custom Orders</span><strong><?php echo $escapeAdminDashboard($periodStat['custom_design_orders'] ?? 0); ?></strong></div>
            <div class="admin-analytics-summary-card"><span>Shop Revenue</span><strong><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($periodStat['shop_revenue'] ?? 0)); ?></strong></div>
            <div class="admin-analytics-summary-card"><span>Custom Revenue</span><strong><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($periodStat['custom_design_revenue'] ?? 0)); ?></strong></div>
          </div>
        </article>
        <?php endforeach; ?>
      </section>
      <?php endif; ?>

      <?php if ($showAdminRecentActivity): ?>
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
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard(($order["order_number"] ?? "") . " - " . ($order["customer_name"] ?? "")); ?><br><small><?php echo $escapeAdminDashboard($formatAdminDashboardDateTime($order["created_at"] ?? "")); ?></small></span><strong><?php echo $formatAdminDashboardLabel($order["order_status"] ?? "new"); ?></strong></div>
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
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard(trim((string) (($member["first_name"] ?? "") . " " . ($member["last_name"] ?? ""))) ?: ($member["email"] ?? "")); ?><br><small><?php echo $escapeAdminDashboard($member["email"] ?? ""); ?></small></span><strong><?php echo $escapeAdminDashboard($formatAdminDashboardDateTime($member["created_at"] ?? "")); ?></strong></div>
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
                <?php
                $messageName = trim((string) ($message["name"] ?? $message["customer_name"] ?? ""));
                $messageEmail = trim((string) ($message["email"] ?? ""));
                $messageSubject = $formatAdminDashboardPreview($message["subject"] ?? "", "No subject", 46);
                $messagePreview = $formatAdminDashboardPreview($message["message"] ?? "", "No message preview available.", 84);
                $messageStatus = trim((string) ($message["status"] ?? "")) ?: "unread";
                $messageDate = $formatAdminDashboardDate($message["created_at"] ?? "");
                $messageDisplayName = $messageName !== '' ? $messageName : ($messageEmail !== '' ? $messageEmail : 'Unknown customer');
                ?>
                <div class="admin-mini-item">
                  <span><?php echo $escapeAdminDashboard($messageDisplayName); ?><br><small><?php echo $escapeAdminDashboard($messageSubject); ?></small><br><small><?php echo $escapeAdminDashboard($messagePreview); ?></small></span>
                  <strong><?php echo $formatAdminDashboardLabel($messageStatus); ?><br><small><?php echo $escapeAdminDashboard($messageDate); ?></small></strong>
                </div>
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
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard($order["customer_name"] ?? ""); ?><br><small><?php echo $escapeAdminDashboard($formatAdminDashboardDateTime($order["created_at"] ?? "")); ?></small></span><strong><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($order["total"] ?? 0)); ?></strong></div>
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
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard(($invoice["invoice_number"] ?? "") . " - " . ($invoice["order_number"] ?? "")); ?><br><small><?php echo $escapeAdminDashboard($formatAdminDashboardDateTime($invoice["created_at"] ?? "")); ?></small></span><strong><?php echo $formatAdminDashboardLabel($invoice["invoice_status"] ?? "pending"); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No invoices yet.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Paid Custom Design Orders</h2>
              <p class="admin-panel-note">Custom design payments that were completed and moved into the paid review queue.</p>
            </div>
          </div>
          <div class="admin-mini-list">
            <?php if ($adminRecentPaidCustomDesignOrders): ?>
              <?php foreach ($adminRecentPaidCustomDesignOrders as $customOrder): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard(($customOrder['order_code'] ?? '') . ' - ' . ($customOrder['customer_name'] ?? '')); ?><br><small><?php echo $escapeAdminDashboard($customOrder['product_name'] ?? 'Custom Product'); ?></small><br><small><?php echo $formatAdminDashboardCustomDesignDate($customOrder['paid_at'] ?? ($customOrder['created_at'] ?? '')); ?></small></span><strong><?php echo $escapeAdminDashboard($formatAdminDashboardCurrency($customOrder['order_total'] ?? 0)); ?><br><small><?php echo $formatAdminDashboardLabel($customOrder['status'] ?? 'paid_review'); ?></small></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No paid custom design orders yet.</p>
            <?php endif; ?>
          </div>
        </article>
      </section>
      <?php endif; ?>

      <?php if ($showAdminExtrasSection): ?>
      <section class="admin-dashboard-extras" aria-label="Dashboard insights">
        <?php if ($showAdminLoginActivity): ?>
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
                <div class="admin-mini-item"><span><?php echo $escapeAdminDashboard($activity['username'] ?? 'GirffoN Admin'); ?><br><small><?php echo $escapeAdminDashboard($activity['ip'] ?? ''); ?></small></span><strong><?php echo $escapeAdminDashboard($formatAdminDashboardDateTime($activity['created_at'] ?? '')); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No login activity captured yet.</p>
            <?php endif; ?>
          </div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminAnalyticsExplorer): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Daily / Monthly / Yearly Stats</h2>
              <p class="admin-panel-note">Professional exact analytics with selectable year and month.</p>
            </div>
          </div>
          <div class="admin-analytics-explorer" data-admin-analytics-explorer>
            <div class="admin-analytics-controls">
              <div class="admin-analytics-toggle-group" role="tablist" aria-label="Analytics period">
                <button type="button" class="admin-chip is-active" data-analytics-period="daily">Daily</button>
                <button type="button" class="admin-chip" data-analytics-period="monthly">Monthly</button>
                <button type="button" class="admin-chip" data-analytics-period="yearly">Yearly</button>
              </div>
              <div class="admin-analytics-filter-group" data-analytics-year-group>
                <?php foreach (($adminAnalyticsExplorer['years'] ?? []) as $year): ?>
                  <button type="button" class="admin-chip <?php echo ((int) $year === (int) ($adminAnalyticsExplorer['selectedYear'] ?? $adminRomeCurrentYear)) ? 'is-active' : ''; ?>" data-analytics-year="<?php echo $escapeAdminDashboard($year); ?>"><?php echo $escapeAdminDashboard($year); ?></button>
                <?php endforeach; ?>
              </div>
              <label class="admin-inline-select" data-analytics-month-wrap>
                <span>Month</span>
                <select data-analytics-month>
                  <?php for ($month = 1; $month <= 12; $month++): ?>
                    <option value="<?php echo $escapeAdminDashboard($month); ?>" <?php echo ((int) $month === (int) ($adminAnalyticsExplorer['selectedMonth'] ?? $adminRomeCurrentMonth)) ? 'selected' : ''; ?>><?php echo $escapeAdminDashboard(date('F', mktime(0, 0, 0, $month, 1))); ?></option>
                  <?php endfor; ?>
                </select>
              </label>
              <div class="admin-analytics-action-row">
                <button type="button" class="admin-button admin-button-soft" data-analytics-download-pdf>Download PDF</button>
              </div>
            </div>
            <div class="admin-analytics-summary-grid" data-analytics-summary></div>
            <div class="admin-analytics-chart" data-analytics-chart></div>
          </div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminWeatherWidget): ?>
        <article class="admin-panel admin-weather-widget" data-admin-weather-widget>
          <div class="admin-panel-head">
            <div>
              <h2>Weather Widget</h2>
              <p class="admin-panel-note">Live weather with selectable country, region, and city.</p>
            </div>
          </div>
          <div class="admin-weather-shell">
            <div class="admin-weather-controls">
              <label class="admin-inline-select">
                <span>Region</span>
                <select data-admin-weather-region>
                  <option value="Italy">Italy</option>
                  <option value="Iran">Iran</option>
                  <option value="United States">United States</option>
                  <option value="France">France</option>
                  <option value="Germany">Germany</option>
                  <option value="Europe">Europe</option>
                  <option value="Americas">Americas</option>
                  <option value="Asia">Asia</option>
                  <option value="World">World</option>
                </select>
              </label>
              <label class="admin-inline-input">
                <span>City</span>
                <input type="text" data-admin-weather-city-input value="<?php echo $escapeAdminDashboard($adminWeatherCity); ?>" placeholder="Enter city">
              </label>
              <div class="admin-weather-actions">
                <button type="button" class="admin-button admin-button-soft" data-admin-weather-apply>Apply</button>
                <button type="button" class="admin-button admin-button-soft" data-admin-weather-clear>Clear</button>
              </div>
            </div>
            <div class="admin-weather-main">
              <div class="admin-weather-condition-badge" data-admin-weather-badge data-weather-kind="neutral">
                <span class="admin-weather-condition-icon" data-admin-weather-icon aria-hidden="true">--</span>
                <span class="admin-weather-condition-label" data-admin-weather-label>Loading</span>
              </div>
              <strong data-admin-weather-temp>--</strong>
              <span data-admin-weather-condition>Loading live weather...</span>
            </div>
            <div class="admin-weather-meta">
              <div><span>City</span><strong data-admin-weather-city><?php echo $escapeAdminDashboard($adminWeatherCity); ?></strong></div>
              <div><span>Country</span><strong data-admin-weather-country><?php echo $escapeAdminDashboard($adminWeatherCountry); ?></strong></div>
              <div><span>Wind</span><strong data-admin-weather-wind>--</strong></div>
            </div>
            <div class="admin-weather-forecast" data-admin-weather-forecast>
              <div class="admin-weather-forecast-card" data-admin-weather-forecast-item>
                <span class="admin-weather-forecast-day">Today</span>
                <span class="admin-weather-forecast-icon" aria-hidden="true">--</span>
                <strong class="admin-weather-forecast-temp">-- / --</strong>
                <span class="admin-weather-forecast-label">Loading</span>
              </div>
              <div class="admin-weather-forecast-card" data-admin-weather-forecast-item>
                <span class="admin-weather-forecast-day">Tomorrow</span>
                <span class="admin-weather-forecast-icon" aria-hidden="true">--</span>
                <strong class="admin-weather-forecast-temp">-- / --</strong>
                <span class="admin-weather-forecast-label">Loading</span>
              </div>
              <div class="admin-weather-forecast-card" data-admin-weather-forecast-item>
                <span class="admin-weather-forecast-day">Day After</span>
                <span class="admin-weather-forecast-icon" aria-hidden="true">--</span>
                <strong class="admin-weather-forecast-temp">-- / --</strong>
                <span class="admin-weather-forecast-label">Loading</span>
              </div>
            </div>
          </div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminWorldClock): ?>
        <article class="admin-panel admin-world-clock-widget" data-admin-world-clock-widget>
          <div class="admin-panel-head">
            <div>
              <h2>World Clock</h2>
              <p class="admin-panel-note">Live world time with timezone offset, day or night status, and quick city comparison.</p>
            </div>
          </div>
          <div class="admin-world-clock-shell">
            <label class="admin-inline-select">
              <span>Location</span>
              <select data-admin-world-clock-zone>
                <option value="America/New_York">USA, New York</option>
                <option value="America/Los_Angeles">USA, Los Angeles</option>
                <option value="America/Toronto">Canada, Toronto</option>
                <option value="America/Vancouver">Canada, Vancouver</option>
                <option value="Europe/Rome">Italy, Rome</option>
                <option value="Europe/Paris">France, Paris</option>
                <option value="Europe/Berlin">Germany, Berlin</option>
                <option value="Europe/London">Europe, London</option>
                <option value="Asia/Tehran">Iran, Tehran</option>
                <option value="Asia/Dubai">Middle East, Dubai</option>
                <option value="Asia/Tokyo">Asia, Tokyo</option>
                <option value="Australia/Sydney">Australia, Sydney</option>
                <option value="UTC">World, UTC</option>
              </select>
            </label>
            <div class="admin-world-clock-face">
              <strong data-admin-world-clock-time>--:--:--</strong>
              <span data-admin-world-clock-date>Loading date...</span>
            </div>
            <div class="admin-world-clock-meta">
              <div><span>Zone</span><strong data-admin-world-clock-label>USA, New York</strong></div>
              <div><span>UTC Offset</span><strong data-admin-world-clock-offset>UTC</strong></div>
              <div><span>Status</span><strong data-admin-world-clock-status>Daytime</strong></div>
              <div><span>Format</span><strong>24 Hours</strong></div>
            </div>
            <div class="admin-world-clock-grid" data-admin-world-clock-grid></div>
          </div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminActiveAdmins): ?>
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
        <?php endif; ?>

        <?php if ($showAdminVisitorAnalytics): ?>
        <article class="admin-panel" data-admin-visitor-analytics-root data-admin-visitor-analytics-endpoint="/GirffoN/backend/admin/visitor-analytics-data.php" data-admin-visitor-analytics="<?php echo $escapeAdminDashboardJson($adminVisitorAnalytics); ?>">
          <div class="admin-panel-head">
            <div>
              <h2>Visitors Analytics</h2>
              <p class="admin-panel-note">Public website visitor analytics tracked across storefront pages, cart, checkout, gift cards, and custom design.</p>
            </div>
          </div>
          <div class="admin-visitor-controls" data-visitor-controls>
            <div class="admin-visitor-filter-group" role="tablist" aria-label="Visitor analytics range">
              <button type="button" class="admin-chip" data-visitor-range="today">Today</button>
              <button type="button" class="admin-chip" data-visitor-range="7days">7 Days</button>
              <button type="button" class="admin-chip is-active" data-visitor-range="30days">30 Days</button>
              <button type="button" class="admin-chip" data-visitor-range="this_year">This Year</button>
              <button type="button" class="admin-chip" data-visitor-range="custom">Custom Range</button>
            </div>
            <div class="admin-visitor-custom-range" data-visitor-custom-wrap hidden>
              <label class="admin-inline-input"><span>Start Date</span><input type="date" data-visitor-start value="<?php echo $escapeAdminDashboard($adminVisitorAnalytics['range_start'] ?? ''); ?>"></label>
              <label class="admin-inline-input"><span>End Date</span><input type="date" data-visitor-end value="<?php echo $escapeAdminDashboard($adminVisitorAnalytics['range_end'] ?? ''); ?>"></label>
              <button type="button" class="admin-button admin-button-soft" data-visitor-apply-range>Apply Range</button>
            </div>
            <div class="admin-visitor-export-group">
              <button type="button" class="admin-button admin-button-soft" data-visitor-export="csv">Export CSV</button>
              <button type="button" class="admin-button admin-button-soft" data-visitor-export="pdf">Export PDF</button>
            </div>
          </div>
          <div class="admin-visitor-range-meta">
            <strong data-visitor-range-label><?php echo $escapeAdminDashboard($adminVisitorAnalytics['range_label'] ?? 'Last 30 Days'); ?></strong>
            <span class="admin-panel-note" data-visitor-last-updated>Live data updates every minute.</span>
          </div>
          <div class="admin-visitor-overview-grid">
            <div class="admin-analytics-card"><span>Live Online Visitors</span><strong data-visitor-live="online"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['online'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Visitors Today</span><strong data-visitor-live="today"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['today'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Visitors This Week</span><strong data-visitor-live="week"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['week'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Visitors This Month</span><strong data-visitor-live="month"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['month'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Total Visitors</span><strong data-visitor-live="total"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['total'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>New Visitors</span><strong data-visitor-summary="new"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['new'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Returning Visitors</span><strong data-visitor-summary="returning"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['returning'] ?? 0); ?></strong></div>
          </div>
          <div class="admin-visitor-overview-grid admin-visitor-overview-grid--secondary">
            <div class="admin-analytics-card"><span>Visitors In Range</span><strong data-visitor-summary="visitors"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['visitors'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Average Session</span><strong data-visitor-summary="average_session_duration_label"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['average_session_duration_label'] ?? '0s'); ?></strong></div>
            <div class="admin-analytics-card"><span>Average Time Per Page</span><strong data-visitor-summary="average_time_per_page_label"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['average_time_per_page_label'] ?? '0s'); ?></strong></div>
            <div class="admin-analytics-card"><span>Conversion Rate</span><strong data-visitor-summary="conversion_rate"><?php echo $escapeAdminDashboard($formatAdminDashboardPercent($adminVisitorAnalytics['conversion_rate'] ?? 0)); ?></strong></div>
          </div>
          <div class="admin-visitor-overview-grid admin-visitor-overview-grid--actions">
            <div class="admin-analytics-card"><span>Bounce Rate</span><strong data-visitor-summary="bounce_rate"><?php echo $escapeAdminDashboard($formatAdminDashboardPercent($adminVisitorAnalytics['bounce_rate'] ?? 0)); ?></strong></div>
            <div class="admin-analytics-card"><span>Add To Cart</span><strong data-visitor-summary="add_to_cart"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['add_to_cart'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Wishlist Adds</span><strong data-visitor-summary="wishlist_adds"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['wishlist_adds'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Custom Design Opens</span><strong data-visitor-summary="custom_design_opens"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['custom_design_opens'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Gift Card Views</span><strong data-visitor-summary="gift_card_views"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['gift_card_views'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Checkout Started</span><strong data-visitor-summary="checkout_started"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['checkout_started'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Completed Orders</span><strong data-visitor-summary="completed_orders"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['completed_orders'] ?? 0); ?></strong></div>
            <div class="admin-analytics-card"><span>Abandoned Carts</span><strong data-visitor-summary="abandoned_carts"><?php echo $escapeAdminDashboard($adminVisitorAnalytics['abandoned_carts'] ?? 0); ?></strong></div>
          </div>

          <div class="admin-visitor-chart-grid">
            <section class="admin-visitor-chart-card">
              <div class="admin-panel-head admin-panel-head-compact">
                <div>
                  <h3>Top Countries</h3>
                  <p class="admin-panel-note">Where storefront visitors are coming from.</p>
                </div>
              </div>
              <div class="admin-visitor-bar-list" data-visitor-list="countries"></div>
            </section>

            <section class="admin-visitor-chart-card">
              <div class="admin-panel-head admin-panel-head-compact">
                <div>
                  <h3>Referrer Details</h3>
                  <p class="admin-panel-note">Google, Instagram, Facebook, Bing, Direct, and other traffic sources.</p>
                </div>
              </div>
              <div class="admin-visitor-bar-list" data-visitor-list="referrers"></div>
            </section>

            <section class="admin-visitor-chart-card">
              <div class="admin-panel-head admin-panel-head-compact">
                <div>
                  <h3>Browser Statistics</h3>
                  <p class="admin-panel-note">Chrome, Edge, Firefox, and Safari usage.</p>
                </div>
              </div>
              <div class="admin-visitor-bar-list" data-visitor-list="browsers"></div>
            </section>

            <section class="admin-visitor-chart-card">
              <div class="admin-panel-head admin-panel-head-compact">
                <div>
                  <h3>Device Statistics</h3>
                  <p class="admin-panel-note">Desktop, mobile, and tablet traffic split.</p>
                </div>
              </div>
              <div class="admin-visitor-bar-list" data-visitor-list="devices"></div>
            </section>

            <section class="admin-visitor-chart-card">
              <div class="admin-panel-head admin-panel-head-compact">
                <div>
                  <h3>Landing Pages</h3>
                  <p class="admin-panel-note">First pages visitors enter during the selected range.</p>
                </div>
              </div>
              <div class="admin-visitor-page-list" data-visitor-list="landing_pages"></div>
            </section>

            <section class="admin-visitor-chart-card">
              <div class="admin-panel-head admin-panel-head-compact">
                <div>
                  <h3>Exit Pages</h3>
                  <p class="admin-panel-note">Where visitor sessions most often finish.</p>
                </div>
              </div>
              <div class="admin-visitor-page-list" data-visitor-list="exit_pages"></div>
            </section>

            <section class="admin-visitor-chart-card">
              <div class="admin-panel-head admin-panel-head-compact">
                <div>
                  <h3>Average Time Per Page</h3>
                  <p class="admin-panel-note">Pages with the longest average engagement time.</p>
                </div>
              </div>
              <div class="admin-visitor-page-list" data-visitor-list="page_durations"></div>
            </section>

            <section class="admin-visitor-chart-card">
              <div class="admin-panel-head admin-panel-head-compact">
                <div>
                  <h3>Search Keywords</h3>
                  <p class="admin-panel-note">Captured search phrases when referrer data includes them.</p>
                </div>
              </div>
              <div class="admin-visitor-page-list" data-visitor-list="keywords"></div>
            </section>
          </div>

          <section class="admin-visitor-pages-card">
            <div class="admin-panel-head admin-panel-head-compact">
              <div>
                <h3>Top 10 Most Visited Pages</h3>
                <p class="admin-panel-note">Most-viewed public pages across the GIRFFON website.</p>
              </div>
            </div>
            <div class="admin-visitor-page-list" data-visitor-list="pages"></div>
          </section>
        </article>
        <?php endif; ?>
      </section>
      <?php endif; ?>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260804r13"></script>
</body>
</html>
