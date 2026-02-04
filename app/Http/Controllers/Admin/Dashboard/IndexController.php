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
use App\Models\Iam\Personnel\User;
use App\Helpers\ConfigurationHelper;
use App\Models\Customers\Customer;


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
            'order.customer.cards',
            'equipment',
            'orderProduct',
              'orderProduct.damageChargeLogs',
        ])
        ->whereHas('equipment', function ($q) {
            $q->where('current_status', EquipmentCurrentStatus::Damaged)
              ->where('not_for_rent', 0)
              ->whereNull('deleted_at');
        })
        ->whereHas('order')
        ->whereHas('orderProduct', function ($q) {
            $q->whereNotIn('damage_status', [
                'completed',
                'uncollectible',
            ]);
        })
        ->latest('id')
        ->get()
        ->unique('order_id')   // one alert per order
        ->values()
        ->map(function ($softAssign, $index) {

                $order = $softAssign->order;
                $equipment = $softAssign->equipment;
                $orderProduct = $softAssign->orderProduct;

                $latestNote = $order?->notes()
                    ->dashboard()
                    ->latest()
                    ->get(['id', 'note', 'created_at']);

                $baseDamage = (float) ($orderProduct->damage_charge ?? 0);
                $adjustments = $orderProduct->damageChargeLogs->sum('change_amount');
                $currentDamage = max(0, $baseDamage + $adjustments);

                return [
                    'id' => $index + 1,

                    'customer' => [
                        'id' => $order?->customer?->id,
                        'full_name' => $order?->customer?->full_name,
                        'cards' => $order?->customer?->cards?->map(fn ($card) => [
                            'id' => $card->unique_id,
                            'label' => $card->card_number,
                        ])->values(),
                    ],

                    'customerName' => $order?->customer?->full_name ?? '—',
                    'orderId' => $order?->unique_id ?? '—',

                    'orderLink' => $order
                        ? route('admin.order-management.orders.edit', $order->unique_id)
                        : null,

                    'amountOwed' => $currentDamage > 0  
                        ? '$' . number_format($currentDamage, 2)
                        : 'Pending',

                    'date' => optional($order?->created_at)->toDateString(),
                    'type' => 'damage',

                    'notes' => $latestNote,

                    'equipment' => [
                        'id' => $equipment?->unique_id,
                        'name' => $equipment?->equipment_name,
                    ],

                    'order_product' => [
                        'id' => $orderProduct?->id,
                        'unique_id' => $orderProduct?->unique_id,
                        'base_damage_charge' => $baseDamage,
                        'current_damage_charge' => $currentDamage,
                    ],
                ];
        });

       $fuelChargeAlerts = OrderProduct::with([
            'order.customer.cards',
            'equipment',
            'fuelChargeLogs',
        ])
        ->whereNotNull('fuel_total_charge')
        ->where('fuel_total_charge', '>', 0)
        ->whereNotIn('fuel_charge_status', [
            'completed',
            'uncollectible',
        ])
        ->whereHas('equipment', function ($q) {
            $q->where('not_for_rent', 0)
            ->whereNull('deleted_at');
        })
        ->whereHas('order')
        ->latest('id')
        ->get()
        ->unique('order_id') // one alert per order
        ->values()
        ->map(function ($orderProduct, $index) {

            $order = $orderProduct->order;
            $equipment = $orderProduct->equipment;

            $latestNote = $order?->notes()
                ->dashboard()
                ->latest()
                ->get(['id', 'note', 'created_at']);

$baseFuelCharge = (float) ($orderProduct->fuel_total_charge ?? 0);
$fuelAdjustments = $orderProduct->fuelChargeLogs->sum('change_amount');
$currentFuelCharge = max(0, $baseFuelCharge + $fuelAdjustments);


            return [
                'id' => $index + 1,

                'customer' => [
                    'id' => $order?->customer?->id,
                    'full_name' => $order?->customer?->full_name,
                    'cards' => $order?->customer?->cards?->map(fn ($card) => [
                        'id' => $card->unique_id,
                        'label' => $card->card_number,
                    ])->values(),
                ],

                'customerName' => $order?->customer?->full_name ?? '—',
                'orderId' => $order?->unique_id ?? '—',

                'orderLink' => $order
                    ? route('admin.order-management.orders.edit', $order->unique_id)
                    : null,

                 'amountOwed' => $currentFuelCharge > 0  
                        ? '$' . number_format($currentFuelCharge, 2)
                        : 'Pending',

                'date' => optional($order?->created_at)->toDateString(),
                'type' => 'fuel',

                'notes' => $latestNote,

                'equipment' => [
                    'id' => $equipment?->unique_id,
                    'name' => $equipment?->equipment_name,
                ],

              'order_product' => [
                'id' => $orderProduct->id,
                'unique_id' => $orderProduct->unique_id,
                'base_fuel_charge' => $baseFuelCharge,
                'current_fuel_charge' => $currentFuelCharge ,
            ],
            ];
        });

        // dd($fuelChargeAlerts);

            // Get sales data for different periods
        $salesData = $this->getSalesData();

        $chartData = $this->getMaintenanceChartData();
        
        $users = User::where('status', 'Active')->get();
      
        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');

        // dd($salesData);
        
        return view('admin.dashboard.index', compact('salesData','damagedOrderAlerts','chartData','users','paymentSetting','fuelChargeAlerts'));
                        
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

            //  IF TODAY → USE DASHBOARD LOGIC
            if ($day === Carbon::today()->toDateString()) {

                $maintenanceDue[] = Equipment::where('current_status', EquipmentCurrentStatus::Maintenance)
                    ->where('not_for_rent', 0)
                    ->whereNull('deleted_at')
                    ->count();

                $maintenanceCompleted[] = EquipmentStatusLog::whereDate('changed_at', $day)
                    ->where('from_status', EquipmentCurrentStatus::Maintenance->value)
                    ->whereIn('to_status', [
                        EquipmentCurrentStatus::Available->value,
                        EquipmentCurrentStatus::Rented->value,
                    ])
                    ->count();

                $damagedDue[] = Equipment::where('current_status', EquipmentCurrentStatus::Damaged)
                    ->where('not_for_rent', 0)
                    ->whereNull('deleted_at')
                    ->count();

                $damagedCompleted[] = EquipmentStatusLog::whereDate('changed_at', $day)
                    ->where('from_status', EquipmentCurrentStatus::Damaged->value)
                    ->whereIn('to_status', [
                        EquipmentCurrentStatus::Available->value,
                        EquipmentCurrentStatus::Rented->value,
                    ])
                    ->count();

                continue;
            }

            //  OTHER DAYS → KEEP YOUR EXISTING LOGIC
            $maintenanceDue[] = EquipmentStatusLog::whereDate('changed_at', $day)
                ->where('to_status', EquipmentCurrentStatus::Maintenance->value)
                ->count();

            $maintenanceCompleted[] = EquipmentStatusLog::whereDate('changed_at', $day)
                ->where('from_status', EquipmentCurrentStatus::Maintenance->value)
                ->whereIn('to_status', [
                    EquipmentCurrentStatus::Available->value,
                    EquipmentCurrentStatus::Rented->value,
                ])
                ->count();

            $damagedDue[] = EquipmentStatusLog::whereDate('changed_at', $day)
                ->where('to_status', EquipmentCurrentStatus::Damaged->value)
                ->count();

            $damagedCompleted[] = EquipmentStatusLog::whereDate('changed_at', $day)
                ->where('from_status', EquipmentCurrentStatus::Damaged->value)
                ->whereIn('to_status', [
                    EquipmentCurrentStatus::Available->value,
                    EquipmentCurrentStatus::Rented->value,
                ])
                ->count();
        }

        return [
            'labels' => collect($days)->map(fn ($d) => CustomHelper::formatDate($d)),
            'maintenance' => [
                'due' => $maintenanceDue,
                'completed' => $maintenanceCompleted,
            ],
            'damaged' => [
                'due' => $damagedDue,
                'completed' => $damagedCompleted,
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
    
    private function getRolling30DaysData($now)
{
    $endDate = $now->copy()->endOfDay();
    $startDate = $now->copy()->subDays(29)->startOfDay();

    // ---------- CURRENT PERIOD ----------
    $currentRows = $this->getRevenueRows($startDate, $endDate);

    $currentPeriodData = $currentRows
        ->groupBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'))
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- PREVIOUS PERIOD ----------
    $prevEndDate = $startDate->copy()->subDay()->endOfDay();
    $prevStartDate = $prevEndDate->copy()->subDays(29)->startOfDay();

    $previousRows = $this->getRevenueRows($prevStartDate, $prevEndDate);

    $previousPeriodData = $previousRows
        ->groupBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'))
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- BUILD OUTPUT ----------
    $categories = [];
    $currentData = [];
    $previousData = [];

    for ($i = 0; $i < 30; $i++) {
        $date = $startDate->copy()->addDays($i);
        $dateStr = $date->format('Y-m-d');

        $categories[] = CustomHelper::formatDate($date);

        $currentData[] = (float) ($currentPeriodData[$dateStr] ?? 0);

        $prevDate = $prevStartDate->copy()->addDays($i);
        $previousData[] = (float) ($previousPeriodData[$prevDate->format('Y-m-d')] ?? 0);
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
    $startDate = $now->copy()->startOfMonth()->startOfDay();
    $endDate   = $now->copy()->endOfMonth()->endOfDay();

    // ---------- CURRENT PERIOD ----------
    $currentRows = $this->getRevenueRows($startDate, $endDate);

    $currentPeriodData = $currentRows
        ->groupBy(function ($row) {
            $day = Carbon::parse($row->date)->day;
            return min(3, floor(($day - 1) / 7)); // week index
        })
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- PREVIOUS PERIOD ----------
    $prevStart = $startDate->copy()->subMonth()->startOfMonth();
    $prevEnd   = $startDate->copy()->subMonth()->endOfMonth();

    $previousRows = $this->getRevenueRows($prevStart, $prevEnd);

    $previousPeriodData = $previousRows
        ->groupBy(function ($row) {
            $day = Carbon::parse($row->date)->day;
            return min(3, floor(($day - 1) / 7));
        })
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- BUILD OUTPUT ----------
    $categories = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    $currentData = [];
    $previousData = [];

    for ($i = 0; $i < 4; $i++) {
        $currentData[]  = (float) ($currentPeriodData[$i] ?? 0);
        $previousData[] = (float) ($previousPeriodData[$i] ?? 0);
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
     * Get last month sales data (grouped by week)
     */

    private function getLastMonthData($now)
{
    $startDate = $now->copy()->subMonth()->startOfMonth()->startOfDay();
    $endDate   = $now->copy()->subMonth()->endOfMonth()->endOfDay();

    // ---------- CURRENT PERIOD ----------
    $currentRows = $this->getRevenueRows($startDate, $endDate);

    $currentPeriodData = $currentRows
        ->groupBy(function ($row) {
            $day = Carbon::parse($row->date)->day;
            return min(3, floor(($day - 1) / 7));
        })
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- PREVIOUS PERIOD ----------
    $prevStart = $startDate->copy()->subMonth()->startOfMonth();
    $prevEnd   = $startDate->copy()->subMonth()->endOfMonth();

    $previousRows = $this->getRevenueRows($prevStart, $prevEnd);

    $previousPeriodData = $previousRows
        ->groupBy(function ($row) {
            $day = Carbon::parse($row->date)->day;
            return min(3, floor(($day - 1) / 7));
        })
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- BUILD OUTPUT ----------
    $categories = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    $currentData = [];
    $previousData = [];

    for ($i = 0; $i < 4; $i++) {
        $currentData[]  = (float) ($currentPeriodData[$i] ?? 0);
        $previousData[] = (float) ($previousPeriodData[$i] ?? 0);
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




private function getRevenueRows(Carbon $start, Carbon $end)
{
    // ORDERS (same rules as Sales Tax)
    $orders = Order::whereBetween('order_date', [$start, $end])
        // ->whereRelation('lastPayment', 'payment_method', '!=', 'COD')
        ->whereHas('lastPayment', function ($q) {
                $q->where('payment_method', '!=', 'COD')
                ->orWhere(function ($q) {
                    $q->where('payment_method', 'COD')
                        ->where('status', 'Paid');
                });
            })
        ->whereRelation('lastPayment', 'payment_method', '!=', 'Account')
        ->get()
        ->map(fn ($order) => (object) [
            'date' => $order->order_date,
            'grand_total' => (float) $order->grand_total,
        ]);

    // PAYMENT ACCOUNTS (same rules as Sales Tax)
    $payments = Customer::with('paymentAccounts')
        ->get()
        ->pluck('paymentAccounts')
        ->flatten()
        ->filter(fn ($p) => $p->date >= $start && $p->date <= $end)
        ->map(fn ($p) => (object) [
            'date' => $p->date,
            'grand_total' => (float) $p->amount,
        ]);

    return $orders->concat($payments);
}


}
