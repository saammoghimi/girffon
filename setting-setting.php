<?php require_once __DIR__ . "/backend/admin/session.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Store Settings Workspace</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .store-settings-hero { display:grid; grid-template-columns:minmax(0, 1.35fr) minmax(280px, 0.85fr); gap:18px; margin-bottom:18px; }
    .store-settings-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:18px; }
    .store-settings-section-grid { display:grid; gap:18px; }
    .store-settings-summary-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px; }
    .store-settings-metric { padding:16px 18px; border-radius:18px; background:rgba(255,255,255,0.86); border:1px solid rgba(199,165,75,0.16); }
    .store-settings-metric span { display:block; color:#7d715f; font-size:0.86rem; margin-bottom:6px; }
    .store-settings-metric strong { display:block; color:#2b241b; font-size:1.4rem; }
    .store-preset-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:14px; }
    .store-preset-card { display:grid; gap:12px; padding:18px; border-radius:20px; border:1px solid rgba(199,165,75,0.18); background:linear-gradient(180deg, rgba(255,252,246,0.96), rgba(255,255,255,0.82)); }
    .store-preset-card h3, .store-settings-section h3 { margin:0; color:#2b241b; font-size:1rem; }
    .store-preset-card p, .store-settings-section-note { margin:0; color:#7d715f; line-height:1.6; font-size:0.92rem; }
    .store-preset-card .admin-button { justify-self:start; }
    .store-settings-section { display:grid; gap:16px; padding:20px; border-radius:24px; background:rgba(255,255,255,0.8); border:1px solid rgba(199,165,75,0.14); }
    .store-setting-card { display:grid; gap:12px; padding:18px; border-radius:22px; border:1px solid rgba(199,165,75,0.16); background:rgba(255,255,255,0.82); }
    .store-setting-toggle { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
    .store-setting-toggle label { display:grid; gap:6px; cursor:pointer; }
    .store-setting-toggle input[type="checkbox"] { margin-top:4px; width:20px; height:20px; accent-color:#c7a54b; cursor:pointer; }
    .store-setting-title { font-size:1rem; font-weight:700; color:#2b241b; }
    .store-setting-note { color:#7d715f; font-size:0.92rem; line-height:1.6; }
    @media (max-width: 980px) { .store-settings-hero, .store-preset-grid { grid-template-columns:1fr; } }
    @media (max-width: 720px) { .store-settings-hero, .store-settings-summary-grid, .store-preset-grid, .store-settings-grid { grid-template-columns:1fr; } }
  </style>
</head>
<body class="admin-page" data-admin-page="settings">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo"><img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo"></span>
        <p>Shape a cleaner store-settings workspace with focused visibility controls.</p>
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
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Store Settings Workspace</strong>
          <p class="admin-panel-note">Choose how dense or minimal the Store Settings editor should feel.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Store Settings Workspace</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <a class="admin-button admin-button-soft admin-refresh-button" href="admin-settings.php" aria-label="Back to Store Settings" title="Back to Store Settings">Store Settings</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" aria-label="Store Settings Workspace" aria-current="page" title="You are already in Store Settings Workspace" disabled>Workspace Controls</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <section class="store-settings-hero">
          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Store Settings Control Center</h2>
                <p class="admin-panel-note">These controls change the visibility of the real Store Settings form fields in your browser. Use presets for a fast professional layout, or tune each field manually.</p>
              </div>
            </div>
            <div class="store-preset-grid">
              <article class="store-preset-card">
                <div>
                  <h3>Full Operations</h3>
                  <p>Keep every field visible for store configuration, pricing, and support management.</p>
                </div>
                <button class="admin-button admin-button-soft" type="button" data-store-preset="full-operations">Apply Preset</button>
              </article>
              <article class="store-preset-card">
                <div>
                  <h3>Checkout Desk</h3>
                  <p>Focus on pricing, tax, and shipping with less policy and support clutter.</p>
                </div>
                <button class="admin-button admin-button-soft" type="button" data-store-preset="checkout-desk">Apply Preset</button>
              </article>
              <article class="store-preset-card">
                <div>
                  <h3>Support View</h3>
                  <p>Focus on contact and return policy details for customer support operations.</p>
                </div>
                <button class="admin-button admin-button-soft" type="button" data-store-preset="support-view">Apply Preset</button>
              </article>
            </div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Current View Summary</h2>
                <p class="admin-panel-note">Quick read on how complete or focused the current Store Settings workspace is.</p>
              </div>
            </div>
            <div class="store-settings-summary-grid">
              <div class="store-settings-metric"><span>Visible Fields</span><strong id="storeSettingsVisibleCount">0 / 8</strong></div>
              <div class="store-settings-metric"><span>Workspace Mode</span><strong id="storeSettingsMode">Custom</strong></div>
              <div class="store-settings-metric"><span>Commerce Controls</span><strong id="storeSettingsCommerceState">Enabled</strong></div>
              <div class="store-settings-metric"><span>Support Controls</span><strong id="storeSettingsSupportState">Enabled</strong></div>
            </div>
          </article>
        </section>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Field Visibility</h2>
              <p class="admin-panel-note">Choose exactly which Store Settings inputs remain visible in admin-settings.php.</p>
            </div>
          </div>

          <form id="storeSettingsWorkspaceForm" novalidate>
            <div class="store-settings-section-grid">
              <section class="store-settings-section">
                <div>
                  <h3>Store Identity</h3>
                  <p class="store-settings-section-note">Basic store identity and locale controls.</p>
                </div>
                <div class="store-settings-grid">
                  <section class="store-setting-card"><div class="store-setting-toggle"><label for="workspaceStoreName"><span class="store-setting-title">Store Name</span><span class="store-setting-note">Show the main brand or storefront name input.</span></label><input id="workspaceStoreName" name="storeName" type="checkbox"></div></section>
                  <section class="store-setting-card"><div class="store-setting-toggle"><label for="workspaceStoreEmail"><span class="store-setting-title">Store Email</span><span class="store-setting-note">Show the main store contact email field.</span></label><input id="workspaceStoreEmail" name="storeEmail" type="checkbox"></div></section>
                  <section class="store-setting-card"><div class="store-setting-toggle"><label for="workspaceCountry"><span class="store-setting-title">Country</span><span class="store-setting-note">Show the country field for base store locale.</span></label><input id="workspaceCountry" name="country" type="checkbox"></div></section>
                  <section class="store-setting-card"><div class="store-setting-toggle"><label for="workspaceCurrency"><span class="store-setting-title">Currency</span><span class="store-setting-note">Show the transaction currency field.</span></label><input id="workspaceCurrency" name="currency" type="checkbox"></div></section>
                </div>
              </section>

              <section class="store-settings-section">
                <div>
                  <h3>Commerce Defaults</h3>
                  <p class="store-settings-section-note">Pricing and checkout-level controls for daily operations.</p>
                </div>
                <div class="store-settings-grid">
                  <section class="store-setting-card"><div class="store-setting-toggle"><label for="workspaceTaxRate"><span class="store-setting-title">Tax Rate</span><span class="store-setting-note">Show the store tax-rate field.</span></label><input id="workspaceTaxRate" name="taxRate" type="checkbox"></div></section>
                  <section class="store-setting-card"><div class="store-setting-toggle"><label for="workspaceShippingCost"><span class="store-setting-title">Shipping Cost</span><span class="store-setting-note">Show the default shipping-cost field.</span></label><input id="workspaceShippingCost" name="shippingCost" type="checkbox"></div></section>
                </div>
              </section>

              <section class="store-settings-section">
                <div>
                  <h3>Policy And Support</h3>
                  <p class="store-settings-section-note">Customer-facing guidance and support communication fields.</p>
                </div>
                <div class="store-settings-grid">
                  <section class="store-setting-card"><div class="store-setting-toggle"><label for="workspaceReturnPolicyText"><span class="store-setting-title">Return Policy</span><span class="store-setting-note">Show the return policy textarea.</span></label><input id="workspaceReturnPolicyText" name="returnPolicyText" type="checkbox"></div></section>
                  <section class="store-setting-card"><div class="store-setting-toggle"><label for="workspaceSupportEmail"><span class="store-setting-title">Support Email</span><span class="store-setting-note">Show the support contact email field.</span></label><input id="workspaceSupportEmail" name="supportEmail" type="checkbox"></div></section>
                </div>
              </section>
            </div>

            <div class="admin-form-actions" style="margin-top:18px;">
              <button class="admin-button" type="submit">Save Workspace</button>
              <a class="admin-button admin-button-soft" href="admin-settings.php">Back to Store Settings</a>
            </div>
            <div id="storeSettingsWorkspaceStatus" class="admin-feedback" role="status" aria-live="polite" style="margin-top:16px;"></div>
          </form>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260505r5"></script>
  <script>
    (function () {
      const storageKey = "girffon_admin_settings_workspace";
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

      const presets = {
        "full-operations": {
          storeName: true,
          storeEmail: true,
          country: true,
          currency: true,
          taxRate: true,
          shippingCost: true,
          returnPolicyText: true,
          supportEmail: true
        },
        "checkout-desk": {
          storeName: true,
          storeEmail: true,
          country: false,
          currency: true,
          taxRate: true,
          shippingCost: true,
          returnPolicyText: false,
          supportEmail: false
        },
        "support-view": {
          storeName: true,
          storeEmail: true,
          country: false,
          currency: false,
          taxRate: false,
          shippingCost: false,
          returnPolicyText: true,
          supportEmail: true
        }
      };

      const form = document.getElementById("storeSettingsWorkspaceForm");
      const status = document.getElementById("storeSettingsWorkspaceStatus");
      const visibleCount = document.getElementById("storeSettingsVisibleCount");
      const mode = document.getElementById("storeSettingsMode");
      const commerceState = document.getElementById("storeSettingsCommerceState");
      const supportState = document.getElementById("storeSettingsSupportState");

      const readPreferences = function () {
        try {
          const raw = localStorage.getItem(storageKey);
          const parsed = raw ? JSON.parse(raw) : {};
          return Object.keys(defaults).reduce(function (result, key) {
            result[key] = Object.prototype.hasOwnProperty.call(parsed, key) ? Boolean(parsed[key]) : defaults[key];
            return result;
          }, {});
        } catch (_error) {
          return { ...defaults };
        }
      };

      const writePreferences = function (preferences) {
        localStorage.setItem(storageKey, JSON.stringify(preferences));
      };

      const detectMode = function (preferences) {
        const serialized = JSON.stringify(preferences);
        const fullOperations = JSON.stringify(presets["full-operations"]);
        const checkoutDesk = JSON.stringify(presets["checkout-desk"]);
        const supportView = JSON.stringify(presets["support-view"]);
        if (serialized === fullOperations) {
          return "Full Operations";
        }
        if (serialized === checkoutDesk) {
          return "Checkout Desk";
        }
        if (serialized === supportView) {
          return "Support View";
        }
        return "Custom";
      };

      const updateSummary = function (preferences) {
        const totalVisible = Object.keys(preferences).filter(function (key) {
          return preferences[key];
        }).length;

        if (visibleCount) {
          visibleCount.textContent = totalVisible + " / " + Object.keys(defaults).length;
        }
        if (mode) {
          mode.textContent = detectMode(preferences);
        }
        if (commerceState) {
          commerceState.textContent = (preferences.currency || preferences.taxRate || preferences.shippingCost) ? "Enabled" : "Hidden";
        }
        if (supportState) {
          supportState.textContent = (preferences.returnPolicyText || preferences.supportEmail) ? "Enabled" : "Hidden";
        }
      };

      const applyToForm = function (preferences) {
        if (!form) {
          return;
        }

        Object.keys(defaults).forEach(function (key) {
          const input = form.elements.namedItem(key);
          if (input && "checked" in input) {
            input.checked = Boolean(preferences[key]);
          }
        });
        updateSummary(preferences);
      };

      if (!form) {
        return;
      }

      applyToForm(readPreferences());

      form.addEventListener("submit", function (event) {
        event.preventDefault();
        const preferences = Object.keys(defaults).reduce(function (result, key) {
          const input = form.elements.namedItem(key);
          result[key] = Boolean(input && "checked" in input && input.checked);
          return result;
        }, {});
        writePreferences(preferences);
        applyToForm(preferences);
        if (status) {
          status.textContent = "Store Settings Workspace saved in localStorage for this browser.";
        }
      });

      document.querySelectorAll("[data-store-preset]").forEach(function (button) {
        button.addEventListener("click", function () {
          const key = String(button.getAttribute("data-store-preset") || "").trim();
          if (!presets[key]) {
            return;
          }
          writePreferences(presets[key]);
          applyToForm(presets[key]);
          if (status) {
            status.textContent = "Preset applied. Save is already complete for this browser.";
          }
        });
      });
    }());
  </script>
</body>
</html>