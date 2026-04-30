<?php

function girffonMailConfig(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $transport = strtolower(trim((string) (getenv('GIRFFON_MAIL_TRANSPORT') ?: 'smtp')));
    if ($transport === '') {
        $transport = 'smtp';
    }

    $smtpHost = (string) (getenv('GIRFFON_SMTP_HOST') ?: 'localhost');
    $smtpPort = (int) (getenv('GIRFFON_SMTP_PORT') ?: 25);
    $smtpUsername = (string) (getenv('GIRFFON_SMTP_USERNAME') ?: '');
    $smtpPassword = (string) (getenv('GIRFFON_SMTP_PASSWORD') ?: '');
    $smtpEncryption = (string) (getenv('GIRFFON_SMTP_ENCRYPTION') ?: '');
    $smtpAuth = filter_var((string) (getenv('GIRFFON_SMTP_AUTH') ?: ($smtpUsername !== '' ? 'true' : 'false')), FILTER_VALIDATE_BOOLEAN);

    $config = [
        'transport' => $transport,
        'from_email' => (string) (getenv('GIRFFON_MAIL_FROM_EMAIL') ?: 'orders@girffon.shop'),
        'from_name' => (string) (getenv('GIRFFON_MAIL_FROM_NAME') ?: 'GirffoN'),
        'reply_to_email' => (string) (getenv('GIRFFON_MAIL_REPLY_TO_EMAIL') ?: 'orders@girffon.shop'),
        'reply_to_name' => (string) (getenv('GIRFFON_MAIL_REPLY_TO_NAME') ?: 'GirffoN Support'),
        'app_url' => rtrim((string) (getenv('GIRFFON_APP_URL') ?: ''), '/'),
        'debug_log' => (string) (getenv('GIRFFON_MAIL_DEBUG_LOG') ?: ''),
        'smtp' => [
            'host' => $smtpHost,
            'port' => $smtpPort,
            'username' => $smtpUsername,
            'password' => $smtpPassword,
            'encryption' => $smtpEncryption,
            'auth' => $smtpAuth,
            'timeout' => (int) (getenv('GIRFFON_SMTP_TIMEOUT') ?: 20),
        ],
    ];

    return $config;
}