<?php

namespace App\Http\Controllers\Api\SalesReports\V1;

use App\Http\Controllers\Controller;
use App\Services\Reports\ApiSalesReportAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    /**
     * Phase 2D consolidation: the trend, top-products/categories, summary,
     * discounts, and refunds endpoints read their numbers from the canonical
     * reporting engines through this adapter instead of endpoint-specific SQL.
     * Response contracts are unchanged. revenue-breakdown, tax-and-payments,
     * and product-sales-details remain on legacy queries (no canonical engine
     * models their semantics yet) — documented in the Phase 2D report.
     */
    public function __construct(private ApiSalesReportAdapter $adapter) {}

    /**
     * Get rolling 30 days comparison data
     * Compares last 30 days vs previous 30 days
     */
    public function getRolling30Days(Request $request)
    {
        $filters = $request->all();
        $today = Carbon::today();
        $thirtyDaysAgo = Carbon::today()->subDays(30);
        $sixtyDaysAgo = Carbon::today()->subDays(60);

        // Check date range type
        $dateRange = $filters['dateRange'] ?? null;
        
        // Handle yearly reports with monthly aggregation
        if ($dateRange === 'this_year' || $dateRange === 'last_year') {
            // For yearly comparisons, we need data from the start of the year(s)
            $startDate = Carbon::now()->subYears(2)->startOfYear();
            $endDate = Carbon::now();
            
            // Remove dateRange from filters since we're handling dates explicitly
            $filtersWithoutDateRange = $filters;
            unset($filtersWithoutDateRange['dateRange']);
            
            $data = $this->adapter->dailyNetSales($startDate, $endDate, $filtersWithoutDateRange);
            return response()->json($this->formatMonthlyData($data, $dateRange));
        }
        
        // Handle this month comparison
        if ($dateRange === 'this_month') {
            // Get this month (from 1st to today)
            $thisMonthStart = Carbon::now()->startOfMonth();
            $today = Carbon::now();
            
            // Get last month for comparison (same days as current month progress)
            $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->day($today->day);
            
            // Remove dateRange from filters since we're handling dates explicitly
            $filtersWithoutDateRange = $filters;
            unset($filtersWithoutDateRange['dateRange']);

            $data = $this->adapter->dailyNetSales($lastMonthStart, $today, $filtersWithoutDateRange);

            return response()->json($this->formatThisMonthData($data, $thisMonthStart, $lastMonthStart, $today));
        }
        
        // Handle last month comparison
        if ($dateRange === 'last_month') {
            // Get last month (previous complete month) - 1st to last day
            $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            // Get month before last month
            $monthBeforeLastStart = Carbon::now()->subMonths(2)->startOfMonth();
            $monthBeforeLastEnd = Carbon::now()->subMonths(2)->endOfMonth();

            // Remove dateRange from filters since we're handling dates explicitly
            $filtersWithoutDateRange = $filters;
            unset($filtersWithoutDateRange['dateRange']);

            $data = $this->adapter->dailyNetSales($monthBeforeLastStart, $lastMonthEnd, $filtersWithoutDateRange);

            return response()->json($this->formatLastMonthData($data, $lastMonthStart, $monthBeforeLastStart));
        }

        // Default: rolling 30 days
        // Remove dateRange from filters since we're handling dates explicitly
        $filtersWithoutDateRange = $filters;
        unset($filtersWithoutDateRange['dateRange']);

        $data = $this->adapter->dailyNetSales($sixtyDaysAgo, $today, $filtersWithoutDateRange);

        return response()->json($this->formatRolling30DaysData($data, $today));
    }

    /**
     * Get 7 day comparison data
     * Compares last 7 days vs previous 7 days
     */
    public function get7DayComparison(Request $request)
    {
        $filters = $request->all();
        $today = Carbon::today();
        $sevenDaysAgo = Carbon::today()->subDays(7);
        $fourteenDaysAgo = Carbon::today()->subDays(14);

        $data = $this->adapter->dailyNetSales($fourteenDaysAgo, $today, $filters);

        return response()->json($this->format7DayData($data, $today));
    }

    /**
     * Get last month comparison
     * Compares previous complete month vs month before that
     */
    public function getLastMonthComparison(Request $request)
    {
        $filters = $request->all();
        $today = Carbon::today();

        // Get first day of current month
        $firstDayCurrentMonth = Carbon::today()->startOfMonth();

        // Get last month (previous complete month)
        $lastMonthStart = $firstDayCurrentMonth->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $firstDayCurrentMonth->copy()->subDay()->endOfDay();

        // Get month before last month
        $monthBeforeLastStart = $lastMonthStart->copy()->subMonth()->startOfMonth();
        $monthBeforeLastEnd = $lastMonthStart->copy()->subDay()->endOfDay();

        $data = $this->adapter->dailyNetSales($monthBeforeLastStart, $lastMonthEnd, $filters);

        return response()->json($this->formatLastMonthData($data, $lastMonthStart, $monthBeforeLastStart));
    }

    /**
     * Get top products by sales
     */
    public function getTopProducts(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $filters = $request->all();
        $includePrevious = $request->input('include_previous', false);

        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange, $filters);

        // Canonical product revenue (ProductSalesPerformanceEngine): partial
        // refunds netted, extension revenue attributed to the parent rental
        [$topProducts, $totalSales] = $this->adapter->topProducts($filters, $startDate, $endDate);

        if ($includePrevious) {
            [$prevStart, $prevEnd] = $this->getPreviousPeriodDates($dateRange, $filters);
            [$prevRows] = $this->adapter->topProducts($filters, $prevStart, $prevEnd);
            $prevById = $prevRows->keyBy('id');

            $topProducts = $topProducts->map(function ($product) use ($prevById) {
                $product->previous_total_sales = (float) ($prevById[$product->id]->total_sales ?? 0);
                return $product;
            });
        }

        return response()->json([
            'products' => $topProducts->take($limit)->values(),
            'total_sales' => $totalSales
        ]);
    }

    /**
     * Get top categories by sales
     */
    public function getTopCategories(Request $request)
    {
        $limit = (int) $request->input('limit', 5);
        $filters = $request->all();
        $includePrevious = $request->input('include_previous', false);

        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange, $filters);

        // Canonical category revenue keyed by the product's primary category
        [$topCategories, $totalSales] = $this->adapter->topCategories($filters, $startDate, $endDate);

        if ($includePrevious) {
            [$prevStart, $prevEnd] = $this->getPreviousPeriodDates($dateRange, $filters);
            [$prevRows] = $this->adapter->topCategories($filters, $prevStart, $prevEnd);
            $prevById = $prevRows->keyBy('id');

            $topCategories = $topCategories->map(function ($category) use ($prevById) {
                $category->previous_total_sales = (float) ($prevById[$category->id]->total_sales ?? 0);
                return $category;
            });
        }

        return response()->json([
            'categories' => $topCategories->take($limit)->values(),
            'total_sales' => $totalSales
        ]);
    }

    /**
     * Get all categories for filter dropdown
     */
    public function getCategories()
    {
        $categories = DB::table('product_categories')
            ->select('id', 'title as name')
            ->orderBy('title')
            ->get();

        return response()->json($categories);
    }

    /**
     * Get products by category for filter dropdown
     */
    public function getProducts(Request $request)
    {
        $categoryId = $request->input('category_id');

        $query = DB::table('products')
            ->select('products.id', 'products.product_name as name');

        if ($categoryId && $categoryId !== 'all') {
            $query->join('product_category_children', 'products.id', '=', 'product_category_children.product_id')
                ->where('product_category_children.product_category_id', $categoryId);
        }

        $products = $query->orderBy('products.product_name')->get();

        return response()->json($products);
    }

    /**
     * Get all stores for filter dropdown
     */
    public function getStores()
    {
        $stores = DB::table('stores')
            ->select('id', 'store_name as name')
            ->orderBy('store_name')
            ->get();

        return response()->json($stores);
    }

    /**
     * Get sales summary with totals and averages
     */
    public function getSalesSummary(Request $request)
    {
        $filters = $request->all();
        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange, $filters);

        // Canonical KPI snapshot — same numbers as the Blade Pure Sales
        // Summary (extension revenue, real discounts, refund netting included)
        $kpis      = $this->adapter->kpis($filters, $startDate, $endDate);
        $itemsSold = $this->adapter->itemsSold($filters, $startDate, $endDate);

        $transactionCount = (int) $kpis['transaction_count'];

        return response()->json([
            'totalGrossSales'    => (float) $kpis['gross_sales'],
            'totalDiscounts'     => (float) $kpis['discounts'],
            'totalRefunds'       => (float) $kpis['refunds'],
            'totalNetSales'      => (float) $kpis['net_sales'],
            'totalTax'           => (float) $kpis['tax_collected'],
            'transactionCount'   => $transactionCount,
            'itemsSold'          => (int) $itemsSold,
            'averageSaleValue'   => (float) $kpis['average_ticket'],
            'averageItemsPerSale' => $transactionCount > 0 ? (float) round($itemsSold / $transactionCount, 2) : 0.0,
        ]);
    }

    /**
     * Get revenue breakdown by type.
     *
     * Each order_product row stores all pricing components in the product_data JSON:
     *   - product_price               → base unit price (rental or retail)
     *   - service_option_price        → delivery fee (flat, not per-unit)
     *   - product_rental_items_prices → keyed add-on prices:
     *       rental_damage_waiver      → unit price × quantity
     *       rental_track_insurance    → unit price × quantity
     *       rental_tire_insurance     → unit price × quantity
     *       rental_prepaid_cleaning   → flat fee (already total)
     *       rental_prepaid_fuel       → flat fee (already total)
     *   - product_option_items        → array of {price, charged} other add-ons
     */
    public function getRevenueBreakdown(Request $request)
    {
        $filters   = $request->all();
        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange, $filters);

        $query = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->whereBetween(DB::raw('DATE(order_payments.payment_datetime)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ]);

        $this->applyFiltersToQuery($query, $filters);

        $data = $query->select(
            'order_products.product_data',
            'order_products.quantity',
            'order_products.sub_total',
            'order_products.tax',
            'products.product_type',
            'order_payments.refund_amount'
        )->get();

        $breakdown = [
            'retailSales'            => 0,
            'rentalRevenue'          => 0,
            'deliveryRevenue'        => 0,
            'damageWaiverRevenue'    => 0,
            'trackInsuranceRevenue'  => 0,
            'prepaidFuelRevenue'     => 0,
            'prepaidCleaningRevenue' => 0,
            'feesOtherRevenue'       => 0,
        ];

        foreach ($data as $item) {
            // Decode product_data JSON
            $productData = is_string($item->product_data)
                ? (json_decode($item->product_data, true) ?? [])
                : ($item->product_data ?? []);

            $quantity    = (int)($item->quantity ?? 1);
            $isRefund    = (float)($item->refund_amount ?? 0) > 0;
            $productType = $item->product_type ?? '';
            $sign        = $isRefund ? -1 : 1;

            // ── Rental add-on prices stored in product_data ──────────────────
            $rentalItemPrices = $productData['product_rental_items_prices'] ?? [];

            // Enum add-ons: unit price × quantity (each rental period)
            $damageWaiver   = (float)($rentalItemPrices['rental_damage_waiver']   ?? 0) * $quantity;
            $trackInsurance = (float)($rentalItemPrices['rental_track_insurance']  ?? 0) * $quantity;
            $tireInsurance  = (float)($rentalItemPrices['rental_tire_insurance']   ?? 0) * $quantity;

            // Flat add-ons: fixed fee regardless of quantity
            $prepaidCleaning = (float)($rentalItemPrices['rental_prepaid_cleaning'] ?? 0);
            $prepaidFuel     = (float)($rentalItemPrices['rental_prepaid_fuel']     ?? 0);

            // ── Delivery fee ─────────────────────────────────────────────────
            $deliveryFee = (float)($productData['service_option_price'] ?? 0);

            // ── Product option items (other add-ons, e.g. extra fuel gallons) ─
            $optionItems  = $productData['product_option_items'] ?? [];
            $optionsTotal = 0;
            foreach ($optionItems as $opt) {
                $optPrice      = (float)($opt['price'] ?? 0);
                $optionsTotal += ($opt['charged'] ?? '') === 'Unlimited'
                    ? $optPrice * $quantity
                    : $optPrice;
            }

            // ── Base product price × quantity (pure rental or retail revenue) ─
            // product_price is the base unit price without any add-ons
            $basePrice   = (float)($productData['product_price'] ?? 0);
            $baseRevenue = $basePrice * $quantity;

            // ── Accumulate into breakdown buckets ────────────────────────────
            $breakdown['deliveryRevenue']        += $sign * $deliveryFee;
            $breakdown['damageWaiverRevenue']    += $sign * $damageWaiver;
            $breakdown['trackInsuranceRevenue']  += $sign * $trackInsurance;
            $breakdown['prepaidCleaningRevenue'] += $sign * $prepaidCleaning;
            $breakdown['prepaidFuelRevenue']     += $sign * $prepaidFuel;
            // Tire insurance and misc option items go to feesOther
            $breakdown['feesOtherRevenue']       += $sign * ($tireInsurance + $optionsTotal);

            if ($productType === 'Retail') {
                $breakdown['retailSales']   += $sign * $baseRevenue;
            } else {
                $breakdown['rentalRevenue'] += $sign * $baseRevenue;
            }
        }

        return response()->json(array_map('floatval', $breakdown));
    }

    /**
     * Get tax and payment method breakdown
     */
    public function getTaxAndPayments(Request $request)
    {
        $filters = $request->all();
        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange, $filters);

        $query = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->whereBetween(DB::raw('DATE(order_payments.payment_datetime)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);

        $this->applyFiltersToQuery($query, $filters);

        $data = $query->select(
            'order_products.total',
            'order_products.tax',
            'order_payments.refund_amount',
            'order_payments.status'
        )->get();

        $salesTaxCollected = 0;
        $payments = [
            'cashPayments' => 0,
            'cardPayments' => 0,
            'achPayments' => 0,
            'checkPayments' => 0,
            'accountPayments' => 0,
            'otherPayments' => 0
        ];

        foreach ($data as $item) {
            $amount = $item->total - $item->tax;
            $tax = $item->tax ?? 0;

            if ($item->refund_amount > 0) {
                $amount = -$amount;
                $tax = -$tax;
            }

            $salesTaxCollected += $tax;

            // Categorize by payment status
            $status = strtolower($item->status ?? '');
            if (strpos($status, 'cash') !== false) {
                $payments['cashPayments'] += $amount;
            } elseif (strpos($status, 'card') !== false) {
                $payments['cardPayments'] += $amount;
            } elseif (strpos($status, 'cheque') !== false || strpos($status, 'check') !== false) {
                $payments['checkPayments'] += $amount;
            } elseif (strpos($status, 'account') !== false) {
                $payments['accountPayments'] += $amount;
            } elseif (strpos($status, 'online') !== false || strpos($status, 'ach') !== false) {
                $payments['achPayments'] += $amount;
            } else {
                $payments['otherPayments'] += $amount;
            }
        }

        return response()->json([
            'salesTaxCollected' => (float)$salesTaxCollected,
            'cashPayments' => (float)$payments['cashPayments'],
            'cardPayments' => (float)$payments['cardPayments'],
            'achPayments' => (float)$payments['achPayments'],
            'checkPayments' => (float)$payments['checkPayments'],
            'accountPayments' => (float)$payments['accountPayments'],
            'otherPayments' => (float)$payments['otherPayments']
        ]);
    }

    /**
     * Get discounts report
     */
    public function getDiscountsReport(Request $request)
    {
        $filters = $request->all();
        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange, $filters);

        // Real numbers via the canonical engine (orders.discount_amount) —
        // this endpoint previously returned hardcoded zeros
        $kpis      = $this->adapter->kpis($filters, $startDate, $endDate);
        $discounts = (float) $kpis['discounts'];
        $gross     = (float) $kpis['gross_sales'];
        $withDiscounts = $this->adapter->discountedTransactionCount($filters, $startDate, $endDate);

        return response()->json([
            'totalDiscounts' => $discounts,
            'discountPercentage' => $gross > 0 ? round($discounts / $gross * 100, 2) : 0,
            'transactionsWithDiscounts' => $withDiscounts,
            'averageDiscountPerTransaction' => $withDiscounts > 0 ? round($discounts / $withDiscounts, 2) : 0
        ]);
    }

    /**
     * Get refunds report
     */
    public function getRefundsReport(Request $request)
    {
        $filters = $request->all();
        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange, $filters);

        // Canonical refund rows (PaymentReconciliationLedger refund stream):
        // one row per refund transaction, dated by the refund date, covering
        // BOTH full and partial refunds — the legacy query missed Partial
        // Refund entirely and fanned out across product lines
        $rows = $this->adapter->refundRows($filters, $startDate, $endDate);

        $totalRefundAmount = round(abs($rows->sum(fn ($r) => (float) $r->grand_total)), 2);
        $refundTransactionCount = $rows->count();
        $fullRefunds    = $rows->where('payment_status', 'Refunded')->count();
        $partialRefunds = $rows->where('payment_status', 'Partial Refund')->count();

        // No refund-reason field exists in the schema — single bucket preserved
        $refundsByReasonArray = $refundTransactionCount > 0
            ? [['reason' => 'Not specified', 'count' => $refundTransactionCount, 'amount' => $totalRefundAmount]]
            : [];

        return response()->json([
            'totalRefundAmount' => (float) $totalRefundAmount,
            'refundTransactionCount' => $refundTransactionCount,
            'fullRefunds' => $fullRefunds,
            'partialRefunds' => $partialRefunds,
            'refundsByReason' => $refundsByReasonArray
        ]);
    }

    /**
     * Get detailed product sales information
     */
    public function getProductSalesDetails(Request $request)
    {
        $filters = $request->all();
        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange, $filters);

        $query = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->whereBetween(DB::raw('DATE(order_payments.payment_datetime)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);

        $this->applyFiltersToQuery($query, $filters);

        $data = $query->select(
            'order_products.product_id',
            'order_products.order_id',
            'order_products.total',
            'order_products.tax',
            'order_products.quantity',
            'products.product_name',
            'products.product_type',
            'order_payments.refund_amount',
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_variant')) as product_variant")
        )->get();

        // Rental usage multipliers by variant (normalized to equivalent daily rental days)
        $rentalMultipliers = [
            'daily'   => 1,
            'weekend' => 2.5,
            'weekly'  => 7,
            'monthly' => 28,
        ];

        $productMap = [];

        foreach ($data as $item) {
            $productId    = (string)$item->product_id;
            $lineTotal    = (float)($item->total ?? 0);
            $lineTax      = (float)($item->tax ?? 0);
            $quantity     = $item->quantity ?? 1;
            $isRefund     = $item->refund_amount > 0;
            $isRental     = strtolower($item->product_type ?? '') === 'rental';
            $variant      = strtolower($item->product_variant ?? 'daily');
            $multiplier   = $rentalMultipliers[$variant] ?? 1;
            $usageQty     = $isRental ? $quantity * $multiplier : 0;

            if (!isset($productMap[$productId])) {
                $productMap[$productId] = [
                    'productId'              => $productId,
                    'productName'            => $item->product_name ?? 'Unknown',
                    'sku'                    => strtoupper(substr($productId, 0, 8)),
                    'quantitySold'           => 0,
                    'grossSales'             => 0,
                    'discountAmount'         => 0,
                    'refundQuantity'         => 0,
                    'refundAmount'           => 0,
                    'taxCollected'           => 0,
                    'itemType'               => $isRental ? 'rental' : 'retail',
                    'salesCount'             => 0,
                    'rentalUsageQuantity'    => 0,
                    'refundRentalUsageQty'   => 0,
                ];
            }

            // Gross Sales = face value of ALL sales (before any deductions)
            $productMap[$productId]['grossSales'] += $lineTotal;

            if ($isRefund) {
                $productMap[$productId]['refundQuantity']       += $quantity;
                $productMap[$productId]['refundAmount']         += ($lineTotal - $lineTax);
                $productMap[$productId]['refundRentalUsageQty'] += $usageQty;
            } else {
                $productMap[$productId]['quantitySold']        += $quantity;
                $productMap[$productId]['taxCollected']        += $lineTax;
                $productMap[$productId]['salesCount']++;
                $productMap[$productId]['rentalUsageQuantity'] += $usageQty;
            }
        }

        $result = [];
        foreach ($productMap as $product) {
            $salesCount = $product['salesCount'];

            // Net Sales = Gross Sales − Returns − Discounts − Allowances
            $netSales = $product['grossSales'] - $product['refundAmount'] - $product['discountAmount'];

            $result[] = [
                'productId'              => $product['productId'],
                'productName'            => $product['productName'],
                'sku'                    => $product['sku'],
                'quantitySold'           => (int)$product['quantitySold'],
                'grossSales'             => (float)$product['grossSales'],
                'discountAmount'         => (float)$product['discountAmount'],
                'netSales'               => (float)$netSales,
                'averageSellingPrice'    => $salesCount > 0 ? (float)($netSales / $salesCount) : 0,
                'refundQuantity'         => (int)$product['refundQuantity'],
                'refundAmount'           => (float)$product['refundAmount'],
                'netQuantitySold'        => (int)($product['quantitySold'] - $product['refundQuantity']),
                'taxCollected'           => (float)$product['taxCollected'],
                'itemType'               => $product['itemType'],
                'rentalUsageQuantity'    => (float)$product['rentalUsageQuantity'],
                'netRentalUsageQuantity' => (float)($product['rentalUsageQuantity'] - $product['refundRentalUsageQty']),
            ];
        }

        return response()->json($result);
    }

    /**
     * Format data for rolling 30 days
     */
    private function formatRolling30DaysData(array $salesByDate, $today)
    {
        $result = [];
        // i=59 down to i=0: 60 data points where i=0 is today
        // current period  → i=0  to i=29 (today back 29 days = 30 days total)
        // previous period → i=30 to i=59 (30 days before the current window)
        for ($i = 59; $i >= 0; $i--) {
            $date = Carbon::parse($today)->subDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $result[] = [
                'date' => $dateStr,
                'sales' => $salesByDate[$dateStr] ?? 0,
                'period' => $i <= 29 ? 'current' : 'previous'
            ];
        }

        return $result;
    }

    /**
     * Format data for 7 day comparison
     */
    private function format7DayData(array $salesByDate, $today)
    {
        $result = [];
        for ($i = 14; $i >= 1; $i--) {
            $date = Carbon::parse($today)->subDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $result[] = [
                'date' => $dateStr,
                'sales' => $salesByDate[$dateStr] ?? 0,
                'period' => $i <= 7 ? 'current' : 'previous'
            ];
        }

        return $result;
    }

    /**
     * Format data for last month comparison
     */
    private function formatLastMonthData(array $salesByDate, $lastMonthStart, $monthBeforeLastStart)
    {
        $result = [];
        
        // Get number of days in last month (we'll use this as the basis)
        $daysInLastMonth = $lastMonthStart->daysInMonth;
        $daysInMonthBeforeLast = $monthBeforeLastStart->daysInMonth;

        // Create aligned data: match day 1 with day 1, day 2 with day 2, etc.
        // Add both periods together, aligned by day number
        $maxDays = max($daysInLastMonth, $daysInMonthBeforeLast);
        
        for ($i = 0; $i < $maxDays; $i++) {
            // Add previous period (month before last)
            if ($i < $daysInMonthBeforeLast) {
                $date = $monthBeforeLastStart->copy()->addDays($i);
                $dateStr = $date->format('Y-m-d');
                
                $result[] = [
                    'date' => $dateStr,
                    'sales' => $salesByDate[$dateStr] ?? 0,
                    'period' => 'previous',
                    'dayNumber' => $i + 1
                ];
            }
        }
        
        // Add current period (last month)
        for ($i = 0; $i < $daysInLastMonth; $i++) {
            $date = $lastMonthStart->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $result[] = [
                'date' => $dateStr,
                'sales' => $salesByDate[$dateStr] ?? 0,
                'period' => 'current',
                'dayNumber' => $i + 1
            ];
        }

        return $result;
    }

    /**
     * Format data for this month comparison
     */
    private function formatThisMonthData(array $salesByDate, $thisMonthStart, $lastMonthStart, $today)
    {
        $result = [];
        
        // Get the current day of month (how many days into the month we are)
        $currentDayOfMonth = $today->day;
        
        // Add previous period (last month, same number of days)
        for ($i = 0; $i < $currentDayOfMonth; $i++) {
            $date = $lastMonthStart->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $result[] = [
                'date' => $dateStr,
                'sales' => $salesByDate[$dateStr] ?? 0,
                'period' => 'previous',
                'dayNumber' => $i + 1
            ];
        }
        
        // Add current period (this month, from day 1 to today)
        for ($i = 0; $i < $currentDayOfMonth; $i++) {
            $date = $thisMonthStart->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $result[] = [
                'date' => $dateStr,
                'sales' => $salesByDate[$dateStr] ?? 0,
                'period' => 'current',
                'dayNumber' => $i + 1
            ];
        }

        return $result;
    }

    /**
     * Format data for yearly comparison with monthly aggregation
     */
    private function formatMonthlyData(array $salesByDate, $dateRange)
    {
        $salesByMonth = [];

        foreach ($salesByDate as $dateStr => $amount) {
            $monthKey = substr($dateStr, 0, 7); // e.g., "2024-01"
            $salesByMonth[$monthKey] = ($salesByMonth[$monthKey] ?? 0) + $amount;
        }

        $result = [];
        
        if ($dateRange === 'this_year') {
            // Current year: Jan to current month
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;
            
            // Previous year: same months for comparison
            $previousYear = $currentYear - 1;
            
            // Add previous year months (previous period) - only up to current month
            for ($month = 1; $month <= $currentMonth; $month++) {
                $monthKey = sprintf('%d-%02d', $previousYear, $month);
                
                $result[] = [
                    'date' => $monthKey,
                    'month' => Carbon::create($previousYear, $month, 1)->format('M Y'),
                    'sales' => $salesByMonth[$monthKey] ?? 0,
                    'period' => 'previous'
                ];
            }
            
            // Add current year months (current period) - up to current month
            for ($month = 1; $month <= $currentMonth; $month++) {
                $monthKey = sprintf('%d-%02d', $currentYear, $month);
                
                $result[] = [
                    'date' => $monthKey,
                    'month' => Carbon::create($currentYear, $month, 1)->format('M Y'),
                    'sales' => $salesByMonth[$monthKey] ?? 0,
                    'period' => 'current'
                ];
            }
        } elseif ($dateRange === 'last_year') {
            // Last year: all 12 months
            $lastYear = Carbon::now()->subYear()->year;
            $yearBeforeLast = $lastYear - 1;
            
            // Add year before last (previous period)
            for ($month = 1; $month <= 12; $month++) {
                $monthKey = sprintf('%d-%02d', $yearBeforeLast, $month);
                
                $result[] = [
                    'date' => $monthKey,
                    'month' => Carbon::create($yearBeforeLast, $month, 1)->format('M Y'),
                    'sales' => $salesByMonth[$monthKey] ?? 0,
                    'period' => 'previous'
                ];
            }
            
            // Add last year (current period)
            for ($month = 1; $month <= 12; $month++) {
                $monthKey = sprintf('%d-%02d', $lastYear, $month);
                
                $result[] = [
                    'date' => $monthKey,
                    'month' => Carbon::create($lastYear, $month, 1)->format('M Y'),
                    'sales' => $salesByMonth[$monthKey] ?? 0,
                    'period' => 'current'
                ];
            }
        }

        return $result;
    }

    /**
     * Get date range for filters
     */
    private function getDateRangeForFilters($dateRange, $filters = [])
    {
        if ($dateRange === 'custom' && !empty($filters['startDate']) && !empty($filters['endDate'])) {
            return [Carbon::parse($filters['startDate'])->startOfDay(), Carbon::parse($filters['endDate'])->endOfDay()];
        }

        switch ($dateRange) {
            case 'today':
                return [Carbon::today(), Carbon::now()];
            case 'yesterday':
                return [Carbon::yesterday(), Carbon::yesterday()->endOfDay()];
            case 'this_week':
                return [Carbon::now()->startOfWeek(), Carbon::now()];
            case 'last_week':
                return [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()];
            case 'this_year':
                return [Carbon::now()->startOfYear(), Carbon::now()];
            case 'last_year':
                return [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()];
            case 'this_month':
                return [Carbon::now()->startOfMonth(), Carbon::now()];
            case 'last_month':
                return [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
            case 'rolling_30':
            default:
                return [Carbon::now()->subDays(30), Carbon::now()];
        }
    }

    /**
     * Get the previous period date range (same duration, shifted back)
     */
    private function getPreviousPeriodDates($dateRange, $filters = [])
    {
        if ($dateRange === 'custom' && !empty($filters['startDate']) && !empty($filters['endDate'])) {
            $start = Carbon::parse($filters['startDate'])->startOfDay();
            $end = Carbon::parse($filters['endDate'])->endOfDay();
            $days = $start->diffInDays($end);
            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($days)->startOfDay();
            return [$prevStart, $prevEnd];
        }

        switch ($dateRange) {
            case 'today':
                return [Carbon::yesterday(), Carbon::yesterday()->endOfDay()];
            case 'yesterday':
                $d = Carbon::today()->subDays(2);
                return [$d->copy()->startOfDay(), $d->copy()->endOfDay()];
            case 'this_week':
                return [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()];
            case 'last_week':
                return [Carbon::now()->subWeeks(2)->startOfWeek(), Carbon::now()->subWeeks(2)->endOfWeek()];
            case 'this_month':
                return [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
            case 'last_month':
                return [Carbon::now()->subMonths(2)->startOfMonth(), Carbon::now()->subMonths(2)->endOfMonth()];
            case 'this_year':
                return [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()];
            case 'last_year':
                return [Carbon::now()->subYears(2)->startOfYear(), Carbon::now()->subYears(2)->endOfYear()];
            case 'rolling_30':
            default:
                return [Carbon::now()->subDays(60), Carbon::now()->subDays(31)];
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFiltersToQuery($query, $filters)
    {
        // Apply store filter
        if (!empty($filters['store']) && $filters['store'] !== 'all') {
            $query->where(function($q) use ($filters) {
                $q->where('order_products.delivery_store_id', $filters['store'])
                  ->orWhere('order_products.pickup_store_id', $filters['store']);
            });
        }

        // Apply category filter (only if not already joined)
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            // Check if product_category_children is not already joined
            $sql = $query->toSql();
            if (strpos($sql, 'product_category_children') === false) {
                $query->join('product_category_children', 'products.id', '=', 'product_category_children.product_id');
            }
            $query->where('product_category_children.product_category_id', $filters['category']);
        }

        // Apply product filter
        if (!empty($filters['product']) && $filters['product'] !== 'all') {
            $query->where('order_products.product_id', $filters['product']);
        }

        // Apply item type filter
        if (!empty($filters['itemType']) && $filters['itemType'] !== 'all') {
            $itemType = $filters['itemType'] === 'rental' ? 'Rental' : 'Retail';
            $query->where('products.product_type', $itemType);
        }

        // Apply waiver filters — rental_damage_waiver in product_data.product_rental_items
        if (!empty($filters['waiverOnly']) && $filters['waiverOnly'] === 'true') {
            $query->whereRaw("JSON_SEARCH(order_products.product_data, 'one', 'rental_damage_waiver', NULL, '$.product_rental_items') IS NOT NULL");
        } elseif (!empty($filters['excludeWaiver']) && $filters['excludeWaiver'] === 'true') {
            $query->whereRaw("JSON_SEARCH(order_products.product_data, 'one', 'rental_damage_waiver', NULL, '$.product_rental_items') IS NULL");
        }

        // Apply insurance filters — rental_track_insurance in product_data.product_rental_items
        if (!empty($filters['insuranceOnly']) && $filters['insuranceOnly'] === 'true') {
            $query->whereRaw("JSON_SEARCH(order_products.product_data, 'one', 'rental_track_insurance', NULL, '$.product_rental_items') IS NOT NULL");
        } elseif (!empty($filters['excludeInsurance']) && $filters['excludeInsurance'] === 'true') {
            $query->whereRaw("JSON_SEARCH(order_products.product_data, 'one', 'rental_track_insurance', NULL, '$.product_rental_items') IS NULL");
        }

        // Apply shipping filter — orders with no shipping have terms_collection length of 0
        if (!empty($filters['excludeShipping']) && $filters['excludeShipping'] === 'true') {
            $query->whereRaw("JSON_LENGTH((SELECT terms_collection FROM orders WHERE id = order_products.order_id)) = 0");
        }

        // Apply delivery filters — product_data.service_method
        if (!empty($filters['deliveryOnly']) && $filters['deliveryOnly'] === 'true') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_method')) = 'Delivery'");
        } elseif (!empty($filters['excludeDelivery']) && $filters['excludeDelivery'] === 'true') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_method')) = 'In Store Pickup'");
        }
    }
}
