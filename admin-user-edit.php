<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/users-data.php";

$adminUserId = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$adminEditedUser = $adminUserId > 0 ? girffonAdminFetchUserById($pdo, $adminUserId) : null;
if (!$adminEditedUser) {
  header("Location: /GirffoN/admin-users.php?error=" . rawurlencode("User not found."));
  exit;
}

$adminUserStatusMessage = trim((string) ($_GET['status'] ?? ''));
$adminUserErrorMessage = trim((string) ($_GET['error'] ?? ''));
$escapeAdminUserEdit = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$formatAdminUserEditLabel = static function ($value) use ($escapeAdminUserEdit) {
  $text = trim((string) $value);
  return $text === '' ? '-' : $escapeAdminUserEdit(ucwords(str_replace('_', ' ', $text)));
};
$adminUserFilterOptions = girffonAdminFetchUserFilterOptions($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin User Edit</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
</head>
<body class="admin-page" data-admin-page="users" data-admin-users-source="database">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>User editor connected to the live admin database.</p>
      </div>
      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link is-active" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
        <a class="admin-nav-link" href="admin-gift-cards.php" aria-label="Gift Cards" title="Gift Cards">10. Gift Cards</a>
      </nav>
      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Edit User</strong>
          <p class="admin-panel-note">Update user identity, contact, location, role, and status.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>
    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title">Edit User</h1>
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
              <h2>Edit <?php echo $escapeAdminUserEdit(trim((string) (($adminEditedUser['first_name'] ?? '') . ' ' . ($adminEditedUser['last_name'] ?? ''))) ?: ($adminEditedUser['username'] ?? 'User')); ?></h2>
              <p class="admin-panel-note">Save user changes back to the users table.</p>
            </div>
            <div class="admin-form-actions">
              <a class="admin-button admin-button-soft" href="admin-user-view.php?id=<?php echo $escapeAdminUserEdit($adminUserId); ?>">View</a>
              <a class="admin-button admin-button-soft" href="admin-users.php">Back</a>
            </div>
          </div>

          <form class="admin-grid-form" action="/GirffoN/backend/admin/save-user.php" method="POST" novalidate>
            <input type="hidden" name="id" value="<?php echo $escapeAdminUserEdit($adminUserId); ?>">

            <div class="admin-field">
              <label for="adminUserFirstName">First Name</label>
              <input class="admin-input" id="adminUserFirstName" name="first_name" type="text" value="<?php echo $escapeAdminUserEdit($adminEditedUser['first_name'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
              <label for="adminUserLastName">Last Name</label>
              <input class="admin-input" id="adminUserLastName" name="last_name" type="text" value="<?php echo $escapeAdminUserEdit($adminEditedUser['last_name'] ?? ''); ?>">
            </div>

            <div class="admin-field">
              <label for="adminUserEmail">Email</label>
              <input class="admin-input" id="adminUserEmail" name="email" type="email" value="<?php echo $escapeAdminUserEdit($adminEditedUser['email'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
              <label for="adminUserPhone">Phone</label>
              <input class="admin-input" id="adminUserPhone" name="phone" type="text" value="<?php echo $escapeAdminUserEdit($adminEditedUser['phone'] ?? ''); ?>">
            </div>

            <div class="admin-field">
              <label for="adminUserCountry">Country</label>
              <input class="admin-input" id="adminUserCountry" name="country" type="text" value="<?php echo $escapeAdminUserEdit($adminEditedUser['country'] ?? ''); ?>">
            </div>

            <div class="admin-field">
              <label for="adminUserCity">City</label>
              <input class="admin-input" id="adminUserCity" name="city" type="text" value="<?php echo $escapeAdminUserEdit($adminEditedUser['city'] ?? ''); ?>">
            </div>

            <div class="admin-field">
              <label for="adminUserRole">Role</label>
              <select class="admin-select" id="adminUserRole" name="role" required>
                <?php foreach ($adminUserFilterOptions['roles'] as $roleOption): ?>
                  <option value="<?php echo $escapeAdminUserEdit($roleOption); ?>"<?php if (($adminEditedUser['role'] ?? '') === $roleOption): ?> selected<?php endif; ?>><?php echo $formatAdminUserEditLabel($roleOption); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="admin-field">
              <label for="adminUserStatus">Status</label>
              <select class="admin-select" id="adminUserStatus" name="status" required>
                <?php foreach ($adminUserFilterOptions['statuses'] as $statusOption): ?>
                  <option value="<?php echo $escapeAdminUserEdit($statusOption); ?>"<?php if (($adminEditedUser['status'] ?? '') === $statusOption): ?> selected<?php endif; ?>><?php echo $formatAdminUserEditLabel($statusOption); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="admin-field admin-field-wide">
              <label for="adminUserAddress">Address</label>
              <textarea class="admin-textarea" id="adminUserAddress" name="address"><?php echo $escapeAdminUserEdit($adminEditedUser['address'] ?? ''); ?></textarea>
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit">Save User</button>
            </div>
            <div class="admin-feedback<?php if ($adminUserErrorMessage): ?> is-error<?php elseif ($adminUserStatusMessage): ?> is-success<?php endif; ?>" role="status" aria-live="polite"><?php echo $escapeAdminUserEdit($adminUserErrorMessage ?: $adminUserStatusMessage); ?></div>
          </form>
        </article>
      </section>
    </main>
  </div>
  <script src="JS/admin-girffon.js?v=20260505r5"></script>
</body>
</html>