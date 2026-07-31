<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function girffonCsrfToken(): string
{
    if (empty($_SESSION['girffon_csrf_token']) || !is_string($_SESSION['girffon_csrf_token'])) {
        $_SESSION['girffon_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['girffon_csrf_token'];
}

function girffonCsrfValidate(?string $token): bool
{
    $sessionToken = (string) ($_SESSION['girffon_csrf_token'] ?? '');
    $requestToken = trim((string) $token);

    return $sessionToken !== '' && $requestToken !== '' && hash_equals($sessionToken, $requestToken);
}

function girffonCsrfRequestToken(): string
{
    $headerNames = [
        'HTTP_X_GIRFFON_CSRF',
        'HTTP_X_CSRF_TOKEN',
        'HTTP_X_XSRF_TOKEN',
    ];

    foreach ($headerNames as $headerName) {
        $value = trim((string) ($_SERVER[$headerName] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return trim((string) ($_POST['_csrf'] ?? $_POST['csrf_token'] ?? ''));
}
