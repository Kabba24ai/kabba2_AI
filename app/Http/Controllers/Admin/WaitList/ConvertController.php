<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Http\Controllers\Controller;
use App\Models\WaitList\EquipmentWaitList;
use Illuminate\Http\Request;

class ConvertController extends Controller
{
    /** Staff created a reservation/order manually — link it and close out. */
    public function __invoke(Request $request, EquipmentWaitList $waitList)
    {
        $validated = $request->validate([
            'converted_order_id' => ['required', 'exists:orders,id'],
        ], [
            'converted_order_id.required' => 'Select the reservation/order this wait list converted into.',
        ]);

        $waitList->convert((int) $validated['converted_order_id']);

        flash('Wait list marked Converted and linked to the order.')->success();

        return redirect()->route('admin.wait-list.show', $waitList);
    }
}
