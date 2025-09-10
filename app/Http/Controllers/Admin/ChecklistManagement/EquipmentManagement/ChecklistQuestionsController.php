<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;

use App\Models\Orders\OrderProduct;


use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;

use Illuminate\Support\Facades\Log;

class ChecklistQuestionsController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $checklist_id     = $request->input('checklist_id');
            $equipment_id     = $request->input('equipment_id');
            $order_product_id = $request->input('order_product_id');



            if (!$checklist_id || !$equipment_id || !$order_product_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters (checklist_id, equipment_id, order_product_id).'
                ]);
            }

            $checklist = ChecklistMaster::find($checklist_id);
            if (!$checklist || !$checklist->rental_ready_template_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checklist not found or has no rental ready template.'
                ]);
            }

            $equipment = Equipment::find($equipment_id);
            if (!$equipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment not found'
                ]);
            }

            //  Load OrderProduct with relations
            $orderProduct = OrderProduct::with([
                'equipmentRentalReadyTemplate.checklistQuestions',
                'equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.answers',
            ])->find($order_product_id);

            if (!$orderProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order product not found'
                ]);
            }

            // ---------------- EXISTING TEMPLATE CASE ----------------
            if ($orderProduct->equipmentRentalReadyTemplate && $orderProduct->equipmentRentalReadyTemplate->status !== 'Rental Ready') {
                $existingTemplate = $orderProduct->equipmentRentalReadyTemplate;

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
                        'category_id' => $qa['category_id'] ?? null,
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
                    'success' => true,
                    'existing_data' => [
                        'questions'        => $qaData,
                        'counts'           => $summary,
                        'general_notes'    => $existingTemplate->general_notes,
                        'existingTemplate' => $existingTemplate,
                    ]
                ]);
            }

            // ---------------- FRESH TEMPLATE CASE ----------------
            if (!$equipment->checklistMaster?->rental_ready_template_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No rental ready template assigned to equipment'
                ]);
            }

            $template = RentalReadyChecklistTemplate::with([
                'questions.question.answers'
            ])->find($checklist->rental_ready_template_id);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ]);
            }

            $questions = $template->questions->map(function ($templateQuestion) {
                $q = $templateQuestion->question;
                if (!$q) return null;

                return [
                    'main_id'   => $q->id,
                    'id'        => $q->unique_id,
                    'title'     => $q->question_name,
                    'category_id' => $q->category->id ?? null,
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
