<?php require_once __DIR__ . "/backend/admin/session.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260917r12">
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

      <?php
      $adminNavCurrentPage = 'settings';
      $adminNavBasePath = '';
      require __DIR__ . '/includes/admin-nav.php';
      ?>

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

        <article class="admin-panel" id="adminAiSettingsPanel">
          <div class="admin-panel-head">
            <div>
              <h2>GIRFFON AI Settings</h2>
              <p class="admin-panel-note">Control customer access, package values, and generation availability.</p>
            </div>
          </div>

          <form id="adminAiSettingsForm" class="admin-grid-form" novalidate>
            <div class="admin-field">
              <label for="adminAiFeatureEnabled">AI Feature</label>
              <select class="admin-input" id="adminAiFeatureEnabled" name="feature_enabled">
                <option value="1">ON</option>
                <option value="0">OFF</option>
              </select>
            </div>

            <div class="admin-field">
              <label for="adminAiGenerationEnabled">AI Generation Availability</label>
              <select class="admin-input" id="adminAiGenerationEnabled" name="generation_enabled">
                <option value="1">ON</option>
                <option value="0">OFF</option>
              </select>
            </div>

            <div class="admin-field">
              <label for="adminAiPackagePrice">Package Price (€)</label>
              <input class="admin-input" id="adminAiPackagePrice" name="package_price" type="number" min="0" max="999999.99" step="0.01" required>
            </div>

            <div class="admin-field">
              <label for="adminAiPackageGenerations">Generations per Package</label>
              <input class="admin-input" id="adminAiPackageGenerations" name="package_generations" type="number" min="1" max="100000" step="1" required>
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit">Save AI Settings</button>
            </div>
            <div id="adminAiSettingsStatus" class="admin-feedback" role="status" aria-live="polite"></div>
          </form>

          <div class="admin-card-grid admin-ai-counters" aria-label="GIRFFON AI counters">
            <article class="admin-stat-card"><span>Packages Sold</span><strong id="adminAiPackagesSold">0</strong></article>
            <article class="admin-stat-card"><span>AI Revenue</span><strong id="adminAiRevenue">€0.00</strong></article>
            <article class="admin-stat-card"><span>Credits Purchased</span><strong id="adminAiCreditsPurchased">0</strong></article>
            <article class="admin-stat-card"><span>Credits Used</span><strong id="adminAiCreditsUsed">0</strong></article>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260505r5"></script>
  <script src="JS/admin-ai-settings.js?v=20260917r1"></script>
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