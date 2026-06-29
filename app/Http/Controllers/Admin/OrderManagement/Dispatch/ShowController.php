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
     * The page shows ALL products in the same order so the driver sees the full truck load.
     * GET  /dispatch/{unique_id}
     */
    public function show(string $unique_id)
    {
        $orderProduct = OrderProduct::with([
            'order',
            'order.customer',
            'order.shippingAddress',
            'order.products',
            'order.products.deliveryEmployee',
            'order.products.deliveryStore',
            'deliveryEmployee',
            'deliveryStore',
        ])->where('unique_id', $unique_id)->firstOrFail();

        $stores    = Store::orderBy('store_name')->get();
        $employees = User::active()->orderBy('first_name')->get();

        // All products in this order — primary product first, then the rest
        $orderProducts = $orderProduct->order?->products
            ->sortByDesc(fn($op) => $op->unique_id === $unique_id)
            ->values()
            ?? collect();

        return view('admin.order_management.dispatch.show', compact(
            'orderProduct',
            'orderProducts',
            'stores',
            'employees',
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
            'sop'                      => 'nullable|array',
            'sop.customer_called'      => 'nullable|boolean',
            'sop.customer_texted'      => 'nullable|boolean',
            'sop.keys'                 => 'nullable|string|in:Full Set,1 Key,N/A',
            'sop.fuel'                 => 'nullable|string|in:Full,3/4,1/2,1/4,Empty',
            'product_options'          => 'nullable|array',
            'related_products'         => 'nullable|array',
            'completed_by'             => 'nullable|integer|exists:users,id',
        ]);

        // Merge with existing so partial saves (e.g. completed_by only) don't wipe other fields
        $existing  = $orderProduct->dispatch_checklist ?? [];
        $checklist = $existing;

        if ($request->has('sop')) {
            $sop = $data['sop'] ?? [];
            $checklist['sop'] = [
                'customer_called' => (bool) ($sop['customer_called'] ?? $existing['sop']['customer_called'] ?? false),
                'customer_texted' => (bool) ($sop['customer_texted'] ?? $existing['sop']['customer_texted'] ?? false),
                'keys'            => $sop['keys'] ?? $existing['sop']['keys'] ?? null,
                'fuel'            => $sop['fuel'] ?? $existing['sop']['fuel'] ?? null,
            ];
        }
        if ($request->has('product_options')) {
            $checklist['product_options'] = array_map('boolval', $data['product_options'] ?? []);
        }
        if ($request->has('related_products')) {
            $checklist['related_products'] = array_map('boolval', $data['related_products'] ?? []);
        }
        if ($request->has('completed_by')) {
            $checklist['completed_by'] = $data['completed_by'] ?? null;
        }
        $checklist['updated_at'] = now()->toISOString();

        $orderProduct->update(['dispatch_checklist' => $checklist]);

        return response()->json(['success' => true, 'checklist' => $checklist]);
    }
}
