<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ai-credit-service.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function girffonAiCustomerResponse(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonAiCustomerPayload(PDO $pdo, int $userId): array
{
    return [
        'ok' => true,
        'authenticated' => $userId > 0,
        'settings' => girffonAiGetSettings($pdo),
        'credits' => $userId > 0 ? girffonAiGetBalance($pdo, $userId) : null,
    ];
}

try {
    $userId = (int) ($_SESSION['girffon_user_id'] ?? $_SESSION['user_id'] ?? 0);
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method === 'GET') {
        girffonAiCustomerResponse(200, girffonAiCustomerPayload($pdo, $userId));
    }

    if ($method !== 'POST') {
        girffonAiCustomerResponse(405, ['ok' => false, 'message' => 'Method not allowed.']);
    }
    if ($userId <= 0) {
        girffonAiCustomerResponse(401, ['ok' => false, 'message' => 'Sign in to use GIRFFON AI credits.']);
    }

    $input = json_decode((string) file_get_contents('php://input'), true);
    $input = is_array($input) ? $input : [];
    $action = trim((string) ($input['action'] ?? ''));

    if ($action === 'mock_purchase') {
        girffonAiCustomerResponse(403, [
            'ok' => false,
            'message' => 'AI package purchases are currently unavailable. Please contact support.',
        ]);
    }

    if ($action === 'consume') {
        $mode = trim((string) ($input['mode'] ?? ''));
        $allowedModes = ['embroidery', 'knitted', 'toy-doll', 'cartoon', '3d-cartoon', 'photo-time'];
        if (!in_array($mode, $allowedModes, true)) {
            girffonAiCustomerResponse(422, ['ok' => false, 'message' => 'Invalid AI mode.']);
        }
        $balance = girffonAiConsumeCredit($pdo, $userId, $mode);
        girffonAiCustomerResponse(200, [
            'ok' => true,
            'message' => 'One AI credit used.',
            'credits' => $balance,
            'mock' => true,
        ]);
    }

    girffonAiCustomerResponse(422, ['ok' => false, 'message' => 'Unknown action.']);
} catch (RuntimeException $exception) {
    girffonAiCustomerResponse(409, ['ok' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    girffonAiCustomerResponse(500, ['ok' => false, 'message' => 'Unable to update AI credits.']);
}
