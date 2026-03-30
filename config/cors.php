<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'https://kabba.ai',

        'http://kabba.local',
        'http://admin.kabba.local',

        'http://sales.kabba.local',
        'https://salesreport.kabba.ai',
        'https://salesreport.rentnking.com',

        config('app.domains.opportunities'),
        config('app.domains.timetrackerpro'),

    ],

    'allowed_origins_patterns' => [
        // Allow any local network IP on the Vite dev server port (local development only)
        '#^http://\d+\.\d+\.\d+\.\d+:5173$#',
        '#^http://\d+\.\d+\.\d+\.\d+:5174$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
