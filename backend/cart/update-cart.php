<?php
require_once __DIR__ . '/cart-common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    girffonCartSendJson(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$data = girffonCartRequestData();
$lineKey = trim((string) ($data['line_key'] ?? ''));
$quantity = max(0, (int) ($data['quantity'] ?? $data['qty'] ?? 0));

if ($lineKey === '') {
    girffonCartSendJson(422, ['success' => false, 'message' => 'Cart item key is required.']);
}

$items = girffonCartSessionItems();
$index = girffonCartFindItemIndex($items, $lineKey);
if ($index < 0) {
    girffonCartSendJson(404, ['success' => false, 'message' => 'Cart item not found.']);
}

if ($quantity <= 0) {
    array_splice($items, $index, 1);
} else {
    $items[$index]['quantity'] = $quantity;
}

girffonCartSaveSessionItems($items);
girffonCartSendJson(200, [
    'success' => true,
    'message' => 'Cart updated successfully.',
    'cart' => girffonCartPayload(),
]);