<?php

namespace App\Services\ResolutionCenter;

use App\Services\ResolutionCenterService;

/**
 * Phase 3.5 — Manual Resolution Scenario Foundation.
 *
 * The Operational Knowledge Framework's second scenario (Phase 3.4 built
 * the framework against exactly one — this is its first real test against
 * a genuinely differently-shaped one). Per the business decision this
 * phase's mission opens with — "many customer-resolution scenarios are too
 * diverse to force into rigid automation" — this scenario does not compute
 * or narrow a recommendation the way {@see CancellationRefundScenario}
 * does. The employee categorizes the issue and selects the resolution
 * directly; `recommend()` only echoes that choice back with contextual
 * next-step guidance, once both answers are present. No branching logic,
 * no invented policy about which resolution fits which category — the
 * framework's `recommend()` contract accommodates this shape without any
 * change to the interface itself.
 */
class ManualResolutionScenario implements ResolutionScenario
{
    public const KEY = 'manual_resolution';

    // Issue categories — from this phase's mission, verbatim.
    public const CATEGORY_EQUIPMENT_EXCHANGE = 'equipment_exchange';

    public const CATEGORY_WRONG_EQUIPMENT = 'wrong_equipment';

    public const CATEGORY_EQUIPMENT_ISSUE = 'equipment_issue';

    public const CATEGORY_WEATHER_DELAY = 'weather_delay';

    public const CATEGORY_JOB_DELAY = 'job_delay';

    public const CATEGORY_CUSTOMER_CANCELLATION = 'customer_cancellation';

    public const CATEGORY_PRICING_CONCERN = 'pricing_concern';

    public const CATEGORY_DAMAGE_DISPUTE = 'damage_dispute';

    public const CATEGORY_GOODWILL_REQUEST = 'goodwill_request';

    public const CATEGORY_OTHER = 'other';

    public const CATEGORY_LABELS = [
        self::CATEGORY_EQUIPMENT_EXCHANGE => 'Equipment Exchange',
        self::CATEGORY_WRONG_EQUIPMENT => 'Wrong Equipment',
        self::CATEGORY_EQUIPMENT_ISSUE => 'Equipment Issue',
        self::CATEGORY_WEATHER_DELAY => 'Weather Delay',
        self::CATEGORY_JOB_DELAY => 'Job Delay',
        self::CATEGORY_CUSTOMER_CANCELLATION => 'Customer Cancellation',
        self::CATEGORY_PRICING_CONCERN => 'Pricing Concern',
        self::CATEGORY_DAMAGE_DISPUTE => 'Damage Dispute',
        self::CATEGORY_GOODWILL_REQUEST => 'Goodwill Request',
        self::CATEGORY_OTHER => 'Other',
    ];

    // Resolution options — from this phase's mission, verbatim.
    public const RESOLUTION_RESCHEDULE = 'reschedule';

    public const RESOLUTION_EQUIPMENT_EXCHANGE_RECOMMENDED = 'equipment_exchange_recommended';

    public const RESOLUTION_STORE_CREDIT = 'store_credit';

    public const RESOLUTION_REFUND_REVIEW = 'refund_review';

    public const RESOLUTION_MANAGER_FOLLOW_UP = 'manager_follow_up';

    public const RESOLUTION_NO_ACTION = 'no_action';

    public const RESOLUTION_OTHER = 'other_manual_resolution';

    public const RESOLUTION_LABELS = [
        self::RESOLUTION_RESCHEDULE => 'Reschedule',
        self::RESOLUTION_EQUIPMENT_EXCHANGE_RECOMMENDED => 'Equipment Exchange Recommended',
        self::RESOLUTION_STORE_CREDIT => 'Store Credit',
        self::RESOLUTION_REFUND_REVIEW => 'Refund Review',
        self::RESOLUTION_MANAGER_FOLLOW_UP => 'Manager Follow-up',
        self::RESOLUTION_NO_ACTION => 'No Action',
        self::RESOLUTION_OTHER => 'Other Manual Resolution',
    ];

