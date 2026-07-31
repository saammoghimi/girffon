<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/gift-cards-data.php';
require_once __DIR__ . '/../utils/csrf.php';

function girffonAdminGiftCardUpdateRedirect(string $type, string $message, array $query = []): void
{
    $query[$type] = $message;
    header('Location: ../../admin-gift-cards.php?' . http_build_query($query));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminGiftCardUpdateRedirect('error', 'Invalid request method.');
}

if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
    girffonAdminGiftCardUpdateRedirect('error', 'Security token mismatch.');
}

$giftCardId = max(0, (int) ($_POST['id'] ?? 0));
if ($giftCardId <= 0) {
    girffonAdminGiftCardUpdateRedirect('error', 'Gift card not found.');
}

try {
    girffonGiftCardUpdate($pdo, $giftCardId, [
        'buyer_name' => $_POST['buyer_name'] ?? '',
        'buyer_email' => $_POST['buyer_email'] ?? '',
        'recipient_name' => $_POST['recipient_name'] ?? '',
        'recipient_email' => $_POST['recipient_email'] ?? '',
        'gift_message' => $_POST['gift_message'] ?? '',
        'delivery_type' => $_POST['delivery_type'] ?? 'digital',
        'status' => $_POST['status'] ?? 'active',
        'expires_at' => $_POST['expires_at'] ?? '',
    ]);
    girffonAdminGiftCardUpdateRedirect('status', 'Gift card updated successfully.', ['edit' => $giftCardId]);
} catch (Throwable $throwable) {
    girffonAdminGiftCardUpdateRedirect('error', $throwable->getMessage(), ['edit' => $giftCardId]);
}
