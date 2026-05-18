<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/newsletter-data.php';

function girffonAdminPromotionalDebugEscape($value): string
{
    if ($value === null) {
        return 'NULL';
    }

    $text = trim((string) $value);
    if ($text === '') {
        $text = '-';
    }

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function girffonAdminPromotionalDebugCount(PDO $pdo, string $sql): int
{
    try {
        $statement = $pdo->query($sql);
        return (int) ($statement ? $statement->fetchColumn() : 0);
    } catch (PDOException $exception) {
        return 0;
    }
}

$userColumns = girffonAdminTableColumns($pdo, 'users');
$audience = girffonAdminFetchPromotionalAudience($pdo);
$eligibleRows = [];
$skippedRows = [];

foreach ($audience as $row) {
    if (!empty($row['ready_to_send'])) {
        $eligibleRows[] = $row;
    } else {
        $skippedRows[] = $row;
    }
}

$totalUsers = ($userColumns !== [] && isset($userColumns['id']))
    ? girffonAdminPromotionalDebugCount($pdo, 'SELECT COUNT(*) FROM users')
    : 0;
$usersWithEmail = ($userColumns !== [] && isset($userColumns['email']))
    ? girffonAdminPromotionalDebugCount($pdo, "SELECT COUNT(*) FROM users WHERE LOWER(TRIM(COALESCE(email, ''))) <> ''")
    : 0;
$promotionalEnabledCount = 0;
foreach ($audience as $row) {
    if (!empty($row['promotional_emails'])) {
        $promotionalEnabledCount++;
    }
}

$checkRequested = (string) ($_GET['check'] ?? '') === '1';
$todayDate = date('Y-m-d');
$adminIdentity = trim((string) ($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? $_SESSION['admin_email'] ?? 'GirffoN Admin'));
if ($adminIdentity === '') {
    $adminIdentity = 'GirffoN Admin';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Promotional Debug</title>
  <link rel="stylesheet" href="/GirffoN/CSS/admin-girffon.css?v=20260511r15">
  <style>
    body {
      font-family: Georgia, serif;
      background: #f5f1ea;
      color: #1f1812;
    }

    .debug-shell {
      max-width: 1320px;
      margin: 0 auto;
      width: 100%;
    }

    .debug-main {
      padding: 32px 20px 48px;
    }

    h1, h2 {
      margin: 0 0 12px;
    }

    .debug-card {
      background: #fffdf9;
      border: 1px solid #dccfbf;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 18px;
      box-shadow: 0 16px 42px rgba(46, 28, 10, 0.08);
    }

    .debug-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 14px;
    }

    .debug-metric {
      border: 1px solid #ece2d5;
      border-radius: 12px;
      padding: 14px;
      background: #fff;
    }

    .debug-metric strong {
      display: block;
      font-size: 1.35rem;
      margin-top: 8px;
    }

    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      min-width: 1040px;
    }

    th,
    td {
      border: 1px solid #ece2d5;
      padding: 10px 12px;
      text-align: left;
      vertical-align: top;
      font-size: 0.95rem;
    }

    th {
      background: #f8f2e8;
      white-space: nowrap;
    }

    .debug-note {
      color: #6e5c4a;
      margin: 0;
    }

    .debug-actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    .debug-actions-copy {
      display: grid;
      gap: 10px;
    }

    .debug-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 48px;
      padding: 0 18px;
      border-radius: 12px;
      border: 1px solid #c9a56a;
      background: #c9a56a;
      color: #1f1812;
      text-decoration: none;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      box-shadow: 0 12px 28px rgba(84, 56, 14, 0.12);
    }

    .debug-button:hover {
      background: #d6b57a;
    }

    .debug-chip {
      display: inline-block;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid #e7d8c3;
      background: #fff;
      color: #6e5c4a;
      font-size: 0.88rem;
    }
  </style>
