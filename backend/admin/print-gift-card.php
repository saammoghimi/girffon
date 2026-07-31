<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/gift-cards-data.php';

$giftCode = strtoupper(trim((string) ($_GET['code'] ?? '')));
if ($giftCode === '') {
    http_response_code(404);
    echo 'Gift card not found.';
    exit;
}

$giftCard = girffonAdminFetchGiftCardByCode($pdo, $giftCode);
if (!$giftCard) {
    http_response_code(404);
    echo 'Gift card not found.';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
echo girffonGiftCardBuildPrintHtml($giftCard);
