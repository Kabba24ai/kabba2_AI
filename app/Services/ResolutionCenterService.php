<?php

namespace App\Services;

use App\Helpers\CustomHelper;
use App\Models\Customers\Customer;
use App\Models\Customers\ResolutionCase;
use App\Models\Customers\ResolutionCaseActivityLog;
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

    // Phase 3.6 — Customer Resolution Operations Center. `status` is a new,
    // purely operational triage field — a separate concept from `outcome`
    // above, not a redefinition of it. See PHASE_3_6_OPERATIONS_AUDIT.md §5
    // for the exact, deliberate mirroring rules between the two.
    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_COMPLETED = 'completed';

    public const WAITING_ON_CUSTOMER = 'customer';

    public const WAITING_ON_EMPLOYEE = 'employee';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_WAITING,
            self::STATUS_ESCALATED, self::STATUS_COMPLETED,
        ], true);
    }

    public static function isValidPriority(string $priority): bool
    {
        return in_array($priority, [
            self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT,
        ], true);
    }

    public static function isValidWaitingOn(string $waitingOn): bool
    {
        return in_array($waitingOn, [self::WAITING_ON_CUSTOMER, self::WAITING_ON_EMPLOYEE], true);
    }

    /**
     * The Audit Trail's single write path — every mutating method in this
     * class calls this, so the trail is genuinely complete rather than
     * covering only the operational actions this phase added.
     */
    private static function logActivity(int $caseId, ?int $userId, string $action, ?string $field = null, $old = null, $new = null): void
    {
        ResolutionCaseActivityLog::create([
            'resolution_case_id' => $caseId,
            'user_id' => $userId,
            'action' => $action,
            'field' => $field,
            'old_value' => $old === null ? null : (string) $old,
            'new_value' => $new === null ? null : (string) $new,
        ]);
    }

    /**
     * Best-effort store snapshot for a resolution case, following the same
     * snapshot-at-creation precedent as this table's existing
     * `balance_snapshot`/`store_credit_snapshot` columns. `orders` has no
     * `store_id` of its own — only its line items
     * (`order_products.delivery_store_id`/`pickup_store_id`) reference a
     * store, and a multi-line order can genuinely span more than one.
     * Returns null (rather than guessing) whenever more than one distinct
     * store is found — see PHASE_3_6_OPERATIONS_AUDIT.md §4.
     */
    private static function resolveStoreId(Order $order): ?int
    {
        $storeIds = $order->products()
            ->get(['delivery_store_id', 'pickup_store_id'])
            ->flatMap(fn ($product) => [$product->delivery_store_id, $product->pickup_store_id])
            ->filter()
            ->unique();

        return $storeIds->count() === 1 ? $storeIds->first() : null;
    }

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

        $case = ResolutionCase::create([
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'scenario_key' => $scenarioKey,
            'issue' => $issue,
            'balance_snapshot' => (float) $order->balance_due,
            'store_credit_snapshot' => CustomerCreditService::remainingBalance($customerId),
            'payment_method_snapshot' => optional($order->last_payment_type)->value,
            'outcome' => self::OUTCOME_PENDING,
            'status' => self::STATUS_OPEN,
            'priority' => self::PRIORITY_NORMAL,
            'responsible_person_id' => $responsibleUserId,
            'store_id' => self::resolveStoreId($order),
        ]);

        self::logActivity($case->id, $responsibleUserId, 'case_opened');

        return $case;
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

        self::logActivity($case->id, auth()->id(), 'answers_recorded', 'answers', null, implode(',', array_map(
            fn ($key, $value) => "{$key}={$value}",
            array_keys($answers),
            $answers
        )));

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

        self::logActivity($case->id, auth()->id(), 'decision_recorded', 'employee_decision', null, $employeeDecision);

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
                // Phase 3.6 — mirrors the operational `status` to
                // `completed` whenever `outcome` reaches a terminal state,
                // same rule `markOutcome()` applies below.
                'status' => self::STATUS_COMPLETED,
                'completed_at' => $case->completed_at ?? now(),
            ];
            // Only overwrite notes if this call actually provided some —
            // otherwise leave whatever recordDecision() already stored
            // untouched (matches the pre-Phase-3.5 behavior for
            // Cancellation/Refund, which never passes notes here).
            if ($notes !== null) {
                $updates['notes'] = $notes;
            }
            $case->update($updates);

            self::logActivity($case->id, $responsibleUserId, 'store_credit_issued', 'credit_issued_id', null, $credit->id);

            return $case->fresh();
        });
    }

    /**
     * Mark the outcome of a case whose recommendation was executed
     * elsewhere (the existing reschedule or refund screens) — closes the
     * audit loop without this service ever having performed that action.
     */
    /**
     * Phase 3.6: also mirrors the operational `status` field to keep it in
     * sync with `outcome`'s terminal states — see
     * PHASE_3_6_OPERATIONS_AUDIT.md §5. `outcome` itself is completely
     * unchanged: same 3 values, same meaning, same callers.
     */
    public static function markOutcome(int $caseId, string $outcome): ResolutionCase
    {
        if (! in_array($outcome, [self::OUTCOME_PENDING, self::OUTCOME_COMPLETED, self::OUTCOME_CANCELLED], true)) {
            throw new \InvalidArgumentException("ResolutionCenterService::markOutcome() received an unrecognized outcome '{$outcome}'.");
        }

        $case = ResolutionCase::findOrFail($caseId);
        $previousStatus = $case->status;
        $updates = ['outcome' => $outcome];

        if (in_array($outcome, [self::OUTCOME_COMPLETED, self::OUTCOME_CANCELLED], true)) {
            $updates['status'] = self::STATUS_COMPLETED;
            $updates['completed_at'] = $case->completed_at ?? now();
        } elseif ($outcome === self::OUTCOME_PENDING && $case->status === self::STATUS_COMPLETED) {
            // Reopening a previously-completed case.
            $updates['status'] = self::STATUS_OPEN;
            $updates['completed_at'] = null;
        }

        $case->update($updates);

        self::logActivity($case->id, auth()->id(), 'outcome_changed', 'outcome', null, $outcome);
        if (($updates['status'] ?? $previousStatus) !== $previousStatus) {
            self::logActivity($case->id, auth()->id(), 'status_changed', 'status', $previousStatus, $updates['status']);
        }

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

    /**
     * Phase 3.6 — Customer Resolution Operations Center.
     *
     * Assign or reassign a case's current owner. Deliberately distinct from
     * `responsible_person_id` (set once at case creation, never changed by
     * any existing code path). Assigning a case to a real user moves a
     * still-`open` case to `in_progress` — a reflection that someone now
     * owns it, not the system choosing an assignee (that remains manual and
     * explicit, per this phase's "no automatic assignment" rule).
     */
    public static function assignCase(int $caseId, ?int $assignedToUserId, int $performedByUserId): ResolutionCase
    {
        $case = ResolutionCase::findOrFail($caseId);
        $previous = $case->assigned_to_user_id;

        $updates = ['assigned_to_user_id' => $assignedToUserId];
        if ($assignedToUserId !== null && $case->status === self::STATUS_OPEN) {
            $updates['status'] = self::STATUS_IN_PROGRESS;
        }
        $case->update($updates);

        $action = $previous === null ? 'assigned' : ($assignedToUserId === null ? 'unassigned' : 'reassigned');
        self::logActivity($case->id, $performedByUserId, $action, 'assigned_to_user_id', $previous, $assignedToUserId);

        return $case->fresh();
    }

    public static function setPriority(int $caseId, string $priority, int $performedByUserId): ResolutionCase
    {
        if (! self::isValidPriority($priority)) {
            throw new \InvalidArgumentException("ResolutionCenterService::setPriority() received an unrecognized priority '{$priority}'.");
        }

        $case = ResolutionCase::findOrFail($caseId);
        $previous = $case->priority;
        $case->update(['priority' => $priority]);

        self::logActivity($case->id, $performedByUserId, 'priority_changed', 'priority', $previous, $priority);

        return $case->fresh();
    }

    /**
     * The mission's "Mark Waiting" manager function. `$waitingOn`
     * distinguishes "Waiting on Customer" from "Waiting on Employee" for
     * the two separate dashboard metrics — see
     * PHASE_3_6_OPERATIONS_AUDIT.md §4.
     */
    public static function markWaiting(int $caseId, string $waitingOn, int $performedByUserId, ?string $note = null): ResolutionCase
    {
        if (! self::isValidWaitingOn($waitingOn)) {
            throw new \InvalidArgumentException("ResolutionCenterService::markWaiting() received an unrecognized waiting_on '{$waitingOn}'.");
        }

        $case = ResolutionCase::findOrFail($caseId);
        $previousStatus = $case->status;

        $updates = ['status' => self::STATUS_WAITING, 'waiting_on' => $waitingOn];
        if ($note !== null) {
            $updates['notes'] = $note;
        }
        $case->update($updates);

        self::logActivity($case->id, $performedByUserId, 'marked_waiting', 'status', $previousStatus, self::STATUS_WAITING);

        return $case->fresh();
    }

    /**
     * The mission's "Escalate" manager function — raises the case for
     * management attention. Never touches `outcome`, `notes` (beyond the
     * activity log entry), or any financial state.
     */
    public static function escalate(int $caseId, int $performedByUserId, ?string $reason = null): ResolutionCase
    {
        $case = ResolutionCase::findOrFail($caseId);
        $previousStatus = $case->status;
        $case->update(['status' => self::STATUS_ESCALATED]);

        self::logActivity($case->id, $performedByUserId, 'escalated', 'status', $previousStatus, self::STATUS_ESCALATED);
        if ($reason !== null) {
            self::logActivity($case->id, $performedByUserId, 'escalation_reason', 'escalation_reason', null, $reason);
        }

        return $case->fresh();
    }

    /**
     * Thin, explicitly-named wrappers around `markOutcome()` for the
     * mission's "Close Case"/"Reopen Case" manager quick-actions — avoids
     * forcing a manager quick-action button to fabricate an
     * `employee_decision` value just to flip `outcome`.
     */
    public static function closeCase(int $caseId): ResolutionCase
    {
        return self::markOutcome($caseId, self::OUTCOME_COMPLETED);
    }

    public static function reopenCase(int $caseId): ResolutionCase
    {
        return self::markOutcome($caseId, self::OUTCOME_PENDING);
    }

    /**
     * The Operations Center's dashboard stat cards — operational metrics
     * only (current queue state), never historical reporting/analytics,
     * per this phase's explicit scope boundary.
     */
    public static function dashboardMetrics(): array
    {
        $todayStart = now()->startOfDay();
        $activeStatuses = [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_WAITING, self::STATUS_ESCALATED];

        $oldestOpen = ResolutionCase::whereIn('status', $activeStatuses)->oldest('created_at')->first();

        $completedToday = ResolutionCase::where('status', self::STATUS_COMPLETED)
            ->where('completed_at', '>=', $todayStart)
            ->get(['created_at', 'completed_at']);

        $averageResolutionHours = $completedToday->isEmpty() ? null : round(
            $completedToday->avg(fn ($case) => $case->created_at->diffInMinutes($case->completed_at)) / 60,
            1
        );

        return [
            'open_cases' => ResolutionCase::where('status', self::STATUS_OPEN)->count(),
            'waiting_on_customer' => ResolutionCase::where('status', self::STATUS_WAITING)->where('waiting_on', self::WAITING_ON_CUSTOMER)->count(),
            'waiting_on_employee' => ResolutionCase::where('status', self::STATUS_WAITING)->where('waiting_on', self::WAITING_ON_EMPLOYEE)->count(),
            'manager_review_required' => ResolutionCase::where('status', self::STATUS_ESCALATED)->count(),
            'completed_today' => $completedToday->count(),
            'average_resolution_hours' => $averageResolutionHours,
            'oldest_open_case_age_days' => $oldestOpen ? $oldestOpen->created_at->diffInDays(now()) : null,
            'oldest_open_case' => $oldestOpen,
            'store_credit_issued_today' => (float) \App\Models\Customers\CustomerCredit::whereIn(
                'id',
                ResolutionCase::whereNotNull('credit_issued_id')->pluck('credit_issued_id')
            )->where('created_at', '>=', $todayStart)->sum('amount'),
        ];
    }
}
