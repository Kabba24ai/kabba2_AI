<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Orders\OrderProduct;
use App\Enums\Equipments\EquipmentCurrentStatus;
use Illuminate\Support\Facades\Log;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;

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
            $q->where('damage_status', '!=', 'completed');
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
        ])
        ->whereNotNull('fuel_total_charge')
        ->where('fuel_total_charge', '>', 0)
        ->where('fuel_charge_status', '!=', 'completed')
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

            $fuelCharge = (float) ($orderProduct->fuel_total_charge ?? 0);

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

                'amountOwed' => '$' . number_format($fuelCharge, 2),

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
                    'fuel_total_charge' => $fuelCharge,
                ],
            ];
        });


        //  Send data to JS
        $this->dispatch('alerts-updated', alerts: $alerts ,   fuelAlerts: $fuelChargeAlerts);
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
