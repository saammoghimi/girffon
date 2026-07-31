<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/gift-cards-data.php';
require_once __DIR__ . '/../utils/csrf.php';

function girffonAdminGiftCardCancelRedirect(string $type, string $message, array $query = []): void
{
    $query[$type] = $message;
    header('Location: ../../admin-gift-cards.php?' . http_build_query($query));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminGiftCardCancelRedirect('error', 'Invalid request method.');
}

if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
    girffonAdminGiftCardCancelRedirect('error', 'Security token mismatch.');
}

$giftCardId = max(0, (int) ($_POST['id'] ?? 0));
if ($giftCardId <= 0) {
    girffonAdminGiftCardCancelRedirect('error', 'Gift card not found.');
}

try {
    girffonGiftCardUpdate($pdo, $giftCardId, [
        'status' => 'cancelled',
    ]);
    girffonAdminGiftCardCancelRedirect('status', 'Gift card cancelled successfully.', ['view' => (string) ($_POST['gift_code'] ?? '')]);
} catch (Throwable $throwable) {
    girffonAdminGiftCardCancelRedirect('error', $throwable->getMessage());
}
