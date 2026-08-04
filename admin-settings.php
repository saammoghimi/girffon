<?php require_once __DIR__ . "/backend/admin/session.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
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
        <a class="admin-nav-link" href="/GirffoN/admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="/GirffoN/admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="/GirffoN/admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="/GirffoN/admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="/GirffoN/admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="/GirffoN/admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="/GirffoN/admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link is-active" href="/GirffoN/admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
        <a class="admin-nav-link" href="/GirffoN/admin-gift-cards.php" aria-label="Gift Cards" title="Gift Cards">10. Gift Cards</a>
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
          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-setting.php" aria-label="Store Settings Workspace" title="Open Store Settings Workspace">Store Settings Workspace</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Workspace Visibility</h2>
              <p class="admin-panel-note">Use Store Settings Workspace to decide which fields remain visible in this page for your current browser session.</p>
            </div>
            <a class="admin-button admin-button-soft" href="setting-setting.php">Open Workspace Controls</a>
          </div>
          <div id="adminSettingsWorkspaceNotice" class="admin-feedback" role="status" aria-live="polite"></div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Store Settings</h2>
              <p class="admin-panel-note">Save default store information directly in localStorage for this admin demo.</p>
            </div>
          </div>

          <form id="adminSettingsForm" class="admin-grid-form" novalidate>
            <div class="admin-field" data-store-setting-field="storeName">
              <label for="adminSettingStoreName">Store Name</label>
              <input class="admin-input" id="adminSettingStoreName" name="storeName" type="text" required>
            </div>

            <div class="admin-field" data-store-setting-field="storeEmail">
              <label for="adminSettingStoreEmail">Store Email</label>
              <input class="admin-input" id="adminSettingStoreEmail" name="storeEmail" type="email" required>
            </div>

            <div class="admin-field" data-store-setting-field="country">
              <label for="adminSettingCountry">Country</label>
              <input class="admin-input" id="adminSettingCountry" name="country" type="text" required>
            </div>

            <div class="admin-field" data-store-setting-field="currency">
              <label for="adminSettingCurrency">Currency</label>
              <input class="admin-input" id="adminSettingCurrency" name="currency" type="text" required>
            </div>

            <div class="admin-field" data-store-setting-field="taxRate">
              <label for="adminSettingTaxRate">Tax Rate</label>
              <input class="admin-input" id="adminSettingTaxRate" name="taxRate" type="number" min="0" step="0.01" required>
            </div>

            <div class="admin-field" data-store-setting-field="shippingCost">
              <label for="adminSettingShippingCost">Shipping Cost</label>
              <input class="admin-input" id="adminSettingShippingCost" name="shippingCost" type="number" min="0" step="0.01" required>
            </div>

            <div class="admin-field admin-field-wide" data-store-setting-field="returnPolicyText">
              <label for="adminSettingReturnPolicyText">Return Policy Text</label>
              <textarea class="admin-textarea" id="adminSettingReturnPolicyText" name="returnPolicyText" required></textarea>
            </div>

            <div class="admin-field admin-field-wide" data-store-setting-field="supportEmail">
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
  <script>
    (function () {
      const workspaceKey = "girffon_admin_settings_workspace";
      const defaults = {
        storeName: true,
        storeEmail: true,
        country: true,
        currency: true,
        taxRate: true,
        shippingCost: true,
        returnPolicyText: true,
        supportEmail: true
      };

      const readWorkspacePreferences = function () {
        try {
          const raw = localStorage.getItem(workspaceKey);
          const parsed = raw ? JSON.parse(raw) : {};
          return Object.keys(defaults).reduce(function (result, key) {
            result[key] = Object.prototype.hasOwnProperty.call(parsed, key) ? Boolean(parsed[key]) : defaults[key];
            return result;
          }, {});
        } catch (_error) {
          return { ...defaults };
        }
      };

      const preferences = readWorkspacePreferences();
      const fields = document.querySelectorAll("[data-store-setting-field]");
      let visibleCount = 0;

      fields.forEach(function (field) {
        const key = String(field.getAttribute("data-store-setting-field") || "").trim();
        const isVisible = key !== "" ? preferences[key] !== false : true;
        field.hidden = !isVisible;
        if (isVisible) {
          visibleCount += 1;
        }
      });

      const notice = document.getElementById("adminSettingsWorkspaceNotice");
      if (notice) {
        notice.textContent = visibleCount > 0
          ? visibleCount + " fields are visible in this Store Settings workspace."
          : "All Store Settings fields are hidden. Open Store Settings Workspace to turn sections back on.";
      }
    }());
  </script>
</body>
</html>