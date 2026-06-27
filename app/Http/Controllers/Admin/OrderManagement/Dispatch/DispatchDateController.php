<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAuditLog;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;

class DispatchDateController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $request->validate([
            'type' => 'required|in:delivery,return',
            'date' => 'nullable|date',
        ]);

        $orderProduct = OrderProduct::where('unique_id', $unique_id)->firstOrFail();
        $type    = $request->type;
        $newDate = $request->date ?: null;

        if ($type === 'delivery') {
            $oldDate  = $orderProduct->dispatch_delivery_date;
            $field    = 'dispatch_delivery_date';
            $action   = $newDate ? 'dispatch_delivery_date_changed' : 'dispatch_delivery_date_cleared';
            $orderProduct->dispatch_delivery_date = $newDate;
        } else {
            $oldDate  = $orderProduct->dispatch_return_date;
            $field    = 'dispatch_return_date';
            $action   = $newDate ? 'dispatch_return_date_changed' : 'dispatch_return_date_cleared';
            $orderProduct->dispatch_return_date = $newDate;
        }

        $orderProduct->save();

        DispatchAuditLog::create([
            'order_product_id' => $orderProduct->id,
            'action'           => $action,
            'field'            => $field,
            'old_value'        => $oldDate,
            'new_value'        => $newDate,
            'user_id'          => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }
}
