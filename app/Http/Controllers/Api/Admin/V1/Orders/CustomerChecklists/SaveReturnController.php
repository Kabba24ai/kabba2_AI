<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Services\Equipment\EquipmentStatusService;
use App\Events\Admin\Orders\OrderCustomerChecklistEvent;
use App\Enums\Api\ApiErrorCode;
use App\Helpers\ApiResponseHelper;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Api\BaseController;
use App\Http\DataObjects\BillingChargeRequest;
use App\Services\BillingEngine;
use App\Services\ChargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists\SaveReturnRequest;

// Model
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductChecklistQuestionAnswers;
use App\Enums\Orders\OrderProductChargeStatus;

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

        $orderProduct = OrderProduct::with(['checklistQuestions.answers', 'returnSignatureMedia', 'equipment', 'order'])
            ->whereHas('order')
            ->where('unique_id', $validated['order_product_unique_id'])
            ->first();

        if (!$orderProduct) {
            return ApiResponseHelper::error(ApiErrorCode::OrderProductNotFound, [], [
                'order_product_unique_id' => $validated['order_product_unique_id'],
            ]);
        }

        $equipment = $orderProduct->equipment;
        if (!$equipment) {
            return ApiResponseHelper::error(ApiErrorCode::EquipmentNotFound, [], [
                'order_product_id' => $orderProduct->id,
                'order_id'         => $orderProduct->order_id,
            ]);
        }

        if (!$equipment->current_status->isRented()) {
            return ApiResponseHelper::error(ApiErrorCode::InvalidEquipmentStatus, ['status' => $equipment->current_status->label()], [
                'equipment_id'             => $equipment->id,
                'order_product_id'         => $orderProduct->id,
                'order_id'                 => $orderProduct->order_id,
                'current_equipment_status' => $equipment->current_status->value,
            ]);
        }

        if ($orderProduct->returnSignatureMedia) {
            return ApiResponseHelper::error(ApiErrorCode::ChecklistAlreadySubmitted, [], [
                'equipment_id'     => $equipment->id,
                'order_product_id' => $orderProduct->id,
                'order_id'         => $orderProduct->order_id,
            ]);
        }

        // Tracks whether any selected return answer has is_damaged=true.
        // Set inside the checklist block; used to branch equipment status and damage_status below.
        $hasDamagedReturn = false;

        if(isset($validated['checklist']) && !empty($validated['checklist'])) {
            $questions = optional($orderProduct->checklistQuestions) ?? collect();

            if ($questions->isEmpty()) {
                return ApiResponseHelper::error(ApiErrorCode::NoQuestionsFound, [], [
                    'equipment_id'     => $equipment->id,
                    'order_product_id' => $orderProduct->id,
                    'order_id'         => $orderProduct->order_id,
                ]);
            }

            $validatedAnswers = collect($validated['checklist'])->keyBy('answer_unique_id');

            // Clear stale return-selected flags for this order product's questions before
            // applying the new selections. Prevents accumulation on mobile retry when no
            // signature has been uploaded yet (the 409 guard only fires after signature upload).
            $questionIds = $questions->pluck('id');
            OrderProductChecklistQuestionAnswers::whereIn('order_product_checklist_question_id', $questionIds)
                ->where('is_return_answer', true)
                ->update(['is_return_answer' => false]);

            foreach ($validatedAnswers as $answer) {
                // $questions is a Collection of OrderProductChecklistQuestion models with ->answers loaded
                $answer = $questions
                    ->pluck('answers') // Collection<Collection<Answer>>
                    ->flatten() // Collection<Answer>
                    ->firstWhere('unique_id', $answer['answer_unique_id'] ?? null);

                if ($answer) {
                    $answer->is_return_answer = true;
                    $answer->user_return_amount = $validatedAnswers[$answer->unique_id]['amount'] ?? null;
                    $answer->save();
                }
            }

            // After answers are saved, check whether any selected return answer is flagged
            // as damaged on its master CustomerAdminQuestionAnswer record (is_damaged=true).
            // One BillingCharge per order product return event — BillingEngine deduplicates
            // on retry via the idempotency key built into mobileReturnDamage().
            $damagedRows = OrderProductChecklistQuestionAnswers::whereIn('order_product_checklist_question_id', $questionIds)
                ->where('is_return_answer', true)
                ->whereHas('answer', fn($q) => $q->where('is_damaged', true))
                ->get(['order_product_checklist_question_id', 'user_return_amount']);

            $hasDamagedReturn = $damagedRows->isNotEmpty();

            if ($hasDamagedReturn) {
                // Sum driver-entered amounts; defaults to 0.0 — staff reviews and sets final amount.
                $damageAmount       = $damagedRows->sum(fn($r) => (float) ($r->user_return_amount ?? 0));
                $damagedQuestionIds = $damagedRows->pluck('order_product_checklist_question_id')->toArray();

                try {
                    BillingEngine::charge(BillingChargeRequest::mobileReturnDamage(
                        orderId:              $orderProduct->order_id,
                        customerId:           (int) $orderProduct->order->customer_id,
                        orderProductId:       $orderProduct->id,
                        amount:               $damageAmount,
                        submittedByUserId:    isset($validated['user_id']) ? (int) $validated['user_id'] : null,
                        checklistQuestionIds: $damagedQuestionIds,
                    ));

                    Log::channel('billing_engine')->info(
                        "Mobile damage charge created | order_product_id={$orderProduct->id}" .
                        " | order_id={$orderProduct->order_id}" .
                        " | damaged_question_count=" . count($damagedQuestionIds) .
                        " | amount={$damageAmount}"
                    );
                } catch (\Throwable $e) {
                    Log::channel('billing_engine')->error(
                        "Mobile damage charge failed | order_product_id={$orderProduct->id}" .
                        " | order_id={$orderProduct->order_id}" .
                        " | error=" . $e->getMessage()
                    );
                }
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
            'fuel_total_charge' => $validated['fuel_total_charge'] ?? null,
            'total_charge' => $validated['total_charge'] ?? null,
        ];

        if ($hasDamagedReturn) {
            $orderProductData['damage_status'] = OrderProductChargeStatus::Pending->value;
        }

        if ($request->hasFile('signature_media')) {
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('signature_media'), 'orders/schedules', $orderProduct);
            if (!empty($mediaData['mediaObj'])) {
                $orderProductData['pickup_signature_media_id'] = $mediaData['mediaObj']->id;
            }
        }

        $orderProduct->update($orderProductData);

        // If the checklist recorded a fuel charge, create the CA ledger entry
        if (!empty($orderProductData['fuel_total_charge']) && $orderProductData['fuel_total_charge'] > 0) {
            $orderProduct->refresh();
            $legacyCa = ChargeService::createFromOrderProduct($orderProduct, 'fuel', $validated['user_id'] ?? null);

            // ── Billing Engine bridge (Phase 3D) — fires only when legacy CA was created ──
            if ($legacyCa !== null) {
                try {
                    BillingEngine::charge(new BillingChargeRequest(
                        type:                BillingChargeType::Fuel->value,
                        orderId:             $orderProduct->order_id,
                        customerId:          (int) $legacyCa->customer_id,
                        amount:              (float) $orderProduct->fuel_total_charge,
                        taxType:             'free',
                        orderProductId:      $orderProduct->id,
                        responsiblePersonId: isset($validated['user_id']) ? (int) $validated['user_id'] : null,
                        sourceModule:        BillingSourceModule::MobileChecklist->value,
                        sourceEvent:         BillingSourceEvent::ReturnChecklistFuelCharge->value,
                        sourceReferenceType: 'OrderProduct',
                        sourceReferenceId:   $orderProduct->id,
                        metadata: [
                            'legacy_controller'          => 'SaveReturnController',
                            'legacy_service'             => 'ChargeService::createFromOrderProduct',
                            'legacy_customer_account_id' => $legacyCa->id,
                            'order_id'                   => $orderProduct->order_id,
                            'order_product_id'           => $orderProduct->id,
                            'customer_id'                => $legacyCa->customer_id,
                            'fuel_initial_reading'       => $orderProduct->fuel_initial_reading,
                            'fuel_final_reading'         => $orderProduct->fuel_final_reading,
                            'fuel_total_charge'          => $orderProduct->fuel_total_charge,
                            'product_id'                 => $orderProduct->product_id,
                            'equipment_id'               => $orderProduct->equipment_id,
                            'submitted_by_user_id'       => $validated['user_id'] ?? null,
                            'mobile_source'              => true,
                        ],
                        idempotencyKey:    "mobile_return_fuel:{$orderProduct->id}:{$orderProduct->fuel_final_reading}",
                        customerAccountId: $legacyCa->id,
                    ));
                } catch (\Throwable $e) {
                    Log::channel('billing_engine')->error(
                        "BillingEngine bridge failed | controller=SaveReturnController " .
                        "| order_product_id={$orderProduct->id} | customer_account_id={$legacyCa->id} " .
                        "| error=" . $e->getMessage()
                    );
                }
            }
        }

        if ($equipment) {
            $equipment->equipment_hours = $validated['end_hours'] ?? null;
            $actorId  = isset($validated['user_id']) ? (int) $validated['user_id'] : null;
            $storeId  = isset($validated['store_id']) ? (int) $validated['store_id'] : null;
            if ($hasDamagedReturn) {
                EquipmentStatusService::markReturnedDamaged($equipment, $orderProduct->order_id, $orderProduct->id, $storeId, $actorId);
            } else {
                EquipmentStatusService::markReturnedToMaintenance($equipment, $orderProduct->order_id, $orderProduct->id, $storeId, $actorId);
            }
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
