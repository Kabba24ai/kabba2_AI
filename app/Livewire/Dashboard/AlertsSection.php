<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\OrderProduct;
use App\Enums\Equipments\EquipmentCurrentStatus;
use Illuminate\Support\Facades\Log;
use App\Helpers\ConfigurationHelper;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\Orders\BillingCharge;

class AlertsSection extends Component
{
    protected $listeners = ['refreshAlerts'];

    public function refreshAlerts()
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

                'orderLink' => $order
                    ? route('admin.order-management.orders.edit', $order->unique_id)
                    : null,
   'order_number' => $order?->order_number ?? '—',
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
                'current_fuel_charge' => $currentFuelCharge ,
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

        $alerts = $alerts->concat($crmDamageCharges)->sortByDesc('_sort_ts')->values();

        // Merge CRM-originated fuel alerts so the poll doesn't drop them.
        // Batch-load linked BillingCharge records to classify source and
        // supply billing_charge_unique_id for action routing in the JS.
        $crmFuelAccounts = CustomerAccount::with(['customer.cards', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Fuel Charge')
            ->where('fuel_alert_status', 'pending')
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

        $fuelChargeAlerts = $fuelChargeAlerts->concat($crmFuelCharges)->sortByDesc('_sort_ts')->values();

        //  Send data to JS
        $this->dispatch('alerts-updated', alerts: $alerts, fuelAlerts: $fuelChargeAlerts);
    }

    public function mount()
    {
        //  Log::info('AlertsSection mounted');
        $this->refreshAlerts();
    }

    public function render()
    {
    //       Log::info('AlertsSection refreshData called', [
    //     'time' => now()->toDateTimeString(),
    // ]);
        return view('livewire.dashboard.alerts-section');
    }
}
