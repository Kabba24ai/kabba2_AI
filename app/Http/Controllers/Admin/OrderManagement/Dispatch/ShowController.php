<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use App\Models\Stores\Store;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    /**
     * Display the dispatch detail / pre-delivery checklist for one order product.
     * GET  /dispatch/{unique_id}
     */
    public function show(string $unique_id)
    {
        $orderProduct = OrderProduct::with([
            'order',
            'order.customer',
            'order.shippingAddress',
            'order.lastPayment',
            'order.products',                   // all products in this order (for the checklist)
            'deliveryEmployee',
            'pickupEmployee',
            'deliveryStore',
            'pickupStore',
        ])->where('unique_id', $unique_id)->firstOrFail();

        $stores    = Store::orderBy('store_name')->get();
        $employees = User::active()->orderBy('first_name')->get();

        // All order products in the same order (for the "products included" checklist)
        $orderProducts = $orderProduct->order?->products ?? collect();

        // Current checklist state (persisted JSON)
        $checklist = $orderProduct->dispatch_checklist ?? [];

        return view('admin.order_management.dispatch.show', compact(
            'orderProduct',
            'orderProducts',
            'stores',
            'employees',
            'checklist',
        ));
    }

    /**
     * Save the dispatch checklist for one order product.
     * POST /dispatch/{unique_id}/checklist
     */
    public function saveChecklist(Request $request, string $unique_id)
    {
        $orderProduct = OrderProduct::where('unique_id', $unique_id)->firstOrFail();

        $data = $request->validate([
            'customer_contact'      => 'nullable|in:spoke,vm,text',
            'options'               => 'nullable|array',
            'options.prepaid_fuel'  => 'nullable|boolean',
            'options.prepaid_cleaning' => 'nullable|boolean',
            'products'              => 'nullable|array',
        ]);

        $checklist = [
            'customer_contact' => $data['customer_contact'] ?? null,
            'options'          => [
                'prepaid_fuel'     => (bool) ($data['options']['prepaid_fuel']     ?? false),
                'prepaid_cleaning' => (bool) ($data['options']['prepaid_cleaning'] ?? false),
            ],
            'products'         => array_map('boolval', $data['products'] ?? []),
            'updated_at'       => now()->toISOString(),
        ];

        $orderProduct->update(['dispatch_checklist' => $checklist]);

        return response()->json(['success' => true, 'checklist' => $checklist]);
    }
}
