<?php

namespace App\Http\Controllers\Admin\Reports\SalesTax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use Carbon\Carbon;
use App\Helpers\CustomHelper ;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
            $query = Order::query()
            ->with('shippingAddress', 'products.product.categories', 'lastPayment', 'products.deliverySignatureMedia', 'products.returnSignatureMedia')

            // Exclude COD payments
            ->whereRelation('lastPayment', 'payment_method', '!=', 'COD')

            // Exclude Account payments
            ->whereRelation('lastPayment', 'payment_method', '!=', 'Account')


            ->when(
                $request->filled('payment_method') && $request->payment_method !== 'All Methods',
                fn($q) =>
                $q->whereRelation('lastPayment', 'payment_method', $request->payment_method)
            )
            
            // Filter by store (via related OrderProducts)
            ->when($request->filled('store'), function ($q) use ($request) {
                $q->whereHas('products', function ($sub) use ($request) {
                    $sub->where(function ($s) use ($request) {
                        $s->where('delivery_store_id', $request->store)
                            ->orWhere('pickup_store_id', $request->store);
                    });
                });
            })


            //  Filter by month_range
            ->when($request->filled('month_range'), function ($q) use ($request) {
                [$year, $month] = explode('-', $request->month_range);
                $start = Carbon::create($year, $month, 1)->startOfMonth();
                $end = Carbon::create($year, $month, 1)->endOfMonth();
                $q->whereBetween('order_date', [$start, $end]);
            })

            //  Filter by date range
            ->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
                $start = Carbon::parse($request->start_date)->startOfDay();
                $end = Carbon::parse($request->end_date)->endOfDay();
                $q->whereBetween('order_date', [$start, $end]);
            });

        $statsQuery = (clone $query); // preserves filters

        $totalRevenue = CustomHelper::formatCurrency($statsQuery->sum('grand_total'));

        // Tax Free Revenue: orders with tax_amount = 0
        $taxFreeRevenue = CustomHelper::formatCurrency(
            (clone $statsQuery)->where('tax_amount', 0)->sum('grand_total')
        );

        // Taxable Revenue: orders with tax_amount > 0
        $taxableRevenue = CustomHelper::formatCurrency(
            (clone $statsQuery)->where('tax_amount', '>', 0)->sum('grand_total')
        );

        // Sales Tax Collected
        $salesTaxCollected = CustomHelper::formatCurrency(
            (clone $statsQuery)->sum('tax_amount')
        );




        $orders = $query->latest('id')->paginate(10)->withQueryString();

            $availableMonths = Order::selectRaw('YEAR(order_date) as year, MONTH(order_date) as month')
                ->groupBy('year', 'month')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->limit(12)
                ->get()
                ->map(function ($item) {
                    $start = Carbon::create($item->year, $item->month, 1)->format('M 1');
                    $end = Carbon::create($item->year, $item->month, 1)->endOfMonth()->format('M d');
                    return [
                        'value' => "{$item->year}-" . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                        'label' => "Pay for {$start} - {$end}",
                    ];
                });

        if ($request->ajax()) {
            $html = view('admin.reports.sales_tax.partials._table', compact('orders'))->render();

            // Include stats
            $stats = [
                'totalRevenue' => $totalRevenue,
                'taxFreeRevenue' => $taxFreeRevenue,
                'taxableRevenue' => $taxableRevenue,
                'salesTaxCollected' => $salesTaxCollected,
            ];

            return response()->json(['success' => true, 'html' => $html, 'stats' => $stats]);
        }

        $stores = Store::all();

        return view('admin.reports.sales_tax.index', [
            'orders' => $orders,
            'stores' => $stores,
            'availableMonths' => $availableMonths,
            'totalRevenue' => $totalRevenue,
            'taxFreeRevenue' => $taxFreeRevenue,
            'taxableRevenue' => $taxableRevenue,
            'salesTaxCollected' => $salesTaxCollected,
        ]);
    }
}
