<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../profile/common.php';

const GIRFFON_DEBUG_EMAIL = 'girffon2025shop@gmail.com';

function girffonUserProfileDebugEscape($value): string
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function girffonUserProfileDebugDisplayValue($value): string
{
  if ($value === null) {
    return 'NULL';
  }

  if (is_bool($value)) {
    return $value ? 'true' : 'false';
  }

  if (is_array($value)) {
    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  $text = trim((string) $value);
  if ($text === '') {
    return 'NULL';
  }

  return girffonUserProfileDebugEscape($text);
}

function girffonUserProfileDebugValueClass($value): string
{
  if ($value === null) {
    return 'value-null';
  }

  if (is_array($value)) {
    return $value === [] ? 'value-null' : 'value-set';
  }

  $text = trim((string) $value);
  return $text === '' ? 'value-null' : 'value-set';
}

function girffonUserProfileDebugRenderValue($value): string
{
  return '<span class="debug-value ' . girffonUserProfileDebugValueClass($value) . '">' . girffonUserProfileDebugDisplayValue($value) . '</span>';
}

function girffonUserProfileDebugAvatarUrl($value): string
{
  $path = trim((string) $value);
  if ($path === '') {
    return '';
  }

  if (strpos($path, '/GirffoN/') === 0) {
    return $path;
  }

  return '/GirffoN/' . ltrim($path, '/');
}

function girffonUserProfileDebugRenderFieldTable(array $fields): void
{
  echo '<table><tbody>';
  foreach ($fields as $label => $value) {
    echo '<tr>';
    echo '<th>' . girffonUserProfileDebugEscape($label) . '</th>';
    echo '<td>' . girffonUserProfileDebugRenderValue($value) . '</td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
}

function girffonUserProfileDebugFetchByEmail(PDO $pdo, string $email): ?array
{
    $availableColumns = girffonProfileTableColumns($pdo, 'users');
    if ($availableColumns === []) {
        return null;
    }

    $selectColumns = girffonProfileExistingColumns($availableColumns, [
        'id',
      'first_name',
      'last_name',
      'username',
        'email',
      'phone',
      'country',
      'city',
      'address',
      'full_address',
      'postcode',
      'postal_code',
        'date_of_birth',
        'gender',
        'preferred_language',
        'avatar',
      'role',
      'status',
      'created_at',
    ]);

    if ($selectColumns === [] || !isset($availableColumns['email'])) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT ' . implode(', ', array_unique($selectColumns)) . '
         FROM users
         WHERE LOWER(email) = LOWER(:email)
         LIMIT 1'
    );
    $statement->execute([':email' => trim($email)]);

    $user = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        return null;
    }

    if (!isset($user['address']) && isset($user['full_address'])) {
        $user['address'] = $user['full_address'];
    }

    return $user;
}

  function girffonUserProfileDebugTableExists(PDO $pdo, string $table): bool
  {
    return girffonProfileTableColumns($pdo, $table) !== [];
  }

  function girffonUserProfileDebugFetchTableRows(PDO $pdo, string $table, array $conditions, int $limit = 10, string $orderBy = ''): array
  {
    $availableColumns = girffonProfileTableColumns($pdo, $table);
    if ($availableColumns === []) {
      return [];
    }

    $clauses = [];
    $params = [];
    foreach ($conditions as $index => $condition) {
      $column = (string) ($condition['column'] ?? '');
      if ($column === '' || !isset($availableColumns[$column])) {
        continue;
      }

      $operator = strtoupper((string) ($condition['operator'] ?? '='));
      $param = ':p' . $index;
      if ($operator === 'LOWER_EQUALS') {
        $clauses[] = 'LOWER(' . $column . ') = LOWER(' . $param . ')';
      } else {
        $clauses[] = $column . ' = ' . $param;
      }
      $params[$param] = $condition['value'] ?? null;
    }

    if ($clauses === []) {
      return [];
    }

    $orderColumns = [];
    foreach (array_filter(array_map('trim', explode(',', $orderBy))) as $segment) {
      $parts = preg_split('/\s+/', $segment) ?: [];
      $column = (string) ($parts[0] ?? '');
      $direction = strtoupper((string) ($parts[1] ?? 'ASC'));
      if ($column !== '' && isset($availableColumns[$column])) {
        $orderColumns[] = $column . ' ' . ($direction === 'DESC' ? 'DESC' : 'ASC');
      }
    }

    if ($orderColumns === []) {
      if (isset($availableColumns['created_at'])) {
        $orderColumns[] = 'created_at DESC';
      } elseif (isset($availableColumns['id'])) {
        $orderColumns[] = 'id DESC';
      }
    }

    $statement = $pdo->prepare(
      'SELECT * FROM ' . $table .
      ' WHERE ' . implode(' OR ', $clauses) .
      ' ORDER BY ' . implode(', ', $orderColumns) .
      ' LIMIT ' . max(1, $limit)
    );
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  function girffonUserProfileDebugFetchLatestOrders(PDO $pdo, array $user): array
  {
    $orderColumns = girffonProfileTableColumns($pdo, 'orders');
    if ($orderColumns === []) {
      return [];
    }

    $conditions = [];
    if (!empty($user['id'])) {
      $conditions[] = ['column' => 'user_id', 'value' => (int) $user['id']];
    }
    if (!empty($user['email'])) {
      $conditions[] = ['column' => 'customer_email', 'value' => (string) $user['email'], 'operator' => 'LOWER_EQUALS'];
      $conditions[] = ['column' => 'email', 'value' => (string) $user['email'], 'operator' => 'LOWER_EQUALS'];
    }

    return girffonUserProfileDebugFetchTableRows($pdo, 'orders', $conditions, 5, 'created_at DESC, id DESC');
  }

  function girffonUserProfileDebugFetchLatestInvoices(PDO $pdo, array $user): array
  {
    $invoiceColumns = girffonProfileTableColumns($pdo, 'invoices');
    if ($invoiceColumns === []) {
      return [];
    }

    $conditions = [];
    if (!empty($user['id'])) {
      $conditions[] = ['column' => 'user_id', 'value' => (int) $user['id']];
    }

    $rows = girffonUserProfileDebugFetchTableRows($pdo, 'invoices', $conditions, 5, 'created_at DESC, id DESC');
    if ($rows !== [] || empty($user['email'])) {
      return $rows;
    }

    $orderColumns = girffonProfileTableColumns($pdo, 'orders');
    if ($orderColumns === [] || !isset($invoiceColumns['order_id']) || !isset($orderColumns['customer_email'])) {
      return [];
    }

    $statement = $pdo->prepare(
      'SELECT invoices.*, orders.order_number, orders.customer_email
       FROM invoices
       LEFT JOIN orders ON orders.id = invoices.order_id
       WHERE LOWER(orders.customer_email) = LOWER(:email)
       ORDER BY invoices.created_at DESC, invoices.id DESC
       LIMIT 5'
    );
    $statement->execute([':email' => (string) $user['email']]);

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  function girffonUserProfileDebugRenderRows(array $rows): void
  {
    if ($rows === []) {
      echo '<div class="empty"><span class="debug-value value-null">No rows found.</span></div>';
      return;
    }

    $columns = [];
    foreach ($rows as $row) {
      foreach (array_keys($row) as $column) {
        if (!in_array($column, $columns, true)) {
          $columns[] = $column;
        }
      }
    }

    echo '<div class="debug-table-wrap"><table><thead><tr>';
    foreach ($columns as $column) {
      echo '<th>' . girffonUserProfileDebugEscape($column) . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
      echo '<tr>';
      foreach ($columns as $column) {
        $value = $row[$column] ?? null;
        echo '<td>' . girffonUserProfileDebugRenderValue($value) . '</td>';
      }
      echo '</tr>';
    }

    echo '</tbody></table></div>';
  }

$debugUser = girffonUserProfileDebugFetchByEmail($pdo, GIRFFON_DEBUG_EMAIL);
$fields = [
    'id' => $debugUser['id'] ?? null,
    'first_name' => $debugUser['first_name'] ?? null,
    'last_name' => $debugUser['last_name'] ?? null,
    'username' => $debugUser['username'] ?? null,
    'email' => $debugUser['email'] ?? GIRFFON_DEBUG_EMAIL,
    'phone' => $debugUser['phone'] ?? null,
    'country' => $debugUser['country'] ?? null,
    'city' => $debugUser['city'] ?? null,
    'address' => $debugUser['address'] ?? null,
    'postal_code' => $debugUser['postal_code'] ?? ($debugUser['postcode'] ?? null),
    'date_of_birth' => $debugUser['date_of_birth'] ?? null,
    'gender' => $debugUser['gender'] ?? null,
    'preferred_language' => $debugUser['preferred_language'] ?? null,
    'avatar' => $debugUser['avatar'] ?? null,
    'role' => $debugUser['role'] ?? null,
    'status' => $debugUser['status'] ?? null,
    'created_at' => $debugUser['created_at'] ?? null,
];

  $identityFields = [
    'id' => $fields['id'],
    'first_name' => $fields['first_name'],
    'last_name' => $fields['last_name'],
    'username' => $fields['username'],
    'email' => $fields['email'],
    'role' => $fields['role'],
    'status' => $fields['status'],
    'created_at' => $fields['created_at'],
  ];

  $contactFields = [
    'phone' => $fields['phone'],
    'country' => $fields['country'],
    'city' => $fields['city'],
    'address' => $fields['address'],
    'postal_code' => $fields['postal_code'],
  ];

  $personalFields = [
    'date_of_birth' => $fields['date_of_birth'],
    'gender' => $fields['gender'],
    'preferred_language' => $fields['preferred_language'],
    'avatar' => $fields['avatar'],
  ];

  $avatarPath = (string) ($fields['avatar'] ?? '');
  $avatarUrl = girffonUserProfileDebugAvatarUrl($avatarPath);

  $userId = (int) ($debugUser['id'] ?? 0);
  $debugEmail = (string) ($debugUser['email'] ?? GIRFFON_DEBUG_EMAIL);
  $userPreferencesRows = $userId > 0 ? girffonUserProfileDebugFetchTableRows($pdo, 'user_preferences', [
    ['column' => 'user_id', 'value' => $userId],
  ], 5, 'updated_at DESC, id DESC') : [];
  $userPaymentMethodRows = $userId > 0 ? girffonUserProfileDebugFetchTableRows($pdo, 'user_payment_methods', [
    ['column' => 'user_id', 'value' => $userId],
  ], 10, 'is_primary DESC, id DESC') : [];
  $userAddressRows = $userId > 0 ? girffonUserProfileDebugFetchTableRows($pdo, 'user_addresses', [
    ['column' => 'user_id', 'value' => $userId],
  ], 10, 'is_primary DESC, id DESC') : [];
  $newsletterSubscriberRows = girffonUserProfileDebugFetchTableRows($pdo, 'newsletter_subscribers', [
    ['column' => 'user_id', 'value' => $userId],
    ['column' => 'email', 'value' => $debugEmail, 'operator' => 'LOWER_EQUALS'],
  ], 10, 'subscribed_at DESC, id DESC');
  $latestOrderRows = $debugUser ? girffonUserProfileDebugFetchLatestOrders($pdo, $debugUser) : [];
  $latestInvoiceRows = $debugUser ? girffonUserProfileDebugFetchLatestInvoices($pdo, $debugUser) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN User Profile Debug</title>
  <style>
    body {
      margin: 0;
      padding: 32px 20px;
      background: #f5f1ea;
      color: #1f1812;
      font-family: Georgia, serif;
    }

    main {
      max-width: 920px;
      margin: 0 auto;
    }

    .debug-card {
      background: #fffdf9;
      border: 1px solid #dccfbf;
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 18px;
      box-shadow: 0 16px 42px rgba(46, 28, 10, 0.08);
    }

    .debug-hero {
      display: grid;
      grid-template-columns: 140px minmax(0, 1fr);
      gap: 20px;
      align-items: center;
    }

    .debug-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid #dccfbf;
      background: #f3e7d6;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .debug-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .debug-avatar-placeholder {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 2px dashed #d65d5d;
      color: #b43333;
      background: #fff2f2;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 16px;
      box-sizing: border-box;
      font-size: 0.9rem;
      line-height: 1.35;
    }

    .debug-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 18px;
      margin-bottom: 18px;
    }

    .debug-related-stack {
      display: grid;
      gap: 18px;
    }

    h2 {
      margin: 0 0 12px;
      font-size: 1.3rem;
    }

    h1 {
      margin: 0 0 8px;
      font-size: 2rem;
    }

    p {
      margin: 0 0 18px;
      color: #6e5c4a;
    }

    .debug-meta {
      margin-bottom: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
    }

    .debug-table-wrap {
      overflow-x: auto;
    }

    th,
    td {
      border: 1px solid #ece2d5;
      padding: 12px 14px;
      text-align: left;
      vertical-align: top;
    }

    th {
      width: 220px;
      background: #f8f2e8;
      font-weight: 700;
    }

      .debug-value {
        font-weight: 600;
        word-break: break-word;
      }

      .value-null {
        color: #b43333;
      }

      .value-set {
        color: #1f1812;
      }

      @media (max-width: 720px) {
        .debug-hero {
          grid-template-columns: 1fr;
          justify-items: center;
          text-align: center;
        }
      }

    .empty {
      color: #8a7761;
    }
  </style>
        <div class="debug-hero">
          <?php if ($avatarUrl !== ''): ?>
            <div class="debug-avatar">
              <img src="<?php echo girffonUserProfileDebugEscape($avatarUrl); ?>" alt="Saved avatar preview">
            </div>
          <?php else: ?>
            <div class="debug-avatar-placeholder">No avatar saved in database</div>
          <?php endif; ?>

          <div>
            <h1>User Profile Debug</h1>
            <p class="debug-meta">Admin-only diagnostics for <strong><?php echo girffonUserProfileDebugEscape(GIRFFON_DEBUG_EMAIL); ?></strong>.</p>
          </div>
        </div>
      </section>

      <?php if ($debugUser): ?>
        <section class="debug-grid">
          <section class="debug-card">
            <h2>Identity</h2>
            <?php girffonUserProfileDebugRenderFieldTable($identityFields); ?>
          </section>

          <section class="debug-card">
            <h2>Contact</h2>
            <?php girffonUserProfileDebugRenderFieldTable($contactFields); ?>
          </section>

          <section class="debug-card">
            <h2>Personal</h2>
            <?php girffonUserProfileDebugRenderFieldTable($personalFields); ?>
          </section>
        </section>
      <?php else: ?>
        <section class="debug-card">
          <div class="empty"><span class="debug-value value-null">No user row found for <?php echo girffonUserProfileDebugEscape(GIRFFON_DEBUG_EMAIL); ?>.</span></div>
        </section>
      <?php endif; ?>

      <section class="debug-related-stack">
        <section class="debug-card">
          <h2>Related Data: user_preferences</h2>
          <?php girffonUserProfileDebugRenderRows($userPreferencesRows); ?>
        </section>

        <section class="debug-card">
          <h2>Related Data: user_payment_methods</h2>
          <?php girffonUserProfileDebugRenderRows($userPaymentMethodRows); ?>
        </section>

        <section class="debug-card">
          <h2>Related Data: user_addresses</h2>
          <?php girffonUserProfileDebugRenderRows($userAddressRows); ?>
        </section>

        <section class="debug-card">
          <h2>Related Data: newsletter_subscribers</h2>
          <?php girffonUserProfileDebugRenderRows($newsletterSubscriberRows); ?>
        </section>

        <section class="debug-card">
          <h2>Related Data: latest orders</h2>
          <?php girffonUserProfileDebugRenderRows($latestOrderRows); ?>
        </section>

        <section class="debug-card">
          <h2>Related Data: latest invoices</h2>
          <?php girffonUserProfileDebugRenderRows($latestInvoiceRows); ?>
        </section>
      </section>

    <section class="debug-card">
      <h2>Latest Invoices</h2>
      <?php girffonUserProfileDebugRenderRows($latestInvoiceRows); ?>
    </section>
  </main>
</body>
</html>
