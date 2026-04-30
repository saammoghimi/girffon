<?php
require_once __DIR__ . '/cart-common.php';

girffonCartSendJson(200, [
    'success' => true,
    'cart' => girffonCartPayload(),
]);