<?php

namespace App\Http\Controllers\Api\SalesReports\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReportController extends Controller
{
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
            
            $data = $this->getSalesData($startDate, $endDate, $filtersWithoutDateRange);
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

            $data = $this->getSalesData($lastMonthStart, $today, $filtersWithoutDateRange);
            
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

            $data = $this->getSalesData($monthBeforeLastStart, $lastMonthEnd, $filtersWithoutDateRange);
            
            return response()->json($this->formatLastMonthData($data, $lastMonthStart, $monthBeforeLastStart));
        }

        // Default: rolling 30 days
        // Remove dateRange from filters since we're handling dates explicitly
        $filtersWithoutDateRange = $filters;
        unset($filtersWithoutDateRange['dateRange']);
        
        $data = $this->getSalesData($sixtyDaysAgo, $today, $filtersWithoutDateRange);
        
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

        $data = $this->getSalesData($fourteenDaysAgo, $today, $filters);
        
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

        $data = $this->getSalesData($monthBeforeLastStart, $lastMonthEnd, $filters);
        
        return response()->json($this->formatLastMonthData($data, $lastMonthStart, $monthBeforeLastStart));
    }

    /**
     * Get top products by sales
     */
    public function getTopProducts(Request $request)
    {
        $limit = $request->input('limit', 10);
        $filters = $request->all();
        
        // Calculate date range based on filters
        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange);

        $query = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->whereBetween(DB::raw('DATE(order_payments.payment_datetime)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);

        // Apply filters
        $this->applyFiltersToQuery($query, $filters);

        $topProducts = $query->select(
                'order_products.product_id as id',
                'products.product_name as name',
                DB::raw('SUM(CASE 
                    WHEN order_payments.refund_amount > 0 
                    THEN -(order_products.total - order_products.tax) 
                    ELSE (order_products.total - order_products.tax) 
                END) as total_sales'),
                DB::raw('COUNT(DISTINCT order_products.order_id) as order_count')
            )
            ->groupBy('order_products.product_id', 'products.product_name')
            ->orderBy('total_sales', 'desc')
            ->limit($limit)
            ->get();

        // Calculate total sales for the same filters
        $totalSalesQuery = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->whereBetween(DB::raw('DATE(order_payments.payment_datetime)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
        
        $this->applyFiltersToQuery($totalSalesQuery, $filters);
        
        $totalSales = $totalSalesQuery->select(
            DB::raw('SUM(CASE 
                WHEN order_payments.refund_amount > 0 
                THEN -(order_products.total - order_products.tax) 
                ELSE (order_products.total - order_products.tax) 
            END) as total')
        )->value('total');

        return response()->json([
            'products' => $topProducts,
            'total_sales' => (float)($totalSales ?? 0)
        ]);
    }

    /**
     * Get top categories by sales
     */
    public function getTopCategories(Request $request)
    {
        $limit = $request->input('limit', 5);
        $filters = $request->all();
        
        // Calculate date range based on filters
        $dateRange = $filters['dateRange'] ?? 'rolling_30';
        [$startDate, $endDate] = $this->getDateRangeForFilters($dateRange);

        $query = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->join('product_category_children', 'products.id', '=', 'product_category_children.product_id')
            ->join('product_categories', 'product_category_children.product_category_id', '=', 'product_categories.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->whereBetween(DB::raw('DATE(order_payments.payment_datetime)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);

        // Apply filters (exclude category filter for top categories)
        $filtersWithoutCategory = $filters;
        unset($filtersWithoutCategory['category']);
        $this->applyFiltersToQuery($query, $filtersWithoutCategory);

        $topCategories = $query->select(
                'product_categories.id',
                'product_categories.title as name',
                DB::raw('SUM(CASE 
                    WHEN order_payments.refund_amount > 0 
                    THEN -(order_products.total - order_products.tax) 
                    ELSE (order_products.total - order_products.tax) 
                END) as total_sales'),
                DB::raw('COUNT(DISTINCT order_products.order_id) as order_count')
            )
            ->groupBy('product_categories.id', 'product_categories.title')
            ->orderBy('total_sales', 'desc')
            ->limit($limit)
            ->get();

        // Calculate total sales for the same filters
        $totalSalesQuery = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->whereBetween(DB::raw('DATE(order_payments.payment_datetime)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
        
        $this->applyFiltersToQuery($totalSalesQuery, $filtersWithoutCategory);
        
        $totalSales = $totalSalesQuery->select(
            DB::raw('SUM(CASE 
                WHEN order_payments.refund_amount > 0 
                THEN -(order_products.total - order_products.tax) 
                ELSE (order_products.total - order_products.tax) 
            END) as total')
        )->value('total');

        return response()->json([
            'categories' => $topCategories,
            'total_sales' => (float)($totalSales ?? 0)
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
     * Private helper: Get sales data with filters
     */
    private function getSalesData($startDate, $endDate, $filters = [])
    {
        // All date range calculations are now handled in getRolling30Days
        // This method just uses the dates passed to it
        
        $query = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->whereBetween(DB::raw('DATE(order_payments.payment_datetime)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->select(
                DB::raw('DATE(order_payments.payment_datetime) as payment_date'),
                'order_products.total',
                'order_products.tax',
                'order_payments.refund_amount',
                'products.product_type',
                'products.product_name'
            );
            
        // Apply store filter
        if (!empty($filters['store']) && $filters['store'] !== 'all') {
            $query->where(function($q) use ($filters) {
                $q->where('order_products.delivery_store_id', $filters['store'])
                  ->orWhere('order_products.pickup_store_id', $filters['store']);
            });
        }

        // Apply category filter
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->join('product_category_children', 'products.id', '=', 'product_category_children.product_id')
                ->where('product_category_children.product_category_id', $filters['category']);
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

        // Apply waiver filters
        if (!empty($filters['waiverOnly']) && $filters['waiverOnly'] === 'true') {
            $query->where('products.product_name', 'LIKE', '%waiver%');
        } elseif (!empty($filters['excludeWaiver']) && $filters['excludeWaiver'] === 'true') {
            $query->where('products.product_name', 'NOT LIKE', '%waiver%');
        }

        // Apply insurance filters
        if (!empty($filters['insuranceOnly']) && $filters['insuranceOnly'] === 'true') {
            $query->where('products.product_name', 'LIKE', '%insurance%');
        } elseif (!empty($filters['excludeInsurance']) && $filters['excludeInsurance'] === 'true') {
            $query->where('products.product_name', 'NOT LIKE', '%insurance%');
        }

        // Apply shipping filter
        if (!empty($filters['excludeShipping']) && $filters['excludeShipping'] === 'true') {
            $query->where('products.product_name', 'NOT LIKE', '%shipping%');
        }

        // Apply delivery filters
        if (!empty($filters['deliveryOnly']) && $filters['deliveryOnly'] === 'true') {
            $query->where('products.product_name', 'LIKE', '%delivery%');
        } elseif (!empty($filters['excludeDelivery']) && $filters['excludeDelivery'] === 'true') {
            $query->where('products.product_name', 'NOT LIKE', '%delivery%');
        }

        return $query->get();
    }

    /**
     * Format data for rolling 30 days
     */
    private function formatRolling30DaysData($data, $today)
    {
        $salesByDate = [];
        
        foreach ($data as $item) {
            $date = $item->payment_date;
            $amount = $item->total - $item->tax; // Exclude tax
            
            // If refunded, subtract the amount
            if ($item->refund_amount > 0) {
                $amount = -$amount;
            }
            
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
            }
            $salesByDate[$date] += $amount;
        }

        $result = [];
        for ($i = 60; $i >= 1; $i--) {
            $date = Carbon::parse($today)->subDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $result[] = [
                'date' => $dateStr,
                'sales' => $salesByDate[$dateStr] ?? 0,
                'period' => $i <= 30 ? 'current' : 'previous'
            ];
        }

        return $result;
    }

    /**
     * Format data for 7 day comparison
     */
    private function format7DayData($data, $today)
    {
        $salesByDate = [];
        
        foreach ($data as $item) {
            $date = $item->payment_date;
            $amount = $item->total - $item->tax;
            
            if ($item->refund_amount > 0) {
                $amount = -$amount;
            }
            
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
            }
            $salesByDate[$date] += $amount;
        }

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
    private function formatLastMonthData($data, $lastMonthStart, $monthBeforeLastStart)
    {
        $salesByDate = [];
        
        foreach ($data as $item) {
            $date = $item->payment_date;
            $amount = $item->total - $item->tax;
            
            if ($item->refund_amount > 0) {
                $amount = -$amount;
            }
            
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
            }
            $salesByDate[$date] += $amount;
        }

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
    private function formatThisMonthData($data, $thisMonthStart, $lastMonthStart, $today)
    {
        $salesByDate = [];
        
        foreach ($data as $item) {
            $date = $item->payment_date;
            $amount = $item->total - $item->tax;
            
            if ($item->refund_amount > 0) {
                $amount = -$amount;
            }
            
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
            }
            $salesByDate[$date] += $amount;
        }

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
    private function formatMonthlyData($data, $dateRange)
    {
        $salesByMonth = [];
        
        foreach ($data as $item) {
            $date = Carbon::parse($item->payment_date);
            $monthKey = $date->format('Y-m'); // e.g., "2024-01"
            $amount = $item->total - $item->tax; // Exclude tax
            
            // If refunded, subtract the amount
            if ($item->refund_amount > 0) {
                $amount = -$amount;
            }
            
            if (!isset($salesByMonth[$monthKey])) {
                $salesByMonth[$monthKey] = 0;
            }
            $salesByMonth[$monthKey] += $amount;
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
    private function getDateRangeForFilters($dateRange)
    {
        switch ($dateRange) {
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

        // Apply waiver filters
        if (!empty($filters['waiverOnly']) && $filters['waiverOnly'] === 'true') {
            $query->where('products.product_name', 'LIKE', '%waiver%');
        } elseif (!empty($filters['excludeWaiver']) && $filters['excludeWaiver'] === 'true') {
            $query->where('products.product_name', 'NOT LIKE', '%waiver%');
        }

        // Apply insurance filters
        if (!empty($filters['insuranceOnly']) && $filters['insuranceOnly'] === 'true') {
            $query->where('products.product_name', 'LIKE', '%insurance%');
        } elseif (!empty($filters['excludeInsurance']) && $filters['excludeInsurance'] === 'true') {
            $query->where('products.product_name', 'NOT LIKE', '%insurance%');
        }

        // Apply shipping filter
        if (!empty($filters['excludeShipping']) && $filters['excludeShipping'] === 'true') {
            $query->where('products.product_name', 'NOT LIKE', '%shipping%');
        }

        // Apply delivery filters
        if (!empty($filters['deliveryOnly']) && $filters['deliveryOnly'] === 'true') {
            $query->where('products.product_name', 'LIKE', '%delivery%');
        } elseif (!empty($filters['excludeDelivery']) && $filters['excludeDelivery'] === 'true') {
            $query->where('products.product_name', 'NOT LIKE', '%delivery%');
        }
    }
}
