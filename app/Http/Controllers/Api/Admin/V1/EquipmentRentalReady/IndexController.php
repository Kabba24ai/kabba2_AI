<?php

namespace App\Http\Controllers\Api\Admin\V1\EquipmentRentalReady;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

// Requests
use App\Http\Requests\Api\Admin\V1\EquipmentRentalReady\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\EquipmentRentalReady\ListResource;

// Model
use App\Models\MaintenanceManagement\Equipment;
use App\Support\Equipment\EquipmentServiceStatusResolver;

// Service — single source of truth, shared with the web rental-ready screen
// (App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\ChecklistQuestionsController)
// so question/answer data and sort order can never drift between the two.
use App\Services\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionsResolver;

class IndexController extends BaseController
{
    /**
     * Equipment Rental Ready List
     *
     * Dedicated admin app endpoint for the rental-ready screen. Mirrors the
     * web rental-ready screen's filters, data, and sort order exactly
     * (see App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\IndexController)
     * without altering the shared /equipment admin app endpoint.
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $search            = $validated['search'] ?? null;
        $category          = $validated['category'] ?? null;
        $status            = $validated['status'] ?? 'All';
        $storeId           = $validated['store_id'] ?? null;
        $perPage           = $validated['per_page'] ?? 10;
        $currentlyAssigned = ($validated['currently_assigned'] ?? '1') !== '0';

        $query = Equipment::with([
                'store',
                'productCategory',
                'orderProduct',
                'order',
                'softAssignments.orderProduct',
                'serviceTemplate.preset',
                'serviceTemplate.templateTasks.task',
                'statusUpdatedByUser',
            ])
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

        // ── Filter: search by name / model / serial number / equipment ID ────
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('equipment_name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('equipment_id', 'like', "%{$search}%");
            });
        }

        // ── Filter: product category ──────────────────────────────────────
        if ($category) {
            $query->where('product_category_id', $category);
        }

        // ── Filter: store location ────────────────────────────────────────
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // ── Filter: current status (Service Due / Service OverDue are computed
        //    statuses, applied after service_status is resolved below) ──────
        if ($status && !in_array($status, ['All', 'Service Due', 'Service OverDue'], true)) {
            $statusMap = [
                'Available'   => 'available',
                'Damaged'     => 'damaged',
                'Maint. Hold' => 'maintenance',
                'Rented'      => 'rented',
            ];

            if (isset($statusMap[$status])) {
                $query->where('current_status', $statusMap[$status]);
            }
        }

        // ── Sort order — identical to the web rental-ready screen ─────────
        if ($currentlyAssigned) {
            // 6-tier revenue-protection priority:
            //   1. Assigned + Maint. Hold
            //   2. Assigned + Damaged
            //   3. Damaged (no assigned order)
            //   4. Maint. Hold (no order)
            //   5. Rented
            //   6. Available
            $query->orderByRaw("CASE
                WHEN (
                    EXISTS (
                        SELECT 1 FROM order_products _chk
                        WHERE _chk.equipment_id = equipment.id
                          AND (_chk.delivery_status = 'Pending' OR _chk.pickup_status = 'Pending')
                    ) OR EXISTS (
                        SELECT 1 FROM equipment_soft_assigns _csa
                        INNER JOIN order_products _cop ON _cop.id = _csa.order_product_id
                        WHERE _csa.equipment_id = equipment.id
                          AND (_cop.delivery_status = 'Pending' OR _cop.pickup_status = 'Pending')
                    )
                ) AND current_status = 'maintenance' THEN 1
                WHEN (
                    EXISTS (
                        SELECT 1 FROM order_products _chk
                        WHERE _chk.equipment_id = equipment.id
                          AND (_chk.delivery_status = 'Pending' OR _chk.pickup_status = 'Pending')
                    ) OR EXISTS (
                        SELECT 1 FROM equipment_soft_assigns _csa
                        INNER JOIN order_products _cop ON _cop.id = _csa.order_product_id
                        WHERE _csa.equipment_id = equipment.id
                          AND (_cop.delivery_status = 'Pending' OR _cop.pickup_status = 'Pending')
                    )
                ) AND current_status = 'damaged' THEN 2
                WHEN current_status = 'damaged'     THEN 3
                WHEN current_status = 'maintenance' THEN 4
                WHEN current_status = 'rented'      THEN 5
                WHEN current_status = 'available'   THEN 6
                ELSE 7
            END ASC")
            ->orderByRaw("(
                SELECT MIN(CASE
                    WHEN _op3.delivery_status = 'Pending' THEN _op3.delivery_date
                    WHEN _op3.pickup_status   = 'Pending' THEN _op3.pickup_date
                END)
                FROM order_products _op3
                WHERE _op3.equipment_id = equipment.id
                  AND (_op3.delivery_status = 'Pending' OR _op3.pickup_status = 'Pending')
            ) IS NULL ASC")
            ->orderByRaw("(
                SELECT MIN(CASE
                    WHEN _op3.delivery_status = 'Pending' THEN _op3.delivery_date
                    WHEN _op3.pickup_status   = 'Pending' THEN _op3.pickup_date
                END)
                FROM order_products _op3
                WHERE _op3.equipment_id = equipment.id
                  AND (_op3.delivery_status = 'Pending' OR _op3.pickup_status = 'Pending')
            ) ASC")
            ->orderBy('equipment_name', 'asc');
        } else {
            $query->orderByRaw("CASE current_status
                WHEN 'maintenance' THEN 1
                WHEN 'damaged'     THEN 2
                WHEN 'rented'      THEN 3
                WHEN 'available'   THEN 4
                ELSE 5 END")->orderBy('equipment_name', 'asc');
        }

        $equipment = $query->paginate($perPage);

        // ── Service status (Service Due / Service OverDue) — matches web screen ──
        $settings = DB::table('service_master_settings')->first();
        $pendingBeforeHours = intval($settings->pending_before_hours ?? 20);
        $pendingAfterHours = intval($settings->pending_after_hours ?? 15);

        $serviceRecords = DB::table('equipment_service_tasks')
            ->select('equipment_id', 'service_task_id', 'interval_value')
            ->whereIn('equipment_id', $equipment->getCollection()->pluck('id'))
            ->get()
            ->groupBy(fn($record) => $record->equipment_id . '_' . $record->service_task_id);

        $equipment->getCollection()->each(function ($item) use ($serviceRecords, $pendingBeforeHours, $pendingAfterHours) {
            $item->service_status = EquipmentServiceStatusResolver::resolve($item, $serviceRecords, $pendingBeforeHours, $pendingAfterHours);
        });

        // Service Due / Service OverDue only make sense within the fetched page,
        // same as the web screen (which filters after paginating).
        if ($status === 'Service Due') {
            $equipment->setCollection($equipment->getCollection()->where('service_status', 'pending')->values());
        } elseif ($status === 'Service OverDue') {
            $equipment->setCollection($equipment->getCollection()->where('service_status', 'overdue')->values());
        }

        // ── Rental-ready checklist questions/answers — same data & sort order
        //    as the web screen's get-checklist-questions call for each equipment.
        $checklistResolver = new RentalReadyChecklistQuestionsResolver();

        $equipment->getCollection()->each(function ($item) use ($checklistResolver) {
            $result = $checklistResolver->resolve($item->checklist_master_id, $item->id, $item->current_order_product_id);
            $item->rental_ready_checklist = $result['status'] === 200 ? $result['body'] : null;
        });

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.equipment.rental_ready_equipment_found'),
            'equipment' => ListResource::collection($equipment),
            'pagination' => [
                'current_page' => $equipment->currentPage(),
                'last_page' => $equipment->lastPage(),
                'per_page' => $equipment->perPage(),
                'total' => $equipment->total(),
            ],
        ]);
    }
}
