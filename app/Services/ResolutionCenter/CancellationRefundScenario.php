<?php

namespace App\Services\ResolutionCenter;

use App\Enums\Orders\OrderPaymentMethod;
use App\Services\ResolutionCenterService;

/**
 * Phase 3.4 — Operational Knowledge Framework.
 *
 * The reference implementation of {@see ResolutionScenario} — Phase 3.3's
 * cancellation/refund workflow, refactored to fit the framework contract.
 *
 * Recommendation logic is delegated entirely to {@see ResolutionDecisionEngine},
 * left completely untouched by this phase (same file, same 8 passing unit
 * tests from Phase 3.3) — this class only adds the framework's descriptive
 * metadata (questions, business rules, available actions, permission and
 * audit requirements) around that already-validated logic. No recommendation
 * this scenario produces changes as a result of this refactor.
 */
class CancellationRefundScenario implements ResolutionScenario
{
    public const KEY = 'cancellation_refund';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Cancellation / Refund';
    }

    public function trigger(): string
    {
        return 'Customer requests cancellation of a rental order.';
    }

    public function questions(): array
    {
        return [
            new ResolutionQuestion(
                key: 'can_reschedule',
                prompt: 'Can the rental be rescheduled?',
                type: ResolutionQuestion::TYPE_BOOLEAN,
            ),
            new ResolutionQuestion(
                key: 'credit_would_satisfy',
                prompt: 'Would Financial Store Credit satisfy the customer?',
                type: ResolutionQuestion::TYPE_BOOLEAN,
                dependsOn: 'can_reschedule',
            ),
            new ResolutionQuestion(
                key: 'payment_method',
                prompt: 'What payment method was used?',
                type: ResolutionQuestion::TYPE_SELECT,
                options: collect(OrderPaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all(),
                dependsOn: 'credit_would_satisfy',
            ),
        ];
    }

    public function businessRules(): array
    {
        return [
            'Reschedule must always be offered before Store Credit.',
            'Store Credit must always be offered before a Refund.',
            'No reschedule fee exists anywhere in this system — every reschedule is free, regardless of this scenario.',
            'No refund fee exists anywhere in this system — "Standard Refund" and "Waive Refund Fee" are currently equivalent in real-world effect (see PHASE_3_3_RESOLUTION_CENTER.md §1).',
            'Manual employee approval is required for every recommendation; nothing is executed automatically.',
        ];
    }

    /**
     * Delegates entirely to the untouched, already-tested
     * ResolutionDecisionEngine — this method only translates the generic
     * $answers array into that engine's concrete parameters and applies the
     * same "have we reached a terminal branch yet?" gate that previously
     * lived in ResolutionCenterService, moved here since it is genuinely
     * this scenario's own business logic, not generic orchestration.
     */
    public function recommend(array $answers): ?ResolutionRecommendation
    {
        if (! array_key_exists('can_reschedule', $answers) || $answers['can_reschedule'] === null) {
            return null;
        }

        $canReschedule = (bool) $answers['can_reschedule'];
        $creditWouldSatisfy = array_key_exists('credit_would_satisfy', $answers) ? $answers['credit_would_satisfy'] : null;
        $paymentMethod = $answers['payment_method'] ?? null;

        $reachedTerminalBranch = $canReschedule === true
            || ($canReschedule === false && $creditWouldSatisfy === true)
            || ($canReschedule === false && $creditWouldSatisfy === false && $paymentMethod !== null);

        if (! $reachedTerminalBranch) {
            return null;
        }

        return (new ResolutionDecisionEngine)->recommend($canReschedule, $creditWouldSatisfy, $paymentMethod);
    }

    public function availableActions(): array
    {
        return [
            new ScenarioAction(
                key: ResolutionPolicy::RECOMMEND_FREE_RESCHEDULE,
                label: ResolutionPolicy::LABELS[ResolutionPolicy::RECOMMEND_FREE_RESCHEDULE],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'Performed on the Order Edit screen\'s existing reschedule control.',
            ),
            new ScenarioAction(
                key: ResolutionPolicy::RECOMMEND_ISSUE_STORE_CREDIT,
                label: ResolutionPolicy::LABELS[ResolutionPolicy::RECOMMEND_ISSUE_STORE_CREDIT],
                executionType: ScenarioAction::EXECUTION_SERVICE,
                description: 'Executed via CustomerCreditService::createFinancialCredit(), behind an explicit, separate employee approval naming a specific amount.',
            ),
            new ScenarioAction(
                key: ResolutionPolicy::RECOMMEND_STANDARD_REFUND,
                label: ResolutionPolicy::LABELS[ResolutionPolicy::RECOMMEND_STANDARD_REFUND],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'Performed on the Order Edit screen\'s existing Refund action.',
            ),
            new ScenarioAction(
                key: ResolutionPolicy::RECOMMEND_WAIVE_REFUND_FEE,
                label: ResolutionPolicy::LABELS[ResolutionPolicy::RECOMMEND_WAIVE_REFUND_FEE],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'Currently equivalent to Standard Refund — no refund fee exists in this system to waive.',
            ),
        ];
    }

    public function permissionRequirements(): array
    {
        return [
            'view' => 'resolution_center.view',
            'use' => 'resolution_center.use',
            'override' => 'resolution_center.override',
            'view_audit_history' => 'resolution_center.view_audit_history',
            'execute_store_credit' => 'customer_credit.grant',
        ];
    }

    public function auditRequirements(): array
    {
        return ['user', 'date', 'customer', 'order', 'recommendations_shown', 'decision_selected', 'manager_override', 'notes', 'outcome'];
    }

    public function completionCriteria(): array
    {
        return [
            ResolutionCenterService::OUTCOME_PENDING,
            ResolutionCenterService::OUTCOME_COMPLETED,
            ResolutionCenterService::OUTCOME_CANCELLED,
        ];
    }
}
