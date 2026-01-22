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

        $data = $this->getSalesData($sixtyDaysAgo, $today, $filters);
        
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
        $thirtyDaysAgo = Carbon::today()->subDays(30);

        $topProducts = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->where('order_payments.payment_datetime', '>=', $thirtyDaysAgo)
            ->select(
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

        return response()->json($topProducts);
    }

    /**
     * Get top categories by sales
     */
    public function getTopCategories(Request $request)
    {
        $limit = $request->input('limit', 5);

        $topCategories = DB::table('order_products')
            ->join('order_payments', 'order_products.order_id', '=', 'order_payments.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->join('product_category_children', 'products.id', '=', 'product_category_children.product_id')
            ->join('product_categories', 'product_category_children.product_category_id', '=', 'product_categories.id')
            ->whereIn('order_payments.status', ['Paid', 'Account', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->select(
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

        return response()->json($topCategories);
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
                'products.product_type'
            );

        // Apply filters
        if (!empty($filters['store']) && $filters['store'] !== 'all') {
            $query->where(function($q) use ($filters) {
                $q->where('order_products.delivery_store_id', $filters['store'])
                  ->orWhere('order_products.pickup_store_id', $filters['store']);
            });
        }

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->join('product_category_children', 'products.id', '=', 'product_category_children.product_id')
                ->where('product_category_children.product_category_id', $filters['category']);
        }

        if (!empty($filters['product']) && $filters['product'] !== 'all') {
            $query->where('order_products.product_id', $filters['product']);
        }

        if (!empty($filters['itemType']) && $filters['itemType'] !== 'all') {
            $itemType = $filters['itemType'] === 'rental' ? 'Rental' : 'Retail';
            $query->where('products.product_type', $itemType);
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
            
            if ($item->refunded_amount > 0) {
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
            
            if ($item->refunded_amount > 0) {
                $amount = -$amount;
            }
            
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
            }
            $salesByDate[$date] += $amount;
        }

        $result = [];
        
        // Get number of days in each month
        $daysInMonthBeforeLast = $monthBeforeLastStart->daysInMonth;
        $daysInLastMonth = $lastMonthStart->daysInMonth;

        // Add month before last (previous period)
        for ($i = 0; $i < $daysInMonthBeforeLast; $i++) {
            $date = $monthBeforeLastStart->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $result[] = [
                'date' => $dateStr,
                'sales' => $salesByDate[$dateStr] ?? 0,
                'period' => 'previous'
            ];
        }

        // Add last month (current period)
        for ($i = 0; $i < $daysInLastMonth; $i++) {
            $date = $lastMonthStart->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $result[] = [
                'date' => $dateStr,
                'sales' => $salesByDate[$dateStr] ?? 0,
                'period' => 'current'
            ];
        }

        return $result;
    }
}
