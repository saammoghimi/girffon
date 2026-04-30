<?php
require_once __DIR__ . '/cart-common.php';

if (!in_array(($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['POST', 'DELETE'], true)) {
    girffonCartSendJson(405, ['success' => false, 'message' => 'Method not allowed.']);
}

girffonCartSaveSessionItems([]);
girffonCartSendJson(200, [
    'success' => true,
    'message' => 'Cart cleared successfully.',
    'cart' => girffonCartPayload(),
]);