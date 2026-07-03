<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Events\Admin\Orders\OrderProductScheduleUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\AssignEquipmentRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class AssignEquipmentController extends Controller
{
    /**
     * Handle equipment assignment and status update to Completed
     */
    public function __invoke(AssignEquipmentRequest $request)
    {
        $validated = $request->validated();

        $orderProduct = OrderProduct::with(['checklistQuestions.answers'])
            ->whereHas('order')
            ->where('unique_id', $validated['order_product_unique_id'])
            ->first();

        $equipment = Equipment::query()
            ->with(['checklistMaster.customerAdminTemplate.templateQuestions.question.answers','checklistMaster.customerAdminTemplate.templateQuestions.question.category'])
            ->where('unique_id', $validated['equipment_unique_id'])
            ->first();
        $user = auth()->user();

        if (!$orderProduct || !$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Order Product or Equipment selected.'
            ], 404);
        }

        // Check if already assigned to different equipment (hard assignment)
        if (!empty($orderProduct->equipment_id) && $orderProduct->equipment_id !== $equipment->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order Product is already hard assigned to other equipment.'
            ], 409);
        }

        if (!empty($orderProduct->equipment_id) && $orderProduct->equipment_id == $equipment->id) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment is already assigned to this order product.'
            ], 409);
        }


        if($equipment->current_status->isRented()){
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Equipment is currently rented and cannot be assigned.',
                ],
                409,
            );
        }

        // Delete existing soft assignment and create new one
        $orderProduct->softAssignment()->delete();

        // Remove any stale checklist questions/answers from a previous equipment assignment
        $orderProduct->checklistQuestions()->delete();
        if ($orderProduct->relationLoaded('checklistQuestions')) {
            $orderProduct->setRelation('checklistQuestions', collect());
        }

        // Store checklist questions and answers for future use
        $questions = optional($equipment->checklistMaster?->customerAdminTemplate?->templateQuestions)->map?->question->values() ?? collect();

        if ($questions->isNotEmpty()) {
            // Create checklist questions
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

            // Manually merge into the loaded relation (no extra query)
            if ($orderProduct->relationLoaded('checklistQuestions')) {
                $orderProduct->setRelation(
                    'checklistQuestions',
                    $orderProduct->checklistQuestions->concat($checklistQuestionData)
                );
            }

            // Create answers for each question (not marked as selected by user)
            foreach ($orderProduct->checklistQuestions as $checklistQuestion) {
                $question = $questions->firstWhere('id', $checklistQuestion->question_id);

                if ($question && isset($question->answers)) {
                    foreach ($question->answers as $index => $answer) {
                        // Create new answer (not selected by user)
                        $checklistQuestion->answers()->create([
                            'order_id' => $orderProduct->order_id,
                            'question_id' => $answer->question_id,
                            'answer_id' => $answer->id,
                            'delivery_answer' => $answer->answer_delivery_text,
                            'return_answer' => $answer->answer_return_text,
                            'delivery_amount' => $answer->delivery_amt,
                            'return_amount' => $answer->return_amt,
                            'is_delivery_answer' => false, // Not selected by user
                            'is_return_answer' => false,
                            'user_delivery_amount' => null, // No user input
                            'is_sync' => $answer->sync_texts ?? 0,
                            'index_number' => $answer->index_number ?? $index + 1,
                        ]);
                    }
                }
            }
        }

        // Now update the status to Completed based on schedule type
        $scheduleType = $validated['schedule_type'];

        $orderProduct->delivery_status = 'Completed';
        $orderProduct->is_delivered = true;
        if (empty($orderProduct->delivery_by)) {
            $orderProduct->delivery_by = $user->id;
        }
        $orderProduct->equipment_id = $equipment->id;
        $orderProduct->equipment_details = $equipment->toArray();
        if (empty($orderProduct->assigned_by)) {
            $orderProduct->assigned_by = $user->id;
        }
        $orderProduct->assigned_at = now();

        // Update equipment status to Rented when delivery is completed
        $equipment->current_status = EquipmentCurrentStatus::Rented->value;
        $equipment->current_status_updated_by = $user->id;
        $equipment->current_status_changed_at = now();
        $equipment->current_order_id = $orderProduct->order_id;
        $equipment->current_order_product_id = $orderProduct->id;
        $equipment->saveQuietly();
        // if ($scheduleType === 'Delivery') {
        // }

        // elseif ($scheduleType === 'Return') {
        //     $orderProduct->pickup_status = 'Completed';
        //     $orderProduct->is_returned = true;
        //     $orderProduct->pickup_by = $user->id;

        //     // Update equipment status to Maintenance when return is completed
        //     $equipment->current_status = EquipmentCurrentStatus::Maintenance->value;
        //     $equipment->current_status_updated_by = $user->id;
        //     $equipment->current_status_changed_at = now();

        //     // Set equipment store to pickup location
        //     if ($orderProduct->pickup_store_id) {
        //         $equipment->store_id = $orderProduct->pickup_store_id;
        //     }

        //     $equipment->saveQuietly();
        // }

        $orderProduct->save();

        $data = [
            'requested_data' => [
                'type' => strtolower($validated['schedule_type']),
                'status' => 'Completed',
            ],
            'order_product' => $orderProduct->toArray(),
        ];

        event(new OrderProductScheduleUpdated($orderProduct->order, $user, $data));

        return response()->json([
            'success' => true,
            'message' => 'Equipment assigned and status updated to Completed successfully.'
        ]);
    }
}
