<?php
require_once __DIR__ . '/../cart/cart-common.php';
require_once __DIR__ . '/../utils/gift-card-service.php';
require_once __DIR__ . '/../utils/csrf.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonCartSendJson(405, ['success' => false, 'message' => 'Method not allowed.']);
}

if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
    girffonCartSendJson(419, ['success' => false, 'message' => 'Security token mismatch.']);
}

$data = girffonCartRequestData();
$amount = girffonGiftCardNormalizeAmount($data['amount'] ?? $data['gift_card_amount'] ?? 0);
$deliveryType = girffonGiftCardNormalizeDeliveryType((string) ($data['delivery_type'] ?? 'digital'));
$recipientName = trim((string) ($data['recipient_name'] ?? ''));
$recipientEmail = strtolower(trim((string) ($data['recipient_email'] ?? '')));
$buyerName = trim((string) ($data['buyer_name'] ?? ''));
$buyerEmail = strtolower(trim((string) ($data['buyer_email'] ?? '')));
$giftMessage = trim((string) ($data['gift_message'] ?? ''));
$expiresAt = trim((string) ($data['expires_at'] ?? ''));

if (!girffonGiftCardAmountAllowed($amount)) {
    girffonCartSendJson(422, ['success' => false, 'message' => 'Gift card amount is invalid.']);
}

if ($recipientName === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    girffonCartSendJson(422, ['success' => false, 'message' => 'Recipient name and email are required.']);
}

if ($buyerName === '' || !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
    girffonCartSendJson(422, ['success' => false, 'message' => 'Buyer name and email are required.']);
}

$giftCardItem = girffonCartNormalizeItem([
    'sku' => 'GIFT-CARD-' . strtoupper($deliveryType),
    'name' => 'GIRFFON Gift Card',
    'image' => 'Image/Logo/Logo.png',
    'size' => 'Gift Card',
    'color' => '#c9a56a',
    'quantity' => 1,
    'price' => $amount,
    'priceNumber' => $amount,
    'item_type' => 'gift_card',
    'delivery_type' => $deliveryType,
    'gift_card_amount' => $amount,
    'buyer_name' => $buyerName,
    'buyer_email' => $buyerEmail,
    'recipient_name' => $recipientName,
    'recipient_email' => $recipientEmail,
    'gift_message' => $giftMessage,
    'expires_at' => $expiresAt,
    'line_key' => sha1('gift-card|' . microtime(true) . '|' . random_int(1000, 999999)),
]);

if (!$giftCardItem) {
    girffonCartSendJson(422, ['success' => false, 'message' => 'Unable to prepare the gift card item.']);
}

$items = girffonCartSessionItems();
$items[] = $giftCardItem;
girffonCartSaveSessionItems($items);

girffonCartSendJson(200, [
    'success' => true,
    'message' => 'Gift card added to cart.',
    'cart' => girffonCartPayload(),
]);
