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
use App\Services\ChargeTaxCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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

        // Wrapped in a transaction (including the event() dispatch below, since its
        // listener runs synchronously inline) so a failure anywhere in this sequence
        // rolls back the checklist answers, the OrderProduct update, and the equipment
        // status change together instead of leaving a partial commit behind a 500.
        // Note: billing charge creation below already has its own try/catch and is
        // deliberately left as-is — this transaction does not change billing behavior.
        return DB::transaction(function () use ($request, $validated) {
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

            // Disambiguates repeated rental cycles on this same OrderProduct row (delivery/
            // return reuse it rather than recreating it per cycle) so a second legitimate
            // damage/fuel charge doesn't collide with the first cycle's idempotency key and
            // get silently dropped. SaveDeliveryController deletes and recreates every
            // order_product_checklist_questions row on every delivery, so the currently-active
            // batch's minimum id/created_at is a reliable, already-present per-cycle nonce —
            // no schema change needed. See CORRECTION_PHASE1_PLAN.md Issue #1.
            $cycleKey       = (string) ($orderProduct->checklistQuestions->min('id') ?? 'nocycle');
            $cycleStartedAt = $orderProduct->checklistQuestions->min('created_at');

            // Tracks whether any selected return answer has is_damaged=true.
            // Set inside the checklist block; used to branch equipment status and damage_status below.
            $hasDamagedReturn = false;

            // PR-A4 (observability only, no enforcement): computed inside the checklist
            // block below when a checklist is actually submitted; stays null otherwise.
            $missingRequiredQuestionCount = null;

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
                            cycleKey:             $cycleKey,
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

                // PR-A4: count required master questions with no selected return answer.
                // Observability only — does not affect pickup_status/response below.
                $missingRequiredQuestionCount = $questions->filter(function ($checklistQuestion) {
                    $masterQuestion = $checklistQuestion->question;
                    // required_question is nullable on the master record; the mobile resource
                    // layer already treats null as required for this same checklist (Phase 1
                    // audit finding) — mirror that default here for a consistent count.
                    $isRequired = $masterQuestion === null || $masterQuestion->required_question === null || (bool) $masterQuestion->required_question;
                    $hasAnswer  = $checklistQuestion->answers->contains(fn ($a) => $a->is_return_answer);

                    return $isRequired && !$hasAnswer;
                })->count();
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
                // Sales Tax Architecture Correction: $validated['fuel_sales_tax_type']
                // lets a future mobile app build send an explicit choice.
                // ChargeService::createFromOrderProduct() still defaults to
                // 'free' when omitted (the app doesn't send this field
                // today) — preserving the exact current, already-in-
                // production behavior rather than silently reversing it.
                // Whether mobile fuel charges SHOULD default to taxed is an
                // explicit business-policy decision for a future,
                // deliberately-rolled-out change, not something decided
                // here — see this correction's report.
                $fuelSalesTaxType = $validated['fuel_sales_tax_type'] ?? null;
                $legacyCa = ChargeService::createFromOrderProduct($orderProduct, 'fuel', $validated['user_id'] ?? null, $cycleStartedAt, $fuelSalesTaxType);

                // ── Billing Engine bridge (Phase 3D) — fires only when legacy CA was created ──
                if ($legacyCa !== null) {
                    $resolved = ChargeTaxCalculator::calculate((float) $orderProduct->fuel_total_charge, $legacyCa->sales_tax_type, (float) $legacyCa->sales_tax);
                    try {
                        BillingEngine::charge(new BillingChargeRequest(
                            type:                BillingChargeType::Fuel->value,
                            orderId:             $orderProduct->order_id,
                            customerId:          (int) $legacyCa->customer_id,
                            amount:              $resolved['base_amount'],
                            taxType:             $legacyCa->sales_tax_type,
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
                            idempotencyKey:    "mobile_return_fuel:{$orderProduct->id}:{$orderProduct->fuel_final_reading}:{$cycleKey}",
                            customerAccountId: $legacyCa->id,
                            taxAmount:         $resolved['tax_amount'],
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

            // PR-A4 (observability only, no enforcement — CORRECTION_PHASE1_PLAN.md Issue #3):
            // pickup_status is set to 'Completed' unconditionally above regardless of these
            // conditions. This block changes no behavior, no response, no status — it only
            // makes the gap measurable so a future phase can decide whether to enforce it.
            $this->logIfReturnIncomplete($orderProduct, $orderProductData, $validated, $missingRequiredQuestionCount);

            // fire event
            $user = auth('api_user')->user();
            $type = 'checklist_return';
            event(new OrderCustomerChecklistEvent($orderProduct->order, $user, $type));

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
            ]);
        });
    }

    /**
     * PR-A4: log (not block) when pickup_status is marked 'Completed' despite the
     * checklist actually being incomplete — missing signature, unanswered required
     * questions, or an empty/omitted checklist array. Purely observational: does not
     * change pickup_status, the HTTP response, or any other behavior. See
     * CORRECTION_PHASE1_PLAN.md Issue #3 and CHECKLIST_SYSTEM_AUDIT.md §8/§12.
     */
    private function logIfReturnIncomplete(
        OrderProduct $orderProduct,
        array $orderProductData,
        array $validated,
        ?int $missingRequiredQuestionCount
    ): void {
        $hasSignature       = !empty($orderProductData['pickup_signature_media_id']);
        $checklistSubmitted = isset($validated['checklist']) && !empty($validated['checklist']);
        $hasMissingRequired = $missingRequiredQuestionCount !== null && $missingRequiredQuestionCount > 0;

        if ($hasSignature && $checklistSubmitted && !$hasMissingRequired) {
            return;
        }

        Log::channel('api_errors')->warning('Return marked Completed despite incomplete checklist submission', [
            'order_product_id'                => $orderProduct->id,
            'order_id'                         => $orderProduct->order_id,
            'signature_present'                => $hasSignature,
            'checklist_submitted'              => $checklistSubmitted,
            'missing_required_question_count'  => $missingRequiredQuestionCount,
        ]);
    }
}
