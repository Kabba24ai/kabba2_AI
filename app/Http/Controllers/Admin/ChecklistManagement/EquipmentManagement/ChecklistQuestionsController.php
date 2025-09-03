<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use Illuminate\Support\Facades\Log;

class ChecklistQuestionsController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $checklist_id = $request->input('checklist_id');
            // Log::info("Fetching checklist questions", ['checklist_id' => $checklist_id]);

            if (!$checklist_id) {
                return response()->json(['success' => false, 'message' => 'No checklist_id provided']);
            }

            $checklist = ChecklistMaster::find($checklist_id);

            if (!$checklist) {
                // Log::warning("Checklist not found", ['checklist_id' => $checklist_id]);
                return response()->json(['success' => false, 'message' => 'Checklist not found']);
            }

            if (!$checklist->rental_ready_template_id) {
                // Log::warning("Checklist has no rental_ready_template_id", ['checklist_id' => $checklist_id]);
                return response()->json(['success' => false, 'message' => 'Checklist has no rental ready template']);
            }

            // Eager load full chain
            $template = RentalReadyChecklistTemplate::with([
                'questions.question.answers'
            ])->find($checklist->rental_ready_template_id);

            if (!$template) {
                // Log::warning("Template not found", ['template_id' => $checklist->rental_ready_template_id]);
                return response()->json(['success' => false, 'message' => 'Template not found']);
            }

            // Map into usable structure for frontend
            $questions = $template->questions->map(function ($templateQuestion) {
                $q = $templateQuestion->question;
                if (!$q) return null;

                return [
                    'id'       => $q->unique_id,
                    'title'    => $q->question_name,
                    'required' => (bool) $q->required_question,
                    'options'  => $q->answers->map(function ($answer) {
                        return [
                            'label'  => $answer->answer_name,
                            'status' => $answer->type
                        ];
                    })->toArray()
                ];
            })->filter()->values(); // filter out nulls in case of missing relations

            return response()->json([
                'success'   => true,
                'questions' => $questions
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
