<?php
require_once __DIR__ . "/backend/admin/session.php";
require_once __DIR__ . "/backend/admin/messages-data.php";

$adminMessageSettingsFile = __DIR__ . "/backend/admin/message-settings-data.php";
if (is_file($adminMessageSettingsFile)) {
  require_once $adminMessageSettingsFile;
}

$adminCurrentId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminCurrentUsername = trim((string) ($_SESSION['admin_username'] ?? 'GirffoN Admin'));

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
$adminMessageTotal = count($adminMessages);
$adminMessageUnread = 0;
foreach ($adminMessages as $adminMessageRow) {
  if (strtolower((string) ($adminMessageRow['status'] ?? 'unread')) === 'unread') {
    $adminMessageUnread++;
  }
}
$adminMessageRead = max(0, $adminMessageTotal - $adminMessageUnread);
$adminMessageStatusMessage = trim((string) ($_GET["status"] ?? ""));
$adminMessageErrorMessage = trim((string) ($_GET["error"] ?? ""));
$adminMessagePreferences = [
  'show_messages_overview' => true,
  'show_summary_cards' => true,
  'show_search_filters' => true,
  'show_message_list' => true,
  'show_subject_column' => true,
  'show_preview_column' => true,
  'show_status_column' => true,
  'show_date_column' => true,
  'show_view_action' => true,
  'show_mark_read_action' => true,
  'show_delete_action' => true,
  'show_contact_tools' => true,
];

if (function_exists('girffonAdminFetchMessagePreferences')) {
  $adminMessagePreferences = girffonAdminFetchMessagePreferences($pdo, $adminCurrentId, $adminCurrentUsername);
}

