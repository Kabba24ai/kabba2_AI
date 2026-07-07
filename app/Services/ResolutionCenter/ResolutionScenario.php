<?php

namespace App\Services\ResolutionCenter;

/**
 * Phase 3.4 — Operational Knowledge Framework.
 *
 * The reusable contract every Resolution Center scenario implements — the
 * nine elements this phase's mission names: Scenario (key/label), Trigger,
 * Required Questions, Business Rules, Recommendation Logic, Available
 * Actions, Permission Requirements, Audit Requirements, Completion
 * Criteria. See docs/customer-credit/OPERATIONAL_KNOWLEDGE_FRAMEWORK.md for
 * the full design and how future scenarios (Equipment Exchange, Damage
 * Settlement, etc.) would implement it.
 *
 * {@see CancellationRefundScenario} is the reference implementation — the
 * existing, already-validated cancellation/refund workflow from Phase 3.3,
 * refactored to fit this contract with zero behavior change.
 */
interface ResolutionScenario
{
    /**
     * A stable, unique identifier — stored on `resolution_cases.scenario_key`.
     */
    public function key(): string;

    /**
     * Human-readable name, for display.
     */
    public function label(): string;

    /**
     * What starts this scenario — a plain-language description, not a
     * machine-evaluated condition. Employees (not code) decide when a
     * scenario applies, consistent with "the system guides, the employee
     * decides."
     */
    public function trigger(): string;

    /**
     * The ordered questions this scenario's guided flow asks.
     *
     * @return ResolutionQuestion[]
     */
    public function questions(): array;

    /**
     * Human-readable statements of the approved policy this scenario
     * encodes — documentation, not executable logic. Exists so a scenario's
     * policy basis is reviewable independent of reading its code.
     *
     * @return string[]
     */
    public function businessRules(): array;

    /**
     * Pure recommendation logic: given the answers collected so far (keyed
     * by each ResolutionQuestion's `key`), return a recommendation, or null
     * if not enough answers have been given yet to reach a terminal branch.
     * Must have no side effects — no persistence, no service calls — so it
     * stays independently unit-testable, the same discipline
     * {@see ResolutionDecisionEngine} already established.
     */
    public function recommend(array $answers): ?ResolutionRecommendation;

    /**
     * The actions a recommendation from this scenario can lead to.
     *
     * @return ScenarioAction[]
     */
    public function availableActions(): array;

    /**
     * Which permission gates which capability for this scenario, as
     * `capability => permission_name` — documentation of what routes
     * already enforce, not a second enforcement mechanism.
     */
    public function permissionRequirements(): array;

    /**
     * Which fields this scenario's audit trail must capture. For any
     * scenario built on `resolution_cases` (the reusable audit table every
     * scenario shares), this is the same universal set Phase 3.3 already
     * established.
     *
     * @return string[]
     */
    public function auditRequirements(): array;

    /**
     * The valid outcome values a case for this scenario can end in.
     *
     * @return string[]
     */
    public function completionCriteria(): array;
}
