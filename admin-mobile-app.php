<?php
require_once __DIR__ . '/backend/admin/session.php';
require_once __DIR__ . '/backend/admin/mobile-app-data.php';
require_once __DIR__ . '/backend/admin/custom-design-orders-data.php';
require_once __DIR__ . '/backend/admin/users-data.php';
require_once __DIR__ . '/backend/admin/products-data.php';
require_once __DIR__ . '/backend/utils/csrf.php';

function girffonMobileAdminEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function girffonMobileAdminDateInput($value): string
{
    $timestamp = $value ? strtotime((string) $value) : false;
    return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
}

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$adminUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));
$csrfToken = girffonCsrfToken();
$definitions = girffonMobileAppContentDefinitions();
$shopNavigationCategories = girffonShopNavigationCategories();
$tabs = ['home' => 'Home', 'shop' => 'Shop', 'custom-design' => 'Custom Design', 'account' => 'Account'];
$areaLabels = [
    'home' => [
        'banner' => 'Banner',
        'category-buttons' => 'Category Buttons',
        'shopping-cart' => 'Shopping Cart',
        'custom-design' => 'Custom Design',
        'catalog' => 'Catalog',
        'gift-cards' => 'Gift Cards',
        'bundles' => 'Bundles',
        'new-arrivals' => 'New Arrivals',
    ],
    'shop' => [
        'category-buttons' => 'Category Buttons',
        'shop-by-product' => 'Shop By Product',
      'new-arrivals' => 'New Arrivals',
      'best-sellers' => 'Best Sellers',
        'make-it-yours' => 'Make It Yours',
        'gift-cards' => 'Gift Cards',
        'bundle' => 'Bundle',
    ],
];
$areaNotes = [
    'banner' => 'Main Home slider media and timing',
    'category-buttons' => 'Compact category destinations',
    'shopping-cart' => 'Cart promotion and messages',
    'custom-design' => 'Home entry to existing Custom Design',
    'catalog' => 'Presentation of the shared catalog',
    'gift-cards' => 'Presentation of existing Gift Cards',
    'bundles' => 'Bundle presentation only',
    'new-arrivals' => 'Number of New Arrivals product cards',
    'best-sellers' => 'Number of Best Sellers product cards',
    'shop-by-product' => 'Custom product destinations using the shared catalog',
    'make-it-yours' => 'Shop entry to existing Custom Design',
    'bundle' => 'Bundle presentation only',
];

