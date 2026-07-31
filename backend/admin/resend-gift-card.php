<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/gift-cards-data.php';
require_once __DIR__ . '/../utils/csrf.php';

function girffonAdminGiftCardResendRedirect(string $type, string $message, array $query = []): void
{
    $query[$type] = $message;
    header('Location: ../../admin-gift-cards.php?' . http_build_query($query));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonAdminGiftCardResendRedirect('error', 'Invalid request method.');
}

if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
    girffonAdminGiftCardResendRedirect('error', 'Security token mismatch.');
}

$giftCode = strtoupper(trim((string) ($_POST['gift_code'] ?? '')));
if ($giftCode === '') {
    girffonAdminGiftCardResendRedirect('error', 'Gift card code is required.');
}

$giftCard = girffonAdminFetchGiftCardByCode($pdo, $giftCode);
if (!$giftCard) {
    girffonAdminGiftCardResendRedirect('error', 'Gift card not found.');
}

if (($giftCard['delivery_type'] ?? '') !== 'digital') {
    girffonAdminGiftCardResendRedirect('error', 'Only digital gift cards can be resent.', ['view' => $giftCode]);
}

try {
    girffonSendGiftCardEmail($giftCard);
    girffonAdminGiftCardResendRedirect('status', 'Gift card email sent successfully.', ['view' => $giftCode]);
} catch (Throwable $throwable) {
    girffonAdminGiftCardResendRedirect('error', $throwable->getMessage(), ['view' => $giftCode]);
}
