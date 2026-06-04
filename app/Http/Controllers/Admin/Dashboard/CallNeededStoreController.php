<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerCallNeeded;
use App\Http\Requests\Admin\Dashboard\CallNeededStoreRequest;

class CallNeededStoreController extends Controller
{
    public function __invoke(CallNeededStoreRequest $request)
    {
        try {

            $callNeeded = CustomerCallNeeded::create([
                'customer_id' => $request->customer_id,
                'reason'      => $request->reason,
                'notes'       => $request->notes,
                'status'      => 'active',
                'created_by'  => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Call reminder created successfully.',
                'data'    => $callNeeded,
            ]);

        } catch (\Exception $e) {

            \Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create call reminder.',
            ], 500);
        }
    }
}