$tab = strtolower(trim((string) ($_GET['tab'] ?? 'home')));
if (!isset($tabs[$tab])) {
    $tab = 'home';
}
$area = strtolower(trim((string) ($_GET['area'] ?? '')));
if (!isset($areaLabels[$tab][$area])) {
    $area = '';
}
$selectedKey = strtolower(trim((string) ($_GET['section'] ?? $_POST['section_key'] ?? '')));
if (!isset($definitions[$selectedKey]) || $definitions[$selectedKey]['group'] !== $tab || ($area !== '' && $definitions[$selectedKey]['area'] !== $area)) {
    $selectedKey = '';
}
$statusMessage = '';
$errorMessage = '';
$previewInput = null;
$shopIconOptions = [];
foreach (glob(__DIR__ . '/Cart/icons/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [] as $iconPath) {
    $shopIconOptions[] = 'Cart/icons/' . basename($iconPath);
}
natcasesort($shopIconOptions);
$shopIconOptions = array_values($shopIconOptions);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!girffonCsrfValidate($_POST['_csrf'] ?? '')) {
            throw new RuntimeException('Security validation failed. Refresh the page and try again.');
        }
        $action = (string) ($_POST['mobile_action'] ?? 'save');
        $sectionKey = strtolower(trim((string) ($_POST['section_key'] ?? '')));
        if (!isset($definitions[$sectionKey])) {
            throw new InvalidArgumentException('Unknown Mobile App content entry.');
        }
        $tab = $definitions[$sectionKey]['group'];
        $area = $definitions[$sectionKey]['area'];
        $selectedKey = $sectionKey;

        if ($action === 'rollback') {
            $result = girffonAdminRollbackMobileContent($pdo, $sectionKey, (int) ($_POST['revision'] ?? 0), $adminId, $adminUsername);
            header('Location: admin-mobile-app.php?tab=' . rawurlencode($tab) . '&area=' . rawurlencode($area) . '&section=' . rawurlencode($sectionKey) . '&status=rolled-back&revision=' . (int) $result['revision']);
            exit;
        }

        $input = [];
        foreach (['section_key', 'section_name', 'title', 'subtitle', 'description', 'image_url', 'mobile_image_url', 'tablet_image_url', 'button_text', 'button_destination', 'start_at', 'end_at', 'price', 'promotional_price', 'free_shipping_message', 'discount_message', 'bundle_message'] as $field) {
            $input[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $input['is_enabled'] = isset($_POST['is_enabled']) ? 1 : 0;
        $input['display_order'] = max(0, min(65535, (int) ($_POST['display_order'] ?? 0)));
        $input['settings'] = is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [];
        if ($sectionKey === 'shop-by-product') {
            $submittedItems = is_array($input['settings']['items'] ?? null) ? $input['settings']['items'] : [];
            $selectedIcons = is_array($_POST['shop_item_icon_select'] ?? null) ? $_POST['shop_item_icon_select'] : [];
            $input['settings']['items'] = [];
            foreach ($submittedItems as $itemIndex => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $selectedIcon = trim((string) ($selectedIcons[$itemIndex] ?? ''));
                if ($selectedIcon !== '') {
                    $item['icon'] = $selectedIcon;
                }
                $input['settings']['items'][$itemIndex] = $item;
            }
        }
        foreach (['muted_autoplay', 'loop', 'play_to_end'] as $toggle) {
            $input['settings'][$toggle] = isset($_POST['settings'][$toggle]) ? 1 : 0;
        }

        if ($action === 'preview') {
            $previewInput = $input;
            $statusMessage = 'Draft preview prepared. Nothing was saved or published.';
        } else {
            foreach (['image_file' => 'image_url', 'mobile_image_file' => 'mobile_image_url', 'tablet_image_file' => 'tablet_image_url'] as $fileField => $pathField) {
                if (isset($_FILES[$fileField])) {
                    $uploadedPath = girffonAdminMobileUploadImage($_FILES[$fileField]);
                    if ($uploadedPath !== '') {
                        $input[$pathField] = $uploadedPath;
                    }
                }
            }
                if ($sectionKey === 'shop-by-product' && isset($_FILES['shop_item_icon_file']['name']) && is_array($_FILES['shop_item_icon_file']['name'])) {
                  foreach ($_FILES['shop_item_icon_file']['name'] as $itemIndex => $fileName) {
                    if ((string) $fileName === '' || !isset($input['settings']['items'][$itemIndex])) {
                      continue;
                    }
                    $uploadedPath = girffonAdminMobileUploadImage([
                      'name' => $fileName,
                      'type' => $_FILES['shop_item_icon_file']['type'][$itemIndex] ?? '',
                      'tmp_name' => $_FILES['shop_item_icon_file']['tmp_name'][$itemIndex] ?? '',
                      'error' => $_FILES['shop_item_icon_file']['error'][$itemIndex] ?? UPLOAD_ERR_NO_FILE,
                      'size' => $_FILES['shop_item_icon_file']['size'][$itemIndex] ?? 0,
                    ]);
                    if ($uploadedPath !== '') {
                      $input['settings']['items'][$itemIndex]['icon'] = $uploadedPath;
                    }
                  }
                }
            $result = girffonAdminSaveMobileContent($pdo, $input, $adminId, $adminUsername, $action === 'publish');
            $status = $action === 'publish' ? 'published' : 'saved';
            header('Location: admin-mobile-app.php?tab=' . rawurlencode($tab) . '&area=' . rawurlencode($area) . '&section=' . rawurlencode($sectionKey) . '&status=' . $status . '&revision=' . (int) $result['revision']);
            exit;
        }
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

try {
    $contentRows = girffonAdminFetchMobileContent($pdo);
    $sharedSystems = girffonAdminMobileSharedSystems($pdo);
} catch (Throwable $exception) {
    $contentRows = [];
    $sharedSystems = [];
    $errorMessage = $errorMessage ?: $exception->getMessage();
}
$rowsByKey = [];
foreach ($contentRows as $row) {
    if (isset($definitions[$row['section_key']])) {
        $rowsByKey[$row['section_key']] = $row;
    }
}
$areaEntries = [];
if ($area !== '') {
    foreach ($definitions as $key => $definition) {
        if ($definition['group'] === $tab && $definition['area'] === $area) {
            $areaEntries[$key] = $definition;
        }
    }
    if ($selectedKey === '') {
        $selectedKey = (string) array_key_first($areaEntries);
    }
}
$selected = $previewInput ?? ($rowsByKey[$selectedKey] ?? []);
$settings = is_array($selected['settings'] ?? null) ? $selected['settings'] : [];
$history = $selectedKey !== '' ? girffonAdminMobileContentHistory($pdo, $selectedKey) : [];
$productCardSectionKeys = ['home-new-arrivals', 'shop-new-arrivals', 'shop-best-sellers'];
$productCardCatalog = [];
$productCardPreviewProducts = [];
$selectedProductIds = [];
$bestSellerAutomaticAvailable = false;
if (in_array($selectedKey, $productCardSectionKeys, true)) {
  $selectedProductIds = array_values(array_unique(array_filter(array_map('intval', (array) ($settings['manual_product_ids'] ?? [])))));
  $selectedProductPositions = array_flip($selectedProductIds);
  $productCardCatalog = array_values(array_filter(girffonAdminFetchProducts($pdo), static function (array $product): bool {
    $status = strtolower(trim((string) ($product['status'] ?? 'active')));
    return in_array($status, ['', 'active', 'published', 'available'], true);
  }));
  usort($productCardCatalog, static function (array $left, array $right) use ($selectedProductPositions): int {
    $leftPosition = $selectedProductPositions[(int) ($left['id'] ?? 0)] ?? PHP_INT_MAX;
    $rightPosition = $selectedProductPositions[(int) ($right['id'] ?? 0)] ?? PHP_INT_MAX;
    return $leftPosition <=> $rightPosition;
  });
  $productCardPreviewProducts = girffonMobileResolveProductSectionProducts($pdo, $selectedKey, $settings);
  $bestSellerAutomaticAvailable = $selectedKey === 'shop-best-sellers' && (bool) girffonMobileBestSellerProductIds($pdo);
}
$newArrivalPreviewProducts = $selectedKey === 'home-new-arrivals' ? $productCardPreviewProducts : [];

$queryStatus = (string) ($_GET['status'] ?? '');
if ($queryStatus === 'saved') {
    $statusMessage = 'Draft saved. Published content remains unchanged.';
} elseif ($queryStatus === 'published') {
    $statusMessage = 'Content published to the Mobile configuration API.';
} elseif ($queryStatus === 'rolled-back') {
    $statusMessage = 'Previous configuration restored as a new revision.';
}

$customOrders = [];
if ($tab === 'custom-design') {
    $customOrders = array_values(array_filter(girffonAdminFetchCustomDesignOrderSummaries($pdo, 100), static fn(array $order): bool => empty($order['is_demo'])));
  foreach ($customOrders as &$customOrder) {
    $customOrder['detail'] = girffonAdminFetchCustomDesignOrderDetail($pdo, (int) $customOrder['id']) ?: [];
  }
  unset($customOrder);
}
$customers = $tab === 'account' ? girffonAdminFetchUsers($pdo, ['role' => 'customer']) : [];
$accountStats = [
    'total' => $tab === 'account' ? girffonAdminCountMembers($pdo) : 0,
    'active' => $tab === 'account' ? girffonAdminCountActiveMembers($pdo) : 0,
    'new' => $tab === 'account' ? girffonAdminCountNewMembersThisMonth($pdo) : 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Mobile App</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260905-mobile-structure">
  <style>
    body.admin-page{overflow-x:hidden}.mobile-api-button::before{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232b241b' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='16 18 22 12 16 6'/%3E%3Cpolyline points='8 6 2 12 8 18'/%3E%3C/svg%3E")}.mobile-main-tabs{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:22px}.mobile-main-tabs a{text-decoration:none}.mobile-main-tabs .is-active{background:var(--admin-accent-strong);color:#fff}.mobile-area-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.mobile-area-card{display:block;min-height:120px;padding:18px;border:1px solid var(--admin-border);border-radius:18px;background:var(--admin-surface-alt);color:var(--admin-text);text-decoration:none;transition:transform .2s ease,border-color .2s ease}.mobile-area-card:hover{transform:translateY(-2px);border-color:var(--admin-accent-strong)}.mobile-area-card strong{display:block;margin-bottom:8px;font-size:1.05rem}.mobile-area-card span{color:var(--admin-muted);font-size:.88rem;line-height:1.45}.mobile-breadcrumbs{display:flex;gap:7px;align-items:center;margin-bottom:15px;color:var(--admin-muted)}.mobile-breadcrumbs a{color:var(--admin-accent-strong)}.mobile-editor-layout{display:grid;grid-template-columns:minmax(190px,260px) minmax(0,1fr);gap:20px}.mobile-editor-layout.is-single{grid-template-columns:minmax(0,1fr)}.mobile-entry-list{display:grid;gap:8px;align-content:start}.mobile-entry-list a{display:flex;justify-content:space-between;gap:8px;padding:11px 12px;border:1px solid var(--admin-border);border-radius:9px;background:var(--admin-surface-alt);color:var(--admin-text);text-decoration:none}.mobile-entry-list a.is-active{border-color:var(--admin-accent-strong);background:#fff7e2}.mobile-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.mobile-form label{display:grid;gap:7px;font-weight:600}.mobile-form input,.mobile-form textarea,.mobile-form select{width:100%;padding:11px 12px;border:1px solid rgba(55,43,30,.17);border-radius:8px;background:#fff;color:#1f1a14;font:inherit}.mobile-form textarea{min-height:88px}.mobile-wide{grid-column:1/-1}.mobile-toggle{display:flex!important;align-items:center;gap:8px}.mobile-toggle input{width:auto}.mobile-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:18px}.mobile-badge{display:inline-flex;padding:6px 9px;border-radius:999px;background:#f2eadc;color:#654f35;font-size:.75rem;font-weight:800}.mobile-badge.ready{background:#e3f2e7;color:#24613a}.mobile-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}.mobile-preview{margin-top:20px;padding:17px;border:1px dashed #c7a54b;border-radius:10px;background:#fffaf0}.mobile-preview img,.mobile-preview video{display:block;width:100%;max-height:280px;object-fit:cover;border-radius:8px;margin-bottom:12px}.mobile-history-row{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-top:1px solid var(--admin-border)}.mobile-table-wrap{overflow:auto}.mobile-orders{width:100%;border-collapse:collapse}.mobile-orders th,.mobile-orders td{padding:12px 10px;border-bottom:1px solid var(--admin-border);text-align:left;vertical-align:middle;white-space:nowrap}.mobile-order-preview{width:52px;height:52px;object-fit:cover;border-radius:7px;background:#f2eadc}.mobile-stat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px}.mobile-stat{padding:18px;border:1px solid var(--admin-border);border-radius:16px;background:var(--admin-surface-alt)}.mobile-stat strong{display:block;font-size:1.8rem;margin-top:7px}.mobile-source-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.mobile-source{padding:15px;border:1px solid var(--admin-border);border-radius:12px;background:var(--admin-surface-alt)}.mobile-note{font-size:.82rem;color:var(--admin-muted);font-weight:400}@media(max-width:1000px){.mobile-area-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.mobile-editor-layout{grid-template-columns:1fr}.mobile-entry-list{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:700px){.mobile-area-grid,.mobile-entry-list,.mobile-form,.mobile-stat-grid,.mobile-source-grid{grid-template-columns:1fr}.mobile-wide{grid-column:auto}.mobile-actions .admin-button{width:100%}}
    .mobile-orders a[href^="admin-user-view.php"] { display:inline-flex; width:36px; min-width:36px; height:36px; min-height:36px; padding:0; align-items:center; justify-content:center; border:1px solid var(--admin-border); border-radius:9px; background:#fffaf0; color:transparent; font-size:0; text-decoration:none; transition:transform .2s ease, border-color .2s ease, background .2s ease; }
    .mobile-orders a[href^="admin-user-view.php"]::before { content:""; width:18px; height:18px; background:center/18px no-repeat url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23493b2a' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z'/%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3C/svg%3E"); }
    .mobile-orders a[href^="admin-user-view.php"]:hover, .mobile-orders a[href^="admin-user-view.php"]:focus-visible { border-color:var(--admin-accent-strong); background:#fff3cf; transform:translateY(-1px); }
    .new-arrivals-source{display:flex;gap:10px;flex-wrap:wrap}.new-arrivals-source label{display:flex;align-items:center;gap:8px;padding:11px 14px;border:1px solid var(--admin-border);border-radius:8px;background:#fff}.new-arrivals-source input{width:auto}.new-arrivals-picker{display:grid;gap:9px;max-height:430px;overflow:auto;padding:3px}.new-arrivals-product{display:grid;grid-template-columns:auto 58px minmax(0,1fr) auto;align-items:center;gap:11px;padding:10px;border:1px solid var(--admin-border);border-radius:8px;background:#fff}.new-arrivals-product>input{width:auto}.new-arrivals-product img,.new-arrivals-product-placeholder{width:58px;height:58px;object-fit:cover;border-radius:6px;background:#f2eadc}.new-arrivals-product-placeholder{display:grid;place-items:center;color:var(--admin-muted);font-size:.7rem}.new-arrivals-product strong,.new-arrivals-product small{display:block}.new-arrivals-order{display:flex;gap:5px}.new-arrivals-order button{width:34px;height:34px;padding:0}.new-arrivals-phone{max-width:390px;margin:20px auto 0;padding:18px 14px 22px;border:7px solid #2b241b;border-radius:28px;background:#fff;overflow:hidden}.new-arrivals-phone-head{text-align:left;margin-bottom:14px}.new-arrivals-phone-head h3{margin:5px 0;font-size:1.15rem}.new-arrivals-phone-head p{margin:0;color:var(--admin-muted);font-size:.82rem}.new-arrivals-preview-grid{display:flex;flex-wrap:nowrap;gap:10px;overflow-x:auto;overflow-y:hidden;padding:2px 2px 10px;scroll-snap-type:x proximity;scrollbar-width:thin;touch-action:pan-x;overscroll-behavior-inline:contain;-webkit-overflow-scrolling:touch}.new-arrivals-preview-card{flex:0 0 46%;min-width:0;scroll-snap-align:start}.new-arrivals-preview-card img,.new-arrivals-preview-image{width:100%;aspect-ratio:1/1.18;object-fit:cover;border-radius:7px;background:#f2eadc}.new-arrivals-preview-image{display:grid;place-items:center;color:var(--admin-muted);font-size:.72rem}.new-arrivals-preview-card strong{display:block;margin-top:7px;font-size:.78rem;line-height:1.3}.new-arrivals-preview-card span{font-size:.76rem;font-weight:800;color:var(--admin-accent-strong)}@media(max-width:700px){.new-arrivals-product{grid-template-columns:auto 48px minmax(0,1fr)}.new-arrivals-product img,.new-arrivals-product-placeholder{width:48px;height:48px}.new-arrivals-order{grid-column:2/-1}.new-arrivals-phone{max-width:100%}.new-arrivals-preview-card{flex-basis:72%}}
    .professional-mobile-preview+.mobile-preview{display:none}.shop-product-item{position:relative;padding-top:78px}.shop-item-image-preview{position:absolute;top:14px;left:14px;width:52px;height:52px;border-radius:7px;object-fit:cover;background:#f2eadc}.shop-item-image-empty{display:grid;place-items:center;color:var(--admin-muted);font-size:.65rem}.shop-by-product-grid{display:flex;flex-wrap:nowrap;gap:10px;overflow-x:auto;overflow-y:hidden;padding:2px 2px 10px;scroll-snap-type:x proximity;scrollbar-width:thin;touch-action:pan-x;overscroll-behavior-inline:contain;-webkit-overflow-scrolling:touch}.shop-by-product-card{display:flex;flex:0 0 72%;align-items:center;gap:9px;padding:9px;border:1px solid var(--admin-border);border-radius:8px;background:#fff;color:var(--admin-text);text-decoration:none;scroll-snap-align:start}.shop-by-product-card img,.shop-by-product-card-image{width:52px;height:52px;flex:0 0 52px;border-radius:7px;object-fit:cover;background:#f2eadc}.shop-by-product-card-image{display:grid;place-items:center;color:var(--admin-muted);font-size:.64rem}.shop-by-product-card strong{min-width:0;font-size:.78rem;line-height:1.3;overflow-wrap:anywhere}.new-arrivals-preview-card a{display:block;color:inherit;text-decoration:none}.new-arrivals-preview-card a:focus-visible,.shop-by-product-card:focus-visible{outline:2px solid var(--admin-accent-strong);outline-offset:2px}
  </style>
</head>
<body class="admin-page" data-admin-page="mobile-app">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header"><span class="admin-brand"><img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo"></span><p>Mobile content and shared services.</p></div>
      <?php $adminNavCurrentPage = 'mobile_app'; $adminNavBasePath = ''; require __DIR__ . '/includes/admin-nav.php'; ?>
      <div class="admin-sidebar-footer"><section class="admin-sidebar-card"><strong>Published API</strong><p class="admin-panel-note">backend/mobile/config.php</p></section><button class="admin-logout-button" type="button" data-admin-logout>Logout</button></div>
    </aside>
    <main class="admin-main">
      <header class="admin-topbar"><div><p class="admin-page-subtitle">Admin</p><h1 class="admin-page-title">Mobile App</h1></div><div class="admin-topbar-actions">          <a class="admin-button admin-button-soft admin-view-shop-button" href="admin-dashboard.php" aria-label="View Shop" title="View Shop">View Shop</a>
<a class="admin-button admin-button-soft mobile-api-button" href="backend/mobile/config.php" target="_blank" rel="noopener" aria-label="View API" title="View API">View API</a><button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload()">Refresh</button>          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
</div></header>

      <nav class="mobile-main-tabs" aria-label="Mobile App main navigation">
        <?php foreach ($tabs as $key => $label): ?><a class="admin-chip<?php echo $tab === $key ? ' is-active' : ''; ?>" href="admin-mobile-app.php?tab=<?php echo rawurlencode($key); ?>"><?php echo girffonMobileAdminEscape($label); ?></a><?php endforeach; ?>
      </nav>
      <?php if ($statusMessage !== '' || $errorMessage !== ''): ?><div class="admin-feedback<?php echo $errorMessage !== '' ? ' is-error' : ' is-success'; ?>" role="status"><?php echo girffonMobileAdminEscape($errorMessage ?: $statusMessage); ?></div><?php endif; ?>

      <?php if (($tab === 'home' || $tab === 'shop') && $area === ''): ?>
        <article class="admin-panel">
          <div class="admin-panel-head"><div><h2><?php echo girffonMobileAdminEscape($tabs[$tab]); ?> sections</h2><p class="admin-panel-note">Choose one section to open its editor.</p></div></div>
          <div class="mobile-area-grid">
            <?php foreach ($areaLabels[$tab] as $areaKey => $label): ?><a class="mobile-area-card" href="admin-mobile-app.php?tab=<?php echo rawurlencode($tab); ?>&amp;area=<?php echo rawurlencode($areaKey); ?>"><strong><?php echo girffonMobileAdminEscape($label); ?></strong><span><?php echo girffonMobileAdminEscape($areaNotes[$areaKey] ?? 'Mobile presentation controls'); ?></span></a><?php endforeach; ?>
          </div>
        </article>

      <?php elseif ($tab === 'home' || $tab === 'shop'): ?>
        <div class="mobile-breadcrumbs"><a href="admin-mobile-app.php?tab=<?php echo rawurlencode($tab); ?>"><?php echo girffonMobileAdminEscape($tabs[$tab]); ?></a><span>/</span><strong><?php echo girffonMobileAdminEscape($areaLabels[$tab][$area]); ?></strong></div>
        <?php if ($tab === 'shop' && $area === 'category-buttons'): ?><div class="mobile-meta" aria-label="Canonical SHOP navigation order"><?php foreach ($shopNavigationCategories as $navigationCategory): ?><span class="mobile-badge<?php echo !empty($navigationCategory['available']) ? ' ready' : ''; ?>"><?php echo girffonMobileAdminEscape($navigationCategory['label']); ?><?php echo empty($navigationCategory['available']) ? ' · Coming soon' : ''; ?></span><?php endforeach; ?></div><?php endif; ?>
        <div class="mobile-editor-layout<?php echo count($areaEntries) === 1 ? ' is-single' : ''; ?>">
          <?php if (count($areaEntries) > 1): ?><aside class="admin-panel mobile-entry-list" aria-label="Section entries"><?php foreach ($areaEntries as $key => $definition): $row = $rowsByKey[$key] ?? []; $entryName = trim((string) ($row['section_name'] ?? '')) ?: $definition['label']; ?><a class="<?php echo $key === $selectedKey ? 'is-active' : ''; ?>" href="admin-mobile-app.php?tab=<?php echo rawurlencode($tab); ?>&amp;area=<?php echo rawurlencode($area); ?>&amp;section=<?php echo rawurlencode($key); ?>"><span><?php echo girffonMobileAdminEscape($entryName); ?></span><small><?php echo !empty($row['has_published']) ? 'r' . (int) $row['revision'] : 'Draft'; ?></small></a><?php endforeach; ?></aside><?php endif; ?>
          <article class="admin-panel">
            <?php $selectedEntryName = trim((string) ($selected['section_name'] ?? '')) ?: $definitions[$selectedKey]['label']; ?>
            <div class="admin-panel-head"><div><h2><?php echo girffonMobileAdminEscape($areaLabels[$tab][$area]); ?> · <?php echo girffonMobileAdminEscape($selectedEntryName); ?></h2><p class="admin-panel-note">Draft changes stay private until Publish.</p></div><span class="mobile-badge<?php echo !empty($selected['has_published']) ? ' ready' : ''; ?>"><?php echo !empty($selected['has_published']) ? 'Published' : 'Draft only'; ?></span></div>
            <div class="mobile-meta"><span class="mobile-badge">Revision <?php echo (int) ($selected['revision'] ?? 0); ?></span><span class="mobile-badge">Modified <?php echo girffonMobileAdminEscape($selected['draft_updated_at'] ?? $selected['updated_at'] ?? 'Never'); ?></span><span class="mobile-badge">Published <?php echo girffonMobileAdminEscape($selected['published_at'] ?? 'Never'); ?></span></div>
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="_csrf" value="<?php echo girffonMobileAdminEscape($csrfToken); ?>"><input type="hidden" name="section_key" value="<?php echo girffonMobileAdminEscape($selectedKey); ?>">
              <?php if ($area !== 'category-buttons'): ?><input type="hidden" name="section_name" value="<?php echo girffonMobileAdminEscape($selectedEntryName); ?>"><?php endif; ?>
              <div class="mobile-form">
                <?php if (in_array($selectedKey, $productCardSectionKeys, true)): ?>
                <input type="hidden" name="display_order" value="<?php echo (int) ($selected['display_order'] ?? 170); ?>">
                <label>Section title<input type="text" name="title" maxlength="180" required value="<?php echo girffonMobileAdminEscape(($selected['title'] ?? '') ?: $definitions[$selectedKey]['label']); ?>"></label>
                <label>Products to show<input type="number" name="settings[product_limit]" min="1" max="60" step="1" required value="<?php echo (int) ($settings['product_limit'] ?? 4); ?>"></label>
                <label class="mobile-wide">Optional subtitle<input type="text" name="subtitle" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['subtitle'] ?? ''); ?>"></label>
                <fieldset class="mobile-wide"><legend>Product source</legend><div class="new-arrivals-source"><label><input type="radio" name="settings[product_source]" value="automatic" <?php echo ($settings['product_source'] ?? 'automatic') !== 'manual' ? 'checked' : ''; ?>>Automatic: <?php echo $selectedKey === 'shop-best-sellers' ? 'best sellers' : 'newest products'; ?></label><label><input type="radio" name="settings[product_source]" value="manual" <?php echo ($settings['product_source'] ?? 'automatic') === 'manual' ? 'checked' : ''; ?>>Manual selection</label></div><?php if ($selectedKey === 'shop-best-sellers' && !$bestSellerAutomaticAvailable): ?><p class="admin-feedback is-error">Automatic mode is not publishable yet because existing order items do not match catalog product IDs or SKUs. Manual mode is fully available.</p><?php endif; ?></fieldset>
                <section class="mobile-wide" id="new-arrivals-manual"><div class="admin-panel-head"><div><h3>Select and order products</h3><p class="admin-panel-note">Checked products are published in the order shown. Use the arrows to reorder them.</p></div></div><div class="new-arrivals-picker" id="new-arrivals-picker"><?php if (!$productCardCatalog): ?><p class="admin-panel-note">No active products are available.</p><?php endif; ?><?php foreach ($productCardCatalog as $product): $productId = (int) ($product['id'] ?? 0); $productImage = trim((string) ($product['image'] ?? '')); ?><div class="new-arrivals-product"><input type="checkbox" name="settings[manual_product_ids][]" value="<?php echo $productId; ?>" <?php echo in_array($productId, $selectedProductIds, true) ? 'checked' : ''; ?> aria-label="Select <?php echo girffonMobileAdminEscape($product['name'] ?? 'product'); ?>"><?php if ($productImage !== ''): ?><img src="<?php echo girffonMobileAdminEscape($productImage); ?>" alt="" loading="lazy" onerror="this.hidden=true"><?php else: ?><span class="new-arrivals-product-placeholder">No image</span><?php endif; ?><span><strong><?php echo girffonMobileAdminEscape($product['name'] ?? 'Unnamed product'); ?></strong><small>€<?php echo number_format((float) ($product['effective_price'] ?? $product['price'] ?? 0), 2); ?></small><?php if (trim((string) ($product['detail_url'] ?? '')) !== ''): ?><a href="<?php echo girffonMobileAdminEscape($product['detail_url']); ?>" target="_blank" rel="noopener">Open product</a><?php endif; ?></span><span class="new-arrivals-order"><button class="admin-button admin-button-soft new-arrivals-up" type="button" aria-label="Move up" title="Move up">↑</button><button class="admin-button admin-button-soft new-arrivals-down" type="button" aria-label="Move down" title="Move down">↓</button></span></div><?php endforeach; ?></div></section>
                <?php elseif ($area === 'new-arrivals' || $area === 'best-sellers'): ?>
                <input type="hidden" name="display_order" value="<?php echo (int) ($selected['display_order'] ?? 170); ?>">
                <label class="mobile-wide"><?php echo $area === 'best-sellers' ? 'Best Sellers' : 'New Arrivals'; ?> product limit<input type="number" name="settings[product_limit]" min="1" <?php echo $tab === 'shop' ? 'max="60" ' : ''; ?>step="1" required value="<?php echo girffonMobileAdminEscape($settings['product_limit'] ?? ''); ?>" placeholder="Example: 60"></label>
                <?php elseif ($area === 'shop-by-product'): ?>
                <input type="hidden" name="display_order" value="<?php echo (int) ($selected['display_order'] ?? 0); ?>">
                <label>Section title<input type="text" name="title" maxlength="180" required value="<?php echo girffonMobileAdminEscape(($selected['title'] ?? '') ?: 'Shop By Product'); ?>"></label>
                <label class="mobile-wide">Optional subtitle<input type="text" name="subtitle" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['subtitle'] ?? ''); ?>"></label>
                <section class="mobile-wide" id="shop-product-items"><div class="admin-panel-head"><div><h3>Shop By Product items</h3><p class="admin-panel-note">Create image cards linking to any GIRFFON product, category, collection, or page.</p></div><button class="admin-button admin-button-soft" id="add-shop-product-item" type="button">Add Item</button></div><div id="shop-product-item-list"><?php foreach (($settings['items'] ?? []) as $itemIndex => $item): ?><div class="mobile-source shop-product-item"><label>Name<input type="text" name="settings[items][<?php echo (int) $itemIndex; ?>][name]" maxlength="120" required value="<?php echo girffonMobileAdminEscape($item['name'] ?? ''); ?>"></label><label>Image path<input type="text" name="settings[items][<?php echo (int) $itemIndex; ?>][icon]" maxlength="500" value="<?php echo girffonMobileAdminEscape($item['image'] ?? $item['icon'] ?? ''); ?>"></label><label>Select image<select name="shop_item_icon_select[<?php echo (int) $itemIndex; ?>]"><option value="">Keep path</option><?php foreach ($shopIconOptions as $iconOption): ?><option value="<?php echo girffonMobileAdminEscape($iconOption); ?>" <?php echo ($item['image'] ?? $item['icon'] ?? '') === $iconOption ? 'selected' : ''; ?>><?php echo girffonMobileAdminEscape(basename($iconOption)); ?></option><?php endforeach; ?></select></label><label>Upload image<input type="file" name="shop_item_icon_file[<?php echo (int) $itemIndex; ?>]" accept="image/jpeg,image/png,image/webp,image/gif"></label><label>Link / Destination<input type="text" name="settings[items][<?php echo (int) $itemIndex; ?>][destination]" maxlength="500" required value="<?php echo girffonMobileAdminEscape($item['destination'] ?? ''); ?>"></label><label>Display order<input type="number" name="settings[items][<?php echo (int) $itemIndex; ?>][display_order]" min="0" max="65535" value="<?php echo (int) ($item['display_order'] ?? 0); ?>"></label><label class="mobile-toggle"><input type="checkbox" name="settings[items][<?php echo (int) $itemIndex; ?>][is_enabled]" value="1" <?php echo !empty($item['is_enabled']) ? 'checked' : ''; ?>>ON / OFF</label><div class="mobile-actions"><button class="admin-button admin-button-soft shop-item-edit" type="button">Edit</button><button class="admin-button admin-button-soft shop-item-delete" type="button">Delete</button></div></div><?php endforeach; ?></div><template id="shop-product-item-template"><div class="mobile-source shop-product-item"><label>Name<input type="text" name="settings[items][__INDEX__][name]" maxlength="120" required></label><label>Image path<input type="text" name="settings[items][__INDEX__][icon]" maxlength="500"></label><label>Select image<select name="shop_item_icon_select[__INDEX__]"><option value="">Choose image</option><?php foreach ($shopIconOptions as $iconOption): ?><option value="<?php echo girffonMobileAdminEscape($iconOption); ?>"><?php echo girffonMobileAdminEscape(basename($iconOption)); ?></option><?php endforeach; ?></select></label><label>Upload image<input type="file" name="shop_item_icon_file[__INDEX__]" accept="image/jpeg,image/png,image/webp,image/gif"></label><label>Link / Destination<input type="text" name="settings[items][__INDEX__][destination]" maxlength="500" required></label><label>Display order<input type="number" name="settings[items][__INDEX__][display_order]" min="0" max="65535" value="10"></label><label class="mobile-toggle"><input type="checkbox" name="settings[items][__INDEX__][is_enabled]" value="1" checked>ON / OFF</label><div class="mobile-actions"><button class="admin-button admin-button-soft shop-item-edit" type="button">Edit</button><button class="admin-button admin-button-soft shop-item-delete" type="button">Delete</button></div></div></template></section>
                <?php else: ?>
                <?php if ($area === 'category-buttons'): ?><label>Button name<input type="text" name="section_name" required maxlength="120" value="<?php echo girffonMobileAdminEscape($selectedEntryName); ?>" placeholder="Example: Animation or Animals"></label><?php if ($tab === 'home'): ?><label>Fixed HOME slot<input type="text" value="<?php echo (int) ($definitions[$selectedKey]['slot'] ?? 0); ?>" readonly></label><?php endif; ?><?php endif; ?>
                <label>Title<input type="text" name="title" maxlength="180" value="<?php echo girffonMobileAdminEscape($selected['title'] ?? ''); ?>"></label>
                <label>Display order<input type="number" name="display_order" min="0" max="65535" value="<?php echo (int) ($selected['display_order'] ?? 0); ?>"></label>
                <?php if (!in_array($area, ['category-buttons', 'make-it-yours'], true)): ?><label class="mobile-wide">Subtitle / promotional text<input type="text" name="subtitle" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['subtitle'] ?? ''); ?>"></label><?php endif; ?>
                <?php if (in_array($area, ['shopping-cart', 'catalog', 'gift-cards', 'bundles', 'bundle', 'shop-by-product'], true)): ?><label class="mobile-wide">Description<textarea name="description"><?php echo girffonMobileAdminEscape($selected['description'] ?? ''); ?></textarea></label><?php endif; ?>
                <label>Button text<input type="text" name="button_text" maxlength="80" value="<?php echo girffonMobileAdminEscape($selected['button_text'] ?? ''); ?>"></label>
                <label>Destination / category<input type="text" name="button_destination" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['button_destination'] ?? ''); ?>"></label>
                <label class="mobile-wide">Media path<input type="text" name="image_url" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['image_url'] ?? ''); ?>"><input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4"><span class="mobile-note">JPG, JPEG, PNG, WebP, GIF or MP4. Maximum 40 MB.<?php if ($tab === 'shop' && $area === 'category-buttons'): ?> Recommended: 1520 x 1034 px.<?php elseif ($tab === 'shop' && in_array($area, ['make-it-yours', 'gift-cards', 'bundle'], true)): ?> Recommended: 1536 x 1024 px.<?php endif; ?></span></label>
                <?php if (in_array($area, ['banner', 'category-buttons'], true)): ?><label>Mobile media path<input type="text" name="mobile_image_url" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['mobile_image_url'] ?? ''); ?>"><input type="file" name="mobile_image_file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4"></label><?php endif; ?><?php if ($area === 'banner'): ?><label>Tablet media path<input type="text" name="tablet_image_url" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['tablet_image_url'] ?? ''); ?>"><input type="file" name="tablet_image_file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4"></label><label>Slide duration<select name="settings[duration_seconds]"><?php for ($second = 1; $second <= 8; $second++): ?><option value="<?php echo $second; ?>" <?php echo (int) ($settings['duration_seconds'] ?? 5) === $second ? 'selected' : ''; ?>><?php echo $second; ?> second<?php echo $second > 1 ? 's' : ''; ?></option><?php endfor; ?></select></label><div><label class="mobile-toggle"><input type="checkbox" name="settings[muted_autoplay]" value="1" <?php echo !empty($settings['muted_autoplay']) ? 'checked' : ''; ?>>Muted autoplay</label><label class="mobile-toggle"><input type="checkbox" name="settings[loop]" value="1" <?php echo !empty($settings['loop']) ? 'checked' : ''; ?>>Loop video</label><label class="mobile-toggle"><input type="checkbox" name="settings[play_to_end]" value="1" <?php echo !empty($settings['play_to_end']) ? 'checked' : ''; ?>>Play until video ends</label></div><?php endif; ?>
                <?php if ($area === 'shopping-cart'): ?><label class="mobile-wide">Free shipping message<input type="text" name="free_shipping_message" value="<?php echo girffonMobileAdminEscape($selected['free_shipping_message'] ?? ''); ?>"></label><label class="mobile-wide">Discount message<input type="text" name="discount_message" value="<?php echo girffonMobileAdminEscape($selected['discount_message'] ?? ''); ?>"></label><label class="mobile-wide">Bundle message<input type="text" name="bundle_message" value="<?php echo girffonMobileAdminEscape($selected['bundle_message'] ?? ''); ?>"></label><?php endif; ?>
                <?php if (!in_array($area, ['category-buttons', 'custom-design', 'make-it-yours'], true)): ?><label>Start date/time<input type="datetime-local" name="start_at" value="<?php echo girffonMobileAdminEscape(girffonMobileAdminDateInput($selected['start_at'] ?? '')); ?>"></label><label>End date/time<input type="datetime-local" name="end_at" value="<?php echo girffonMobileAdminEscape(girffonMobileAdminDateInput($selected['end_at'] ?? '')); ?>"></label><?php endif; ?>
                <?php endif; ?>
                <label class="mobile-toggle mobile-wide"><input type="checkbox" name="is_enabled" value="1" <?php echo !empty($selected['is_enabled']) ? 'checked' : ''; ?>>ON / OFF</label>
                <?php if ($tab === 'shop' && $area === 'category-buttons'): ?><label class="mobile-toggle mobile-wide"><input type="checkbox" name="settings[loop]" value="1" <?php echo !empty($settings['loop']) ? 'checked' : ''; ?>>Loop MP4 / Video</label><?php endif; ?>
              </div>
              <div class="mobile-actions"><button class="admin-button admin-button-accent" type="submit" name="mobile_action" value="save">Save Draft</button><button class="admin-button admin-button-soft" type="submit" name="mobile_action" value="preview">Preview</button><button class="admin-button" type="submit" name="mobile_action" value="publish">Publish</button></div>
            </form>
            <?php if (in_array($selectedKey, ['shop-new-arrivals', 'shop-best-sellers'], true)): ?><section class="mobile-preview professional-mobile-preview" aria-label="<?php echo girffonMobileAdminEscape($definitions[$selectedKey]['label']); ?> mobile preview"><span class="mobile-badge"><?php echo $previewInput ? 'Unsaved preview' : 'Draft preview'; ?></span><div class="new-arrivals-phone"><header class="new-arrivals-phone-head"><h3><?php echo girffonMobileAdminEscape(($selected['title'] ?? '') ?: $definitions[$selectedKey]['label']); ?></h3><?php if (trim((string) ($selected['subtitle'] ?? '')) !== ''): ?><p><?php echo girffonMobileAdminEscape($selected['subtitle']); ?></p><?php endif; ?></header><div class="new-arrivals-preview-grid"><?php foreach ($productCardPreviewProducts as $product): ?><article class="new-arrivals-preview-card"><?php $productLink = trim((string) ($product['product_link'] ?? '')); if ($productLink !== ''): ?><a href="<?php echo girffonMobileAdminEscape($productLink); ?>" target="_blank" rel="noopener"><?php endif; ?><?php if (($product['image'] ?? '') !== ''): ?><img src="<?php echo girffonMobileAdminEscape($product['image']); ?>" alt="" loading="lazy" onerror="this.hidden=true"><?php else: ?><span class="new-arrivals-preview-image">No image</span><?php endif; ?><strong><?php echo girffonMobileAdminEscape($product['name'] ?? 'Unnamed product'); ?></strong><span>€<?php echo number_format((float) ($product['price'] ?? 0), 2); ?></span><?php if ($productLink !== ''): ?></a><?php endif; ?></article><?php endforeach; ?><?php if (!$productCardPreviewProducts): ?><p class="admin-panel-note"><?php echo $selectedKey === 'shop-best-sellers' && ($settings['product_source'] ?? 'automatic') !== 'manual' ? 'Automatic Best Sellers is unavailable until order items match catalog products.' : 'No products match this draft selection.'; ?></p><?php endif; ?></div></div></section><?php endif; ?>
            <?php if ($selectedKey === 'shop-by-product'): ?><section class="mobile-preview professional-mobile-preview" aria-label="Shop By Product mobile preview"><span class="mobile-badge"><?php echo $previewInput ? 'Unsaved preview' : 'Draft preview'; ?></span><div class="new-arrivals-phone"><header class="new-arrivals-phone-head"><h3><?php echo girffonMobileAdminEscape(($selected['title'] ?? '') ?: 'Shop By Product'); ?></h3><?php if (trim((string) ($selected['subtitle'] ?? '')) !== ''): ?><p><?php echo girffonMobileAdminEscape($selected['subtitle']); ?></p><?php endif; ?></header><div class="shop-by-product-grid"><?php $enabledShopItems = 0; foreach ((array) ($settings['items'] ?? []) as $item): if (empty($item['is_enabled'])) continue; $enabledShopItems++; $itemImage = trim((string) ($item['image'] ?? $item['icon'] ?? '')); ?><a class="shop-by-product-card" href="<?php echo girffonMobileAdminEscape($item['destination'] ?? ''); ?>" target="_blank" rel="noopener"><?php if ($itemImage !== ''): ?><img src="<?php echo girffonMobileAdminEscape($itemImage); ?>" alt="" loading="lazy" onerror="this.hidden=true"><?php else: ?><span class="shop-by-product-card-image">No image</span><?php endif; ?><strong><?php echo girffonMobileAdminEscape($item['name'] ?? 'Unnamed item'); ?></strong></a><?php endforeach; ?><?php if ($enabledShopItems === 0): ?><p class="admin-panel-note">No enabled destination cards in this draft.</p><?php endif; ?></div></div></section><?php endif; ?>
            <?php if ($selectedKey === 'home-new-arrivals'): ?><section class="mobile-preview" aria-label="New Arrivals mobile preview"><span class="mobile-badge"><?php echo $previewInput ? 'Unsaved preview' : 'Draft preview'; ?></span><div class="new-arrivals-phone"><header class="new-arrivals-phone-head"><h3><?php echo girffonMobileAdminEscape(($selected['title'] ?? '') ?: 'New Arrivals'); ?></h3><?php if (trim((string) ($selected['subtitle'] ?? '')) !== ''): ?><p><?php echo girffonMobileAdminEscape($selected['subtitle']); ?></p><?php endif; ?></header><div class="new-arrivals-preview-grid"><?php foreach ($newArrivalPreviewProducts as $product): ?><article class="new-arrivals-preview-card"><?php $productLink = trim((string) ($product['product_link'] ?? '')); if ($productLink !== ''): ?><a href="<?php echo girffonMobileAdminEscape($productLink); ?>" target="_blank" rel="noopener"><?php endif; ?><?php if (($product['image'] ?? '') !== ''): ?><img src="<?php echo girffonMobileAdminEscape($product['image']); ?>" alt="" loading="lazy" onerror="this.hidden=true"><?php else: ?><span class="new-arrivals-preview-image">No image</span><?php endif; ?><strong><?php echo girffonMobileAdminEscape($product['name'] ?? 'Unnamed product'); ?></strong><span>€<?php echo number_format((float) ($product['sale_price'] ?? $product['price'] ?? 0), 2); ?></span><?php if ($productLink !== ''): ?></a><?php endif; ?></article><?php endforeach; ?><?php if (!$newArrivalPreviewProducts): ?><p class="admin-panel-note">No products match this draft selection.</p><?php endif; ?></div></div></section><?php else: ?><section class="mobile-preview" aria-label="Draft preview"><?php $media = (string) ($selected['image_url'] ?? ''); if ($media !== ''): ?><?php if (strtolower(pathinfo(parse_url($media, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION)) === 'mp4'): ?><video src="<?php echo girffonMobileAdminEscape($media); ?>" <?php echo !empty($settings['muted_autoplay']) ? 'muted autoplay' : ''; ?> <?php echo !empty($settings['loop']) ? 'loop' : ''; ?> controls></video><?php else: ?><img src="<?php echo girffonMobileAdminEscape($media); ?>" alt="" onerror="this.hidden=true"><?php endif; ?><?php endif; ?><span class="mobile-badge"><?php echo $previewInput ? 'Unsaved preview' : 'Draft preview'; ?></span><h3><?php echo girffonMobileAdminEscape(($selected['title'] ?? '') ?: $definitions[$selectedKey]['label']); ?></h3><p><?php echo in_array($area, ['new-arrivals', 'best-sellers'], true) ? (int) ($settings['product_limit'] ?? 0) . ' products' : ($area === 'shop-by-product' ? count($settings['items'] ?? []) . ' items' : girffonMobileAdminEscape($selected['subtitle'] ?? '')); ?></p></section><?php endif; ?>
            <?php if ($history): ?><section><h3>Previous published configurations</h3><?php foreach ($history as $entry): ?><div class="mobile-history-row"><span>Revision <?php echo (int) $entry['revision']; ?> · <?php echo girffonMobileAdminEscape($entry['published_at']); ?></span><form method="post"><input type="hidden" name="_csrf" value="<?php echo girffonMobileAdminEscape($csrfToken); ?>"><input type="hidden" name="section_key" value="<?php echo girffonMobileAdminEscape($selectedKey); ?>"><input type="hidden" name="revision" value="<?php echo (int) $entry['revision']; ?>"><button class="admin-button admin-button-soft" name="mobile_action" value="rollback">Restore</button></form></div><?php endforeach; ?></section><?php endif; ?>
          </article>
        </div>

      <?php elseif ($tab === 'custom-design'): ?>
        <article class="admin-panel">
          <div class="admin-panel-head"><div><h2>Custom Design Order Receiver</h2><p class="admin-panel-note">Submitted customer orders from the existing Custom Design system. The design library and editor are not managed here.</p></div><a class="admin-button admin-button-soft" href="admin-custom-orders.php">Open full order management</a></div>
          <div class="mobile-stat-grid"><section class="mobile-stat"><span>Total received</span><strong><?php echo count($customOrders); ?></strong></section><section class="mobile-stat"><span>Paid</span><strong><?php echo count(array_filter($customOrders, static fn(array $order): bool => ($order['payment_status'] ?? '') === 'paid')); ?></strong></section><section class="mobile-stat"><span>In production</span><strong><?php echo count(array_filter($customOrders, static fn(array $order): bool => ($order['status'] ?? '') === 'in_production')); ?></strong></section></div>
          <?php if (!$customOrders): ?><p class="admin-panel-note">No submitted Custom Design orders are currently stored.</p><?php else: ?><div class="mobile-table-wrap"><table class="mobile-orders"><thead><tr><th>Preview</th><th>Order ID</th><th>Customer</th><th>Product</th><th>Size / color / qty</th><th>Personalization</th><th>Price</th><th>Status</th><th>Payment</th><th>Date</th><th></th></tr></thead><tbody><?php foreach ($customOrders as $order): $detail = $order['detail'] ?? []; $checkout = $detail['checkout_summary'] ?? []; ?><tr><td><?php if (!empty($order['preview_front'])): ?><img class="mobile-order-preview" src="<?php echo girffonMobileAdminEscape($order['preview_front']); ?>" alt="Front design"><?php endif; ?></td><td><?php echo girffonMobileAdminEscape($order['order_code']); ?></td><td><strong><?php echo girffonMobileAdminEscape($order['customer_name']); ?></strong><br><small><?php echo girffonMobileAdminEscape($order['customer_email']); ?></small></td><td><?php echo girffonMobileAdminEscape($order['product_name']); ?></td><td><?php echo girffonMobileAdminEscape($checkout['size'] ?? ''); ?> / <?php echo girffonMobileAdminEscape($checkout['color'] ?? ''); ?> / <?php echo (int) ($checkout['quantity'] ?? 1); ?></td><td><?php echo (int) $order['text_count']; ?> text · <?php echo (int) $order['upload_count']; ?> uploads<?php if (!empty($detail['customer_note'])): ?><br><small title="<?php echo girffonMobileAdminEscape($detail['customer_note']); ?>"><?php echo girffonMobileAdminEscape(mb_strimwidth($detail['customer_note'], 0, 45, '...')); ?></small><?php endif; ?></td><td>€<?php echo number_format((float) ($checkout['order_total'] ?? $order['order_total']), 2); ?></td><td><?php echo girffonMobileAdminEscape(ucwords(str_replace('_', ' ', $order['status']))); ?></td><td><?php echo girffonMobileAdminEscape(ucfirst($order['payment_status'])); ?></td><td><?php echo girffonMobileAdminEscape($order['created_at']); ?></td><td><a class="admin-button admin-button-soft" href="admin-custom-order-view.php?id=<?php echo (int) $order['id']; ?>">View all views & details</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </article>

      <?php else: ?>
        <article class="admin-panel">
          <div class="admin-panel-head"><div><h2>Shared Customer Accounts</h2><p class="admin-panel-note">Website and future Mobile authentication use the same secure users table, password hashes, and customer IDs.</p></div><span class="mobile-badge ready">One account system</span></div>
          <div class="mobile-stat-grid"><section class="mobile-stat"><span>Total registered customers</span><strong><?php echo $accountStats['total']; ?></strong></section><section class="mobile-stat"><span>Active accounts</span><strong><?php echo $accountStats['active']; ?></strong></section><section class="mobile-stat"><span>New registrations this month</span><strong><?php echo $accountStats['new']; ?></strong></section></div>
          <div class="mobile-source-grid">
            <?php $accountSources = ['users' => 'Login, profile and account status', 'user_preferences' => 'Membership preferences', 'user_addresses' => 'Shared addresses', 'wishlist_items' => 'Shared wishlist', 'orders' => 'Orders and order history', 'gift_cards' => 'Gift Cards']; foreach ($accountSources as $tableName => $label): $source = $sharedSystems[$tableName] ?? ['exists' => false, 'count' => null]; ?><section class="mobile-source"><strong><?php echo girffonMobileAdminEscape($label); ?></strong><p><span class="mobile-badge<?php echo $source['exists'] ? ' ready' : ''; ?>"><?php echo $source['exists'] ? 'Connected' : 'Unavailable'; ?></span></p><small><?php echo $source['exists'] ? number_format((int) $source['count']) . ' shared records' : 'No duplicate storage created'; ?></small></section><?php endforeach; ?>
          </div>
          <p class="admin-panel-note" style="margin-top:18px">Registration source totals are not shown because the existing users table does not currently record Website versus Mobile origin. No plain-text passwords are read or displayed.</p>
          <?php if ($customers): ?><div class="mobile-table-wrap" style="margin-top:18px"><table class="mobile-orders"><thead><tr><th>Customer ID</th><th>Customer</th><th>Email</th><th>Country</th><th>Status</th><th>Registered</th><th></th></tr></thead><tbody><?php foreach (array_slice($customers, 0, 12) as $customer): ?><tr><td><?php echo (int) $customer['id']; ?></td><td><?php echo girffonMobileAdminEscape(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: $customer['username']); ?></td><td><?php echo girffonMobileAdminEscape($customer['email']); ?></td><td><?php echo girffonMobileAdminEscape($customer['country']); ?></td><td><?php echo girffonMobileAdminEscape($customer['status']); ?></td><td><?php echo girffonMobileAdminEscape($customer['created_at']); ?></td><td><a class="admin-button admin-button-soft" href="admin-user-view.php?id=<?php echo (int) $customer['id']; ?>">View account</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </article>
      <?php endif; ?>
    </main>
  </div>
  <script src="JS/admin-girffon.js?v=20260518r11"></script>
  <script>
    const shopItemList = document.getElementById('shop-product-item-list');
    const shopItemTemplate = document.getElementById('shop-product-item-template');
    const addShopItemButton = document.getElementById('add-shop-product-item');
    if (shopItemList && shopItemTemplate && addShopItemButton) {
      let shopItemIndex = shopItemList.children.length;
      const syncShopItemOrder = () => {
        shopItemList.querySelectorAll('.shop-product-item').forEach((item, index) => {
          const orderInput = item.querySelector('input[name$="[display_order]"]');
          if (orderInput) orderInput.value = String((index + 1) * 10);
        });
      };
      const updateShopItemPreview = (item) => {
        let preview = item.querySelector('.shop-item-image-preview');
        let empty = item.querySelector('.shop-item-image-empty');
        if (!preview) {
          preview = document.createElement('img');
          preview.className = 'shop-item-image-preview';
          preview.alt = '';
          item.prepend(preview);
        }
        if (!empty) {
          empty = document.createElement('span');
          empty.className = 'shop-item-image-preview shop-item-image-empty';
          empty.textContent = 'No image';
          item.prepend(empty);
        }
        const pathInput = item.querySelector('input[name$="[icon]"]');
        const imageSelect = item.querySelector('select[name^="shop_item_icon_select"]');
        const fileInput = item.querySelector('input[type="file"]');
        const file = fileInput?.files?.[0];
        const source = file ? URL.createObjectURL(file) : (imageSelect?.value || pathInput?.value || '');
        preview.hidden = source === '';
        empty.hidden = source !== '';
        if (source !== '') preview.src = source;
      };
      const enhanceShopItem = (item) => {
        const actions = item.querySelector('.mobile-actions');
        if (actions && !actions.querySelector('.shop-item-up')) {
          actions.insertAdjacentHTML('afterbegin', '<button class="admin-button admin-button-soft shop-item-up" type="button" aria-label="Move up" title="Move up">↑</button><button class="admin-button admin-button-soft shop-item-down" type="button" aria-label="Move down" title="Move down">↓</button>');
        }
        item.querySelector('input[name$="[icon]"]')?.addEventListener('input', () => updateShopItemPreview(item));
        item.querySelector('select[name^="shop_item_icon_select"]')?.addEventListener('change', () => updateShopItemPreview(item));
        item.querySelector('input[type="file"]')?.addEventListener('change', () => updateShopItemPreview(item));
        updateShopItemPreview(item);
      };
      shopItemList.querySelectorAll('.shop-product-item').forEach(enhanceShopItem);
      syncShopItemOrder();
      addShopItemButton.addEventListener('click', () => {
        shopItemList.insertAdjacentHTML('beforeend', shopItemTemplate.innerHTML.replaceAll('__INDEX__', String(shopItemIndex++)));
        enhanceShopItem(shopItemList.lastElementChild);
        syncShopItemOrder();
      });
      shopItemList.addEventListener('click', (event) => {
        const item = event.target.closest('.shop-product-item');
        if (!item) return;
        if (event.target.closest('.shop-item-delete') && window.confirm('Delete this Shop By Product item?')) item.remove();
        if (event.target.closest('.shop-item-edit')) item.querySelector('input[type="text"]')?.focus();
        if (event.target.closest('.shop-item-up') && item.previousElementSibling) {
          item.parentElement.insertBefore(item, item.previousElementSibling);
        }
        if (event.target.closest('.shop-item-down') && item.nextElementSibling) {
          item.parentElement.insertBefore(item.nextElementSibling, item);
        }
        syncShopItemOrder();
      });
      shopItemList.closest('form')?.addEventListener('submit', syncShopItemOrder);
    }
    const newArrivalsManual = document.getElementById('new-arrivals-manual');
    const newArrivalsPicker = document.getElementById('new-arrivals-picker');
    const newArrivalsSources = document.querySelectorAll('input[name="settings[product_source]"]');
    const updateNewArrivalsSource = () => {
      if (!newArrivalsManual) return;
      const source = document.querySelector('input[name="settings[product_source]"]:checked')?.value;
      newArrivalsManual.hidden = source !== 'manual';
    };
    newArrivalsSources.forEach((input) => input.addEventListener('change', updateNewArrivalsSource));
    updateNewArrivalsSource();
    newArrivalsPicker?.addEventListener('click', (event) => {
      const product = event.target.closest('.new-arrivals-product');
      if (!product) return;
      if (event.target.closest('.new-arrivals-up') && product.previousElementSibling) {
        product.parentElement.insertBefore(product, product.previousElementSibling);
      }
      if (event.target.closest('.new-arrivals-down') && product.nextElementSibling) {
        product.parentElement.insertBefore(product.nextElementSibling, product);
      }
    });
  </script>
</body>
</html>
