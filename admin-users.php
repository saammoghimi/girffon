<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/users-data.php";

$adminUserSettingsFile = __DIR__ . "/backend/admin/user-settings-data.php";
if (is_file($adminUserSettingsFile)) {
  require_once $adminUserSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));

$buildAdminUsersRedirectUrl = static function (array $params = []): string {
  $baseUrl = '/GirffoN/admin-users.php';
  if (!$params) {
    return $baseUrl;
  }

  return $baseUrl . '?' . http_build_query($params);
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $action = trim((string) ($_POST['action'] ?? ''));

  if ($action === 'delete-user') {
    $userId = max(0, (int) ($_POST['id'] ?? 0));
    $redirectParams = [
      'search' => trim((string) ($_POST['redirect_search'] ?? '')),
      'role' => trim((string) ($_POST['redirect_role'] ?? '')),
      'status' => trim((string) ($_POST['redirect_status'] ?? '')),
      'country' => trim((string) ($_POST['redirect_country'] ?? '')),
    ];

    if ($userId <= 0) {
      $redirectParams['error'] = 'Invalid user selected.';
      header('Location: ' . $buildAdminUsersRedirectUrl(array_filter($redirectParams, static function ($value) {
        return trim((string) $value) !== '';
      })));
      exit;
    }

    $existingUser = girffonAdminFetchUserById($pdo, $userId);
    if (!$existingUser) {
      $redirectParams['error'] = 'User not found.';
      header('Location: ' . $buildAdminUsersRedirectUrl(array_filter($redirectParams, static function ($value) {
        return trim((string) $value) !== '';
      })));
      exit;
    }

    try {
      $deleted = girffonAdminDeleteUser($pdo, $userId);
      $redirectParams['notice'] = $deleted ? 'User deleted successfully.' : 'User could not be deleted.';
      if (!$deleted) {
        unset($redirectParams['notice']);
        $redirectParams['error'] = 'User could not be deleted.';
      }
    } catch (PDOException $exception) {
      $redirectParams['error'] = 'Unable to delete the user right now.';
    }

    header('Location: ' . $buildAdminUsersRedirectUrl(array_filter($redirectParams, static function ($value) {
      return trim((string) $value) !== '';
    })));
    exit;
  }
}

$adminUserFilters = [
  'search' => trim((string) ($_GET['search'] ?? '')),
  'role' => trim((string) ($_GET['role'] ?? '')),
  'status' => trim((string) ($_GET['status'] ?? '')),
  'country' => trim((string) ($_GET['country'] ?? '')),
];

$adminUsers = girffonAdminFetchUsers($pdo, $adminUserFilters);
$adminUserFilterOptions = girffonAdminFetchUserFilterOptions($pdo);
$adminTotalMembers = girffonAdminCountMembers($pdo);
$adminActiveMembers = girffonAdminCountActiveMembers($pdo);
$adminAdminUsers = girffonAdminCountAdminUsers($pdo);
$adminNewMembersThisMonth = girffonAdminCountNewMembersThisMonth($pdo);
$adminUserStatusMessage = trim((string) ($_GET['notice'] ?? ''));
$adminUserErrorMessage = trim((string) ($_GET['error'] ?? ''));
$adminUserPreferences = [
  'show_summary_cards' => true,
  'show_filter_panel' => true,
  'show_users_directory' => true,
  'show_username_column' => true,
  'show_email_column' => true,
  'show_phone_column' => true,
  'show_country_column' => true,
  'show_city_column' => true,
  'show_address_column' => true,
  'show_role_column' => true,
  'show_status_column' => true,
  'show_created_at_column' => true,
  'show_view_action' => true,
  'show_edit_action' => true,
  'show_orders_action' => true,
  'show_invoices_action' => true,
  'show_email_action' => true,
  'show_sms_action' => true,
  'show_delete_action' => true,
];

