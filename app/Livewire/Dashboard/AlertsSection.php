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


        //  Send data to JS
        $this->dispatch('alerts-updated', alerts: $alerts);
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
