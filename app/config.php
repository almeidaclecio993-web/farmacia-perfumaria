<?php

declare(strict_types=1);

return [
    'app_name' => 'Farmácia e Perfumaria',
    'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8000',
    'whatsapp_number' => getenv('WHATSAPP_NUMBER') ?: '5511999999999',
    'admin' => [
        'email' => getenv('ADMIN_EMAIL') ?: '',
        'password' => getenv('ADMIN_PASSWORD') ?: '',
    ],
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'farmacia',
        'username' => getenv('DB_USERNAME') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    'session_name' => 'pharma_session',
    'csrf_secret' => getenv('CSRF_SECRET') ?: '',
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://127.0.0.1:8000/auth/google/callback',
    ],
];
