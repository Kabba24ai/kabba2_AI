<?php

namespace App\Services\ResolutionCenter;

use App\Enums\Orders\OrderPaymentMethod;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 *
 * The decision tree from this phase's mission, implemented exactly as
 * specified — no additional branches, no invented policy. Pure and
 * stateless: no database access, no side effects, so every branch is
 * independently unit-testable without a database connection.
 *
 * `$requiresManagerOverride` is always false in this phase — no concrete
 * "when is a manager override required" threshold exists anywhere in this
 * codebase's approved policy today, and this phase does not invent one
 * (per its explicit "do not invent new policy" rule). The Manager Override
 * field remains available on every case regardless, per the mission's UI
 * spec ("Manager Override — where applicable"); it is optional input, not
 * conditionally forced by this engine, until a real threshold is approved.
 */
class ResolutionDecisionEngine
{
    /**
     * @param  bool  $canReschedule  Step 1's answer.
     * @param  bool|null  $creditWouldSatisfy  Step 2's answer — only meaningful
     *                                         (and only asked) when $canReschedule is false.
     * @param  string|null  $paymentMethod  An {@see OrderPaymentMethod} value —
     *                                      only meaningful (and only asked) once a
     *                                      refund is reached.
     */
    public function recommend(bool $canReschedule, ?bool $creditWouldSatisfy, ?string $paymentMethod): ResolutionRecommendation
    {
        // Step 1 — Reschedule first.
        if ($canReschedule) {
            return new ResolutionRecommendation(
                options: [ResolutionPolicy::RECOMMEND_FREE_RESCHEDULE],
                nextStep: 'Use the existing reschedule option on this order\'s Edit screen. No fee applies — rescheduling has never had a fee in this system.',
            );
        }

        // Step 2 — Store Credit second.
        if ($creditWouldSatisfy === true) {
            return new ResolutionRecommendation(
                options: [ResolutionPolicy::RECOMMEND_ISSUE_STORE_CREDIT],
                nextStep: 'Approve below to issue Financial Store Credit via CustomerCreditService, referenced to this order.',
            );
        }

        // Step 3 — Refund last.
        if ($paymentMethod === OrderPaymentMethod::Card->value) {
            return new ResolutionRecommendation(
                options: [ResolutionPolicy::RECOMMEND_STANDARD_REFUND, ResolutionPolicy::RECOMMEND_WAIVE_REFUND_FEE],
                nextStep: 'Use the existing Refund action on this order\'s Edit screen. No refund fee exists in this system today, so both options currently have the same practical effect — see PHASE_3_3_RESOLUTION_CENTER.md §1.',
            );
        }

        return new ResolutionRecommendation(
            options: [ResolutionPolicy::RECOMMEND_STANDARD_REFUND],
            nextStep: 'Use the existing Refund action on this order\'s Edit screen.',
        );
    }
}
