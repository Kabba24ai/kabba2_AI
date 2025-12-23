<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\MaintenanceManagement\Equipment;
use Carbon\Carbon;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\Log;

class MaintenanceCharts extends Component
{
     protected $listeners = [
        'refreshMaintenanceCharts' => 'refreshCharts',
    ];

   public function refreshCharts()
    {
        $chartData = $this->getChartData();

        $this->dispatch(
            'maintenance-charts-updated',
            chartData: $chartData
        );
    }


    private function getChartData(): array
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


    public function mount()
    {
        // Log::info('MaintenanceCharts mounted');
        // Initial load
        $this->refreshCharts();
    }

    public function render()
    {
        //  Log::info('MaintenanceCharts refreshData called', [
        //     'time' => now()->toDateTimeString(),
        // ]);
        return view('livewire.dashboard.maintenance-charts');
    }
}