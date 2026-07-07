<?php

namespace App\Services\ResolutionCenter;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 *
 * A plain value object — what {@see ResolutionDecisionEngine} returns.
 * Never persisted directly; {@see \App\Services\ResolutionCenterService}
 * copies its fields onto a {@see \App\Models\Customers\ResolutionCase} row.
 */
class ResolutionRecommendation
{
    /**
     * @param  string[]  $options  One or more recommendation keys (see
     *                             ResolutionPolicy::RECOMMEND_*). Almost
     *                             always one — two only for the Credit
     *                             Card refund branch, which the mission's
     *                             decision tree explicitly offers as a
     *                             choice between Standard Refund and Waive
     *                             Refund Fee.
     */
    public function __construct(
        public readonly array $options,
        public readonly string $nextStep,
        public readonly bool $requiresManagerOverride = false,
    ) {}

    public function primaryOption(): string
    {
        return $this->options[0];
    }

    public function labels(): array
    {
        return array_map(fn ($key) => ResolutionPolicy::LABELS[$key] ?? $key, $this->options);
    }
}
