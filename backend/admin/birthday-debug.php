<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/newsletter-data.php';

function girffonAdminBirthdayDebugEscape($value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    }

    $text = trim((string) $value);
    if ($text === '') {
        $text = '-';
    }

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function girffonAdminBirthdayDebugDatabaseName(PDO $pdo): string
{
    try {
        $statement = $pdo->query('SELECT DATABASE()');
        return (string) ($statement ? $statement->fetchColumn() : '');
    } catch (PDOException $exception) {
        return '';
    }
}

function girffonAdminBirthdayDebugYesNo($value): string
{
    return (int) $value === 1 ? 'yes' : 'no';
}

function girffonAdminBirthdayDebugFormatSqlError(?PDOException $exception): array
{
  if (!$exception instanceof PDOException) {
    return [
      'sqlstate' => '',
      'message' => '',
    ];
  }

  $errorInfo = $exception->errorInfo;

  return [
    'sqlstate' => (string) ($errorInfo[0] ?? $exception->getCode() ?? ''),
    'message' => $exception->getMessage(),
  ];
}

function girffonAdminBirthdayDebugCount(PDO $pdo, string $sql): int
{
  try {
    $statement = $pdo->query($sql);
    return (int) ($statement ? $statement->fetchColumn() : 0);
  } catch (PDOException $exception) {
    return 0;
  }
}

