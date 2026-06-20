<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderExtraCharges;
use App\Events\Admin\Orders\OrderExtraChargeEvent;
use App\Services\ChargeService;

class MarkUncollectibleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $orderProductUniqueId)
    {
        $request->validate([
            'type' => 'required|in:damage,fuel',
        ]);

        $orderProduct = OrderProduct::whereHas('order')
            ->where('id', $orderProductUniqueId)
            ->firstOrFail();

        DB::transaction(function () use ($request, $orderProduct) {

            $order    = $orderProduct->order;
            $employee = auth()->user();

            // Update OP status + sync any linked CA charge records
            ChargeService::markUncollectible(
                $orderProduct,
                $request->type,
                auth()->id() ?? 0
            );

            //  Create OrderExtraCharges record (audit)

$virtualCharge = new OrderExtraCharges([
    'customer_id' => $order->customer_id,
    'order_id' => $order->id,
    'order_product_id' => $orderProduct->id,
    'type' => $request->type,   // damage | fuel
    'amount' => 0,
    'payment_type' => 'uncollectible',
]);

            //  Fire history / activity event
            event(new OrderExtraChargeEvent(
                $order,
                $virtualCharge,
                $employee,
                'uncollectable'
            ));
        });

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->type) . ' payment marked uncollectible',
        ]);
    }
}
