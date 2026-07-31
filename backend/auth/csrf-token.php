<?php
require_once __DIR__ . '/../utils/csrf.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'csrf_token' => girffonCsrfToken(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
