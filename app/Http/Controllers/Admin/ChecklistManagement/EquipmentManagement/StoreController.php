<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestionLog;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Iam\Personnel\User;
use App\Helpers\CustomHelper;
use App\Services\ChecklistManagement\RentalReadyCompletionCalculator;
use Illuminate\Http\Request;

use App\Http\Requests\Admin\ChecklistManagement\EquipmentManagement\StoreRequest;



class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        // Fetch the inspection user
        $inspectionUser = User::find($request->input('inspectorSelect'));
        $status = $request->input('equipment_status') ?? 'maintenance';

        $templateStatus = match ($status) {
            'available'   => 'Rental Ready',
            'damaged'     => 'Damaged',
            'maintenance' => 'Draft',
            default       => 'Draft',
        };

        // Start transaction
        DB::beginTransaction();

        try {
            // Decode payload
            $qaPayload = json_decode($request->input('rental_ready_all_qa_json'), true);
            if (!is_array($qaPayload)) {
                // Log::error('QA payload is invalid JSON', ['payload' => $request->input('rental_ready_all_qa_json')]);
                return back()->withInput()->withErrors(['rental_ready_all_qa_json' => 'Invalid checklist payload.']);
            }

            $counts = [
                'total_questions' => data_get($qaPayload, 'counts.total_questions', 0),
                'required_questions' => data_get($qaPayload, 'counts.required_questions', 0),
                'optional_questions' => data_get($qaPayload, 'counts.optional_questions', 0),
                'required_items_completed' => data_get($qaPayload, 'counts.required_items_completed', 0),
                'items_requiring_maintenance' => data_get($qaPayload, 'counts.items_requiring_maintenance', 0),
                'damaged_items' => data_get($qaPayload, 'counts.damaged_items', 0),
            ];

            // Normalize date/time using your helper
            $inspectionDate = $request->filled('inspection_date')
                ? CustomHelper::parseDateFromInput($request->input('inspection_date'))
                : now()->toDateString();

            $inspectionTime = CustomHelper::formatTime(now(), 'H:i:s');

            // Start base query
            $query = EquipmentRentalReadyTemplate::where('equipment_id', $request->input('equipment_id'));

            $orderProductId = data_get($qaPayload, 'order_product_id'); // no default
            $orderId = data_get($qaPayload, 'order_id'); // no default



            if ($orderProductId !== null && $orderProductId !== '') {
                // If order_product_id exists, filter by it
                $query->where('order_product_id', $orderProductId);
            } else {
                // If it's missing/null, filter for null in DB
                $query->whereNull('order_product_id');
            }

            $existingTemplate = $query->latest('id')->first();


            if ($existingTemplate && $existingTemplate->status !== 'Rental Ready') {
                // Update existing (Draft/Damaged)
                $existingTemplate->update([
                    'employee_id' => $request->input('inspectorSelect'),
                    'employee_name' => optional($inspectionUser)->full_name,
                    'inspection_date' => $inspectionDate,
                    'inspection_time' => $inspectionTime,
                    'equipment_hours' => $request->input('equipmentHours'),
                    'general_notes' => $request->input('general_notes'),
                    'status' => $templateStatus,
                    'total_questions' => $counts['total_questions'],
                    'required_questions' => $counts['required_questions'],
                    'optional_questions' => $counts['optional_questions'],
                    'required_items_completed' => $counts['required_items_completed'],
                    'items_requiring_maintenance' => $counts['items_requiring_maintenance'],
                    'damaged_items' => $counts['damaged_items'],
                    'updated_by' => auth()->id(),
                ]);





                $template = $existingTemplate;

                // Update/create questions
                foreach ((array) data_get($qaPayload, 'questions', []) as $index => $q) {

                    $qidRaw = data_get($q, 'id'); // could be numeric id or unique_id from FE

                    if (empty($qidRaw)) {
                        Log::warning('Skipping question with empty id and main_id', ['index' => $index, 'q' => $q]);
                        continue;
                    }

                    // Resolve the real DB question ID
                    $questionModel = null;
                    if (is_numeric($qidRaw)) {
                        $questionModel = RentalReadyChecklistQuestion::find((int)$qidRaw);
                    }
                    if (!$questionModel) {
                        $questionModel = RentalReadyChecklistQuestion::where('unique_id', $qidRaw)->orWhere('id', $qidRaw)->first();
                    }
                    if (!$questionModel) {
                        Log::warning('No matching RentalReadyChecklistQuestion found for payload id', ['qidRaw' => $qidRaw, 'question' => $q]);
                        continue;
                    }

                    $questionDbId = $questionModel->id;

                    // Resolve selected answer id (if provided)
                    $selectedRaw = data_get($q, 'selected_answer.id');
                    $selectedAnswerId = null;
                    if (!empty($selectedRaw)) {
                        if (is_numeric($selectedRaw)) {
                            $answerModel = RentalReadyChecklistQuestionAnswer::find((int)$selectedRaw);
                        } else {
                            $answerModel = RentalReadyChecklistQuestionAnswer::where('unique_id', $selectedRaw)
                                ->orWhere('id', $selectedRaw)
                                ->first();
                        }

                        if ($answerModel) {
                            $selectedAnswerId = $answerModel->id;
                        } else {
                            Log::warning('Selected answer not found for payload', ['selectedRaw' => $selectedRaw, 'q' => $q]);
                        }
                    }

                    $updData = [
                        'selected_answer_id' => $selectedAnswerId,
                        'rental_ready_qa_json' => json_encode($q),
                        'general_notes' => data_get($q, 'note'),
                    ];

                    $question = EquipmentRentalReadyChecklistQuestion::updateOrCreate(
                        [
                            'equipment_rental_ready_template_id' => $template->id,
                            'rental_ready_checklist_questions_id' => $questionDbId,
                        ],
                        $updData
                    );

                }

                // Consolidated log after processing all questions
                EquipmentRentalReadyChecklistQuestionLog::create([
                    'equipment_rental_ready_template_id' => $template->id,
                    'rental_ready_all_qa_json' => json_encode($qaPayload),
                    'action_by' => auth()->id(),
                    'action_user_name' => optional(auth()->user())->full_name,
                    'inspection_date' => $inspectionDate ?? null,
                    'equipment_hours' => $request->input('equipmentHours') ?? null,
                    'inspector_name' => optional($inspectionUser)->full_name ?? null,
                ]);

                Log::info('Created consolidated checklist log', [
                    'template_id' => $template->id,
                    'questions_count' => count(data_get($qaPayload, 'questions', []))
                ]);

            } else {
                // Create new template. If existing was Rental Ready, delete it first (and its questions/logs)
                if ($existingTemplate && $existingTemplate->status === 'Rental Ready') {
                    // Log::info('Removing previous Rental Ready template', ['template_id' => $existingTemplate->id]);

                        EquipmentRentalReadyTemplate::where('equipment_id', $request->input('equipment_id'))
                            ->where('status', 'Rental Ready')
                            ->update([
                                'is_complete' => 1,
                                'updated_by' => auth()->id(),
                            ]);
                }

                $template = EquipmentRentalReadyTemplate::create([
                    'equipment_id' => $request->input('equipment_id'),
                    'employee_id' => $request->input('inspectorSelect'),
                    'employee_name' => optional($inspectionUser)->full_name,
                    'order_product_id' => $orderProductId,
                    'order_id' => $orderId,
                    'inspection_date' => $inspectionDate,
                    'inspection_time' => $inspectionTime,
                    'equipment_hours' => $request->input('equipmentHours'),
                    'general_notes' => $request->input('general_notes'),
                    'status' => $templateStatus,
                    'total_questions' => $counts['total_questions'],
                    'required_questions' => $counts['required_questions'],
                    'optional_questions' => $counts['optional_questions'],
                    'required_items_completed' => $counts['required_items_completed'],
                    'items_requiring_maintenance' => $counts['items_requiring_maintenance'],
                    'damaged_items' => $counts['damaged_items'],
                    'created_by' => auth()->id(),
                ]);


                foreach ((array) data_get($qaPayload, 'questions', []) as $index => $q) {
                    $qidRaw = data_get($q, 'id');      // unique_id
                    $mainId = data_get($q, 'main_id'); // numeric id

                    $questionModel = null;

                    if (empty($qidRaw)) {
                        continue;
                    }


                    if ($mainId) {
                        $questionModel = RentalReadyChecklistQuestion::find((int) $mainId);
                    }

                    if (!$questionModel && $qidRaw) {
                        $questionModel = RentalReadyChecklistQuestion::where('unique_id', $qidRaw)
                            ->orWhere('id', $qidRaw)
                            ->first();
                    }

                    if (!$questionModel) {
                        continue;
                    }

                    $questionDbId = $questionModel->id;

                    // map selected answer
                    $selectedRaw = data_get($q, 'selected_answer.id');
                    $selectedAnswerId = null;
                    if (!empty($selectedRaw)) {
                        if (is_numeric($selectedRaw)) {
                            $answerModel = RentalReadyChecklistQuestionAnswer::find((int)$selectedRaw);
                        } else {
                            $answerModel = RentalReadyChecklistQuestionAnswer::where('unique_id', $selectedRaw)->orWhere('id', $selectedRaw)->first();
                        }
                        if ($answerModel) {
                            $selectedAnswerId = $answerModel->id;
                        } else {
                            Log::warning('Selected answer not found for payload (create path)', ['selectedRaw' => $selectedRaw, 'q' => $q]);
                        }
                    }

                    $question = EquipmentRentalReadyChecklistQuestion::create([
                        'equipment_rental_ready_template_id' => $template->id,
                        'rental_ready_checklist_questions_id' => $questionDbId,
                        'selected_answer_id' => $selectedAnswerId,
                        'rental_ready_qa_json' => json_encode($q),
                        'general_notes' => data_get($q, 'note'),
                    ]);

                    // Log::info('Created checklist question', ['checklist_question_id' => $question->id, 'question_db_id' => $questionDbId]);

                }

                // Consolidated log after processing all questions
                EquipmentRentalReadyChecklistQuestionLog::create([
                    'equipment_rental_ready_template_id' => $template->id,
                    'rental_ready_all_qa_json' => json_encode($qaPayload),
                    'action_by' => auth()->id(),
                    'action_user_name' => optional(auth()->user())->full_name,
                    'inspection_date' => $inspectionDate ?? null,
                    'equipment_hours' => $request->input('equipmentHours') ?? null,
                    'inspector_name' => optional($inspectionUser)->full_name ?? null,
                ]);

                Log::info('Created consolidated checklist log', [
                    'template_id' => $template->id,
                    'questions_count' => count(data_get($qaPayload, 'questions', []))
                ]);
            }

            // Update equipment status
            if ($equipment = Equipment::find($request->input('equipment_id'))) {
                $equipment->update([
                    'current_status' => match ($status) {
                        'available' => 'available',
                        'damaged' => 'damaged',
                        'maintenance' => 'maintenance',
                    },
                    'updated_by' => auth()->id(),
                    'current_status_changed_at' => now(),
                    'current_status_updated_by' => $request->input('inspectorSelect'),
                    'equipment_hours' => $request->input('equipmentHours'),
                ]);
                Log::info('Equipment status updated', ['equipment_id' => $equipment->id, 'current_status' => $equipment->current_status]);

                // PR-B2 Stage 3 (Phase 2, D2: observability-first): compute the
                // server-trusted completion result and log a warning when it disagrees
                // with what the client submitted. Observation only — does not change
                // $templateStatus/$counts (still persisted as submitted, above/below)
                // and does not route through EquipmentStatusService. See
                // docs/checklist-system-audit/PR-B2_STAGE3_ADMIN_OBSERVABILITY.md.
                $this->logCompletionMismatchIfAny($equipment, $qaPayload, $templateStatus, $status, $counts);
            } else {
                Log::warning('Equipment not found when updating status', ['equipment_id' => $request->input('equipment_id')]);
            }

            DB::commit();

            Log::info('CHECKLIST SAVE COMMIT', ['template_id' => $template->id]);

            flash('Checklist saved successfully.')->success();
            // return redirect()->route('admin.checklist-management.equipment-management.index');

            // {{ route('admin.checklist-management.equipment-management.show', $eq->unique_id) }}
            return redirect()->route('admin.checklist-management.equipment-management.show', $equipment->unique_id);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CHECKLIST SAVE ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'equipment_id' => $request->input('equipment_id'),
                'payload_sample' => substr($request->input('rental_ready_all_qa_json'), 0, 2000),
            ]);

            flash('Something went wrong while saving checklist: ' . $e->getMessage())->error();
            return redirect()->back()->withInput();
        }
    }

    /**
     * PR-B2 Stage 3 (D2: observability-first). Computes the server-trusted
     * completion result via RentalReadyCompletionCalculator and logs a structured
     * warning to the api_errors channel when it disagrees with the client-submitted
     * status/counts this controller still persists. Enforcement (actually using
     * $result instead of the submitted values) is deferred to a later stage, per D2.
     *
     * @param  array<string, mixed>  $counts  the client-submitted counts this
     *         controller persists today (unchanged by this method).
     */
    private function logCompletionMismatchIfAny(
        Equipment $equipment,
        array $qaPayload,
        string $submittedTemplateStatus,
        string $submittedEquipmentStatus,
        array $counts
    ): void {
        $normalizedQuestions = $this->resolveNormalizedQuestionsFromPayload($qaPayload);

        $result = app(RentalReadyCompletionCalculator::class)->calculate($normalizedQuestions);

        $statusMismatch = $submittedTemplateStatus !== $result->status;

        $mismatchedCountFields = collect($result->counts)
            ->filter(fn ($computedValue, $key) => (int) ($counts[$key] ?? 0) !== (int) $computedValue)
            ->keys()
            ->all();

        if (!$statusMismatch && empty($mismatchedCountFields)) {
            return;
        }

        Log::channel('api_errors')->warning('Admin Rental Ready submission disagrees with server-computed completion result', [
            'equipment_id'                 => $equipment->id,
            'equipment_unique_id'          => $equipment->unique_id,
            'submitted_equipment_status'   => $submittedEquipmentStatus,
            'submitted_template_status'    => $submittedTemplateStatus,
            'computed_status'              => $result->status,
            'submitted_counts'             => $counts,
            'computed_counts'              => $result->counts,
            'mismatched_count_fields'      => $mismatchedCountFields,
            'actor_id'                     => auth()->id(),
        ]);
    }

    /**
     * Builds the calculator's normalized input from the real DB models — never from
     * the submitted JSON's own 'required'/'status' fields — per PR-B2's D2 principle
     * that business-determining facts (is this question required? what type is the
     * selected answer?) must be resolved server-side, not trusted from the client.
     *
     * @return array<int, array{required_question: bool, selected_answer: array{type: string}|null}>
     */
    private function resolveNormalizedQuestionsFromPayload(array $qaPayload): array
    {
        $normalized = [];

        foreach ((array) data_get($qaPayload, 'questions', []) as $q) {
            $qidRaw = data_get($q, 'id');
            $mainId = data_get($q, 'main_id');

            $questionModel = null;
            if ($mainId) {
                $questionModel = RentalReadyChecklistQuestion::find((int) $mainId);
            }
            if (!$questionModel && $qidRaw) {
                $questionModel = RentalReadyChecklistQuestion::where('unique_id', $qidRaw)
                    ->orWhere('id', $qidRaw)
                    ->first();
            }
            if (!$questionModel) {
                continue;
            }

            $selectedRaw = data_get($q, 'selected_answer.id');
            $answerModel = null;
            if (!empty($selectedRaw)) {
                $answerModel = is_numeric($selectedRaw)
                    ? RentalReadyChecklistQuestionAnswer::find((int) $selectedRaw)
                    : RentalReadyChecklistQuestionAnswer::where('unique_id', $selectedRaw)->orWhere('id', $selectedRaw)->first();
            }

            $normalized[] = [
                'required_question' => (bool) $questionModel->required_question,
                'selected_answer'   => $answerModel ? ['type' => $answerModel->type] : null,
            ];
        }

        return $normalized;
    }
}
