<?php

function girffonMobilePublicBasePath(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $backendPosition = strpos($scriptName, '/backend/');
    if ($backendPosition !== false) {
        return rtrim(substr($scriptName, 0, $backendPosition), '/');
    }

    return '';
}

function girffonMobilePublicUrl(string $value): string
{
    $value = trim(str_replace('\\', '/', $value));
    if ($value === '' || preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $value)) {
        return $value;
    }

    $path = ltrim(preg_replace('#^(?:\./)+#', '', $value), '/');
    $fragment = '';
    $fragmentPosition = strpos($path, '#');
    if ($fragmentPosition !== false) {
        $fragment = substr($path, $fragmentPosition + 1);
        $path = substr($path, 0, $fragmentPosition);
    }
    $path = str_replace(' ', '%20', $path);
    $basePath = girffonMobilePublicBasePath();

    return ($basePath !== '' ? $basePath : '') . '/' . $path . ($fragment !== '' ? '#' . rawurlencode($fragment) : '');
}

function girffonMobileNormalizePublicConfiguration(array $configuration): array
{
    foreach ($configuration as $key => &$value) {
        if (is_array($value)) {
            $value = girffonMobileNormalizePublicConfiguration($value);
            continue;
        }
        if (!is_string($value) || !in_array($key, ['image', 'image_url', 'mobile_image_url', 'tablet_image_url', 'product_link', 'detail_url', 'destination'], true)) {
            continue;
        }
        $value = girffonMobilePublicUrl($value);
    }
    unset($value);

    return $configuration;
}
