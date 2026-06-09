<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerCallNeeded;
use App\Http\Requests\Admin\Dashboard\CallNeededStoreRequest;
use App\Models\Customers\Customer;

class CallNeededStoreController extends Controller
{
    public function __invoke(CallNeededStoreRequest $request)
    {
        try {

            $callNeeded = CustomerCallNeeded::create([
                'customer_id' => $request->customer_id,
                'reason'      => $request->reason,
                'notes'       => $request->notes,
                'is_urgent'   => $request->boolean('is_urgent'),
                'status'      => 'active',
                'created_by'  => $request->assigned_to,
                'auth_by' => auth()->id(),
            ]);


            $customer = Customer::find($request->customer_id);

            $description = "Call reminder created.";

            if ($callNeeded->assignee) {
                $description .= " Assigned to {$callNeeded->assignee->full_name}.";
            }

            $description .= " Reason: " . ucwords(str_replace('_', ' ', $request->reason)) . ".";

            if ($request->boolean('is_urgent')) {
                $description .= " Priority: Urgent.";
            }

            if ($request->filled('notes')) {
                $description .= " Notes: {$request->notes}";
            }

            $callNeeded->customer->notes()->create([
                'customer_call_needed_id' => $callNeeded->id,
                'description' => $description,
                'created_by' => $request->assigned_to,
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