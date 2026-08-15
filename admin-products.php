<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/products-data.php";

$adminProductSettingsFile = __DIR__ . "/backend/admin/product-settings-data.php";
if (is_file($adminProductSettingsFile)) {
  require_once $adminProductSettingsFile;
}

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
$formatAdminProductDateTime = static function ($value) {
  $raw = trim((string) $value);
  if ($raw === '') {
    return '';
  }

  $timestamp = strtotime($raw);
  if ($timestamp === false) {
    return $raw;
  }

  return date('Y-m-d H:i', $timestamp);
};
$adminDiscountPercentOptions = range(5, 50, 5);
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
$adminNormalizeProductCategory = static function ($value) {
  $normalized = strtolower(trim((string) $value));
  $normalized = str_replace(['&', '/'], [' and ', ' '], $normalized);
  $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
  $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));

  if ($normalized === '') {
    return 'uncategorized';
  }

  if (str_contains($normalized, 'custom')) {
    return 'custom-design';
  }

  if (str_contains($normalized, 'home')) {
    return 'home-living';
  }

  if (str_contains($normalized, 'accessor')) {
    return 'accessories';
  }

  if (str_contains($normalized, 'kid')) {
    return 'kids';
  }

  if (str_contains($normalized, 'women')) {
    return 'women';
  }

  if (str_contains($normalized, 'men')) {
    return 'men';
  }

  return str_replace(' ', '-', $normalized);
};
$adminCategoryLabels = [
  'all' => 'All',
  'men' => 'Men',
  'women' => 'Women',
  'kids' => 'Kids',
  'accessories' => 'Accessories',
  'home-living' => 'Home & Living',
  'custom-design' => 'Custom Design',
];
$adminCategoryCounts = array_fill_keys(array_keys($adminCategoryLabels), 0);
$adminCategoryCounts['all'] = count($adminProducts);
foreach ($adminProducts as $adminProductCountRow) {
  $adminProductCategoryKey = $adminNormalizeProductCategory($adminProductCountRow['category'] ?? '');
  if (array_key_exists($adminProductCategoryKey, $adminCategoryCounts)) {
    $adminCategoryCounts[$adminProductCategoryKey] += 1;
  }
}
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
$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
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

