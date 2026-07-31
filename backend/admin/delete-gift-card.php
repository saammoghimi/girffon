<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/gift-cards-data.php';
require_once __DIR__ . '/../utils/csrf.php';

function girffonAdminGiftCardDeleteRedirect(string $type, string $message): void
{
    header('Location: ../../admin-gift-cards.php?' . http_build_query([$type => $message]));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminGiftCardDeleteRedirect('error', 'Invalid request method.');
}

if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
    girffonAdminGiftCardDeleteRedirect('error', 'Security token mismatch.');
}

$giftCardId = max(0, (int) ($_POST['id'] ?? 0));
if ($giftCardId <= 0) {
    girffonAdminGiftCardDeleteRedirect('error', 'Gift card not found.');
}

try {
    girffonGiftCardDelete($pdo, $giftCardId);
    girffonAdminGiftCardDeleteRedirect('status', 'Gift card deleted successfully.');
} catch (Throwable $throwable) {
    girffonAdminGiftCardDeleteRedirect('error', $throwable->getMessage());
}
