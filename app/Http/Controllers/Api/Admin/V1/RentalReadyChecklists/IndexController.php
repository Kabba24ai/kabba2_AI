<?php

namespace App\Http\Controllers\Api\Admin\V1\RentalReadyChecklists;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\RentalReadyChecklists\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\RentalReadyChecklistQuestions\ListResource;

// Model
use App\Models\MaintenanceManagement\Equipment;

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

        $equipment = Equipment::query()
            ->with(['checklistMaster.rentalReadyTemplate.templateQuestions.question.answers','checklistMaster.rentalReadyTemplate.templateQuestions.question.category'])
            ->where('unique_id', $uniqueId)
            ->first();

        if (!$equipment || !$equipment->checklistMaster?->rental_ready_template_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.rental_ready_checklists.no_rental_ready_checklist_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $questions = optional($equipment->checklistMaster?->rentalReadyTemplate?->templateQuestions)->map->question->values() ?? collect();

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
            'rental_ready_checklist_questions' => ListResource::collection($questions),
        ]);
    }
}
