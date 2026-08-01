<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Enums\ChecklistManagement\RentalReadyResult;
use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 3A — read-only data source for the workspace's RIGHT panel
 * (latest-completed-inspection comparison).
 *
 * Returns ONLY the latest COMPLETED, non-voided inspection for a unit — the
 * same authoritative record the operational readers resolve (a draft is never
 * returned here, so an in-progress inspection can never masquerade as the
 * previous result). Answers come from each row's frozen snapshot
 * (rental_ready_qa_json), NEVER the live master, so the comparison stays
 * correct after the checklist is later edited. Each question carries its stable
 * master question id so the client can align current-vs-previous by identity.
 *
 * Read-only: no writes, no lifecycle transitions, never touches equipment
 * status. Scoped to its equipment (the equipment 404s on a bad unit; a null
 * inspection is a normal 200 for a never-inspected unit).
 */
class RentalReadyLatestCompletedController extends Controller
{
    public function __invoke(Request $request, string $equipment): JsonResponse
    {
        $equipmentModel = Equipment::where('unique_id', $equipment)->firstOrFail();

        // The authoritative previous inspection: latest COMPLETED, non-voided.
        // (A completed row can never also be voided — lifecycle_status is a
        // single value — so `completed` already excludes voided.)
        $inspection = EquipmentRentalReadyTemplate::query()
            ->where('equipment_id', $equipmentModel->id)
            ->completed()
            ->with(['employee:id,first_name,last_name', 'checklistQuestions', 'orderProduct.order:id,order_number', 'order:id,order_number'])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();

        if (! $inspection) {
            return response()->json([
                'success' => true,
                'inspection' => null,
            ]);
        }

        // Exactly the fields the comparison panel renders — no notes, no audit
        // columns, no raw model attributes. Sorted by the snapshot's own order,
        // then that ordering key is dropped from the payload.
        $questions = $inspection->checklistQuestions
            ->map(function ($row) {
                $snap = json_decode($row->rental_ready_qa_json ?? '', true);
                $snap = is_array($snap) ? $snap : [];
                $selected = $snap['selected_answer'] ?? null;

                return [
                    // Stable master question id — the PRIMARY alignment key. Kept
                    // numeric when present so it matches the current form's
                    // `main_id`. The unique_id is a secondary key so a RESUMED
                    // draft (whose current items key off the question unique_id)
                    // still aligns.
                    'question_id' => $snap['question_id'] ?? null,
                    'question_unique_id' => $snap['unique_id'] ?? null,
                    'question_name' => $snap['question_name'] ?? '(question text not recorded)',
                    'selected_answer_name' => $selected['answer_name'] ?? null,
                    'selected_answer_type' => $selected['type'] ?? null,
                    '_order' => $snap['question_order'] ?? PHP_INT_MAX,
                ];
            })
            ->sortBy('_order')
            ->map(fn ($q) => \Illuminate\Support\Arr::except($q, ['_order']))
            ->values();

        $result = $inspection->result instanceof RentalReadyResult ? $inspection->result : null;
        $orderNumber = $inspection->orderProduct?->order?->order_number
            ?? $inspection->order?->order_number;

        return response()->json([
            'success' => true,
            'inspection' => [
                'unique_id' => $inspection->unique_id,
                'result' => $result?->value,
                'result_label' => $result?->label(),
                'inspector' => $inspection->employee_name ?: optional($inspection->employee)->full_name,
                'equipment_hours' => $inspection->equipment_hours,
                'completed_at' => $inspection->completed_at?->format('M j, Y g:i A'),
                'order_number' => $orderNumber,
                'detail_url' => route('admin.checklist-management.equipment-management.rental-ready-history.show', [$equipmentModel->unique_id, $inspection->unique_id]),
                'history_url' => route('admin.checklist-management.equipment-management.rental-ready-history', $equipmentModel->unique_id),
                'questions' => $questions,
            ],
        ]);
    }
}
