<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\ServiceManagement\ServiceIntakeOrderPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer-Related Ticket — live order search.
 *
 * GET service-management/tickets/order-search?search=&by=order|customer
 *
 * Searches ALL orders server-side (like the Orders page) instead of filtering a
 * capped client-side preload. This is what lets an order whose equipment is
 * soft-assigned through the Queue Line — invisible to the old hard-FK preload —
 * be found and serviced. Each result carries its serviceable units (resolved
 * from hard + soft assignment via ServiceIntakeOrderPresenter), so selecting an
 * order drives the equipment and complaint pickers exactly as before.
 */
class OrderSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('search', ''));
        $by   = $request->query('by') === 'customer' ? 'customer' : 'order';

        // Same 2-char floor the client enforces — avoids scanning on a keystroke.
        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $orders = Order::with(ServiceIntakeOrderPresenter::relations())
            ->when($by === 'order', function ($q) use ($term) {
                $q->where(function ($s) use ($term) {
                    $s->where('order_number', 'like', "%{$term}%")
                        ->orWhere('reference_order_number', 'like', "%{$term}%");
                });
            })
            ->when($by === 'customer', function ($q) use ($term) {
                $q->where(function ($s) use ($term) {
                    $s->where('customer_name', 'like', "%{$term}%")
                        ->orWhereHas('billingAddress', function ($b) use ($term) {
                            $b->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$term}%"]);
                        });
                });
            })
            ->latest('id')
            ->limit(25)
            ->get();

        $data = $orders
            ->map(fn (Order $order) => ServiceIntakeOrderPresenter::present($order))
            ->filter() // drop orders with no serviceable unit (e.g. extension charges)
            ->values();

        return response()->json(['data' => $data]);
    }
}
