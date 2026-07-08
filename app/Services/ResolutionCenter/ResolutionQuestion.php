<?php

namespace App\Services\ResolutionCenter;

/**
 * Phase 3.4 — Operational Knowledge Framework.
 *
 * A descriptive manifest entry for one question in a scenario's guided
 * flow — used for framework introspection (e.g. documenting or listing a
 * scenario's questions) and future reuse in later scenarios. The actual
 * conditional step-by-step flow (which question to show next, given prior
 * answers) is not generically evaluated from this object in this phase —
 * it remains implemented directly in each scenario's own controller/view
 * flow, exactly as {@see CancellationRefundScenario}'s already does,
 * matching the reference implementation. A generic conditional-flow
 * evaluator is deliberately deferred until a second scenario exists to
 * validate its shape against — building one now, with only one scenario to
 * generalize from, would be exactly the kind of speculative design this
 * phase's "do not expand scope" rule warns against.
 */
class ResolutionQuestion
{
    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_SELECT = 'select';

    /**
     * @param  string  $key  Matches a key in the array {@see ResolutionScenario::recommend()} receives.
     * @param  string  $type  One of the TYPE_* constants.
     * @param  array  $options  For TYPE_SELECT: value => label.
     * @param  string|null  $dependsOn  Descriptive only — which prior question's answer determines
     *                                  whether this one is asked. Not evaluated by this class.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $prompt,
        public readonly string $type,
        public readonly array $options = [],
        public readonly ?string $dependsOn = null,
    ) {
    }
}
