<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Orders\OrderProduct;
use Carbon\Carbon;


use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Enums\Orders\OrderPaymentStatus;


use Illuminate\Support\Facades\Log;
use App\Models\MaintenanceManagement\Equipment;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;



class ScheduleSection extends Component
{
    public array $scheduleStats = [];
    public array $equipmentStats = [];

    public function mount()
    {
        //  Log::info('ScheduleSection mounted');
        $this->refreshData();
    }

    public function refreshData()
    {

    //      Log::info('ScheduleSection refreshData called', [
    //     'time' => now()->toDateTimeString(),
    // ]);

        $this->scheduleStats = [
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

        // Maintenance / damaged logic
        $maintenanceCompleted = EquipmentStatusLog::whereDate('changed_at', today())
            ->where('from_status', EquipmentCurrentStatus::Maintenance->value)
            ->whereIn('to_status', [
                EquipmentCurrentStatus::Available->value,
                EquipmentCurrentStatus::Rented->value,
            ])->count();

        $damagedCompleted = EquipmentStatusLog::whereDate('changed_at', today())
            ->where('from_status', EquipmentCurrentStatus::Damaged->value)
            ->whereIn('to_status', [
                EquipmentCurrentStatus::Available->value,
                EquipmentCurrentStatus::Rented->value,
            ])->count();

        $this->equipmentStats = [
            'maintenance' => [
                'due_today' => Equipment::where('current_status', EquipmentCurrentStatus::Maintenance)->where('not_for_rent', 0)->whereNull('deleted_at')->count(),
                'completed_today' => $maintenanceCompleted,
            ],
            'damaged' => [
                'due_today' => Equipment::where('current_status', EquipmentCurrentStatus::Damaged)->where('not_for_rent', 0)->whereNull('deleted_at')->count(),
                'completed_today' => $damagedCompleted,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.schedule-section');
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
}
