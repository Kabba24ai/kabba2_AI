<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Enums
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Events\Admin\Orders\OrderCustomerChecklistEvent;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists\SaveDeliveryRequest;

// Model
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;

class SaveDeliveryController extends BaseController
{
    /**
     * Order Delivery Checklist Save
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

        if($orderProduct?->checklistQuestions->isNotEmpty() && isset($validated['checklist']) && !empty($validated['checklist'])) {
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

        if ((!$equipment || !$equipment->checklistMaster?->customer_admin_template_id) && isset($validated['checklist']) && !empty($validated['checklist'])) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.customer_checklists.no_customer_checklist_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        if($equipment->current_status->isRented()){
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.equipment_is_rented'),
                ],
                JsonResponse::HTTP_CONFLICT,
            );
        }

        if(isset($validated['checklist']) && !empty($validated['checklist'])) {
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
        }

        $orderProductData = [
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_time' => now()->format('H:i'),
            'delivery_by' => $validated['user_id'],
            'delivery_notes' => $validated['note'] ?? null,
            'delivery_signature_media_id' => null,
            'delivery_status' => 'Completed',
            'is_delivered' => true,
            'is_returned' => false,
            'start_hours' => $validated['start_hours'] ?? null,
            'fuel_initial_reading' => $validated['fuel_initial_reading'] ?? null,
        ];

        if($storeId = $validated['store_id'] ?? null){
            $orderProductData['delivery_store_id'] = $storeId;
        }

        if (empty($orderProduct->equipment_details) || $orderProduct->equipment_id !== $equipment->id) {
            $orderProductData['equipment_id'] = $equipment->id;
            $orderProductData['equipment_details'] = $equipment->toArray();
            $orderProductData['assigned_by'] = $validated['user_id'];
            $orderProductData['assigned_at'] = now();

            $equipment->equipment_hours = $validated['start_hours'] ?? null;
            $equipment->current_status = EquipmentCurrentStatus::Rented->value;
            $equipment->current_status_updated_by = $validated['user_id'];
            $equipment->current_status_changed_at = now();
            $equipment->current_order_id = $orderProduct->order_id;
            $equipment->current_order_product_id = $orderProduct->id;
            $equipment->saveQuietly();
        }

        if ($request->hasFile('signature_media')) {
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('signature_media'), 'orders/schedules', $orderProduct);
            if (!empty($mediaData['mediaObj'])) {
                $orderProductData['delivery_signature_media_id'] = $mediaData['mediaObj']->id;
            }
        }

        $orderProduct->update($orderProductData);
        $orderProduct->softAssignment()->delete();

        // fire event
        $user = auth('api_user')->user();
        $type = 'checklist_delivery';
        event(new OrderCustomerChecklistEvent($orderProduct->order, $user, $type));

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
        ]);
    }
}
