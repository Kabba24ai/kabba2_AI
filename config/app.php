<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // Below are the custom configurations for the application ----------------------------------------------------------------------------------

    /*
    |--------------------------------------------------------------------------
    | Application Domain Configuration
    |--------------------------------------------------------------------------
    |
    | These values define the custom domains used in the application, such as
    | for the API, admin panel, and frontend. They are useful when routing
    | by subdomain or deploying to separate hostnames.
    |
    */

    'domains' => [
        'front' => env('FRONT_DOMAIN', 'kabba.local'),
        'admin' => env('ADMIN_DOMAIN', 'admin.kabba.local'),
        'api' => env('API_DOMAIN', 'api.kabba.local'),
        'api_url' => env('API_DOMAIN_URL', 'http://api.kabba.local'),
        'project_manager' => env('PROJECT_MANAGER_URL', 'http://projectmanager.kabba.ai'),
        'opportunities' => env('OPPORTUNITIES_DOMAIN', 'http://opportunities.kabba.ai'),
        'timetrackerpro' => env('TIMETRACKERPRO_DOMAIN', 'http://timetrackerpro.kabba.ai'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Date & Time Format Configuration
    |--------------------------------------------------------------------------
    |
    | Defines the formats used across the application for consistency.
    |
    */
    'date' => [
        'db_date_format' => env('DB_DATE_FORMAT', 'Y-m-d'),
        'db_time_format' => env('DB_TIME_FORMAT', 'H:i:s'),
        'db_date_time_format' => env('DB_DATE_TIME_FORMAT', 'Y-m-d H:i:s'),
        'date_format' => env('DATE_FORMAT', 'd/m/Y'),
        'time_format' => env('TIME_FORMAT', 'h:i A'),
        'date_time_format' => env('DATE_TIME_FORMAT', 'd/m/Y - h:i A'),
        'js_date_format' => env('JS_DATE_FORMAT', 'dd/MM/yyyy'),
        'aire_datepicker_format' => env('AIRE_DATEPICKER_FORMAT', 'MM/dd/yyyy'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image and File Settings
    |--------------------------------------------------------------------------
    |
    | Controls settings for media such as images and uploads.
    |
    */
    'webp_quality' => env('WEBP_QUALITY', 70),
    'max_file_upload_size' => env('MAX_FILE_UPLOAD_SIZE', 10),

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default records per page for various parts of the application.
    |
    */
    'pagination' => [
        'admin' => env('APP_RECORDS_PER_PAGE', 20),
        'front' => env('FRONT_RECORDS_PER_PAGE', 5),
        'front_search' => env('FRONT_SEARCH_RECORDS_PER_PAGE', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default currency formatting for the application.
    |
    */
    'currency' => [
        'code' => env('CURRENCY_CODE', '$'),
        'name' => env('CURRENCY_NAME', 'USD'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Seeders Settings
    |--------------------------------------------------------------------------
    |
    |
    */
    'seeders' => [
        'existing_settings_update' => env('EXISTING_SETTINGS_UPDATE', false),
    ],


    'super_admin_passcode' => env('SUPER_ADMIN_PASSCODE', '12345678'),

    'demo_enabled' => env('DEMO_ENABLED', false),

    'vite_origin_protocol' => env('VITE_ORIGIN_PROTOCOL', 'http'),

    'contact_number' => env('CONTACT_NUMBER', ''),

];
