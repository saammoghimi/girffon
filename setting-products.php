<?php
require_once __DIR__ . "/backend/admin/session.php";

$adminProductSettingsFile = __DIR__ . "/backend/admin/product-settings-data.php";
$adminProductSettingsAvailable = is_file($adminProductSettingsFile);
if ($adminProductSettingsAvailable) {
  require_once $adminProductSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$adminProductSettingStatus = trim((string) ($_GET['status'] ?? ''));
$adminProductSettingError = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $productPreset = trim((string) ($_POST['product_preset'] ?? ''));
  $productPreferences = [
    'show_product_form' => isset($_POST['show_product_form']),
    'show_product_list' => isset($_POST['show_product_list']),
    'show_barcode_input' => isset($_POST['show_barcode_input']),
    'show_description_input' => isset($_POST['show_description_input']),
    'show_sale_price_input' => isset($_POST['show_sale_price_input']),
    'show_image_input' => isset($_POST['show_image_input']),
    'show_barcode_column' => isset($_POST['show_barcode_column']),
    'show_sale_price_column' => isset($_POST['show_sale_price_column']),
    'show_variant_column' => isset($_POST['show_variant_column']),
    'show_status_column' => isset($_POST['show_status_column']),
    'show_edit_action' => isset($_POST['show_edit_action']),
    'show_print_action' => isset($_POST['show_print_action']),
    'show_delete_action' => isset($_POST['show_delete_action']),
  ];

  $productPresets = [
    'full-control' => [
      'show_product_form' => true,
      'show_product_list' => true,
      'show_barcode_input' => true,
      'show_description_input' => true,
      'show_sale_price_input' => true,
      'show_image_input' => true,
      'show_barcode_column' => true,
      'show_sale_price_column' => true,
      'show_variant_column' => true,
      'show_status_column' => true,
      'show_edit_action' => true,
      'show_print_action' => true,
      'show_delete_action' => true,
    ],
    'inventory-focus' => [
      'show_product_form' => true,
      'show_product_list' => true,
      'show_barcode_input' => true,
      'show_description_input' => false,
      'show_sale_price_input' => false,
      'show_image_input' => false,
      'show_barcode_column' => true,
      'show_sale_price_column' => false,
      'show_variant_column' => true,
      'show_status_column' => true,
      'show_edit_action' => true,
      'show_print_action' => true,
      'show_delete_action' => false,
    ],
    'catalog-review' => [
      'show_product_form' => false,
      'show_product_list' => true,
      'show_barcode_input' => false,
      'show_description_input' => false,
      'show_sale_price_input' => false,
      'show_image_input' => false,
      'show_barcode_column' => false,
      'show_sale_price_column' => true,
      'show_variant_column' => true,
      'show_status_column' => true,
      'show_edit_action' => true,
      'show_print_action' => false,
      'show_delete_action' => false,
    ],
  ];

  if ($productPreset !== '' && isset($productPresets[$productPreset])) {
    $productPreferences = $productPresets[$productPreset];
  }

  $saved = $adminProductSettingsAvailable && function_exists('girffonAdminSaveProductPreferences')
    ? girffonAdminSaveProductPreferences($pdo, $adminCurrentId, $adminCurrentUsername, $productPreferences)
    : false;
  header('Location: /GirffoN/setting-products.php?' . ($saved ? 'status=' . rawurlencode('Product settings saved for your account.') : 'error=' . rawurlencode('Unable to save product settings right now.')));
  exit;
}

$adminProductPreferences = [
  'show_product_form' => true,
  'show_product_list' => true,
  'show_barcode_input' => true,
  'show_description_input' => true,
  'show_sale_price_input' => true,
  'show_image_input' => true,
  'show_barcode_column' => true,
  'show_sale_price_column' => true,
  'show_variant_column' => true,
  'show_status_column' => true,
  'show_edit_action' => true,
  'show_print_action' => true,
  'show_delete_action' => true,
];

