<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/dashboard-data.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode(is_string($raw) ? $raw : '', true);
$payload = is_array($payload) ? $payload : [];

$tracked = girffonAdminTrackWebsiteVisitor($pdo, $payload);

http_response_code($tracked ? 200 : 202);
echo json_encode([
    'ok' => true,
    'tracked' => $tracked,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);