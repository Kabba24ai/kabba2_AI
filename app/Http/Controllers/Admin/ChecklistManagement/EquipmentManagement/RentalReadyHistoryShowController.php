<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Http\Request;

/**
 * Phase 2B — read-only Rental Ready inspection DETAIL.
 *
 * A faithful historical reconstruction: every answer is rendered from the
 * inspection's OWN immutable snapshot (rental_ready_qa_json on each child row),
 * NEVER the current checklist master — so it stays correct after questions,
 * sections, or answer options are edited later. Lifecycle and result are shown
 * separately; incomplete drafts are clearly labelled.
 */
class RentalReadyHistoryShowController extends Controller
{
    public function __invoke(Request $request, string $equipment, string $template)
    {
        $equipmentModel = Equipment::where('unique_id', $equipment)->firstOrFail();

        $inspection = EquipmentRentalReadyTemplate::query()
            ->where('unique_id', $template)
            ->where('equipment_id', $equipmentModel->id)
            ->with(['employee', 'checklistQuestions', 'orderProduct.order:id,order_number', 'order:id,order_number'])
            ->firstOrFail();

        // Reconstruct the ordered answer list from each child row's frozen
        // snapshot. Fall back to the row's own columns if a legacy row lacks a
        // structured blob. Group by the snapshot's section (never the live master).
        $questions = $inspection->checklistQuestions->map(function ($row) {
            $snap = json_decode($row->rental_ready_qa_json ?? '', true);
            $snap = is_array($snap) ? $snap : [];

            return [
                'question_name' => $snap['question_name'] ?? '(question text not recorded)',
                'section_name' => $snap['section_name'] ?? null,
                'section_id' => $snap['section_id'] ?? ($snap['category_id'] ?? null),
                'question_order' => $snap['question_order'] ?? null,
                'required_question' => (bool) ($snap['required_question'] ?? false),
                'answers' => $snap['answers'] ?? [],
                'selected_answer' => $snap['selected_answer'] ?? null,
                'note' => $snap['note'] ?? ($row->general_notes ?? ''),
            ];
        })
            ->sortBy(fn ($q) => $q['question_order'] ?? PHP_INT_MAX)
            ->values();

        // Section-grouped (preserving first-seen order), from the SNAPSHOT.
        $sections = $questions
            ->groupBy(fn ($q) => $q['section_name'] ?? 'Uncategorized')
            ->map(fn ($group) => $group->values());

        $orderNumber = $inspection->orderProduct?->order?->order_number
            ?? $inspection->order?->order_number;

        return view('admin.checklist_management.equipment_management.rental_ready_inspection_detail', [
            'equipment' => $equipmentModel,
            'inspection' => $inspection,
            'sections' => $sections,
            'questionCount' => $questions->count(),
            'orderNumber' => $orderNumber,
        ]);
    }
}
