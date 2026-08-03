<?php

return [

    // Logged Admin App Endpoints
    //
    // The ONE place to opt an inbound Admin App endpoint into api_logs
    // (service_name 'admin_app'). Keys are URI patterns matched against
    // Illuminate\Http\Request::is() (wildcard supported, no leading slash);
    // values are the human-readable "name" stored on the log row. A request
    // matching NO pattern here is never logged — add an entry to start
    // logging another endpoint, no route or controller changes needed.
    //
    // Example (the '*' below is the wildcard, matching any order_product_unique_id):
    //   'api/admin/v1/queue-line/* /mark-staged' => 'Queue Line Mark Staged',
    // (write it with no space in the real entry — the space here only avoids
    // an accidental */ inside this comment block)

    'endpoints' => [
        'api/admin/v1/orders/customer-checklists/save-delivery' => 'Order Delivery Checklist Save',
        'api/admin/v1/orders/customer-checklists/save-return' => 'Order Return Checklist Save',
        'api/admin/v1/orders/schedules/driver-checklist' => 'Update Driver Checklist',
        'api/admin/v1/orders/schedules/update-delivery-pickup-inputs' => 'Update Delivery & Pickup Input Fields',
    ],

    // Comma-separated developer emails notified whenever one of the endpoints
    // above is logged with status 'failed'. Leave empty to disable emailing.
    'failure_notification_emails' => env('ADMIN_APP_API_FAILURE_EMAILS', ''),

];