</head>
<body class="admin-page" data-admin-page="promotional-debug">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="/GirffoN/Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Promotional audience verification and read-only send readiness checks for the GirffoN admin team.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="/GirffoN/admin-dashboard.php" aria-label="Dashboard" title="Dashboard"><span class="admin-nav-link-index">1. </span><span class="admin-nav-link-label">Dashboard</span></a>
        <a class="admin-nav-link" href="/GirffoN/admin-products.php" aria-label="Products" title="Products"><span class="admin-nav-link-index">2. </span><span class="admin-nav-link-label">Products</span></a>
        <a class="admin-nav-link" href="/GirffoN/admin-orders.php" aria-label="Orders" title="Orders"><span class="admin-nav-link-index">3. </span><span class="admin-nav-link-label">Orders</span></a>
        <a class="admin-nav-link" href="/GirffoN/admin-invoices.php" aria-label="Invoices" title="Invoices"><span class="admin-nav-link-index">4. </span><span class="admin-nav-link-label">Invoices</span></a>
        <a class="admin-nav-link" href="/GirffoN/admin-messages.php" aria-label="Messages" title="Messages"><span class="admin-nav-link-index">5. </span><span class="admin-nav-link-label">Messages</span></a>
        <a class="admin-nav-link" href="/GirffoN/admin-users.php" aria-label="Users" title="Users"><span class="admin-nav-link-index">6. </span><span class="admin-nav-link-label">Users</span></a>
        <a class="admin-nav-link is-active" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter"><span class="admin-nav-link-index">7. </span><span class="admin-nav-link-label">Newsletter</span></a>
        <a class="admin-nav-link" href="/GirffoN/admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders"><span class="admin-nav-link-index">8. </span><span class="admin-nav-link-label">Custom Design Orders</span></a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Promotional Verification</strong>
          <p class="admin-panel-note">Review eligible and skipped audience members before running the promotional campaign.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main debug-main">
      <div class="debug-shell">
        <header class="admin-topbar">
          <div>
            <p class="admin-page-subtitle">Admin</p>
            <h1 class="admin-page-title">Promotional Debug</h1>
          </div>
          <div class="admin-topbar-actions">
            <a class="admin-button admin-button-soft admin-view-shop-button" href="/GirffoN/index.html" aria-label="View Shop" title="View Shop">View Shop</a>
            <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
            <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
            <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
          </div>
        </header>

        <section class="debug-card">
          <h1>Promotional Audience Debug</h1>
          <p class="debug-note">Admin-only read-only diagnostics for promotional campaign eligibility. This page does not send email.</p>
        </section>

        <section class="debug-card">
          <div class="debug-actions">
            <div class="debug-actions-copy">
              <h2>Promotional Audience Control</h2>
              <p class="debug-note">Check which customers and subscribers are ready for the GirffoN promotional campaign right now.</p>
              <?php if ($checkRequested): ?>
                <span class="debug-chip">Latest promotional audience check completed.</span>
              <?php endif; ?>
            </div>
            <form method="GET" action="promotional-debug.php">
              <input type="hidden" name="check" value="1">
              <button class="debug-button" type="submit">Check Promotional Audience</button>
            </form>
          </div>

          <div class="debug-grid" style="margin-top:16px;">
            <div class="debug-metric">
              Today Date
              <strong><?php echo girffonAdminPromotionalDebugEscape($todayDate); ?></strong>
            </div>
            <div class="debug-metric">
              Total Users
              <strong><?php echo girffonAdminPromotionalDebugEscape($totalUsers); ?></strong>
            </div>
            <div class="debug-metric">
              Users With Email
              <strong><?php echo girffonAdminPromotionalDebugEscape($usersWithEmail); ?></strong>
            </div>
            <div class="debug-metric">
              Promotional Enabled Count
              <strong><?php echo girffonAdminPromotionalDebugEscape($promotionalEnabledCount); ?></strong>
            </div>
            <div class="debug-metric">
              Ready To Send Count
              <strong><?php echo girffonAdminPromotionalDebugEscape(count($eligibleRows)); ?></strong>
            </div>
            <div class="debug-metric">
              Logged Admin
              <strong><?php echo girffonAdminPromotionalDebugEscape($adminIdentity); ?></strong>
            </div>
          </div>
        </section>

        <section class="debug-card">
          <h2>Eligible Users</h2>
          <p class="debug-note">Recipients currently ready for the promotional campaign.</p>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>User ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Source</th>
                  <th>Status</th>
                  <th>Promotional Emails</th>
                  <th>Ready To Send</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($eligibleRows): ?>
                  <?php foreach ($eligibleRows as $row): ?>
                    <tr>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['user_id'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['name'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['email'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['source'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['status'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape(!empty($row['promotional_emails']) ? 'yes' : 'no'); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape(!empty($row['ready_to_send']) ? 'yes' : 'no'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7">No eligible users are ready for promotional sending right now.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="debug-card">
          <h2>Skipped Users</h2>
          <p class="debug-note">Recipients currently excluded because they are invalid, disabled, or inactive.</p>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>User ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Source</th>
                  <th>Status</th>
                  <th>Promotional Emails</th>
                  <th>Skipped Reason</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($skippedRows): ?>
                  <?php foreach ($skippedRows as $row): ?>
                    <tr>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['user_id'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['name'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['email'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['source'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['status'] ?? ''); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape(!empty($row['promotional_emails']) ? 'yes' : 'no'); ?></td>
                      <td><?php echo girffonAdminPromotionalDebugEscape($row['skipped_reason'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7">No skipped users were returned for the promotional audience view.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>
  <script src="/GirffoN/JS/admin-girffon.js?v=20260505r5"></script>
</body>
</html>