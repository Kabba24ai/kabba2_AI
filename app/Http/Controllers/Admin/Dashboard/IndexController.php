<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Enums\Orders\OrderPaymentStatus;
use Carbon\Carbon;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Facades\Log;
use App\Models\MaintenanceManagement\Equipment;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;


use App\Helpers\CustomHelper;


use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        $damagedOrderAlerts = EquipmentSoftAssign::with([
            'order.customer',
            'equipment',
            'orderProduct',
        ])
        ->whereHas('equipment', function ($q) {
            $q->where('current_status', EquipmentCurrentStatus::Damaged)
              ->where('not_for_rent', 0)
              ->whereNull('deleted_at');
        })
        ->whereHas('order')
        ->latest('id')
        ->get()
        ->unique('order_id')   // one alert per order
        ->values()
        ->map(function ($softAssign, $index) {

            $order = $softAssign->order;
            $equipment = $softAssign->equipment;

            $latestNote = $order?->notes()
                ->whereDate('created_at', today())
                ->latest()
                ->first();

            return [
                'id'           => $index + 1,
                'customerName' => $order?->customer?->full_name ?? '—',
                'orderId'      => $order?->unique_id ?? '—',

                'orderLink'    => $order
                    ? route('admin.order-management.orders.edit', $order->unique_id)
                    : null,

                'amountOwed'   => ($order?->grand_total ?? 0) > 0
                    ? '$' . number_format($order->grand_total, 2)
                    : 'Pending',

                'date'         => optional($order?->created_at)->toDateString(),
                'type'         => 'damage',

                'notes'        => $latestNote?->note ?? '',

                'equipment'    => [
                    'id'   => $equipment?->unique_id,
                    'name' => $equipment?->equipment_name,
                ],

                'order_product' => [
                    'id' => $softAssign->orderProduct?->id,
                ],
            ];
        });

        // Get sales data for different periods
        $salesData = $this->getSalesData();

        $chartData = $this->getMaintenanceChartData();

        // dd($damagedOrderAlerts);
        
        return view('admin.dashboard.index', compact('salesData','damagedOrderAlerts','chartData'));
        
    }


        private function getScheduleCount(
            string $type,       
            string $transport,  
            string $status,     
            bool $todayOnly = false
        ) {
            $query = OrderProduct::query()
                ->where('product_data->product_type', 'Rental')
                ->whereNotNull($type . '_date')
                ->where($type . '_transport_mode', $transport)
                ->when($status === 'Completed',
                    fn ($q) => $q->where($type . '_status', 'Completed'),
                    fn ($q) => $q->whereIn($type . '_status', ['Pending', 'Reschedule'])
                )
                ->when($todayOnly,
                    fn ($q) => $q->whereDate($type . '_date', Carbon::today())
                );


            return $query->count();
        }


        private function getMaintenanceChartData()
        {
            $days = collect(range(13, 0))->map(fn ($i) =>
                Carbon::today()->subDays($i)->toDateString()
            );

            $maintenanceDue = [];
            $maintenanceCompleted = [];
            $damagedDue = [];
            $damagedCompleted = [];

            foreach ($days as $day) {

                // ENTERED maintenance that day
                $maintenanceDue[] = EquipmentStatusLog::whereDate('changed_at', $day)
                    ->where('to_status', EquipmentCurrentStatus::Maintenance->value)
                    ->whereHas('equipment', function ($q) {
                        $q->where('not_for_rent', 0)
                        ->whereNull('deleted_at');
                    })->count();

                // EXITED maintenance that day
                $maintenanceCompleted[] = EquipmentStatusLog::whereDate('changed_at', $day)
                    ->where('from_status', EquipmentCurrentStatus::Maintenance->value)
                    ->whereIn('to_status', [
                        EquipmentCurrentStatus::Available->value,
                        EquipmentCurrentStatus::Rented->value,
                    ])->whereHas('equipment', function ($q) {
                        $q->where('not_for_rent', 0)
                        ->whereNull('deleted_at');
                    })
                    ->count();

                // ENTERED damaged
                $damagedDue[] = EquipmentStatusLog::whereDate('changed_at', $day)
                    ->where('to_status', EquipmentCurrentStatus::Damaged->value)
                    ->whereHas('equipment', function ($q) {
                        $q->where('not_for_rent', 0)
                        ->whereNull('deleted_at');
                    })->count();

                // EXITED damaged
                $damagedCompleted[] = EquipmentStatusLog::whereDate('changed_at', $day)
                    ->where('from_status', EquipmentCurrentStatus::Damaged->value)
                    ->whereIn('to_status', [
                        EquipmentCurrentStatus::Available->value,
                        EquipmentCurrentStatus::Rented->value,
                    ])->whereHas('equipment', function ($q) {
                        $q->where('not_for_rent', 0)
                        ->whereNull('deleted_at');
                    })
                    ->count();
            }

              // Formatted labels for chart
            $labels = $days->map(fn ($date) =>
                CustomHelper::formatDate($date)
            );

            return [
                'labels' => $labels,
                'maintenance' => [
                    'due' =>  $maintenanceDue,
                    'completed' => $maintenanceCompleted,
                ],
                'damaged' => [
                    'due' => $damagedDue ,
                    'completed' => $damagedCompleted ,
                ],
            ];
        }



    /**
     * Get sales data grouped by different periods
     */
    private function getSalesData()
    {
        $now = Carbon::now();
        
        // Rolling 30 days data
        $rolling30Days = $this->getRolling30DaysData($now);
        
        // Current month data
        $currentMonth = $this->getCurrentMonthData($now);
        
        // Last month data
        $lastMonth = $this->getLastMonthData($now);
        
        return [
            'rolling30' => $rolling30Days,
            'currentMonth' => $currentMonth,
            'lastMonth' => $lastMonth,
        ];
    }
    
    /**
     * Get rolling 30 days sales data
     */
    private function getRolling30DaysData($now)
    {
        $endDate = $now->copy();
        $startDate = $now->copy()->subDays(29); // Last 30 days including today
        
        // Get current period data - only orders with Paid/Account status
        $currentPeriodData = Order::whereBetween('orders.order_date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ])
        ->join('order_payments', 'orders.id', '=', 'order_payments.order_id')
        ->whereIn('order_payments.status', [
            OrderPaymentStatus::Paid->value,
            OrderPaymentStatus::Account->value,
            OrderPaymentStatus::InvoiceCard->value,
            OrderPaymentStatus::InvoiceCash->value,
            OrderPaymentStatus::InvoiceOnline->value,
            OrderPaymentStatus::InvoiceCheque->value,
            OrderPaymentStatus::InvoiceOther->value,
        ])
        ->select(DB::raw('DATE(orders.order_date) as date'), DB::raw('SUM(DISTINCT orders.grand_total) as total'))
        ->groupBy('date', 'orders.id')
        ->get()
        ->groupBy('date')
        ->map(function ($items) {
            return $items->sum('total');
        })
        ->toArray();
        
        // Get previous period data (30 days before)
        $prevEndDate = $startDate->copy()->subDay();
        $prevStartDate = $prevEndDate->copy()->subDays(29);
        
        $previousPeriodData = Order::whereBetween('orders.order_date', [
            $prevStartDate->format('Y-m-d'),
            $prevEndDate->format('Y-m-d')
        ])
        ->join('order_payments', 'orders.id', '=', 'order_payments.order_id')
        ->whereIn('order_payments.status', [
            OrderPaymentStatus::Paid->value,
            OrderPaymentStatus::Account->value,
            OrderPaymentStatus::InvoiceCard->value,
            OrderPaymentStatus::InvoiceCash->value,
            OrderPaymentStatus::InvoiceOnline->value,
            OrderPaymentStatus::InvoiceCheque->value,
            OrderPaymentStatus::InvoiceOther->value,
        ])
        ->select(DB::raw('DATE(orders.order_date) as date'), DB::raw('SUM(DISTINCT orders.grand_total) as total'))
        ->groupBy('date', 'orders.id')
        ->get()
        ->groupBy('date')
        ->map(function ($items) {
            return $items->sum('total');
        })
        ->toArray();
        
        // Build complete data arrays with all dates
        $categories = [];
        $currentData = [];
        $previousData = [];
        
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            $categories[] = $date->format('n/j'); // Format: M/D
            
            $currentData[] = isset($currentPeriodData[$dateStr]) ? (float)$currentPeriodData[$dateStr] : 0;
            
            $prevDate = $prevStartDate->copy()->addDays($i);
            $prevDateStr = $prevDate->format('Y-m-d');
            $previousData[] = isset($previousPeriodData[$prevDateStr]) ? (float)$previousPeriodData[$prevDateStr] : 0;
        }
        
        return [
            'categories' => $categories,
            'current' => $currentData,
            'previous' => $previousData,
            'totalSales' => array_sum($currentData),
            'previousTotalSales' => array_sum($previousData),
        ];
    }
    
    /**
     * Get current month sales data (grouped by week)
     */
    private function getCurrentMonthData($now)
    {
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();
        
        // Get current month data
        $currentMonthData = $this->getWeeklyData($startDate, $endDate);
        
        // Get previous month data
        $prevStartDate = $now->copy()->subMonth()->startOfMonth();
        $prevEndDate = $now->copy()->subMonth()->endOfMonth();
        $previousMonthData = $this->getWeeklyData($prevStartDate, $prevEndDate);
        
        return [
            'categories' => $currentMonthData['categories'],
            'current' => $currentMonthData['data'],
            'previous' => $previousMonthData['data'],
            'totalSales' => array_sum($currentMonthData['data']),
            'previousTotalSales' => array_sum($previousMonthData['data']),
        ];
    }
    
    /**
     * Get last month sales data (grouped by week)
     */
    private function getLastMonthData($now)
    {
        $startDate = $now->copy()->subMonth()->startOfMonth();
        $endDate = $now->copy()->subMonth()->endOfMonth();
        
        // Get last month data
        $lastMonthData = $this->getWeeklyData($startDate, $endDate);
        
        // Get month before last data
        $prevStartDate = $now->copy()->subMonths(2)->startOfMonth();
        $prevEndDate = $now->copy()->subMonths(2)->endOfMonth();
        $previousMonthData = $this->getWeeklyData($prevStartDate, $prevEndDate);
        
        return [
            'categories' => $lastMonthData['categories'],
            'current' => $lastMonthData['data'],
            'previous' => $previousMonthData['data'],
            'totalSales' => array_sum($lastMonthData['data']),
            'previousTotalSales' => array_sum($previousMonthData['data']),
        ];
    }
    
    /**
     * Get weekly aggregated data for a date range
     */
    private function getWeeklyData($startDate, $endDate)
    {
        $orders = Order::whereBetween('orders.order_date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ])
        ->join('order_payments', 'orders.id', '=', 'order_payments.order_id')
        ->whereIn('order_payments.status', [
            OrderPaymentStatus::Paid->value,
            OrderPaymentStatus::Account->value,
            OrderPaymentStatus::InvoiceCard->value,
            OrderPaymentStatus::InvoiceCash->value,
            OrderPaymentStatus::InvoiceOnline->value,
            OrderPaymentStatus::InvoiceCheque->value,
            OrderPaymentStatus::InvoiceOther->value,
        ])
        ->select('orders.id', 'orders.order_date', 'orders.grand_total')
        ->distinct()
        ->get();
        
        // Group by week
        $weeklyData = [0, 0, 0, 0]; // 4 weeks
        $categories = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        
        foreach ($orders as $order) {
            $orderDate = Carbon::parse($order->order_date);
            $dayOfMonth = $orderDate->day;
            
            // Determine which week (0-3)
            $weekIndex = min(3, floor(($dayOfMonth - 1) / 7));
            $weeklyData[$weekIndex] += (float)$order->grand_total;
        }
        
        return [
            'categories' => $categories,
            'data' => $weeklyData,
        ];
    }
}
