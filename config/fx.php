<?php
return [
    'api_url' => env('FX_API_URL', 'https://api.exchangerate.host/latest'),
    'base' => env('FX_API_BASE', 'USD'),
    'symbols' => explode(',', env('FX_SYMBOLS', 'XOF,XAF,NGN')), // comma separated list
    'timeout' => (int)env('FX_API_TIMEOUT', 10),
];