    private const NEXT_STEP_TEXT = [
        self::RESOLUTION_RESCHEDULE => 'Use the existing reschedule option on this order\'s Edit screen. No fee applies — rescheduling has never had a fee in this system.',
        self::RESOLUTION_EQUIPMENT_EXCHANGE_RECOMMENDED => 'Coordinate the equipment swap directly with the store/warehouse — no automated equipment-exchange mechanism exists in this system yet.',
        self::RESOLUTION_STORE_CREDIT => 'Approve below to issue Financial Store Credit via CustomerCreditService, referenced to this order. Amount, reason, and notes are all required for this scenario.',
        self::RESOLUTION_REFUND_REVIEW => 'Escalate to a manager for review, then use the existing Refund action on this order\'s Edit screen if approved — this system never processes the refund automatically.',
        self::RESOLUTION_MANAGER_FOLLOW_UP => 'Escalate this case to a manager. Record the outcome here once it has been followed up on.',
        self::RESOLUTION_NO_ACTION => 'No further action is required. Mark this case Completed once confirmed with the customer.',
        self::RESOLUTION_OTHER => 'Document exactly what manual action was taken in Notes, since it falls outside the standard resolution options.',
    ];

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Manual Resolution';
    }

    public function trigger(): string
    {
        return 'A customer issue that does not fit a rigid decision tree — the employee categorizes it and selects the appropriate resolution directly.';
    }

    public function questions(): array
    {
        return [
            new ResolutionQuestion(
                key: 'issue_category',
                prompt: 'What category best describes this issue?',
                type: ResolutionQuestion::TYPE_SELECT,
                options: self::CATEGORY_LABELS,
            ),
            new ResolutionQuestion(
                key: 'selected_resolution',
                prompt: 'What resolution has been decided?',
                type: ResolutionQuestion::TYPE_SELECT,
                options: self::RESOLUTION_LABELS,
                dependsOn: 'issue_category',
            ),
        ];
    }

    public function businessRules(): array
    {
        return [
            'This scenario does not compute or narrow a recommendation — the employee selects the resolution directly, per the approved business decision that many resolution scenarios are too diverse for a rigid decision tree.',
            'Store Credit is only issued via an explicit, separate "Approve & Issue" action requiring an amount, reason, and notes — never automatically, and never merely because this resolution was selected.',
            'No order, inventory, or refund is modified automatically by this scenario.',
        ];
    }

    /**
     * Not a computed recommendation — an echo of the employee's own
     * selection, paired with contextual guidance for carrying it out. Null
     * until both the category and the resolution have been recorded.
     */
    public function recommend(array $answers): ?ResolutionRecommendation
    {
        if (empty($answers['issue_category']) || empty($answers['selected_resolution'])) {
            return null;
        }

        $selected = $answers['selected_resolution'];

        return new ResolutionRecommendation(
            options: [$selected],
            nextStep: self::NEXT_STEP_TEXT[$selected] ?? 'Document the outcome once this resolution has been carried out.',
        );
    }

    public function availableActions(): array
    {
        return [
            new ScenarioAction(
                key: self::RESOLUTION_RESCHEDULE,
                label: self::RESOLUTION_LABELS[self::RESOLUTION_RESCHEDULE],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'Performed on the Order Edit screen\'s existing reschedule control.',
            ),
            new ScenarioAction(
                key: self::RESOLUTION_EQUIPMENT_EXCHANGE_RECOMMENDED,
                label: self::RESOLUTION_LABELS[self::RESOLUTION_EQUIPMENT_EXCHANGE_RECOMMENDED],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'Coordinated manually with the store/warehouse — no equipment-exchange mechanism exists in this system.',
            ),
            new ScenarioAction(
                key: self::RESOLUTION_STORE_CREDIT,
                label: self::RESOLUTION_LABELS[self::RESOLUTION_STORE_CREDIT],
                executionType: ScenarioAction::EXECUTION_SERVICE,
                description: 'Executed via CustomerCreditService::createFinancialCredit(), behind an explicit, separate employee approval requiring amount, reason, and notes.',
            ),
            new ScenarioAction(
                key: self::RESOLUTION_REFUND_REVIEW,
                label: self::RESOLUTION_LABELS[self::RESOLUTION_REFUND_REVIEW],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'Reviewed by a manager, then performed manually via the Order Edit screen\'s existing Refund action if approved.',
            ),
            new ScenarioAction(
                key: self::RESOLUTION_MANAGER_FOLLOW_UP,
                label: self::RESOLUTION_LABELS[self::RESOLUTION_MANAGER_FOLLOW_UP],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'Escalated to a manager outside this system; the outcome is recorded here once resolved.',
            ),
            new ScenarioAction(
                key: self::RESOLUTION_NO_ACTION,
                label: self::RESOLUTION_LABELS[self::RESOLUTION_NO_ACTION],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'No further action taken — the case is documented and closed.',
            ),
            new ScenarioAction(
                key: self::RESOLUTION_OTHER,
                label: self::RESOLUTION_LABELS[self::RESOLUTION_OTHER],
                executionType: ScenarioAction::EXECUTION_MANUAL,
                description: 'A manual action outside the standard options, fully described in Notes.',
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
        return ['user', 'customer', 'order', 'issue_category', 'selected_resolution', 'notes', 'manager_override', 'outcome', 'timestamp'];
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
