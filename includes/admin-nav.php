<?php
$adminNavItems = [
    'dashboard' => [
        'number' => '1.',
        'label' => 'Dashboard',
        'file' => 'admin-dashboard.php',
    ],
    'products' => [
        'number' => '2.',
        'label' => 'Products',
        'file' => 'admin-products.php',
    ],
    'orders' => [
        'number' => '3.',
        'label' => 'Orders',
        'file' => 'admin-orders.php',
    ],
    'invoices' => [
        'number' => '4.',
        'label' => 'Invoices',
        'file' => 'admin-invoices.php',
    ],
    'messages' => [
        'number' => '5.',
        'label' => 'Messages',
        'file' => 'admin-messages.php',
    ],
    'users' => [
        'number' => '6.',
        'label' => 'Users',
        'file' => 'admin-users.php',
    ],
    'newsletter' => [
        'number' => '7.',
        'label' => 'Newsletter',
        'file' => 'admin-newsletter.php',
    ],
    'custom_orders' => [
        'number' => '8.',
        'label' => 'Custom Design Orders',
        'file' => 'admin-custom-orders.php',
    ],
    'settings' => [
        'number' => '9.',
        'label' => 'Settings',
        'file' => 'admin-settings.php',
    ],
    'gift_cards' => [
        'number' => '10.',
        'label' => 'Gift Cards',
        'file' => 'admin-gift-cards.php',
    ],
    'homepage' => [
        'number' => '11.',
        'label' => 'Homepage',
        'file' => 'admin-homepage.php',
    ],
    'mobile_app' => [
        'number' => '12.',
        'label' => 'Mobile App',
        'file' => 'admin-mobile-app.php',
    ],
];

$adminNavCurrentPage = isset($adminNavCurrentPage) ? (string) $adminNavCurrentPage : '';
$adminNavBasePath = isset($adminNavBasePath) ? trim((string) $adminNavBasePath) : '';
$adminNavVisibleKeys = isset($adminNavVisibleKeys) && is_array($adminNavVisibleKeys)
    ? array_values($adminNavVisibleKeys)
    : array_keys($adminNavItems);

$adminNavPathPrefix = '';
if ($adminNavBasePath !== '') {
    $adminNavPathPrefix = rtrim(str_replace('\\', '/', $adminNavBasePath), '/') . '/';
}
?>
<nav class="admin-nav">
  <?php foreach ($adminNavVisibleKeys as $adminNavKey): ?>
    <?php if (!isset($adminNavItems[$adminNavKey])) { continue; } ?>
    <?php
      $adminNavItem = $adminNavItems[$adminNavKey];
      $adminNavHref = $adminNavPathPrefix . $adminNavItem['file'];
      $adminNavIsActive = $adminNavKey === $adminNavCurrentPage;
      $adminNavLabel = $adminNavItem['label'];
      $adminNavNumber = $adminNavItem['number'];
    ?>
    <a class="admin-nav-link<?php echo $adminNavIsActive ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($adminNavHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($adminNavLabel, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($adminNavLabel, ENT_QUOTES, 'UTF-8'); ?>"><span class="admin-nav-link-index"><?php echo htmlspecialchars($adminNavNumber, ENT_QUOTES, 'UTF-8'); ?> </span><span class="admin-nav-link-label"><?php echo htmlspecialchars($adminNavLabel, ENT_QUOTES, 'UTF-8'); ?></span></a>
  <?php endforeach; ?>
</nav>