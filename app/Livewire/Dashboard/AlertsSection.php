<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Orders\OrderProduct;
use App\Enums\Equipments\EquipmentCurrentStatus;
use Illuminate\Support\Facades\Log;

class AlertsSection extends Component
{
    protected $listeners = ['refreshAlerts'];

    public function refreshAlerts()
    {
        $alerts = OrderProduct::with([
            'order.customer',
            'equipment',
            ])
            ->whereHas('equipment', function ($q) {
                $q->where('current_status', EquipmentCurrentStatus::Damaged)
                ->where('not_for_rent', 0)
                ->whereNull('deleted_at');
            })
            ->whereHas('order')
            ->orderByDesc('id')
            ->get()
            ->unique('order_id')   
        
            ->values()
            ->map(function ($op, $index) {

                $latestNote =
                $op->order
                    ?->notes()
                    ->whereDate('created_at', today())
                    ->latest()
                    ->first()
                ??
                $op->order
                    ?->notes()
                    ->latest()
                    ->first();

                return [
                    'id'           => $index + 1, // for frontend list key
                    'customerName' => $op->order?->customer?->full_name
                                        ?? '—',
                    'orderId'      => $op->order?->unique_id ?? '—',
                    'orderLink' => $op->order
            ? route('admin.order-management.orders.edit', $op->order->unique_id)
            : null,

                    'amountOwed'   => ($op->order?->grand_total ?? 0) > 0
                                        ? '$' . number_format($op->order->grand_total, 2)
                                        : 'Pending',
                    'date'         => optional($op->order?->created_at)
                                        ->toDateString(),
                    'type'         => 'damage',
                    'notes'        => $latestNote?->note ?? '',
                    'equipment'    => [
                        'id'   => $op->equipment?->unique_id,
                        'name' => $op->equipment?->equipment_name,
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
