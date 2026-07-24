<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitored API Services
    |--------------------------------------------------------------------------
    |
    | Only services listed here (and set to true) get persisted into the
    | api_logs table. Add a key matching the service_name you pass to
    | App\Services\ApiLogger::log() to start monitoring another API.
    |
    */

    'services' => [
        'kabba_client_api' => true,
    ],

];