if (function_exists('girffonAdminFetchUserPreferences')) {
  $adminUserPreferences = girffonAdminFetchUserPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$showAdminUserSummaryCards = !empty($adminUserPreferences['show_summary_cards']);
$showAdminUserFilterPanel = !empty($adminUserPreferences['show_filter_panel']);
$showAdminUsersDirectory = !empty($adminUserPreferences['show_users_directory']);
$showAdminUserUsernameColumn = !empty($adminUserPreferences['show_username_column']);
$showAdminUserEmailColumn = !empty($adminUserPreferences['show_email_column']);
$showAdminUserPhoneColumn = !empty($adminUserPreferences['show_phone_column']);
$showAdminUserCountryColumn = !empty($adminUserPreferences['show_country_column']);
$showAdminUserCityColumn = !empty($adminUserPreferences['show_city_column']);
$showAdminUserAddressColumn = !empty($adminUserPreferences['show_address_column']);
$showAdminUserRoleColumn = !empty($adminUserPreferences['show_role_column']);
$showAdminUserStatusColumn = !empty($adminUserPreferences['show_status_column']);
$showAdminUserCreatedAtColumn = !empty($adminUserPreferences['show_created_at_column']);
$showAdminUserViewAction = !empty($adminUserPreferences['show_view_action']);
$showAdminUserEditAction = !empty($adminUserPreferences['show_edit_action']);
$showAdminUserOrdersAction = !empty($adminUserPreferences['show_orders_action']);
$showAdminUserInvoicesAction = !empty($adminUserPreferences['show_invoices_action']);
$showAdminUserEmailAction = !empty($adminUserPreferences['show_email_action']);
$showAdminUserSmsAction = !empty($adminUserPreferences['show_sms_action']);
$showAdminUserDeleteAction = !empty($adminUserPreferences['show_delete_action']);
$adminUserVisibleColumnCount = 3
  + ($showAdminUserUsernameColumn ? 1 : 0)
  + ($showAdminUserEmailColumn ? 1 : 0)
  + ($showAdminUserPhoneColumn ? 1 : 0)
  + ($showAdminUserCountryColumn ? 1 : 0)
  + ($showAdminUserCityColumn ? 1 : 0)
  + ($showAdminUserAddressColumn ? 1 : 0)
  + ($showAdminUserRoleColumn ? 1 : 0)
  + ($showAdminUserStatusColumn ? 1 : 0)
  + ($showAdminUserCreatedAtColumn ? 1 : 0)
  + 1;

$escapeAdminUser = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$formatAdminUserLabel = static function ($value) use ($escapeAdminUser) {
  $text = trim((string) $value);
  if ($text === '') {
    return '-';
  }

  return $escapeAdminUser(ucwords(str_replace('_', ' ', $text)));
};
$formatAdminUserDate = static function ($value) use ($escapeAdminUser) {
  $text = trim((string) $value);
  if ($text === '') {
    return '-';
  }

  $timestamp = strtotime($text);
  return $timestamp ? $escapeAdminUser(date('Y-m-d H:i', $timestamp)) : $escapeAdminUser($text);
};
$resolveAdminUserBadgeClass = static function ($value, $type = 'status') {
  $normalized = strtolower(trim((string) $value));

  if ($type === 'role') {
    if ($normalized === 'admin') {
      return 'is-warning';
    }

    if ($normalized === 'customer') {
      return 'is-success';
    }

    return 'is-neutral';
  }

  if (in_array($normalized, ['active', 'paid', 'delivered', 'read'], true)) {
    return 'is-success';
  }

  if (in_array($normalized, ['inactive', 'suspended', 'blocked', 'failed', 'cancelled'], true)) {
    return 'is-danger';
  }

  return 'is-warning';
};
$buildAdminUserFullName = static function (array $user) {
  $fullName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
  if ($fullName !== '') {
    return $fullName;
  }

  return trim((string) ($user['username'] ?? '')) ?: trim((string) ($user['email'] ?? 'Unknown User'));
};
$buildAdminUserInitials = static function (array $user) use ($buildAdminUserFullName) {
  $name = trim($buildAdminUserFullName($user));
  if ($name === '') {
    return 'U';
  }

  $parts = preg_split('/\s+/', $name) ?: [];
  $initials = '';

  foreach (array_slice($parts, 0, 2) as $part) {
    $initials .= strtoupper(substr((string) $part, 0, 1));
  }

  return $initials !== '' ? $initials : 'U';
};
$activeFilterSummary = array_filter($adminUserFilters, static function ($value) {
  return trim((string) $value) !== '';
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Users</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    @media (max-width: 1280px) and (min-width: 721px) {
      .admin-main,
      .admin-page-section,
      .admin-page-section > .admin-panel,
      .admin-page-section > .admin-table-panel,
      .admin-grid-form,
      .admin-field,
      .admin-field-wide,
      .admin-card-grid,
      .admin-table-wrap {
        min-width: 0;
      }

      .admin-main {
        max-width: 100%;
        overflow-x: hidden;
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

      .admin-card-grid,
      .admin-grid-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 720px) {
      .admin-topbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 14px;
      }

      .admin-topbar-actions {
        width: auto !important;
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 10px !important;
        justify-content: flex-end !important;
        align-self: flex-end !important;
        margin-left: auto !important;
      }

      .admin-topbar-actions .admin-button {
        position: relative;
        flex: 0 0 48px;
        width: 48px !important;
        min-width: 48px !important;
        height: 48px;
        min-height: 48px;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        color: transparent !important;
        font-size: 0 !important;
        line-height: 0;
        overflow: visible;
        white-space: nowrap;
      }

      .admin-topbar-actions .admin-button::before {
        content: "";
        width: 18px;
        height: 18px;
        background-repeat: no-repeat;
        background-position: center;
        background-size: 18px 18px;
      }

      .admin-view-shop-button::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 11.5 12 5l8.5 6.5'/%3E%3Cpath d='M6.5 10.5V19h11v-8.5'/%3E%3Cpath d='M10 19v-4.5h4V19'/%3E%3C/svg%3E");
      }

      .admin-refresh-button::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 2v6h-6'/%3E%3Cpath d='M3 22v-6h6'/%3E%3Cpath d='M20.49 9A9 9 0 0 0 5.64 5.64L3 8'/%3E%3Cpath d='M3.51 15A9 9 0 0 0 18.36 18.36L21 16'/%3E%3C/svg%3E");
      }

      .admin-settings-button::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='3.1'/%3E%3Cpath d='M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.7l-1 1a1.2 1.2 0 0 1-1.7 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9v.3a1.2 1.2 0 0 1-1.2 1.2h-1.4a1.2 1.2 0 0 1-1.2-1.2v-.2a1 1 0 0 0-.7-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.7 0l-1-1a1.2 1.2 0 0 1 0-1.7l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6h-.3A1.2 1.2 0 0 1 3 13.4V12a1.2 1.2 0 0 1 1.2-1.2h.2a1 1 0 0 0 .9-.7 1 1 0 0 0-.2-1.1L5 8.9a1.2 1.2 0 0 1 0-1.7l1-1a1.2 1.2 0 0 1 1.7 0l.1.1a1 1 0 0 0 1.1.2h.1a1 1 0 0 0 .6-.9v-.3A1.2 1.2 0 0 1 10.8 4h1.4a1.2 1.2 0 0 1 1.2 1.2v.2a1 1 0 0 0 .7.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.7 0l1 1a1.2 1.2 0 0 1 0 1.7l-.1.1a1 1 0 0 0-.2 1.1v.1a1 1 0 0 0 .9.6h.3A1.2 1.2 0 0 1 21 12v1.4a1.2 1.2 0 0 1-1.2 1.2h-.2a1 1 0 0 0-.9.4Z'/%3E%3C/svg%3E");
      }

      .admin-topbar-logout-button::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23b63a3a' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M10 6H7.5A1.5 1.5 0 0 0 6 7.5v9A1.5 1.5 0 0 0 7.5 18H10'/%3E%3Cpath d='M14 8l4 4-4 4'/%3E%3Cpath d='M18 12H10'/%3E%3C/svg%3E");
      }
    }

    @media (max-width: 520px) {
      .admin-main,
      .admin-page-section,
      .admin-page-section > .admin-panel,
      .admin-page-section > .admin-table-panel,
      .admin-grid-form,
      .admin-field,
      .admin-field-wide,
      .admin-card-grid {
        min-width: 0;
      }

      .admin-main {
        overflow-x: hidden;
      }

      .admin-input,
      .admin-select {
        min-width: 0;
        padding: 12px 13px;
      }

      .admin-panel-head h2 {
        font-size: 1rem;
      }

      .admin-inline-note,
      .admin-panel-note,
      .admin-feedback,
      .admin-table td,
      .admin-table td strong {
        overflow-wrap: anywhere;
      }

      .admin-stat-card {
        min-height: 0;
        padding: 16px 16px 18px;
      }

      .admin-stat-card strong {
        font-size: clamp(1.7rem, 8vw, 2.2rem);
      }

      .admin-order-thumb {
        width: 42px;
        height: 42px;
      }

      .admin-actions-toggle {
        width: 38px;
        min-width: 38px;
        height: 38px;
      }

      .admin-actions-dropdown {
        min-width: 168px;
      }

      .admin-table {
        min-width: 760px;
      }

      .admin-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
      }
    }

    @media (max-width: 420px) {
      .admin-main {
        padding-left: 10px !important;
        padding-right: 10px !important;
      }

      .admin-panel,
      .admin-table-panel,
      .admin-stat-card {
        padding: 14px 12px !important;
      }

      .admin-topbar-actions {
        gap: 8px !important;
      }

      .admin-topbar-actions .admin-button {
        flex: 0 0 44px;
        width: 44px !important;
        min-width: 44px !important;
        height: 44px;
        min-height: 44px;
      }

      .admin-table {
        min-width: 720px;
      }

      .admin-table-wrap {
        border-radius: 16px;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="users" data-admin-users-source="database">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Users directory connected to the live admin database.</p>
      </div>

      <?php
      $adminNavCurrentPage = 'users';
      $adminNavBasePath = '';
      require __DIR__ . '/includes/admin-nav.php';
      ?>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>User Fields</strong>
          <p class="admin-panel-note">Identity, contact details, location, role, and status.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Users</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-users.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <?php if ($showAdminUserSummaryCards): ?>
      <section class="admin-card-grid" aria-label="User summary cards">
        <article class="admin-stat-card">
          <span>Total Members</span>
          <strong><?php echo $escapeAdminUser($adminTotalMembers); ?></strong>
          <p class="admin-status">Customer accounts with role set to customer.</p>
        </article>
        <article class="admin-stat-card">
          <span>Active Members</span>
          <strong><?php echo $escapeAdminUser($adminActiveMembers); ?></strong>
          <p class="admin-status">Customer accounts currently marked active.</p>
        </article>
        <article class="admin-stat-card">
          <span>Admin Users</span>
          <strong><?php echo $escapeAdminUser($adminAdminUsers); ?></strong>
          <p class="admin-status">Administrative accounts with elevated access.</p>
        </article>
        <article class="admin-stat-card">
          <span>New This Month</span>
          <strong><?php echo $escapeAdminUser($adminNewMembersThisMonth); ?></strong>
          <p class="admin-status">New customer registrations created this month.</p>
        </article>
      </section>
      <?php endif; ?>

      <section class="admin-page-section">
        <?php if ($adminUserStatusMessage || $adminUserErrorMessage): ?>
          <div class="admin-feedback<?php if ($adminUserErrorMessage): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
            <?php echo $escapeAdminUser($adminUserErrorMessage ?: $adminUserStatusMessage); ?>
          </div>
        <?php endif; ?>

        <?php if ($showAdminUserFilterPanel): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>User Filters</h2>
              <p class="admin-panel-note">Search and refine the members list by identity and location.</p>
            </div>
          </div>

          <form class="admin-grid-form" method="GET" action="admin-users.php" novalidate>
            <div class="admin-field admin-field-wide">
              <label for="adminUsersSearch">Search</label>
              <input class="admin-input" id="adminUsersSearch" name="search" type="text" placeholder="Search by name, email, username, or phone" value="<?php echo $escapeAdminUser($adminUserFilters['search']); ?>">
            </div>

            <div class="admin-field">
              <label for="adminUsersRoleFilter">Role</label>
              <select class="admin-select" id="adminUsersRoleFilter" name="role">
                <option value="">All roles</option>
                <?php foreach ($adminUserFilterOptions['roles'] as $roleOption): ?>
                  <option value="<?php echo $escapeAdminUser($roleOption); ?>"<?php if ($adminUserFilters['role'] === $roleOption): ?> selected<?php endif; ?>><?php echo $formatAdminUserLabel($roleOption); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="admin-field">
              <label for="adminUsersStatusFilter">Status</label>
              <select class="admin-select" id="adminUsersStatusFilter" name="status">
                <option value="">All statuses</option>
                <?php foreach ($adminUserFilterOptions['statuses'] as $statusOption): ?>
                  <option value="<?php echo $escapeAdminUser($statusOption); ?>"<?php if ($adminUserFilters['status'] === $statusOption): ?> selected<?php endif; ?>><?php echo $formatAdminUserLabel($statusOption); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="admin-field">
              <label for="adminUsersCountryFilter">Country</label>
              <select class="admin-select" id="adminUsersCountryFilter" name="country">
                <option value="">All countries</option>
                <?php foreach ($adminUserFilterOptions['countries'] as $countryOption): ?>
                  <option value="<?php echo $escapeAdminUser($countryOption); ?>"<?php if ($adminUserFilters['country'] === $countryOption): ?> selected<?php endif; ?>><?php echo $escapeAdminUser($countryOption); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit">Apply Filters</button>
              <a class="admin-button admin-button-soft" href="admin-users.php">Reset</a>
            </div>
          </form>
        </article>
        <?php endif; ?>

        <p class="admin-inline-note">
          <?php echo $escapeAdminUser(count($adminUsers)); ?> user record<?php echo count($adminUsers) === 1 ? '' : 's'; ?> shown.
          <?php if ($activeFilterSummary): ?>
            Filters active: <?php echo $escapeAdminUser(implode(', ', array_map(static function ($value) {
              return trim((string) $value);
            }, $activeFilterSummary))); ?>.
          <?php else: ?>
            No filters applied.
          <?php endif; ?>
        </p>
      </section>

      <?php if ($showAdminUsersDirectory): ?>
      <section class="admin-page-section">
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Users Directory</h2>
              <p class="admin-panel-note">Live database listing of GirffoN members and administrators.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Avatar</th>
                  <th>ID</th>
                  <th>Full Name</th>
                  <?php if ($showAdminUserUsernameColumn): ?><th>Username</th><?php endif; ?>
                  <?php if ($showAdminUserEmailColumn): ?><th>Email</th><?php endif; ?>
                  <?php if ($showAdminUserPhoneColumn): ?><th>Phone</th><?php endif; ?>
                  <?php if ($showAdminUserCountryColumn): ?><th>Country</th><?php endif; ?>
                  <?php if ($showAdminUserCityColumn): ?><th>City</th><?php endif; ?>
                  <?php if ($showAdminUserAddressColumn): ?><th>Address</th><?php endif; ?>
                  <?php if ($showAdminUserRoleColumn): ?><th>Role</th><?php endif; ?>
                  <?php if ($showAdminUserStatusColumn): ?><th>Status</th><?php endif; ?>
                  <?php if ($showAdminUserCreatedAtColumn): ?><th>Created Date</th><?php endif; ?>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($adminUsers): ?>
                  <?php foreach ($adminUsers as $user): ?>
                    <?php
                      $userId = (int) ($user['id'] ?? 0);
                      $userFullName = $buildAdminUserFullName($user);
                      $userEmail = trim((string) ($user['email'] ?? ''));
                      $userPhone = trim((string) ($user['phone'] ?? ''));
                      $userViewUrl = 'admin-user-view.php?id=' . rawurlencode((string) $userId);
                      $userEditUrl = 'admin-user-edit.php?id=' . rawurlencode((string) $userId);
                      $userOrdersUrl = 'admin-orders.php?user_id=' . rawurlencode((string) $userId);
                      $userInvoicesUrl = 'admin-invoices.php?user_id=' . rawurlencode((string) $userId);
                      $userHasEmail = $userEmail !== '' && filter_var($userEmail, FILTER_VALIDATE_EMAIL);
                      $userHasPhone = $userPhone !== '';
                      $userSmsUrl = $userHasPhone ? 'sms:' . rawurlencode($userPhone) : '';
                    ?>
                    <tr id="user-<?php echo $escapeAdminUser($userId); ?>">
                      <td>
                        <div class="admin-order-thumb admin-order-thumb-placeholder"><?php echo $escapeAdminUser($buildAdminUserInitials($user)); ?></div>
                      </td>
                      <td><strong><?php echo $escapeAdminUser($userId); ?></strong></td>
                      <td><strong><?php echo $escapeAdminUser($userFullName); ?></strong></td>
                      <?php if ($showAdminUserUsernameColumn): ?><td><?php echo $escapeAdminUser($user['username'] ?? '-'); ?></td><?php endif; ?>
                      <?php if ($showAdminUserEmailColumn): ?><td><?php echo $escapeAdminUser($userEmail !== '' ? $userEmail : '-'); ?></td><?php endif; ?>
                      <?php if ($showAdminUserPhoneColumn): ?><td><?php echo $escapeAdminUser($userPhone !== '' ? $userPhone : '-'); ?></td><?php endif; ?>
                      <?php if ($showAdminUserCountryColumn): ?><td><?php echo $escapeAdminUser(($user['country'] ?? '') !== '' ? $user['country'] : '-'); ?></td><?php endif; ?>
                      <?php if ($showAdminUserCityColumn): ?><td><?php echo $escapeAdminUser(($user['city'] ?? '') !== '' ? $user['city'] : '-'); ?></td><?php endif; ?>
                      <?php if ($showAdminUserAddressColumn): ?><td><?php echo $escapeAdminUser(($user['address'] ?? '') !== '' ? $user['address'] : '-'); ?></td><?php endif; ?>
                      <?php if ($showAdminUserRoleColumn): ?><td><span class="admin-badge <?php echo $escapeAdminUser($resolveAdminUserBadgeClass($user['role'] ?? '', 'role')); ?>"><?php echo $formatAdminUserLabel($user['role'] ?? '-'); ?></span></td><?php endif; ?>
                      <?php if ($showAdminUserStatusColumn): ?><td><span class="admin-badge <?php echo $escapeAdminUser($resolveAdminUserBadgeClass($user['status'] ?? '', 'status')); ?>"><?php echo $formatAdminUserLabel($user['status'] ?? '-'); ?></span></td><?php endif; ?>
                      <?php if ($showAdminUserCreatedAtColumn): ?><td><?php echo $formatAdminUserDate($user['created_at'] ?? ''); ?></td><?php endif; ?>
                      <td>
                        <?php if ($showAdminUserViewAction || $showAdminUserEditAction || $showAdminUserOrdersAction || $showAdminUserInvoicesAction || $showAdminUserEmailAction || $showAdminUserSmsAction || $showAdminUserDeleteAction): ?>
                        <div class="admin-table-actions admin-table-actions-menu" data-admin-user-menu>
                          <button class="admin-action-button admin-actions-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Open user actions" title="Actions">
                            <svg class="admin-actions-toggle-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                              <circle cx="12" cy="12" r="2.4"></circle>
                              <path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.7l-1 1a1.2 1.2 0 0 1-1.7 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9v.2A1.2 1.2 0 0 1 14 21h-4a1.2 1.2 0 0 1-1.2-1.2v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.7 0l-1-1a1.2 1.2 0 0 1 0-1.7l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6h-.2A1.2 1.2 0 0 1 2 14v-4a1.2 1.2 0 0 1 1.2-1.2h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1L4 7a1.2 1.2 0 0 1 0-1.7l1-1a1.2 1.2 0 0 1 1.7 0l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9v-.2A1.2 1.2 0 0 1 10 2h4a1.2 1.2 0 0 1 1.2 1.2v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.7 0l1 1a1.2 1.2 0 0 1 0 1.7l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.2A1.2 1.2 0 0 1 22 10v4a1.2 1.2 0 0 1-1.2 1.2h-.2a1 1 0 0 0-.9.6Z"></path>
                            </svg>
                          </button>
                          <div class="admin-actions-dropdown" hidden>
                            <?php if ($showAdminUserViewAction): ?>
                            <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userViewUrl); ?>">View</a>
                            <?php endif; ?>
                            <?php if ($showAdminUserEditAction): ?>
                            <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userEditUrl); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if ($showAdminUserOrdersAction): ?>
                            <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userOrdersUrl); ?>">Orders</a>
                            <?php endif; ?>
                            <?php if ($showAdminUserInvoicesAction): ?>
                            <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userInvoicesUrl); ?>">Invoices</a>
                            <?php endif; ?>
                            <?php if ($showAdminUserEmailAction): ?>
                            <?php if ($userHasEmail): ?>
                              <a class="admin-actions-menu-link" href="mailto:<?php echo $escapeAdminUser($userEmail); ?>">Send Email</a>
                            <?php else: ?>
                              <span class="admin-actions-menu-link is-disabled" aria-disabled="true" title="No email available">Send Email</span>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($showAdminUserSmsAction): ?>
                            <?php if ($userHasPhone): ?>
                              <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userSmsUrl); ?>">Send SMS</a>
                            <?php else: ?>
                              <span class="admin-actions-menu-link is-disabled" aria-disabled="true" title="No phone number available">Send SMS</span>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($showAdminUserDeleteAction): ?>
                            <form class="admin-actions-delete-form" method="POST" action="admin-users.php">
                              <input type="hidden" name="action" value="delete-user">
                              <input type="hidden" name="id" value="<?php echo $escapeAdminUser($userId); ?>">
                              <input type="hidden" name="redirect_search" value="<?php echo $escapeAdminUser($adminUserFilters['search']); ?>">
                              <input type="hidden" name="redirect_role" value="<?php echo $escapeAdminUser($adminUserFilters['role']); ?>">
                              <input type="hidden" name="redirect_status" value="<?php echo $escapeAdminUser($adminUserFilters['status']); ?>">
                              <input type="hidden" name="redirect_country" value="<?php echo $escapeAdminUser($adminUserFilters['country']); ?>">
                              <button class="admin-actions-menu-link is-danger" type="submit" data-admin-delete-user data-user-name="<?php echo $escapeAdminUser($userFullName); ?>">Delete</button>
                            </form>
                            <?php endif; ?>
                          </div>
                        </div>
                        <?php else: ?>
                          <span class="admin-panel-note">Locked</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="<?php echo $escapeAdminUser($adminUserVisibleColumnCount); ?>" class="admin-empty">No users matched the current search and filters.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>
      <?php endif; ?>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const actionMenus = Array.from(document.querySelectorAll("[data-admin-user-menu]"));

      if (!actionMenus.length) {
        return;
      }

      const closeMenu = function (menuWrap) {
        const toggle = menuWrap.querySelector(".admin-actions-toggle");
        const dropdown = menuWrap.querySelector(".admin-actions-dropdown");

        if (!toggle || !dropdown) {
          return;
        }

        toggle.setAttribute("aria-expanded", "false");
        menuWrap.classList.remove("is-open");
        dropdown.hidden = true;
      };

      const openMenu = function (menuWrap) {
        actionMenus.forEach(function (item) {
          if (item !== menuWrap) {
            closeMenu(item);
          }
        });

        const toggle = menuWrap.querySelector(".admin-actions-toggle");
        const dropdown = menuWrap.querySelector(".admin-actions-dropdown");

        if (!toggle || !dropdown) {
          return;
        }

        toggle.setAttribute("aria-expanded", "true");
        menuWrap.classList.add("is-open");
        dropdown.hidden = false;
      };

      actionMenus.forEach(function (menuWrap) {
        const toggle = menuWrap.querySelector(".admin-actions-toggle");
        const dropdown = menuWrap.querySelector(".admin-actions-dropdown");

        if (!toggle || !dropdown) {
          return;
        }

        toggle.addEventListener("click", function (event) {
          event.preventDefault();
          event.stopPropagation();

          if (menuWrap.classList.contains("is-open")) {
            closeMenu(menuWrap);
            return;
          }

          openMenu(menuWrap);
        });

        dropdown.addEventListener("click", function () {
          closeMenu(menuWrap);
        });
      });

      document.addEventListener("click", function (event) {
        actionMenus.forEach(function (menuWrap) {
          if (!menuWrap.contains(event.target)) {
            closeMenu(menuWrap);
          }
        });
      });

      document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") {
          return;
        }

        actionMenus.forEach(closeMenu);
      });

      document.addEventListener("submit", function (event) {
        const deleteForm = event.target.closest(".admin-actions-delete-form");
        if (!deleteForm) {
          return;
        }

        const trigger = deleteForm.querySelector("[data-admin-delete-user]");
        const userName = trigger ? (trigger.getAttribute("data-user-name") || "this user") : "this user";
        if (!window.confirm("Delete " + userName + "? This action cannot be undone.")) {
          event.preventDefault();
        }
      });
    });
  </script>
</body>
</html>