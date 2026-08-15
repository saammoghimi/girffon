<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../profile/communication-common.php';

function girffonAdminNewsletterDebugValue($value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    $text = trim((string) $value);
    return $text === '' ? '-' : $text;
}

function girffonAdminNewsletterDebugEscape($value): string
{
    return htmlspecialchars(girffonAdminNewsletterDebugValue($value), ENT_QUOTES, 'UTF-8');
}

function girffonAdminNewsletterDebugDatabaseName(PDO $pdo): string
{
    try {
        $statement = $pdo->query('SELECT DATABASE()');
        return (string) ($statement ? $statement->fetchColumn() : '');
    } catch (PDOException $exception) {
        return '';
    }
}

function girffonAdminNewsletterDebugTableColumns(PDO $pdo, string $table): array
{
    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

function girffonAdminNewsletterDebugCount(PDO $pdo, string $table, string $whereClause = '', array $params = []): int
{
    try {
        $sql = 'SELECT COUNT(*) FROM ' . $table;
        if ($whereClause !== '') {
            $sql .= ' WHERE ' . $whereClause;
        }
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return (int) ($statement->fetchColumn() ?: 0);
    } catch (PDOException $exception) {
        return 0;
    }
}

function girffonAdminNewsletterDebugLatestSubscribers(PDO $pdo): array
{
    $columns = girffonAdminNewsletterDebugTableColumns($pdo, 'newsletter_subscribers');
    if ($columns === []) {
        return [];
    }

    $columnLookup = [];
    foreach ($columns as $column) {
        $name = (string) ($column['Field'] ?? '');
        if ($name !== '') {
            $columnLookup[$name] = true;
        }
    }

    $selectColumns = ['id'];
    foreach (['user_id', 'email', 'status', 'source', 'subscribed_at', 'updated_at'] as $columnName) {
        if (isset($columnLookup[$columnName])) {
            $selectColumns[] = $columnName;
        }
    }

    $orderBy = isset($columnLookup['subscribed_at'])
        ? 'subscribed_at DESC, id DESC'
        : 'id DESC';

    try {
        $statement = $pdo->query('SELECT ' . implode(', ', array_unique($selectColumns)) . ' FROM newsletter_subscribers ORDER BY ' . $orderBy . ' LIMIT 10');
        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException $exception) {
        return [];
    }
}

$databaseName = girffonAdminNewsletterDebugDatabaseName($pdo);
$subscriberColumns = girffonAdminNewsletterDebugTableColumns($pdo, 'newsletter_subscribers');
$subscriberTableExists = $subscriberColumns !== [];
$subscriberCount = $subscriberTableExists ? girffonAdminNewsletterDebugCount($pdo, 'newsletter_subscribers') : 0;
$latestSubscribers = $subscriberTableExists ? girffonAdminNewsletterDebugLatestSubscribers($pdo) : [];
$newsletterMessageCount = girffonCommunicationTableExists($pdo, 'contact_messages')
    ? girffonAdminNewsletterDebugCount($pdo, 'contact_messages', 'subject = :subject', [':subject' => 'Newsletter Subscription'])
    : 0;
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
  <title>GirffoN Newsletter Debug</title>
  <style>
    body {
      margin: 0;
      font-family: Georgia, serif;
      background: #f5f1ea;
      color: #1f1812;
    }

    main {
      max-width: 1120px;
      margin: 0 auto;
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

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
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
    }

    .debug-note {
      color: #6e5c4a;
      margin: 0;
    }
  </style>
</head>
<body>
  <main>
    <section class="debug-card">
      <h1>Newsletter Debug</h1>
      <p class="debug-note">Admin-only diagnostics for the current newsletter subscription connection and table state.</p>
    </section>

    <section class="debug-card">
      <div class="debug-grid">
        <div class="debug-metric">
          Connected Database
          <strong><?php echo girffonAdminNewsletterDebugEscape($databaseName); ?></strong>
        </div>
        <div class="debug-metric">
          Logged Admin
          <strong><?php echo girffonAdminNewsletterDebugEscape($adminIdentity); ?></strong>
        </div>
        <div class="debug-metric">
          newsletter_subscribers Exists
          <strong><?php echo girffonAdminNewsletterDebugEscape($subscriberTableExists ? 'true' : 'false'); ?></strong>
        </div>
        <div class="debug-metric">
          newsletter_subscribers Count
          <strong><?php echo girffonAdminNewsletterDebugEscape($subscriberCount); ?></strong>
        </div>
        <div class="debug-metric">
          contact_messages Newsletter Subscription Count
          <strong><?php echo girffonAdminNewsletterDebugEscape($newsletterMessageCount); ?></strong>
        </div>
      </div>
    </section>

    <section class="debug-card">
      <h2>newsletter_subscribers Columns</h2>
      <?php if ($subscriberColumns): ?>
        <table>
          <thead>
            <tr>
              <th>Field</th>
              <th>Type</th>
              <th>Null</th>
              <th>Key</th>
              <th>Default</th>
              <th>Extra</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subscriberColumns as $column): ?>
              <tr>
                <td><?php echo girffonAdminNewsletterDebugEscape($column['Field'] ?? ''); ?></td>
                <td><?php echo girffonAdminNewsletterDebugEscape($column['Type'] ?? ''); ?></td>
                <td><?php echo girffonAdminNewsletterDebugEscape($column['Null'] ?? ''); ?></td>
                <td><?php echo girffonAdminNewsletterDebugEscape($column['Key'] ?? ''); ?></td>
                <td><?php echo girffonAdminNewsletterDebugEscape($column['Default'] ?? ''); ?></td>
                <td><?php echo girffonAdminNewsletterDebugEscape($column['Extra'] ?? ''); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="debug-note">newsletter_subscribers does not exist on the current connection.</p>
      <?php endif; ?>
    </section>

    <section class="debug-card">
      <h2>Last 10 newsletter_subscribers Rows</h2>
      <?php if ($latestSubscribers): ?>
        <table>
          <thead>
            <tr>
              <?php foreach (array_keys($latestSubscribers[0]) as $columnName): ?>
                <th><?php echo girffonAdminNewsletterDebugEscape($columnName); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latestSubscribers as $row): ?>
              <tr>
                <?php foreach ($row as $value): ?>
                  <td><?php echo girffonAdminNewsletterDebugEscape($value); ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="debug-note">No rows found in newsletter_subscribers on the current connection.</p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>