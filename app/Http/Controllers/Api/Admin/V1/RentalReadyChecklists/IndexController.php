<?php

namespace App\Http\Controllers\Api\Admin\V1\RentalReadyChecklists;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

// Requests
use App\Http\Requests\Api\Admin\V1\RentalReadyChecklists\IndexRequest;
use App\Http\Resources\Api\Admin\V1\EquipmentRentalReadyChecklist\ListResource as EquipmentRentalReadyChecklistListResource;
// Resources
use App\Http\Resources\Api\Admin\V1\RentalReadyChecklistQuestions\ListResource;

// Model
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;

class IndexController extends BaseController
{
    /**
     * Rental Ready Checklist Questions List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $uniqueId = $validated['equipment_unique_id'];

        $equipment = Equipment::with(['store','lastRentalReadyTemplate','orderProduct','checklistMaster.rentalReadyTemplate.templateQuestions.question.answers','checklistMaster.rentalReadyTemplate.templateQuestions.question.category'])->where('unique_id', $uniqueId)->first();

        // PR-B3 (Phase 2, decision D3): the original guard here rejected equipment that
        // was either isRented() OR isAvailable(). The isAvailable() half is confirmed
        // obsolete and has been removed permanently — line ~83 of this same method
        // explicitly treats available equipment as a normal, expected case (it's the
        // very state a piece of equipment is in before its first rental-ready
        // inspection), and SaveController.php's own docblock confirms "Available" is a
        // valid pre-rental-inspection state. Rejecting it here would have been wrong.
        //
        // The isRented() half is kept as observability-only logging, not deleted: the
        // sibling SaveController.php DOES enforce an equivalent live rule on the write
        // path (equipment cannot be inspected while actively rented), so it's plausible
        // this read-only listing endpoint should eventually match that. Log the
        // frequency before deciding — do not reject requests here yet.
        // See docs/checklist-system-audit/PR-B3_VALIDATION_GUARDS.md.
        if ($equipment && $equipment->orderProduct && $equipment->current_status->isRented()) {
            Log::channel('api_errors')->warning('Rental Ready checklist questions listed for currently-rented equipment', [
                'equipment_id'        => $equipment->id,
                'equipment_unique_id' => $uniqueId,
                'order_product_id'    => $equipment->orderProduct->id,
            ]);
        }

        if (isset($equipment->orderProduct)) {
            // find from order product's rental ready checklist if exists
            $questions = optional($equipment->orderProduct->equipmentRentalReadyTemplate?->checklistQuestions)
                        ->pluck('rental_ready_qa_json')   // same as map->question but clearer
                        ->filter()            // remove nulls
                        ->values() ?? collect();

            $questions = collect($questions)->map(function ($item) {
                            return is_string($item) ? json_decode($item, true) : $item; // decode to array
                        });

        }else{
            // find from equipment rental ready checklist if exists
            if (!$equipment || !$equipment->checklistMaster?->rental_ready_template_id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => trans('messages.api.admin.v1.rental_ready_checklists.no_rental_ready_checklist_found'),
                    ],
                    JsonResponse::HTTP_NOT_FOUND,
                );
            }

            $questions = optional($equipment->checklistMaster?->rentalReadyTemplate?->templateQuestions)
                        ->pluck('question')   // same as map->question but clearer
                        ->filter()            // remove nulls
                        ->values() ?? collect();
        }

        if ($questions->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.rental_ready_checklists.no_questions_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        if($equipment->current_status->isAvailable()){
            $equipmentRentalReadyData = null;
        }else{
            $equipmentRentalReadyData = $equipment->lastRentalReadyTemplate;
        }


        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.rental_ready_checklists.rental_ready_checklist_found'),
            'equipment_rental_ready' => new EquipmentRentalReadyChecklistListResource($equipmentRentalReadyData),
            'rental_ready_checklist_questions' => ListResource::collection($questions),
        ]);
    }
}
