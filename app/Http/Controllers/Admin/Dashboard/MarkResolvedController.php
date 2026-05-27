<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderExtraCharges;
use App\Events\Admin\Orders\OrderExtraChargeEvent;

class MarkResolvedController extends Controller
{
    /**
     * Mark a fuel or damage alert as resolved, storing the resolution
     * note and the person responsible for the resolution.
     */
    public function __invoke(Request $request, $orderProductId)
    {
        $request->validate([
            'type'            => 'required|in:damage,fuel',
            'resolution_note' => 'required|string|max:1000',
            'resolved_by'     => 'required|exists:users,id',
        ]);

        $orderProduct = OrderProduct::whereHas('order')
            ->where('id', $orderProductId)
            ->firstOrFail();

        DB::transaction(function () use ($request, $orderProduct) {

            $order    = $orderProduct->order;
            $employee = auth()->user();

            // Update the product status to resolved
            if ($request->type === 'damage') {
                $orderProduct->damage_status = 'resolved';
            }

            if ($request->type === 'fuel') {
                $orderProduct->fuel_charge_status = 'resolved';
            }

            $orderProduct->save();

            // Persist an audit record capturing the note and resolver
            $charge = OrderExtraCharges::create([
                'customer_id'           => $order->customer_id,
                'order_id'              => $order->id,
                'order_product_id'      => $orderProduct->id,
                'type'                  => $request->type,
                'amount'                => 0,
                'payment_type'          => 'resolved',
                'notes'                 => $request->resolution_note,
                'responsible_person_id' => $request->resolved_by,
            ]);

            // Fire history / activity event
            event(new OrderExtraChargeEvent(
                $order,
                $charge,
                $employee,
                'resolved'
            ));
        });

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->type) . ' charge marked as resolved',
        ]);
    }
}
