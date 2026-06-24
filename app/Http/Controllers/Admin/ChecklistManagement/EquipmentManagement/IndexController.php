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
        $totalStart = microtime(true);

        $start = microtime(true);

        $users = User::active()->get();

        // logger('USERS LOAD: ' . (microtime(true) - $start) . ' sec');

        // $equipments = Equipment::with(['productCategory', 'latestRentalReadyTemplate', 'orderProduct', 'orderProduct.order','order', 'serviceTemplate.preset', 'serviceTemplate.templateTasks.task'])->where('not_for_rent', 0)->orderBy('equipment_name', 'asc')->paginate(5);
        // ->get();

        $currentlyAssigned = $request->boolean('currently_assigned', true);

        $query = Equipment::with(['productCategory', 'latestRentalReadyTemplate', 'orderProduct', 'orderProduct.order', 'order', 'serviceTemplate.preset', 'serviceTemplate.templateTasks.task'])
            ->where('not_for_rent', 0)
            ->selectRaw("equipment.*, (
                CASE WHEN (
                    EXISTS (
                        SELECT 1 FROM order_products op
                        WHERE op.equipment_id = equipment.id
                          AND (op.delivery_status = 'Pending' OR op.pickup_status = 'Pending')
                    ) OR EXISTS (
                        SELECT 1 FROM equipment_soft_assigns esa
                        INNER JOIN order_products op2 ON op2.id = esa.order_product_id
                        WHERE esa.equipment_id = equipment.id
                          AND (op2.delivery_status = 'Pending' OR op2.pickup_status = 'Pending')
                    )
                ) THEN 1 ELSE 0 END
            ) AS is_assigned");

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

        $start = microtime(true);

        if ($currentlyAssigned) {
            // Priority order (uses the is_assigned alias computed in selectRaw):
            // 1 — assigned + maintenance hold (needs work before next rental)
            // 2 — assigned + damaged          (needs work before next rental)
            // 3 — damaged,     no active order
            // 4 — maintenance, no active order
            // 5 — rented   (out on rent; never elevated by assignment)
            // 6 — available (never elevated by assignment)
            $query->orderByRaw("
                CASE
                    WHEN is_assigned = 1 AND current_status = 'maintenance' THEN 1
                    WHEN is_assigned = 1 AND current_status = 'damaged'     THEN 2
                    WHEN current_status = 'damaged'                         THEN 3
                    WHEN current_status = 'maintenance'                     THEN 4
                    WHEN current_status = 'rented'                          THEN 5
                    WHEN current_status = 'available'                       THEN 6
                    ELSE 7
                END
            ");
        } else {
            $query->orderByRaw("CASE current_status
                WHEN 'damaged'     THEN 1
                WHEN 'maintenance' THEN 2
                WHEN 'rented'      THEN 3
                WHEN 'available'   THEN 4
                ELSE 5 END");
        }

        $equipments = $query
            ->orderBy('equipment_name', 'asc')
            ->paginate(10);
        // logger('EQUIPMENT QUERY: ' . (microtime(true) - $start) . ' sec');

        $start = microtime(true);

        $categories = ProductCategory::getHierarchy();

        // logger('CATEGORY LOAD: ' . (microtime(true) - $start) . ' sec');

        $selectedEquipment = null;

        if ($equipment) {
            $start = microtime(true);

            $selectedEquipment = Equipment::where('unique_id', $equipment)->firstOrFail();

            // logger('SELECTED EQUIPMENT: ' . (microtime(true) - $start) . ' sec');
        }

        // Load service settings
        $start = microtime(true);

        $settings = DB::table('service_master_settings')->first();

        // logger('SERVICE SETTINGS: ' . (microtime(true) - $start) . ' sec');
        $pendingBeforeHours = $settings->pending_before_hours ?? 20;
        $pendingAfterHours = $settings->pending_after_hours ?? 15;

        $start = microtime(true);

        // Load all service records
        $serviceRecords = DB::table('equipment_service_tasks')
            ->select('equipment_id', 'service_task_id', 'interval_value')
            ->get()
            ->groupBy(function ($record) {
                return $record->equipment_id . '_' . $record->service_task_id;
            });
        // logger('SERVICE RECORDS: ' . (microtime(true) - $start) . ' sec');
        // dd($selectedEquipment);

        $start = microtime(true);

        $equipments->getCollection()->transform(function ($item) use ($serviceRecords, $pendingBeforeHours, $pendingAfterHours) {
            $serviceStatus = 'empty';

            if ($item->serviceTemplate && $item->serviceTemplate->preset && $item->serviceTemplate->templateTasks->isNotEmpty()) {
                $intervalType = $item->serviceTemplate->preset->interval_type ?? 'hour';
                $isDateBased = $intervalType !== 'hour';

                $currentValue = $isDateBased && $item->date_acquired ? ceil((time() - strtotime($item->date_acquired)) / (60 * 60 * 24)) : $item->equipment_hours ?? 0;

                $hasOverdue = false;
                $hasPending = false;
                $hasNotDue = false;
                $totalTasks = 0;
                $completedTasks = 0;

                foreach ($item->serviceTemplate->templateTasks as $templateTask) {
                    $taskId = $templateTask->task?->id;
                    if (!$taskId) {
                        continue;
                    }

                    $ints = $templateTask->intervals ?? ($templateTask->intervals_json ?? ($templateTask->interval ?? []));
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

                        if ($currentValue < $greyThreshold) {
                            $hasNotDue = true;
                        } elseif ($currentValue <= $yellowMax) {
                            $hasPending = true;
                        } else {
                            $hasOverdue = true;
                        }
                    }
                }

                if ($hasOverdue) {
                    $serviceStatus = 'overdue';
                } elseif ($hasPending) {
                    $serviceStatus = 'pending';
                } elseif ($totalTasks > 0 && $completedTasks === $totalTasks) {
                    $serviceStatus = 'completed';
                } elseif ($hasNotDue) {
                    $serviceStatus = 'not_due';
                }
            }

            $item->service_status = $serviceStatus;
            return $item;
        });

        // logger('TRANSFORM TIME: ' . (microtime(true) - $start) . ' sec');

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
            // logger('TOTAL AJAX TIME: ' . (microtime(true) - $totalStart) . ' sec');

            return response()->json([
                'data' => $equipments->items(),
                'current_page' => $equipments->currentPage(),
                'last_page' => $equipments->lastPage(),
                'total' => $equipments->total(),
            ]);
        }

        // logger('TOTAL PAGE TIME: ' . (microtime(true) - $totalStart) . ' sec');

        return view('admin.checklist_management.equipment_management.index', [
            'users' => $users,
            'equipments' => $equipments,
            'categories' => $categories,
            'selectedEquipmentId' => $equipment,
            'selectedEquipmentName' => $selectedEquipment->equipment_name ?? null,
            'serviceRecords' => $serviceRecords,
            'pendingBeforeHours' => $pendingBeforeHours,
            'pendingAfterHours' => $pendingAfterHours,
        ]);
    }
}
