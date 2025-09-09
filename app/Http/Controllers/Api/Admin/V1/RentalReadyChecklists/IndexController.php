<?php

namespace App\Http\Controllers\Api\Admin\V1\RentalReadyChecklists;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

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
        $uniqueId = $validated['order_product_unique_id'];

        $orderProduct = OrderProduct::query()
            ->with(['equipmentRentalReadyTemplate','equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.answers','equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.category'])
            ->where('unique_id', $uniqueId)
            ->first();

        if (isset($orderProduct->equipmentRentalReadyTemplate)) {

            $questions = optional($orderProduct->equipmentRentalReadyTemplate->checklistQuestions)
                        ->pluck('rental_ready_qa_json')   // same as map->question but clearer
                        ->filter()            // remove nulls
                        ->values() ?? collect();

            $questions = collect($questions)->map(function ($item) {
                            return is_string($item) ? json_decode($item, true) : $item; // decode to array
                        });
        }else{
            if (!$orderProduct->equipment || !$orderProduct->equipment->checklistMaster?->rental_ready_template_id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => trans('messages.api.admin.v1.rental_ready_checklists.no_rental_ready_checklist_found'),
                    ],
                    JsonResponse::HTTP_NOT_FOUND,
                );
            }

            $questions = optional($orderProduct->equipment->checklistMaster?->rentalReadyTemplate?->templateQuestions)
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

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.rental_ready_checklists.rental_ready_checklist_found'),
            'equipment_rental_ready' => new EquipmentRentalReadyChecklistListResource($orderProduct->equipmentRentalReadyTemplate ?? []),
            'rental_ready_checklist_questions' => ListResource::collection($questions),
        ]);
    }
}
