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
 * GET service-management/tickets/order-search?search=&by=any|order|customer
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
        // 'any' (the consolidated selector) matches order OR customer; the older
        // 'order' / 'customer' scopes are still honored for back-compat.
        $by = in_array($request->query('by'), ['order', 'customer'], true)
            ? $request->query('by')
            : 'any';

        // Same 2-char floor the client enforces — avoids scanning on a keystroke.
        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $matchesOrder    = $by === 'order' || $by === 'any';
        $matchesCustomer = $by === 'customer' || $by === 'any';
        // The consolidated selector (by=any — what both intakes use) also matches
        // the order's product/equipment names, mirroring the Field Service search
        // and the shared ServiceOrderLabel it displays.
        $matchesEquipment = $by === 'any';

        $orders = Order::with(ServiceIntakeOrderPresenter::relations())
            ->where(function ($q) use ($term, $matchesOrder, $matchesCustomer, $matchesEquipment) {
                if ($matchesOrder) {
                    $q->orWhere('order_number', 'like', "%{$term}%")
                        ->orWhere('reference_order_number', 'like', "%{$term}%");
                }
                if ($matchesCustomer) {
                    $q->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhereHas('billingAddress', function ($b) use ($term) {
                            $b->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$term}%"]);
                        });
                }
                if ($matchesEquipment) {
                    // EXISTS subquery — a multi-product order still returns once.
                    $q->orWhereHas('products', function ($p) use ($term) {
                        $p->where('product_name', 'like', "%{$term}%");
                    });
                }
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
