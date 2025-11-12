<?php

namespace App\Http\Controllers\Admin\Reports\SalesTax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use Carbon\Carbon;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Helpers\ConfigurationHelper;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        //  Orders Query
        $ordersQuery = Order::with('shippingAddress', 'products.product.categories', 'lastPayment')
            ->whereRelation('lastPayment', 'payment_method', '!=', 'COD')
            ->whereRelation('lastPayment', 'payment_method', '!=', 'Account')
            ->when($request->filled('payment_method') && $request->payment_method !== 'All Methods', fn($q) => $q->whereRelation('lastPayment', 'payment_method', $request->payment_method))
            ->when($request->filled('store'), function ($q) use ($request) {
                $q->whereHas('products', fn($sub) => $sub->where(fn($s) => $s->where('delivery_store_id', $request->store)->orWhere('pickup_store_id', $request->store)));
            })
            ->when($request->filled('month_range'), function ($q) use ($request) {
                [$year, $month] = explode('-', $request->month_range);
                $start = Carbon::create($year, $month, 1)->startOfMonth();
                $end = Carbon::create($year, $month, 1)->endOfMonth();
                $q->whereBetween('order_date', [$start, $end]);
            })
            ->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
                $start = Carbon::parse($request->start_date)->startOfDay();
                $end = Carbon::parse($request->end_date)->endOfDay();
                $q->whereBetween('order_date', [$start, $end]);
            });

        $orders = $ordersQuery->get();

        // Load payment accounts ONLY when a store is NOT selected
        $paymentAccounts = collect(); // default empty collection

        if (empty($request->store)) {
            //  Payment Accounts
            $paymentAccounts = Customer::with([
                'paymentAccounts' => function ($q) use ($request) {
                    if ($request->filled('payment_method') && $request->payment_method !== 'All Methods') {
                        $q->where('payment_type', $request->payment_method);
                    }
                    if ($request->filled('month_range')) {
                        [$year, $month] = explode('-', $request->month_range);
                        $start = Carbon::create($year, $month, 1)->startOfMonth();
                        $end = Carbon::create($year, $month, 1)->endOfMonth();
                        $q->whereBetween('date', [$start, $end]);
                    }
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $start = Carbon::parse($request->start_date)->startOfDay();
                        $end = Carbon::parse($request->end_date)->endOfDay();
                        $q->whereBetween('date', [$start, $end]);
                    }
                },
            ])
                ->get()
                ->pluck('paymentAccounts')
                ->flatten()
                ->map(function ($item) {
                    $item->is_payment_account = true;
                    return $item;
                });
        }

        //  Combine & Paginate
        $combined = $orders->concat($paymentAccounts)->sortByDesc(fn($item) => isset($item->is_payment_account) ? $item->date : $item->order_date)->values();

        //  Combine into unified rows
        $reportRows = $orders
            ->map(function ($order) {
                $productNames = $order->products->pluck('product_name')->toArray();

                $paymentType = $order->last_payment_type?->value;
                if ($paymentType === 'Cheque') {
                    $paymentType = 'Check'; // normalize spelling
                }

                return (object) [
                    'type' => 'order',
                    'unique_id' => $order->unique_id,
                    'link' => $order->view_link,
                    'date' => $order->order_date,
                    'customer_name' => $order->shippingAddress?->full_name ?? '-',
                    'products' => implode(', ', $productNames),
                    'payment_type' => $paymentType ?? '-',
                    'subtotal' => $order->subtotal,
                    'tax_amount' => $order->tax_amount,
                    'discount_amount' => $order->discount_amount,
                    'grand_total' => $order->grand_total,
                ];
            })
            ->concat(
                $paymentAccounts->map(function ($payment) {
                    $amount = $payment->amount ?? 0;
                    $taxAmount = $amount - $amount / (1 + ($payment->sales_tax ?? 0));
                    $subtotal = $amount - $taxAmount;

                    $AccountspaymentType = $payment->payment_type?->value ?? '';
                    if (strtolower($AccountspaymentType) === 'cheque') {
                        $AccountspaymentType = 'Check';
                    }

                    return (object) [
                        'type' => 'payment',
                        'unique_id' => $payment->customer?->unique_id ?? '-',
                        'link' => '<a href="' . route('admin.reports.sales-tax.paymentview', $payment->customer?->unique_id) . '" class="text-brand-500 underline font-bold" target="_blank">View Payment</a>',
                        'date' => $payment->date,
                        'customer_name' => $payment->customer?->full_name ?? '-',
                        'products' => 'Payment Account',
                        'payment_type' => $AccountspaymentType ?? '-',
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'discount_amount' => 0,
                        'grand_total' => $amount,
                    ];
                }),
            )
            ->sortByDesc(fn($row) => $row->date)
            ->values();

        $totalRevenue = CustomHelper::formatCurrency($reportRows->sum(fn($row) => $row->grand_total));

        $taxFreeRevenue = CustomHelper::formatCurrency($reportRows->filter(fn($row) => $row->tax_amount == 0)->sum(fn($row) => $row->subtotal));

        $reversetaxableRevenue = $reportRows->filter(fn($row) => $row->tax_amount > 0)->sum(fn($row) => $row->grand_total);

        $taxableRevenue = $reversetaxableRevenue / (1 + $sales_tax);

        $taxableRevenue = CustomHelper::formatCurrency($taxableRevenue);

        $salesTaxCollected = CustomHelper::formatCurrency($reportRows->sum(fn($row) => $row->tax_amount));

        $reportRows = $reportRows->filter(fn($row) => $row->tax_amount > 0)->values();

        // Pagination
        $perPage = 10;
        $page = $request->get('page', 1);
        $total = $reportRows->count();
        $items = $reportRows->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        //  AJAX response
        if ($request->ajax()) {
            $orders = $paginated;

            $html = view('admin.reports.sales_tax.partials._table', compact('orders'))->render();

            $stats = [
                'totalRevenue' => $totalRevenue,
                'taxFreeRevenue' => $taxFreeRevenue,
                'taxableRevenue' => $taxableRevenue,
                'salesTaxCollected' => $salesTaxCollected,
            ];

            return response()->json(['success' => true, 'html' => $html, 'stats' => $stats]);
        }

        // Normal view
        $availableMonths = Order::selectRaw('YEAR(order_date) as year, MONTH(order_date) as month')->groupBy('year', 'month')->orderByDesc('year')->orderByDesc('month')->limit(12)->get()->map(
            fn($item) => [
                'value' => "{$item->year}-" . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                'label' => 'Pay for ' . Carbon::create($item->year, $item->month, 1)->format('M 1') . ' - ' . Carbon::create($item->year, $item->month, 1)->endOfMonth()->format('M d'),
            ],
        );

        return view('admin.reports.sales_tax.index', [
            'orders' => $paginated,
            'stores' => Store::all(),
            'availableMonths' => $availableMonths,
            'totalRevenue' => $totalRevenue,
            'taxFreeRevenue' => $taxFreeRevenue,
            'taxableRevenue' => $taxableRevenue,
            'salesTaxCollected' => $salesTaxCollected,
        ]);
    }
}
