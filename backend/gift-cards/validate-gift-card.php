<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/gift-card-service.php';
require_once __DIR__ . '/../utils/csrf.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!girffonCsrfValidate(girffonCsrfRequestToken())) {
    http_response_code(419);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Security token mismatch.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$giftCode = strtoupper(trim((string) ($payload['gift_card_code'] ?? $payload['gift_code'] ?? '')));
if ($giftCode === '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Gift card code is required.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$clientIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    girffonGiftCardCheckRateLimit($clientIp . '|' . $giftCode);
    $giftCard = girffonGiftCardValidateForUse($pdo, $giftCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'gift_card' => [
            'gift_code' => (string) ($giftCard['gift_code'] ?? ''),
            'remaining_balance' => (float) ($giftCard['remaining_balance'] ?? 0),
            'expires_at' => (string) ($giftCard['expires_at'] ?? ''),
            'status' => (string) ($giftCard['status'] ?? 'active'),
        ],
        'message' => 'Gift card is valid.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $throwable) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'message' => $throwable->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
