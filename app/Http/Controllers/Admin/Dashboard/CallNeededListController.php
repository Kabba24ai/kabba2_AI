<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerCallNeeded;
use App\Helpers\CustomHelper;

class CallNeededListController extends Controller
{
    public function __invoke()
    {
       $calls = CustomerCallNeeded::with([
            'customer',
            'supplier',
            'creator',
            'assignee',
            'activities.user',
        ])
        ->where('status', 'active')
        ->where(function ($q) {
            $q->whereNull('follow_up_at')->orWhere('follow_up_at', '<=', now());
        })
        ->latest()
        ->get();

       return response()->json([
    'success' => true,
    'count'   => $calls->count(),
        'data'    => $calls->map(function ($call) {
                return [
                    'id' => $call->id,
                    'reason' => $call->reason,
                    'notes' => $call->notes,
                    'priority' => $call->priority?->value ?? 'normal',
                    'created_at' => CustomHelper::formatDateTime($call->created_at),
                    'was_rescheduled' => $call->follow_up_at !== null,
                    'follow_up_at_label' => $call->follow_up_at
                        ? $call->follow_up_at->format('M j, Y g:i A')
                        : null,

                  'contact_type' => $call->customer_id ? 'customer'
                    : ($call->supplier_id ? 'supplier' : 'manual'),

                  'customer' => $call->customer
                    ? [
                        'id'           => $call->customer->id,
                        'full_name'    => $call->customer->full_name,
                        'company_name' => $call->customer->company_name,
                        'phone'        => $call->customer->phone
                            ? CustomHelper::formatPhone($call->customer->phone)
                            : null,
                        'email'        => $call->customer->email,
                    ]
                    : ($call->supplier
                        ? [
                            'id'           => null,
                            'full_name'    => $call->supplier->name,
                            'company_name' => null,
                            'phone'        => $call->supplier->phone
                                ? CustomHelper::formatPhone($call->supplier->phone)
                                : ($call->supplier->primary_contact_phone
                                    ? CustomHelper::formatPhone($call->supplier->primary_contact_phone)
                                    : null),
                            'email'        => $call->supplier->email ?? $call->supplier->primary_contact_email,
                        ]
                        : [
                            'id'           => null,
                            'full_name'    => $call->contact_name,
                            'company_name' => null,
                            'phone'        => $call->contact_phone
                                ? CustomHelper::formatPhone($call->contact_phone)
                                : null,
                            'email'        => $call->contact_email,
                        ]),

                  'supplier' => $call->supplier ? [
                        'id'                   => $call->supplier->id,
                        'name'                 => $call->supplier->name,
                        'phone'                => $call->supplier->phone
                            ? CustomHelper::formatPhone($call->supplier->phone)
                            : null,
                        'email'                => $call->supplier->email,
                        'primary_contact_name' => $call->supplier->primary_contact_name,
                        'primary_contact_phone'=> $call->supplier->primary_contact_phone
                            ? CustomHelper::formatPhone($call->supplier->primary_contact_phone)
                            : null,
                  ] : null,

                   'creator' => [
    'id' => $call->creator?->id,
    'full_name' => $call->creator?->full_name ?? $call->creator?->name,
],

'assignee' => [
    'id' => $call->assignee?->id,
    'full_name' => $call->assignee?->full_name ?? $call->assignee?->name,
],


'activities' => $call->activities->map(function ($activity) {

    return [
        'id' => $activity->id,
        'status' => $activity->status,
        'notes' => $activity->notes,
        'follow_up_date' => $activity->follow_up_date,
        'created_at' => CustomHelper::formatDateTime(
            $activity->created_at
        ),

        'user' => [
            'id' => $activity->user?->id,
            'full_name' => $activity->user?->full_name
                ?? $activity->user?->name,
        ],
    ];
}),

                ];
            }),
        ]);
    }
}