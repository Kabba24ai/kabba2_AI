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
                'products' => require base_path('resources/lang/en/api/admin/v1/products/messages.php'),
                'product_categories' => require base_path('resources/lang/en/api/admin/v1/product_categories/messages.php'),
                'locations' => require base_path('resources/lang/en/api/admin/v1/locations/messages.php'),
                'equipment' => require base_path('resources/lang/en/api/admin/v1/equipment/messages.php'),
                'customer_checklists' => require base_path('resources/lang/en/api/admin/v1/customer_checklists/messages.php'),
                'rental_ready_checklists' => require base_path('resources/lang/en/api/admin/v1/rental_ready_checklists/messages.php'),
                'stores' => require base_path('resources/lang/en/api/admin/v1/stores/messages.php'),
                'customers' => require base_path('resources/lang/en/api/admin/v1/customers/messages.php'),
                'configurations' => require base_path('resources/lang/en/api/admin/v1/configurations/messages.php'),
                'user_notifications' => require base_path('resources/lang/en/api/admin/v1/user_notifications/messages.php'),

            ],
        ],

        'time_tracker' => [
            'v1' => [
                'auth' => require base_path('resources/lang/en/api/time_tracker/v1/auth/messages.php'),
            ],
        ],
    ],
];
