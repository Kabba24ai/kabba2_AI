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
