<?php

namespace App\Http\Controllers\Api\Admin\V1\Equipment;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Equipment\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Equipment\ListResource;

// Model
use App\Models\MaintenanceManagement\Equipment;

class IndexController extends BaseController
{
    /**
     * Equipment List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = $validated['type'] ?? null;

        // that is use for the ordering of the equipment based on the type
        if($type === 'RentalReady'){
            $order = ['damaged', 'maintenance', 'rented', 'available'];
        }else if($type === 'Checklist'){
            $order = ['available', 'rented', 'maintenance', 'damaged'];
        }else{
            $order = ['damaged', 'maintenance', 'rented', 'available'];
        }

        $search            = $validated['search'] ?? null;
        $searchById        = $validated['search_by_id'] ?? null;
        $currentlyAssigned = isset($validated['currently_assigned'])
                                ? ($validated['currently_assigned'] !== '0')
                                : null; // null = not sent, use original FIELD sort

        $query = Equipment::with(['store', 'productCategory', 'orderProduct','checklistMaster.customerAdminTemplate.templateQuestions.question.answers','checklistMaster.customerAdminTemplate.templateQuestions.question.category','orderProduct.checklistQuestions.answers', 'orderProduct.checklistQuestions.deliverySelectedAnswer', 'orderProduct.checklistQuestions.returnSelectedAnswer', 'softAssignments','checklistMaster.rentalReadyTemplate.templateQuestions.question.answers','checklistMaster.rentalReadyTemplate.templateQuestions.question.category', 'orderProduct.equipmentRentalReadyTemplate.checklistQuestions']);

        // ── Filter: search by name / model / serial number ───────────────────
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('equipment_name', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        // ── Filter: search by Equipment ID ───────────────────────────────────
        if ($searchById) {
            $query->where('equipment_id', 'like', "%{$searchById}%");
        }

        // ── Filter: store location ────────────────────────────────────────────
        if (!empty($validated['store_id'])) {
            $query->where('store_id', $validated['store_id']);
        }

        // ── Sort order ───────────────────────────────────────────────────────
        if ($currentlyAssigned === true) {
            // 6-tier revenue-protection priority (same as web checklist page):
            //  1. Currently Assigned + Maint. Hold
            //  2. Currently Assigned + Damaged
            //  3. Damaged  (no pending order)
            //  4. Maint. Hold (no pending order)
            //  5. Rented
            //  6. Available
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
            ->orderBy('equipment_name', 'ASC');
        } else {
            // Original sort — unchanged
            $query->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')")
                  ->orderBy('equipment_name', 'ASC');
        }

        $equipment = $query->get();


        $equipment->map(function($item) {
            $isRentedAndDelivered = $item->current_status->isRented()
                && $item->orderProduct
                && ($item->orderProduct->is_delivered == 1);

            if ($isRentedAndDelivered) {
                $questions = optional($item->orderProduct->checklistQuestions) ?? collect();

                $rentalReadyQuestions = collect(
                                            $item->orderProduct->equipmentRentalReadyTemplate?->checklistQuestions
                                        )
                                            ->pluck('rental_ready_qa_json')
                                            ->filter()
                                            ->map(fn ($item) => is_string($item) ? json_decode($item, true) : $item)
                                            ->values();

            } else {
                $templateQuestions = $item->checklistMaster?->customerAdminTemplate?->templateQuestions;
                $questions = collect($templateQuestions)
                    ->pluck('question')
                    ->filter()
                    ->values();

                $rentalReadyQuestions = $item->checklistMaster?->rentalReadyTemplate?->templateQuestions;
                $rentalReadyQuestions = collect($rentalReadyQuestions)
                    ->pluck('question')   // same as map->question but clearer
                    ->filter()            // remove nulls
                    ->values() ?? collect();

            }

            $item->setRelation('checklistQA', $questions);
            $item->setRelation('rentalReadyQA', $rentalReadyQuestions);
            return $item;
        });
        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.equipment.equipment_found'),
            'equipment' => ListResource::collection($equipment),
        ]);
    }
}
