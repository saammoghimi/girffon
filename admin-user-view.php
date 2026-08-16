<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/users-data.php";

$adminUserId = max(0, (int) ($_GET['id'] ?? 0));
$adminViewedUser = $adminUserId > 0 ? girffonAdminFetchUserById($pdo, $adminUserId) : null;
if (!$adminViewedUser) {
  header("Location: /GirffoN/admin-users.php?error=" . rawurlencode("User not found."));
  exit;
}

$adminUserOrders = girffonAdminFetchRecentOrdersForUser($pdo, $adminUserId, 5);
$adminUserInvoices = girffonAdminFetchRecentInvoicesForUser($pdo, $adminUserId, 5);
$adminUserCommunication = girffonAdminFetchUserCommunicationSnapshot($pdo, $adminUserId);
$adminUserStatusMessage = trim((string) ($_GET['status'] ?? ''));
$adminUserErrorMessage = trim((string) ($_GET['error'] ?? ''));

$escapeAdminUserView = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$formatAdminUserViewLabel = static function ($value) use ($escapeAdminUserView) {
  $text = trim((string) $value);
  return $text === '' ? '-' : $escapeAdminUserView(ucwords(str_replace('_', ' ', $text)));
};
$formatAdminUserViewDate = static function ($value) use ($escapeAdminUserView) {
  $text = trim((string) $value);
  if ($text === '') {
    return '-';
  }

  $timestamp = strtotime($text);
  return $timestamp ? $escapeAdminUserView(date('Y-m-d H:i', $timestamp)) : $escapeAdminUserView($text);
};
$adminUserFullName = trim((string) (($adminViewedUser['first_name'] ?? '') . ' ' . ($adminViewedUser['last_name'] ?? '')));
if ($adminUserFullName === '') {
  $adminUserFullName = trim((string) ($adminViewedUser['username'] ?? '')) ?: trim((string) ($adminViewedUser['email'] ?? 'Unknown User'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin User View</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
</head>
<body class="admin-page" data-admin-page="users" data-admin-users-source="database">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>User profile connected to the live admin database.</p>
      </div>
      <?php
      $adminNavCurrentPage = 'users';
      $adminNavBasePath = '';
      require __DIR__ . '/includes/admin-nav.php';
      ?>
      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>User Profile</strong>
          <p class="admin-panel-note">Profile details, recent orders, and invoice activity.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>
    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title">User Profile</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2><?php echo $escapeAdminUserView($adminUserFullName); ?></h2>
              <p class="admin-panel-note">Full member profile loaded from the users table.</p>
            </div>
            <div class="admin-form-actions">
              <a class="admin-button admin-button-soft" href="admin-users.php">Back</a>
              <a class="admin-button admin-button-accent" href="admin-user-edit.php?id=<?php echo $escapeAdminUserView($adminUserId); ?>">Edit User</a>
            </div>
          </div>

          <?php if ($adminUserStatusMessage || $adminUserErrorMessage): ?>
            <div class="admin-feedback<?php if ($adminUserErrorMessage): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
              <?php echo $escapeAdminUserView($adminUserErrorMessage ?: $adminUserStatusMessage); ?>
            </div>
          <?php endif; ?>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <tbody>
                <tr><th>Full Name</th><td><?php echo $escapeAdminUserView($adminUserFullName); ?></td></tr>
                <tr><th>Username</th><td><?php echo $escapeAdminUserView($adminViewedUser['username'] ?? '-'); ?></td></tr>
                <tr><th>Email</th><td><?php echo $escapeAdminUserView($adminViewedUser['email'] ?? '-'); ?></td></tr>
                <tr><th>Phone</th><td><?php echo $escapeAdminUserView(($adminViewedUser['phone'] ?? '') !== '' ? $adminViewedUser['phone'] : '-'); ?></td></tr>
                <tr><th>Country</th><td><?php echo $escapeAdminUserView(($adminViewedUser['country'] ?? '') !== '' ? $adminViewedUser['country'] : '-'); ?></td></tr>
                <tr><th>City</th><td><?php echo $escapeAdminUserView(($adminViewedUser['city'] ?? '') !== '' ? $adminViewedUser['city'] : '-'); ?></td></tr>
                <tr><th>Address</th><td><?php echo $escapeAdminUserView(($adminViewedUser['address'] ?? '') !== '' ? $adminViewedUser['address'] : '-'); ?></td></tr>
                <tr><th>Role</th><td><?php echo $formatAdminUserViewLabel($adminViewedUser['role'] ?? '-'); ?></td></tr>
                <tr><th>Status</th><td><?php echo $formatAdminUserViewLabel($adminViewedUser['status'] ?? '-'); ?></td></tr>
                <tr><th>Created Date</th><td><?php echo $formatAdminUserViewDate($adminViewedUser['created_at'] ?? ''); ?></td></tr>
              </tbody>
            </table>
          </div>
        </article>
      </section>

      <section class="admin-content-grid">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Orders</h2>
              <p class="admin-panel-note">Latest orders linked to this user email.</p>
            </div>
          </div>
          <div class="admin-mini-list">
            <?php if ($adminUserOrders): ?>
              <?php foreach ($adminUserOrders as $order): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminUserView(($order['order_number'] ?? '') . ' - ' . ($order['customer_name'] ?? '')); ?></span><strong><?php echo $formatAdminUserViewDate($order['created_at'] ?? ''); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No recent orders found for this user.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Recent Invoices</h2>
              <p class="admin-panel-note">Latest invoices linked to this user email.</p>
            </div>
          </div>
          <div class="admin-mini-list">
            <?php if ($adminUserInvoices): ?>
              <?php foreach ($adminUserInvoices as $invoice): ?>
                <div class="admin-mini-item"><span><?php echo $escapeAdminUserView(($invoice['invoice_number'] ?? '') . ' - ' . ($invoice['order_number'] ?? '')); ?></span><strong><?php echo $formatAdminUserViewDate($invoice['created_at'] ?? ''); ?></strong></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="admin-empty">No recent invoices found for this user.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Communication Preferences</h2>
              <p class="admin-panel-note">Saved email preferences, newsletter state, and latest test email result.</p>
            </div>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <tbody>
                <tr><th>Promotional Emails</th><td><?php echo $formatAdminUserViewLabel(!empty($adminUserCommunication['preferences']['promotional_emails']) ? 'enabled' : 'disabled'); ?></td></tr>
                <tr><th>Catalog Emails</th><td><?php echo $formatAdminUserViewLabel(!empty($adminUserCommunication['preferences']['catalog_emails']) ? 'enabled' : 'disabled'); ?></td></tr>
                <tr><th>Birthday Discount Emails</th><td><?php echo $formatAdminUserViewLabel(!empty($adminUserCommunication['preferences']['birthday_discount_emails']) ? 'enabled' : 'disabled'); ?></td></tr>
                <tr><th>Order Updates</th><td><?php echo $formatAdminUserViewLabel(!empty($adminUserCommunication['preferences']['order_updates']) ? 'enabled' : 'disabled'); ?></td></tr>
                <tr><th>SMS Notifications</th><td><?php echo $escapeAdminUserView(!empty($adminUserCommunication['preferences']['sms_notifications']) ? 'Requested - Available soon' : 'Available soon'); ?></td></tr>
                <tr><th>Two-Factor</th><td><?php echo $formatAdminUserViewLabel(!empty($adminUserCommunication['preferences']['two_factor_enabled']) ? 'enabled' : 'disabled'); ?></td></tr>
                <tr><th>Preferences Updated</th><td><?php echo $formatAdminUserViewDate($adminUserCommunication['preferences']['updated_at'] ?? ''); ?></td></tr>
                <tr><th>Newsletter Subscription</th><td><?php echo $formatAdminUserViewLabel($adminUserCommunication['newsletter']['status'] ?? 'not subscribed'); ?></td></tr>
                <tr><th>Newsletter Source</th><td><?php echo $escapeAdminUserView(($adminUserCommunication['newsletter']['source'] ?? '') !== '' ? $adminUserCommunication['newsletter']['source'] : '-'); ?></td></tr>
                <tr><th>Subscribed At</th><td><?php echo $formatAdminUserViewDate($adminUserCommunication['newsletter']['subscribed_at'] ?? ''); ?></td></tr>
                <tr><th>Latest Test Email</th><td><?php echo $formatAdminUserViewLabel($adminUserCommunication['latest_test_email']['status'] ?? 'none'); ?></td></tr>
                <tr><th>Test Email Transport</th><td><?php echo $escapeAdminUserView(($adminUserCommunication['latest_test_email']['transport'] ?? '') !== '' ? $adminUserCommunication['latest_test_email']['transport'] : '-'); ?></td></tr>
                <tr><th>Test Email Date</th><td><?php echo $formatAdminUserViewDate($adminUserCommunication['latest_test_email']['created_at'] ?? ''); ?></td></tr>
                <tr><th>Test Email Error</th><td><?php echo $escapeAdminUserView(($adminUserCommunication['latest_test_email']['error_message'] ?? '') !== '' ? $adminUserCommunication['latest_test_email']['error_message'] : '-'); ?></td></tr>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>
  <script src="JS/admin-girffon.js?v=20260505r5"></script>
</body>
</html>