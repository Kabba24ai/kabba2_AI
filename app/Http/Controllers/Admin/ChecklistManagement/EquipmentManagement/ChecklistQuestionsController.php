<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;

use Illuminate\Support\Facades\Log;

class ChecklistQuestionsController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $checklist_id = $request->input('checklist_id');
            $equipment_id = $request->input('equipment_id');

            if (!$checklist_id) {
                return response()->json(['success' => false, 'message' => 'No checklist_id provided']);
            }

            $checklist = ChecklistMaster::find($checklist_id);

            if (!$checklist) {
                return response()->json(['success' => false, 'message' => 'Checklist not found']);
            }

            if (!$checklist->rental_ready_template_id) {
                return response()->json(['success' => false, 'message' => 'Checklist has no rental ready template']);
            }

            $equipment = Equipment::find($equipment_id);

            if (!$equipment) {
                return response()->json(['success' => false, 'message' => 'Equipment not found']);
            }

            // Log::info("Equipment status", ['status' => $equipment->current_status]);

            if ($equipment->current_status == 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment is available; checklist questions cannot be fetched.'
                ]);
            }

            // If rented → return fresh questions only
            if ($equipment->status_label == 'Rented') {
                $template = RentalReadyChecklistTemplate::with([
                    'questions.question.answers'
                ])->find($checklist->rental_ready_template_id);

                if (!$template) {
                    return response()->json(['success' => false, 'message' => 'Template not found']);
                }

                $questions = $template->questions->map(function ($templateQuestion) {
                    $q = $templateQuestion->question;
                    if (!$q) return null;

                    return [
                        'main_id' => $q->id,
                        'id'       => $q->unique_id,
                        'title'    => $q->question_name,
                        'required' => (bool) $q->required_question,
                        'options'  => $q->answers->map(function ($answer) {
                            return [
                                'label'  => $answer->answer_name,
                                'status' => $answer->type,
                                'id'     => $answer->id
                            ];
                        })->toArray()
                    ];
                })->filter()->values();

                return response()->json([
                    'success'   => true,
                    'questions' => $questions
                ]);
            }

            // For other statuses → return existing saved data only
            $existingTemplate = EquipmentRentalReadyTemplate::with([
                'checklistQuestions'
            ])->where('equipment_id', $equipment_id)->where('status', '!=', 'Rental Ready')->where('is_complete', 0)
                ->latest()
                ->first();

            if (!$existingTemplate) {
                return response()->json([
                    'success' => false,
                    'message' => 'No saved checklist found for this equipment.'
                ]);
            }

            $summary = [
                'total_questions'          => $existingTemplate->total_questions,
                'required_questions'       => $existingTemplate->required_questions,
                'optional_questions'       => $existingTemplate->optional_questions,
                'required_items_completed' => $existingTemplate->required_items_completed,
                'items_requiring_maintenance' => $existingTemplate->items_requiring_maintenance,
                'damaged_items'            => $existingTemplate->damaged_items,
            ];

            $qaData = $existingTemplate->checklistQuestions->map(function ($q) {
                $qa = json_decode($q->rental_ready_qa_json, true);

                return [
                    'question_id' => $qa['id'] ?? null, // unique_id saved from frontend
                    'title'       => $qa['question'] ?? null,
                    'required'    => $qa['is_required'] ?? false,
                    'options'     => $qa['options'] ?? [],
                    'answer_id'   => $q->selected_answer_id,
                    'status'      => $qa['selected_answer']['status'] ?? null,
                    'notes'       => $q->general_notes,
                ];
            })->toArray();

            return response()->json([
                'success'       => true,
                'existing_data' => [
                    'questions'     => $qaData,
                    'counts'        => $summary,
                    'general_notes' => $existingTemplate->general_notes,
                    'existingTemplate' => $existingTemplate,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error("Error fetching checklist questions", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
