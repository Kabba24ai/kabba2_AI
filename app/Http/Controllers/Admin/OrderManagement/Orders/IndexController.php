<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Orders\Order;
use App\Models\ProductManagement\Product;
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

        // Return only the table partial if it's an AJAX request
        if ($request->ajax()) {
            // Fetch orders from the database, most recent first
            $query = Order::query()
                ->with('shippingAddress','billingAddress', 'products.product.categories', 'lastPayment', 'products.deliverySignatureMedia', 'products.returnSignatureMedia', 'products.equipment', 'products.softAssignment.equipment')->withCount('notes')
                ->when($request->filled('customer_name'), function ($q) use ($request) {
                    $name = trim($request->customer_name);
                    $q->whereHas('billingAddress', function ($s) use ($name) {
                        $s->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$name}%"]);
                    });
                })

                ->when($request->filled('customer_company_name'), function ($q) use ($request) {
                    $q->where('company_name', 'like', "%{$request->customer_company_name}%");
                    // or: whereHas('customer', fn ($s) => $s->where('company_name','like',"%{$request->customer_company_name}%"))
                })

                ->when($request->filled('customer_phone'), function ($q) use ($request) {
                    $q->whereHas('billingAddress', function ($s) use ($request) {
                        $s->where('phone', 'like', "%{$request->customer_phone}%");
                    });
                })

                ->when($request->filled('order_number'), function ($q) use ($request) {
                    $q->where('order_number', 'like', "%{$request->order_number}%")
                    ->orWhere('reference_order_number', 'like', "%{$request->order_number}%");
                })

                // Category / Product / Equipment-ID filters are parent-aware:
                // extension child orders ("3153-A") own no order_products rows,
                // so they also qualify when their PARENT order matches. The
                // extensionChildren scope keeps reorders out of the parent-match
                // branch (they carry reference_order_number but have their own
                // products and must keep matching on those alone).
                ->when($request->filled('category'), function ($q) use ($request) {
                    $categoryMatch = function ($s) use ($request) {
                        $s->where('product_categories.id', $request->category); // fully qualified
                    };
                    $q->where(function ($outer) use ($categoryMatch) {
                        $outer->whereHas('products.product.categories', $categoryMatch)
                            ->orWhere(function ($ext) use ($categoryMatch) {
                                $ext->extensionChildren()
                                    ->whereHas('referenceOrder.products.product.categories', $categoryMatch);
                            });
                    });
                })

                ->when($request->filled('product'), function ($q) use ($request) {
                    $productMatch = function ($s) use ($request) {
                        $s->where('product_id', $request->product); // fully qualified
                    };
                    $q->where(function ($outer) use ($productMatch) {
                        $outer->whereHas('products', $productMatch)
                            ->orWhere(function ($ext) use ($productMatch) {
                                $ext->extensionChildren()
                                    ->whereHas('referenceOrder.products', $productMatch);
                            });
                    });
                })

                ->when($request->filled('payment_method') && $request->payment_method !== 'All Methods', function ($q) use ($request) {
                    // if lastPayment is a latest-of-many relation:
                    $q->whereRelation('lastPayment', 'payment_method', $request->payment_method);
                })

                ->when($request->filled('payment_status') && $request->payment_status !== 'All Status', function ($q) use ($request) {
                    // only match the latest payment's status
                    $q->whereRelation('lastPayment', 'status', $request->payment_status);
                })

                ->when($request->filled('equipment_id_search'), function ($q) use ($request) {
                    $search = trim($request->equipment_id_search);
                    $equipmentMatch = function ($s) use ($search) {
                        $s->where(function ($sub) use ($search) {
                            // Primary: equipment_details JSON (source of truth for table display)
                            $sub->where('equipment_details->equipment_id', 'like', "%{$search}%")
                            // Hard assignment FK → equipment.equipment_id readable string
                            ->orWhereHas('equipment', function ($eq) use ($search) {
                                $eq->where('equipment_id', 'like', "%{$search}%");
                            })
                            // Soft assignment
                            ->orWhereHas('softAssignment.equipment', function ($eq) use ($search) {
                                $eq->where('equipment_id', 'like', "%{$search}%");
                            });
                        });
                    };
                    $q->where(function ($outer) use ($equipmentMatch) {
                        $outer->whereHas('products', $equipmentMatch)
                            ->orWhere(function ($ext) use ($equipmentMatch) {
                                $ext->extensionChildren()
                                    ->whereHas('referenceOrder.products', $equipmentMatch);
                            });
                    });
                })

                ->when($request->filled('order_type') && in_array($request->order_type, ['Rental', 'Retail']), function ($q) use ($request) {
                    $q->whereHas('products.product', function ($s) use ($request) {
                        $s->where('product_type', $request->order_type);
                    });
                });

            $perPage = $request->input('per_page', 30);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $orders = $query->latest('id')->paginate($perPageVal)->withQueryString();

            $html = view('admin.order_management.orders.partials._table', compact('orders'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        }

        $categories = ProductCategory::getHierarchy();

        $products = Product::order()->pluck('product_name', 'id');

        return view('admin.order_management.orders.index', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
