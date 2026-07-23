<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Services
use App\Services\Equipment\EquipmentStatusService;
use App\Events\Admin\Orders\OrderCustomerChecklistEvent;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists\SaveDeliveryRequest;

// Model
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductChecklistQuestionAnswers;

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

        // Wrapped in a transaction (including the event() dispatch below, since its
        // listener runs synchronously inline) so a failure anywhere in this sequence
        // rolls back the checklist rows, the OrderProduct update, and the equipment
        // status change together instead of leaving a partial commit behind a 500.
        return DB::transaction(function () use ($request, $validated) {
            $uniqueId = $validated['equipment_unique_id'];
            // PR-A4 (observability only, no enforcement): computed inside the checklist
            // block below when a checklist is actually submitted; stays null otherwise.
            $missingRequiredQuestionCount = null;

            $orderProduct = OrderProduct::with(['checklistQuestions.answers', 'product.categories'])
                ->whereHas('order')
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

            if($equipment && $equipment->current_status->isRented()){
                return response()->json(
                    [
                        'success' => false,
                        'message' => trans('messages.api.admin.v1.orders.equipment_is_rented'),
                    ],
                    JsonResponse::HTTP_CONFLICT,
                );
            }

            // BUG-4 fix: reject a duplicate delivery submission for the CURRENT cycle
            // before any destructive action below (checklist question/answer rebuild,
            // media upload, equipment assignment/status change). is_delivered=true with
            // is_returned=false means this order product has an active, still-open
            // delivery on record: nothing has closed that cycle yet — a genuine return
            // always sets is_returned=true (SaveReturnController), and a checklist
            // removal always sets is_delivered=false (RemoveController) — so any further
            // delivery submission while both hold is necessarily a resubmission of the
            // SAME cycle, not a legitimate new one, regardless of what changed the
            // equipment's own current_status in the meantime. Mirrors
            // SaveReturnController's own already-submitted guard (409, same translation
            // key). See docs/checklist-system-audit/P3_10_BUG4_DUPLICATE_DELIVERY_GUARD.md.
            if ($orderProduct->is_delivered && !$orderProduct->is_returned) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => trans('messages.api.admin.v1.orders.checklist_already_exists'),
                    ],
                    JsonResponse::HTTP_CONFLICT,
                );
            }

            // Queue Line staging is informational, not restrictive (2026-07-23):
            // the in-store handoff / truck-path completion is never blocked for
            // lacking staging/fuel verification. A never-staged handoff is a
            // legitimate Fast Track — recorded (staged_at null on the completion
            // latch, surfaced as "FAST TRACK / NOT STAGED") and never prevented.
            // Completion is recorded by SyncOnCustomerChecklist off the
            // OrderCustomerChecklistEvent this controller dispatches.

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


                // Remove any stale checklist questions/answers from a previous equipment assignment
                //
                // BUG-3 (P3-12A) fix: soft-delete the stale answer rows FIRST, before the
                // parent questions — a bulk relation delete() fires no per-row events, so
                // without this the old answers would survive live under a parent no longer
                // in the active checklist, exactly as BUG-3 described. The question ids are
                // read via a fresh query (not the possibly-loaded relation) so this is
                // correct regardless of what was eager-loaded earlier in the request. See
                // docs/checklist-system-audit/P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md.
                $staleQuestionIds = $orderProduct->checklistQuestions()->pluck('id');
                OrderProductChecklistQuestionAnswers::whereIn('order_product_checklist_question_id', $staleQuestionIds)->delete();

                $orderProduct->checklistQuestions()->delete();
                if ($orderProduct->relationLoaded('checklistQuestions')) {
                    $orderProduct->setRelation('checklistQuestions', collect());
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

                // PR-A4: count required master questions with no selected delivery answer.
                // Observability only — does not affect delivery_status/response below.
                $missingRequiredQuestionCount = $orderProduct->checklistQuestions->filter(function ($checklistQuestion) use ($questions) {
                    $masterQuestion = $questions->firstWhere('id', $checklistQuestion->question_id);
                    // required_question is nullable on the master record; the mobile resource
                    // layer already treats null as required for this same checklist (Phase 1
                    // audit finding) — mirror that default here for a consistent count.
                    $isRequired = $masterQuestion === null || $masterQuestion->required_question === null || (bool) $masterQuestion->required_question;
                    $hasAnswer  = $checklistQuestion->answers()->where('is_delivery_answer', true)->exists();

                    return $isRequired && !$hasAnswer;
                })->count();
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
            }

            // BUG-2 fix: reapply the rented transition on EVERY successful delivery, not
            // only when the equipment_id/equipment_details snapshot above changes. The
            // isRented() conflict guard earlier in this method already ensures execution
            // only reaches here when the equipment is NOT currently rented, so a
            // re-delivery of the SAME equipment (e.g. one that cycled through a return and
            // was left 'damaged'/'maintenance') must still be transitioned back to
            // 'rented' and get a fresh equipment_status_logs entry — previously this was
            // skipped whenever equipment_id already matched the order product's prior
            // assignment. See docs/checklist-system-audit/P3_9_BUG2_REDELIVERY_STATUS_FIX.md.
            $equipment->equipment_hours = $validated['start_hours'] ?? null;
            EquipmentStatusService::markRented(
                $equipment,
                $orderProduct->order_id,
                $orderProduct->id,
                isset($validated['user_id']) ? (int) $validated['user_id'] : null
            );

            if ($request->hasFile('signature_media')) {
                $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('signature_media'), 'orders/schedules', $orderProduct);
                if (!empty($mediaData['mediaObj'])) {
                    $orderProductData['delivery_signature_media_id'] = $mediaData['mediaObj']->id;
                }
            }

            $orderProduct->update($orderProductData);
            $orderProduct->softAssignment()->delete();

            // PR-A4 (observability only, no enforcement — CORRECTION_PHASE1_PLAN.md Issue #3):
            // delivery_status is set to 'Completed' unconditionally above regardless of these
            // conditions. This block changes no behavior, no response, no status — it only
            // makes the gap measurable so a future phase can decide whether to enforce it.
            $this->logIfDeliveryIncomplete($orderProduct, $orderProductData, $validated, $missingRequiredQuestionCount);

            // BUG-12 (observability only, no enforcement, per the plan's own recommended
            // observe-first approach — mirrors the PR-A4 pattern above): logs when the
            // delivered equipment's category doesn't match any category the booked product
            // is assigned to. Does not block the delivery or change the response. See
            // docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md BUG-12 and
            // docs/checklist-system-audit/P3_12B_BUG12_EQUIPMENT_CATEGORY_MISMATCH_LOG.md.
            $this->logIfEquipmentCategoryMismatch($orderProduct, $equipment);

            // fire event
            $user = auth('api_user')->user();
            $type = 'checklist_delivery';
            event(new OrderCustomerChecklistEvent($orderProduct->order, $user, $type));

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
            ]);
        });
    }

    /**
     * PR-A4: log (not block) when delivery_status is marked 'Completed' despite the
     * checklist actually being incomplete — missing signature, unanswered required
     * questions, or an empty/omitted checklist array. Purely observational: does not
     * change delivery_status, the HTTP response, or any other behavior. See
     * CORRECTION_PHASE1_PLAN.md Issue #3 and CHECKLIST_SYSTEM_AUDIT.md §8/§12.
     */
    private function logIfDeliveryIncomplete(
        OrderProduct $orderProduct,
        array $orderProductData,
        array $validated,
        ?int $missingRequiredQuestionCount
    ): void {
        $hasSignature       = !empty($orderProductData['delivery_signature_media_id']);
        $checklistSubmitted = isset($validated['checklist']) && !empty($validated['checklist']);
        $hasMissingRequired = $missingRequiredQuestionCount !== null && $missingRequiredQuestionCount > 0;

        if ($hasSignature && $checklistSubmitted && !$hasMissingRequired) {
            return;
        }

        Log::channel('api_errors')->warning('Delivery marked Completed despite incomplete checklist submission', [
            'order_product_id'                => $orderProduct->id,
            'order_id'                         => $orderProduct->order_id,
            'signature_present'                => $hasSignature,
            'checklist_submitted'              => $checklistSubmitted,
            'missing_required_question_count'  => $missingRequiredQuestionCount,
        ]);
    }

    /**
     * BUG-12 (observability only, no enforcement): logs when the delivered equipment's
     * category doesn't match any category the booked product is assigned to. A product
     * with zero assigned categories is treated as unknown and intentionally NOT logged
     * — the category-data set is not confirmed complete across all products, so treating
     * "no categories assigned" as a mismatch would produce noisy false positives rather
     * than a meaningful signal. Purely observational: does not change delivery_status,
     * the HTTP response, or any other behavior. See
     * docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md BUG-12 and
     * docs/checklist-system-audit/P3_12B_BUG12_EQUIPMENT_CATEGORY_MISMATCH_LOG.md.
     */
    private function logIfEquipmentCategoryMismatch(OrderProduct $orderProduct, ?Equipment $equipment): void
    {
        if (!$equipment || !$equipment->product_category_id) {
            return;
        }

        $bookedCategoryIds = $orderProduct->product?->categories->pluck('id') ?? collect();

        if ($bookedCategoryIds->isEmpty() || $bookedCategoryIds->contains($equipment->product_category_id)) {
            return;
        }

        Log::channel('api_errors')->warning('Delivered equipment category does not match the booked product\'s category', [
            'order_product_id'      => $orderProduct->id,
            'order_id'               => $orderProduct->order_id,
            'equipment_id'           => $equipment->id,
            'equipment_category_id'  => $equipment->product_category_id,
            'product_id'             => $orderProduct->product_id,
            'booked_category_ids'    => $bookedCategoryIds->values()->all(),
        ]);
    }
}
