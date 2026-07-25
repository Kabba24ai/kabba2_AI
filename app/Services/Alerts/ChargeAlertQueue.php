<?php

namespace App\Services\Alerts;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\Customers\CustomerAccount;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\OrderProduct;
use App\Services\AlertLifecycleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Dashboard V2 Phase 1A — the canonical Fuel/Damage outstanding-alert queues
 * and their summary metrics, extracted VERBATIM from
 * Livewire\Dashboard\AlertsSection::refreshAlerts()/summarize() so the
 * dashboard cards and the operational workspaces can never disagree: both
 * consume these exact methods. This is a pure code move — every inclusion,
 * exclusion, dedup (one alert per order), CRM-merge, sort, and timezone rule
 * is unchanged from the Phase 1B dashboard implementation.
 *
 * Do not add workspace-specific filtering here — filters are applied by the
 * workspace controller ON TOP of these canonical collections, so the
 * unfiltered workspace queue always reconciles with the dashboard count.
 */
class ChargeAlertQueue
{
    /**
     * Business day boundary + age are reported in the operational timezone,
     * NOT UTC (Dashboard V2 Phase 1B).
     */
    public const BUSINESS_TZ = 'America/Chicago';

    /**
     * The outstanding FUEL queue: OrderProduct checklist charges (one per
     * order) merged with pure-CRM CustomerAccount fuel charges, newest first.
     */
    public static function fuelAlerts(): Collection
    {
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
            'account', // transferred to the customer's credit account — no longer awaiting collection
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
                'order_number' => $order?->order_number ?? '—',
                'order_db_id' => $order?->id,
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

        // Merge CRM-originated fuel alerts so the poll doesn't drop them.
        // Batch-load linked BillingCharge records to classify source and
        // supply billing_charge_unique_id for action routing in the JS.
        $crmFuelAccounts = CustomerAccount::with(['customer.cards', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Fuel Charge')
            ->where('fuel_alert_status', 'pending')
            ->whereNull('order_product_id')
            ->latest()
            ->get();

        $fuelBillingCharges = BillingCharge::whereIn('customer_account_id', $crmFuelAccounts->pluck('id'))
            ->where('billing_charge_type', 'fuel')
            ->get()
            ->keyBy('customer_account_id');

        $crmFuelCharges = $crmFuelAccounts->map(function ($account) use ($fuelBillingCharges) {
                $base    = (float) ($account->amount ?? 0);
                $taxRate = (float) ($account->sales_tax ?? 0);
                $total   = ($account->sales_tax_type === 'add' && $taxRate > 0)
                    ? $base + $base * $taxRate
                    : $base;
                $hasOrder      = $account->order !== null;
                $billingCharge = $fuelBillingCharges->get($account->id);
                return [
                    'id'                       => 10000 + $account->id,
                    'source'                   => 'crm',
                    'show_full_actions'        => $hasOrder,
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
                    'order_db_id'              => $hasOrder ? $account->order->id : null,
                    'orderLink'                => $hasOrder
                        ? route('admin.order-management.orders.edit', $account->order->unique_id)
                        : ($account->customer ? route('admin.crm.customers.view', $account->customer->unique_id) : null),
                    'crmLink'                  => $account->customer
                        ? route('admin.crm.customers.view', $account->customer->unique_id)
                        : null,
                    'amountOwed'               => '$' . number_format($total, 2),
                    'date'                     => optional($account->date)->toDateString(),
                    '_sort_ts'                 => ($account->date ?? $account->created_at)?->timestamp ?? 0,
                    'type'                     => 'fuel',
                    'notes'                    => $hasOrder
                        ? $account->order->notes()->dashboard()->latest()->get(['id', 'note', 'created_at'])
                        : collect(),
                    'equipment'                => null,
                    'order_product'            => $billingCharge ? [
                        'base_fuel_charge'    => (float) ($billingCharge->amount ?? 0),
                        'current_fuel_charge' => (float) ($billingCharge->amount ?? 0),
                    ] : null,
                    'billing_charge_unique_id' => $billingCharge?->unique_id,
                    'customer_account_id'      => $account->unique_id,
                ];
            });

        return $fuelChargeAlerts->concat($crmFuelCharges)->sortByDesc('_sort_ts')->values();
    }

    /**
     * The outstanding DAMAGE queue: damaged-equipment soft-assign alerts
     * (one per order) merged with pure-CRM CustomerAccount damage charges,
     * newest first.
     */
    public static function damageAlerts(): Collection
    {
        $alerts = EquipmentSoftAssign::with([
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
            // NULL damage_status means damage not yet processed — treat as active.
            // whereNotIn alone silently excludes NULL rows in MySQL.
            $q->where(function ($inner) {
                $inner->whereNull('damage_status')
                      ->orWhereNotIn('damage_status', ['completed', 'uncollectible', 'resolved', 'account']);
            });
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
                'order_db_id' => $order?->id,

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

        // Merge CRM-originated damage alerts so the poll doesn't drop them.
        // Batch-load linked BillingCharge records to classify source and
        // supply billing_charge_unique_id for action routing in the JS.
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
                    'order_db_id'              => $hasOrder ? $account->order->id : null,
                    'orderLink'                => $hasOrder
                        ? route('admin.order-management.orders.edit', $account->order->unique_id)
                        : ($account->customer ? route('admin.crm.customers.view', $account->customer->unique_id) : null),
                    'crmLink'                  => $account->customer
                        ? route('admin.crm.customers.view', $account->customer->unique_id)
                        : null,
                    'amountOwed'               => '$' . number_format($total, 2),
                    'date'                     => optional($account->date)->toDateString(),
                    '_sort_ts'                 => ($account->date ?? $account->created_at)?->timestamp ?? 0,
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

        return $alerts->concat($crmDamageCharges)->sortByDesc('_sort_ts')->values();
    }

    /**
     * Reduce an outstanding-alert collection to the card metrics.
     * New Today: queue-entry timestamp falls within today in BUSINESS_TZ.
     * Average Age: mean(now - queue-entry timestamp) over outstanding items,
     * whole days, 0 when the queue is empty.
     */
    public static function summarize(Collection $collection, string $alertType): array
    {
        $todayStart = Carbon::now(self::BUSINESS_TZ)->startOfDay()->timestamp;
        $now        = Carbon::now()->timestamp;

        $outstanding = $collection->count();

        $newToday = $collection->filter(
            fn ($a) => (int) ($a['_sort_ts'] ?? 0) >= $todayStart
        )->count();

        $avgAgeDays = 0;
        if ($outstanding > 0) {
            $avgSeconds = $collection->avg(
                fn ($a) => max(0, $now - (int) ($a['_sort_ts'] ?? $now))
            );
            $avgAgeDays = (int) round($avgSeconds / 86400);
        }

        return [
            'outstanding'        => $outstanding,
            // Completed Today: distinct alert sources that left the queue today
            // (business tz). Resolved This Week: same, for the current Sun–Sat
            // calendar week. Both from the canonical lifecycle log.
            'completed_today'    => AlertLifecycleService::completedTodayCount($alertType),
            'resolved_this_week' => AlertLifecycleService::resolvedThisWeekCount($alertType),
            'new_today'          => $newToday,
            'avg_age_days'       => $avgAgeDays,
        ];
    }
}
