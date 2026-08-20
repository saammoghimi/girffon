<?php
require_once __DIR__ . '/backend/admin/session.php';
require_once __DIR__ . '/backend/admin/mobile-app-data.php';
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
$statusMessage = '';
$errorMessage = '';
$previewInput = null;
$allowedSections = girffonMobileAppHomeDefaults();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!girffonCsrfValidate($_POST['_csrf'] ?? '')) {
        $errorMessage = 'Security validation failed. Refresh the page and try again.';
    } else {
        $sectionKey = strtolower(trim((string) ($_POST['section_key'] ?? '')));
        $startAt = trim((string) ($_POST['start_at'] ?? ''));
        $endAt = trim((string) ($_POST['end_at'] ?? ''));
        $input = [
            'section_key' => $sectionKey,
            'section_name' => trim((string) ($_POST['section_name'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'subtitle' => trim((string) ($_POST['subtitle'] ?? '')),
            'image_url' => trim((string) ($_POST['image_url'] ?? '')),
            'mobile_image_url' => trim((string) ($_POST['mobile_image_url'] ?? '')),
            'tablet_image_url' => trim((string) ($_POST['tablet_image_url'] ?? '')),
            'button_text' => trim((string) ($_POST['button_text'] ?? '')),
            'button_destination' => trim((string) ($_POST['button_destination'] ?? '')),
            'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
            'display_order' => max(0, min(65535, (int) ($_POST['display_order'] ?? 0))),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'workflow_status' => 'draft',
        ];
        $action = (string) ($_POST['mobile_action'] ?? 'save');

        if (!isset($allowedSections[$sectionKey])) {
            $errorMessage = 'Unknown Home section.';
        } elseif ($input['section_name'] === '') {
            $errorMessage = 'Section name is required.';
        } elseif ($startAt !== '' && $endAt !== '' && strtotime($endAt) < strtotime($startAt)) {
            $errorMessage = 'End date must be after the start date.';
        } elseif ($action === 'preview') {
            $previewInput = $input;
            $statusMessage = 'Admin preview prepared. Nothing was published or sent to Flutter.';
        } else {
            $publish = $action === 'publish';
            if (girffonAdminSaveMobileAppHomeSection($pdo, $input, $adminId, $adminUsername, $publish)) {
                header('Location: admin-mobile-app.php?tab=home&section=' . rawurlencode($sectionKey) . '&status=' . ($publish ? 'published' : 'saved'));
                exit;
            }
            $errorMessage = 'The Home configuration could not be saved.';
        }
    }
}

$storageReady = girffonAdminEnsureMobileAppHomeTable($pdo);
$sections = girffonAdminFetchMobileAppHomeSections($pdo);
$sectionsByKey = [];
foreach ($sections as $section) {
    $sectionsByKey[(string) $section['section_key']] = $section;
}
$selectedKey = strtolower(trim((string) ($_GET['section'] ?? $_POST['section_key'] ?? 'hero-slider')));
if (!isset($allowedSections[$selectedKey])) {
    $selectedKey = 'hero-slider';
}
$selected = $previewInput ?? ($sectionsByKey[$selectedKey] ?? [
    'section_key' => $selectedKey,
    'section_name' => $allowedSections[$selectedKey],
    'title' => '', 'subtitle' => '', 'image_url' => '', 'mobile_image_url' => '',
    'tablet_image_url' => '', 'button_text' => '', 'button_destination' => '',
    'is_enabled' => 0, 'display_order' => 0, 'start_at' => null, 'end_at' => null,
    'workflow_status' => 'draft',
]);
$queryStatus = (string) ($_GET['status'] ?? '');
if ($queryStatus === 'saved') {
    $statusMessage = 'Home section saved as Draft. Flutter remains disconnected.';
} elseif ($queryStatus === 'published') {
    $statusMessage = 'Home configuration published inside Admin storage only. No mobile API is connected.';
}
$publishedCount = count(array_filter($sections, static fn(array $section): bool => ($section['workflow_status'] ?? '') === 'published'));
$enabledCount = count(array_filter($sections, static fn(array $section): bool => !empty($section['is_enabled'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Mobile App</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260819-mobile-app-1">
  <style>
    body.admin-page { overflow-x: hidden; }
    .mobile-admin-tabs { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:24px; }
    .mobile-admin-tab.is-active { background:linear-gradient(135deg,var(--admin-accent),var(--admin-accent-strong)); }
    .mobile-admin-overview { margin-bottom:26px; }
    .mobile-admin-status { display:inline-flex; align-items:center; padding:7px 10px; border-radius:999px; background:rgba(143,60,45,.1); color:#8f3c2d; font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .mobile-admin-status.is-ready { background:rgba(47,125,74,.12); color:#24613a; }
    .mobile-admin-tab-panel[hidden] { display:none; }
    .mobile-admin-layout { display:grid; grid-template-columns:minmax(220px,.34fr) minmax(0,1fr); gap:24px; }
    .mobile-admin-section-list { display:grid; gap:9px; align-content:start; }
    .mobile-admin-section-link { display:flex; justify-content:space-between; gap:12px; padding:12px 14px; border:1px solid var(--admin-border); border-radius:14px; color:var(--admin-text); text-decoration:none; background:var(--admin-surface-alt); }
    .mobile-admin-section-link.is-active { border-color:var(--admin-accent-strong); background:#fff7e2; }
    .mobile-admin-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
    .mobile-admin-form-grid label { display:grid; gap:8px; color:#433526; font-weight:600; }
    .mobile-admin-form-grid input, .mobile-admin-form-grid textarea, .mobile-admin-form-grid select { width:100%; padding:12px 14px; border:1px solid rgba(55,43,30,.12); border-radius:14px; background:rgba(255,250,244,.9); color:#1f1a14; font:inherit; }
    .mobile-admin-form-grid textarea { min-height:100px; resize:vertical; }
    .mobile-admin-wide { grid-column:1/-1; }
    .mobile-admin-toggle { display:flex !important; grid-column:1/-1; align-items:center; grid-template-columns:auto 1fr !important; }
    .mobile-admin-toggle input { width:auto; }
    .mobile-admin-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; }
    .mobile-admin-preview { margin-top:22px; padding:22px; border:1px dashed rgba(199,165,75,.45); border-radius:20px; background:#fffaf0; }
    .mobile-admin-placeholder-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin-top:18px; }
    .mobile-admin-placeholder { padding:18px; border-radius:18px; border:1px solid var(--admin-border); background:var(--admin-surface-alt); }
    .mobile-admin-placeholder h3 { margin:0 0 8px; }
    .mobile-admin-disabled { opacity:.62; pointer-events:none; }
    @media (max-width:1024px) { .mobile-admin-layout { grid-template-columns:1fr; } .mobile-admin-section-list { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width:768px) { .mobile-admin-section-list { grid-template-columns:repeat(2,minmax(0,1fr)); } .mobile-admin-form-grid, .mobile-admin-placeholder-grid { grid-template-columns:1fr; } .mobile-admin-wide { grid-column:auto; } }
    @media (max-width:480px) { .mobile-admin-tabs { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); } .mobile-admin-section-list { grid-template-columns:1fr; } .mobile-admin-actions .admin-button { width:100%; } }
  </style>
</head>
<body class="admin-page" data-admin-page="mobile-app">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo"><img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo"></span>
        <p>Prepare mobile content management without connecting Flutter.</p>
      </div>
      <?php $adminNavCurrentPage = 'mobile_app'; $adminNavBasePath = ''; require __DIR__ . '/includes/admin-nav.php'; ?>
      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card"><strong>Mobile App</strong><p class="admin-panel-note">Future-ready configuration. Integration remains disabled.</p></section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div><p class="admin-page-subtitle">Admin</p><h1 class="admin-page-title" id="adminCurrentPage">Mobile App</h1></div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <nav class="mobile-admin-tabs" aria-label="Mobile App management sections" role="tablist">
        <button class="admin-chip mobile-admin-tab is-active" type="button" role="tab" aria-selected="true" data-mobile-tab="home">Home</button>
        <button class="admin-chip mobile-admin-tab" type="button" role="tab" aria-selected="false" data-mobile-tab="shop">Shop</button>
        <button class="admin-chip mobile-admin-tab" type="button" role="tab" aria-selected="false" data-mobile-tab="custom-design">Custom Design</button>
        <button class="admin-chip mobile-admin-tab" type="button" role="tab" aria-selected="false" data-mobile-tab="account">Account</button>
      </nav>

      <?php if ($statusMessage !== '' || $errorMessage !== ''): ?>
        <div class="admin-feedback<?php echo $errorMessage !== '' ? ' is-error' : ' is-success'; ?>" role="status"><?php echo girffonMobileAdminEscape($errorMessage !== '' ? $errorMessage : $statusMessage); ?></div>
      <?php endif; ?>

      <section class="admin-card-grid mobile-admin-overview" aria-label="Mobile App overview">
        <article class="admin-stat-card"><span>Home</span><strong>Ready</strong><p class="admin-status"><span class="mobile-admin-status is-ready">Configuration Ready</span><br><?php echo $enabledCount; ?> enabled · <?php echo $publishedCount; ?> published</p></article>
        <article class="admin-stat-card"><span>Shop</span><strong>Off</strong><p class="admin-status"><span class="mobile-admin-status">Not Connected</span><br>Will reuse the existing product system.</p></article>
        <article class="admin-stat-card"><span>Custom Design</span><strong>Off</strong><p class="admin-status"><span class="mobile-admin-status">Not Connected</span><br>Existing resources remain untouched.</p></article>
        <article class="admin-stat-card"><span>Account</span><strong>Off</strong><p class="admin-status"><span class="mobile-admin-status">Not Connected</span><br>No customer authentication connection.</p></article>
      </section>

      <section class="mobile-admin-tab-panel" data-mobile-panel="home">
        <div class="mobile-admin-layout">
          <aside class="admin-panel mobile-admin-section-list" aria-label="Home sections">
            <?php foreach ($allowedSections as $key => $label): $row = $sectionsByKey[$key] ?? []; ?>
              <a class="mobile-admin-section-link<?php echo $key === $selectedKey ? ' is-active' : ''; ?>" href="admin-mobile-app.php?tab=home&amp;section=<?php echo rawurlencode($key); ?>"><span><?php echo girffonMobileAdminEscape($label); ?></span><small><?php echo girffonMobileAdminEscape(ucfirst((string) ($row['workflow_status'] ?? 'draft'))); ?></small></a>
            <?php endforeach; ?>
          </aside>

          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Home · <?php echo girffonMobileAdminEscape($allowedSections[$selectedKey]); ?></h2><p class="admin-panel-note">Stored for future API delivery. It is not currently consumed by Flutter.</p></div><span class="mobile-admin-status is-ready"><?php echo $storageReady ? 'Storage Ready' : 'Storage Unavailable'; ?></span></div>
            <form method="post" action="admin-mobile-app.php?tab=home&amp;section=<?php echo rawurlencode($selectedKey); ?>">
              <input type="hidden" name="_csrf" value="<?php echo girffonMobileAdminEscape($csrfToken); ?>">
              <input type="hidden" name="section_key" value="<?php echo girffonMobileAdminEscape($selectedKey); ?>">
              <div class="mobile-admin-form-grid">
                <label>Section name<input type="text" name="section_name" required maxlength="120" value="<?php echo girffonMobileAdminEscape($selected['section_name'] ?? ''); ?>"></label>
                <label>Display order<input type="number" name="display_order" min="0" max="65535" value="<?php echo (int) ($selected['display_order'] ?? 0); ?>"></label>
                <label class="mobile-admin-wide">Title<input type="text" name="title" maxlength="180" value="<?php echo girffonMobileAdminEscape($selected['title'] ?? ''); ?>"></label>
                <label class="mobile-admin-wide">Subtitle<textarea name="subtitle"><?php echo girffonMobileAdminEscape($selected['subtitle'] ?? ''); ?></textarea></label>
                <label class="mobile-admin-wide">Image path / URL<input type="text" name="image_url" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['image_url'] ?? ''); ?>" placeholder="Image/Mobile/home/hero-desktop.jpg"></label>
                <label>Mobile image path / URL<input type="text" name="mobile_image_url" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['mobile_image_url'] ?? ''); ?>"></label>
                <label>Tablet image path / URL<input type="text" name="tablet_image_url" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['tablet_image_url'] ?? ''); ?>"></label>
                <label>Button text<input type="text" name="button_text" maxlength="80" value="<?php echo girffonMobileAdminEscape($selected['button_text'] ?? ''); ?>"></label>
                <label>Button destination<input type="text" name="button_destination" maxlength="500" value="<?php echo girffonMobileAdminEscape($selected['button_destination'] ?? ''); ?>" placeholder="Future route or deep link"></label>
                <label>Start date<input type="datetime-local" name="start_at" value="<?php echo girffonMobileAdminEscape(girffonMobileAdminDateInput($selected['start_at'] ?? '')); ?>"></label>
                <label>End date<input type="datetime-local" name="end_at" value="<?php echo girffonMobileAdminEscape(girffonMobileAdminDateInput($selected['end_at'] ?? '')); ?>"></label>
                <label class="mobile-admin-toggle"><input type="checkbox" name="is_enabled" value="1" <?php echo !empty($selected['is_enabled']) ? 'checked' : ''; ?>><span>Section ON / OFF</span></label>
              </div>
              <div class="mobile-admin-actions">
                <button class="admin-button admin-button-accent" type="submit" name="mobile_action" value="save">Save Draft</button>
                <button class="admin-button admin-button-soft" type="submit" name="mobile_action" value="preview">Preview</button>
                <button class="admin-button" type="submit" name="mobile_action" value="publish">Publish</button>
              </div>
            </form>
            <div class="mobile-admin-preview" aria-label="Admin preview">
              <span class="mobile-admin-status<?php echo ($selected['workflow_status'] ?? 'draft') === 'published' ? ' is-ready' : ''; ?>"><?php echo girffonMobileAdminEscape(ucfirst((string) ($selected['workflow_status'] ?? 'draft'))); ?></span>
              <h3><?php echo girffonMobileAdminEscape(($selected['title'] ?? '') !== '' ? $selected['title'] : $selected['section_name']); ?></h3>
              <p><?php echo girffonMobileAdminEscape(($selected['subtitle'] ?? '') !== '' ? $selected['subtitle'] : 'No subtitle configured.'); ?></p>
              <p class="admin-panel-note">Admin-only preview · Flutter delivery disabled</p>
            </div>
          </article>
        </div>
      </section>

      <?php
      $futurePanels = [
          'shop' => ['Shop', ['Enable Mobile Shop', 'Product Source: Existing GIRFFON Shop', 'Categories & Product Visibility', 'New Arrivals & Featured Products', 'Sale Products', 'Gift Cards & Bundles']],
          'custom-design' => ['Custom Design', ['Design Library', 'Icons', 'Flags', 'Shapes', 'Categories', 'Mobile Custom Design Status', 'Future API Status']],
          'account' => ['Account', ['Membership', 'Login', 'Profile', 'Addresses', 'Wishlist', 'Orders & Order History', 'Gift Cards']],
      ];
      foreach ($futurePanels as $panelKey => [$panelTitle, $items]): ?>
        <section class="mobile-admin-tab-panel" data-mobile-panel="<?php echo girffonMobileAdminEscape($panelKey); ?>" hidden>
          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2><?php echo girffonMobileAdminEscape($panelTitle); ?></h2><p class="admin-panel-note">Preparation only. This area does not read, write, duplicate, or replace any existing GIRFFON system.</p></div><span class="mobile-admin-status">Integration Status: Not Connected</span></div>
            <div class="mobile-admin-placeholder-grid">
              <?php foreach ($items as $item): ?><article class="mobile-admin-placeholder"><h3><?php echo girffonMobileAdminEscape($item); ?></h3><p class="admin-panel-note">Reserved for future integration with the existing GIRFFON system.</p><button class="admin-button admin-button-soft mobile-admin-disabled" type="button" disabled>Not Connected</button></article><?php endforeach; ?>
            </div>
          </article>
        </section>
      <?php endforeach; ?>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
  <script>
    (function () {
      const tabs = Array.from(document.querySelectorAll('[data-mobile-tab]'));
      const panels = Array.from(document.querySelectorAll('[data-mobile-panel]'));
      function activateTab(key) {
        tabs.forEach(function (tab) {
          const active = tab.dataset.mobileTab === key;
          tab.classList.toggle('is-active', active);
          tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (panel) { panel.hidden = panel.dataset.mobilePanel !== key; });
        const url = new URL(window.location.href);
        url.searchParams.set('tab', key);
        window.history.replaceState({}, '', url);
      }
      tabs.forEach(function (tab) { tab.addEventListener('click', function () { activateTab(tab.dataset.mobileTab); }); });
      const requested = new URLSearchParams(window.location.search).get('tab');
      activateTab(tabs.some(function (tab) { return tab.dataset.mobileTab === requested; }) ? requested : 'home');
    }());
  </script>
</body>
</html>