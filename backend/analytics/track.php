<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/dashboard-data.php';

function girffonAnalyticsDebugLog(array $entry): void
{
    if (function_exists('girffonAdminAnalyticsDebugLog')) {
        girffonAdminAnalyticsDebugLog($entry);
        return;
    }

    $directory = __DIR__ . '/../logs';
    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents($directory . '/visitor-analytics-debug.log', $line, FILE_APPEND);
}

function girffonAnalyticsDebugPayloadValue(array $payload, string $key, $default = '')
{
    if (array_key_exists($key, $payload)) {
        return $payload[$key];
    }
    if (isset($payload['meta']) && is_array($payload['meta']) && array_key_exists($key, $payload['meta'])) {
        return $payload['meta'][$key];
    }

    return $default;
}

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    $response = [
        'ok' => false,
        'message' => 'Method not allowed.',
    ];
    girffonAnalyticsDebugLog([
        'event_type' => 'invalid_method',
        'page_url' => '',
        'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'platform' => (string) ($_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? ''),
        'viewport_width' => 0,
        'screen_width' => 0,
        'touch_points' => 0,
        'device_pixel_ratio' => 0,
        'detected_device' => girffonAdminAnalyticsDetectDevice((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
        'endpoint_response' => $response,
        'http_status' => 405,
    ]);
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode(is_string($raw) ? $raw : '', true);
$payload = is_array($payload) ? $payload : [];

$deviceDecision = girffonAdminAnalyticsDetectDeviceDecision((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), $payload);
$deviceSignals = girffonAdminAnalyticsDeviceSignals($payload);
$sourceDecision = girffonAdminAnalyticsTrafficSourceDecision($payload, (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), (string) ($_SERVER['HTTP_REFERER'] ?? ''));

$tracked = girffonAdminTrackWebsiteVisitor($pdo, $payload);
$trackDebug = function_exists('girffonAdminAnalyticsGetLastTrackDebug') ? girffonAdminAnalyticsGetLastTrackDebug() : [];

$response = [
    'ok' => true,
    'tracked' => $tracked,
];

girffonAnalyticsDebugLog([
    'timestamp' => gmdate('c'),
    'tracker_version' => (string) girffonAnalyticsDebugPayloadValue($payload, 'tracker_version', ''),
    'event_type' => (string) ($payload['event_type'] ?? 'page_view'),
    'page_url' => (string) girffonAnalyticsDebugPayloadValue($payload, 'page_url', $payload['page_path'] ?? ''),
    'page_path' => (string) ($payload['page_path'] ?? ''),
    'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'platform' => (string) girffonAnalyticsDebugPayloadValue($payload, 'platform', $deviceSignals['platform'] ?? ''),
    'viewport_width' => (int) girffonAnalyticsDebugPayloadValue($payload, 'viewport_width', 0),
    'viewport_height' => (int) girffonAnalyticsDebugPayloadValue($payload, 'viewport_height', 0),
    'screen_width' => (int) girffonAnalyticsDebugPayloadValue($payload, 'screen_width', 0),
    'screen_height' => (int) girffonAnalyticsDebugPayloadValue($payload, 'screen_height', 0),
    'touch_points' => (int) girffonAnalyticsDebugPayloadValue($payload, 'touch_points', 0),
    'device_pixel_ratio' => (float) girffonAnalyticsDebugPayloadValue($payload, 'device_pixel_ratio', 0),
    'pointer_type' => (string) girffonAnalyticsDebugPayloadValue($payload, 'pointer_type', $deviceSignals['pointer_type'] ?? ''),
    'orientation' => (string) girffonAnalyticsDebugPayloadValue($payload, 'orientation', $deviceSignals['orientation'] ?? ''),
    'ua_client_hints' => girffonAnalyticsDebugPayloadValue($payload, 'ua_client_hints', []),
    'detected_device' => (string) ($deviceDecision['device'] ?? 'Desktop'),
    'matched_detection_rule' => (string) ($deviceDecision['rule'] ?? 'desktop_fallback'),
    'tracked' => $tracked,
    'action_result' => (string) ($trackDebug['action_result'] ?? ($tracked ? 'tracking_completed' : 'tracking_not_recorded')),
    'database_insert_result' => (string) ($trackDebug['database_insert_result'] ?? ($tracked ? 'recorded' : 'not_recorded')),
    'document_referrer' => (string) ($sourceDecision['document_referrer'] ?? ($payload['referrer'] ?? '')),
    'utm_source' => (string) ($sourceDecision['utm_source'] ?? ''),
    'fbclid' => (string) ($sourceDecision['fbclid'] ?? ''),
    'traffic_source' => (string) ($sourceDecision['source'] ?? 'Direct'),
    'matched_source_rule' => (string) ($sourceDecision['rule'] ?? 'direct_no_referrer'),
    'endpoint_response' => $response,
    'http_status' => $tracked ? 200 : 202,
]);

http_response_code($tracked ? 200 : 202);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);