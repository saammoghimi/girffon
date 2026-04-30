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
  'description' => $adminEditingProduct['description'] ?? '',
  'price' => $adminEditingProduct['price'] ?? '',
  'sale_price' => $adminEditingProduct['sale_price'] ?? '',
  'stock' => $adminEditingProduct['stock'] ?? 0,
  'category' => $adminEditingProduct['category'] ?? '',
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
  <link rel="stylesheet" href="CSS/admin-girffon.css">
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
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link is-active" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Product Fields</strong>
          <p class="admin-panel-note">Product name, SKU, price, image URL, and stock.</p>
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
                  <th>Price</th>
                  <th>Sale Price</th>
                  <th>Category</th>
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
                      <td><?php echo $escapeAdminProduct($product["sku"] ?? ""); ?></td>
                      <td><?php echo $escapeAdminProduct($formatAdminProductCurrency($product["price"] ?? 0)); ?></td>
                      <td><?php echo $escapeAdminProduct(($product["sale_price"] ?? null) !== null && $product["sale_price"] !== '' ? $formatAdminProductCurrency($product["sale_price"]) : '-'); ?></td>
                      <td><?php echo $escapeAdminProduct($product["category"] ?? "-"); ?></td>
                      <td><?php echo $formatAdminProductLabel($product["status"] ?? "active"); ?></td>
                      <td><?php echo $escapeAdminProduct($product["stock"] ?? 0); ?></td>
                      <td><?php echo $escapeAdminProduct($product["updated_at"] ?? $product["created_at"] ?? '-'); ?></td>
                      <td>
                        <div class="admin-grid-form">
                          <a class="admin-button admin-button-soft" href="admin-products.php?edit=<?php echo $escapeAdminProduct($product['id'] ?? 0); ?>">Edit</a>
                          <form action="backend/admin/delete-product.php" method="POST" onsubmit="return confirm('Delete this product?');">
                            <input type="hidden" name="id" value="<?php echo $escapeAdminProduct($product['id'] ?? 0); ?>">
                            <button class="admin-button admin-button-danger" type="submit">Delete</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="10" class="admin-empty">No products found in the database yet.</td>
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
</body>
</html>
