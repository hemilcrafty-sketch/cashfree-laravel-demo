<?php

return [
    'app_id' => env('CASHFREE_APP_ID'),
    'secret_key' => env('CASHFREE_SECRET_KEY'),
    'environment' => env('CASHFREE_ENVIRONMENT', 'sandbox'), // sandbox or production
    'api_version' => '2023-08-01',
    'return_url' => env('CASHFREE_RETURN_URL', 'http://localhost:8000/'),
];
