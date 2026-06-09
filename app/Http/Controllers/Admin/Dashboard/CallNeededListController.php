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
            'creator',
            'assignee',
        ])
        ->where('status', 'active')
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
                    'is_urgent' => (bool) $call->is_urgent,
                    'created_at' => CustomHelper::formatDateTime($call->created_at),

                   'customer' => [
                            'id' => $call->customer->id,
                            'full_name' => $call->customer->full_name,
                            'company_name' => $call->customer->company_name,
                            'phone' => $call->customer->phone
                    ? CustomHelper::formatPhone($call->customer->phone)
                    : null,
                            'email' => $call->customer->email,
                        ],

                   'creator' => [
    'id' => $call->creator?->id,
    'full_name' => $call->creator?->full_name ?? $call->creator?->name,
],

'assignee' => [
    'id' => $call->assignee?->id,
    'full_name' => $call->assignee?->full_name ?? $call->assignee?->name,
],
                ];
            }),
        ]);
    }
}