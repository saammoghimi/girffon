<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/products-data.php";

girffonAdminEnsureProductsTable($pdo);
$adminProducts = girffonAdminFetchProducts($pdo);
$adminEditingProductId = max(0, (int) ($_GET["edit"] ?? 0));
$adminEditingProduct = $adminEditingProductId > 0 ? girffonAdminFetchProductById($pdo, $adminEditingProductId) : null;
$adminProductStatusMessage = trim((string) ($_GET["status"] ?? ""));
$adminProductErrorMessage = trim((string) ($_GET["error"] ?? ""));
$adminScriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/admin-products.php'));
$adminAppBasePath = rtrim((string) preg_replace('#/[^/]+$#', '', $adminScriptName), '/');
$adminBuildPath = static function (string $path) use ($adminAppBasePath) {
  $normalizedPath = ltrim($path, '/');
  if ($adminAppBasePath === '') {
    return '/' . $normalizedPath;
  }
  return $adminAppBasePath . '/' . $normalizedPath;
};
$escapeAdminProduct = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$formatAdminProductCurrency = static function ($value) {
  return "EUR " . number_format((float) $value, 2, ".", ",");
};
$formatAdminProductLabel = static function ($value) use ($escapeAdminProduct) {
  return $escapeAdminProduct(ucwords(str_replace("_", " ", (string) $value)));
};
$formatAdminProductVariant = static function ($size, $color) {
  $sizeValue = trim((string) $size);
  $colorValue = trim((string) $color);
  if ($sizeValue === '' && $colorValue === '') {
    return '- / -';
  }
  if ($sizeValue === '') {
    $sizeValue = '-';
  }
  if ($colorValue === '') {
    $colorValue = '-';
  }
  return $sizeValue . ' / ' . $colorValue;
};
$resolveAdminProductImage = static function ($path) use ($adminBuildPath) {
  $value = trim((string) $path);
  if ($value === "") {
    return null;
  }
  if (preg_match('/^https?:\/\//i', $value)) {
    return $value;
  }
  if (str_starts_with($value, '/')) {
    return $value;
  }
  return $adminBuildPath(ltrim(str_replace('\\', '/', $value), '/'));
};
$adminNormalizeProductStatus = static function ($value) {
  $normalized = strtolower(trim((string) $value));
  return in_array($normalized, ['active', 'draft', 'archived'], true) ? $normalized : 'active';
};
if ($adminEditingProductId > 0 && !$adminEditingProduct && $adminProductErrorMessage === '') {
  $adminProductErrorMessage = 'Product not found.';
}
$adminFormProduct = [
  'id' => $adminEditingProduct['id'] ?? 0,
  'name' => $adminEditingProduct['name'] ?? '',
  'sku' => $adminEditingProduct['sku'] ?? '',
  'barcode' => $adminEditingProduct['barcode'] ?? girffonAdminBuildProductBarcode((string) ($adminEditingProduct['sku'] ?? '')),
  'description' => $adminEditingProduct['description'] ?? '',
  'price' => $adminEditingProduct['price'] ?? '',
  'sale_price' => $adminEditingProduct['sale_price'] ?? '',
  'stock' => $adminEditingProduct['stock'] ?? 0,
  'category' => $adminEditingProduct['category'] ?? '',
  'size' => $adminEditingProduct['size'] ?? '',
  'color' => $adminEditingProduct['color'] ?? '',
  'image' => $adminEditingProduct['image'] ?? '',
  'status' => $adminNormalizeProductStatus($adminEditingProduct['status'] ?? 'active'),
];
$adminProductFormAction = $adminEditingProduct ? 'backend/admin/update-product.php' : 'backend/admin/save-product.php';
$adminProductFormHeading = $adminEditingProduct ? 'Edit Product' : 'Add Product';
$adminProductFormNote = $adminEditingProduct ? 'Update an existing product entry in MySQL.' : 'Save a new product entry to MySQL.';
$adminProductFormSubmit = $adminEditingProduct ? 'Update Product' : 'Save Product';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Products</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260511r15">
  <style>
    .admin-product-table-actions {
      display: flex;
      flex-wrap: nowrap;
      justify-content: center;
      gap: 10px;
      align-items: center;
    }

    .admin-product-table-actions form {
      margin: 0;
    }

    .admin-product-table-actions .admin-button {
      min-width: 42px;
      width: 42px;
      min-height: 42px;
      height: 42px;
      padding: 0;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0;
      line-height: 0;
    }

    .admin-product-action-icon {
      width: 16px;
      height: 16px;
      display: block;
      flex: 0 0 auto;
    }

    .admin-nav-link-index,
    .admin-nav-link-label {
      display: inline;
    }

    .admin-product-table-actions .admin-button-soft {
      color: #2b241b;
    }

    .admin-product-table-actions .admin-button-danger {
      color: #a63a31;
    }

    @media (max-width: 768px) {
      .admin-product-table-actions {
        flex-wrap: wrap;
        justify-content: flex-start;
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

    @media (max-width: 1120px) {
      .admin-nav-link-index {
        display: none;
      }

      .admin-nav-link-label {
        display: block;
        min-width: 0;
        color: #2b241b;
        font-size: 0.95rem;
        line-height: 1.2;
        letter-spacing: 0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }

    @media (max-width: 520px) {
      .admin-main,
      .admin-page-section,
      .admin-page-section > .admin-panel,
      .admin-page-section > .admin-table-panel,
      .admin-grid-form,
      .admin-field,
      .admin-field-wide {
        min-width: 0;
      }

      .admin-main {
        overflow-x: hidden;
      }

      .admin-grid-form {
        gap: 14px;
      }

      .admin-input,
      .admin-select,
      .admin-textarea {
        min-width: 0;
        padding: 12px 13px;
      }

      .admin-panel-head h2 {
        font-size: 1rem;
      }

      .admin-panel-note,
      .admin-feedback {
        overflow-wrap: anywhere;
      }

      .admin-product-table-actions {
        gap: 8px;
        justify-content: flex-start;
      }

      .admin-product-table-actions .admin-button {
        min-width: 40px;
        width: 40px;
        min-height: 40px;
        height: 40px;
      }

      .admin-product-action-icon {
        width: 15px;
        height: 15px;
      }

      .admin-table {
        min-width: 520px;
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
      .admin-table-panel {
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

      .admin-product-table-actions {
        justify-content: center;
      }

      .admin-order-thumb {
        width: 44px;
        height: 44px;
      }

      .admin-table {
        min-width: 480px;
      }

      .admin-table-wrap {
        border-radius: 16px;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="products" data-admin-products-source="database">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Products management connected to the live admin database.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard"><span class="admin-nav-link-index">1. </span><span class="admin-nav-link-label">Dashboard</span></a>
        <a class="admin-nav-link is-active" href="admin-products.php" aria-label="Products" title="Products"><span class="admin-nav-link-index">2. </span><span class="admin-nav-link-label">Products</span></a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders"><span class="admin-nav-link-index">3. </span><span class="admin-nav-link-label">Orders</span></a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices"><span class="admin-nav-link-index">4. </span><span class="admin-nav-link-label">Invoices</span></a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages"><span class="admin-nav-link-index">5. </span><span class="admin-nav-link-label">Messages</span></a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users"><span class="admin-nav-link-index">6. </span><span class="admin-nav-link-label">Users</span></a>
        <a class="admin-nav-link is-active" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Product Fields</strong>
          <p class="admin-panel-note">Product name, SKU, barcode, price, stock, size, color, and status.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Products</h1>
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
              <h2><?php echo $escapeAdminProduct($adminProductFormHeading); ?></h2>
              <p class="admin-panel-note"><?php echo $escapeAdminProduct($adminProductFormNote); ?></p>
            </div>
          </div>

          <form id="adminProductsForm" class="admin-grid-form" action="<?php echo $escapeAdminProduct($adminProductFormAction); ?>" method="POST" novalidate>
            <?php if ($adminEditingProduct): ?>
              <input type="hidden" name="id" value="<?php echo $escapeAdminProduct($adminFormProduct['id']); ?>">
            <?php endif; ?>

            <div class="admin-field">
              <label for="adminProductName">Product Name</label>
              <input class="admin-input" id="adminProductName" name="name" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['name']); ?>" required>
            </div>

            <div class="admin-field">
              <label for="adminProductSku">SKU</label>
              <input class="admin-input" id="adminProductSku" name="sku" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['sku']); ?>" required>
            </div>

            <div class="admin-field">
              <label for="adminProductBarcode">Barcode</label>
              <input class="admin-input" id="adminProductBarcode" name="barcode" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['barcode']); ?>" placeholder="Auto from SKU if empty">
            </div>

            <div class="admin-field admin-field-wide">
              <label for="adminProductDescription">Description</label>
              <textarea class="admin-textarea" id="adminProductDescription" name="description"><?php echo $escapeAdminProduct($adminFormProduct['description']); ?></textarea>
            </div>

            <div class="admin-field">
              <label for="adminProductPrice">Price</label>
              <input class="admin-input" id="adminProductPrice" name="price" type="number" step="0.01" min="0" value="<?php echo $escapeAdminProduct($adminFormProduct['price']); ?>" required>
            </div>

            <div class="admin-field">
              <label for="adminProductSalePrice">Sale Price</label>
              <input class="admin-input" id="adminProductSalePrice" name="sale_price" type="number" step="0.01" min="0" value="<?php echo $escapeAdminProduct($adminFormProduct['sale_price']); ?>">
            </div>

            <div class="admin-field">
              <label for="adminProductStock">Stock</label>
              <input class="admin-input" id="adminProductStock" name="stock" type="number" min="0" value="<?php echo $escapeAdminProduct($adminFormProduct['stock']); ?>" required>
            </div>

            <div class="admin-field">
              <label for="adminProductCategory">Category</label>
              <input class="admin-input" id="adminProductCategory" name="category" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['category']); ?>" required>
            </div>

            <div class="admin-field">
              <label for="adminProductSize">Size</label>
              <input class="admin-input" id="adminProductSize" name="size" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['size']); ?>" placeholder="S, M, L or 42" required>
            </div>

            <div class="admin-field">
              <label for="adminProductColor">Color</label>
              <input class="admin-input" id="adminProductColor" name="color" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['color']); ?>" placeholder="Black, Blue, White" required>
            </div>

            <div class="admin-field">
              <label for="adminProductStatus">Status</label>
              <select class="admin-select" id="adminProductStatus" name="status" required>
                <option value="active"<?php if ($adminFormProduct['status'] === 'active'): ?> selected<?php endif; ?>>Active</option>
                <option value="draft"<?php if ($adminFormProduct['status'] === 'draft'): ?> selected<?php endif; ?>>Draft</option>
                <option value="archived"<?php if ($adminFormProduct['status'] === 'archived'): ?> selected<?php endif; ?>>Archived</option>
              </select>
            </div>

            <div class="admin-field admin-field-wide">
              <label for="adminProductImage">Image URL / Path</label>
              <input class="admin-input" id="adminProductImage" name="image" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['image']); ?>" placeholder="https://... or uploads/...">
            </div>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit"><?php echo $escapeAdminProduct($adminProductFormSubmit); ?></button>
              <?php if ($adminEditingProduct): ?>
                <a class="admin-button admin-button-soft" href="admin-products.php">Cancel Edit</a>
              <?php endif; ?>
            </div>
            <div id="adminProductsStatus" class="admin-feedback" role="status" aria-live="polite"<?php if ($adminProductErrorMessage): ?> style="color:#9f2f2f;"<?php endif; ?>><?php echo $escapeAdminProduct($adminProductErrorMessage ?: $adminProductStatusMessage); ?></div>
          </form>
        </article>

        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Product List</h2>
              <p class="admin-panel-note">Current products stored in the admin database.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Product Name</th>
                  <th>SKU</th>
                  <th>Barcode</th>
                  <th>Price</th>
                  <th>Sale Price</th>
                  <th>Category</th>
                  <th>Size / Color</th>
                  <th>Status</th>
                  <th>Stock</th>
                  <th>Updated</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="adminProductsTableBody">
                <?php if ($adminProducts): ?>
                  <?php foreach ($adminProducts as $product): ?>
                    <?php $productImage = $resolveAdminProductImage($product["image"] ?? ""); ?>
                    <tr>
                      <td>
                        <?php if ($productImage): ?>
                          <img class="admin-order-thumb" src="<?php echo $escapeAdminProduct($productImage); ?>" alt="<?php echo $escapeAdminProduct($product["name"] ?? "Product image"); ?>">
                        <?php else: ?>
                          <div class="admin-order-thumb admin-order-thumb-placeholder">No image</div>
                        <?php endif; ?>
                      </td>
                      <td><strong><?php echo $escapeAdminProduct($product["name"] ?? ""); ?></strong></td>
                      <td><?php echo $escapeAdminProduct($product["sku"] ?? "-"); ?></td>
                      <td><?php echo $escapeAdminProduct(($product["barcode"] ?? '') !== '' ? $product["barcode"] : girffonAdminBuildProductBarcode((string) ($product["sku"] ?? ''))); ?></td>
                      <td><?php echo $escapeAdminProduct($formatAdminProductCurrency($product["price"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminProduct(($product["sale_price"] ?? null) !== null && $product["sale_price"] !== '' ? $formatAdminProductCurrency($product["sale_price"]) : '-'); ?></td>
                      <td><?php echo $escapeAdminProduct($product["category"] ?? "-"); ?></td>
                      <td><?php echo $escapeAdminProduct($formatAdminProductVariant($product["size"] ?? '', $product["color"] ?? '')); ?></td>
                      <td><?php echo $formatAdminProductLabel($product["status"] ?? "active"); ?></td>
                      <td><?php echo $escapeAdminProduct($product["stock"] ?? 0); ?></td>
                      <td><?php echo $escapeAdminProduct($product["updated_at"] ?? $product["created_at"] ?? '-'); ?></td>
                      <td>
                        <div class="admin-product-table-actions">
                          <a class="admin-button admin-button-soft" href="admin-products.php?edit=<?php echo $escapeAdminProduct($product['id'] ?? 0); ?>" aria-label="Edit product" title="Edit product">
                            <svg class="admin-product-action-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                              <path d="M4 20h4l10-10-4-4L4 16v4Z" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                              <path d="M12 6l4 4" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                          </a>
                          <form action="backend/admin/delete-product.php" method="POST" onsubmit="return confirm('Delete this product?');">
                            <input type="hidden" name="id" value="<?php echo $escapeAdminProduct($product['id'] ?? 0); ?>">
                            <button class="admin-button admin-button-danger" type="submit" aria-label="Delete product" title="Delete product">
                              <svg class="admin-product-action-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M8 6V4h8v2" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M19 6l-1 14H6L5 6" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M10 11v6M14 11v6" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                              </svg>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="12" class="admin-empty">No products found in the database yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260505r5"></script>
</body>
</html>
