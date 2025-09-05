<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists\SaveRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerChecklistQuestions\ListResource;

// Model
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;

class SaveController extends BaseController
{
    /**
     * Order Customer Checklist Save
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(SaveRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $uniqueId = $validated['equipment_unique_id'];

        $orderProduct = OrderProduct::with(['checklistQuestions.answers'])
            ->where('unique_id', $validated['order_product_unique_id'])
            ->first();

        if (!$orderProduct) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.customer_checklists.no_order_product_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $equipment = Equipment::query()
            ->with(['checklistMaster.customerAdminTemplate.templateQuestions.question.answers','checklistMaster.customerAdminTemplate.templateQuestions.question.category'])
            ->where('unique_id', $uniqueId)
            ->first();

        if (!$equipment || !$equipment->checklistMaster?->customer_admin_template_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.customer_checklists.no_customer_checklist_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        if($orderProduct->checklistQuestions->isEmpty()){
            $questions = optional($equipment->checklistMaster?->customerAdminTemplate?->templateQuestions)->map->question->values() ?? collect();
        }else{
            $questions = optional($orderProduct->checklistQuestions) ?? collect();
        }

        if ($questions->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.customer_checklists.no_questions_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }



        $type = $validated['type']; // 'Delivery' or 'Return'

        // Create checklist questions only if they do not already exist for this order product
        $existingQuestionIds = $orderProduct->checklistQuestions->pluck('question_id')->toArray();

        $questionsToCreate = $questions->filter(function ($question) use ($existingQuestionIds) {
            return !in_array($question->id, $existingQuestionIds);
        })->values();

        if ($questionsToCreate->isNotEmpty()) {
            $orderProduct->checklistQuestions()->createMany($questionsToCreate->map(function ($question, $index) use ($orderProduct) {
                return [
                    'order_id' => $orderProduct->order_id,
                    'question_id' => $question->id,
                    'question_category_id' => $question->category_id,
                    'question_name' => $question->question_name,
                    'delivery_question' => $question->question_delivery_text,
                    'return_question' => $question->question_return_text,
                    'index_number' => $question->index_number ?? $index + 1,
                ];
            })->toArray());
        }

        $validatedAnswers = collect($validated['checklist'])->keyBy('answer_unique_id')->map(function ($item) {
            return [
                'question_unique_id' => $item['question_unique_id'],
                'amount' => $item['amount'] ?? null,
            ];
        })->toArray();

        // Refresh the checklistQuestions relationship to get the latest data from the database
        $orderProduct->load('checklistQuestions.answers');
        foreach ($orderProduct->checklistQuestions as $checklistQuestion) {
            $question = $questions->firstWhere('id', $checklistQuestion->question_id);

            if ($question && isset($question->answers)) {
                foreach ($question->answers as $index => $answer) {
                    // Find if answer already exists for this checklistQuestion
                    $existingAnswer = $checklistQuestion->answers()->where('answer_id', $answer->id)->first();

                    if (!$existingAnswer) {
                        $isDeliveryAnswer = false;
                        if ($type === 'Delivery') {
                            $isDeliveryAnswer = isset($validatedAnswers[$answer->unique_id]);
                        }
                        // Create new answer
                        $checklistQuestion->answers()->create([
                            'order_id' => $orderProduct->order_id,
                            'question_id' => $answer->question_id,
                            'answer_id' => $answer->id,
                            'delivery_answer' => $answer->delivery_answer,
                            'return_answer' => $answer->return_answer,
                            'delivery_amount' => $answer->delivery_amount,
                            'return_amount' => $answer->return_amount,
                            'is_delivery_answer' => $isDeliveryAnswer,
                            'is_return_answer' => false,
                            'user_delivery_amount' => $isDeliveryAnswer ? $validatedAnswers[$answer->unique_id]['amount'] ?? null : null,
                            'is_sync' => $answer->is_sync,
                            'index_number' => $answer->index_number ?? $index + 1,
                        ]);
                    } elseif ($type === 'Return') {
                        $isReturnAnswer = false;
                        if ($type === 'Return') {
                            $isReturnAnswer = isset($validatedAnswers[$answer->unique_id]);
                        }
                        // Update only the return values
                        $existingAnswer->update([
                            'is_return_answer' => $isReturnAnswer,
                            'user_return_amount' => $isReturnAnswer ? $validatedAnswers[$answer->unique_id]['amount'] ?? null : null,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
        ]);
    }
}
