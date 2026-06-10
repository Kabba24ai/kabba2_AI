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

            $customerId = $request->customer_id;

            $callNeeded = CustomerCallNeeded::create([
                'customer_id'   => $customerId ?: null,

                'contact_name'  => $customerId
                    ? null
                    : $request->contact_name,

                'contact_email' => $customerId
                    ? null
                    : $request->contact_email,

                'contact_phone' => $customerId
                    ? null
                    : $request->contact_phone,

                'reason'      => $request->reason,
                'notes'       => $request->notes,
                'is_urgent'   => $request->boolean('is_urgent'),
                'status'      => 'active',
                'created_by'  => $request->assigned_to,
                'auth_by'     => auth()->id(),
            ]);

            $description = $customerId
                ? 'Call reminder created.'
                : 'Non-customer call reminder created.';

            if ($callNeeded->assignee) {
                $description .= " Assigned to {$callNeeded->assignee->full_name}.";
            }

            $description .= ' Reason: ' .
                ucwords(str_replace('_', ' ', $request->reason)) . '.';

            if ($request->boolean('is_urgent')) {
                $description .= ' Priority: Urgent.';
            }

            if ($request->filled('notes')) {
                $description .= " Notes: {$request->notes}";
            }
            
            if ($callNeeded->customer) {

                $callNeeded->customer->notes()->create([
                    'customer_call_needed_id' => $callNeeded->id,
                    'description'            => $description,
                    'created_by'             => $request->assigned_to,
                ]);
            }

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