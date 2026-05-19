<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Orders\OrderProduct;
use Carbon\Carbon;

use App\Models\MaintenanceManagement\Equipment;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use Illuminate\Support\Facades\DB;

class ScheduleSection extends Component
{
    public array $scheduleStats = [];
    public array $equipmentStats = [];
    public int $pendingCount = 0;
    public int $overdueCount = 0;

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
            ->whereIn('to_status', [EquipmentCurrentStatus::Available->value, EquipmentCurrentStatus::Rented->value])
            ->count();

        $damagedCompleted = EquipmentStatusLog::whereDate('changed_at', today())
            ->where('from_status', EquipmentCurrentStatus::Damaged->value)
            ->whereIn('to_status', [EquipmentCurrentStatus::Available->value, EquipmentCurrentStatus::Rented->value])
            ->count();

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

        // Calculate service status counts
        $serviceStatusCounts = $this->getServiceStatusCounts();
        $this->pendingCount = $serviceStatusCounts['pending'];
        $this->overdueCount = $serviceStatusCounts['overdue'];
    }

    public function render()
    {
        return view('livewire.dashboard.schedule-section');
    }

    //   private function getScheduleCount(
    //         string $type,
    //         string $transport,
    //         string $status,
    //         bool $todayOnly = false
    //     ) {
    //         $query = OrderProduct::query()
    //             ->where('product_data->product_type', 'Rental')
    //             ->whereNotNull($type . '_date')
    //             ->where($type . '_transport_mode', $transport)
    //             ->when($status === 'Completed',
    //                 fn ($q) => $q->where($type . '_status', 'Completed'),
    //                 fn ($q) => $q->whereIn($type . '_status', ['Pending', 'Reschedule'])
    //             )
    //             ->when($todayOnly,
    //                 fn ($q) => $q->whereDate($type . '_date', Carbon::today())
    //             );

    //         return $query->count();
    //     }

    private function getScheduleCount(string $type, string $transport, string $status, bool $todayOnly = false)
    {
        $query = OrderProduct::query()
            ->has('order')
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
                $query->where('delivery_status', 'Completed')->where('is_delivered', true)->whereDate('delivery_date', Carbon::today());
            } else {
                // MATCH LIST PAGE
                $query->where('delivery_status', 'Pending');
                // MATCH LIST PAGE
                if ($todayOnly) {
                    $query->whereDate('delivery_date', '<=', Carbon::today());
                }
            }

        }

        /*
        |--------------------------------------------------------------------------
        | RETURN
        |--------------------------------------------------------------------------
        */
        if ($type === 'pickup') {
            if ($status === 'Completed') {
                $query->where('pickup_status', 'Completed')->where('is_returned', true)->whereDate('pickup_date', Carbon::today());
            } else {
                // MATCH LIST PAGE
                $query->where('pickup_status', 'Pending')->where('delivery_status', 'Completed');
                // MATCH LIST PAGE
                if ($todayOnly) {
                    $query->whereDate('pickup_date', '<=', Carbon::today());
                }
            }

        }

        // return $query->distinct('order_id')->count('order_id');
        return $query->count();
    }

    private function getServiceStatusCounts()
    {
        $equipmentWithService = Equipment::with(['serviceTemplate.preset', 'serviceTemplate.templateTasks.task', 'productCategory'])
            ->whereNotNull('equipment_service_id')
            ->latest()
            ->get()
            ->sortBy(function ($item) {
                return strtolower($item->equipment_name);
            });

        $serviceRecords = DB::table('equipment_service_tasks')
            ->leftJoin('users as performed_user', 'equipment_service_tasks.performed_by', '=', 'performed_user.id')
            ->leftJoin('users as checked_user', 'equipment_service_tasks.checked_by', '=', 'checked_user.id')
            ->select('equipment_service_tasks.*', DB::raw('CONCAT(performed_user.first_name, " ", COALESCE(performed_user.last_name, "")) as performed_by_name'), DB::raw('CONCAT(checked_user.first_name, " ", COALESCE(checked_user.last_name, "")) as checked_by_name'))
            ->get()
            ->groupBy(function ($record) {
                return $record->equipment_id . '_' . $record->service_task_id;
            });

        $settings = DB::table('service_master_settings')->first();
        $pendingBeforeHours = $settings->pending_before_hours ?? 20;
        $pendingAfterHours = $settings->pending_after_hours ?? 15;

        $pendingCount = 0;
        $overdueCount = 0;

        foreach ($equipmentWithService as $item) {
            if (!$item->serviceTemplate || !$item->serviceTemplate->preset || !$item->serviceTemplate->templateTasks->count()) {
                continue;
            }

            $intervalType = $item->serviceTemplate->preset->interval_type ?? 'hour';
            $isDateBased = $intervalType !== 'hour';

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
                if (!$taskId) {
                    continue;
                }

                $ints = $templateTask->intervals ?? ($templateTask->intervals_json ?? ($templateTask->interval ?? []));
                $arr = [];
                if (is_array($ints)) {
                    $arr = $ints;
                } elseif (is_string($ints)) {
                    try {
                        $arr = json_decode($ints, true) ?? [];
                    } catch (\Exception $e) {
                        $arr = [];
                    }
                } elseif (is_numeric($ints)) {
                    $arr = [$ints];
                }

                foreach ($arr as $interval) {
                    // Check if this interval is completed
                    $recordKey = $item->id . '_' . $taskId;
                    $records = $serviceRecords[$recordKey] ?? collect();
                    $isCompleted = $records->contains(function ($record) use ($interval) {
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
            'pending' => $pendingCount,
            'overdue' => $overdueCount,
        ];
    }
}
