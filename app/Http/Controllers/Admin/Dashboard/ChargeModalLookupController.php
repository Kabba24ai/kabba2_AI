<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use Illuminate\Http\Request;

/**
 * Shared New Fuel Charge modal — read-only typeahead lookups for the
 * order and customer selectors. Deliberately small result sets with just
 * enough context to distinguish records; no writes, no side effects.
 */
class ChargeModalLookupController extends Controller
{
    private const LIMIT = 8;

    /** Upper bound on the dependent Customer → Order dropdown (active + recent history). */
    private const CUSTOMER_ORDER_LIMIT = 100;

    public function orders(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $orders = Order::query()
            ->with('customer:id,first_name,last_name')
            ->where(function ($query) use ($q) {
                $query->where('order_number', 'like', "%{$q}%")
                    ->orWhere('unique_id', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->whereRaw(
                        "CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$q}%"]
                    ));
            })
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();

        return response()->json([
            'results' => $orders->map(fn (Order $order) => [
                'id'            => $order->id,
                'order_number'  => $order->order_number,
                'customer_id'   => $order->customer_id,
                'customer_name' => $order->customer?->full_name ?? $order->customer_name ?? '—',
                'order_date'    => optional($order->order_date ?? $order->created_at)->format('M j, Y'),
                'status'        => $order->status,
            ])->values(),
        ]);
    }

    /**
     * All orders for a single customer, for the dependent "Customer Order"
     * dropdown in the New Task modal. Newest first (there is no single order
     * status column to group "active" by — overall order state is derived from
     * line-item delivery/pickup statuses, which is not cheaply sortable here).
     * Each row carries the first product name and a count of any additional
     * products so the option stays readable ("#3151 — Mini Excavator +2 more").
     */
    public function customerOrders(Request $request)
    {
        $customerId = (int) $request->input('customer_id', 0);

        if ($customerId < 1) {
            return response()->json(['results' => []]);
        }

        $orders = Order::query()
            ->where('customer_id', $customerId)
            ->with([
                'customer:id,first_name,last_name',
                'products' => fn ($q) => $q->orderBy('id')->select('id', 'order_id', 'product_name'),
            ])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(self::CUSTOMER_ORDER_LIMIT)
            ->get();

        return response()->json([
            'results' => $orders->map(function (Order $order) {
                $products   = $order->products;
                $firstName  = optional($products->first())->product_name ?? 'Order';
                $extraCount = max(0, $products->count() - 1);
                $rawDate    = $order->order_date ?? $order->created_at;

                return [
                    'id'            => $order->id,
                    'order_number'  => $order->order_number,
                    'customer_id'   => $order->customer_id,
                    'customer_name' => $order->customer?->full_name ?? $order->customer_name ?? '—',
                    'first_product' => $firstName,
                    'extra_count'   => $extraCount,
                    'order_date'    => $rawDate ? \Illuminate\Support\Carbon::parse($rawDate)->format('M j, Y') : null,
                ];
            })->values(),
        ]);
    }

    public function customers(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $customers = Customer::query()
            ->whereIn('status', ['Active', 'Archived'])
            ->where(function ($query) use ($q) {
                $query->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$q}%"])
                    ->orWhere('company_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('first_name')
            ->limit(self::LIMIT)
            ->get(['id', 'first_name', 'last_name', 'company_name', 'phone']);

        return response()->json([
            'results' => $customers->map(fn (Customer $customer) => [
                'id'      => $customer->id,
                'name'    => $customer->full_name,
                'company' => $customer->company_name,
                'phone'   => $customer->phone,
            ])->values(),
        ]);
    }
}
