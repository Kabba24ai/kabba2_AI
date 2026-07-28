<?php

namespace App\Services\ChecklistManagement\RentalReady;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for "which rental-ready checklist questions/answers
 * (in which order) does this equipment show right now" — shared by the web
 * rental-ready screen (Admin\ChecklistManagement\EquipmentManagement\ChecklistQuestionsController)
 * and the admin app API, so the two surfaces cannot drift out of sync again
 * (see the questions()/templateQuestions() ordering mismatch fixed 2026-07-28).
 *
 * Every method returns a plain ['status' => int, 'body' => array] pair —
 * callers are responsible for turning that into a JsonResponse.
 */
class RentalReadyChecklistQuestionsResolver
{
    /**
     * @param  int|string|null  $checklistId
     * @param  int|string|null  $equipmentId
     * @param  int|string|null  $orderProductId
     * @return array{status:int, body:array}
     */
    public function resolve($checklistId, $equipmentId, $orderProductId): array
    {
        try {
            if (!$checklistId || !$equipmentId) {
                return $this->error(422, 'Missing required parameters (checklist_id, equipment_id).');
            }

            $checklist = ChecklistMaster::find($checklistId);
            if (!$checklist || !$checklist->rental_ready_template_id) {
                return $this->error(404, 'Checklist not found or has no rental ready template.');
            }

            $equipment = Equipment::find($equipmentId);
            if (!$equipment) {
                return $this->error(404, 'Equipment not found');
            }

            // CASE 1: Fresh template OR existing with no order_product_id
            if (!$orderProductId || $orderProductId === '-') {
                $existingTemplate = EquipmentRentalReadyTemplate::where('equipment_id', $equipmentId)
                    ->whereNull('order_product_id')
                    ->latest('id')
                    ->first();

                if ($existingTemplate && $existingTemplate->status !== 'Rental Ready') {
                    return $this->returnExistingTemplate($existingTemplate);
                }

                return $this->returnFreshTemplate($equipment, $checklist);
            }

            // CASE 2: Existing template (with order_product_id)
            $orderProduct = OrderProduct::with([
                'equipmentRentalReadyTemplate.checklistQuestions',
                'equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.answers',
            ])
                ->whereHas('order')
                ->find($orderProductId);

            if (!$orderProduct) {
                return $this->error(404, 'Order product not found');
            }

            if (
                $orderProduct->equipmentRentalReadyTemplate &&
                $orderProduct->equipmentRentalReadyTemplate->status !== 'Rental Ready'
            ) {
                return $this->returnExistingTemplate($orderProduct->equipmentRentalReadyTemplate);
            }

            // fallback to fresh
            return $this->returnFreshTemplate($equipment, $checklist);
        } catch (\Throwable $e) {
            Log::error('Error fetching checklist questions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(500, 'Server error: ' . $e->getMessage());
        }
    }

    private function error(int $status, string $message): array
    {
        return [
            'status' => $status,
            'body' => [
                'success' => false,
                'message' => $message,
            ],
        ];
    }

    /**
     * Return a fresh template with questions.
     */
    private function returnFreshTemplate(Equipment $equipment, ChecklistMaster $checklist): array
    {
        if (!$equipment->checklistMaster?->rental_ready_template_id) {
            return $this->error(404, 'No rental ready template assigned to equipment');
        }

        $template = RentalReadyChecklistTemplate::with([
            'questions.question.answers',
            'questions.question.category',
        ])->find($checklist->rental_ready_template_id);

        if (!$template) {
            return $this->error(404, 'Template not found');
        }

        $template = $this->ensureMiscellaneousQuestionInTemplate($template);

        $questions = $template->questions->map(function ($templateQuestion) {
            $q = $templateQuestion->question;
            if (!$q) {
                return null;
            }

            return [
                'main_id' => $q->id,
                'id' => $q->unique_id,
                'title' => $q->question_name,
                'category_id' => $q->category->id ?? null,
                'required' => (bool) $q->required_question,
                'answers' => $q->answers->map(fn($answer) => [
                    'id' => $answer->id,
                    'unique_id' => $answer->unique_id,
                    'label' => $answer->answer_name,
                    'status' => $answer->type,
                ])->toArray(),
                'answer_id' => null,
                'status' => null,
                'notes' => null,
            ];
        })->filter()->values();

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'questions' => $questions,
            ],
        ];
    }

    /**
     * Return an existing template with saved answers.
     */
    private function returnExistingTemplate(EquipmentRentalReadyTemplate $existingTemplate): array
    {
        $summary = [
            'total_questions' => $existingTemplate->total_questions,
            'required_questions' => $existingTemplate->required_questions,
            'optional_questions' => $existingTemplate->optional_questions,
            'required_items_completed' => $existingTemplate->required_items_completed,
            'items_requiring_maintenance' => $existingTemplate->items_requiring_maintenance,
            'damaged_items' => $existingTemplate->damaged_items,
        ];

        $qaData = $existingTemplate->checklistQuestions->map(function ($q) {
            $qa = json_decode($q->rental_ready_qa_json, true);

            return [
                'main_id' => $qa['id'] ?? null,
                'id' => $qa['unique_id'] ?? $qa['id'] ?? null,
                'title' => $qa['question_name'] ?? null,
                'category_id' => $qa['category_id'] ?? null,
                'required' => $qa['required_question'] ?? false,
                'answers' => collect($qa['answers'] ?? [])->map(fn($a) => [
                    'id' => $a['id'],
                    'unique_id' => $a['unique_id'],
                    'label' => $a['answer_name'] ?? $a['label'],
                    'status' => $a['type'] ?? $a['status'],
                ])->toArray(),
                'answer_id' => $q->selected_answer_id,
                'status' => $qa['selected_answer']['type'] ?? null,
                'notes' => $q->general_notes,
            ];
        });

        $miscQuestion = RentalReadyChecklistQuestion::with(['answers', 'category'])
            ->where('question_name', 'Miscellaneous')
            ->first();

        if ($miscQuestion && !$qaData->contains('main_id', $miscQuestion->id)) {
            $qaData->push([
                'main_id' => $miscQuestion->id,
                'id' => $miscQuestion->unique_id,
                'title' => $miscQuestion->question_name,
                'category_id' => $miscQuestion->category->id ?? null,
                'required' => (bool) $miscQuestion->required_question,
                'answers' => $miscQuestion->answers->map(fn($answer) => [
                    'id' => $answer->id,
                    'unique_id' => $answer->unique_id,
                    'label' => $answer->answer_name,
                    'status' => $answer->type,
                ])->toArray(),
                'answer_id' => null,
                'status' => null,
                'notes' => null,
            ]);
        }

        $qaData = $qaData->values()->toArray();

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'existing_data' => [
                    'questions' => $qaData,
                    'counts' => $summary,
                    'general_notes' => $existingTemplate->general_notes,
                    'existingTemplate' => $existingTemplate,
                ],
            ],
        ];
    }

    private function ensureMiscellaneousQuestionInTemplate(
        RentalReadyChecklistTemplate $template
    ): RentalReadyChecklistTemplate {
        // STEP 1: Ensure Miscellaneous Category Exists
        $miscCategory = RentalReadyChecklistCategory::where('category_name', 'Miscellaneous')->first();

        if (!$miscCategory) {
            $miscCategory = RentalReadyChecklistCategory::create([
                'category_name' => 'Miscellaneous',
                'description' => 'System generated Miscellaneous category',
            ]);
        }

        // STEP 2: Ensure Miscellaneous Question Exists
        $miscQuestion = RentalReadyChecklistQuestion::with(['answers', 'category'])
            ->where('question_name', 'Miscellaneous')
            ->first();

        if (!$miscQuestion) {
            $miscQuestion = RentalReadyChecklistQuestion::create([
                'question_name' => 'Miscellaneous',
                'category_id' => $miscCategory->id,
                'required_question' => 1,
            ]);

            $this->createDefaultMiscAnswers($miscQuestion->id);

            $miscQuestion->load(['answers', 'category']);
        } elseif ($miscQuestion->answers->count() === 0) {
            // Safety Check - Ensure answers exist
            $this->createDefaultMiscAnswers($miscQuestion->id);

            $miscQuestion->load('answers');
        }

        // STEP 3: Check Template Mapping
        $existsInTemplate = $template->questions->contains(function ($templateQuestion) use ($miscQuestion) {
            return (int) $templateQuestion->question_id === (int) $miscQuestion->id;
        });

        // STEP 4: Add To Template If Missing
        if (!$existsInTemplate) {
            $nextIndex = ((int) $template->questions->max('index_number')) + 1;

            RentalReadyChecklistTemplateQuestion::create([
                'template_id' => $template->id,
                'question_id' => $miscQuestion->id,
                'index_number' => $nextIndex,
                'required' => $miscQuestion->required_question ?? 1,
            ]);

            $template->load([
                'questions.question.answers',
                'questions.question.category',
            ]);
        }

        return $template;
    }

    private function createDefaultMiscAnswers(int $questionId): void
    {
        $defaultAnswers = [
            ['answer_name' => 'Inspection Required', 'type' => 'Maint. Hold', 'index_number' => 1],
            ['answer_name' => 'Operable', 'type' => 'Rental Ready', 'index_number' => 2],
            ['answer_name' => 'Miscellaneous Damage', 'type' => 'Damaged', 'index_number' => 3],
        ];

        foreach ($defaultAnswers as $answer) {
            RentalReadyChecklistQuestionAnswer::create([
                'question_id' => $questionId,
                'answer_name' => $answer['answer_name'],
                'type' => $answer['type'],
                'index_number' => $answer['index_number'],
            ]);
        }
    }
}
