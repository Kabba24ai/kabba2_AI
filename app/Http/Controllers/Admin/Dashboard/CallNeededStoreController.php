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

            $customerId  = $request->customer_id;
            $supplierId  = $request->supplier_id;
            $orderId     = $request->order_id;

            // Order-linked call: the order's customer is authoritative. A
            // mismatched customer_id from the browser is corrected here; an
            // order with no customer link keeps whatever was submitted.
            if ($orderId) {
                $orderCustomerId = \App\Models\Orders\Order::whereKey($orderId)->value('customer_id');
                if ($orderCustomerId !== null) {
                    $customerId = $orderCustomerId;
                }
            }

            $isManual    = !$customerId && !$supplierId;

            $callNeeded = CustomerCallNeeded::create([
                'customer_id'   => $customerId ?: null,
                'supplier_id'   => $supplierId  ?: null,
                'order_id'      => $orderId    ?: null,

                'contact_name'  => $isManual ? $request->contact_name  : null,
                'contact_email' => $isManual ? $request->contact_email : null,
                'contact_phone' => $isManual ? $request->contact_phone : null,

                'reason'      => $request->reason,
                'category'    => $request->category ?: null,
                'notes'       => $request->notes,
                'priority'    => $request->input('priority', 'normal'),
                'due_date'    => $request->due_date ?: null,
                'status'      => 'active',
                'created_by'  => $request->assigned_to,
                'auth_by'     => auth()->id(),
            ]);

            $description = $customerId
                ? 'Call reminder created.'
                : ($supplierId ? 'Supplier call reminder created.' : 'Non-customer call reminder created.');

            if ($callNeeded->assignee) {
                $description .= " Assigned to {$callNeeded->assignee->full_name}.";
            }

            $description .= ' Reason: ' .
                ucwords(str_replace('_', ' ', $request->reason)) . '.';

            $priority = $request->input('priority', 'normal');
            if ($priority !== 'normal') {
                $description .= ' Priority: ' . ucfirst($priority) . '.';
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