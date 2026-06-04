<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerCallNeeded;

class CallNeededClearController extends Controller
{
    public function __invoke($id)
    {
        $call = CustomerCallNeeded::findOrFail($id);

        $call->update([
            'status' => 'clear',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call reminder cleared successfully.',
        ]);
    }
}