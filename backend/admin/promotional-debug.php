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
  <link rel="icon" type="image/png" href="../../Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Promotional Debug</title>
  <link rel="stylesheet" href="/GirffoN/CSS/admin-girffon.css?v=20260518r11">
  <style>
    @media (max-width: 1280px) and (min-width: 721px) {
      .admin-main,
      .debug-main,
      .debug-shell,
      .debug-card,
      .debug-grid,
      .table-wrap {
        min-width: 0;
      }

      .admin-main,
      .debug-main {
        max-width: 100%;
        overflow-x: hidden;
      }

      .debug-shell {
        max-width: 100%;
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

      .table-wrap {
        width: 100%;
        max-width: 100%;
      }
    }

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

    .admin-nav .admin-nav-link:nth-child(9)::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='3.2'/%3E%3Cpath d='M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 1 1-1.7 1.7l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V19a1.2 1.2 0 1 1-2.4 0v-.2a1 1 0 0 0-.7-.9 1 1 0 0 0-1 .2l-.2.1a1.2 1.2 0 1 1-1.7-1.7l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H5a1.2 1.2 0 1 1 0-2.4h.2a1 1 0 0 0 .9-.7 1 1 0 0 0-.2-1l-.1-.2a1.2 1.2 0 1 1 1.7-1.7l.1.1a1 1 0 0 0 1.1.2h.1a1 1 0 0 0 .6-.9V5a1.2 1.2 0 1 1 2.4 0v.2a1 1 0 0 0 .7.9 1 1 0 0 0 1-.2l.2-.1a1.2 1.2 0 1 1 1.7 1.7l-.1.1a1 1 0 0 0-.2 1.1v.1a1 1 0 0 0 .9.6H19a1.2 1.2 0 1 1 0 2.4h-.2a1 1 0 0 0-.9.7 1 1 0 0 0 .2 1Z'/%3E%3C/svg%3E");
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

      <?php
      $adminNavCurrentPage = 'newsletter';
      $adminNavBasePath = '/GirffoN';
      $adminNavVisibleKeys = ['dashboard', 'products', 'orders', 'invoices', 'messages', 'users', 'newsletter', 'custom_orders', 'settings'];
      require dirname(__DIR__, 2) . '/includes/admin-nav.php';
      ?>

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
  <script src="/GirffoN/JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>