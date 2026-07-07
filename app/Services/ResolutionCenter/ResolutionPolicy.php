<?php

namespace App\Services\ResolutionCenter;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 *
 * The approved priority order this initiative's mission mandates —
 * Reschedule first, Store Credit second, Refund last — expressed as
 * constants only. This class holds no logic; it exists so the priority
 * order is named in exactly one place rather than implied by the shape of
 * {@see ResolutionDecisionEngine}.
 */
class ResolutionPolicy
{
    public const RECOMMEND_FREE_RESCHEDULE = 'free_reschedule';

    public const RECOMMEND_ISSUE_STORE_CREDIT = 'issue_store_credit';

    public const RECOMMEND_STANDARD_REFUND = 'standard_refund';

    public const RECOMMEND_WAIVE_REFUND_FEE = 'waive_refund_fee';

    /**
     * Priority order, first to last — reschedule is always attempted
     * before store credit, which is always attempted before a refund.
     */
    public const PRIORITY_ORDER = [
        self::RECOMMEND_FREE_RESCHEDULE,
        self::RECOMMEND_ISSUE_STORE_CREDIT,
        self::RECOMMEND_STANDARD_REFUND,
        self::RECOMMEND_WAIVE_REFUND_FEE,
    ];

    public const LABELS = [
        self::RECOMMEND_FREE_RESCHEDULE => 'Free Reschedule',
        self::RECOMMEND_ISSUE_STORE_CREDIT => 'Issue Store Credit',
        self::RECOMMEND_STANDARD_REFUND => 'Standard Refund',
        self::RECOMMEND_WAIVE_REFUND_FEE => 'Waive Refund Fee',
    ];
}
