<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Enums\Orders\OrderPaymentStatus;
use Carbon\Carbon;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Facades\Log;
use App\Models\MaintenanceManagement\Equipment;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;

// Events
use App\Events\Admin\Orders\OrderNoteEvent;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\Notes\PostRequest;
use App\Models\Orders\OrderProductDamageChargeLog;
use App\Helpers\CustomHelper;


use Illuminate\Support\Facades\DB;

class AmountUpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $unique_id)
{
    $request->validate([
        'amount' => 'required|numeric',
        'type'   => 'required|string', // damage / fuel
    ]);

    $orderProduct = OrderProduct::where('unique_id', $unique_id)->firstOrFail();

        if ($request->type == 'damage') {

            DB::transaction(function () use ($orderProduct, $request) {

                $base = (float) $orderProduct->damage_charge;

                $adjustments = $orderProduct
                    ->damageChargeLogs()
                    ->sum('change_amount');

                $before = $base + $adjustments;

                //  CHANGE is ALWAYS relative
                $change = (float) $request->amount;

                $after = max(0, $before + $change);

                $action =
                    $change > 0 ? 'add' :
                    ($change < 0 ? 'subtract' : 'review');

                OrderProductDamageChargeLog::create([
                    'order_product_id' => $orderProduct->id,
                    'before_amount'    => $before,
                    'change_amount'    => $change,
                    'after_amount'     => $after,
                    'action'           => $action,
                    'user_id'          => auth()->id(),
                    'note'             => $request->note,
                ]);
            });
        }

    return response()->json([
        'success' => true,
        'message' => 'Damage charge updated',
    ]);
}


}
