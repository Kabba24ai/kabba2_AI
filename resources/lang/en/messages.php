<?php


return [

    /*
    |--------------------------------------------------------------------------
    | Messages Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various messages throughout the
    | application. You can modify these lines according to your application's
    | requirements.
    |
    */

    'api' => [
        'admin' => [
            'v1' => [
                'auth' => require base_path('resources/lang/en/api/admin/v1/auth/messages.php'),
                'orders' => require base_path('resources/lang/en/api/admin/v1/orders/messages.php'),
                'users' => require base_path('resources/lang/en/api/admin/v1/users/messages.php'),
            ],
        ],
    ],
];
