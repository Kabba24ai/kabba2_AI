<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Equipment::with('statusUpdatedByUser', 'productCategory', 'checklistMaster', 'store', 'order.customer', 'activeEquipmentRentalReadyTemplate', 'serviceTemplate.preset', 'serviceTemplate.templateTasks.task');
        $this->applyCommonFilters($query, $request);

        // Load service settings for threshold calculations
        $settings = DB::table('service_master_settings')->first();
        $pendingBeforeHours = $settings->pending_before_hours ?? 20;
        $pendingAfterHours = $settings->pending_after_hours ?? 15;

        // Load all service records
        $serviceRecords = DB::table('equipment_service_tasks')
            ->select('equipment_id', 'service_task_id', 'interval_value')
            ->get()
            ->groupBy(function ($record) {
                return $record->equipment_id . '_' . $record->service_task_id;
            });

        $this->applyServiceDueFilter($query, $request->service_due, $serviceRecords, $pendingBeforeHours, $pendingAfterHours);

        $stats = $this->buildStats($query);

        $equipmentQuery = clone $query;

        // status filter (ignore when store filter is active)
        $equipmentQuery->when($request->status && !$request->filled('location_store'), function ($q, $status) {
            $q->where('current_status', $status);
        });

        $perPage = $request->input('per_page', 30);
        $perPageVal = $perPage === 'all' ? max(1, $equipmentQuery->count()) : (int) $perPage;
        $equipment = $equipmentQuery
            ->orderBy(ProductCategory::select('title')->whereColumn('product_categories.id', 'equipment.product_category_id'), 'asc')
            ->orderBy('equipment_name', 'asc')
            ->orderBy('equipment_id', 'asc')
            ->paginate($perPageVal)
            ->withQueryString();

        $stores = Store::active()->orderByAdmin()->pluck('store_name', 'id');

        $categories = ProductCategory::getHierarchy();

        // Return only the table partial if it's an AJAX request
        if ($request->ajax()) {
            $html = view('admin.maintenance_management.equipment.partials._table', compact('equipment', 'serviceRecords', 'pendingBeforeHours', 'pendingAfterHours'))->render();
            $statsHtml = view('admin.maintenance_management.equipment.partials._stats', compact('stats'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
                'stats_html' => $statsHtml,
            ]);
        }

        return view('admin.maintenance_management.equipment.index', compact('stats', 'categories', 'stores', 'serviceRecords', 'pendingBeforeHours', 'pendingAfterHours'));
    }

    /**
     * Calculate service status for an equipment item
     */
    private function calculateServiceStatus($item, $serviceRecords, $pendingBeforeHours, $pendingAfterHours)
    {
        $serviceStatus = 'empty';

        if ($item->serviceTemplate && $item->serviceTemplate->preset && $item->serviceTemplate->templateTasks->isNotEmpty()) {
            $intervalType = $item->serviceTemplate->preset->interval_type ?? 'hour';
            $isDateBased = $intervalType !== 'hour';

            // Calculate current value
            if ($isDateBased && $item->date_acquired) {
                $currentValue = ceil((time() - strtotime($item->date_acquired)) / (60 * 60 * 24));
            } else {
                $currentValue = $item->equipment_hours ?? 0;
            }

            $tasks = $item->serviceTemplate->templateTasks;

            $hasOverdue = false;
            $hasPending = false;
            $hasNotDue = false;
            $totalTasks = 0;
            $completedTasks = 0;

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
                    $totalTasks++;

                    // Check if this interval is completed
                    $recordKey = $item->id . '_' . $taskId;
                    $records = $serviceRecords[$recordKey] ?? collect();
                    $isCompleted = $records->contains(function ($record) use ($interval) {
                        return $record->interval_value == $interval;
                    });

                    if ($isCompleted) {
                        $completedTasks++;
                        continue;
                    }

                    // Calculate status for this interval
                    $before = intval($pendingBeforeHours ?? 20);
                    $after = intval($pendingAfterHours ?? 15);
                    $greyThreshold = $interval - $before;
                    $yellowMax = $interval + $after;

                    if ($currentValue < $greyThreshold) {
                        $hasNotDue = true;
                    } elseif ($currentValue <= $yellowMax) {
                        $hasPending = true;
                    } else {
                        $hasOverdue = true;
                    }
                }
            }

            // Determine overall status based on priority
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

        return $serviceStatus;
    }

    private function applyCommonFilters($query, Request $request)
    {
        // Checklist Master filter
        $query->when($request->checklist_master, function ($q, $checklistMaster) {
            if ($checklistMaster === 'assigned') {
                $q->whereNotNull('checklist_master_id');
            } elseif ($checklistMaster === 'Pending') {
                $q->whereNull('checklist_master_id');
            }
        });

        // Location store filter
        $query->when($request->location_store, function ($q, $locationstore) {
            if ($locationstore === 'rented') {
                $q->where('current_status', 'rented');
                return;
            }

            $q->where('store_id', $locationstore);
        });

        // Search filter
        $query->when($request->search, function ($q, $search) {
            $q->where('equipment_name', 'like', '%' . $search . '%');
        });

        $query->when($request->equipment_id, function ($q, $equipmentId) {
            $q->where('equipment_id', 'like', '%' . $equipmentId . '%');
        });

        // Category filter
        $query->when($request->category, function ($q, $category) {
            $q->where('product_category_id', $category);
        });
    }

    private function applyServiceDueFilter($query, $serviceDue, $serviceRecords, $pendingBeforeHours, $pendingAfterHours)
    {
        if (!$serviceDue) {
            return;
        }

        $allEquipment = $query->get();
        $filteredIds = [];

        foreach ($allEquipment as $item) {
            $serviceStatus = $this->calculateServiceStatus($item, $serviceRecords, $pendingBeforeHours, $pendingAfterHours);

            if ($serviceStatus === $serviceDue) {
                $filteredIds[] = $item->id;
            }
        }

        if (!empty($filteredIds)) {
            $query->whereIn('id', $filteredIds);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    private function buildStats($query)
    {
        $statsByStatus = (clone $query)
            ->select('current_status', DB::raw('count(*) as aggregate'))
            ->groupBy('current_status')
            ->pluck('aggregate', 'current_status');

        return [
            'total' => $statsByStatus->sum(),
            'available' => $statsByStatus->get('available', 0),
            'rented' => $statsByStatus->get('rented', 0),
            'maintenance' => $statsByStatus->get('maintenance', 0),
            'damaged' => $statsByStatus->get('damaged', 0),
        ];
    }
}