if ($adminProductSettingsAvailable && function_exists('girffonAdminFetchProductPreferences')) {
  $adminProductPreferences = girffonAdminFetchProductPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$escapeProductSetting = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$productSettingGroups = [
  'workspace' => [
    'title' => 'Workspace Layout',
    'note' => 'Control which main products blocks stay visible in your admin workspace.',
    'settings' => [
      'show_product_form' => ['label' => 'Product Form', 'note' => 'Show or hide the add/edit product form block at the top of the page.'],
      'show_product_list' => ['label' => 'Product List', 'note' => 'Show or hide the products table for your admin account.'],
    ],
  ],
  'form' => [
    'title' => 'Form Fields',
    'note' => 'Choose which advanced product fields stay visible in the editor form.',
    'settings' => [
      'show_barcode_input' => ['label' => 'Barcode Field', 'note' => 'Show the barcode input in the add/edit product form.'],
      'show_description_input' => ['label' => 'Description Field', 'note' => 'Show the long product description textarea.'],
      'show_sale_price_input' => ['label' => 'Sale Price Field', 'note' => 'Show the sale price input for promotional pricing.'],
      'show_image_input' => ['label' => 'Image Field', 'note' => 'Show the image URL/path field in the product form.'],
    ],
  ],
  'table' => [
    'title' => 'Table Columns',
    'note' => 'Control the data density of the products list for each admin account.',
    'settings' => [
      'show_barcode_column' => ['label' => 'Barcode Column', 'note' => 'Display barcode preview and barcode value inside the table.'],
      'show_sale_price_column' => ['label' => 'Sale Price Column', 'note' => 'Display the sale price column in the product list.'],
      'show_variant_column' => ['label' => 'Size / Color Column', 'note' => 'Display product size and color variations in the table.'],
      'show_status_column' => ['label' => 'Status Column', 'note' => 'Display active, draft, and archived status in the product list.'],
    ],
  ],
  'actions' => [
    'title' => 'Action Buttons',
    'note' => 'Decide which row-level product actions are available on your screen.',
    'settings' => [
      'show_edit_action' => ['label' => 'Edit Action', 'note' => 'Show the edit button inside each product row.'],
      'show_print_action' => ['label' => 'Print Barcode Action', 'note' => 'Show the barcode print button in the table actions.'],
      'show_delete_action' => ['label' => 'Delete Action', 'note' => 'Show the delete button for product rows.'],
    ],
  ],
];

$productPresetCards = [
  'full-control' => ['title' => 'Full Control', 'note' => 'Everything visible for complete merchandising and product management.'],
  'inventory-focus' => ['title' => 'Inventory Focus', 'note' => 'Lean workspace for barcode, stock, and operational product maintenance.'],
  'catalog-review' => ['title' => 'Catalog Review', 'note' => 'Table-first view for reviewing products without a busy edit form.'],
];

$activeProductSettingsCount = 0;
foreach ($adminProductPreferences as $preferenceEnabled) {
  if (!empty($preferenceEnabled)) {
    $activeProductSettingsCount++;
  }
}

$productSettingsTotal = count($adminProductPreferences);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Product Settings</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .product-settings-hero {
      display: grid;
      grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.8fr);
      gap: 18px;
      margin-bottom: 18px;
    }

    .product-settings-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .product-settings-section-grid {
      display: grid;
      gap: 18px;
    }

    .product-settings-summary-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .product-settings-metric {
      padding: 16px 18px;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.86);
      border: 1px solid rgba(199, 165, 75, 0.16);
    }

    .product-settings-metric span {
      display: block;
      color: #7d715f;
      font-size: 0.86rem;
      margin-bottom: 6px;
    }

    .product-settings-metric strong {
      display: block;
      color: #2b241b;
      font-size: 1.4rem;
    }

    .product-preset-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .product-preset-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 20px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      background: linear-gradient(180deg, rgba(255, 252, 246, 0.96), rgba(255, 255, 255, 0.82));
    }

    .product-preset-card h3,
    .product-settings-section h3 {
      margin: 0;
      color: #2b241b;
      font-size: 1rem;
    }

    .product-preset-card p,
    .product-settings-section-note {
      margin: 0;
      color: #7d715f;
      line-height: 1.6;
      font-size: 0.92rem;
    }

    .product-preset-card .admin-button {
      justify-self: start;
    }

    .product-settings-section {
      display: grid;
      gap: 16px;
      padding: 20px;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(199, 165, 75, 0.14);
    }

    .product-setting-card {
      display: grid;
      gap: 12px;
      padding: 18px;
      border-radius: 22px;
      border: 1px solid rgba(199, 165, 75, 0.16);
      background: rgba(255, 255, 255, 0.82);
    }

    .product-setting-toggle {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
    }

    .product-setting-toggle label {
      display: grid;
      gap: 6px;
      cursor: pointer;
    }

    .product-setting-toggle input[type="checkbox"] {
      margin-top: 4px;
      width: 20px;
      height: 20px;
      accent-color: #c7a54b;
      cursor: pointer;
    }

    .product-setting-title {
      font-size: 1rem;
      font-weight: 700;
      color: #2b241b;
    }

    .product-setting-note {
      color: #7d715f;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      .product-settings-hero,
      .product-settings-summary-grid,
      .product-preset-grid,
      .product-settings-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 980px) {
      .product-settings-hero,
      .product-preset-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="product-settings">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Personal product management visibility and table preferences for each admin.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link is-active" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Product Settings</strong>
          <p class="admin-panel-note">These switches affect only your own products screen.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title">Product Settings</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-products.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if (!$adminProductSettingsAvailable): ?>
          <div class="admin-feedback is-error" role="status" aria-live="polite">
            Product settings storage file is missing on this server. The products page will use default layout until deployment is complete.
          </div>
        <?php endif; ?>

        <?php if ($adminProductSettingStatus || $adminProductSettingError): ?>
          <div class="admin-feedback<?php if ($adminProductSettingError): ?> is-error<?php else: ?> is-success<?php endif; ?>" role="status" aria-live="polite">
            <?php echo $escapeProductSetting($adminProductSettingError ?: $adminProductSettingStatus); ?>
          </div>
        <?php endif; ?>

        <section class="product-settings-hero">
          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Products Workspace Control</h2>
                <p class="admin-panel-note">Signed in as <?php echo $escapeProductSetting($adminCurrentUsername !== '' ? $adminCurrentUsername : 'GirffoN Admin'); ?>. These settings are saved per admin and directly control the products form, product table, and row actions.</p>
              </div>
            </div>
            <div class="product-preset-grid">
              <?php foreach ($productPresetCards as $presetKey => $presetMeta): ?>
                <article class="product-preset-card">
                  <div>
                    <h3><?php echo $escapeProductSetting($presetMeta['title']); ?></h3>
                    <p><?php echo $escapeProductSetting($presetMeta['note']); ?></p>
                  </div>
                  <form method="POST" action="setting-products.php">
                    <input type="hidden" name="product_preset" value="<?php echo $escapeProductSetting($presetKey); ?>">
                    <button class="admin-button admin-button-soft" type="submit">Apply Preset</button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Current View Summary</h2>
                <p class="admin-panel-note">Quick snapshot of how much of the Products workspace is enabled for this admin account.</p>
              </div>
            </div>
            <div class="product-settings-summary-grid">
              <div class="product-settings-metric">
                <span>Enabled Controls</span>
                <strong><?php echo $escapeProductSetting($activeProductSettingsCount); ?> / <?php echo $escapeProductSetting($productSettingsTotal); ?></strong>
              </div>
              <div class="product-settings-metric">
                <span>Workspace Mode</span>
                <strong><?php echo $escapeProductSetting(!empty($adminProductPreferences['show_product_form']) && !empty($adminProductPreferences['show_product_list']) ? 'Full Workspace' : (!empty($adminProductPreferences['show_product_list']) ? 'List Focused' : 'Form Focused')); ?></strong>
              </div>
              <div class="product-settings-metric">
                <span>Barcode Tools</span>
                <strong><?php echo $escapeProductSetting(!empty($adminProductPreferences['show_barcode_input']) || !empty($adminProductPreferences['show_barcode_column']) || !empty($adminProductPreferences['show_print_action']) ? 'Enabled' : 'Minimal'); ?></strong>
              </div>
              <div class="product-settings-metric">
                <span>Danger Actions</span>
                <strong><?php echo $escapeProductSetting(!empty($adminProductPreferences['show_delete_action']) ? 'Delete Visible' : 'Protected'); ?></strong>
              </div>
            </div>
          </article>
        </section>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Fine-Grained Product Settings</h2>
              <p class="admin-panel-note">Build your own Products workspace with grouped controls for layout, editor fields, list columns, and action buttons.</p>
            </div>
          </div>

          <form method="POST" action="setting-products.php" novalidate>
            <div class="product-settings-section-grid">
              <?php foreach ($productSettingGroups as $groupKey => $groupMeta): ?>
                <section class="product-settings-section">
                  <div>
                    <h3><?php echo $escapeProductSetting($groupMeta['title']); ?></h3>
                    <p class="product-settings-section-note"><?php echo $escapeProductSetting($groupMeta['note']); ?></p>
                  </div>
                  <div class="product-settings-grid">
                    <?php foreach ($groupMeta['settings'] as $settingKey => $settingMeta): ?>
                      <section class="product-setting-card">
                        <div class="product-setting-toggle">
                          <label for="<?php echo $escapeProductSetting($settingKey); ?>">
                            <span class="product-setting-title"><?php echo $escapeProductSetting($settingMeta['label']); ?></span>
                            <span class="product-setting-note"><?php echo $escapeProductSetting($settingMeta['note']); ?></span>
                          </label>
                          <input id="<?php echo $escapeProductSetting($settingKey); ?>" name="<?php echo $escapeProductSetting($settingKey); ?>" type="checkbox" <?php if (!empty($adminProductPreferences[$settingKey])): ?>checked<?php endif; ?>>
                        </div>
                      </section>
                    <?php endforeach; ?>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>

            <div class="admin-form-actions" style="margin-top: 22px;">
              <button class="admin-button admin-button-accent" type="submit">Save Product Settings</button>
              <a class="admin-button admin-button-soft" href="admin-products.php">Back To Products</a>
            </div>
          </form>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r12"></script>
</body>
</html>