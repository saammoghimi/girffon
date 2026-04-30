<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/users-data.php";

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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Users</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=<?php echo rawurlencode((string) @filemtime(__DIR__ . '/CSS/admin-girffon.css')); ?>">
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

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link is-active" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
      </nav>

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
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

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

      <section class="admin-page-section">
        <?php if ($adminUserStatusMessage || $adminUserErrorMessage): ?>
          <div class="admin-feedback<?php if ($adminUserErrorMessage): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
            <?php echo $escapeAdminUser($adminUserErrorMessage ?: $adminUserStatusMessage); ?>
          </div>
        <?php endif; ?>

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
                  <th>Username</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Country</th>
                  <th>City</th>
                  <th>Address</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Created Date</th>
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
                      <td><?php echo $escapeAdminUser($user['username'] ?? '-'); ?></td>
                      <td><?php echo $escapeAdminUser($userEmail !== '' ? $userEmail : '-'); ?></td>
                      <td><?php echo $escapeAdminUser($userPhone !== '' ? $userPhone : '-'); ?></td>
                      <td><?php echo $escapeAdminUser(($user['country'] ?? '') !== '' ? $user['country'] : '-'); ?></td>
                      <td><?php echo $escapeAdminUser(($user['city'] ?? '') !== '' ? $user['city'] : '-'); ?></td>
                      <td><?php echo $escapeAdminUser(($user['address'] ?? '') !== '' ? $user['address'] : '-'); ?></td>
                      <td><span class="admin-badge <?php echo $escapeAdminUser($resolveAdminUserBadgeClass($user['role'] ?? '', 'role')); ?>"><?php echo $formatAdminUserLabel($user['role'] ?? '-'); ?></span></td>
                      <td><span class="admin-badge <?php echo $escapeAdminUser($resolveAdminUserBadgeClass($user['status'] ?? '', 'status')); ?>"><?php echo $formatAdminUserLabel($user['status'] ?? '-'); ?></span></td>
                      <td><?php echo $formatAdminUserDate($user['created_at'] ?? ''); ?></td>
                      <td>
                        <div class="admin-table-actions admin-table-actions-menu" data-admin-user-menu>
                          <button class="admin-action-button admin-actions-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Open user actions" title="Actions">
                            <svg class="admin-actions-toggle-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                              <circle cx="12" cy="12" r="2.4"></circle>
                              <path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.7l-1 1a1.2 1.2 0 0 1-1.7 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9v.2A1.2 1.2 0 0 1 14 21h-4a1.2 1.2 0 0 1-1.2-1.2v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.7 0l-1-1a1.2 1.2 0 0 1 0-1.7l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6h-.2A1.2 1.2 0 0 1 2 14v-4a1.2 1.2 0 0 1 1.2-1.2h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1L4 7a1.2 1.2 0 0 1 0-1.7l1-1a1.2 1.2 0 0 1 1.7 0l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9v-.2A1.2 1.2 0 0 1 10 2h4a1.2 1.2 0 0 1 1.2 1.2v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.7 0l1 1a1.2 1.2 0 0 1 0 1.7l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.2A1.2 1.2 0 0 1 22 10v4a1.2 1.2 0 0 1-1.2 1.2h-.2a1 1 0 0 0-.9.6Z"></path>
                            </svg>
                          </button>
                          <div class="admin-actions-dropdown" hidden>
                            <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userViewUrl); ?>">View</a>
                            <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userEditUrl); ?>">Edit</a>
                            <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userOrdersUrl); ?>">Orders</a>
                            <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userInvoicesUrl); ?>">Invoices</a>
                            <?php if ($userHasEmail): ?>
                              <a class="admin-actions-menu-link" href="mailto:<?php echo $escapeAdminUser($userEmail); ?>">Send Email</a>
                            <?php else: ?>
                              <span class="admin-actions-menu-link is-disabled" aria-disabled="true" title="No email available">Send Email</span>
                            <?php endif; ?>
                            <?php if ($userHasPhone): ?>
                              <a class="admin-actions-menu-link" href="<?php echo $escapeAdminUser($userSmsUrl); ?>">Send SMS</a>
                            <?php else: ?>
                              <span class="admin-actions-menu-link is-disabled" aria-disabled="true" title="No phone number available">Send SMS</span>
                            <?php endif; ?>
                            <form class="admin-actions-delete-form" method="POST" action="admin-users.php">
                              <input type="hidden" name="action" value="delete-user">
                              <input type="hidden" name="id" value="<?php echo $escapeAdminUser($userId); ?>">
                              <input type="hidden" name="redirect_search" value="<?php echo $escapeAdminUser($adminUserFilters['search']); ?>">
                              <input type="hidden" name="redirect_role" value="<?php echo $escapeAdminUser($adminUserFilters['role']); ?>">
                              <input type="hidden" name="redirect_status" value="<?php echo $escapeAdminUser($adminUserFilters['status']); ?>">
                              <input type="hidden" name="redirect_country" value="<?php echo $escapeAdminUser($adminUserFilters['country']); ?>">
                              <button class="admin-actions-menu-link is-danger" type="submit" data-admin-delete-user data-user-name="<?php echo $escapeAdminUser($userFullName); ?>">Delete</button>
                            </form>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="13" class="admin-empty">No users matched the current search and filters.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js"></script>
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