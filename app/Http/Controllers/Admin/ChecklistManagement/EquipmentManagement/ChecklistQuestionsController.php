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

            // ---------------- FRESH QUESTIONS ----------------
            if ($equipment->status_label == 'Rented' || $equipment->status_label == 'Available') {
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
                        'main_id'   => $q->id,
                        'id'        => $q->unique_id,
                        'title'     => $q->question_name,
                        'required'  => (bool) $q->required_question,
                        'answers'   => $q->answers->map(fn($answer) => [
                            'id'        => $answer->id,
                            'unique_id' => $answer->unique_id,
                            'label'     => $answer->answer_name,
                            'status'    => $answer->type,
                        ])->toArray(),
                        'answer_id' => null,
                        'status'    => null,
                        'notes'     => null,
                    ];
                })->filter()->values();

                return response()->json([
                    'success'   => true,
                    'questions' => $questions
                ]);
            }

            // ---------------- EXISTING SAVED QUESTIONS ----------------
            $existingTemplate = EquipmentRentalReadyTemplate::with([
                'checklistQuestions'
            ])->where('equipment_id', $equipment_id)
                ->where('status', '!=', 'Rental Ready')
                ->where('is_complete', 0)
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
                    'main_id'   => $qa['main_id'] ?? null,
                    'id'        => $qa['unique_id'] ?? $qa['id'] ?? null,
                    'title'     => $qa['question_name'] ?? null,
                    'required'  => $qa['required_question'] ?? false,
                    'answers'   => collect($qa['answers'] ?? [])->map(fn($a) => [
                        'id'        => $a['id'],
                        'unique_id' => $a['unique_id'],
                        'label'     => $a['answer_name'] ?? $a['label'],
                        'status'    => $a['type'] ?? $a['status'],
                    ])->toArray(),
                    'answer_id' => $q->selected_answer_id,
                    'status'    => $qa['selected_answer']['type'] ?? null,
                    'notes'     => $q->general_notes,
                ];
            })->toArray();

            return response()->json([
                'success'       => true,
                'existing_data' => [
                    'questions'       => $qaData,
                    'counts'          => $summary,
                    'general_notes'   => $existingTemplate->general_notes,
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
