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
        $query = Order::query()->with('shippingAddress', 'products.product.categories', 'lastPayment');

        if ($request->filled('customer_name')) {
            $query->whereHas('shippingAddress', function ($q) use ($request) {
                $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->customer_name}%"]);
            });
            // $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
        }

        if ($request->filled('customer_company_name')) {
            $query->where('company_name', 'like', '%' . $request->customer_company_name . '%');
            // $query->whereHas('customer', function ($q) use ($request) {
            //     $q->where('company_name', 'like', '%' . $request->customer_company_name . '%');
            // });
        }

        if ($request->filled('customer_phone')) {
            // $query->where('customer_phone', 'like', '%' . $request->customer_phone . '%');
            $query->whereHas('shippingAddress', function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->customer_phone . '%');
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('products.product.categories', function ($q) use ($request) {
                $q->where('product_categories.id', $request->category); // Fully qualified!
            });
        }

        if ($request->filled('payment_method') && $request->payment_method != 'All Methods') {
            $query->whereHas('lastPayment', function ($q) use ($request) {
                $q->where('payment_method', $request->payment_method);
            });
        }

        if ($request->filled('payment_status') && $request->payment_status != 'All Status') {
            $query->whereHas('lastPayment', function ($q) use ($request) {
                $q->where('status', $request->payment_status);
            });
        }

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
            'categories' => $categories
        ]);
    }
}
