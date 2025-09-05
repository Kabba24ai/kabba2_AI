<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists\SaveDeliveryRequest;


// Model
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;

class SaveDeliveryController extends BaseController
{
    /**
     * Order Customer Checklist Save
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(SaveDeliveryRequest $request): JsonResponse
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
                    'message' => trans('messages.api.admin.v1.orders.no_order_product_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        if($orderProduct->checklistQuestions->isNotEmpty()){
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.checklist_already_exists'),
                ],
                JsonResponse::HTTP_CONFLICT,
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

        $questions = optional($equipment->checklistMaster?->customerAdminTemplate?->templateQuestions)->map->question->values() ?? collect();

        if ($questions->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.customer_checklists.no_questions_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }


        $checklistQuestionData = $orderProduct->checklistQuestions()->createMany($questions->map(function ($question, $index) use ($orderProduct) {
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


        // Option A: manually merge into the loaded relation (no extra query)
        if ($orderProduct->relationLoaded('checklistQuestions')) {
            $orderProduct->setRelation(
                'checklistQuestions',
                $orderProduct->checklistQuestions->concat($checklistQuestionData)
            );
        }


        $validatedAnswers = collect($validated['checklist'])->keyBy('answer_unique_id')->map(function ($item) {
            return [
                'question_unique_id' => $item['question_unique_id'],
                'amount' => $item['amount'] ?? null,
            ];
        })->toArray();

        foreach ($orderProduct->checklistQuestions as $checklistQuestion) {
            $question = $questions->firstWhere('id', $checklistQuestion->question_id);

            if ($question && isset($question->answers)) {
                foreach ($question->answers as $index => $answer) {

                    $isDeliveryAnswer = isset($validatedAnswers[$answer->unique_id]) ? true : false;
                    $deliveryAmount = isset($validatedAnswers[$answer->unique_id]) ? $validatedAnswers[$answer->unique_id]['amount'] : 0;

                    // Create new answer
                    $createdAnswer = $checklistQuestion->answers()->create([
                        'order_id' => $orderProduct->order_id,
                        'question_id' => $answer->question_id,
                        'answer_id' => $answer->id,
                        'delivery_answer' => $answer->answer_delivery_text,
                        'return_answer' => $answer->answer_return_text,
                        'delivery_amount' => $answer->delivery_amt,
                        'return_amount' => $answer->return_amt,
                        'is_delivery_answer' => $isDeliveryAnswer,
                        'is_return_answer' => false,
                        'user_delivery_amount' => $deliveryAmount,
                        'is_sync' => $answer->sync_texts ?? 0,
                        'index_number' => $answer->index_number ?? $index + 1,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
        ]);
    }
}
