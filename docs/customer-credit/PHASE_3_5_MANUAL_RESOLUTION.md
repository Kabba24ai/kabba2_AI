# Phase 3.5 — Manual Resolution Scenario Foundation: Design

Last updated: 2026-07-06

---

## 1. Pre-Implementation Findings

This phase found the backend of its own mission **already implemented, uncommitted, in the working tree** before any new code was written: `ManualResolutionScenario`, `ManualResolutionAnswerController`, `ManualResolutionAnswerRequest`, its route, and its `ResolutionScenarioRegistry` entry all existed already, matching this phase's mission almost verbatim (the same 10 issue categories, the same 7 employee-selected resolutions, Store Credit routed exclusively through `CustomerCreditService::createFinancialCredit()`). `ResolutionCenterService` had also already been generalized (`recordAnswers()` takes a generic `array $answers`; `approveAndIssueCredit()` gates on the case's own scenario's `service`-typed actions instead of a hardcoded Cancellation/Refund constant) — both changes carried Phase 3.5 docblocks describing exactly this work.

What was **not** done: no Blade UI path existed to reach the scenario (no branch in `show.blade.php`, no scenario picker on the Order Edit "Start Resolution Case" panel — every case defaulted to `cancellation_refund` regardless), one test (`ResolutionScenarioRegistryTest::test_all_returns_every_registered_scenario`) still asserted the pre-Phase-3.5 count of 1, no dedicated `ManualResolutionScenario` test file existed, and neither this document nor a completion report existed. Per this project's own "stop, document, don't silently redesign" rule, this was surfaced to the business owner before proceeding; the confirmed direction was to **finish the existing implementation**, not rebuild it.

This document therefore describes the system as completed by this phase — the parts that already existed, plus the integration work this phase added.

## 2. Architecture

Manual Resolution is the Operational Knowledge Framework's second `ResolutionScenario` (`app/Services/ResolutionCenter/ManualResolutionScenario.php`), registered in `ResolutionScenarioRegistry` alongside `CancellationRefundScenario`. Unlike its sibling, it computes no recommendation: `recommend()` returns `null` until both `issue_category` and `selected_resolution` are present, then echoes the employee's own selection back as a single-option `ResolutionRecommendation`, paired with contextual next-step guidance drawn from a static text map (`NEXT_STEP_TEXT`). No branching logic, no invented policy about which resolution fits which category — exactly the shape the framework's mission mandated ("many customer-resolution scenarios are too diverse to force into rigid automation").

