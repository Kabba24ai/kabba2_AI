<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Enums\WaitList\WaitListReason;
use App\Enums\WaitList\WaitListRequestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WaitList\SaveWaitListRequest;
use App\Models\Customers\Customer;
use App\Models\WaitList\EquipmentWaitList;

class StoreController extends Controller
{
    public function __invoke(SaveWaitListRequest $request)
    {
        $validated = $request->validated();
        $customer  = Customer::findOrFail($validated['customer_id']);

        $waitList = EquipmentWaitList::create([
            'customer_id'         => $customer->id,
            // Display snapshot pulled from the CRM record
            'customer_name'       => trim($customer->first_name . ' ' . $customer->last_name),
            'company_name'        => $customer->company_name,
            'phone'               => $customer->phone ?: $customer->company_phone,
            'email'               => $customer->email,
            'request_type'        => $validated['request_type'],
            'product_category_id' => $validated['request_type'] === WaitListRequestType::Category->value
                ? $validated['product_category_id'] : null,
            'store_preference'    => $validated['store_preference'],
            'store_id'            => $validated['store_id'] ?? null,
            // Form submits the reason code; the readable label is what gets stored
            'reason'              => WaitListReason::from($validated['reason'])->label(),
            'internal_notes'      => $validated['internal_notes'] ?? null,
            'priority_override'   => $validated['priority_override'] ?? null,
            'created_by'          => auth()->id(),
        ]);

        if ($validated['request_type'] === WaitListRequestType::SpecificEquipment->value) {
            foreach (array_unique($validated['equipment_ids']) as $equipmentId) {
                $waitList->items()->create(['equipment_id' => $equipmentId]);
            }
        }

        flash('Wait list record created for ' . $waitList->customer_name . '.')->success();

        return redirect()->route('admin.wait-list.show', $waitList);
    }
}
