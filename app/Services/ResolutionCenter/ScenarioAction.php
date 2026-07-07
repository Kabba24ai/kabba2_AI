<?php

namespace App\Services\ResolutionCenter;

/**
 * Phase 3.4 — Operational Knowledge Framework.
 *
 * Describes one action a scenario's recommendation can lead to. Purely
 * descriptive — this object does not execute anything. `executionType`
 * names which of the two categories every action in this framework must
 * fall into, per the Resolution Center's core principle ("the system
 * guides, the employee decides — nothing happens automatically"):
 *
 * - 'service': reachable only via an explicit, separate employee action
 *   that a controller executes through an existing service (e.g.
 *   CustomerCreditService) — never automatic, but genuinely executed by
 *   this system.
 * - 'manual': recorded here, but performed by the employee on a different,
 *   already-existing screen (e.g. the Order Edit reschedule/refund
 *   controls) — this system never executes it.
 */
class ScenarioAction
{
    public const EXECUTION_SERVICE = 'service';

    public const EXECUTION_MANUAL = 'manual';

    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $executionType,
        public readonly string $description,
    ) {
    }
}
