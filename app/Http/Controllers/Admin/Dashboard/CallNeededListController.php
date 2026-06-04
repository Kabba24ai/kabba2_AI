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
                    'created_at' => CustomHelper::formatDateTime($call->created_at),

                    'customer' => [
                        'id' => $call->customer->id,
                        'full_name' => $call->customer->full_name,
                    ],
                ];
            }),
        ]);
    }
}