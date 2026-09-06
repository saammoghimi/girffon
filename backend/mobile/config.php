<?php
require_once __DIR__ . '/../admin/mobile-app-data.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60, stale-while-revalidate=300');

try {
    $configuration = girffonMobilePublishedConfiguration($pdo);
    $mobileBasePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/backend/mobile/config.php'))), '/');
    $backendBasePath = rtrim(str_replace('\\', '/', dirname($mobileBasePath)), '/');
    $configuration['endpoints'] = [
        'config' => $mobileBasePath . '/config.php',
        'catalog' => $mobileBasePath . '/catalog.php',
        'authentication' => $backendBasePath . '/auth/login.php',
        'profile' => $backendBasePath . '/profile/',
        'orders' => $backendBasePath . '/orders/',
        'gift_cards' => $backendBasePath . '/gift-cards/',
        'custom_design' => $backendBasePath . '/custom-design/',
    ];
    $body = json_encode([
        'success' => true,
        'data' => $configuration,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $etag = '"' . hash('sha256', $body) . '"';
    header('ETag: ' . $etag);
    if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
        http_response_code(304);
        exit;
    }
    echo $body;
} catch (Throwable $exception) {
    error_log('Mobile configuration API failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mobile configuration is temporarily unavailable.']);
}
