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
            'customer_id' => $request->customer_id ?: null,
            'contact_name' => $request->customer_id
                ? null
                : $request->contact_name,
            'contact_email' => $request->customer_id
                ? null
                : $request->contact_email,
            'contact_phone' => $request->customer_id
                ? null
                : $request->contact_phone,
            'created_by' => $request->assigned_to,
            'reason' => $request->reason,
            'is_urgent' => $request->boolean('is_urgent'),
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call reminder updated successfully.',
        ]);
    }
}