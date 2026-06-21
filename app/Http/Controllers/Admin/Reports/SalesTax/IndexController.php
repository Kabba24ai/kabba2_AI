<?php

namespace App\Http\Controllers\Admin\Reports\SalesTax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Orders\OrderExtraCharges;

use App\Models\Stores\Store;
use Carbon\Carbon;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;

use App\Helpers\CustomHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Helpers\ConfigurationHelper;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

    try {
           

            $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

         
                $ordersQuery = Order::query()
    ->select([
        'id',
        'unique_id',
        'order_number',
        'order_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
    ])
    ->with([
        'shippingAddress:id,order_id,first_name,last_name',  
        'products:id,order_id,product_name',
        'payments',
        'lastPayment'
    ])->whereHas('lastPayment', function ($q) {
                    $q->where('payment_method', '!=', 'COD')
                    ->orWhere(function ($q) {
                        $q->where('payment_method', 'COD')
                            ->where('status', 'Paid');
                    });
                })
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
          
                $paymentAccounts = CustomerAccount::query()
                    ->with('customer')
                    ->where('type', 'payment')

                    ->when(
                        $request->filled('payment_method')
                        && $request->payment_method !== 'All Methods',
                        fn($q) => $q->where('payment_type', $request->payment_method)
                    )

                    ->when($request->filled('month_range'), function ($q) use ($request) {

                        [$year, $month] = explode('-', $request->month_range);

                        $start = Carbon::create($year, $month, 1)->startOfMonth();
                        $end = Carbon::create($year, $month, 1)->endOfMonth();

                        $q->whereBetween('date', [$start, $end]);
                    })

                    ->when(
                        $request->filled('start_date')
                        && $request->filled('end_date'),
                        function ($q) use ($request) {

                            $start = Carbon::parse($request->start_date)->startOfDay();
                            $end = Carbon::parse($request->end_date)->endOfDay();

                            $q->whereBetween('date', [$start, $end]);
                        }
                    )

                    ->get()

                    ->map(function ($item) {
                        $item->is_payment_account = true;
                        return $item;
                    });


            } else {
                $paymentAccounts = collect();
            }

            //  Combine & Paginate
            $combined = $orders->concat($paymentAccounts)->sortByDesc(fn($item) => isset($item->is_payment_account) ? $item->date : $item->order_date)->values();
         

            $orderRefundedPayments = $orders->flatMap(function ($order) {

                return $order->payments
                    ->filter(function ($payment) {

                        return in_array(
                            $payment->status?->value ?? $payment->status,
                            [
                                'Refunded',
                                'Partial Refund',
                            ]
                        );

                    })
                    ->map(function ($payment) use ($order) {

                        $refundAmount = (float) $payment->refund_amount;

                        // Prefer stored tax_refunded (accurate) over the estimated helper.
                        $refundTax = ((float) ($payment->tax_refunded ?? 0) > 0)
                            ? (float) $payment->tax_refunded
                            : CustomHelper::calculateRefundSalesTax(
                                $refundAmount,
                                $order->subtotal,
                                $order->tax_amount
                            );

                        $refundSubtotal = $refundAmount - $refundTax;

                        // Refund transaction date — must reflect WHEN the refund was processed,
                        // not the original order date (transaction-date accounting).
                        $refundDate = $payment->refunded_at
                            ?? $payment->payment_datetime
                            ?? $payment->created_at;

                        return (object) [
                            'type' => 'refund',
                            'unique_id' => $order->unique_id,
                            'link' => $order->view_link,
                            'date' => $refundDate,
                            'customer_name' =>
                                $order->shippingAddress?->full_name ?? '-',
                            'products' =>
                                'Refund - ' .
                                $order->products
                                    ->pluck('product_name')
                                    ->implode(', '),
                            'payment_type' =>
                                $payment->payment_method?->label() ?? '-',
                            // NEGATIVE VALUES
                            'subtotal' => -$refundSubtotal,
                            'tax_amount' => -$refundTax,
                            'discount_amount' => 0,
                            'grand_total' => -$refundAmount,
                        ];
                    });

            });

            // dd($orderRefundedPayments);

            //  Combine into unified rows
            $reportRows = $orders
                ->map(function ($order) {
                    $productNames = $order->products->pluck('product_name')->toArray();

                    $paymentType = $order->last_payment_type?->label();

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
                ->concat($orderRefundedPayments)
                ->concat(
                    $paymentAccounts->map(function ($payment) {
                        $amount = $payment->amount ?? 0;
                        $taxAmount = $amount - $amount / (1 + ($payment->sales_tax ?? 0));
                        $subtotal = $amount - $taxAmount;

                        $AccountspaymentType = $payment->payment_type?->label() ?? '';

                        return (object) [
                            'type' => 'payment',
                            'unique_id' => $payment->customer?->unique_id ?? '-',
                            'link' => '<a href="' . route('admin.reports.sales-tax.paymentview', $payment->customer?->unique_id) . '" class="text-brand-500 underline font-bold" >View Payment</a>',
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
                

            $reportRowsTotal = $reportRows->sum(fn($row) => $row->grand_total);


            $rowsalesTaxCollected = $reportRows->filter(fn($row) => $row->tax_amount == 0)->sum(fn($row) => $row->subtotal) ;

            $orderExtraCharges = OrderExtraCharges::query()
                ->when($request->filled('month_range'), function ($q) use ($request) {
                    [$year, $month] = explode('-', $request->month_range);
                    $start = Carbon::create($year, $month, 1)->startOfMonth();
                    $end = Carbon::create($year, $month, 1)->endOfMonth();
                    $q->whereBetween('created_at', [$start, $end]);
                })
                ->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
                    $start = Carbon::parse($request->start_date)->startOfDay();
                    $end = Carbon::parse($request->end_date)->endOfDay();
                    $q->whereBetween('created_at', [$start, $end]);
                })
                ->get();

           
            $extraChargesTotalRaw = $orderExtraCharges->sum('amount');
           
            $taxFreeRevenue = CustomHelper::formatCurrency($rowsalesTaxCollected + $extraChargesTotalRaw);

            $reversetaxableRevenue = $reportRows->filter(fn($row) => $row->tax_amount > 0)->sum(fn($row) => $row->grand_total);

            $taxableRevenue = $reversetaxableRevenue / (1 + $sales_tax);

            $salesTaxCollectedbeforCurrencyicon = $reportRows->sum(fn($row) => $row->tax_amount) ;

            $rowtaxableRevenue = $reportRowsTotal - $rowsalesTaxCollected ;

            $taxableRevenue = CustomHelper::formatCurrency( $rowtaxableRevenue );

            $salesTaxCollected = CustomHelper::formatCurrency( $rowtaxableRevenue * $sales_tax );

            // $reportRows = $reportRows->filter(fn($row) => $row->tax_amount > 0)->values();
            $reportRows = $reportRows
    ->filter(fn($row) => $row->tax_amount != 0)
    ->values();

            $totalCollectedAllSources = CustomHelper::formatCurrency($extraChargesTotalRaw + $reportRowsTotal);

            $totalRevenue = CustomHelper::formatCurrency( $reportRowsTotal - $rowsalesTaxCollected);

            // Pagination
            $perPage = $request->get('per_page', 30);
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
                    'totalCollectedAllSources' => $totalCollectedAllSources,
                    'totalRevenue' => $totalRevenue,

                    'taxFreeRevenue' => $taxFreeRevenue,
                    'taxableRevenue' => $taxableRevenue,
                    'salesTaxCollected' => $salesTaxCollected,
                ];

                return response()->json(['success' => true, 'html' => $html, 'stats' => $stats]);
            }

            
            // Get months from Orders
            $orderMonths = Order::selectRaw('YEAR(order_date) as year, MONTH(order_date) as month')
                ->groupBy('year', 'month')
                ->get();

            // Get months from Payment Accounts
            $paymentMonths = CustomerAccount::selectRaw('YEAR(date) as year, MONTH(date) as month')
            ->where('type', 'payment')
                ->groupBy('year', 'month')
                ->get();

            // Merge both collections
            $allMonths = $orderMonths
                ->concat($paymentMonths)
                ->unique(function ($item) {
                    return $item->year . '-' . $item->month;
                })
                ->sortByDesc(function ($item) {
                    return $item->year . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                })
                ->take(12)
                ->values();

            // Format for dropdown
            $availableMonths = $allMonths->map(fn($item) => [
                'value' => "{$item->year}-" . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                'label' => 'Pay for '
                    . Carbon::create($item->year, $item->month, 1)->format('M 1')
                    . ' - '
                    . Carbon::create($item->year, $item->month, 1)->endOfMonth()->format('M d'),
            ]);

            return view('admin.reports.sales_tax.index', [
                'orders' => $paginated,
                'stores' => Store::all(),
                'availableMonths' => $availableMonths,
                'totalRevenue' => $totalRevenue,
                'totalCollectedAllSources' => $totalCollectedAllSources,
                'taxFreeRevenue' => $taxFreeRevenue,
                'taxableRevenue' => $taxableRevenue,
                'salesTaxCollected' => $salesTaxCollected,
            ]);

        } catch (\Throwable $e) {

        Log::info('Sales Tax Report Error', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
    }
}
