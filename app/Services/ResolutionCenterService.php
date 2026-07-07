<?php

namespace App\Services;

use App\Helpers\CustomHelper;
use App\Models\Customers\Customer;
use App\Models\Customers\ResolutionCase;
use App\Models\Orders\Order;
use App\Services\ResolutionCenter\CancellationRefundScenario;
use App\Services\ResolutionCenter\ScenarioAction;
use App\Services\ResolutionCenter\ResolutionScenarioRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 * Phase 3.4 — Operational Knowledge Framework: generalized to route
 * recommendation logic through {@see \App\Services\ResolutionCenter\ResolutionScenarioRegistry}
 * instead of instantiating a hardcoded engine.
 * Phase 3.5 — Manual Resolution Scenario Foundation: `recordAnswers()` now
 * takes a generic `array $answers` (previously three concrete,
 * cancellation/refund-shaped parameters) so a scenario with a completely
 * different answer shape — Manual Resolution's `issue_category`/
 * `selected_resolution` — can use the same method; `approveAndIssueCredit()`'s
 * gate now checks the case's own scenario's `service`-typed actions instead
 * of a hardcoded cancellation/refund constant, and accepts an optional
 * `$notes` parameter. Both changes were verified to produce byte-for-byte
 * identical behavior for the Cancellation/Refund scenario — see
 * PHASE_3_5_COMPLETION_REPORT.md's validation section — before being relied
 * on for the new scenario.
 *
 * The only class that persists a {@see ResolutionCase} or calls
 * {@see CustomerCreditService} on the Resolution Center's behalf. Each
 * {@see \App\Services\ResolutionCenter\ResolutionScenario} decides *what*
 * to recommend (pure, no side effects); this class records *that a session
 * happened* and, only on an explicit, separate employee action, executes
 * the one action this framework's actions can be `service`-typed for
 * (issuing Store Credit, for both scenarios that offer it so far).
 * `manual`-typed actions (Reschedule, Refund, Manager Follow-up, etc.) are
 * never executed here — see PHASE_3_3_RESOLUTION_CENTER.md §4 and
 * OPERATIONAL_KNOWLEDGE_FRAMEWORK.md.
 *
 * Does not modify, wrap, or redesign CustomerCreditService — every call
 * into it uses that service's existing public methods exactly as Phase 3.2
 * already established (an optional $orderId), with no new parameters added
 * on its account. Notes are passed through the existing $internalComments
 * parameter Phase 3.1 already added — not a new CustomerCreditService
 * parameter.
 */
class ResolutionCenterService
{
    public const OUTCOME_PENDING = 'pending';

    public const OUTCOME_COMPLETED = 'completed';

    public const OUTCOME_CANCELLED = 'cancelled';

    public const DECISION_FOLLOWED = 'followed_recommendation';

    public const DECISION_OVERRIDDEN = 'overridden';

    /**
     * Open a new resolution case for a customer/order, capturing a
     * point-in-time snapshot of balance, store credit, and payment method
     * for audit purposes only — never re-read as a live figure afterward.
     *
     * @param  string  $scenarioKey  Defaults to the reference implementation
     *                               (cancellation/refund) — Phase 3.4 does not add new
     *                               scenarios, so nothing calls this with any other value yet.
     */
    public static function startCase(int $customerId, int $orderId, string $issue, ?int $responsibleUserId = null, string $scenarioKey = CancellationRefundScenario::KEY): ResolutionCase
    {
        $order = Order::findOrFail($orderId);

        return ResolutionCase::create([
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'scenario_key' => $scenarioKey,
            'issue' => $issue,
            'balance_snapshot' => (float) $order->balance_due,
            'store_credit_snapshot' => CustomerCreditService::remainingBalance($customerId),
            'payment_method_snapshot' => optional($order->last_payment_type)->value,
            'outcome' => self::OUTCOME_PENDING,
            'responsible_person_id' => $responsibleUserId,
        ]);
    }

    /**
     * Record the guided-flow answers available so far, in whatever shape
     * the case's own scenario uses, and — once that scenario reports
     * enough are present — store its recommendation. Safe to call
     * repeatedly as the employee progresses through the guided questions;
     * never recommends prematurely on an incomplete answer set (the
     * scenario itself decides what "enough" means, via a null return from
     * `recommend()`).
     *
     * Known answer keys are also written to their own typed columns for
     * scenarios that have one (`can_reschedule`/`credit_would_satisfy` for
     * Cancellation/Refund, `issue_category` for Manual Resolution) — this
     * keeps each scenario's own dedicated columns populated exactly as
     * before, without this method needing to know which scenario it is
     * serving.
     */
    public static function recordAnswers(int $caseId, array $answers): ResolutionCase
    {
        $case = ResolutionCase::findOrFail($caseId);

        foreach (['can_reschedule', 'credit_would_satisfy', 'issue_category'] as $knownColumn) {
            if (array_key_exists($knownColumn, $answers)) {
                $case->{$knownColumn} = $answers[$knownColumn];
            }
        }

        $scenario = ResolutionScenarioRegistry::get($case->scenario_key ?? CancellationRefundScenario::KEY);
        $recommendation = $scenario->recommend($answers);

        if ($recommendation !== null) {
            $case->recommended_resolution = implode(',', $recommendation->options);
            $case->recommended_next_step = $recommendation->nextStep;
        }

        $case->save();

        return $case->fresh();
    }

