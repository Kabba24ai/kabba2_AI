<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\RentalReadyChecklists;

use App\Enums\Api\ApiErrorCode;
use App\Services\Equipment\EquipmentStatusService;
use App\Services\ChecklistManagement\RentalReadyCompletionCalculator;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\RentalReadyChecklists\SaveRequest;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestionLog;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
// Resources

// Model
use App\Models\Orders\OrderProduct;

class SaveController extends BaseController
{
    /**
     * Order Rental Ready Checklist Save
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(SaveRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $uniqueId = $validated['equipment_unique_id'];

        $equipment = Equipment::with(['lastRentalReadyTemplate','orderProduct','checklistMaster.rentalReadyTemplate.templateQuestions.question.answers','checklistMaster.rentalReadyTemplate.templateQuestions.question.category'])->where('unique_id', $uniqueId)->first();

        // Rental Ready cannot overwrite equipment status while it is actively rented to a customer.
        // Available, Maintenance, and Damaged are all valid states for a pre-rental inspection.
        if ($equipment && $equipment->current_status->isRented()) {
            return ApiResponseHelper::error(ApiErrorCode::EquipmentCurrentlyRented, [], [
                'equipment_id'             => $equipment->id,
                'current_equipment_status' => $equipment->current_status->value,
            ]);
        }

        if (isset($equipment->orderProduct->equipmentRentalReadyTemplate)) {
            // fetch questions from order products equipment template
            $questions = optional($equipment->orderProduct->equipmentRentalReadyTemplate->checklistQuestions)
                        ->pluck('rental_ready_qa_json')   // same as map->question but clearer
                        ->filter()            // remove nulls
                        ->values() ?? collect();

            $questions = collect($questions)->map(function ($item) {
                            return is_string($item) ? json_decode($item, true) : $item; // decode to array
                        });
        }else{
            // fetch questions from equipment's checklist master rental ready template
            if (!$equipment || !$equipment->checklistMaster?->rental_ready_template_id) {
                return ApiResponseHelper::error(ApiErrorCode::NoChecklistFound, [], [
                    'equipment_id' => $equipment?->id,
                ]);
            }

            $questions = optional($equipment->checklistMaster?->rentalReadyTemplate?->templateQuestions)
                        ->pluck('question')   // same as map->question but clearer
                        ->filter()            // remove nulls
                        ->values() ?? collect();
        }


        if ($questions->isEmpty()) {
            return ApiResponseHelper::error(ApiErrorCode::NoQuestionsFound, [], [
                'equipment_id' => $equipment->id,
            ]);
        }

        $employee = User::where('id', $validated['user_id'])->first();
        if (!$employee) {
            return ApiResponseHelper::error(ApiErrorCode::UserNotFound, [], [
                'equipment_id' => $equipment->id,
                'user_id'      => $validated['user_id'],
            ]);
        }

        $validatedChecklist = $validated['checklist'] ?? [];
        $validatedChecklist = collect($validatedChecklist)->keyBy('question_unique_id')->toArray();

        $newQuestions = [];
        foreach ($questions as $key => $question) {
            if(is_array($question)){
                $questionUniqueId = $question['unique_id'] ?? null;
                $newQuestions[$key] = $question;
                $newQuestions[$key]['note'] = $validatedChecklist[$questionUniqueId]['note'] ?? '';
                if(isset($questionUniqueId) && isset($validatedChecklist[$questionUniqueId])){
                   foreach($newQuestions[$key]['answers'] as &$ans){
                        if($ans['unique_id'] == $validatedChecklist[$questionUniqueId]['answer_unique_id']){
                            $ans['is_selected'] = true;
                            $answer = $ans;
                        }else{
                            $ans['is_selected'] = false;
                        }
                    }
                    unset($ans);
                    $newQuestions[$key]['selected_answer'] = $answer ?? null;
                }else{
                    $newQuestions[$key]['selected_answer'] = null;
                }
            }else{
                $existsQuestion = isset($question->unique_id) && isset($validatedChecklist[$question->unique_id]);
                // if question is not an array, keep it as is
                $newQuestions[$key]['id'] = $question->id ?? null;
                $newQuestions[$key]['unique_id'] = $question->unique_id ?? null;
                $newQuestions[$key]['question_name'] = $question->question_name ?? null;
                $newQuestions[$key]['category_id'] = $question->category_id ?? null;
                $newQuestions[$key]['required_question'] = (bool)$question->required_question ?? false;
                $newQuestions[$key]['note'] = $existsQuestion ? $validatedChecklist[$question->unique_id]['note'] ?? '' : '';
                $newQuestions[$key]['answers'] = $question->answers->map(function ($answer) use ($question,$validatedChecklist, $existsQuestion) {
                    return [
                        'id' => $answer->id,
                        'type' => $answer->type,
                        'unique_id' => $answer->unique_id,
                        'answer_name' => $answer->answer_name,
                        'is_selected' => ($existsQuestion && $answer->unique_id === $validatedChecklist[$question->unique_id]['answer_unique_id']),
                    ];
                })->toArray();
                $newQuestions[$key]['selected_answer'] = null;

                if(isset($question->unique_id) && isset($validatedChecklist[$question->unique_id])){
                    $answer = collect($newQuestions[$key]['answers'])->firstWhere('unique_id', $validatedChecklist[$question->unique_id]['answer_unique_id']);
                    $newQuestions[$key]['selected_answer'] = $answer ?? null;
                }
            }
        }

        // PR-B2 (Phase 2, Stage 2): completion counts/flags/status are now computed by
        // RentalReadyCompletionCalculator, extracted verbatim from this controller's
        // former inline logic. See docs/checklist-system-audit/PR-B2_CALCULATOR_DESIGN.md.
        $result = app(RentalReadyCompletionCalculator::class)->calculate($newQuestions);

        $counts         = $result->counts;
        $hasDamaged     = $result->hasDamaged;
        $hasMaintenance = $result->hasMaintenance;
        $allRentalReady = $result->allRentalReady;
        $status         = $result->status;

        $logArray = [
            'counts' => $counts,
            'questions' => $newQuestions,
        ];

        // PR-B3 (Phase 2, observability-first per decision D3): this check was written
        // already-disabled when the endpoint was first built (commit edaf9ef4, 2025-09-11)
        // — it has never actually rejected a request in this codebase's history. No
        // evidence was found that it's incorrect, so rather than deleting it, log what
        // it WOULD reject without changing the response. Do not reject requests here —
        // enforcement is a separate future decision gated on reviewing this telemetry.
        // See docs/checklist-system-audit/PR-B3_VALIDATION_GUARDS.md.
        if ($result->anyAnswerMissing) {
            Log::channel('api_errors')->warning('Rental Ready checklist saved despite unanswered questions', [
                'equipment_id'         => $equipment->id,
                'equipment_unique_id'  => $uniqueId,
                'total_questions'      => $counts['total_questions'],
                'unanswered_count'     => collect($newQuestions)->filter(fn ($q) => empty($q['selected_answer']))->count(),
            ]);
        }

        if($template = EquipmentRentalReadyTemplate::with('checklistQuestions')->where('equipment_id', $equipment->id)
                    ->where('order_id', $equipment->current_order_id ?? null)
                    ->where('order_product_id', $equipment->current_order_product_id ?? null)
                    ->where('status', '!=', "Rental Ready")
                    ->latest('id')
                    ->first()) {
            $template->employee_id = $employee->id;
            $template->employee_name = $employee->full_name;
            $template->inspection_date = now()->format('Y-m-d');
            $template->inspection_time = now()->format('H:i');
            $template->equipment_hours = $validated['equipment_hours'] ?? null;
            $template->status = $status ?? $template->status;
            $template->general_notes = $validated['general_notes'] ?? null;
            $template->total_questions = $counts['total_questions'];
            $template->required_questions = $counts['required_questions'];
            $template->optional_questions = $counts['optional_questions'];
            $template->required_items_completed = $counts['required_items_completed'];
            $template->items_requiring_maintenance = $counts['items_requiring_maintenance'];
            $template->damaged_items = $counts['damaged_items'];
            $template->is_complete = $allRentalReady;
            $template->updated_by = auth()->id();
            $template->save();

            foreach ($newQuestions as $key => $question) {
                $existingQuestion = $template->checklistQuestions()->where('rental_ready_checklist_questions_id', $question['id'] ?? null)->first();
                if ($existingQuestion) {
                     $existingQuestion->selected_answer_id = $question['selected_answer']['id'] ?? null;
                    $existingQuestion->rental_ready_qa_json = json_encode($question);
                    $existingQuestion->general_notes = $question['note'] ?? null;
                    $existingQuestion->save();
                } else {
                    $template->checklistQuestions()->create([
                        'rental_ready_checklist_questions_id' => $question['id'] ?? null,
                        'selected_answer_id' => $question['selected_answer']['id'] ?? null,
                        'general_notes' => $question['note'] ?? null,
                        'rental_ready_qa_json' => json_encode($question),
                    ]);
                }
            }
        }else{
            $template = new EquipmentRentalReadyTemplate();
            $template->equipment_id = $equipment->id;
            $template->employee_id = $employee->id;
            $template->employee_name = $employee->full_name;
            $template->order_id = $equipment->current_order_id ?? null;
            $template->order_product_id = $equipment->current_order_product_id ?? null;
            $template->inspection_date = now()->format('Y-m-d');
            $template->inspection_time = now()->format('H:i');
            $template->equipment_hours = $validated['equipment_hours'] ?? null;
            $template->general_notes = $validated['general_notes'] ?? null;
            $template->status = $status ?? null;
            $template->total_questions = $counts['total_questions'];
            $template->required_questions = $counts['required_questions'];
            $template->optional_questions = $counts['optional_questions'];
            $template->required_items_completed = $counts['required_items_completed'];
            $template->items_requiring_maintenance = $counts['items_requiring_maintenance'];
            $template->damaged_items = $counts['damaged_items'];
            $template->is_complete = $allRentalReady;
            $template->created_by = auth()->id();
            $template->save();

            foreach ($newQuestions as $key => $question) {
                $template->checklistQuestions()->create([
                    'rental_ready_checklist_questions_id' => $question['id'] ?? null,
                    'selected_answer_id' => $question['selected_answer']['id'] ?? null,
                    'general_notes' => $question['note'] ?? null,
                    'rental_ready_qa_json' => json_encode($question),
                    'created_by' => auth()->id(),
                ]);
            }
        }

        // Consolidated log after processing all questions
        EquipmentRentalReadyChecklistQuestionLog::create([
            'equipment_rental_ready_template_id' => $template->id,
            'rental_ready_all_qa_json' => json_encode($logArray),
            'action_by' => auth()->id(),
            'action_user_name' => optional(auth()->user())->full_name,
            'inspection_date' => $template->inspection_date ?? null,
            'equipment_hours' => $template->equipment_hours ?? null,
            'inspector_name' => $employee->full_name ?? null,
        ]);



        if ($hasDamaged) {
            EquipmentStatusService::markDamagedFromRentalReady($equipment, $employee->id);
        } elseif ($allRentalReady) {
            EquipmentStatusService::markAvailableFromRentalReady($equipment, $employee->id);
        } else {
            EquipmentStatusService::markMaintenanceFromRentalReady($equipment, $employee->id);
        }


        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
        ]);
    }
}