function girffonAdminBirthdayDebugSummary(PDO $pdo): array
{
  $userColumns = girffonAdminTableColumns($pdo, 'users');
  $subscriberColumns = girffonAdminTableColumns($pdo, 'newsletter_subscribers');
  $preferenceTable = girffonAdminNotificationPreferenceTable($pdo);
  $preferenceColumns = $preferenceTable !== '' ? girffonAdminTableColumns($pdo, $preferenceTable) : [];

  $summary = [
    'total_users' => 0,
    'users_with_email' => 0,
    'users_with_date_of_birth' => 0,
    'users_with_today_birthday' => 0,
    'users_with_birthday_discount_emails_enabled' => 0,
    'newsletter_subscribers_count' => 0,
  ];

  if ($userColumns !== []) {
    $summary['total_users'] = girffonAdminBirthdayDebugCount($pdo, 'SELECT COUNT(*) FROM users');

    if (isset($userColumns['email'])) {
      $summary['users_with_email'] = girffonAdminBirthdayDebugCount(
        $pdo,
        "SELECT COUNT(*) FROM users WHERE LOWER(TRIM(COALESCE(email, ''))) <> ''"
      );
    }

    if (isset($userColumns['date_of_birth'])) {
      $summary['users_with_date_of_birth'] = girffonAdminBirthdayDebugCount(
        $pdo,
        "SELECT COUNT(*) FROM users WHERE COALESCE(CAST(date_of_birth AS CHAR), '') <> ''"
      );
      $summary['users_with_today_birthday'] = girffonAdminBirthdayDebugCount(
        $pdo,
        "SELECT COUNT(*)
         FROM users
         WHERE COALESCE(CAST(date_of_birth AS CHAR), '') <> ''
           AND (
            DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
            OR DATE_FORMAT(STR_TO_DATE(CAST(date_of_birth AS CHAR), '%m/%d/%Y'), '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
           )"
      );
    }
  }

  if ($preferenceTable !== '' && isset($preferenceColumns['birthday_discount_emails'])) {
    $summary['users_with_birthday_discount_emails_enabled'] = girffonAdminBirthdayDebugCount(
      $pdo,
      "SELECT COUNT(*) FROM {$preferenceTable} WHERE COALESCE(birthday_discount_emails, 0) = 1"
    );
  }

  if (isset($subscriberColumns['status'])) {
    $summary['newsletter_subscribers_count'] = girffonAdminBirthdayDebugCount(
      $pdo,
      "SELECT COUNT(*)
       FROM newsletter_subscribers
       WHERE LOWER(TRIM(COALESCE(status, ''))) = 'subscribed'"
    );
  } elseif ($subscriberColumns !== []) {
    $summary['newsletter_subscribers_count'] = girffonAdminBirthdayDebugCount($pdo, 'SELECT COUNT(*) FROM newsletter_subscribers');
  }

  return $summary;
}

function girffonAdminBirthdayDebugFetchRawUsers(PDO $pdo, int $limit = 20): array
{
  $userColumns = girffonAdminTableColumns($pdo, 'users');
  if ($userColumns === [] || !isset($userColumns['id'])) {
    return [
      'rows' => [],
      'error' => [
        'sqlstate' => '',
        'message' => 'users table is missing or unavailable.',
      ],
    ];
  }

  $firstNameExpression = isset($userColumns['first_name']) ? 'COALESCE(u.first_name, \'\')' : "''";
  $lastNameExpression = isset($userColumns['last_name']) ? 'COALESCE(u.last_name, \'\')' : "''";
  $statusExpression = isset($userColumns['status']) ? 'COALESCE(u.status, \'\')' : "''";
  $emailExpression = isset($userColumns['email']) ? "LOWER(TRIM(COALESCE(u.email, '')))" : "''";
  $dateOfBirthExpression = isset($userColumns['date_of_birth']) ? "COALESCE(CAST(u.date_of_birth AS CHAR), '')" : "''";

  try {
    $statement = $pdo->query(
      "SELECT
        u.id AS user_id,
        {$emailExpression} AS email,
        {$firstNameExpression} AS first_name,
        {$lastNameExpression} AS last_name,
        {$statusExpression} AS status,
        {$dateOfBirthExpression} AS date_of_birth
       FROM users u
       ORDER BY u.id ASC
       LIMIT " . max(1, $limit)
    );

    return [
      'rows' => $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [],
      'error' => [
        'sqlstate' => '',
        'message' => '',
      ],
    ];
  } catch (PDOException $exception) {
    return [
      'rows' => [],
      'error' => girffonAdminBirthdayDebugFormatSqlError($exception),
    ];
  }
}

function girffonAdminBirthdayDebugFetchRows(PDO $pdo): array
{
    $userColumns = girffonAdminTableColumns($pdo, 'users');
    if ($userColumns === [] || !isset($userColumns['id'], $userColumns['email'], $userColumns['date_of_birth'])) {
        return [];
    }

    $firstNameExpression = isset($userColumns['first_name']) ? 'COALESCE(u.first_name, \'\')' : "''";
    $lastNameExpression = isset($userColumns['last_name']) ? 'COALESCE(u.last_name, \'\')' : "''";
    $statusExpression = isset($userColumns['status']) ? 'COALESCE(u.status, \'\')' : "''";

    try {
        $statement = $pdo->query(
            "SELECT
                u.id AS user_id,
                LOWER(TRIM(COALESCE(u.email, ''))) AS email,
                {$firstNameExpression} AS first_name,
                {$lastNameExpression} AS last_name,
                {$statusExpression} AS status,
                u.date_of_birth AS date_of_birth,
                DATE_FORMAT(CURDATE(), '%m-%d') AS today_month_day,
                1 AS birthday_match,
                NULL AS preference_user_id,
                NULL AS birthday_discount_emails,
                0 AS preference_found,
                0 AS newsletter_subscribed,
                0 AS join_match,
                1 AS final_eligible,
                '' AS skipped_reason
             FROM users u
             WHERE LOWER(TRIM(COALESCE(u.email, ''))) <> ''
               AND u.date_of_birth IS NOT NULL
               AND DATE_FORMAT(CAST(u.date_of_birth AS DATE), '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
             ORDER BY u.id ASC"
        );

        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

$databaseName = girffonAdminBirthdayDebugDatabaseName($pdo);
$userColumns = girffonAdminTableColumns($pdo, 'users');
$dateOfBirthExists = isset($userColumns['date_of_birth']);
$preferenceTable = girffonAdminNotificationPreferenceTable($pdo);
$userPreferencesTableExists = girffonAdminTableColumns($pdo, 'user_preferences') !== [];
$subscriberTableExists = girffonAdminTableColumns($pdo, 'newsletter_subscribers') !== [];
$summary = girffonAdminBirthdayDebugSummary($pdo);
$rows = girffonAdminBirthdayDebugFetchRows($pdo);
$birthdayCandidateCount = count($rows);
$todayDate = date('Y-m-d');
$todayMonthDay = date('m/d');
$birthdayCheckRequested = (string) ($_GET['check'] ?? '') === '1';
$birthdayDebugLogDirectory = dirname(__DIR__) . '/logs';
if (!is_dir($birthdayDebugLogDirectory)) {
  @mkdir($birthdayDebugLogDirectory, 0777, true);
}
@file_put_contents($birthdayDebugLogDirectory . '/birthday-debug-page.log', date('c') . ' birthday-debug candidate_count=' . $birthdayCandidateCount . PHP_EOL, FILE_APPEND);
$rawUserDebug = girffonAdminBirthdayDebugFetchRawUsers($pdo, 20);
$rawUsers = (array) ($rawUserDebug['rows'] ?? []);
$rawUserError = is_array($rawUserDebug['error'] ?? null) ? $rawUserDebug['error'] : ['sqlstate' => '', 'message' => ''];
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
  <title>GirffoN Birthday Debug</title>
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
      min-width: 1180px;
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
<body class="admin-page" data-admin-page="birthday-debug">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="/GirffoN/Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Birthday candidate verification and send-readiness checks for the GirffoN admin team.</p>
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
        <a class="admin-nav-link" href="/GirffoN/admin-settings.php" aria-label="Settings" title="Settings"><span class="admin-nav-link-index">9. </span><span class="admin-nav-link-label">Settings</span></a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Birthday Verification</strong>
          <p class="admin-panel-note">Check today&apos;s birthday candidates first, then return to Newsletter Admin to send.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main debug-main">
      <div class="debug-shell">
        <header class="admin-topbar">
          <div>
            <p class="admin-page-subtitle">Admin</p>
            <h1 class="admin-page-title">Birthday Debug</h1>
          </div>
          <div class="admin-topbar-actions">
            <a class="admin-button admin-button-soft admin-view-shop-button" href="/GirffoN/index.html" aria-label="View Shop" title="View Shop">View Shop</a>
            <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
            <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
            <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
          </div>
        </header>

        <section class="debug-card">
          <h1>Birthday Debug</h1>
          <p class="debug-note">Admin-only read-only diagnostics for birthday email eligibility. This page does not send email.</p>
        </section>

        <section class="debug-card">
      <div class="debug-actions">
        <div class="debug-actions-copy">
          <h2>Birthday Check Control</h2>
          <p class="debug-note">This page helps admin verify which customers will receive Birthday Discount Emails today.</p>
          <?php if ($birthdayCheckRequested): ?>
            <span class="debug-chip">Latest birthday query checked just now.</span>
          <?php endif; ?>
        </div>
        <form method="GET" action="birthday-debug.php">
          <input type="hidden" name="check" value="1">
          <button class="debug-button" type="submit">Check Today Birthday Customers</button>
        </form>
      </div>
      <div class="debug-grid" style="margin-top:16px;">
        <div class="debug-metric">
          Today Date
          <strong><?php echo girffonAdminBirthdayDebugEscape($todayDate); ?></strong>
        </div>
        <div class="debug-metric">
          Today Month/Day
          <strong><?php echo girffonAdminBirthdayDebugEscape($todayMonthDay); ?></strong>
        </div>
        <div class="debug-metric">
          Birthday Candidate Count
          <strong><?php echo girffonAdminBirthdayDebugEscape($birthdayCandidateCount); ?></strong>
        </div>
      </div>
        </section>

        <section class="debug-card">
      <div class="debug-grid">
        <div class="debug-metric">
          Connected Database
          <strong><?php echo girffonAdminBirthdayDebugEscape($databaseName); ?></strong>
        </div>
        <div class="debug-metric">
          Logged Admin
          <strong><?php echo girffonAdminBirthdayDebugEscape($adminIdentity); ?></strong>
        </div>
        <div class="debug-metric">
          users.date_of_birth Exists
          <strong><?php echo girffonAdminBirthdayDebugEscape($dateOfBirthExists ? 'true' : 'false'); ?></strong>
        </div>
        <div class="debug-metric">
          user_preferences Table Exists
          <strong><?php echo girffonAdminBirthdayDebugEscape($userPreferencesTableExists ? 'true' : 'false'); ?></strong>
        </div>
        <div class="debug-metric">
          newsletter_subscribers Table Exists
          <strong><?php echo girffonAdminBirthdayDebugEscape($subscriberTableExists ? 'true' : 'false'); ?></strong>
        </div>
        <div class="debug-metric">
          Active Preference Source
          <strong><?php echo girffonAdminBirthdayDebugEscape($preferenceTable === '' ? 'none' : $preferenceTable); ?></strong>
        </div>
        <div class="debug-metric">
          Active Users Listed
          <strong><?php echo girffonAdminBirthdayDebugEscape(count($rows)); ?></strong>
        </div>
        <div class="debug-metric">
          Birthday Candidate Count
          <strong><?php echo girffonAdminBirthdayDebugEscape($birthdayCandidateCount); ?></strong>
        </div>
        <div class="debug-metric">
          Total Users
          <strong><?php echo girffonAdminBirthdayDebugEscape($summary['total_users']); ?></strong>
        </div>
        <div class="debug-metric">
          Users With Email
          <strong><?php echo girffonAdminBirthdayDebugEscape($summary['users_with_email']); ?></strong>
        </div>
        <div class="debug-metric">
          Users With Date of Birth
          <strong><?php echo girffonAdminBirthdayDebugEscape($summary['users_with_date_of_birth']); ?></strong>
        </div>
        <div class="debug-metric">
          Users With Today Birthday
          <strong><?php echo girffonAdminBirthdayDebugEscape($summary['users_with_today_birthday']); ?></strong>
        </div>
        <div class="debug-metric">
          Birthday Discount Emails Enabled
          <strong><?php echo girffonAdminBirthdayDebugEscape($summary['users_with_birthday_discount_emails_enabled']); ?></strong>
        </div>
        <div class="debug-metric">
          Newsletter Subscribers Count
          <strong><?php echo girffonAdminBirthdayDebugEscape($summary['newsletter_subscribers_count']); ?></strong>
        </div>
      </div>
        </section>

        <section class="debug-card">
      <h2>Active User Eligibility</h2>
      <p class="debug-note">Temporary native DATE candidate count: <?php echo girffonAdminBirthdayDebugEscape($birthdayCandidateCount); ?></p>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Email</th>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Status</th>
              <th>Date of Birth Raw</th>
              <th>Today Month/Day</th>
              <th>Birthday Match</th>
              <th>Birthday Discount Emails</th>
              <th>Newsletter Subscribed</th>
              <th>Final Eligible</th>
              <th>Skipped Reason</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows): ?>
              <?php foreach ($rows as $row): ?>
                <tr>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['user_id'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['email'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['first_name'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['last_name'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['status'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['date_of_birth'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['today_month_day'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape(girffonAdminBirthdayDebugYesNo($row['birthday_match'] ?? 0)); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['birthday_discount_emails']); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape(girffonAdminBirthdayDebugYesNo($row['newsletter_subscribed'] ?? 0)); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape(girffonAdminBirthdayDebugYesNo($row['final_eligible'] ?? 0)); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['skipped_reason'] ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="12">No active users were returned for the birthday debug view.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
        </section>

        <section class="debug-card">
      <h2>All Users Raw Debug</h2>
      <p class="debug-note">First 20 rows from users with no status or birthday filtering, plus raw preference and newsletter join values.</p>
      <?php if (($rawUserError['sqlstate'] ?? '') !== '' || ($rawUserError['message'] ?? '') !== ''): ?>
        <div class="debug-card" style="margin:16px 0 0;padding:16px;border-color:#d08d7a;background:#fff6f3;box-shadow:none;">
          <h2 style="font-size:1rem;margin-bottom:10px;">Raw Query SQL Error</h2>
          <div class="debug-grid">
            <div class="debug-metric">
              SQLSTATE
              <strong><?php echo girffonAdminBirthdayDebugEscape($rawUserError['sqlstate'] ?? ''); ?></strong>
            </div>
            <div class="debug-metric">
              PDO Message
              <strong style="font-size:1rem;line-height:1.4;"><?php echo girffonAdminBirthdayDebugEscape($rawUserError['message'] ?? ''); ?></strong>
            </div>
          </div>
        </div>
      <?php endif; ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Email</th>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Status</th>
              <th>Date of Birth</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rawUsers): ?>
              <?php foreach ($rawUsers as $row): ?>
                <tr>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['user_id'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['email'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['first_name'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['last_name'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['status'] ?? ''); ?></td>
                  <td><?php echo girffonAdminBirthdayDebugEscape($row['date_of_birth'] ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6">No users were returned for the raw debug view.</td>
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