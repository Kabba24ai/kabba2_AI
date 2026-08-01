<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\RentalReadyChecklists;

use App\Enums\Api\ApiErrorCode;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Admin\V1\Orders\RentalReadyChecklists\SaveRequest;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Services\ChecklistManagement\RentalReadyInspectionException;
use App\Services\ChecklistManagement\RentalReadyInspectionService;
use Illuminate\Http\JsonResponse;

/**
 * Order Rental Ready Checklist Save (mobile).
 *
 * Phase 2A: a THIN caller of the canonical, server-authoritative
 * RentalReadyInspectionService — which owns validation, snapshot, result
 * calculation, equipment.current_status, immutability, transaction, and
 * idempotency. Nothing here trusts the device for the result or status.
 *
 * @group Admin App
 * @authenticated
 */
class SaveController extends BaseController
{
    public function __invoke(SaveRequest $request, RentalReadyInspectionService $service): JsonResponse
    {
        $validated = $request->validated();

        $equipment = Equipment::with('checklistMaster.rentalReadyTemplate')
            ->where('unique_id', $validated['equipment_unique_id'])->first();
        if (! $equipment) {
            return ApiResponseHelper::error(ApiErrorCode::EquipmentNotFound, [], [
                'equipment_unique_id' => $validated['equipment_unique_id'],
            ]);
        }

        $employee = User::find($validated['user_id']);
        if (! $employee) {
            return ApiResponseHelper::error(ApiErrorCode::UserNotFound, [], [
                'equipment_id' => $equipment->id,
                'user_id' => $validated['user_id'],
            ]);
        }

        $answers = collect($validated['checklist'] ?? [])->map(fn ($c) => [
            'question_unique_id' => $c['question_unique_id'] ?? null,
            'answer_unique_id' => $c['answer_unique_id'] ?? null,
            'note' => $c['note'] ?? null,
        ])->all();

        try {
            $result = $service->record(
                equipment: $equipment,
                performedBy: $employee,
                actor: auth()->user(),
                source: 'mobile',
                answers: $answers,
                equipmentHours: isset($validated['equipment_hours']) ? (int) $validated['equipment_hours'] : null,
                generalNotes: $validated['general_notes'] ?? null,
                completionIdempotencyKey: $validated['completion_idempotency_key'] ?? null,
                inspectionUuid: $validated['inspection_uuid'] ?? null,
            );
        } catch (RentalReadyInspectionException $e) {
            return ApiResponseHelper::error($this->mapErrorCode($e), [], array_merge($e->context, [
                'equipment_id' => $equipment->id,
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
            'inspection' => [
                'uuid' => $result->template->unique_id,
                'lifecycle_status' => $result->lifecycle->value,
                'result' => $result->result?->value,
                'was_replay' => $result->wasReplay,
            ],
        ]);
    }

    private function mapErrorCode(RentalReadyInspectionException $e): ApiErrorCode
    {
        return match ($e->errorCode) {
            'EQUIPMENT_RENTED' => ApiErrorCode::EquipmentCurrentlyRented,
            'NO_CHECKLIST' => ApiErrorCode::NoChecklistFound,
            'NO_QUESTIONS' => ApiErrorCode::NoQuestionsFound,
            'INSPECTION_FINALIZED' => ApiErrorCode::ChecklistAlreadySubmitted,
            default => ApiErrorCode::InvalidEquipmentStatus,
        };
    }
}
