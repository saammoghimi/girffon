<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../ai/ai-credit-service.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function girffonAiAdminResponse(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$adminId = (int) ($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['girffon_admin_id'] ?? 0);
$adminRole = strtolower(trim((string) ($_SESSION['admin_role'] ?? $_SESSION['role'] ?? '')));
if ($adminId <= 0 || $adminRole !== 'admin') {
    girffonAiAdminResponse(401, ['ok' => false, 'message' => 'Admin authentication required.']);
}

try {
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method === 'GET') {
        girffonAiAdminResponse(200, [
            'ok' => true,
            'settings' => girffonAiGetSettings($pdo),
            'counters' => girffonAiGetCounters($pdo),
        ]);
    }

    if ($method !== 'POST') {
        girffonAiAdminResponse(405, ['ok' => false, 'message' => 'Method not allowed.']);
    }

    $input = json_decode((string) file_get_contents('php://input'), true);
    $input = is_array($input) ? $input : [];
    $settings = girffonAiSaveSettings($pdo, $input, $adminId);

    girffonAiAdminResponse(200, [
        'ok' => true,
        'message' => 'GIRFFON AI settings saved.',
        'settings' => $settings,
        'counters' => girffonAiGetCounters($pdo),
    ]);
} catch (InvalidArgumentException $exception) {
    girffonAiAdminResponse(422, ['ok' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    girffonAiAdminResponse(500, ['ok' => false, 'message' => 'Unable to save GIRFFON AI settings.']);
}
