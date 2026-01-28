<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://kabba.local',
        'http://admin.kabba.local',
        'https://timetrackerpro.kabba.ai',
        'https://timetrackerpro.rentnking.com',
        'https://opportunities.rentnking.com',
        'https://opportunities.kabba.ai',

    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