Two questions only, both single-select, the second (`selected_resolution`) marked `dependsOn: 'issue_category'` for UI sequencing (descriptive only, per the framework's existing convention — not evaluated in code):

1. **Issue Category** — Equipment Exchange, Wrong Equipment, Equipment Issue, Weather Delay, Job Delay, Customer Cancellation, Pricing Concern, Damage Dispute, Goodwill Request, Other.
2. **Selected Resolution** — Reschedule, Equipment Exchange Recommended, Store Credit, Refund Review, Manager Follow-up, No Action, Other Manual Resolution.

Because both answers are known the instant the employee submits, this is a **single-step action**, not a multi-request wizard — `ManualResolutionAnswerController`/`PUT .../manual-answer` handles it in one round trip, unlike Cancellation/Refund's sequential three-step `AnswerController` flow.

## 3. Employee-Selected Outcome → Code

| Resolution | Execution Type | What Happens |
|---|---|---|
| Reschedule | Manual | Employee uses the existing reschedule control on Order Edit — no fee, since none exists anywhere in this system. |
| Equipment Exchange Recommended | Manual | Coordinated directly with the store/warehouse — no equipment-exchange mechanism exists in this system yet. |
| Store Credit | **Service** | The only `EXECUTION_SERVICE` action. Executed via the case's existing "Approve & Issue Store Credit" form, which calls `ResolutionCenterService::approveAndIssueCredit()` → `CustomerCreditService::createFinancialCredit()`, referenced to the order. Requires amount, reason, and notes. |
| Refund Review | Manual | Escalated to a manager, then performed on Order Edit's existing Refund action if approved. Never processed automatically. |
| Manager Follow-up | Manual | Escalated outside the system; outcome recorded on the case once resolved. |
| No Action | Manual | Case documented and closed, nothing further happens. |
| Other Manual Resolution | Manual | Whatever was actually done is described in Notes — this option exists precisely because the ten categories and six named resolutions cannot cover every real situation. |

## 4. Execution Boundaries — What This Phase Does and Does Not Execute

Identical boundary to Phase 3.3: this framework can only ever execute one thing on a scenario's behalf — issuing Financial Store Credit, and only behind an explicit, separate "Approve & Issue" action naming a specific amount. Every other resolution — Reschedule, Equipment Exchange, Refund, Manager Follow-up, No Action, Other — is recorded here and performed manually elsewhere (or nowhere, for No Action). Nothing in this phase modifies an order, reallocates inventory, processes a refund, or issues credit automatically. The employee remains in control at every step; the Resolution Center assists by categorizing, documenting, and (for Store Credit only) executing an explicit, separately-confirmed action.

`ResolutionCenterService::approveAndIssueCredit()`'s gate was generalized (not reimplemented per-scenario) to check whichever action(s) the case's own scenario marks `EXECUTION_SERVICE` and intersect that with the case's actual recommendation — so Cancellation/Refund's `issue_store_credit` key and Manual Resolution's `store_credit` key both work through one method, with neither scenario's gate logic duplicated. Verified to produce identical accept/reject behavior for Cancellation/Refund as the previous hardcoded check (Completion Report §Validation), and to correctly reject every non-Store-Credit Manual Resolution outcome (also verified live).

## 5. Data Model

No new table. `resolution_cases.scenario_key` (added ahead of this phase, defaulting to `cancellation_refund`) and `resolution_cases.issue_category` (also added ahead of this phase) are Manual Resolution's only dedicated columns; `selected_resolution` is not stored in its own column — it is written into the same `recommended_resolution`/`recommended_next_step` columns Cancellation/Refund already uses, via `ResolutionCenterService::recordAnswers()`'s existing generic write path. No migration was added by this phase's own work.

## 6. Permissions

Reuses the four existing `resolution_center.*` permissions (`view`/`use`/`override`/`view_audit_history`) plus `customer_credit.grant` for the Store Credit execution path — identical permission map to `CancellationRefundScenario`. No new permission module was needed.

## 7. UI Integration (added by this phase)

- **Order Edit's "Start Resolution Case" panel** (`resources/views/admin/order_management/orders/edit.blade.php`) now includes a scenario picker built from `ResolutionScenarioRegistry::all()`, defaulting to `cancellation_refund` so the existing default behavior for anyone who doesn't touch the new dropdown is unchanged.
- **The case detail screen** (`resources/views/admin/resolution_center/show.blade.php`) now branches on `$case->scenario_key`: Cancellation/Refund's existing three-step wizard renders exactly as before (moved under its own `@if`, not otherwise touched); Manual Resolution renders a single "Categorize & Resolve" form with the two select inputs described above. The shared, scenario-agnostic sections below (Recommendation display, "Approve & Issue Store Credit", Employee Decision) required two small generalizations: the Store Credit gate now recognizes either scenario's key, and resolution-key labels are looked up through a merged map (`ResolutionPolicy::LABELS + ManualResolutionScenario::RESOLUTION_LABELS`) instead of only the former — both additive, both verified to leave Cancellation/Refund's own keys and rendered labels unchanged (the two label sets are disjoint).

## 8. Out of Scope (unchanged from the mission)

Promotional Credit, Gift Cards, AI-generated recommendations, automatic operational actions (inventory, order modification, refunds), Collections, Payment Plans, Bad Debt workflow, Customer Portal. This phase adds one new scenario to an existing framework — it does not touch any of these.
