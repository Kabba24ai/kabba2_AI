# Phase 3.5 — Manual Resolution Scenario Foundation: Completion Report

Last updated: 2026-07-06

---

## Executive Summary

Manual Resolution — the Operational Knowledge Framework's second scenario — is complete and reachable end-to-end. Its backend (`ManualResolutionScenario`, its controller/request/route, the registry entry, and `ResolutionCenterService`'s generalization to serve any scenario generically) was found already built and uncommitted at the start of this phase; this phase's own work closed the three gaps that made it unusable and unverified: no UI path existed to reach it, one framework test was left failing from before the scenario was registered, and no scenario-specific test or documentation existed. See `PHASE_3_5_MANUAL_RESOLUTION.md` for full design detail.

## Files Created (this phase)

- `tests/Unit/Services/ResolutionCenter/ManualResolutionScenarioTest.php` — 8 tests covering key/label, the null-until-both-answers gate, the echo-back recommendation for every one of the 7 resolution options, `availableActions()` coverage, the Store-Credit-is-the-only-service-action invariant, permission reuse, and completion criteria.
- `docs/customer-credit/PHASE_3_5_MANUAL_RESOLUTION.md`
- `docs/customer-credit/PHASE_3_5_COMPLETION_REPORT.md` (this file)

## Files Modified (this phase)

- `tests/Unit/Services/ResolutionCenter/ResolutionScenarioRegistryTest.php` — `test_all_returns_every_registered_scenario` asserted `assertCount(1, ...)` from before Manual Resolution was registered; a real, currently-failing regression. Updated to assert 2, plus an explicit `ManualResolutionScenario` instance check.
- `resources/views/admin/resolution_center/show.blade.php` — added a `scenario_key`-gated branch rendering Manual Resolution's single-step "Categorize & Resolve" form; the existing Cancellation/Refund three-step wizard was moved under its own branch, not otherwise edited. Generalized two lines shared by both scenarios: the Store Credit gate now recognizes either scenario's action key, and resolution-key badge labels are looked up through a merged label map instead of only `ResolutionPolicy::LABELS`.
- `resources/views/admin/order_management/orders/edit.blade.php` — added a scenario picker (built from `ResolutionScenarioRegistry::all()`) to the existing "Start Resolution Case" panel, defaulting to `cancellation_refund` so a user who ignores the new control gets identical behavior to before.

## Pre-Existing, Uncommitted Work Found at Phase Start (not created by this phase, verified and relied upon)

- `app/Services/ResolutionCenter/ManualResolutionScenario.php`, `app/Http/Controllers/Admin/ResolutionCenter/ManualResolutionAnswerController.php`, `app/Http/Requests/Admin/ResolutionCenter/ManualResolutionAnswerRequest.php`, the `manual-answer` route, and the `ResolutionScenarioRegistry` entry.
- `ResolutionCenterService::recordAnswers()`'s generalization to a generic `array $answers`, and `approveAndIssueCredit()`'s generalization to gate on the case's own scenario's `EXECUTION_SERVICE` actions rather than a hardcoded Cancellation/Refund constant.
- `database/migrations/customers/2026_07_04_150000_add_scenario_key_to_resolution_cases_table.php` and `..._180000_add_issue_category_to_resolution_cases_table.php`.

## Existing Workflow Preserved

Verified three ways that Cancellation/Refund's behavior is unchanged:

1. **Default scenario.** `StoreCaseRequest::prepareForValidation()` still defaults `scenario_key` to `cancellation_refund` when the field is absent — confirmed live (see Validation Results): a case started the old way (no `scenario_key` in the request) resolves to `cancellation_refund`, exactly as before this phase.
2. **Wizard rendering.** The three-step Cancellation/Refund wizard in `show.blade.php` is byte-identical to its pre-phase form, only moved inside a scenario-key `@if` branch it did not previously need.
3. **Generalized gates produce identical results.** `approveAndIssueCredit()`'s new generic gate (case's scenario's own `service`-typed action keys, intersected with the recommendation) reduces to exactly the old hardcoded `issue_store_credit`-only check for `CancellationRefundScenario`, since that scenario's only `EXECUTION_SERVICE` action is still keyed `issue_store_credit`. Verified live: a Cancellation/Refund case recommending `free_reschedule` still resolves correctly, and the label/store-credit-gate Blade generalizations use a disjoint key space between the two scenarios (verified by direct inspection of both label maps), so neither lookup can change Cancellation/Refund's rendered output.

## Business Policies Enforced

- Store Credit is the only resolution any scenario in this framework can execute automatically, and only behind an explicit, separate, amount-naming "Approve & Issue" action — never merely because a case reached that recommendation.
- Every other Manual Resolution outcome (Reschedule, Equipment Exchange Recommended, Refund Review, Manager Follow-up, No Action, Other) is manual: recorded here, performed elsewhere (or nowhere, for No Action), never automated.
- No AI-generated recommendation exists anywhere in this scenario — `recommend()` only echoes the employee's own selection.

## Validation Results

