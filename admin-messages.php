<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/messages-data.php";

function girffonAdminMessagesRedirect(string $type, string $message): void
{
  header("Location: /GirffoN/admin-messages.php?" . $type . "=" . rawurlencode($message));
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $messageId = max(0, (int) ($_POST['message_id'] ?? 0));
  $action = trim((string) ($_POST['action'] ?? ''));

  if ($messageId <= 0) {
    girffonAdminMessagesRedirect('error', 'Invalid message selected.');
  }

  if ($action === 'mark-read') {
    $updated = girffonAdminMarkMessageRead($pdo, $messageId);
    girffonAdminMessagesRedirect($updated ? 'status' : 'error', $updated ? 'Message marked as read.' : 'Unable to update the message.');
  }

  if ($action === 'delete-message') {
    $deleted = girffonAdminDeleteMessage($pdo, $messageId);
    girffonAdminMessagesRedirect($deleted ? 'status' : 'error', $deleted ? 'Message deleted successfully.' : 'Unable to delete the message.');
  }

  girffonAdminMessagesRedirect('error', 'Unknown message action.');
}

$adminMessages = girffonAdminFetchMessages($pdo);
$adminMessageStatusMessage = trim((string) ($_GET["status"] ?? ""));
$adminMessageErrorMessage = trim((string) ($_GET["error"] ?? ""));
$escapeAdminMessage = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$formatAdminMessageLabel = static function ($value) use ($escapeAdminMessage) {
  return $escapeAdminMessage(ucwords(str_replace("_", " ", (string) $value)));
};
$formatAdminMessageDate = static function ($value) use ($escapeAdminMessage) {
  if (!$value) {
    return "-";
  }
  $timestamp = strtotime((string) $value);
  return $timestamp ? $escapeAdminMessage(date("Y-m-d H:i", $timestamp)) : $escapeAdminMessage($value);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Messages</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css">
</head>
<body class="admin-page" data-admin-page="messages" data-admin-messages-source="database">
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-header">
        <span class="admin-brand" aria-label="GirffoN logo">
          <img class="admin-brand-logo" src="Image/Logo/logo for gif.png" alt="GirffoN Logo">
        </span>
        <p>Messages inbox for customer contact records.</p>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-link" href="admin-dashboard.php" aria-label="Dashboard" title="Dashboard">1. Dashboard</a>
        <a class="admin-nav-link" href="admin-products.php" aria-label="Products" title="Products">2. Products</a>
        <a class="admin-nav-link" href="admin-orders.php" aria-label="Orders" title="Orders">3. Orders</a>
        <a class="admin-nav-link" href="admin-invoices.php" aria-label="Invoices" title="Invoices">4. Invoices</a>
        <a class="admin-nav-link is-active" href="admin-messages.php" aria-label="Messages" title="Messages">5. Messages</a>
        <a class="admin-nav-link" href="admin-users.php" aria-label="Users" title="Users">6. Users</a>
      </nav>

      <div class="admin-sidebar-footer">
        <section class="admin-sidebar-card">
          <strong>Message Fields</strong>
          <p class="admin-panel-note">Customer name, email, message, and status.</p>
        </section>
        <button class="admin-logout-button" type="button" data-admin-logout>Logout</button>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <p class="admin-page-subtitle">Admin</p>
          <h1 class="admin-page-title" id="adminCurrentPage">Messages</h1>
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
              <h2>Live Messages</h2>
              <p class="admin-panel-note">Customer contact messages are saved from the live contact form into MySQL.</p>
            </div>
          </div>
          <div id="adminMessagesStatus" class="admin-feedback" role="status" aria-live="polite"<?php if ($adminMessageErrorMessage): ?> style="color:#9f2f2f;"<?php endif; ?>><?php echo $escapeAdminMessage($adminMessageErrorMessage ?: $adminMessageStatusMessage); ?></div>
        </article>

        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Message List</h2>
              <p class="admin-panel-note">All customer message records in the admin database.</p>
            </div>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Customer</th>
                  <th>Subject</th>
                  <th>Message</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="adminMessagesTableBody">
                <?php if ($adminMessages): ?>
                  <?php foreach ($adminMessages as $message): ?>
                    <tr>
                      <td>
                        <strong><?php echo $escapeAdminMessage($message["name"] ?? ""); ?></strong>
                        <div><?php echo $escapeAdminMessage($message["email"] ?? "-"); ?></div>
                      </td>
                      <td><?php echo $escapeAdminMessage($message["subject"] ?? "-"); ?></td>
                      <td><?php echo nl2br($escapeAdminMessage($message["message"] ?? "")); ?></td>
                      <td><?php echo $formatAdminMessageLabel($message["status"] ?? "unread"); ?></td>
                      <td><?php echo $formatAdminMessageDate($message["created_at"] ?? ""); ?></td>
                      <td>
                        <div class="admin-table-actions">
                          <form method="POST" action="admin-messages.php" style="display:inline;">
                            <input type="hidden" name="message_id" value="<?php echo $escapeAdminMessage((string) ($message['id'] ?? '0')); ?>">
                            <input type="hidden" name="action" value="mark-read">
                            <button class="admin-action-button" type="submit"<?php if (strtolower((string) ($message['status'] ?? '')) === 'read'): ?> disabled<?php endif; ?>>Mark Read</button>
                          </form>
                          <form method="POST" action="admin-messages.php" style="display:inline;" onsubmit="return window.confirm('Delete this message?');">
                            <input type="hidden" name="message_id" value="<?php echo $escapeAdminMessage((string) ($message['id'] ?? '0')); ?>">
                            <input type="hidden" name="action" value="delete-message">
                            <button class="admin-action-button" type="submit">Delete</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="admin-empty">No messages found in the database yet.</td>
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
