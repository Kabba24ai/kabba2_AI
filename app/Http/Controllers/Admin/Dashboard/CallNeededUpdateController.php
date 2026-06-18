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
        $isManual = !$request->customer_id && !$request->supplier_id;

        $id->update([
            'customer_id'   => $request->customer_id ?: null,
            'supplier_id'   => $request->supplier_id  ?: null,
            'contact_name'  => $isManual ? $request->contact_name  : null,
            'contact_email' => $isManual ? $request->contact_email : null,
            'contact_phone' => $isManual ? $request->contact_phone : null,
            'created_by'    => $request->assigned_to,
            'reason'        => $request->reason,
            'is_urgent'     => $request->boolean('is_urgent'),
            'notes'         => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call reminder updated successfully.',
        ]);
    }
}