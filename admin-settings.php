<?php require_once __DIR__ . "/backend/admin/session.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260511r15">
</head>
<body class="admin-page" data-admin-page="settings">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Store settings and business defaults for the admin panel.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard"><span class="admin-nav-link-index">1. </span><span class="admin-nav-link-label">Dashboard</span></a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products"><span class="admin-nav-link-index">2. </span><span class="admin-nav-link-label">Products</span></a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders"><span class="admin-nav-link-index">3. </span><span class="admin-nav-link-label">Orders</span></a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices"><span class="admin-nav-link-index">4. </span><span class="admin-nav-link-label">Invoices</span></a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages"><span class="admin-nav-link-index">5. </span><span class="admin-nav-link-label">Messages</span></a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users"><span class="admin-nav-link-index">6. </span><span class="admin-nav-link-label">Users</span></a>
        <a class="admin-nav-link is-active" href="admin-settings.php" aria-label="Settings" title="Settings"><span class="admin-nav-link-index">7. </span><span class="admin-nav-link-label">Settings</span></a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Store Settings</strong>
          <p class="admin-panel-note">Business contact, pricing defaults, and return policy text.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Settings</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Store Settings</h2>
              <p class="admin-panel-note">Save default store information directly in localStorage for this admin demo.</p>
            </div>
          </div>

          <form id="adminSettingsForm" class="admin-grid-form" novalidate>
            <div class="admin-field">
              <label for="adminSettingStoreName">Store Name</label>
              <input class="admin-input" id="adminSettingStoreName" name="storeName" type="text" required>
            </div>

            <div class="admin-field">
              <label for="adminSettingStoreEmail">Store Email</label>
              <input class="admin-input" id="adminSettingStoreEmail" name="storeEmail" type="email" required>
            </div>

            <div class="admin-field">
              <label for="adminSettingCountry">Country</label>
              <input class="admin-input" id="adminSettingCountry" name="country" type="text" required>
            </div>

            <div class="admin-field">
              <label for="adminSettingCurrency">Currency</label>
              <input class="admin-input" id="adminSettingCurrency" name="currency" type="text" required>
            </div>

            <div class="admin-field">
              <label for="adminSettingTaxRate">Tax Rate</label>
              <input class="admin-input" id="adminSettingTaxRate" name="taxRate" type="number" min="0" step="0.01" required>
            </div>

            <div class="admin-field">
              <label for="adminSettingShippingCost">Shipping Cost</label>
              <input class="admin-input" id="adminSettingShippingCost" name="shippingCost" type="number" min="0" step="0.01" required>
            </div>

            <div class="admin-field admin-field-wide">
              <label for="adminSettingReturnPolicyText">Return Policy Text</label>
              <textarea class="admin-textarea" id="adminSettingReturnPolicyText" name="returnPolicyText" required></textarea>
            </div>

            <div class="admin-field admin-field-wide">
              <label for="adminSettingSupportEmail">Support Email</label>
              <input class="admin-input" id="adminSettingSupportEmail" name="supportEmail" type="email" required>
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit">Save Settings</button>
            </div>
            <div id="adminSettingsStatus" class="admin-feedback" role="status" aria-live="polite"></div>
          </form>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260505r5"></script>
</body>
</html>