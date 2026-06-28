<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\BillingCharge;
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
use App\Models\Dashboard\FuelNotePreset;


use App\Helpers\CustomHelper;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Tasks\Task;
use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Support\Collection;

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

                                'date'      => optional($order?->created_at)->toDateString(),
                                '_sort_ts'  => $orderProduct?->created_at?->timestamp ?? $order?->created_at?->timestamp ?? 0,
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

                'date'     => optional($order?->created_at)->toDateString(),
                '_sort_ts' => $orderProduct->created_at?->timestamp ?? $order?->created_at?->timestamp ?? 0,
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
                'current_fuel_charge' => $currentFuelCharge,
            ],
            ];
        });

        // Merge CRM-originated Damage alerts (CustomerAccount records).
        // Classification: order_id IS NULL → pure CRM (limited actions), order_id IS NOT NULL → order-linked (full actions).
        $crmDamageAccounts = CustomerAccount::with(['customer.cards', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Damages')
            ->where('damage_alert_status', 'pending')
            ->whereNull('order_product_id')
            ->latest()
            ->get();

        $damageBillingCharges = BillingCharge::whereIn('customer_account_id', $crmDamageAccounts->pluck('id'))
            ->where('billing_charge_type', 'damage')
            ->get()
            ->keyBy('customer_account_id');

        $crmDamageCharges = $crmDamageAccounts->map(function ($account) use ($damageBillingCharges) {
                $base          = (float) ($account->amount ?? 0);
                $taxRate       = (float) ($account->sales_tax ?? 0);
                $total         = ($account->sales_tax_type === 'add' && $taxRate > 0)
                    ? $base + $base * $taxRate
                    : $base;
                $hasOrder      = $account->order !== null;
                $billingCharge = $damageBillingCharges->get($account->id);

                return [
                    'id'                       => 20000 + $account->id,
                    'source'                   => 'crm',
                    'show_full_actions'        => $hasOrder,
                    '_sort_ts'                 => ($account->date ?? $account->created_at)?->timestamp ?? 0,
                    'customer'                 => [
                        'id'        => $account->customer_id,
                        'full_name' => $account->customer?->full_name,
                        'cards'     => $account->customer?->cards?->map(fn ($c) => [
                            'id'    => $c->unique_id,
                            'label' => $c->card_number,
                        ])->values() ?? [],
                    ],
                    'customerName'             => $account->customer?->full_name ?? '—',
                    'orderId'                  => $hasOrder ? $account->order->unique_id : null,
                    'order_number'             => $hasOrder ? $account->order->order_number : null,
                    'orderLink'                => $hasOrder
                        ? route('admin.order-management.orders.edit', $account->order->unique_id)
                        : ($account->customer ? route('admin.crm.customers.view', $account->customer->unique_id) : null),
                    'amountOwed'               => '$' . number_format($total, 2),
                    'date'                     => optional($account->date)->toDateString(),
                    'type'                     => 'damage',
                    'notes'                    => $hasOrder
                        ? $account->order->notes()->dashboard()->latest()->get(['id', 'note', 'created_at'])
                        : collect(),
                    'equipment'                => null,
                    'order_product'            => $billingCharge ? [
                        'base_damage_charge'    => (float) ($billingCharge->amount ?? 0),
                        'current_damage_charge' => (float) ($billingCharge->amount ?? 0),
                    ] : null,
                    'billing_charge_unique_id' => $billingCharge?->unique_id,
                    'customer_account_id'      => $account->unique_id,
                ];
            });

        $damagedOrderAlerts = $damagedOrderAlerts->concat($crmDamageCharges)->sortByDesc('_sort_ts')->values();

        // CustomerAccount fuel charge alerts — covers both CRM-originated and order-linked records.
        // Classification: order_id IS NULL → pure CRM (limited actions), order_id IS NOT NULL → order-linked (full actions).
        $crmFuelAccounts = CustomerAccount::with(['customer.cards', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Fuel Charge')
            ->where('fuel_alert_status', 'pending')
            ->whereNull('order_product_id')
            ->latest()
            ->get();

        // Batch-load associated BillingCharge records (keyed by customer_account_id) to avoid N+1.
        // Order-linked records created via AlertChargeController always have a matching BillingCharge.
        $fuelBillingCharges = BillingCharge::whereIn('customer_account_id', $crmFuelAccounts->pluck('id'))
            ->where('billing_charge_type', 'fuel')
            ->get()
            ->keyBy('customer_account_id');

        $crmFuelCharges = $crmFuelAccounts->map(function ($account) use ($fuelBillingCharges) {
            $base          = (float) ($account->amount ?? 0);
            $taxRate       = (float) ($account->sales_tax ?? 0);
            $total         = ($account->sales_tax_type === 'add' && $taxRate > 0)
                ? $base + $base * $taxRate
                : $base;
            $hasOrder      = $account->order !== null;
            $billingCharge = $fuelBillingCharges->get($account->id);

            return [
                'id'             => 10000 + $account->id,
                'source'         => 'crm',
                'show_full_actions' => $hasOrder,
                '_sort_ts'       => ($account->date ?? $account->created_at)?->timestamp ?? 0,
                'customer'       => [
                    'id'        => $account->customer_id,
                    'full_name' => $account->customer?->full_name,
                    'cards'     => $account->customer?->cards?->map(fn ($c) => [
                        'id'    => $c->unique_id,
                        'label' => $c->card_number,
                    ])->values() ?? [],
                ],
                'customerName'   => $account->customer?->full_name ?? '—',
                'orderId'        => $hasOrder ? $account->order->unique_id : null,
                'order_number'   => $hasOrder ? $account->order->order_number : null,
                'orderLink'      => $hasOrder
                    ? route('admin.order-management.orders.edit', $account->order->unique_id)
                    : ($account->customer ? route('admin.crm.customers.view', $account->customer->unique_id) : null),
                'amountOwed'     => '$' . number_format($total, 2),
                'date'           => optional($account->date)->toDateString(),
                'type'           => 'fuel',
                'notes'          => $hasOrder
                    ? $account->order->notes()->dashboard()->latest()->get(['id', 'note', 'created_at'])
                    : collect(),
                'equipment'      => null,
                // Populate amounts from BillingCharge for order-linked records (used by Adjust modal).
                // CRM records have this null; Adjust button is hidden for them anyway.
                'order_product'  => $billingCharge ? [
                    'base_fuel_charge'    => (float) ($billingCharge->amount ?? 0),
                    'current_fuel_charge' => (float) ($billingCharge->amount ?? 0),
                ] : null,
                'billing_charge_unique_id' => $billingCharge?->unique_id,
                'customer_account_id'      => $account->unique_id,
            ];
        });

        $fuelChargeAlerts = $fuelChargeAlerts->concat($crmFuelCharges)->sortByDesc('_sort_ts')->values();

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

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'primary_contact_name', 'primary_contact_phone']);

        $resolutionPresets = ResolutionNotePreset::orderBy('label')->get();
        $fuelNotePresets   = FuelNotePreset::orderBy('label')->get();

        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        $teamWorkload = $this->getTeamWorkloadSummary();

        $categories = TaskCategory::cases();
        $priorities = TaskPriority::cases();
        $statuses   = TaskStatus::cases();
        ['productCategories' => $productCategories, 'equipmentList' => $equipmentList] = $this->getEquipmentDataForDashboard();

        return view('admin.dashboard.index', compact('salesData','customers','suppliers','damagedOrderAlerts','chartData','users','paymentSetting','fuelChargeAlerts','pendingCount','overdueCount','overdueOrderCount','resolutionPresets','fuelNotePresets','sales_tax','teamWorkload','categories','priorities','statuses','productCategories','equipmentList'));

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
        'grand_total' => (float) $order->subtotal,
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
                    'date' => $payment->refunded_at
                        ?? $payment->payment_datetime
                        ?? $payment->created_at
                        ?? $order->order_date,
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
        // ->map(fn ($p) => (object) [
        //     'date' => $p->date,
        //     'grand_total' => (float) $p->amount,
        // ]);

        ->map(function ($p) {
            $amount = (float) $p->amount;
            $salesTaxRate = (float) $p->sales_tax;

            $taxAmount = $salesTaxRate > 0
                ? $amount * $salesTaxRate
                : 0;

            return (object) [
                'date' => $p->date,
                'grand_total' => $amount - $taxAmount,
            ];
        });

      return $orderRows
        ->concat($orderRefundedPayments)
        ->concat($payments);
}

private function getTeamWorkloadSummary(): Collection
{
    $openTasks = Task::open()
        ->whereNotNull('assigned_to_user_id')
        ->with('assignedTo')
        ->get();

    $openCalls = CustomerCallNeeded::where('status', 'active')
        ->where(fn($q) => $q->whereNull('follow_up_at')->orWhere('follow_up_at', '<=', now()))
        ->whereNotNull('created_by')
        ->with('assignee')
        ->get();

    $summary = [];

    foreach ($openTasks as $task) {
        $uid = $task->assigned_to_user_id;
        if (!isset($summary[$uid])) {
            $summary[$uid] = ['user' => $task->assignedTo, 'task_count' => 0, 'call_count' => 0, 'urgent_count' => 0, 'overdue_count' => 0];
        }
        $summary[$uid]['task_count']++;
        if ($task->priority?->value === 'urgent') $summary[$uid]['urgent_count']++;
        if ($task->isOverdue()) $summary[$uid]['overdue_count']++;
    }

    foreach ($openCalls as $call) {
        $uid = $call->created_by;
        if (!isset($summary[$uid])) {
            $summary[$uid] = ['user' => $call->assignee, 'task_count' => 0, 'call_count' => 0, 'urgent_count' => 0, 'overdue_count' => 0];
        }
        $summary[$uid]['call_count']++;
        if (($call->priority?->value === 'urgent') || $call->is_urgent) $summary[$uid]['urgent_count']++;
        if ($call->due_date && $call->due_date->isPast()) $summary[$uid]['overdue_count']++;
    }

    return collect(array_values($summary))->sort(function ($a, $b) {
        if ($b['urgent_count'] !== $a['urgent_count']) return $b['urgent_count'] - $a['urgent_count'];
        if ($b['overdue_count'] !== $a['overdue_count']) return $b['overdue_count'] - $a['overdue_count'];
        $totalDiff = ($b['task_count'] + $b['call_count']) - ($a['task_count'] + $a['call_count']);
        if ($totalDiff !== 0) return $totalDiff;
        return strcmp($a['user']?->full_name ?? '', $b['user']?->full_name ?? '');
    })->values();
}

private function getEquipmentDataForDashboard(): array
{
    $usedCategoryIds = Equipment::whereNotNull('product_category_id')->pluck('product_category_id')->unique();

    $productCategories = ProductCategory::whereNull('parent_id')
        ->whereIn('id', $usedCategoryIds)
        ->orderBy('title')
        ->get(['id', 'title']);

    $statusLabels = ['available' => 'Available', 'rented' => 'Rented', 'maintenance' => 'Maint. Hold', 'damaged' => 'Damaged'];

    $equipmentList = Equipment::whereNotNull('product_category_id')
        ->orderBy('equipment_name')
        ->get(['id', 'equipment_id', 'equipment_name', 'product_category_id', 'current_status', 'serial_number'])
        ->map(fn($e) => [
            'id'           => $e->id,
            'equipment_id' => $e->equipment_id,
            'name'         => $e->equipment_name,
            'category_id'  => $e->product_category_id,
            'status'       => $statusLabels[$e->getRawOriginal('current_status')] ?? '',
            'serial'       => $e->serial_number ?? '',
        ]);

    return compact('productCategories', 'equipmentList');
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