    /**
     * Record what the employee actually decided — following the
     * recommendation or overriding it — plus any manager override and
     * notes. Does not itself execute anything.
     */
    public static function recordDecision(
        int $caseId,
        string $employeeDecision,
        ?string $employeeDecisionDetail = null,
        ?int $managerOverrideUserId = null,
        ?string $managerOverrideReason = null,
        ?string $notes = null,
    ): ResolutionCase {
        if (! in_array($employeeDecision, [self::DECISION_FOLLOWED, self::DECISION_OVERRIDDEN], true)) {
            throw new \InvalidArgumentException("ResolutionCenterService::recordDecision() received an unrecognized decision '{$employeeDecision}'.");
        }

        $case = ResolutionCase::findOrFail($caseId);

        $case->update([
            'employee_decision' => $employeeDecision,
            'employee_decision_detail' => $employeeDecisionDetail,
            'manager_override_user_id' => $managerOverrideUserId,
            'manager_override_reason' => $managerOverrideReason,
            'notes' => $notes,
        ]);

        return $case->fresh();
    }

    /**
     * The one service-backed execution any scenario in this framework can
     * offer — issuing Financial Store Credit — reachable only via an
     * explicit, separate employee action naming a specific amount. Not
     * automatic: nothing is issued merely because the recommendation was
     * shown or the case was opened. Delegates entirely to
     * CustomerCreditService, referencing this case's order (the $orderId
     * parameter Phase 3.2 added); $notes (Phase 3.5) is passed through
     * CustomerCreditService's existing $internalComments parameter (Phase
     * 3.1) — not a new parameter added to that service.
     *
     * The gate below checks the case's own scenario's `service`-typed
     * actions (see ScenarioAction::EXECUTION_SERVICE) rather than a single
     * hardcoded recommendation key — generalized in Phase 3.5 so any
     * scenario can offer Store Credit under its own key
     * (Cancellation/Refund's `issue_store_credit`, Manual Resolution's
     * `store_credit`) without this method knowing either name in advance.
     * Verified to produce identical accept/reject decisions to the old
     * hardcoded check for every Cancellation/Refund branch — see
     * PHASE_3_5_COMPLETION_REPORT.md.
     */
    public static function approveAndIssueCredit(int $caseId, float $amount, string $reason, int $responsibleUserId, ?string $notes = null): ResolutionCase
    {
        $case = ResolutionCase::findOrFail($caseId);

        $scenario = ResolutionScenarioRegistry::get($case->scenario_key ?? CancellationRefundScenario::KEY);
        $serviceActionKeys = array_map(
            fn ($action) => $action->key,
            array_filter($scenario->availableActions(), fn ($action) => $action->executionType === ScenarioAction::EXECUTION_SERVICE)
        );
        $recommendedKeys = explode(',', (string) $case->recommended_resolution);

        if (empty(array_intersect($serviceActionKeys, $recommendedKeys))) {
            throw new \InvalidArgumentException(
                "ResolutionCenterService::approveAndIssueCredit() rejected: case {$caseId}'s recommendation was "
                ."'{$case->recommended_resolution}', which is not a service-executable Store Credit action for its scenario."
            );
        }

        return DB::transaction(function () use ($case, $amount, $reason, $responsibleUserId, $notes) {
            $credit = CustomerCreditService::createFinancialCredit(
                customerId: $case->customer_id,
                amount: $amount,
                reason: $reason,
                responsibleUserId: $responsibleUserId,
                internalComments: $notes,
                orderId: $case->order_id,
            );

            $updates = [
                'credit_issued_id' => $credit->id,
                'outcome' => self::OUTCOME_COMPLETED,
            ];
            // Only overwrite notes if this call actually provided some —
            // otherwise leave whatever recordDecision() already stored
            // untouched (matches the pre-Phase-3.5 behavior for
            // Cancellation/Refund, which never passes notes here).
            if ($notes !== null) {
                $updates['notes'] = $notes;
            }
            $case->update($updates);

            return $case->fresh();
        });
    }

    /**
     * Mark the outcome of a case whose recommendation was executed
     * elsewhere (the existing reschedule or refund screens) — closes the
     * audit loop without this service ever having performed that action.
     */
    public static function markOutcome(int $caseId, string $outcome): ResolutionCase
    {
        if (! in_array($outcome, [self::OUTCOME_PENDING, self::OUTCOME_COMPLETED, self::OUTCOME_CANCELLED], true)) {
            throw new \InvalidArgumentException("ResolutionCenterService::markOutcome() received an unrecognized outcome '{$outcome}'.");
        }

        $case = ResolutionCase::findOrFail($caseId);
        $case->update(['outcome' => $outcome]);

        return $case->fresh();
    }

    /**
     * The customer's account status, for display alongside a case — reused
     * exactly as CustomHelper already computes it, never recomputed here.
     */
    public static function customerStatus(int $customerId): array
    {
        return CustomHelper::getCustomerAccountStatus(Customer::findOrFail($customerId));
    }
}
