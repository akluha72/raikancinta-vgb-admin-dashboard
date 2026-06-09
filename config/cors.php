<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| The guest app and gallery are served from different domains than this API,
| so they need explicit CORS allowances. Allowed origins are pulled from
| CORS_ALLOWED_ORIGINS in .env (comma-separated) — never "*" in production.
|
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Falls back to localhost dev hosts if the env var is unset, so local
    // development works out of the box without opening it to the world.
    'allowed_origins' => $origins ?: [
        'http://localhost:5173',
        'http://localhost:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // The gallery token is returned in the JSON body and sent back as a
    // bearer header, so no credentialed cookies are required.
    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
