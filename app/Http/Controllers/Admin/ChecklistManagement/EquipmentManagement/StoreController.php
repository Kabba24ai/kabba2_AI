<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChecklistManagement\EquipmentManagement\StoreRequest;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Services\ChecklistManagement\RentalReadyInspectionException;
use App\Services\ChecklistManagement\RentalReadyInspectionService;
use Illuminate\Support\Facades\Log;

/**
 * Admin web Rental Ready save.
 *
 * Phase 2A: a THIN caller of the canonical, server-authoritative
 * RentalReadyInspectionService. The browser's `equipment_status` and its
 * computed status/counts in `rental_ready_all_qa_json` are NO LONGER trusted —
 * this controller extracts only the raw per-question SELECTIONS (which answer
 * the inspector chose) and the service recomputes everything (snapshot, result,
 * equipment.current_status), guarantees immutability, and wraps it all in one
 * transaction. Web input can no longer directly set equipment status.
 */
class StoreController extends Controller
{
    public function __invoke(StoreRequest $request, RentalReadyInspectionService $service)
    {
        $equipment = Equipment::with('checklistMaster.rentalReadyTemplate')
            ->find($request->input('equipment_id'));
        if (! $equipment) {
            flash('Equipment not found.')->error();

            return redirect()->back()->withInput();
        }

        $inspector = User::find($request->input('inspectorSelect')) ?? auth()->user();

        $qaPayload = json_decode($request->input('rental_ready_all_qa_json'), true);
        if (! is_array($qaPayload)) {
            return back()->withInput()->withErrors(['rental_ready_all_qa_json' => 'Invalid checklist payload.']);
        }

        // Extract ONLY the raw selections (question + chosen answer + note).
        // Everything business-determining (required?, answer type, counts,
        // result, status) is resolved server-side by the service.
        $answers = collect(data_get($qaPayload, 'questions', []))->map(function ($q) {
            $qid = data_get($q, 'id');            // unique_id in the FE payload
            $selId = data_get($q, 'selected_answer.id');

            return [
                'question_id' => data_get($q, 'main_id') ?? (is_numeric($qid) ? $qid : null),
                'question_unique_id' => (! is_numeric($qid)) ? $qid : null,
                'answer_id' => is_numeric($selId) ? $selId : null,
                'answer_unique_id' => data_get($q, 'selected_answer.unique_id') ?? (! is_numeric($selId) ? $selId : null),
                'note' => data_get($q, 'note'),
            ];
        })->all();

        try {
            $result = $service->record(
                equipment: $equipment,
                performedBy: $inspector,
                actor: auth()->user(),
                source: 'web',
                answers: $answers,
                equipmentHours: $request->filled('equipmentHours') ? (int) $request->input('equipmentHours') : null,
                generalNotes: $request->input('general_notes'),
                completionIdempotencyKey: $request->input('completion_idempotency_key'),
                inspectionUuid: $request->input('inspection_uuid'),
            );
        } catch (RentalReadyInspectionException $e) {
            Log::warning('Rental Ready web save rejected', ['code' => $e->errorCode, 'context' => $e->context]);
            flash($e->getMessage())->error();

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            Log::error('CHECKLIST SAVE ERROR', [
                'message' => $e->getMessage(),
                'equipment_id' => $equipment->id,
                'user_id' => auth()->id(),
            ]);
            flash('Something went wrong while saving checklist: ' . $e->getMessage())->error();

            return redirect()->back()->withInput();
        }

        flash($result->wasReplay ? 'Inspection already recorded.' : 'Checklist saved successfully.')->success();

        return redirect()->route('admin.checklist-management.equipment-management.show', $equipment->unique_id);
    }
}
