<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Events\Admin\Orders\OrderCustomerChecklistEvent;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists\SaveReturnRequest;

// Model
use App\Models\Orders\OrderProduct;

class SaveReturnController extends BaseController
{
    /**
     * Order Return Checklist Save
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(SaveReturnRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $orderProduct = OrderProduct::with(['checklistQuestions.answers', 'returnSignatureMedia', 'equipment'])
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

        $equipment = $orderProduct->equipment;
        if (!$equipment) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.no_equipment_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        if (!$equipment->current_status->isRented()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.equipment_status', ['status' => $equipment->current_status->label()]),
                ],
                JsonResponse::HTTP_FORBIDDEN,
            );
        }

        if ($orderProduct->returnSignatureMedia) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.checklist_already_exists'),
                ],
                JsonResponse::HTTP_CONFLICT,
            );
        }

        $questions = optional($orderProduct->checklistQuestions) ?? collect();

        if ($questions->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.customer_checklists.no_questions_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $validatedAnswers = collect($validated['checklist'])->keyBy('answer_unique_id');

        foreach ($validatedAnswers as $answer) {
            // $questions is a Collection of OrderProductChecklistQuestion models with ->answers loaded
            $answer = $questions
                ->pluck('answers') // Collection<Collection<Answer>>
                ->flatten() // Collection<Answer>
                ->firstWhere('unique_id', $answer['answer_unique_id'] ?? null);

            if ($answer) {
                $answer->is_return_answer = true; // later if we save multiple time then need to false old ones
                $answer->user_return_amount = $validatedAnswers[$answer->unique_id]['amount'] ?? null;
                $answer->save();
            }
        }

        $orderProductData = [
            'pickup_store_id' => $validated['store_id'],
            'pickup_date' => now()->format('Y-m-d'),
            'pickup_time' => now()->format('H:i'),
            'pickup_by' => $validated['user_id'],
            'pickup_notes' => $validated['note'] ?? null,
            'pickup_signature_media_id' => null,
            'pickup_status' => 'Completed',
            'is_returned' => true,
            'end_hours' => $validated['end_hours'] ?? null,
            'fuel_final_reading' => $validated['fuel_final_reading'] ?? null,
            'total_charge' => $validated['total_charge'] ?? null,
        ];

        if ($request->hasFile('signature_media')) {
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('signature_media'), 'orders/schedules', $orderProduct);
            if (!empty($mediaData['mediaObj'])) {
                $orderProductData['pickup_signature_media_id'] = $mediaData['mediaObj']->id;
            }
        }

        $orderProduct->update($orderProductData);

        if ($equipment) {
            $equipment->current_status = EquipmentCurrentStatus::Maintenance->value;
            $equipment->current_status_updated_by = $validated['user_id'];
            $equipment->current_status_changed_at = now();
            $equipment->store_id = $validated['store_id'];
            $equipment->saveQuietly();
        }

        // fire event
        $user = auth('api_user')->user();
        $type = 'checklist_return';
        event(new OrderCustomerChecklistEvent($orderProduct->order, $user, $type));

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
        ]);
    }
}
