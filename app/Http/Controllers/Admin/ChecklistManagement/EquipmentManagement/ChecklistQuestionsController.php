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
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;

class ChecklistQuestionsController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $checklist_id     = $request->input('checklist_id');
            $equipment_id     = $request->input('equipment_id');
            $order_product_id = $request->input('order_product_id');

            // Required params
            if (!$checklist_id || !$equipment_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters (checklist_id, equipment_id).'
                ], 422);
            }

            $checklist = ChecklistMaster::find($checklist_id);
            if (!$checklist || !$checklist->rental_ready_template_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checklist not found or has no rental ready template.'
                ], 404);
            }

            $equipment = Equipment::find($equipment_id);
            if (!$equipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment not found'
                ], 404);
            }

            // CASE 1: resume an in-progress DRAFT, else a fresh inspection.
            // Phase 2A: a finalized inspection (Rental Ready / Maintenance Hold /
            // Damaged) is immutable — selecting the equipment starts a NEW
            // inspection rather than reopening a completed one's answers.
            if (!$order_product_id || $order_product_id === '-') {
                $existingDraft = EquipmentRentalReadyTemplate::where('equipment_id', $equipment_id)
                    ->whereNull('order_product_id')
                    ->where('lifecycle_status', 'draft')
                    ->latest('id')
                    ->first();

                if ($existingDraft) {
                    return $this->returnExistingTemplate($existingDraft);
                }

                return $this->returnFreshTemplate($equipment, $checklist);
            }

            // CASE 2: Existing template (with order_product_id)
            $orderProduct = OrderProduct::with([
                'equipmentRentalReadyTemplate.checklistQuestions',
                'equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.answers',
            ])
                ->whereHas('order')
                ->find($order_product_id);

            if (!$orderProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order product not found'
                ], 404);
            }

            if (
                $orderProduct->equipmentRentalReadyTemplate &&
                ($orderProduct->equipmentRentalReadyTemplate->lifecycle_status?->value ?? null) === 'draft'
            ) {
                return $this->returnExistingTemplate($orderProduct->equipmentRentalReadyTemplate);
            }

            // fallback to fresh
            return $this->returnFreshTemplate($equipment, $checklist);
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

    /**
     * Return a fresh template with questions.
     */

    private function returnFreshTemplate(Equipment $equipment, ChecklistMaster $checklist)
    {
        if (!$equipment->checklistMaster?->rental_ready_template_id) {
            return response()->json([
                'success' => false,
                'message' => 'No rental ready template assigned to equipment'
            ], 404);
        }

        $template = RentalReadyChecklistTemplate::with([
            'questions.question.answers',
            'questions.question.category',
        ])->find($checklist->rental_ready_template_id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        $template = $this->ensureMiscellaneousQuestionInTemplate($template);

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
    }

    /**
     * Return an existing template with saved answers.
     */
    private function returnExistingTemplate(EquipmentRentalReadyTemplate $existingTemplate)
    {
        $summary = [
            'total_questions'             => $existingTemplate->total_questions,
            'required_questions'          => $existingTemplate->required_questions,
            'optional_questions'          => $existingTemplate->optional_questions,
            'required_items_completed'    => $existingTemplate->required_items_completed,
            'items_requiring_maintenance' => $existingTemplate->items_requiring_maintenance,
            'damaged_items'               => $existingTemplate->damaged_items,
        ];

        $qaData = $existingTemplate->checklistQuestions->map(function ($q) {
            $qa = json_decode($q->rental_ready_qa_json, true);

            return [
                'main_id'   => $qa['id'] ?? null,
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
        });
// ->toArray();

        $miscQuestion = RentalReadyChecklistQuestion::with(['answers', 'category'])
    ->where('question_name', 'Miscellaneous')
    ->first();

if ($miscQuestion && !$qaData->contains('main_id', $miscQuestion->id)) {
    $qaData->push([
        'main_id'     => $miscQuestion->id,
        'id'          => $miscQuestion->unique_id,
        'title'       => $miscQuestion->question_name,
        'category_id' => $miscQuestion->category->id ?? null,
        'required'    => (bool) $miscQuestion->required_question,
        'answers'     => $miscQuestion->answers->map(fn($answer) => [
            'id'        => $answer->id,
            'unique_id' => $answer->unique_id,
            'label'     => $answer->answer_name,
            'status'    => $answer->type,
        ])->toArray(),
        'answer_id'   => null,
        'status'      => null,
        'notes'       => null,
    ]);
}

$qaData = $qaData->values()->toArray();


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

    private function ensureMiscellaneousQuestionInTemplate(
        RentalReadyChecklistTemplate $template
    ): RentalReadyChecklistTemplate {

        // Log::info('Miscellaneous setup started', [
        //     'template_id' => $template->id,
        //     'template_name' => $template->template_name,
        // ]);

        /*
        |--------------------------------------------------------------------------
        | STEP 1 : Ensure Miscellaneous Category Exists
        |--------------------------------------------------------------------------
        */

        $miscCategory = RentalReadyChecklistCategory::where(
            'category_name',
            'Miscellaneous'
        )->first();

        if (!$miscCategory) {

            // Log::warning('Miscellaneous category not found. Creating category.');

            $miscCategory = RentalReadyChecklistCategory::create([
                'category_name' => 'Miscellaneous',
                'description'   => 'System generated Miscellaneous category',
            ]);
        } 

        /*
        |--------------------------------------------------------------------------
        | STEP 2 : Ensure Miscellaneous Question Exists
        |--------------------------------------------------------------------------
        */

        $miscQuestion = RentalReadyChecklistQuestion::with([
            'answers',
            'category'
        ])
        ->where('question_name', 'Miscellaneous')
        ->first();

        if (!$miscQuestion) {

            // Log::warning('Global Miscellaneous question not found. Creating question.');

            $miscQuestion = RentalReadyChecklistQuestion::create([
                'question_name'     => 'Miscellaneous',
                'category_id'       => $miscCategory->id,
                'required_question' => 1,
            ]);

            $defaultAnswers = [
                [
                    'answer_name'  => 'Inspection Required',
                    'type'         => 'Maint. Hold',
                    'index_number' => 1,
                ],
                [
                    'answer_name'  => 'Operable',
                    'type'         => 'Rental Ready',
                    'index_number' => 2,
                ],
                [
                    'answer_name'  => 'Miscellaneous Damage',
                    'type'         => 'Damaged',
                    'index_number' => 3,
                ],
            ];

            foreach ($defaultAnswers as $answer) {

                RentalReadyChecklistQuestionAnswer::create([
                    'question_id'  => $miscQuestion->id,
                    'answer_name'  => $answer['answer_name'],
                    'type'         => $answer['type'],
                    'index_number' => $answer['index_number'],
                ]);
            }


            $miscQuestion->load([
                'answers',
                'category'
            ]);

        } else {

          
            /*
            |--------------------------------------------------------------------------
            | Safety Check - Ensure answers exist
            |--------------------------------------------------------------------------
            */

            if ($miscQuestion->answers->count() === 0) {

                // Log::warning('Miscellaneous question exists but answers missing.');

                $defaultAnswers = [
                    [
                        'answer_name'  => 'Inspection Required',
                        'type'         => 'Maint. Hold',
                        'index_number' => 1,
                    ],
                    [
                        'answer_name'  => 'Operable',
                        'type'         => 'Rental Ready',
                        'index_number' => 2,
                    ],
                    [
                        'answer_name'  => 'Miscellaneous Damage',
                        'type'         => 'Damaged',
                        'index_number' => 3,
                    ],
                ];

                foreach ($defaultAnswers as $answer) {

                    RentalReadyChecklistQuestionAnswer::create([
                        'question_id'  => $miscQuestion->id,
                        'answer_name'  => $answer['answer_name'],
                        'type'         => $answer['type'],
                        'index_number' => $answer['index_number'],
                    ]);
                }

                $miscQuestion->load('answers');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 3 : Check Template Mapping
        |--------------------------------------------------------------------------
        */

        $existsInTemplate = $template->questions->contains(function ($templateQuestion) use ($miscQuestion) {

            return (int) $templateQuestion->question_id === (int) $miscQuestion->id;
        });

       
        /*
        |--------------------------------------------------------------------------
        | STEP 4 : Add To Template If Missing
        |--------------------------------------------------------------------------
        */

        if (!$existsInTemplate) {

            $nextIndex = ((int) $template->questions->max('index_number')) + 1;

            RentalReadyChecklistTemplateQuestion::create([
                'template_id'  => $template->id,
                'question_id'  => $miscQuestion->id,
                'index_number' => $nextIndex,
                'required'     => $miscQuestion->required_question ?? 1,
            ]);


            $template->load([
                'questions.question.answers',
                'questions.question.category',
            ]);

        } 
       

        return $template;
    }
}