if (function_exists('girffonAdminFetchProductPreferences')) {
  $adminProductPreferences = girffonAdminFetchProductPreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$showAdminProductForm = !empty($adminProductPreferences['show_product_form']);
$showAdminProductList = !empty($adminProductPreferences['show_product_list']);
$showAdminProductBarcodeInput = !empty($adminProductPreferences['show_barcode_input']);
$showAdminProductDescriptionInput = !empty($adminProductPreferences['show_description_input']);
$showAdminProductSalePriceInput = !empty($adminProductPreferences['show_sale_price_input']);
$showAdminProductImageInput = !empty($adminProductPreferences['show_image_input']);
$showAdminProductBarcodeColumn = !empty($adminProductPreferences['show_barcode_column']);
$showAdminProductSalePriceColumn = !empty($adminProductPreferences['show_sale_price_column']);
$showAdminProductVariantColumn = !empty($adminProductPreferences['show_variant_column']);
$showAdminProductStatusColumn = !empty($adminProductPreferences['show_status_column']);
$showAdminProductEditAction = !empty($adminProductPreferences['show_edit_action']);
$showAdminProductPrintAction = !empty($adminProductPreferences['show_print_action']);
$showAdminProductDeleteAction = !empty($adminProductPreferences['show_delete_action']);
$adminProductTableColumnCount = 10
  + ($showAdminProductBarcodeColumn ? 1 : 0)
  + ($showAdminProductSalePriceColumn ? 1 : 0)
  + ($showAdminProductVariantColumn ? 1 : 0)
  + ($showAdminProductStatusColumn ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Products</title>
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

      .admin-grid-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .admin-table-wrap {
        width: 100%;
        max-width: 100%;
      }
    }

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

    .admin-product-barcode-cell {
      min-width: 170px;
    }

    .admin-product-barcode-box {
      display: grid;
      gap: 6px;
      justify-items: start;
      min-width: 140px;
    }

    .admin-product-barcode-svg {
      width: 148px;
      height: 46px;
      display: block;
      background: #fff;
    }

    .admin-product-barcode-value {
      font-size: 0.74rem;
      letter-spacing: 0.08em;
      color: #5b5142;
      font-family: var(--admin-font-display);
    }

    .admin-product-print-label {
      width: 360px;
      padding: 18px 20px;
      color: #1f1a14;

    .admin-product-bulk-panel {
      display: grid;
      gap: 16px;
      padding: 18px;
      margin-bottom: 18px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      border-radius: 22px;
      background: linear-gradient(180deg, rgba(255, 252, 246, 0.96), rgba(255, 255, 255, 0.88));
    }

    .admin-product-bulk-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .admin-product-bulk-check {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      min-height: 48px;
      font-weight: 600;
      color: #2b241b;
    }

    .admin-product-select-cell,
    .admin-product-select-head {
      width: 48px;
      text-align: center;
    }

    .admin-product-sale-badge,
    .admin-product-sale-status {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .admin-product-sale-badge {
      background: rgba(211, 70, 92, 0.12);
      color: #b4374e;
    }

    .admin-product-sale-status {
      background: rgba(199, 165, 75, 0.14);
      color: #8b6624;
    }

    .admin-product-discount-stack {
      display: grid;
      gap: 6px;
      min-width: 180px;
    }

    .admin-product-discount-stack small {
      color: #6b5d49;
      line-height: 1.4;
    }

    .admin-product-sale-pricing {
      display: grid;
      gap: 4px;
      min-width: 140px;
    }

    .admin-product-sale-pricing strong {
      color: #b4374e;
    }

    .admin-product-sale-pricing span {
      color: #7d715f;
      text-decoration: line-through;
      font-size: 0.88rem;
    }
      font-family: Georgia, "Times New Roman", serif;
    }

    .admin-product-print-label h1 {
      margin: 0 0 8px;
      font-size: 1.1rem;

      .admin-product-bulk-grid {
        grid-template-columns: 1fr;
      }
    }

    .admin-product-print-meta {
      display: grid;
      gap: 4px;
      margin-top: 12px;
      font-size: 0.92rem;
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

    .admin-hidden-form {
      display: none;
    }

    .admin-products-toolbar {
      display: grid;
      gap: 18px;
      margin-bottom: 18px;
      padding: 18px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      border-radius: 22px;
      background: linear-gradient(180deg, rgba(255, 252, 246, 0.96), rgba(255, 255, 255, 0.88));
    }

    .admin-products-toolbar-grid,
    .admin-products-table-controls,
    .admin-products-advanced-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .admin-products-toolbar-grid .admin-field,
    .admin-products-table-controls .admin-field,
    .admin-products-advanced-grid .admin-field {
      margin: 0;
    }

    .admin-products-toolbar-grid .admin-field-wide {
      grid-column: span 2;
    }

    .admin-products-search-note {
      margin: 6px 0 0;
      color: #6b5d49;
      font-size: 0.84rem;
    }

    .admin-category-tabs,
    .admin-scope-toggle,
    .admin-discount-chip-grid,
    .admin-pagination {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .admin-category-tab,
    .admin-scope-button,
    .admin-discount-chip,
    .admin-pagination-button {
      border: 1px solid rgba(43, 36, 27, 0.12);
      background: #fff;
      color: #2b241b;
      border-radius: 999px;
      padding: 10px 14px;
      font: inherit;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
    }

    .admin-category-tab.is-active,
    .admin-scope-button.is-active,
    .admin-discount-chip.is-active,
    .admin-pagination-button.is-active {
      background: #2b241b;
      color: #fff8ef;
      border-color: #2b241b;
    }

    .admin-category-tab:hover,
    .admin-scope-button:hover,
    .admin-discount-chip:hover,
    .admin-pagination-button:hover {
      transform: translateY(-1px);
      border-color: rgba(43, 36, 27, 0.32);
    }

    .admin-products-selection-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
      padding: 14px 16px;
      border-radius: 18px;
      background: rgba(247, 242, 231, 0.78);
      border: 1px solid rgba(199, 165, 75, 0.14);
    }

    .admin-products-selection-summary {
      display: flex;
      flex-wrap: wrap;
      gap: 10px 16px;
      align-items: center;
      color: #5a4e3f;
    }

    .admin-products-selection-summary strong {
      color: #2b241b;
    }

    .admin-products-selection-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
    }

    .admin-products-selection-actions .admin-button {
      min-height: 42px;
    }

    .admin-product-bulk-panel {
      gap: 18px;
    }

    .admin-product-bulk-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      flex-wrap: wrap;
    }

    .admin-product-bulk-head h3 {
      margin: 0 0 6px;
      color: #2b241b;
    }

    .admin-product-discount-note {
      margin: 0;
      color: #6b5d49;
      max-width: 720px;
    }

    .admin-discount-chip-grid {
      align-items: center;
    }

    .admin-discount-custom-field {
      min-width: 160px;
      flex: 0 0 160px;
    }

    .admin-discount-custom-field .admin-input:disabled {
      opacity: 0.55;
      cursor: not-allowed;
    }

    .admin-products-advanced {
      border: 1px solid rgba(199, 165, 75, 0.16);
      border-radius: 18px;
      padding: 14px 16px;
      background: rgba(255, 255, 255, 0.78);
    }

    .admin-products-advanced summary {
      cursor: pointer;
      font-weight: 700;
      color: #2b241b;
      list-style: none;
    }

    .admin-products-advanced summary::-webkit-details-marker {
      display: none;
    }

    .admin-products-advanced[open] summary {
      margin-bottom: 14px;
    }

    .admin-products-table-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
      margin: 18px 0 12px;
    }

    .admin-products-table-results {
      color: #6b5d49;
      font-size: 0.92rem;
    }

    .admin-product-name-cell {
      min-width: 220px;
    }

    .admin-product-name-stack {
      display: grid;
      gap: 4px;
    }

    .admin-product-name-stack small,
    .admin-product-discount-stack small {
      color: #6b5d49;
      line-height: 1.4;
    }

    .admin-product-discount-empty {
      color: #8a8072;
      font-weight: 700;
    }

    .admin-product-discount-stack {
      min-width: 170px;
    }

    .admin-product-sale-pricing {
      min-width: 120px;
    }

    .admin-product-select-head input,
    .admin-product-select-cell input {
      width: 18px;
      height: 18px;
      accent-color: #2b241b;
      cursor: pointer;
    }

    .admin-table tbody tr[hidden] {
      display: none;
    }

    .admin-table tbody tr.is-selected {
      background: rgba(199, 165, 75, 0.08);
    }

    .admin-pagination-button[disabled] {
      opacity: 0.45;
      cursor: not-allowed;
      transform: none;
    }

    .admin-pagination-button[disabled]:hover {
      transform: none;
      border-color: rgba(43, 36, 27, 0.12);
    }

    .admin-visually-muted {
      color: #7a6d5c;
    }

    @media (max-width: 1080px) {
      .admin-products-toolbar-grid,
      .admin-products-table-controls,
      .admin-products-advanced-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .admin-products-toolbar-grid .admin-field-wide {
        grid-column: span 2;
      }
    }

    @media (max-width: 720px) {
      .admin-products-toolbar-grid,
      .admin-products-table-controls,
      .admin-products-advanced-grid {
        grid-template-columns: 1fr;
      }

      .admin-products-toolbar-grid .admin-field-wide {
        grid-column: span 1;
      }

      .admin-products-selection-bar,
      .admin-product-bulk-head,
      .admin-products-table-meta {
        flex-direction: column;
        align-items: stretch;
      }

      .admin-products-selection-actions,
      .admin-scope-toggle,
      .admin-discount-chip-grid,
      .admin-pagination {
        width: 100%;
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
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders"><span class="admin-nav-link-index">8. </span><span class="admin-nav-link-label">Custom Design Orders</span></a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings"><span class="admin-nav-link-index">9. </span><span class="admin-nav-link-label">Settings</span></a>
        <a class="admin-nav-link" href="admin-gift-cards.php" aria-label="Gift Cards" title="Gift Cards"><span class="admin-nav-link-index">10. </span><span class="admin-nav-link-label">Gift Cards</span></a>
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
          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-products.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if ($showAdminProductForm): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2><?php echo $escapeAdminProduct($adminProductFormHeading); ?></h2>
              <p class="admin-panel-note"><?php echo $escapeAdminProduct($adminProductFormNote); ?></p>
              <p class="admin-panel-note">Size and color are optional for synced catalog rows that represent base storefront products.</p>
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

            <?php if ($showAdminProductBarcodeInput): ?>
            <div class="admin-field">
              <label for="adminProductBarcode">Barcode</label>
              <input class="admin-input" id="adminProductBarcode" name="barcode" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['barcode']); ?>" placeholder="Auto from SKU if empty">
            </div>
            <?php endif; ?>

            <?php if ($showAdminProductDescriptionInput): ?>
            <div class="admin-field admin-field-wide">
              <label for="adminProductDescription">Description</label>
              <textarea class="admin-textarea" id="adminProductDescription" name="description"><?php echo $escapeAdminProduct($adminFormProduct['description']); ?></textarea>
            </div>
            <?php endif; ?>

            <div class="admin-field">
              <label for="adminProductPrice">Price</label>
              <input class="admin-input" id="adminProductPrice" name="price" type="number" step="0.01" min="0" value="<?php echo $escapeAdminProduct($adminFormProduct['price']); ?>" required>
            </div>

            <?php if ($showAdminProductSalePriceInput): ?>
            <div class="admin-field">
              <label for="adminProductSalePrice">Sale Price</label>
              <input class="admin-input" id="adminProductSalePrice" name="sale_price" type="number" step="0.01" min="0" value="<?php echo $escapeAdminProduct($adminFormProduct['sale_price']); ?>">
            </div>
            <?php endif; ?>

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
              <input class="admin-input" id="adminProductSize" name="size" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['size']); ?>" placeholder="S, M, L or 42">
            </div>

            <div class="admin-field">
              <label for="adminProductColor">Color</label>
              <input class="admin-input" id="adminProductColor" name="color" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['color']); ?>" placeholder="Black, Blue, White">
            </div>

            <div class="admin-field">
              <label for="adminProductStatus">Status</label>
              <select class="admin-select" id="adminProductStatus" name="status" required>
                <option value="active"<?php if ($adminFormProduct['status'] === 'active'): ?> selected<?php endif; ?>>Active</option>
                <option value="draft"<?php if ($adminFormProduct['status'] === 'draft'): ?> selected<?php endif; ?>>Draft</option>
                <option value="archived"<?php if ($adminFormProduct['status'] === 'archived'): ?> selected<?php endif; ?>>Archived</option>
              </select>
            </div>

            <?php if ($showAdminProductImageInput): ?>
            <div class="admin-field admin-field-wide">
              <label for="adminProductImage">Image URL / Path</label>
              <input class="admin-input" id="adminProductImage" name="image" type="text" value="<?php echo $escapeAdminProduct($adminFormProduct['image']); ?>" placeholder="https://... or uploads/...">
            </div>
            <?php endif; ?>

            <div class="admin-form-actions">
              <button class="admin-button admin-button-accent" type="submit"><?php echo $escapeAdminProduct($adminProductFormSubmit); ?></button>
              <?php if ($adminEditingProduct): ?>
                <a class="admin-button admin-button-soft" href="admin-products.php">Cancel Edit</a>
              <?php endif; ?>
            </div>
            <div id="adminProductsStatus" class="admin-feedback" role="status" aria-live="polite"<?php if ($adminProductErrorMessage): ?> style="color:#9f2f2f;"<?php endif; ?>><?php echo $escapeAdminProduct($adminProductErrorMessage ?: $adminProductStatusMessage); ?></div>
          </form>
        </article>
        <?php endif; ?>

        <?php if ($showAdminProductList): ?>
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Product List</h2>
              <p class="admin-panel-note">Current products stored in the admin database.</p>
            </div>
          </div>

          <form id="adminBulkDiscountForm" action="backend/admin/bulk-product-discount.php" method="POST">
            <input type="hidden" id="adminDiscountScopeInput" name="discount_scope" value="selected">
            <input type="hidden" id="adminDiscountPercentInput" name="discount_percent" value="">

            <div class="admin-products-toolbar">
              <div class="admin-products-toolbar-grid">
                <div class="admin-field admin-field-wide">
                  <label for="adminProductsSearch">Search Products</label>
                  <input class="admin-input" id="adminProductsSearch" type="search" placeholder="Search by product name, SKU, or barcode">
                  <p class="admin-products-search-note">Examples: FR-MEN-001, GRF-FR-MEN-001, Men's France T-Shirt</p>
                </div>

                <div class="admin-field">
                  <label for="adminProductsFilterStatus">Status Filter</label>
                  <select class="admin-select" id="adminProductsFilterStatus">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="on-sale">On Sale</option>
                    <option value="no-discount">No Discount</option>
                  </select>
                </div>
              </div>

              <div class="admin-category-tabs" role="tablist" aria-label="Product categories">
                <?php foreach ($adminCategoryLabels as $adminCategoryKey => $adminCategoryLabel): ?>
                  <button
                    class="admin-category-tab<?php echo $adminCategoryKey === 'all' ? ' is-active' : ''; ?>"
                    type="button"
                    data-category-filter="<?php echo $escapeAdminProduct($adminCategoryKey); ?>"
                    role="tab"
                    aria-selected="<?php echo $adminCategoryKey === 'all' ? 'true' : 'false'; ?>"
                  >
                    <?php echo $escapeAdminProduct($adminCategoryLabel); ?>
                    <span class="admin-visually-muted">(<?php echo $escapeAdminProduct($adminCategoryCounts[$adminCategoryKey] ?? 0); ?>)</span>
                  </button>
                <?php endforeach; ?>
              </div>

              <div class="admin-products-selection-bar">
                <div class="admin-products-selection-summary">
                  <strong id="adminProductsSelectedSummary">Selected: 0 products</strong>
                  <span id="adminProductsVisibleSummary">Showing 0 of <?php echo $escapeAdminProduct(count($adminProducts)); ?> products</span>
                  <span id="adminProductsScopeSummary">Scope: Selected Products</span>
                </div>

                <div class="admin-products-selection-actions">
                  <button class="admin-button admin-button-soft" type="button" id="adminSelectVisibleProducts">Select Visible Products</button>
                  <button class="admin-button admin-button-soft" type="button" id="adminClearSelectedProducts">Clear Selection</button>
                </div>
              </div>
            </div>

            <div class="admin-product-bulk-panel">
              <div class="admin-product-bulk-head">
                <div>
                  <h3>Product Discount</h3>
                  <p class="admin-product-discount-note">Common workflow: search a product, select one or more rows, choose 5%-50%, then apply the discount. Switch the scope to All Products only when you want a store-wide campaign.</p>
                </div>

                <div class="admin-scope-toggle" role="group" aria-label="Discount scope">
                  <button class="admin-scope-button is-active" type="button" data-discount-scope="selected">Selected Products</button>
                  <button class="admin-scope-button" type="button" data-discount-scope="all">All Products</button>
                </div>
              </div>

              <div class="admin-discount-chip-grid" role="group" aria-label="Discount percentages">
                <?php foreach ($adminDiscountPercentOptions as $discountPercentOption): ?>
                  <button class="admin-discount-chip" type="button" data-discount-chip="<?php echo $escapeAdminProduct($discountPercentOption); ?>"><?php echo $escapeAdminProduct($discountPercentOption); ?>%</button>
                <?php endforeach; ?>

                <div class="admin-field admin-discount-custom-field">
                  <label for="adminDiscountPercentCustom">Custom %</label>
                  <input class="admin-input" id="adminDiscountPercentCustom" type="number" min="5" max="50" step="1" placeholder="5-50" disabled>
                </div>
              </div>

              <details class="admin-products-advanced">
                <summary>Advanced Discount Settings</summary>
                <div class="admin-products-advanced-grid">
                  <div class="admin-field">
                    <label for="adminDiscountLabel">Campaign Label</label>
                    <input class="admin-input" id="adminDiscountLabel" name="discount_label" type="text" placeholder="Christmas Sale">
                  </div>

                  <div class="admin-field">
                    <label for="adminDiscountStartAt">Start Date</label>
                    <input class="admin-input" id="adminDiscountStartAt" name="discount_start_at" type="datetime-local">
                  </div>

                  <div class="admin-field">
                    <label for="adminDiscountEndAt">End Date</label>
                    <input class="admin-input" id="adminDiscountEndAt" name="discount_end_at" type="datetime-local">
                  </div>

                  <div class="admin-field">
                    <label class="admin-product-bulk-check" for="adminDiscountEnabled">
                      <input id="adminDiscountEnabled" name="discount_enabled" type="checkbox" checked>
                      <span>Enable discount</span>
                    </label>
                  </div>
                </div>
              </details>

              <div class="admin-form-actions">
                <button class="admin-button admin-button-accent" type="submit" name="discount_action" value="apply">Apply Discount</button>
                <button class="admin-button admin-button-soft" type="submit" name="discount_action" value="remove">Remove Discount</button>
                <button class="admin-button admin-button-danger" type="submit" name="discount_action" value="remove" data-force-scope="all" data-confirm-message="Remove discounts from all products?">Remove From All Products</button>
              </div>
            </div>

            <div class="admin-products-table-meta">
              <div class="admin-products-table-results" id="adminProductsResultsMeta">Showing 0 products</div>

              <div class="admin-products-table-controls">
                <div class="admin-field">
                  <label for="adminProductsRowsPerPage">Rows Per Page</label>
                  <select class="admin-select" id="adminProductsRowsPerPage">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="admin-table-wrap">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th class="admin-product-select-head"><input type="checkbox" id="adminProductsSelectAll" aria-label="Select all visible products"></th>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <?php if ($showAdminProductBarcodeColumn): ?>
                    <th>Barcode</th>
                    <?php endif; ?>
                    <th>Price</th>
                    <?php if ($showAdminProductSalePriceColumn): ?>
                    <th>Sale Price</th>
                    <?php endif; ?>
                    <th>Discount</th>
                    <th>Category</th>
                    <?php if ($showAdminProductVariantColumn): ?>
                    <th>Size / Color</th>
                    <?php endif; ?>
                    <?php if ($showAdminProductStatusColumn): ?>
                    <th>Status</th>
                    <?php endif; ?>
                    <th>Stock</th>
                    <th>Updated</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="adminProductsTableBody">
                  <?php if ($adminProducts): ?>
                    <?php foreach ($adminProducts as $product): ?>
                      <?php
                        $productId = (int) ($product['id'] ?? 0);
                        $productImage = $resolveAdminProductImage($product['image'] ?? '');
                        $discountStatus = strtolower(trim((string) ($product['discount_status'] ?? 'disabled')));
                        $discountCaption = trim((string) (($product['sale_caption'] ?? '') !== '' ? $product['sale_caption'] : ($product['discount_label'] ?? '')));
                        $productBarcode = ($product['barcode'] ?? '') !== '' ? $product['barcode'] : girffonAdminBuildProductBarcode((string) ($product['sku'] ?? ''));
                        $productVariant = $formatAdminProductVariant($product['size'] ?? '', $product['color'] ?? '');
                        $categoryKey = $adminNormalizeProductCategory($product['category'] ?? '');
                        $productStatus = $adminNormalizeProductStatus($product['status'] ?? 'active');
                        $productSearchText = implode(' ', [
                          (string) ($product['name'] ?? ''),
                          (string) ($product['sku'] ?? ''),
                          (string) $productBarcode,
                          (string) ($product['category'] ?? ''),
                        ]);
                        $isOnSale = !empty($product['is_on_sale']) && ($product['effective_sale_price'] ?? null) !== null;
                        $displayDiscountPercent = (int) ($product['display_discount_percent'] ?? 0);
                        $discountBadge = $displayDiscountPercent > 0 ? ($displayDiscountPercent . '% OFF') : trim((string) ($product['sale_badge'] ?? 'SALE'));
                        $salePriceValue = $isOnSale
                          ? $formatAdminProductCurrency($product['effective_sale_price'] ?? 0)
                          : (($product['sale_price'] ?? null) !== null && $product['sale_price'] !== '' ? $formatAdminProductCurrency($product['sale_price']) : '-');
                        $updatedAtDisplay = $formatAdminProductDateTime($product['updated_at'] ?? $product['created_at'] ?? '');
                      ?>
                      <tr
                        class="admin-product-row"
                        data-product-id="<?php echo $escapeAdminProduct($productId); ?>"
                        data-search-text="<?php echo $escapeAdminProduct($productSearchText); ?>"
                        data-category-slug="<?php echo $escapeAdminProduct($categoryKey); ?>"
                        data-product-status="<?php echo $escapeAdminProduct($productStatus); ?>"
                        data-discount-filter="<?php echo $escapeAdminProduct($isOnSale ? 'on-sale' : 'no-discount'); ?>"
                      >
                        <td class="admin-product-select-cell">
                          <input type="checkbox" class="admin-product-select" name="product_ids[]" value="<?php echo $escapeAdminProduct($productId); ?>" aria-label="Select <?php echo $escapeAdminProduct($product['name'] ?? 'product'); ?>">
                        </td>
                        <td>
                          <?php if ($productImage): ?>
                            <img class="admin-order-thumb" src="<?php echo $escapeAdminProduct($productImage); ?>" alt="<?php echo $escapeAdminProduct($product['name'] ?? 'Product image'); ?>">
                          <?php else: ?>
                            <div class="admin-order-thumb admin-order-thumb-placeholder">No image</div>
                          <?php endif; ?>
                        </td>
                        <td class="admin-product-name-cell">
                          <div class="admin-product-name-stack">
                            <strong><?php echo $escapeAdminProduct($product['name'] ?? ''); ?></strong>
                            <?php if (trim((string) ($product['description'] ?? '')) !== ''): ?>
                              <small><?php echo $escapeAdminProduct($product['description']); ?></small>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td><?php echo $escapeAdminProduct($product['sku'] ?? '-'); ?></td>
                        <?php if ($showAdminProductBarcodeColumn): ?>
                        <td class="admin-product-barcode-cell">
                          <div class="admin-product-barcode-box">
                            <svg class="admin-product-barcode-svg" data-product-barcode value="<?php echo $escapeAdminProduct($productBarcode); ?>" aria-label="Barcode for <?php echo $escapeAdminProduct($product['name'] ?? 'Product'); ?>"></svg>
                            <span class="admin-product-barcode-value"><?php echo $escapeAdminProduct($productBarcode); ?></span>
                          </div>
                        </td>
                        <?php endif; ?>
                        <td><?php echo $escapeAdminProduct($formatAdminProductCurrency($product['price'] ?? 0)); ?></td>
                        <?php if ($showAdminProductSalePriceColumn): ?>
                        <td>
                          <?php if ($isOnSale): ?>
                            <div class="admin-product-sale-pricing">
                              <strong><?php echo $escapeAdminProduct($salePriceValue); ?></strong>
                              <span><?php echo $escapeAdminProduct($formatAdminProductCurrency($product['price'] ?? 0)); ?></span>
                            </div>
                          <?php else: ?>
                            <?php echo $escapeAdminProduct($salePriceValue); ?>
                          <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td>
                          <?php if ($isOnSale || $discountStatus !== 'disabled'): ?>
                            <div class="admin-product-discount-stack">
                              <?php if ($isOnSale): ?>
                                <span class="admin-product-sale-badge"><?php echo $escapeAdminProduct($discountBadge); ?></span>
                              <?php else: ?>
                                <span class="admin-product-sale-status"><?php echo $escapeAdminProduct(ucfirst($discountStatus)); ?></span>
                              <?php endif; ?>
                              <?php if ($discountCaption !== '' && strcasecmp($discountCaption, $discountBadge) !== 0): ?>
                                <small><?php echo $escapeAdminProduct($discountCaption); ?></small>
                              <?php endif; ?>
                              <?php if (($product['discount_start_at'] ?? '') !== '' || ($product['discount_end_at'] ?? '') !== ''): ?>
                                <small>
                                  <?php echo $escapeAdminProduct(($product['discount_start_at'] ?? '') !== '' ? $formatAdminProductDateTime($product['discount_start_at']) : 'Now'); ?>
                                  <?php echo $escapeAdminProduct(' -> '); ?>
                                  <?php echo $escapeAdminProduct(($product['discount_end_at'] ?? '') !== '' ? $formatAdminProductDateTime($product['discount_end_at']) : 'Open End'); ?>
                                </small>
                              <?php endif; ?>
                            </div>
                          <?php else: ?>
                            <span class="admin-product-discount-empty">&mdash;</span>
                          <?php endif; ?>
                        </td>
                        <td><?php echo $escapeAdminProduct($product['category'] ?? '-'); ?></td>
                        <?php if ($showAdminProductVariantColumn): ?>
                        <td><?php echo $escapeAdminProduct($productVariant); ?></td>
                        <?php endif; ?>
                        <?php if ($showAdminProductStatusColumn): ?>
                        <td><?php echo $formatAdminProductLabel($productStatus); ?></td>
                        <?php endif; ?>
                        <td><?php echo $escapeAdminProduct($product['stock'] ?? 0); ?></td>
                        <td><?php echo $escapeAdminProduct($updatedAtDisplay !== '' ? $updatedAtDisplay : '-'); ?></td>
                        <td>
                          <div class="admin-product-table-actions">
                            <?php if ($showAdminProductEditAction): ?>
                              <a class="admin-button admin-button-soft" href="admin-products.php?edit=<?php echo $escapeAdminProduct($productId); ?>" aria-label="Edit product" title="Edit product">
                                <svg class="admin-product-action-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M4 20h4l10-10-4-4L4 16v4Z" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M12 6l4 4" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </a>
                            <?php endif; ?>
                            <?php if ($showAdminProductPrintAction): ?>
                              <button
                                class="admin-button admin-button-soft"
                                type="button"
                                data-print-product-barcode
                                data-product-name="<?php echo $escapeAdminProduct($product['name'] ?? 'Product'); ?>"
                                data-product-sku="<?php echo $escapeAdminProduct($product['sku'] ?? '-'); ?>"
                                data-product-barcode-value="<?php echo $escapeAdminProduct($productBarcode); ?>"
                                data-product-price="<?php echo $escapeAdminProduct($salePriceValue); ?>"
                                data-product-variant="<?php echo $escapeAdminProduct($productVariant); ?>"
                                aria-label="Print barcode"
                                title="Print barcode"
                              >
                                <svg class="admin-product-action-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M7 8V4h10v4" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M6 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-1" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M7 14h10v6H7z" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M17 11h.01" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </button>
                            <?php endif; ?>
                            <?php if ($showAdminProductDeleteAction): ?>
                              <button class="admin-button admin-button-danger" type="submit" form="adminDeleteProduct<?php echo $escapeAdminProduct($productId); ?>" aria-label="Delete product" title="Delete product">
                                <svg class="admin-product-action-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M3 6h18" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M8 6V4h8v2" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M19 6l-1 14H6L5 6" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M10 11v6M14 11v6" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </button>
                            <?php endif; ?>
                            <?php if (!$showAdminProductEditAction && !$showAdminProductPrintAction && !$showAdminProductDeleteAction): ?>
                              <span class="admin-panel-note">Locked</span>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <tr id="adminProductsNoResults" hidden>
                      <td colspan="<?php echo $escapeAdminProduct($adminProductTableColumnCount); ?>" class="admin-empty">No products match the current search or filters.</td>
                    </tr>
                  <?php else: ?>
                    <tr>
                      <td colspan="<?php echo $escapeAdminProduct($adminProductTableColumnCount); ?>" class="admin-empty">No products found in the database yet.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="admin-products-table-meta">
              <div class="admin-products-table-results" id="adminProductsPageMeta">Page 1 of 1</div>
              <div class="admin-pagination" id="adminProductsPagination" aria-label="Products pagination"></div>
            </div>
          </form>

          <?php if ($showAdminProductDeleteAction && $adminProducts): ?>
            <?php foreach ($adminProducts as $product): ?>
              <form id="adminDeleteProduct<?php echo $escapeAdminProduct((int) ($product['id'] ?? 0)); ?>" class="admin-hidden-form" action="backend/admin/delete-product.php" method="POST" onsubmit="return confirm('Delete this product?');">
                <input type="hidden" name="id" value="<?php echo $escapeAdminProduct((int) ($product['id'] ?? 0)); ?>">
              </form>
            <?php endforeach; ?>
          <?php endif; ?>
        </article>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
  <script>
    (function () {
      const code39Patterns = {
        '0': 'nnnwwnwnn', '1': 'wnnwnnnnw', '2': 'nnwwnnnnw', '3': 'wnwwnnnnn', '4': 'nnnwwnnnw',
        '5': 'wnnwwnnnn', '6': 'nnwwwnnnn', '7': 'nnnwnnwnw', '8': 'wnnwnnwnn', '9': 'nnwwnnwnn',
        'A': 'wnnnnwnnw', 'B': 'nnwnnwnnw', 'C': 'wnwnnwnnn', 'D': 'nnnnwwnnw', 'E': 'wnnnwwnnn',
        'F': 'nnwnwwnnn', 'G': 'nnnnnwwnw', 'H': 'wnnnnwwnn', 'I': 'nnwnnwwnn', 'J': 'nnnnwwwnn',
        'K': 'wnnnnnnww', 'L': 'nnwnnnnww', 'M': 'wnwnnnnwn', 'N': 'nnnnwnnww', 'O': 'wnnnwnnwn',
        'P': 'nnwnwnnwn', 'Q': 'nnnnnnwww', 'R': 'wnnnnnwwn', 'S': 'nnwnnnwwn', 'T': 'nnnnwnwwn',
        'U': 'wwnnnnnnw', 'V': 'nwwnnnnnw', 'W': 'wwwnnnnnn', 'X': 'nwnnwnnnw', 'Y': 'wwnnwnnnn',
        'Z': 'nwwnwnnnn', '-': 'nwnnnnwnw', '.': 'wwnnnnwnn', ' ': 'nwwnnnwnn', '$': 'nwnwnwnnn',
        '/': 'nwnwnnnwn', '+': 'nwnnnwnwn', '%': 'nnnwnwnwn', '*': 'nwnnwnwnn'
      };

      function escapeHtml(value) {
        return String(value || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;');
      }

      function normalizeBarcodeValue(value) {
        return String(value || '').toUpperCase().replace(/[^0-9A-Z\-\.\ $\/\+%]/g, '');
      }

      function renderCode39Fallback(svg, rawValue) {
        if (!svg) {
          return;
        }

        const value = normalizeBarcodeValue(rawValue);
        const encoded = '*' + value + '*';
        const barHeight = 34;
        const quietZone = 10;
        const narrow = 2;
        const wide = 4;
        let x = quietZone;
        let bars = '';

        for (let index = 0; index < encoded.length; index += 1) {
          const symbol = encoded.charAt(index);
          const pattern = code39Patterns[symbol] || code39Patterns['*'];
          for (let step = 0; step < pattern.length; step += 1) {
            const width = pattern.charAt(step) === 'w' ? wide : narrow;
            if (step % 2 === 0) {
              bars += '<rect x="' + x + '" y="4" width="' + width + '" height="' + barHeight + '" fill="#1f1a14"></rect>';
            }
            x += width;
          }
          x += narrow;
        }

        const totalWidth = x + quietZone;
        svg.setAttribute('viewBox', '0 0 ' + totalWidth + ' 46');
        svg.innerHTML = bars + '<text x="' + (totalWidth / 2) + '" y="44" text-anchor="middle" font-size="7.5" letter-spacing="1.2" fill="#1f1a14">' + escapeHtml(value) + '</text>';
      }

      const bulkDiscountForm = document.getElementById('adminBulkDiscountForm');
      const selectAllProducts = document.getElementById('adminProductsSelectAll');
      const productsTableBody = document.getElementById('adminProductsTableBody');
      const productsNoResults = document.getElementById('adminProductsNoResults');
      const searchInput = document.getElementById('adminProductsSearch');
      const statusFilter = document.getElementById('adminProductsFilterStatus');
      const rowsPerPageSelect = document.getElementById('adminProductsRowsPerPage');
      const categoryButtons = Array.from(document.querySelectorAll('[data-category-filter]'));
      const scopeButtons = Array.from(document.querySelectorAll('[data-discount-scope]'));
      const percentButtons = Array.from(document.querySelectorAll('[data-discount-chip]'));
      const customPercentInput = document.getElementById('adminDiscountPercentCustom');
      const discountPercentInput = document.getElementById('adminDiscountPercentInput');
      const discountScopeInput = document.getElementById('adminDiscountScopeInput');
      const selectedSummary = document.getElementById('adminProductsSelectedSummary');
      const visibleSummary = document.getElementById('adminProductsVisibleSummary');
      const scopeSummary = document.getElementById('adminProductsScopeSummary');
      const resultsMeta = document.getElementById('adminProductsResultsMeta');
      const pageMeta = document.getElementById('adminProductsPageMeta');
      const paginationRoot = document.getElementById('adminProductsPagination');
      const selectVisibleButton = document.getElementById('adminSelectVisibleProducts');
      const clearSelectedButton = document.getElementById('adminClearSelectedProducts');
      const productRows = productsTableBody ? Array.from(productsTableBody.querySelectorAll('.admin-product-row')) : [];

      const productsState = {
        search: '',
        category: 'all',
        status: 'all',
        rowsPerPage: Number(rowsPerPageSelect && rowsPerPageSelect.value) || 10,
        page: 1,
        scope: discountScopeInput ? discountScopeInput.value : 'selected',
        selectedDiscount: '',
        customDiscount: false
      };

      function normalizeFilterValue(value) {
        return String(value || '')
          .toLowerCase()
          .replace(/&/g, ' and ')
          .replace(/[^a-z0-9]+/g, ' ')
          .trim()
          .replace(/\s+/g, ' ');
      }

      function getCheckedProducts() {
        return productRows.filter(function (row) {
          const checkbox = row.querySelector('.admin-product-select');
          return Boolean(checkbox && checkbox.checked);
        });
      }

      function getFilteredRows() {
        return productRows.filter(function (row) {
          const searchText = normalizeFilterValue(row.dataset.searchText || '');
          const matchesSearch = productsState.search === '' || searchText.indexOf(productsState.search) !== -1;
          const matchesCategory = productsState.category === 'all' || row.dataset.categorySlug === productsState.category;
          const matchesStatus = productsState.status === 'all'
            || (productsState.status === 'active' && row.dataset.productStatus === 'active')
            || (productsState.status === 'on-sale' && row.dataset.discountFilter === 'on-sale')
            || (productsState.status === 'no-discount' && row.dataset.discountFilter === 'no-discount');
          return matchesSearch && matchesCategory && matchesStatus;
        });
      }

      function getVisibleRows() {
        return getFilteredRows().filter(function (row) {
          return !row.hidden;
        });
      }

      function updateSelectionSummary() {
        const checkedProducts = getCheckedProducts();
        const checkedCount = checkedProducts.length;
        const filteredRows = getFilteredRows();
        const visibleRows = getVisibleRows();
        const totalProducts = productRows.length;

        if (selectedSummary) {
          selectedSummary.textContent = 'Selected: ' + checkedCount + ' product' + (checkedCount === 1 ? '' : 's');
        }

        if (visibleSummary) {
          visibleSummary.textContent = 'Showing ' + visibleRows.length + ' of ' + filteredRows.length + ' filtered products';
        }

        if (scopeSummary) {
          scopeSummary.textContent = productsState.scope === 'all'
            ? 'Scope: All Products (' + totalProducts + ')'
            : 'Scope: Selected Products';
        }

        if (resultsMeta) {
          resultsMeta.textContent = filteredRows.length === totalProducts
            ? 'Showing ' + filteredRows.length + ' total products'
            : 'Showing ' + filteredRows.length + ' filtered of ' + totalProducts + ' total products';
        }

        if (selectAllProducts) {
          const visibleCheckboxes = visibleRows.map(function (row) {
            return row.querySelector('.admin-product-select');
          }).filter(Boolean);
          const checkedVisible = visibleCheckboxes.filter(function (checkbox) { return checkbox.checked; }).length;
          selectAllProducts.checked = visibleCheckboxes.length > 0 && checkedVisible === visibleCheckboxes.length;
          selectAllProducts.indeterminate = checkedVisible > 0 && checkedVisible < visibleCheckboxes.length;
        }

        productRows.forEach(function (row) {
          const checkbox = row.querySelector('.admin-product-select');
          row.classList.toggle('is-selected', Boolean(checkbox && checkbox.checked));
        });
      }

      function renderPagination(filteredRows) {
        if (!paginationRoot) {
          return;
        }

        paginationRoot.innerHTML = '';
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / productsState.rowsPerPage));

        if (pageMeta) {
          pageMeta.textContent = 'Page ' + productsState.page + ' of ' + totalPages;
        }

        function appendPageButton(label, page, disabled, isActive) {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'admin-pagination-button' + (isActive ? ' is-active' : '');
          button.textContent = label;
          button.disabled = Boolean(disabled);
          button.addEventListener('click', function () {
            if (disabled || page === productsState.page) {
              return;
            }
            productsState.page = page;
            updateTableView();
          });
          paginationRoot.appendChild(button);
        }

        appendPageButton('Previous', Math.max(1, productsState.page - 1), productsState.page <= 1, false);
        for (let page = 1; page <= totalPages; page += 1) {
          appendPageButton(String(page), page, false, page === productsState.page);
        }
        appendPageButton('Next', Math.min(totalPages, productsState.page + 1), productsState.page >= totalPages, false);
      }

      function updateTableView() {
        const filteredRows = getFilteredRows();
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / productsState.rowsPerPage));
        productsState.page = Math.min(productsState.page, totalPages);
        productsState.page = Math.max(productsState.page, 1);

        const startIndex = (productsState.page - 1) * productsState.rowsPerPage;
        const endIndex = startIndex + productsState.rowsPerPage;

        productRows.forEach(function (row) {
          row.hidden = true;
        });

        filteredRows.slice(startIndex, endIndex).forEach(function (row) {
          row.hidden = false;
        });

        if (productsNoResults) {
          productsNoResults.hidden = filteredRows.length !== 0;
        }

        renderPagination(filteredRows);
        updateSelectionSummary();
      }

      function setDiscountScope(scope) {
        productsState.scope = scope === 'all' ? 'all' : 'selected';
        if (discountScopeInput) {
          discountScopeInput.value = productsState.scope;
        }
        scopeButtons.forEach(function (button) {
          const active = button.getAttribute('data-discount-scope') === productsState.scope;
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        updateSelectionSummary();
      }

      function setDiscountPercent(value, customMode) {
        const normalized = String(value || '').trim();
        productsState.selectedDiscount = normalized;
        productsState.customDiscount = Boolean(customMode);
        if (discountPercentInput) {
          discountPercentInput.value = normalized;
        }

        percentButtons.forEach(function (button) {
          const active = !productsState.customDiscount && button.getAttribute('data-discount-chip') === normalized;
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (customPercentInput) {
          customPercentInput.disabled = !productsState.customDiscount;
          if (productsState.customDiscount) {
            customPercentInput.value = normalized;
          } else {
            customPercentInput.value = '';
          }
        }
      }

      if (searchInput) {
        searchInput.addEventListener('input', function () {
          productsState.search = normalizeFilterValue(searchInput.value);
          productsState.page = 1;
          updateTableView();
        });
      }

      if (statusFilter) {
        statusFilter.addEventListener('change', function () {
          productsState.status = statusFilter.value || 'all';
          productsState.page = 1;
          updateTableView();
        });
      }

      if (rowsPerPageSelect) {
        rowsPerPageSelect.addEventListener('change', function () {
          productsState.rowsPerPage = Math.max(1, Number(rowsPerPageSelect.value) || 10);
          productsState.page = 1;
          updateTableView();
        });
      }

      categoryButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          productsState.category = button.getAttribute('data-category-filter') || 'all';
          productsState.page = 1;
          categoryButtons.forEach(function (candidate) {
            const active = candidate === button;
            candidate.classList.toggle('is-active', active);
            candidate.setAttribute('aria-selected', active ? 'true' : 'false');
          });
          updateTableView();
        });
      });

      scopeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          setDiscountScope(button.getAttribute('data-discount-scope') || 'selected');
        });
      });

      percentButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          setDiscountPercent(button.getAttribute('data-discount-chip') || '', false);
        });
      });

      if (customPercentInput) {
        customPercentInput.addEventListener('focus', function () {
          productsState.customDiscount = true;
          percentButtons.forEach(function (button) {
            button.classList.remove('is-active');
            button.setAttribute('aria-pressed', 'false');
          });
          customPercentInput.disabled = false;
        });

        customPercentInput.addEventListener('input', function () {
          const rawValue = String(customPercentInput.value || '').trim();
          setDiscountPercent(rawValue, true);
        });
      }

      if (selectAllProducts) {
        selectAllProducts.addEventListener('change', function () {
          getVisibleRows().forEach(function (row) {
            const checkbox = row.querySelector('.admin-product-select');
            if (checkbox) {
              checkbox.checked = selectAllProducts.checked;
            }
          });
          updateSelectionSummary();
        });
      }

      if (selectVisibleButton) {
        selectVisibleButton.addEventListener('click', function () {
          getVisibleRows().forEach(function (row) {
            const checkbox = row.querySelector('.admin-product-select');
            if (checkbox) {
              checkbox.checked = true;
            }
          });
          updateSelectionSummary();
        });
      }

      if (clearSelectedButton) {
        clearSelectedButton.addEventListener('click', function () {
          productRows.forEach(function (row) {
            const checkbox = row.querySelector('.admin-product-select');
            if (checkbox) {
              checkbox.checked = false;
            }
          });
          updateSelectionSummary();
        });
      }

      productRows.forEach(function (row) {
        const checkbox = row.querySelector('.admin-product-select');
        if (!checkbox) {
          return;
        }
        checkbox.addEventListener('change', updateSelectionSummary);
      });

      if (bulkDiscountForm) {
        bulkDiscountForm.addEventListener('submit', function (event) {
          const submitter = event.submitter;
          const action = submitter ? String(submitter.value || '').toLowerCase() : '';
          const forceScope = submitter ? submitter.getAttribute('data-force-scope') : '';
          const confirmMessage = submitter ? submitter.getAttribute('data-confirm-message') : '';
          const checkedCount = getCheckedProducts().length;

          if (forceScope === 'all') {
            setDiscountScope('all');
          }

          if (confirmMessage && !window.confirm(confirmMessage)) {
            event.preventDefault();
            return;
          }

          if (productsState.scope !== 'all' && checkedCount === 0) {
            window.alert('Select at least one product or switch the scope to All Products.');
            event.preventDefault();
            return;
          }

          if (action === 'apply') {
            const discountValue = Number.parseInt(String(discountPercentInput && discountPercentInput.value || ''), 10);
            if (!Number.isFinite(discountValue) || discountValue < 5 || discountValue > 50) {
              window.alert('Choose a discount between 5% and 50% before applying.');
              event.preventDefault();
            }
          }
        });
      }

      setDiscountScope(productsState.scope);
      updateTableView();

      function renderProductBarcode(svg) {
        const value = normalizeBarcodeValue(svg && svg.getAttribute('value'));
        if (!value) {
          return;
        }

        if (window.JsBarcode) {
          window.JsBarcode(svg, value, {
            format: 'CODE128',
            displayValue: false,
            margin: 0,
            width: 1.5,
            height: 38,
            background: '#ffffff',
            lineColor: '#1f1a14'
          });
          return;
        }

        renderCode39Fallback(svg, value);
      }

      function renderAllProductBarcodes(root) {
        Array.from((root || document).querySelectorAll('[data-product-barcode]')).forEach(renderProductBarcode);
      }

      function openPrintLabel(button) {
        const name = button.getAttribute('data-product-name') || 'Product';
        const sku = button.getAttribute('data-product-sku') || '-';
        const barcode = button.getAttribute('data-product-barcode-value') || sku;
        const price = button.getAttribute('data-product-price') || '-';
        const variant = button.getAttribute('data-product-variant') || '- / -';
        const row = button.closest('tr');
        const barcodeSvg = row ? row.querySelector('[data-product-barcode]') : null;
        const barcodeMarkup = barcodeSvg ? barcodeSvg.outerHTML : '';
        const printWindow = window.open('', '_blank', 'width=460,height=620');

        if (!printWindow) {
          return;
        }

        printWindow.document.open();
        printWindow.document.write('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Print Barcode</title><style>body{margin:0;padding:24px;background:#fff;color:#1f1a14;font-family:Georgia,"Times New Roman",serif}.admin-product-print-label{width:360px;padding:18px 20px;border:1px solid #d8c7a1}.admin-product-print-label h1{margin:0 0 8px;font-size:1.1rem}.admin-product-print-barcode{margin:14px 0 8px}.admin-product-print-barcode svg{width:100%;height:86px;display:block}.admin-product-print-meta{display:grid;gap:4px;font-size:.92rem}.admin-product-print-meta strong{display:inline-block;min-width:56px}@media print{body{padding:0}.admin-product-print-label{border:0;width:auto}}</style></head><body><div class="admin-product-print-label"><h1>' + escapeHtml(name) + '</h1><div class="admin-product-print-barcode">' + barcodeMarkup + '</div><div class="admin-product-print-meta"><div><strong>SKU:</strong> ' + escapeHtml(sku) + '</div><div><strong>Barcode:</strong> ' + escapeHtml(barcode) + '</div><div><strong>Price:</strong> ' + escapeHtml(price) + '</div><div><strong>Variant:</strong> ' + escapeHtml(variant) + '</div></div></div><script>window.onload=function(){window.print();};<\/script></body></html>');
        printWindow.document.close();
      }

      document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-print-product-barcode]');
        if (!button) {
          return;
        }

        openPrintLabel(button);
      });

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
          renderAllProductBarcodes(document);
        });
      } else {
        renderAllProductBarcodes(document);
      }
    }());
  </script>
  <script src="JS/admin-girffon.js?v=20260518r11"></script>
</body>
</html>
