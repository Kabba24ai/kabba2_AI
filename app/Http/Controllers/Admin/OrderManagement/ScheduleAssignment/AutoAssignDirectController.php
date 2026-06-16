<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment;

use App\Http\Controllers\Controller;
use App\Models\Orders\OrderProduct;
use App\Services\AutoAssignDirectService;
use Illuminate\Http\Request;

class AutoAssignDirectController extends Controller
{
    public function __invoke(Request $request, AutoAssignDirectService $service)
    {
        if ($request->filled('order_product_id')) {
            $single = OrderProduct::with(['product.categories', 'softAssignment', 'order'])
                ->find((int) $request->order_product_id);

            $orderProducts = $single ? collect([$single]) : collect();
        } else {
            $query = OrderProduct::query()
                ->with(['product.categories', 'softAssignment', 'order'])
                ->where('product_data->product_type', 'Rental')
                ->whereHas('order')
                ->whereNotNull('delivery_date')
                ->whereDoesntHave('softAssignment')
                ->whereDoesntHave('equipment')
                ->where(function ($q) {
                    $q->where('delivery_status', 'Pending')->orWhere('pickup_status', 'Pending');
                })
                ->where('delivery_status', '!=', 'Reschedule')
                ->where('pickup_status', '!=', 'Reschedule');

            if ($request->filled('category')) {
                $query->whereHas('product.categories', function ($q) use ($request) {
                    $q->where('product_categories.id', $request->category);
                });
            }

            $orderProducts = $query->get();
        }

        $assigned = 0;
        $skipped  = 0;
        $details  = [];

        foreach ($orderProducts as $orderProduct) {
            $result = $service->assignSingle($orderProduct);

            $details[] = array_merge([
                'product_name' => $orderProduct->product_name,
                'order'        => $orderProduct->order?->order_number ?? '—',
            ], $result);

            if ($result['status'] === 'assigned') {
                $assigned++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'success'  => true,
            'assigned' => $assigned,
            'skipped'  => $skipped,
            'total'    => $orderProducts->count(),
            'details'  => $details,
        ]);
    }
}
