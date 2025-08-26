<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Orders\Order;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Fetch orders from the database, most recent first
        $query = Order::query()
            ->with('shippingAddress', 'products.product.categories', 'lastPayment')
            ->when($request->filled('customer_name'), function ($q) use ($request) {
                $name = trim($request->customer_name);
                $q->whereHas('shippingAddress', function ($s) use ($name) {
                    $s->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$name}%"]);
                });
            })

            ->when($request->filled('customer_company_name'), function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->customer_company_name}%");
                // or: whereHas('customer', fn ($s) => $s->where('company_name','like',"%{$request->customer_company_name}%"))
            })

            ->when($request->filled('customer_phone'), function ($q) use ($request) {
                $q->whereHas('shippingAddress', function ($s) use ($request) {
                    $s->where('phone', 'like', "%{$request->customer_phone}%");
                });
            })

            ->when($request->filled('category'), function ($q) use ($request) {
                $q->whereHas('products.product.categories', function ($s) use ($request) {
                    $s->where('product_categories.id', $request->category); // fully qualified
                });
            })

            ->when($request->filled('payment_method') && $request->payment_method !== 'All Methods', function ($q) use ($request) {
                // if lastPayment is a latest-of-many relation:
                $q->whereRelation('lastPayment', 'payment_method', $request->payment_method);
            })

            ->when($request->filled('payment_status') && $request->payment_status !== 'All Status', function ($q) use ($request) {
                // only match the latest payment's status
                $q->whereRelation('lastPayment', 'status', $request->payment_status);
            });

        $orders = $query->latest('id')->paginate(10)->withQueryString(); // keeps filters in pagination links

        // Return only the table partial if it's an AJAX request
        if ($request->ajax()) {
            $html = view('admin.order_management.orders.partials._table', compact('orders'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        }

        $categories = ProductCategory::getHierarchy();

        return view('admin.order_management.orders.index', [
            'orders' => $orders,
            'categories' => $categories,
        ]);
    }
}
