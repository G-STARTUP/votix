<?php

return [
    // Moneroo API key (set in .env as MONEROO_API_KEY)
    'api_key' => env('MONEROO_API_KEY', ''),

    // Operating environment: SANDBOX or PRODUCTION
    'mode' => env('MONEROO_ENV', 'SANDBOX'),

    // Incoming webhook secret for signature verification
    'webhook_secret' => env('MONEROO_WEBHOOK_SECRET', ''),

    // HTTP timeout (seconds) for Moneroo client requests
    'timeout' => (int) env('MONEROO_TIMEOUT', 30),
];
