<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerCallNeeded;

class CallNeededShowController extends Controller
{
    public function __invoke($id)
    {
        $call = CustomerCallNeeded::with([
            'customer',
            'supplier',
            'creator',
            'assignee',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $call,
        ]);
    }
}