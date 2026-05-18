<?php require_once __DIR__ . "/backend/admin/session.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Custom Design Orders</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r7">
</head>
<body class="admin-page" data-admin-page="custom-orders">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Custom design orders dashboard shell ready for future live order integration.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard"><span class="admin-nav-link-index">1. </span><span class="admin-nav-link-label">Dashboard</span></a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products"><span class="admin-nav-link-index">2. </span><span class="admin-nav-link-label">Products</span></a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders"><span class="admin-nav-link-index">3. </span><span class="admin-nav-link-label">Orders</span></a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices"><span class="admin-nav-link-index">4. </span><span class="admin-nav-link-label">Invoices</span></a>
        <a class="admin-nav-link" href="admin-messages.php" aria-label="Messages" title="Messages"><span class="admin-nav-link-index">5. </span><span class="admin-nav-link-label">Messages</span></a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users"><span class="admin-nav-link-index">6. </span><span class="admin-nav-link-label">Users</span></a>
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter"><span class="admin-nav-link-index">7. </span><span class="admin-nav-link-label">Newsletter</span></a>
        <a class="admin-nav-link is-active" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders"><span class="admin-nav-link-index">8. </span><span class="admin-nav-link-label">Custom Design Orders</span></a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Custom Design Orders</strong>
          <p class="admin-panel-note">This page is ready for future custom design order records and file review.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Custom Design Orders</h1>
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
              <h2>Custom Design Orders</h2>
              <p class="admin-panel-note">Empty admin table ready for custom design orders. Cart and checkout connection are not enabled yet.</p>
            </div>
          </div>
        </article>

        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Order List</h2>
              <p class="admin-panel-note">Incoming custom design orders will appear here after backend integration.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Product</th>
                  <th>Files</th>
                  <th>Text</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="8" class="admin-empty">No custom design orders yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script src="JS/admin-girffon.js?v=20260518r6"></script>
</body>
</html>