<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerCallNeeded;
use App\Http\Requests\Admin\Dashboard\CallNeededStoreRequest;

class CallNeededUpdateController extends Controller
{
    public function __invoke(
        CallNeededStoreRequest $request,
        CustomerCallNeeded $id
    ) {
         $id->update([
        'customer_id' => $request->customer_id,
        'created_by'  => $request->assigned_to, // Assignee
        'reason'      => $request->reason,
          'is_urgent'   => $request->boolean('is_urgent'),
        'notes'       => $request->notes,
    ]);

        return response()->json([
            'success' => true,
            'message' => 'Call reminder updated successfully.',
        ]);
    }
}