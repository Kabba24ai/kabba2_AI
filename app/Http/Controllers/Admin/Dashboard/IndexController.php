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
use App\Models\Customers\CustomerAccount;
use App\Models\Dashboard\ResolutionNotePreset;


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
                'resolved',
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
            'order_number' => $order?->order_number ?? '—',
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
                        'resolved',
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
                'order_number' => $order?->order_number ?? '—',
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

        // Merge CRM-originated Damage alerts (CustomerAccount records)
        $crmDamageCharges = CustomerAccount::with(['customer.cards'])
            ->where('type', 'charge')
            ->where('reason', 'Damages')
            ->where('damage_alert_status', 'pending')
            ->latest()
            ->get()
            ->map(function ($account) {
                $base    = (float) ($account->amount ?? 0);
                $taxRate = (float) ($account->sales_tax ?? 0);
                $total   = ($account->sales_tax_type === 'add' && $taxRate > 0)
                    ? $base + $base * $taxRate
                    : $base;

                return [
                    'id'             => 20000 + $account->id,
                    'source'         => 'crm',
                    'customer'       => [
                        'id'        => $account->customer_id,
                        'full_name' => $account->customer?->full_name,
                        'cards'     => $account->customer?->cards?->map(fn ($c) => [
                            'id'    => $c->unique_id,
                            'label' => $c->card_number,
                        ])->values() ?? [],
                    ],
                    'customerName'   => $account->customer?->full_name ?? '—',
                    'orderId'        => null,
                    'order_number'   => 'CRM',
                    'orderLink'      => $account->customer
                        ? route('admin.crm.customers.view', $account->customer->unique_id)
                        : null,
                    'amountOwed'     => '$' . number_format($total, 2),
                    'date'           => optional($account->date)->toDateString(),
                    'type'           => 'damage',
                    'notes'          => [],
                    'equipment'      => null,
                    'order_product'  => null,
                    'customer_account_id' => $account->unique_id,
                ];
            });

        $damagedOrderAlerts = $damagedOrderAlerts->concat($crmDamageCharges)->values();

        // Merge CRM-originated Fuel Charge alerts (CustomerAccount records)
        $crmFuelCharges = CustomerAccount::with(['customer.cards'])
            ->where('type', 'charge')
            ->where('reason', 'Fuel Charge')
            ->where('fuel_alert_status', 'pending')
            ->latest()
            ->get()
            ->map(function ($account) {
                $base    = (float) ($account->amount ?? 0);
                $taxRate = (float) ($account->sales_tax ?? 0);
                $total   = ($account->sales_tax_type === 'add' && $taxRate > 0)
                    ? $base + $base * $taxRate
                    : $base;

                return [
                    'id'             => 10000 + $account->id,
                    'source'         => 'crm',
                    'customer'       => [
                        'id'        => $account->customer_id,
                        'full_name' => $account->customer?->full_name,
                        'cards'     => $account->customer?->cards?->map(fn ($c) => [
                            'id'    => $c->unique_id,
                            'label' => $c->card_number,
                        ])->values() ?? [],
                    ],
                    'customerName'   => $account->customer?->full_name ?? '—',
                    'orderId'        => null,
                    'order_number'   => 'CRM',
                    'orderLink'      => $account->customer
                        ? route('admin.crm.customers.view', $account->customer->unique_id)
                        : null,
                    'amountOwed'     => '$' . number_format($total, 2),
                    'date'           => optional($account->date)->toDateString(),
                    'type'           => 'fuel',
                    'notes'          => [],
                    'equipment'      => null,
                    'order_product'  => null,
                    'customer_account_id' => $account->unique_id,
                ];
            });

        $fuelChargeAlerts = $fuelChargeAlerts->concat($crmFuelCharges)->values();

            // Get sales data for different periods
        $salesData = $this->getSalesData();

        $chartData = $this->getMaintenanceChartData();

        $users = User::active()->orderBy('first_name')->get();

        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');

        // Calculate service status counts
        $serviceStatusCounts = $this->getServiceStatusCounts();
        $pendingCount = $serviceStatusCounts['pendingCount'];
        $overdueCount = $serviceStatusCounts['overdueCount'];

        // Overdue orders: delivered but not yet returned past their pickup date
        $overdueOrderCount = OrderProduct::query()
            ->where('product_data->product_type', 'Rental')
            ->where('delivery_status', 'Completed')
            ->where('pickup_status', 'Pending')
            ->whereNotNull('pickup_date')
            ->whereDate('pickup_date', '<', Carbon::today())
            ->count();

        $data = [
            'deliveries_truck' => [
                'due_today' => $this->getScheduleCount('delivery', 'Truck', 'Due', true),
                'completed_today' => $this->getScheduleCount('delivery', 'Truck', 'Completed', true),
            ],
            'deliveries_store' => [
                'due_today' => $this->getScheduleCount('delivery', 'Store', 'Due', true),
                'completed_today' => $this->getScheduleCount('delivery', 'Store', 'Completed', true),
            ],
            'returns_truck' => [
                'due_today' => $this->getScheduleCount('pickup', 'Truck', 'Due', true),
                'completed_today' => $this->getScheduleCount('pickup', 'Truck', 'Completed', true),
            ],
            'returns_store' => [
                'due_today' => $this->getScheduleCount('pickup', 'Store', 'Due', true),
                'completed_today' => $this->getScheduleCount('pickup', 'Store', 'Completed', true),
            ],
        ];

        $customers = Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $resolutionPresets = ResolutionNotePreset::orderBy('label')->get();

        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        return view('admin.dashboard.index', compact('salesData','customers','damagedOrderAlerts','chartData','users','paymentSetting','fuelChargeAlerts','pendingCount','overdueCount','overdueOrderCount','resolutionPresets','sales_tax'));

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
            ->where($type . '_transport_mode', $transport);

        /*
        |--------------------------------------------------------------------------
        | DELIVERY
        |--------------------------------------------------------------------------
        */
        if ($type === 'delivery') {

            if ($status === 'Completed') {

                $query->where('delivery_status', 'Completed');

            } else {

                // MATCH LIST PAGE
                $query->where('delivery_status', 'Pending');
            }

            // MATCH LIST PAGE
            if ($todayOnly) {
                $query->whereDate('delivery_date', '<=', Carbon::today());
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN
        |--------------------------------------------------------------------------
        */
        if ($type === 'pickup') {

            if ($status === 'Completed') {

                $query->where('pickup_status', 'Completed');

            } else {

                // MATCH LIST PAGE
                $query->where('pickup_status', 'Pending')
                    ->where('delivery_status', 'Completed');
            }

            // MATCH LIST PAGE
            if ($todayOnly) {
                $query->whereDate('pickup_date', '<=', Carbon::today());
            }
        }

        
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



private function getCurrentMonthData($now)
{
    $startDate = $now->copy()->startOfMonth()->startOfDay();
    $endDate   = $now->copy()->endOfMonth()->endOfDay();

    // ---------- CURRENT PERIOD ----------
    $currentRows = $this->getRevenueRows($startDate, $endDate);

    $currentPeriodData = $currentRows
        ->groupBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'))
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- PREVIOUS PERIOD (last month) ----------
    $prevStart = $startDate->copy()->subMonth()->startOfMonth();
    $prevEnd   = $startDate->copy()->subMonth()->endOfMonth();

    $previousRows = $this->getRevenueRows($prevStart, $prevEnd);

    $previousPeriodData = $previousRows
        ->groupBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'))
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- BUILD OUTPUT ----------
    $daysInMonth = $startDate->daysInMonth;

    $categories = [];
    $currentData = [];
    $previousData = [];

    for ($i = 0; $i < $daysInMonth; $i++) {
        $date = $startDate->copy()->addDays($i);
        $dateStr = $date->format('Y-m-d');

        $categories[] = CustomHelper::formatDate($date);
        $currentData[] = (float) ($currentPeriodData[$dateStr] ?? 0);

        $prevDate = $prevStart->copy()->addDays($i);
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


private function getLastMonthData($now)
{
    $startDate = $now->copy()->subMonth()->startOfMonth()->startOfDay();
    $endDate   = $now->copy()->subMonth()->endOfMonth()->endOfDay();

    // ---------- CURRENT PERIOD ----------
    $currentRows = $this->getRevenueRows($startDate, $endDate);

    $currentPeriodData = $currentRows
        ->groupBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'))
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- PREVIOUS PERIOD (two months ago) ----------
    $prevStart = $startDate->copy()->subMonth()->startOfMonth();
    $prevEnd   = $startDate->copy()->subMonth()->endOfMonth();

    $previousRows = $this->getRevenueRows($prevStart, $prevEnd);

    $previousPeriodData = $previousRows
        ->groupBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'))
        ->map(fn ($items) => $items->sum('grand_total'))
        ->toArray();

    // ---------- BUILD OUTPUT ----------
    $daysInMonth = $startDate->daysInMonth;

    $categories = [];
    $currentData = [];
    $previousData = [];

    for ($i = 0; $i < $daysInMonth; $i++) {
        $date = $startDate->copy()->addDays($i);
        $dateStr = $date->format('Y-m-d');

        $categories[] = CustomHelper::formatDate($date);
        $currentData[] = (float) ($currentPeriodData[$dateStr] ?? 0);

        $prevDate = $prevStart->copy()->addDays($i);
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
    $orders = Order::with(['payments'])->whereBetween('order_date', [$start, $end])
        // ->whereRelation('lastPayment', 'payment_method', '!=', 'COD')
        ->whereHas('lastPayment', function ($q) {
                $q->where('payment_method', '!=', 'COD')
                ->orWhere(function ($q) {
                    $q->where('payment_method', 'COD')
                        ->where('status', 'Paid');
                });
            })
        ->whereRelation('lastPayment', 'payment_method', '!=', 'Account')
        ->get();
        // ->map(fn ($order) => (object) [
        //     'date' => $order->order_date,
        //     'grand_total' => (float) $order->grand_total,
        // ]);

         $orderRows = $orders->map(fn ($order) => (object) [
        'date' => $order->order_date,
        'grand_total' => (float) $order->grand_total,
        ]);

        $orderRefundedPayments = $orders->flatMap(function ($order) {
        return $order->payments
            ->filter(function ($payment) {
                return in_array($payment->status?->value ?? $payment->status, [
                    'Refunded',
                    'Partial Refund',
                ]);
            })
            ->map(function ($payment) use ($order) {
                return (object) [
                    'date' => $payment->payment_datetime ?? $payment->created_at ?? $order->order_date,
                    'grand_total' => -((float) $payment->refund_amount),
                ];
            });
    });

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

      return $orderRows
        ->concat($orderRefundedPayments)
        ->concat($payments);
}

private function getServiceStatusCounts()
{
    $equipmentWithService = Equipment::with([
            'serviceTemplate.preset',
            'serviceTemplate.templateTasks.task',
            'productCategory'
        ])
        ->whereNotNull('equipment_service_id')
        ->latest()
        ->get()
        ->sortBy(function($item) {
            return strtolower($item->equipment_name);
        });

    $serviceRecords = DB::table('equipment_service_tasks')
        ->leftJoin('users as performed_user', 'equipment_service_tasks.performed_by', '=', 'performed_user.id')
        ->leftJoin('users as checked_user', 'equipment_service_tasks.checked_by', '=', 'checked_user.id')
        ->select(
            'equipment_service_tasks.*',
            DB::raw('CONCAT(performed_user.first_name, " ", COALESCE(performed_user.last_name, "")) as performed_by_name'),
            DB::raw('CONCAT(checked_user.first_name, " ", COALESCE(checked_user.last_name, "")) as checked_by_name')
        )
        ->get()
        ->groupBy(function($record) {
            return $record->equipment_id . '_' . $record->service_task_id;
        });

    $settings = DB::table('service_master_settings')->first();
    $pendingBeforeHours = $settings->pending_before_hours ?? 20;
    $pendingAfterHours = $settings->pending_after_hours ?? 15;

    $pendingCount = 0;
    $overdueCount = 0;
    foreach($equipmentWithService as $item) {
        if (!$item->serviceTemplate || !$item->serviceTemplate->preset || !$item->serviceTemplate->templateTasks->count()) {
            continue;
        }

        $intervalType = $item->serviceTemplate->preset->interval_type ?? 'hour';
        $isDateBased = ($intervalType !== 'hour');

        // Calculate current value
        if ($isDateBased && $item->date_acquired) {
            $currentValue = ceil((time() - strtotime($item->date_acquired)) / (60 * 60 * 24));
        } else {
            $currentValue = $item->equipment_hours ?? 0;
        }

        $intervals = $item->serviceTemplate->preset->intervals ?? [];
        $tasks = $item->serviceTemplate->templateTasks;

        $hasOverdue = false;
        $hasPending = false;

        foreach ($tasks as $templateTask) {
            $taskId = $templateTask->task?->id;
            if (!$taskId) continue;

            $ints = $templateTask->intervals ?? $templateTask->intervals_json ?? $templateTask->interval ?? [];
            $arr = [];
            if (is_array($ints)) {
                $arr = $ints;
            } elseif (is_string($ints)) {
                try { $arr = json_decode($ints, true) ?? []; } catch(\Exception $e) { $arr = []; }
            } elseif (is_numeric($ints)) {
                $arr = [$ints];
            }

            foreach ($arr as $interval) {
                // Check if this interval is completed
                $recordKey = $item->id . '_' . $taskId;
                $records = $serviceRecords[$recordKey] ?? collect();
                $isCompleted = $records->contains(function($record) use ($interval) {
                    return $record->interval_value == $interval;
                });

                if ($isCompleted) {
                    continue;
                }

                // Calculate status for this interval
                $before = intval($pendingBeforeHours);
                $after = intval($pendingAfterHours);
                $greyThreshold = $interval - $before;
                $yellowMax = $interval + $after;

                if ($currentValue < $greyThreshold) {
                    // Not due - skip
                } elseif ($currentValue <= $yellowMax) {
                    $hasPending = true;
                } else {
                    $hasOverdue = true;
                }
            }
        }

        if ($hasOverdue) {
            $overdueCount++;
        } elseif ($hasPending) {
            $pendingCount++;
        }
    }


    return [
        'pendingCount' => $pendingCount,
        'overdueCount' => $overdueCount
    ];
}


}
