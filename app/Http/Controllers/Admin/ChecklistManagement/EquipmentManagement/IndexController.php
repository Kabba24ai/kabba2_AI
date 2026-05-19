<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request, $equipment = null)
    {
        $users = User::active()->get();


        // $equipments = Equipment::with(['productCategory', 'latestRentalReadyTemplate', 'orderProduct', 'orderProduct.order','order', 'serviceTemplate.preset', 'serviceTemplate.templateTasks.task'])->where('not_for_rent', 0)->orderBy('equipment_name', 'asc')->paginate(5);
        // ->get();

        $query = Equipment::with([
    'productCategory',
    'latestRentalReadyTemplate',
    'orderProduct',
    'orderProduct.order',
    'order',
    'serviceTemplate.preset',
    'serviceTemplate.templateTasks.task'
])->where('not_for_rent', 0);

if ($request->search) {

    $search = $request->search;

    $query->where(function ($q) use ($search) {

        $q->where('equipment_name', 'like', "%{$search}%")
          ->orWhere('model', 'like', "%{$search}%")
          ->orWhere('serial_number', 'like', "%{$search}%")
          ->orWhere('equipment_id', 'like', "%{$search}%");

    });
}

if ($request->category && $request->category != 'All Categories') {

    $query->whereHas('productCategory', function ($q) use ($request) {

     $q->where('title', $request->category);

    });
}

if ($request->status && $request->status != 'All Statuses') {

    // NORMAL EQUIPMENT STATUS
    if ($request->status == 'Available') {

        $query->where('current_status', 'available');

    } elseif ($request->status == 'Damaged') {

        $query->where('current_status', 'damaged');

    } elseif ($request->status == 'Maint. Hold') {

        $query->where('current_status', 'maintenance');

    } elseif ($request->status == 'Rented') {

        $query->where('current_status', 'rented');
    }
}

$equipments = $query
    ->orderBy('equipment_name', 'asc')
    ->paginate(10);

        $categories = ProductCategory::getHierarchy();

        $selectedEquipment = null;

        if ($equipment) {
            $selectedEquipment = Equipment::where('unique_id', $equipment)->firstOrFail();
        }

        // Load service settings
        $settings = DB::table('service_master_settings')->first();
        $pendingBeforeHours = $settings->pending_before_hours ?? 20;
        $pendingAfterHours = $settings->pending_after_hours ?? 15;

        // Load all service records
        $serviceRecords = DB::table('equipment_service_tasks')
            ->select('equipment_id', 'service_task_id', 'interval_value')
            ->get()
            ->groupBy(function($record) {
                return $record->equipment_id . '_' . $record->service_task_id;
            });

        // dd($selectedEquipment);




            $equipments->getCollection()->transform(function ($item) use ($serviceRecords, $pendingBeforeHours, $pendingAfterHours) {
                $serviceStatus = 'empty';

                if ($item->serviceTemplate && $item->serviceTemplate->preset && $item->serviceTemplate->templateTasks->isNotEmpty()) {
                    $intervalType = $item->serviceTemplate->preset->interval_type ?? 'hour';
                    $isDateBased = ($intervalType !== 'hour');

                    $currentValue = ($isDateBased && $item->date_acquired)
                        ? ceil((time() - strtotime($item->date_acquired)) / (60 * 60 * 24))
                        : ($item->equipment_hours ?? 0);

                    $hasOverdue = false;
                    $hasPending = false;
                    $hasNotDue = false;
                    $totalTasks = 0;
                    $completedTasks = 0;

                    foreach ($item->serviceTemplate->templateTasks as $templateTask) {
                        $taskId = $templateTask->task?->id;
                        if (!$taskId) continue;

                        $ints = $templateTask->intervals ?? $templateTask->intervals_json ?? $templateTask->interval ?? [];
                        $arr = is_array($ints) ? $ints : (is_string($ints) ? json_decode($ints, true) ?? [] : [$ints]);

                        foreach ($arr as $interval) {
                            $totalTasks++;

                            $recordKey = $item->id . '_' . $taskId;
                            $records = $serviceRecords[$recordKey] ?? collect();

                            if ($records->contains(fn($record) => $record->interval_value == $interval)) {
                                $completedTasks++;
                                continue;
                            }

                            $greyThreshold = $interval - intval($pendingBeforeHours);
                            $yellowMax = $interval + intval($pendingAfterHours);

                            if ($currentValue < $greyThreshold) $hasNotDue = true;
                            elseif ($currentValue <= $yellowMax) $hasPending = true;
                            else $hasOverdue = true;
                        }
                    }

                    if ($hasOverdue) $serviceStatus = 'overdue';
                    elseif ($hasPending) $serviceStatus = 'pending';
                    elseif ($totalTasks > 0 && $completedTasks === $totalTasks) $serviceStatus = 'completed';
                    elseif ($hasNotDue) $serviceStatus = 'not_due';
                }

                $item->service_status = $serviceStatus;
                return $item;
            });

            // FILTER SERVICE STATUS

if ($request->status == 'Service Due') {

    $filtered = collect($equipments->items())
        ->filter(function ($item) {
            return $item->service_status == 'pending';
        })
        ->values();

    $equipments->setCollection($filtered);
}

if ($request->status == 'Service OverDue') {

    $filtered = collect($equipments->items())
        ->filter(function ($item) {
            return $item->service_status == 'overdue';
        })
        ->values();

    $equipments->setCollection($filtered);
}

            if ($request->ajax()) {
                return response()->json([
                    'data' => $equipments->items(),
                    'current_page' => $equipments->currentPage(),
                    'last_page' => $equipments->lastPage(),
                    'total' => $equipments->total(),
                ]);
            }


        return view('admin.checklist_management.equipment_management.index', [
            'users' => $users,
            'equipments' => $equipments,
            'categories' => $categories,
            'selectedEquipmentId' => $equipment,
            'selectedEquipmentName'=> $selectedEquipment->equipment_name ?? null ,
            'serviceRecords' => $serviceRecords,
            'pendingBeforeHours' => $pendingBeforeHours,
            'pendingAfterHours' => $pendingAfterHours,
        ]);
    }
}