Ran live against the real, unmodified `ResolutionCenterService`/`CustomerCreditService`/`ManualResolutionScenario` classes, inside a rolled-back `DB::transaction()` (same technique established since Phase 2.4), using a synthetic customer/order/employee created and destroyed within the same transaction. Two unrelated, pre-existing local-database schema gaps (`order_payments.deleted_at`, `users.deleted_at`/`employee_code` — this SQLite dev database is missing columns some MySQL-only migrations add, the same class of gap disclosed in Phase 3.3's own validation) were patched for this script only, inside the same transaction, and rolled back with everything else — confirmed via `PRAGMA table_info` afterward showing neither column present in the real schema.

Checks performed and results:

1. Started a case with `scenarioKey: ManualResolutionScenario::KEY` → `resolution_cases.scenario_key` persisted as `manual_resolution`, `outcome` = `pending`. **Passed.**
2. `recordAnswers(['issue_category' => 'wrong_equipment', 'selected_resolution' => 'store_credit'])` → `issue_category` column written, `recommended_resolution` = `store_credit`, `recommended_next_step` matched the scenario's own `NEXT_STEP_TEXT` for Store Credit exactly. **Passed.**
3. `approveAndIssueCredit(amount: 42.50, ...)` → `CustomerCreditService::remainingBalance()` moved from `0` to `42.5`; case `outcome` flipped to `completed`; `credit_issued_id` set to the real, newly-created `customer_credits` row. **Passed.**
4. A second case selecting `no_action` (a manual-only resolution) → `approveAndIssueCredit()` correctly threw `InvalidArgumentException`, refusing to issue credit for a non-Store-Credit resolution. **Passed** — proves the generalized service-action gate rejects incorrectly just as it accepts correctly.
5. A case started with no `scenarioKey` argument (mirroring the existing Order Edit form before this phase's picker was added) → `scenario_key` defaulted to `cancellation_refund`. **Passed.**
6. That default-scenario case's own `recommend()` (via `recordAnswers(['can_reschedule' => true])`) → `recommended_resolution` = `free_reschedule`, matching pre-existing Cancellation/Refund behavior exactly. **Passed.**
7. Post-rollback: `customers`, `orders`, `resolution_cases`, `users`, and `customer_credits` row counts all confirmed `0` via direct SQL — zero residue. **Passed.**

Both edited Blade files (`show.blade.php`, `edit.blade.php`) were compiled through the real, fully-booted `BladeCompiler` (resolving `x-heroicon-*` component tags via the actual service container, not a bare compiler instance) and linted with `php -l` — no syntax errors in either.

## Test Results

- `tests/Unit` suite: **87/87 passing** (74 pre-existing + 8 new `ManualResolutionScenarioTest` + `ResolutionScenarioRegistryTest`'s existing 5, one of which was fixed by this phase — up from the 74/75 green/1-failing state found at phase start).
- `tests/Feature` suite: **not run** — same standing environment limitation documented since Phase 2.1 (no configured test database; the console-boot HTTPS-redirect issue also documented since Phase 2.4, worked around here only informally via `VITE_ORIGIN_PROTOCOL=http` for manual `artisan tinker`/Blade-lint invocations, not fixed).

## Risks

- **The two local-database schema gaps patched during validation** (`order_payments.deleted_at`, `users.deleted_at`, `users.employee_code`) are pre-existing environment drift, not introduced by this phase — but they mean this SQLite dev database cannot currently run `CustomerCreditService`/`ResolutionCenterService` code paths touching those tables without a temporary patch. Not fixed here (out of this phase's scope, per its own "no unrelated fixes" rule) — flagged for whoever eventually builds real test-database infrastructure (open item since Phase 2.1).
- **No automated Feature/browser test exists for the new UI branch** — the scenario picker and the "Categorize & Resolve" form were verified by Blade compilation (real container) and by the live service-layer validation above, not by driving the actual HTTP request/response cycle or a browser. A manual click-through in a real environment is recommended before this ships.
- **"Other Manual Resolution" and several manual outcomes (Equipment Exchange Recommended, Manager Follow-up) have no dedicated mechanism of their own** — by design, per this phase's mission (their entire purpose is to fall back to Notes/manual coordination), but worth naming so a future reviewer doesn't mistake this for an oversight.

## Overall Epic Progress

Customer Credit Platform (Epic 2): Phase 3.0 through 3.5 all complete. The Operational Knowledge Framework (Phase 3.4) now has two real scenarios — Cancellation/Refund and Manual Resolution — proving its "one interface, one registry line" extensibility claim against a genuinely differently-shaped second scenario, not just a hypothetical.

## Recommendation for Phase 3.6

Confirm with the business which of the following is the actual next priority before building it (this phase, like 3.4 before it, deliberately does not guess):

1. A cross-case/cross-customer Resolution Center reporting view (which categories/resolutions occur most, by employee or by store) — flagged as useful but unbuilt since Phase 3.1's audit.
2. A third scenario from `OPERATIONAL_KNOWLEDGE_FRAMEWORK.md`'s named-but-unbuilt list (Damage Settlement, Bad Debt Negotiation, Collections, Payment Plans, Pricing Adjustment, Customer Goodwill, Warranty Adjustment), or Equipment Exchange as a real (not just manual-outcome) scenario.
3. Resolving the `credit_category` (Financial vs. Promotional) schema question that has blocked Promotional Credit since Phase 3.0, if Promotional Credit is becoming a near-term priority.

Whichever is chosen, this phase's experience confirms the framework itself needs no further generalization to support it — `ResolutionCenterService` and the Blade view now key everything off `$case->scenario_key` and the scenario's own declared actions, not scenario-specific code.
