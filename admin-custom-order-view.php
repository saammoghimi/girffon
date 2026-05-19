<?php
require_once __DIR__ . '/backend/admin/session.php';
require_once __DIR__ . '/backend/admin/custom-design-orders-data.php';

$customDesignOrderId = (int) ($_GET['id'] ?? 0);
$showDemoCustomDesignOrder = isset($_GET['demo']) && $_GET['demo'] === '1';
$customDesignStatusMessage = trim((string) ($_GET['status'] ?? ''));
$customDesignErrorMessage = trim((string) ($_GET['error'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$showDemoCustomDesignOrder && $customDesignOrderId > 0) {
  $nextStatus = trim((string) ($_POST['status'] ?? 'new'));
  $nextAdminNote = trim((string) ($_POST['admin_note'] ?? ''));
  $saved = girffonAdminUpdateCustomDesignOrder($pdo, $customDesignOrderId, $nextStatus, $nextAdminNote);
  header('Location: admin-custom-order-view.php?id=' . urlencode((string) $customDesignOrderId) . '&' . ($saved ? 'status=' . rawurlencode('Order status and admin note saved.') : 'error=' . rawurlencode('Unable to save custom design order changes.')));
  exit;
}

$customDesignOrder = $showDemoCustomDesignOrder
  ? girffonAdminCustomDesignDemoOrderDetail()
  : girffonAdminFetchCustomDesignOrderDetail($pdo, $customDesignOrderId);

if (!$customDesignOrder) {
  $customDesignOrder = girffonAdminCustomDesignDemoOrderDetail();
  $showDemoCustomDesignOrder = true;
}

$escapeCustomDesignView = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatCustomDesignViewStatus = static function ($value) {
  return ucwords(str_replace('_', ' ', (string) $value));
};

$formatCustomDesignViewDate = static function ($value) {
  return girffonAdminCustomDesignFormatRomeDate((string) $value);
};

$customDesignStatuses = girffonAdminCustomDesignOrderStatuses();
$customDesignPreviewDownloadNames = [
  'front' => 'custom-order-front.png',
  'back' => 'custom-order-back.png',
  'right' => 'custom-order-right.png',
  'left' => 'custom-order-left.png',
];
$customDesignPreviewViews = is_array($customDesignOrder['preview_views'] ?? null) ? $customDesignOrder['preview_views'] : [];
$customDesignUploads = is_array($customDesignOrder['uploads'] ?? null) ? $customDesignOrder['uploads'] : [];
$customDesignTexts = is_array($customDesignOrder['texts'] ?? null) ? $customDesignOrder['texts'] : [];
$customDesignFlags = is_array($customDesignOrder['flags'] ?? null) ? $customDesignOrder['flags'] : [];
$customDesignShapes = is_array($customDesignOrder['shapes'] ?? null) ? $customDesignOrder['shapes'] : [];
$customDesignIcons = is_array($customDesignOrder['icons'] ?? null) ? $customDesignOrder['icons'] : [];
$customDesignFill = is_array($customDesignOrder['fill'] ?? null) ? $customDesignOrder['fill'] : [];
$customDesignSizeLines = is_array($customDesignOrder['size_lines'] ?? null) ? $customDesignOrder['size_lines'] : [];
$customDesignAddDesign = is_array($customDesignOrder['add_design'] ?? null) ? $customDesignOrder['add_design'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Custom Design Order Detail</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .admin-custom-detail-grid { display:grid; gap:18px; }
    .admin-custom-hero { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(280px,0.75fr); gap:18px; }
    .admin-custom-meta-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; }
    .admin-custom-meta-card { padding:16px 18px; border-radius:18px; border:1px solid rgba(199,165,75,0.15); background:rgba(255,255,255,0.88); }
    .admin-custom-meta-card span { display:block; color:#8a7753; font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; }
    .admin-custom-meta-card strong { display:block; margin-top:8px; color:#2b241b; font-size:1.15rem; }
    .admin-custom-preview-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
    .admin-custom-preview-card, .admin-custom-upload-card, .admin-custom-info-card { padding:18px; border-radius:22px; border:1px solid rgba(199,165,75,0.16); background:rgba(255,255,255,0.88); }
    .admin-custom-preview-card h3, .admin-custom-upload-card h3, .admin-custom-info-card h3 { margin:0 0 12px; color:#2b241b; font-size:1rem; }
    .admin-custom-preview-frame { min-height:220px; border-radius:18px; border:1px dashed rgba(160,131,54,0.34); background:linear-gradient(180deg, rgba(252,249,243,0.98), rgba(244,236,220,0.92)); display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .admin-custom-preview-frame img { width:100%; height:100%; object-fit:contain; display:block; }
    .admin-custom-preview-actions { display:flex; justify-content:flex-start; margin-top:12px; }
    .admin-custom-preview-empty { color:#7d715f; text-align:center; padding:24px; line-height:1.6; }
    .admin-custom-upload-grid { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:14px; }
    .admin-custom-upload-thumb { min-height:120px; border-radius:16px; background:rgba(247,240,225,0.9); border:1px solid rgba(199,165,75,0.15); display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .admin-custom-upload-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .admin-custom-upload-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
    .admin-custom-list { display:grid; gap:12px; }
    .admin-custom-list-item { padding:16px 18px; border-radius:18px; border:1px solid rgba(199,165,75,0.15); background:rgba(255,255,255,0.82); }
    .admin-custom-list-item strong { display:block; color:#2b241b; margin-bottom:8px; }
    .admin-custom-info-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
    .admin-custom-kv { display:grid; gap:8px; }
    .admin-custom-kv span { color:#8a7753; font-size:.84rem; text-transform:uppercase; letter-spacing:.08em; }
    .admin-custom-kv strong, .admin-custom-kv p { margin:0; color:#2b241b; }
    .admin-custom-note-box { padding:16px 18px; border-radius:18px; background:rgba(255,255,255,0.82); border:1px solid rgba(199,165,75,0.15); min-height:100px; white-space:pre-wrap; }
    .admin-custom-status-form { display:grid; gap:14px; }
    .admin-custom-status-form .admin-field { margin:0; }
    @media (max-width: 980px) { .admin-custom-hero, .admin-custom-upload-grid { grid-template-columns:1fr; } }
    @media (max-width: 720px) { .admin-custom-preview-grid, .admin-custom-meta-grid, .admin-custom-info-grid { grid-template-columns:1fr; } }
  </style>
</head>
<body class="admin-page" data-admin-page="custom-orders">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo"><img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo"></span>
        <p>Review custom design orders separately from cart, checkout, and invoice systems.</p>
      </div>
      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard"><span class="admin-nav-link-index">1. </span><span class="admin-nav-link-label">Dashboard</span></a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products"><span class="admin-nav-link-index">2. </span><span class="admin-nav-link-label">Products</span></a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders"><span class="admin-nav-link-index">3. </span><span class="admin-nav-link-label">Orders</span></a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices"><span class="admin-nav-link-index">4. </span><span class="admin-nav-link-label">Invoices</span></a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages"><span class="admin-nav-link-index">5. </span><span class="admin-nav-link-label">Messages</span></a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users"><span class="admin-nav-link-index">6. </span><span class="admin-nav-link-label">Users</span></a>
        <a class="admin-nav-link" href="admin-newsletter.php" aria-label="Newsletter" title="Newsletter"><span class="admin-nav-link-index">7. </span><span class="admin-nav-link-label">Newsletter</span></a>
        <a class="admin-nav-link is-active" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders"><span class="admin-nav-link-index">8. </span><span class="admin-nav-link-label">Custom Design Orders</span></a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings"><span class="admin-nav-link-index">9. </span><span class="admin-nav-link-label">Settings</span></a>
      </nav>
      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card"><strong>Order Review</strong><p class="admin-panel-note">Use this detail page to inspect each custom design before production.</p></section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Custom Design Order Detail</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button-soft admin-view-shop-button" href="Index.html" aria-label="View Shop" title="View Shop">View Shop</a>
          <a class="admin-button admin-button-soft admin-refresh-button" href="admin-custom-orders.php" aria-label="Back to Orders" title="Back to Orders">Orders</a>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section admin-custom-detail-grid">
        <section class="admin-custom-hero">
          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2><?php echo $escapeCustomDesignView($customDesignOrder['order_code'] ?? '-'); ?></h2>
                <p class="admin-panel-note">Receiver page for one custom design order. CartTest and Send Invoice are not used here.</p>
              </div>
            </div>
            <div class="admin-custom-meta-grid">
              <div class="admin-custom-meta-card"><span>Customer</span><strong><?php echo $escapeCustomDesignView($customDesignOrder['customer_name'] ?? '-'); ?></strong></div>
              <div class="admin-custom-meta-card"><span>Product</span><strong><?php echo $escapeCustomDesignView($customDesignOrder['product_name'] ?? '-'); ?></strong></div>
              <div class="admin-custom-meta-card"><span>Status</span><strong><?php echo $escapeCustomDesignView($formatCustomDesignViewStatus($customDesignOrder['status'] ?? 'new')); ?></strong></div>
              <div class="admin-custom-meta-card"><span>Date</span><strong><?php echo $escapeCustomDesignView($formatCustomDesignViewDate($customDesignOrder['created_at'] ?? '')); ?></strong></div>
            </div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2>Customer And Review</h2>
                <p class="admin-panel-note">Customer contact, internal note, and production readiness state.</p>
              </div>
            </div>
            <div class="admin-custom-info-grid">
              <div class="admin-custom-kv"><span>Email</span><strong><?php echo $escapeCustomDesignView($customDesignOrder['customer_email'] ?? '-'); ?></strong></div>
              <div class="admin-custom-kv"><span>Phone</span><strong><?php echo $escapeCustomDesignView($customDesignOrder['customer_phone'] ?? '-'); ?></strong></div>
            </div>
            <?php if ($customDesignStatusMessage !== ''): ?><p class="admin-feedback" role="status" aria-live="polite" style="margin-top:14px;"><?php echo $escapeCustomDesignView($customDesignStatusMessage); ?></p><?php endif; ?>
            <?php if ($customDesignErrorMessage !== ''): ?><p class="admin-feedback" role="alert" aria-live="assertive" style="margin-top:14px;color:#9f2f2f;"><?php echo $escapeCustomDesignView($customDesignErrorMessage); ?></p><?php endif; ?>
            <?php if ($showDemoCustomDesignOrder): ?>
              <p class="admin-panel-note" style="margin-top:14px;">Demo placeholder detail is shown because no real custom design order was found yet.</p>
            <?php endif; ?>
          </article>
        </section>

        <article class="admin-panel">
          <div class="admin-panel-head"><div><h2>T-Shirt Preview</h2><p class="admin-panel-note">Front, back, right sleeve, and left sleeve previews are separated for faster design review.</p></div></div>
          <div class="admin-custom-preview-grid">
            <?php foreach ($customDesignPreviewViews as $previewKey => $previewView): ?>
              <section class="admin-custom-preview-card">
                <h3><?php echo $escapeCustomDesignView($previewView['label'] ?? 'Preview'); ?></h3>
                <div class="admin-custom-preview-frame">
                  <?php if (!empty($previewView['path'])): ?>
                    <img src="<?php echo $escapeCustomDesignView($previewView['path']); ?>" alt="<?php echo $escapeCustomDesignView($previewView['label'] ?? 'Preview'); ?>">
                  <?php else: ?>
                    <div class="admin-custom-preview-empty">No preview image saved for this angle yet.</div>
                  <?php endif; ?>
                </div>
                <?php if (!empty($previewView['path'])): ?>
                  <div class="admin-custom-preview-actions">
                    <a class="admin-button admin-button-soft" href="<?php echo $escapeCustomDesignView($previewView['path']); ?>" download="<?php echo $escapeCustomDesignView($customDesignPreviewDownloadNames[$previewKey] ?? ('custom-order-' . $previewKey . '.png')); ?>" data-preview-download data-preview-src="<?php echo $escapeCustomDesignView($previewView['path']); ?>" data-preview-filename="<?php echo $escapeCustomDesignView($customDesignPreviewDownloadNames[$previewKey] ?? ('custom-order-' . $previewKey . '.png')); ?>">Download</a>
                  </div>
                <?php endif; ?>
              </section>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-head">
            <div><h2>Uploaded Photos</h2><p class="admin-panel-note">Customer-uploaded images from 1 to 100 can be reviewed and downloaded individually. ZIP download is a placeholder for the future archive flow.</p></div>
            <button class="admin-button admin-button-soft" type="button" disabled aria-disabled="true">Download All ZIP Placeholder</button>
          </div>
          <div class="admin-custom-upload-grid">
            <?php if ($customDesignUploads): ?>
              <?php foreach ($customDesignUploads as $customDesignUpload): ?>
                <section class="admin-custom-upload-card">
                  <div class="admin-custom-upload-thumb">
                    <?php if (!empty($customDesignUpload['path'])): ?>
                      <img src="<?php echo $escapeCustomDesignView($customDesignUpload['path']); ?>" alt="<?php echo $escapeCustomDesignView($customDesignUpload['name'] ?? 'Upload'); ?>">
                    <?php else: ?>
                      <div class="admin-custom-preview-empty">No image preview</div>
                    <?php endif; ?>
                  </div>
                  <div class="admin-custom-upload-actions">
                    <strong><?php echo $escapeCustomDesignView($customDesignUpload['name'] ?? '-'); ?></strong>
                    <span class="admin-panel-note"><?php echo $escapeCustomDesignView($customDesignUpload['size_label'] ?? '-'); ?></span>
                    <?php if (!empty($customDesignUpload['download_url'])): ?><a class="admin-button admin-button-soft" href="<?php echo $escapeCustomDesignView($customDesignUpload['download_url']); ?>" download>Download</a><?php endif; ?>
                  </div>
                </section>
              <?php endforeach; ?>
            <?php else: ?>
              <section class="admin-custom-upload-card"><div class="admin-custom-preview-empty">No uploaded photos found for this order yet.</div></section>
            <?php endif; ?>
          </div>
        </article>

        <section class="admin-custom-info-grid">
          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Text / Font Data</h2><p class="admin-panel-note">Text content, font name, size, color, position, and style.</p></div></div>
            <div class="admin-custom-list">
              <?php if ($customDesignTexts): ?>
                <?php foreach ($customDesignTexts as $textItem): ?>
                  <section class="admin-custom-list-item">
                    <strong><?php echo $escapeCustomDesignView($textItem['content'] ?? '-'); ?></strong>
                    <div class="admin-custom-kv"><span>Font Name</span><p><?php echo $escapeCustomDesignView($textItem['font_name'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Font Size</span><p><?php echo $escapeCustomDesignView($textItem['font_size'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Text Color</span><p><?php echo $escapeCustomDesignView($textItem['text_color'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Text Position</span><p><?php echo $escapeCustomDesignView($textItem['text_position'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Text Style</span><p><?php echo $escapeCustomDesignView($textItem['text_style'] ?? '-'); ?></p></div>
                  </section>
                <?php endforeach; ?>
              <?php else: ?>
                <section class="admin-custom-list-item"><strong>No text data</strong><p class="admin-panel-note">No text layers were stored with this custom design order.</p></section>
              <?php endif; ?>
            </div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Flags</h2><p class="admin-panel-note">Flag name, flag image or code, plus position and size when available.</p></div></div>
            <div class="admin-custom-list">
              <?php if ($customDesignFlags): ?>
                <?php foreach ($customDesignFlags as $flagItem): ?>
                  <section class="admin-custom-list-item">
                    <strong><?php echo $escapeCustomDesignView($flagItem['name'] ?? '-'); ?></strong>
                    <?php if (!empty($flagItem['image'])): ?><div class="admin-custom-preview-frame" style="min-height:120px;margin-bottom:12px;"><img src="<?php echo $escapeCustomDesignView($flagItem['image']); ?>" alt="<?php echo $escapeCustomDesignView($flagItem['name'] ?? 'Flag'); ?>"></div><?php endif; ?>
                    <div class="admin-custom-kv"><span>Flag Code</span><p><?php echo $escapeCustomDesignView($flagItem['code'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Position</span><p><?php echo $escapeCustomDesignView($flagItem['position'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Size</span><p><?php echo $escapeCustomDesignView($flagItem['size'] ?? '-'); ?></p></div>
                  </section>
                <?php endforeach; ?>
              <?php else: ?>
                <section class="admin-custom-list-item"><strong>No flag data</strong><p class="admin-panel-note">No flag record was stored with this order.</p></section>
              <?php endif; ?>
            </div>
          </article>
        </section>

        <section class="admin-custom-info-grid">
          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Shapes</h2><p class="admin-panel-note">Shape type or name, color, position, and size.</p></div></div>
            <div class="admin-custom-list">
              <?php if ($customDesignShapes): ?>
                <?php foreach ($customDesignShapes as $shapeItem): ?>
                  <section class="admin-custom-list-item">
                    <strong><?php echo $escapeCustomDesignView($shapeItem['name'] ?? '-'); ?></strong>
                    <div class="admin-custom-kv"><span>Shape Color</span><p><?php echo $escapeCustomDesignView($shapeItem['color'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Position</span><p><?php echo $escapeCustomDesignView($shapeItem['position'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Size</span><p><?php echo $escapeCustomDesignView($shapeItem['size'] ?? '-'); ?></p></div>
                  </section>
                <?php endforeach; ?>
              <?php else: ?>
                <section class="admin-custom-list-item"><strong>No shape data</strong><p class="admin-panel-note">No shape layers were stored with this custom design order.</p></section>
              <?php endif; ?>
            </div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Icons</h2><p class="admin-panel-note">Icon name or emoji, position, and size.</p></div></div>
            <div class="admin-custom-list">
              <?php if ($customDesignIcons): ?>
                <?php foreach ($customDesignIcons as $iconItem): ?>
                  <section class="admin-custom-list-item">
                    <strong><?php echo $escapeCustomDesignView($iconItem['name'] ?? '-'); ?></strong>
                    <div class="admin-custom-kv"><span>Position</span><p><?php echo $escapeCustomDesignView($iconItem['position'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Size</span><p><?php echo $escapeCustomDesignView($iconItem['size'] ?? '-'); ?></p></div>
                  </section>
                <?php endforeach; ?>
              <?php else: ?>
                <section class="admin-custom-list-item"><strong>No icon data</strong><p class="admin-panel-note">No icon or emoji layers were stored with this order.</p></section>
              <?php endif; ?>
            </div>
          </article>
        </section>

        <section class="admin-custom-info-grid">
          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Fill</h2><p class="admin-panel-note">Fill color or fill style applied to the product or a layer.</p></div></div>
            <div class="admin-custom-list">
              <?php if ($customDesignFill): ?>
                <?php foreach ($customDesignFill as $fillItem): ?>
                  <section class="admin-custom-list-item">
                    <strong><?php echo $escapeCustomDesignView(($fillItem['name'] ?? '') !== '' ? $fillItem['name'] : 'Fill'); ?></strong>
                    <div class="admin-custom-kv"><span>Fill Color / Value</span><p><?php echo $escapeCustomDesignView($fillItem['value'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Fill Style</span><p><?php echo $escapeCustomDesignView($fillItem['style'] ?? '-'); ?></p></div>
                  </section>
                <?php endforeach; ?>
              <?php else: ?>
                <section class="admin-custom-list-item"><strong>No fill data</strong><p class="admin-panel-note">No fill configuration was saved with this order.</p></section>
              <?php endif; ?>
            </div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Size / Color Lines</h2><p class="admin-panel-note">All fit, size, color, and quantity lines selected in the Cart &amp; Invoice panel.</p></div></div>
            <div class="admin-custom-list">
              <?php if ($customDesignSizeLines): ?>
                <?php foreach ($customDesignSizeLines as $sizeLineItem): ?>
                  <section class="admin-custom-list-item">
                    <strong><?php echo $escapeCustomDesignView(trim((string) (($sizeLineItem['fit'] ?? '') !== '' ? $sizeLineItem['fit'] : 'Size line'))); ?></strong>
                    <div class="admin-custom-kv"><span>Size</span><p><?php echo $escapeCustomDesignView($sizeLineItem['size'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Color</span><p><?php echo $escapeCustomDesignView($sizeLineItem['color'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Quantity</span><p><?php echo $escapeCustomDesignView((string) ($sizeLineItem['quantity'] ?? 1)); ?></p></div>
                  </section>
                <?php endforeach; ?>
              <?php else: ?>
                <section class="admin-custom-list-item"><strong>No size/color lines</strong><p class="admin-panel-note">No cart size or color lines were stored with this order.</p></section>
              <?php endif; ?>
            </div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Add Design</h2><p class="admin-panel-note">Selected design folder name, file name, and design image preview if available.</p></div></div>
            <div class="admin-custom-list">
              <?php if ($customDesignAddDesign): ?>
                <?php foreach ($customDesignAddDesign as $designItem): ?>
                  <section class="admin-custom-list-item">
                    <strong><?php echo $escapeCustomDesignView(($designItem['file_name'] ?? '') !== '' ? $designItem['file_name'] : 'Selected design'); ?></strong>
                    <?php if (!empty($designItem['image'])): ?><div class="admin-custom-preview-frame" style="min-height:160px;margin-bottom:12px;"><img src="<?php echo $escapeCustomDesignView($designItem['image']); ?>" alt="<?php echo $escapeCustomDesignView($designItem['file_name'] ?? 'Design image'); ?>"></div><?php endif; ?>
                    <div class="admin-custom-kv"><span>Layer</span><p><?php echo $escapeCustomDesignView(($designItem['name'] ?? '') !== '' ? $designItem['name'] : '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>View</span><p><?php echo $escapeCustomDesignView(($designItem['view'] ?? '') !== '' ? ucfirst((string) $designItem['view']) : '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Folder Name</span><p><?php echo $escapeCustomDesignView($designItem['folder_name'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>File Name</span><p><?php echo $escapeCustomDesignView($designItem['file_name'] ?? '-'); ?></p></div>
                    <div class="admin-custom-kv"><span>Position</span><p><?php echo $escapeCustomDesignView($designItem['position'] ?? '-'); ?></p></div>
                  </section>
                <?php endforeach; ?>
              <?php else: ?>
                <section class="admin-custom-list-item"><strong>No add-design data</strong><p class="admin-panel-note">No add-design record was stored with this custom design order.</p></section>
              <?php endif; ?>
            </div>
          </article>
        </section>

        <section class="admin-custom-info-grid">
          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Customer Notes</h2><p class="admin-panel-note">Customer explanation and instructions for the design.</p></div></div>
            <div class="admin-custom-note-box"><?php echo $escapeCustomDesignView(($customDesignOrder['customer_note'] ?? '') !== '' ? $customDesignOrder['customer_note'] : 'No customer instructions were provided.'); ?></div>
          </article>

          <article class="admin-panel">
            <div class="admin-panel-head"><div><h2>Admin Internal Note</h2><p class="admin-panel-note">Update internal note and status without touching invoice or payment flow.</p></div></div>
            <form class="admin-custom-status-form" method="POST" action="<?php echo $escapeCustomDesignView($showDemoCustomDesignOrder ? 'admin-custom-order-view.php?demo=1' : 'admin-custom-order-view.php?id=' . urlencode((string) $customDesignOrderId)); ?>">
              <div class="admin-field">
                <label for="customDesignOrderStatus">Status Change</label>
                <select class="admin-select" id="customDesignOrderStatus" name="status" <?php if ($showDemoCustomDesignOrder): ?>disabled<?php endif; ?>>
                  <?php foreach ($customDesignStatuses as $statusOption): ?>
                    <option value="<?php echo $escapeCustomDesignView($statusOption); ?>" <?php if (($customDesignOrder['status'] ?? 'new') === $statusOption): ?>selected<?php endif; ?>><?php echo $escapeCustomDesignView($formatCustomDesignViewStatus($statusOption)); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="admin-field admin-field-wide">
                <label for="customDesignAdminNote">Admin Internal Note</label>
                <textarea class="admin-textarea" id="customDesignAdminNote" name="admin_note" <?php if ($showDemoCustomDesignOrder): ?>disabled<?php endif; ?>><?php echo $escapeCustomDesignView($customDesignOrder['admin_note'] ?? ''); ?></textarea>
              </div>
              <div class="admin-form-actions">
                <button class="admin-button" type="submit" <?php if ($showDemoCustomDesignOrder): ?>disabled aria-disabled="true"<?php endif; ?>>Save Review</button>
              </div>
            </form>
          </article>
        </section>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
  <script>
    (function () {
      const previewDownloadLinks = document.querySelectorAll('[data-preview-download]');
      if (!previewDownloadLinks.length) {
        return;
      }

      function loadImage(src) {
        return new Promise((resolve, reject) => {
          const image = new Image();
          image.onload = () => resolve(image);
          image.onerror = () => reject(new Error('preview-load-failed'));
          image.src = src;
        });
      }

      async function downloadUpscaledPreview(event) {
        event.preventDefault();
        const link = event.currentTarget;
        if (!(link instanceof HTMLAnchorElement)) {
          return;
        }

        const src = (link.dataset.previewSrc || link.getAttribute('href') || '').trim();
        const filename = (link.dataset.previewFilename || link.getAttribute('download') || 'custom-order-preview.png').trim();
        if (!src) {
          return;
        }

        try {
          const image = await loadImage(src);
          const sourceWidth = Math.max(1, Number(image.naturalWidth) || 0);
          const sourceHeight = Math.max(1, Number(image.naturalHeight) || 0);
          const targetWidth = 900;
          const targetHeight = Math.max(1, Math.round((sourceHeight / sourceWidth) * targetWidth));
          const canvas = document.createElement('canvas');
          canvas.width = targetWidth;
          canvas.height = targetHeight;
          const ctx = canvas.getContext('2d');

          if (!ctx) {
            window.location.href = src;
            return;
          }

          ctx.imageSmoothingEnabled = true;
          ctx.imageSmoothingQuality = 'high';
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, targetWidth, targetHeight);
          ctx.drawImage(image, 0, 0, targetWidth, targetHeight);

          canvas.toBlob((blob) => {
            if (!blob) {
              window.location.href = src;
              return;
            }

            const blobUrl = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = blobUrl;
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            downloadLink.remove();
            window.setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
          }, 'image/png');
        } catch (error) {
          window.location.href = src;
        }
      }

      previewDownloadLinks.forEach((link) => {
        link.addEventListener('click', downloadUpscaledPreview);
      });
    }());
  </script>
</body>
</html>