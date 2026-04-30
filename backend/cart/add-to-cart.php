<?php
require_once __DIR__ . '/cart-common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonCartSendJson(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$item = girffonCartNormalizeItem(girffonCartRequestData());
if (!$item) {
    girffonCartSendJson(422, ['success' => false, 'message' => 'Invalid cart item payload.']);
}

$items = girffonCartSessionItems();
$index = girffonCartFindItemIndex($items, $item['line_key']);
if ($index >= 0) {
    $items[$index]['quantity'] = max(1, (int) ($items[$index]['quantity'] ?? 1)) + (int) $item['quantity'];
} else {
    $items[] = $item;
}

girffonCartSaveSessionItems($items);
girffonCartSendJson(200, [
    'success' => true,
    'message' => 'Item added to cart.',
    'cart' => girffonCartPayload(),
]);