$showAdminMessagesOverview = !empty($adminMessagePreferences['show_messages_overview']);
$showAdminMessagesSummaryCards = !empty($adminMessagePreferences['show_summary_cards']);
$showAdminMessagesSearchFilters = !empty($adminMessagePreferences['show_search_filters']);
$showAdminMessagesList = !empty($adminMessagePreferences['show_message_list']);
$showAdminMessagesSubjectColumn = !empty($adminMessagePreferences['show_subject_column']);
$showAdminMessagesPreviewColumn = !empty($adminMessagePreferences['show_preview_column']);
$showAdminMessagesStatusColumn = !empty($adminMessagePreferences['show_status_column']);
$showAdminMessagesDateColumn = !empty($adminMessagePreferences['show_date_column']);
$showAdminMessagesViewAction = !empty($adminMessagePreferences['show_view_action']);
$showAdminMessagesMarkReadAction = !empty($adminMessagePreferences['show_mark_read_action']);
$showAdminMessagesDeleteAction = !empty($adminMessagePreferences['show_delete_action']);
$showAdminMessagesContactTools = !empty($adminMessagePreferences['show_contact_tools']);
$adminMessageVisibleColumnCount = 1
  + ($showAdminMessagesSubjectColumn ? 1 : 0)
  + ($showAdminMessagesPreviewColumn ? 1 : 0)
  + ($showAdminMessagesStatusColumn ? 1 : 0)
  + ($showAdminMessagesDateColumn ? 1 : 0)
  + 1;
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
$formatAdminMessagePreview = static function ($value, int $maxLength = 110) use ($escapeAdminMessage) {
  $text = trim((string) $value);
  if ($text === '') {
    return '';
  }

  if (strlen($text) > $maxLength) {
    $text = substr($text, 0, max(0, $maxLength - 3)) . '...';
  }

  return $escapeAdminMessage($text);
};
$formatAdminMessageContactValue = static function ($value) use ($escapeAdminMessage) {
  $text = trim((string) $value);
  return $text === '' ? '-' : $escapeAdminMessage($text);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GirffoN Admin Messages</title>
  <link rel="stylesheet" href="CSS/admin-girffon.css?v=20260518r11">
  <style>
    .admin-message-summary {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: flex-end;
    }

    .admin-message-summary-card {
      min-width: 112px;
      padding: 14px 16px;
      border-radius: 18px;
      border: 1px solid rgba(199, 165, 75, 0.18);
      background: linear-gradient(180deg, #fffdfa 0%, #f7efde 100%);
      box-shadow: 0 14px 28px rgba(124, 91, 37, 0.08);
    }

    .admin-message-summary-card span {
      display: block;
      font-size: 0.76rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #8d7a5e;
      margin-bottom: 6px;
    }

    .admin-message-summary-card strong {
      font-size: 1.35rem;
      color: #2b241b;
    }

    .admin-message-summary-card.is-warning {
      background: linear-gradient(180deg, #fffaf1 0%, #f8ead0 100%);
    }

    .admin-message-summary-card.is-success {
      background: linear-gradient(180deg, #f8fcf7 0%, #e8f4e7 100%);
    }

    .admin-message-search-form {
      margin-bottom: 22px;
    }

    .admin-message-filter-feedback {
      margin: -8px 0 18px;
      padding: 12px 16px;
      border-radius: 16px;
      border: 1px solid rgba(199, 165, 75, 0.22);
      background: linear-gradient(180deg, #fffdfa 0%, #f8f0df 100%);
      color: #5f4b2e;
      font-weight: 600;
    }

    .admin-message-filter-feedback.is-empty {
      border-color: rgba(159, 47, 47, 0.2);
      background: linear-gradient(180deg, #fff9f8 0%, #fbe9e4 100%);
      color: #9f2f2f;
    }

    .admin-message-meta {
      margin-top: 4px;
      color: #8d7a5e;
      font-size: 0.88rem;
    }

    .admin-message-preview {
      max-width: 44ch;
      color: #2b241b;
    }

    .admin-message-row-hidden {
      display: none;
    }

    .admin-modal-shell {
      position: fixed;
      inset: 0;
      z-index: 999;
      display: grid;
      place-items: center;
      padding: 24px;
    }

    .admin-modal-shell[hidden] {
      display: none !important;
    }

    .admin-modal-overlay {
      position: absolute;
      inset: 0;
      background: rgba(43, 36, 27, 0.42);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }

    .admin-modal-card {
      position: relative;
      z-index: 1;
      width: min(100%, 980px);
      max-height: min(88vh, 860px);
      overflow: auto;
      padding: 26px;
      border-radius: 28px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 249, 239, 0.99) 100%);
      border: 1px solid rgba(199, 165, 75, 0.24);
      box-shadow: 0 30px 60px rgba(43, 36, 27, 0.2);
    }

    .admin-modal-head {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      align-items: flex-start;
      margin-bottom: 18px;
    }

    .admin-modal-grid {
      display: grid;
      grid-template-columns: minmax(260px, 0.9fr) minmax(0, 1.4fr);
      gap: 18px;
    }

    .admin-modal-section {
      padding: 18px;
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.82);
      border: 1px solid rgba(199, 165, 75, 0.16);
    }

    .admin-modal-section > strong {
      display: block;
      margin-bottom: 14px;
      font-size: 1.06rem;
    }

    .admin-message-detail-list {
      margin: 0;
      display: grid;
      gap: 12px;
    }

    .admin-message-detail-list div {
      display: grid;
      gap: 4px;
      padding-bottom: 12px;
      border-bottom: 1px solid #efe7d8;
    }

    .admin-message-detail-list div:last-child {
      padding-bottom: 0;
      border-bottom: 0;
    }

    .admin-message-detail-list dt {
      font-size: 0.74rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: #8d7a5e;
      font-weight: 700;
    }

    .admin-message-detail-list dd {
      margin: 0;
      font-size: 0.98rem;
      color: #2b241b;
    }

    .admin-message-full-body-title {
      margin-top: 18px;
      margin-bottom: 10px;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--admin-text-soft);
      font-weight: 700;
    }

    .admin-message-full-body {
      margin-top: 18px;
      padding: 16px 18px;
      min-height: 180px;
      max-height: 320px;
      overflow-y: auto;
      border-radius: 18px;
      background: #fffdf9;
      border: 1px solid rgba(199, 165, 75, 0.18);
      white-space: pre-wrap;
      line-height: 1.7;
    }

    .admin-message-contact-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 16px;
    }

    .admin-button-disabled {
      opacity: 0.58;
      pointer-events: none;
    }

    .admin-table-actions {
      display: flex;
      flex-wrap: nowrap;
      gap: 10px;
      align-items: center;
      justify-content: center;
    }

    .admin-message-action-form {
      margin: 0;
    }

    .admin-message-action-cluster {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 0;
    }

    .admin-message-action-cluster .admin-message-action-form {
      display: inline-flex;
    }

    .admin-message-action-cluster .admin-action-button,
    .admin-message-action-cluster .admin-message-icon-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 42px;
      width: 42px;
      min-height: 42px;
      height: 42px;
      padding: 0;
      border: 1px solid rgba(199, 165, 75, 0.3);
      border-radius: 999px;
      background: linear-gradient(180deg, #fffdfa 0%, #f6eedc 100%);
      box-shadow: 0 10px 20px rgba(124, 91, 37, 0.08);
    }

    .admin-message-action-cluster .admin-action-button:hover,
    .admin-message-action-cluster .admin-action-button:focus-visible,
    .admin-message-action-cluster .admin-message-icon-button:hover,
    .admin-message-action-cluster .admin-message-icon-button:focus-visible {
      transform: translateY(-1px);
      border-color: rgba(169, 131, 34, 0.42);
      box-shadow: 0 14px 24px rgba(124, 91, 37, 0.12);
      background: linear-gradient(180deg, #fffefb 0%, #f3e7cf 100%);
    }

    .admin-message-action-cluster .admin-message-icon {
      width: 16px;
      height: 16px;
      flex: 0 0 auto;
    }

    @media (max-width: 820px) {
      .admin-message-summary {
        justify-content: flex-start;
      }

      .admin-modal-grid {
        grid-template-columns: 1fr;
      }

      .admin-table-actions {
        flex-wrap: wrap;
      }
    }

    @media (max-width: 768px) {
      .admin-message-summary-card {
        flex: 1 1 132px;
      }

      .admin-table-actions,
      .admin-message-action-cluster {
        justify-content: flex-start;
      }

      .admin-message-action-cluster {
        flex-wrap: wrap;
      }

      .admin-modal-shell {
        padding: 16px;
      }

      .admin-modal-card {
        padding: 18px;
        border-radius: 22px;
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

    @media (max-width: 520px) {
      .admin-main,
      .admin-page-section,
      .admin-page-section > .admin-panel,
      .admin-page-section > .admin-table-panel,
      .admin-grid-form,
      .admin-field,
      .admin-field-wide,
      .admin-message-summary,
      .admin-modal-grid,
      .admin-modal-section {
        min-width: 0;
      }

      .admin-main {
        overflow-x: hidden;
      }

      .admin-input,
      .admin-select {
        min-width: 0;
        padding: 12px 13px;
      }

      .admin-panel-head h2,
      .admin-modal-section > strong {
        font-size: 1rem;
      }

      .admin-inline-note,
      .admin-panel-note,
      .admin-feedback,
      .admin-message-preview,
      .admin-message-meta,
      .admin-message-detail-list dd,
      .admin-message-full-body,
      .admin-table td,
      .admin-table td strong {
        overflow-wrap: anywhere;
      }

      .admin-message-summary {
        gap: 8px;
      }

      .admin-message-summary-card {
        min-width: 0;
        flex: 1 1 calc(50% - 8px);
        padding: 12px 13px;
      }

      .admin-message-action-cluster .admin-action-button,
      .admin-message-action-cluster .admin-message-icon-button {
        min-width: 40px;
        width: 40px;
        min-height: 40px;
        height: 40px;
      }

      .admin-message-action-cluster .admin-message-icon {
        width: 15px;
        height: 15px;
      }

      .admin-table {
        min-width: 560px;
      }

      .admin-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
      }

      .admin-modal-shell {
        padding: 12px;
      }

      .admin-modal-card {
        width: min(100%, calc(100vw - 24px));
        padding: 16px;
        border-radius: 20px;
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

      .admin-message-summary-card {
        flex-basis: 100%;
      }

      .admin-table {
        min-width: 520px;
      }

      .admin-table-wrap {
        border-radius: 16px;
      }
    }
  </style>
</head>
<body class="admin-page" data-admin-page="messages" data-admin-messages-source="database" data-admin-messages-contact-tools="<?php echo $showAdminMessagesContactTools ? 'true' : 'false'; ?>">
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
        <a class="admin-nav-link" href="/GirffoN/admin-newsletter.php" aria-label="Newsletter" title="Newsletter">7. Newsletter</a>
        <a class="admin-nav-link" href="admin-custom-orders.php" aria-label="Custom Design Orders" title="Custom Design Orders">8. Custom Design Orders</a>
        <a class="admin-nav-link" href="admin-settings.php" aria-label="Settings" title="Settings">9. Settings</a>
        <a class="admin-nav-link" href="admin-gift-cards.php" aria-label="Gift Cards" title="Gift Cards">10. Gift Cards</a>
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
          <button class="admin-button admin-button-soft admin-refresh-button" type="button" aria-label="Refresh" title="Refresh" onclick="window.location.reload();">Refresh</button>
          <button class="admin-button admin-button-soft admin-settings-button" type="button" data-admin-settings data-admin-settings-target="setting-messages.php" aria-label="Settings" title="Settings">Settings</button>
          <button class="admin-button admin-button-danger admin-topbar-logout-button" type="button" data-admin-logout aria-label="Logout" title="Logout">Logout</button>
        </div>
      </header>

      <section class="admin-page-section">
        <?php if ($showAdminMessagesOverview): ?>
        <article class="admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Live Messages</h2>
              <p class="admin-panel-note">Customer contact messages are saved from the live contact form into MySQL.</p>
            </div>
          </div>
          <div id="adminMessagesStatus" class="admin-feedback" role="status" aria-live="polite"<?php if ($adminMessageErrorMessage): ?> style="color:#9f2f2f;"<?php endif; ?>><?php echo $escapeAdminMessage($adminMessageErrorMessage ?: $adminMessageStatusMessage); ?></div>
        </article>
        <?php endif; ?>

        <?php if ($showAdminMessagesList): ?>
        <article class="admin-table-panel">
          <div class="admin-panel-head">
            <div>
              <h2>Message List</h2>
              <p class="admin-panel-note">All customer message records in the admin database.</p>
            </div>
            <?php if ($showAdminMessagesSummaryCards): ?>
            <div class="admin-message-summary" aria-label="Message summary">
              <div class="admin-message-summary-card">
                <span>Total</span>
                <strong id="adminMessagesTotalCount"><?php echo $escapeAdminMessage((string) $adminMessageTotal); ?></strong>
              </div>
              <div class="admin-message-summary-card is-warning">
                <span>Unread</span>
                <strong id="adminMessagesUnreadCount"><?php echo $escapeAdminMessage((string) $adminMessageUnread); ?></strong>
              </div>
              <div class="admin-message-summary-card is-success">
                <span>Read</span>
                <strong id="adminMessagesReadCount"><?php echo $escapeAdminMessage((string) $adminMessageRead); ?></strong>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($showAdminMessagesSearchFilters): ?>
          <form id="adminMessagesSearchForm" class="admin-grid-form admin-message-search-form" action="#" method="GET">
            <div class="admin-field admin-field-wide">
              <label for="adminMessageSearchInput">Search</label>
              <input class="admin-input admin-message-search-input" id="adminMessageSearchInput" type="search" placeholder="Customer, email, phone, city, address, subject, or message" autocomplete="off">
            </div>
            <div class="admin-field">
              <label for="adminMessageStatusFilter">Status</label>
              <select class="admin-select admin-message-filter-select" id="adminMessageStatusFilter">
                <option value="all">All statuses</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
              </select>
            </div>
            <div class="admin-form-actions">
              <button id="adminMessageSearchButton" class="admin-button admin-button-accent admin-message-search-button" type="submit">Apply Filters</button>
              <button id="adminMessageSearchReset" class="admin-button admin-button-soft" type="button">Clear</button>
            </div>
          </form>

          <p id="adminMessagesFilterStatus" class="admin-inline-note admin-message-filter-feedback" role="status" aria-live="polite" data-total="<?php echo $escapeAdminMessage((string) $adminMessageTotal); ?>">Showing all <?php echo $escapeAdminMessage((string) $adminMessageTotal); ?> messages.</p>
          <?php endif; ?>

          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Customer</th>
                  <?php if ($showAdminMessagesSubjectColumn): ?><th>Subject</th><?php endif; ?>
                  <?php if ($showAdminMessagesPreviewColumn): ?><th>Message</th><?php endif; ?>
                  <?php if ($showAdminMessagesStatusColumn): ?><th>Status</th><?php endif; ?>
                  <?php if ($showAdminMessagesDateColumn): ?><th>Date</th><?php endif; ?>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="adminMessagesTableBody">
                <?php if ($adminMessages): ?>
                  <?php foreach ($adminMessages as $message): ?>
                    <?php
                      $messageName = (string) ($message['name'] ?? '');
                      $messageEmail = (string) ($message['email'] ?? '');
                      $messagePhone = (string) ($message['phone'] ?? '');
                      $messageCountry = (string) ($message['country'] ?? '');
                      $messageCity = (string) ($message['city'] ?? '');
                      $messageAddress = (string) ($message['address'] ?? '');
                      $messageSubject = (string) ($message['subject'] ?? '-');
                      $messageBody = (string) ($message['message'] ?? '');
                      $messageStatus = strtolower((string) ($message['status'] ?? 'unread'));
                      $messageSearchText = strtolower(trim($messageName . ' ' . $messageEmail . ' ' . $messagePhone . ' ' . $messageCountry . ' ' . $messageCity . ' ' . $messageAddress . ' ' . $messageSubject . ' ' . $messageBody));
                    ?>
                    <tr
                      data-message-status="<?php echo $escapeAdminMessage($messageStatus); ?>"
                      data-message-search="<?php echo $escapeAdminMessage($messageSearchText); ?>"
                      data-message-name="<?php echo $escapeAdminMessage($messageName); ?>"
                      data-message-email="<?php echo $escapeAdminMessage($messageEmail); ?>"
                      data-message-phone="<?php echo $escapeAdminMessage($messagePhone); ?>"
                      data-message-country="<?php echo $escapeAdminMessage($messageCountry); ?>"
                      data-message-city="<?php echo $escapeAdminMessage($messageCity); ?>"
                      data-message-address="<?php echo $escapeAdminMessage($messageAddress); ?>"
                      data-message-subject="<?php echo $escapeAdminMessage($messageSubject); ?>"
                      data-message-body="<?php echo $escapeAdminMessage($messageBody); ?>"
                      data-message-date="<?php echo $formatAdminMessageDate($message["created_at"] ?? ""); ?>"
                    >
                      <td>
                        <strong><?php echo $escapeAdminMessage($messageName); ?></strong>
                        <div><?php echo $escapeAdminMessage($messageEmail ?: "-"); ?></div>
                        <?php if (trim($messagePhone) !== ''): ?>
                          <div class="admin-message-meta"><?php echo $escapeAdminMessage($messagePhone); ?></div>
                        <?php endif; ?>
                      </td>
                      <?php if ($showAdminMessagesSubjectColumn): ?><td><?php echo $escapeAdminMessage($messageSubject); ?></td><?php endif; ?>
                      <?php if ($showAdminMessagesPreviewColumn): ?>
                      <td>
                        <div class="admin-message-preview"><?php echo nl2br($formatAdminMessagePreview($messageBody)); ?></div>
                      </td>
                      <?php endif; ?>
                      <?php if ($showAdminMessagesStatusColumn): ?><td><?php echo $formatAdminMessageLabel($messageStatus ?: "unread"); ?></td><?php endif; ?>
                      <?php if ($showAdminMessagesDateColumn): ?><td><?php echo $formatAdminMessageDate($message["created_at"] ?? ""); ?></td><?php endif; ?>
                      <td>
                        <div class="admin-table-actions">
                          <div class="admin-message-action-cluster" aria-label="Message actions">
                            <?php if ($showAdminMessagesViewAction): ?>
                            <button class="admin-action-button admin-message-icon-button" type="button" data-message-view data-action="view-message" aria-label="View message" title="View message">
                              <svg class="admin-message-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                <circle cx="12" cy="12" r="2.5" stroke="#2b241b" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></circle>
                              </svg>
                            </button>
                            <?php endif; ?>
                            <?php if ($showAdminMessagesMarkReadAction): ?>
                            <form method="POST" action="admin-messages.php" class="admin-message-action-form">
                              <input type="hidden" name="message_id" value="<?php echo $escapeAdminMessage((string) ($message['id'] ?? '0')); ?>">
                              <input type="hidden" name="action" value="mark-read">
                              <button class="admin-action-button admin-message-icon-button admin-action-button-read" type="submit" data-action="mark-read-message" aria-label="Mark as read" title="Mark as read">
                                <svg class="admin-message-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M20 6 9 17l-5-5" stroke="#2f7d4a" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($showAdminMessagesDeleteAction): ?>
                            <form method="POST" action="admin-messages.php" class="admin-message-action-form" onsubmit="return window.confirm('Delete this message?');">
                              <input type="hidden" name="message_id" value="<?php echo $escapeAdminMessage((string) ($message['id'] ?? '0')); ?>">
                              <input type="hidden" name="action" value="delete-message">
                              <button class="admin-action-button admin-message-icon-button admin-action-button-delete" type="submit" data-action="delete-message-row" aria-label="Delete message" title="Delete message">
                                <svg class="admin-message-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M3 6h18" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M8 6V4h8v2" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M19 6l-1 14H6L5 6" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                  <path d="M10 11v6M14 11v6" stroke="#b63a3a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </button>
                            </form>
                            <?php endif; ?>
                            <?php if (!$showAdminMessagesViewAction && !$showAdminMessagesMarkReadAction && !$showAdminMessagesDeleteAction): ?>
                              <span class="admin-panel-note">Locked</span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="<?php echo $escapeAdminMessage($adminMessageVisibleColumnCount); ?>" class="admin-empty">No messages found in the database yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <?php if ($showAdminMessagesViewAction): ?>
  <div id="adminMessageModal" class="admin-modal-shell" hidden>
    <div id="adminMessageModalOverlay" class="admin-modal-overlay"></div>
    <section class="admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="adminMessageModalTitle">
      <div class="admin-modal-head">
        <div>
          <p class="admin-page-subtitle">Message Details</p>
          <h2 id="adminMessageModalTitle">Customer Message</h2>
        </div>
        <button id="adminMessageModalClose" class="admin-action-button" type="button">Close</button>
      </div>
      <div class="admin-modal-grid">
        <div class="admin-modal-section">
          <strong>Customer</strong>
          <dl class="admin-message-detail-list">
            <div><dt>Name</dt><dd id="adminModalMessageName">-</dd></div>
            <div><dt>Email</dt><dd id="adminModalMessageEmail">-</dd></div>
            <div><dt>Phone</dt><dd id="adminModalMessagePhone">-</dd></div>
            <div><dt>Country</dt><dd id="adminModalMessageCountry">-</dd></div>
            <div><dt>City</dt><dd id="adminModalMessageCity">-</dd></div>
            <div><dt>Address</dt><dd id="adminModalMessageAddress">-</dd></div>
          </dl>
        </div>
        <div class="admin-modal-section">
          <strong>Message</strong>
          <p class="admin-panel-note">Click the eye icon to view full customer details and the complete message text.</p>
          <dl class="admin-message-detail-list">
            <div><dt>Subject</dt><dd id="adminModalMessageSubject">-</dd></div>
            <div><dt>Status</dt><dd id="adminModalMessageStatus">-</dd></div>
            <div><dt>Date</dt><dd id="adminModalMessageDate">-</dd></div>
          </dl>
          <div class="admin-message-full-body-title">Full Message</div>
          <div class="admin-message-full-body" id="adminModalMessageBody">-</div>
          <div class="admin-message-contact-actions">
            <a id="adminModalMessageEmailLink" class="admin-button admin-button-soft" href="#" target="_blank" rel="noopener noreferrer">Email</a>
            <a id="adminModalMessagePhoneLink" class="admin-button admin-button-soft" href="#" target="_blank" rel="noopener noreferrer">Call</a>
          </div>
        </div>
      </div>
    </section>
  </div>
  <?php endif; ?>

  <script src="JS/admin-girffon.js?v=20260518r11"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      if (document.body.dataset.adminMessagesReady === "true") {
        return;
      }

      const form = document.getElementById("adminMessagesSearchForm");
      const tableBody = document.getElementById("adminMessagesTableBody");
      const searchInput = document.getElementById("adminMessageSearchInput");
      const searchButton = document.getElementById("adminMessageSearchButton");
      const resetButton = document.getElementById("adminMessageSearchReset");
      const statusFilter = document.getElementById("adminMessageStatusFilter");
      const feedback = document.getElementById("adminMessagesFilterStatus");
      const totalNode = document.getElementById("adminMessagesTotalCount");
      const unreadNode = document.getElementById("adminMessagesUnreadCount");
      const readNode = document.getElementById("adminMessagesReadCount");
      const modal = document.getElementById("adminMessageModal");
      const overlay = document.getElementById("adminMessageModalOverlay");
      const closeButton = document.getElementById("adminMessageModalClose");
      const titleNode = document.getElementById("adminMessageModalTitle");
      const detailFields = {
        name: document.getElementById("adminModalMessageName"),
        email: document.getElementById("adminModalMessageEmail"),
        phone: document.getElementById("adminModalMessagePhone"),
        country: document.getElementById("adminModalMessageCountry"),
        city: document.getElementById("adminModalMessageCity"),
        address: document.getElementById("adminModalMessageAddress"),
        subject: document.getElementById("adminModalMessageSubject"),
        status: document.getElementById("adminModalMessageStatus"),
        date: document.getElementById("adminModalMessageDate"),
        body: document.getElementById("adminModalMessageBody"),
        emailLink: document.getElementById("adminModalMessageEmailLink"),
        phoneLink: document.getElementById("adminModalMessagePhoneLink")
      };
      let currentModalEmail = "";
      let currentModalPhone = "";
      let currentModalSubject = "";

      if (!tableBody || !modal || !overlay || !closeButton) {
        return;
      }

      const rows = Array.from(tableBody.querySelectorAll("tr[data-message-search]"));
      const filtersEnabled = !!(form && searchInput && statusFilter && feedback && totalNode && unreadNode && readNode);

      const getValue = function (value) {
        const text = String(value || "").trim();
        return text || "-";
      };

      const buildMailtoHref = function (emailValue, subjectValue) {
        const email = String(emailValue || "").trim();
        if (!email) {
          return "#";
        }

        const subject = String(subjectValue || "").trim();
        const query = subject ? "?subject=" + encodeURIComponent("Re: " + subject) : "";
        return "mailto:" + email + query;
      };

      const buildTelHref = function (phoneValue) {
        const phone = String(phoneValue || "").trim().replace(/\s+/g, "");
        return phone ? "tel:" + phone : "#";
      };

      const updateFeedback = function (visibleTotal, totalCount, query, status) {
        if (!feedback) {
          return;
        }

        const activeParts = [];
        if (query) {
          activeParts.push('search: "' + query + '"');
        }
        if (status && status !== "all") {
          activeParts.push("status: " + status);
        }

        if (visibleTotal === 0) {
          feedback.textContent = activeParts.length
            ? "No messages found for " + activeParts.join(" | ") + "."
            : "No messages found.";
        } else if (activeParts.length) {
          feedback.textContent = "Showing " + visibleTotal + " of " + totalCount + " messages for " + activeParts.join(" | ") + ".";
        } else {
          feedback.textContent = "Showing all " + totalCount + " messages.";
        }

        feedback.classList.toggle("is-empty", visibleTotal === 0);
      };

      const applyFilters = function () {
        if (!filtersEnabled) {
          return;
        }
        const query = String(searchInput.value || "").trim().toLowerCase();
        const status = String(statusFilter.value || "all").trim().toLowerCase();
        let visibleTotal = 0;
        let visibleUnread = 0;
        let visibleRead = 0;

        rows.forEach(function (row) {
          const rowSearch = String(row.dataset.messageSearch || "");
          const rowStatus = String(row.dataset.messageStatus || "unread").toLowerCase();
          const matchesQuery = !query || rowSearch.includes(query);
          const matchesStatus = status === "all" || rowStatus === status;
          const visible = matchesQuery && matchesStatus;

          row.classList.toggle("admin-message-row-hidden", !visible);

          if (!visible) {
            return;
          }

          visibleTotal += 1;
          if (rowStatus === "read") {
            visibleRead += 1;
          } else {
            visibleUnread += 1;
          }
        });

        if (totalNode) {
          totalNode.textContent = String(visibleTotal);
        }
        if (unreadNode) {
          unreadNode.textContent = String(visibleUnread);
        }
        if (readNode) {
          readNode.textContent = String(visibleRead);
        }

        updateFeedback(visibleTotal, rows.length, query, status);
      };

      const closeModal = function () {
        modal.hidden = true;
      };

      const openModal = function (row) {
        currentModalEmail = String(row.dataset.messageEmail || "").trim();
        currentModalPhone = String(row.dataset.messagePhone || "").trim();
        currentModalSubject = String(row.dataset.messageSubject || "").trim();

        detailFields.name.textContent = getValue(row.dataset.messageName);
        detailFields.email.textContent = getValue(row.dataset.messageEmail);
        detailFields.phone.textContent = getValue(row.dataset.messagePhone);
        detailFields.country.textContent = getValue(row.dataset.messageCountry);
        detailFields.city.textContent = getValue(row.dataset.messageCity);
        detailFields.address.textContent = getValue(row.dataset.messageAddress);
        detailFields.subject.textContent = getValue(row.dataset.messageSubject);
        detailFields.status.textContent = getValue(row.dataset.messageStatus);
        detailFields.date.textContent = getValue(row.dataset.messageDate);
        detailFields.body.textContent = getValue(row.dataset.messageBody);
        detailFields.body.scrollTop = 0;

        if (titleNode) {
          titleNode.textContent = getValue(row.dataset.messageName) === "-"
            ? "Customer Message"
            : getValue(row.dataset.messageName) + " Message";
        }

        if (detailFields.emailLink && detailFields.phoneLink) {
          const contactToolsEnabled = document.body.dataset.adminMessagesContactTools !== "false";

          detailFields.emailLink.hidden = !contactToolsEnabled;
          detailFields.phoneLink.hidden = !contactToolsEnabled;

          detailFields.emailLink.href = buildMailtoHref(currentModalEmail, currentModalSubject);
          detailFields.emailLink.classList.toggle("admin-button-disabled", !currentModalEmail || !contactToolsEnabled);
          detailFields.emailLink.setAttribute("aria-disabled", currentModalEmail && contactToolsEnabled ? "false" : "true");
          if (currentModalEmail && contactToolsEnabled) {
            detailFields.emailLink.removeAttribute("tabindex");
          } else {
            detailFields.emailLink.setAttribute("tabindex", "-1");
          }

          detailFields.phoneLink.href = buildTelHref(currentModalPhone);
          detailFields.phoneLink.classList.toggle("admin-button-disabled", !currentModalPhone || !contactToolsEnabled);
          detailFields.phoneLink.setAttribute("aria-disabled", currentModalPhone && contactToolsEnabled ? "false" : "true");
          if (currentModalPhone && contactToolsEnabled) {
            detailFields.phoneLink.removeAttribute("tabindex");
          } else {
            detailFields.phoneLink.setAttribute("tabindex", "-1");
          }
        }

        modal.hidden = false;
      };

      if (filtersEnabled && form) {
        form.addEventListener("submit", function (event) {
          event.preventDefault();
          applyFilters();
        });
      }

      if (filtersEnabled && searchInput) {
        searchInput.addEventListener("input", applyFilters);
        searchInput.addEventListener("keydown", function (event) {
          if (event.key === "Enter") {
            event.preventDefault();
            applyFilters();
          }
        });
      }

      if (filtersEnabled && searchButton) {
        searchButton.addEventListener("click", function (event) {
          event.preventDefault();
          applyFilters();
        });
      }

      if (filtersEnabled && resetButton) {
        resetButton.addEventListener("click", function () {
          searchInput.value = "";
          statusFilter.value = "all";
          applyFilters();
          searchInput.focus();
        });
      }

      if (filtersEnabled && statusFilter) {
        statusFilter.addEventListener("change", applyFilters);
      }

      if (detailFields.emailLink) {
        detailFields.emailLink.addEventListener("click", function (event) {
          event.preventDefault();
          if (!currentModalEmail || document.body.dataset.adminMessagesContactTools === "false") {
            window.alert("Email not available");
            return;
          }

          window.location.href = buildMailtoHref(currentModalEmail, currentModalSubject);
        });
      }

      if (detailFields.phoneLink) {
        detailFields.phoneLink.addEventListener("click", function (event) {
          event.preventDefault();
          if (!currentModalPhone || document.body.dataset.adminMessagesContactTools === "false") {
            window.alert("Phone not available");
            return;
          }

          window.location.href = buildTelHref(currentModalPhone);
        });
      }

      document.addEventListener("click", function (event) {
        const viewButton = event.target.closest("[data-message-view]");
        if (viewButton) {
          const row = viewButton.closest("tr[data-message-search]");
          if (row) {
            openModal(row);
          }
          return;
        }

        if (event.target === overlay || event.target === closeButton) {
          closeModal();
        }
      });

      document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && !modal.hidden) {
          closeModal();
        }
      });

      if (filtersEnabled) {
        applyFilters();
      }
    });
  </script>
</body>
</html>
