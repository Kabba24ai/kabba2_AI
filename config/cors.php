<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://kabba.local',
        'http://admin.kabba.local',
        'http://sales.kabba.local',
        'https://salesreport.kabba.ai',
        'https://salesreport.rentnking.com',
        'https://timetrackerpro.kabba.ai',
        'https://timetrackerpro.rentnking.com',
        'https://opportunities.rentnking.com',
        'https://opportunities.kabba.ai',

        
        'https://opportunities.seoequip.customers.kabba.ai',             
        'https://opportunities.pnwequipments.customers.kabba.ai',
        
        'https://timetrackerpro.seoequip.customers.kabba.ai/',
        'https://timetrackerpro.pnwequipments.customers.kabba.ai',

    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
