<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/dashboard-data.php';

header('Content-Type: application/json; charset=utf-8');

$range = trim((string) ($_GET['range'] ?? '30days'));
$startDate = trim((string) ($_GET['start_date'] ?? ''));
$endDate = trim((string) ($_GET['end_date'] ?? ''));

echo json_encode([
    'ok' => true,
    'analytics' => girffonAdminFetchVisitorAnalytics($pdo, [
        'range' => $range,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);