<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;

class ExtraChargesShowController extends Controller
{
    public function __invoke(Request $request, $orderId)
    {
        $order = Order::with([
            'products.checklistQuestions.answers',
            'products.damageChargeLogs',
            'products.equipment',
        ])->findOrFail($orderId);

        /* ==============================
         *  CHECKLIST TABLE
         ==============================*/
        $checklistRows = [];
        $checklistTotal = 0;

        foreach ($order->products as $product) {
            foreach ($product->checklistQuestions()->indexOrder()->get() as $question) {

                $valid = $question->answers
                    ->filter(fn ($a) => $a->is_delivery_answer || $a->is_return_answer)
                    ->sortByDesc('index_number')
                    ->first();

                $latest = $valid ?? $question->answers->sortByDesc('index_number')->first();

                if (!$latest) {
                    continue;
                }

                // SAME logic as Blade: Return-only creates charge
                if ($latest->is_return_answer && !$latest->is_delivery_answer) {

                    $deliveryAmount =
                        $question->deliverySelectedAnswer->delivery_amount ?? 0;

                    $returnAmount =
                        $latest->user_return_amount
                        ?? ($latest->return_amount ?? 0);

                    $amount = max($returnAmount - $deliveryAmount, 0);

                    if ($amount > 0) {
                        $checklistRows[] = [
                            'item'      => $question->question_name,
                            'delivered' => $question->deliverySelectedAnswer->delivery_answer
                                            ?? 'Admin Override',
                            'returned'  => $question->returnSelectedAnswer->return_answer ?? '-',
                            'amount'    => $amount,
                        ];

                        $checklistTotal += $amount;
                    }
                }
            }
        }

        /* ==============================
         *  DAMAGE SUMMARY
         ==============================*/
        $damageBaseTotal = 0;
        $damageAdjustmentTotal = 0;

        foreach ($order->products as $product) {
            $damageBaseTotal += (float) ($product->damage_charge ?? 0);
            $damageAdjustmentTotal +=
                $product->damageChargeLogs?->sum('change_amount') ?? 0;
        }

        $finalDamageTotal = max(0, $damageBaseTotal + $damageAdjustmentTotal);

        /* ==============================
         *  HOUR TRACKING TABLE
         ==============================*/
        $hourRows = [];
        $hourTrackingTotal = 0;

        foreach ($order->products as $product) {

            if (($product->equipment?->is_tracked ?? 'No') !== 'Yes') {
                continue;
            }

            $start = (float) $product->start_hours;
            $end   = (float) $product->end_hours;
            $alloc = (float) $product->allocated_hours;
            $rate  = (float) $product->hour_rate;

            $used       = max(0, $end - $start);
            $additional = max(0, $used - $alloc);
            $charge     = $additional * $rate;

            $hourTrackingTotal += $charge;

            $hourRows[] = [
                'product'          => $product->product_name,
                'start_hours'      => $start,
                'end_hours'        => $end,
                'allocated_hours'  => $alloc,
                'additional_hours' => $additional,
                'rate'             => $rate,
                'charge'           => $charge,
                'total'            => $charge,
            ];
        }

        /* ==============================
         *  GRAND TOTAL (NO FUEL)
         ==============================*/
        $grandTotal =
            $checklistTotal +
            $hourTrackingTotal +
            $finalDamageTotal;

        return response()->json([
            'success' => true,
            'order_id' => $order->id,

            'checklist' => [
                'rows'  => $checklistRows,
                'total' => $checklistTotal,
            ],

            'damage' => [
                'base'       => $damageBaseTotal,
                'adjustment' => $damageAdjustmentTotal,
                'final'      => $finalDamageTotal,
            ],

            'hour_tracking' => [
                'rows'  => $hourRows,
                'total' => $hourTrackingTotal,
            ],

            'grand_total' => $grandTotal,
        ]);
    }
}
