# PR-B2 — Reconcile Rental Ready Completion Logic (Final)

**Date:** 2026-07-11
**Status:** Stages 1-4 complete. D2 implemented as **observability-first**, per client/product decision. Enforcement deliberately deferred — see §5.
**Supersedes/consolidates:** `PR-B2_READINESS_REVIEW.md`, `PR-B2_CALCULATOR_DESIGN.md`, and the three stage reports (`PR-B2_STAGE2_CALCULATOR_EXTRACTION.md`, `PR-B2_STAGE3_ADMIN_OBSERVABILITY.md`) into one document, mirroring the PR-B1/PR-B3 final-write-up pattern.

---

## 1. Objective (recap)

One shared, server-trusted completion/status calculation for Rental Ready inspections, used by both the mobile API path and the admin-web path, so the two surfaces cannot silently disagree about an equipment's Rental Ready status for the same underlying answers.

## 2. Root cause (recap, confirmed by `PR-B2_READINESS_REVIEW.md`)

Two independent, parallel implementations existed:
- **Mobile path** (`SaveController.php`) — computed counts/status from the actual submitted answers. Server-trusted.
- **Admin-web path** (`StoreController.php`) — trusted client-submitted counts and a raw `equipment_status` field verbatim, with no server-side verification. Client-trusted.

A third, undocumented drifted implementation was also found in `index.blade.php`'s client-side JS (`computeStatusSummary()`), whose "ready" gating scope (all questions) differs from the PHP calculator's rule (required questions only) — flagged for a separate fast-follow, not fixed in PR-B2 (see §7).

---

## 3. What was built, stage by stage

### Stage 1 — Characterization baseline
Locked the mobile path's **existing** behavior before touching any code: `tests/Feature/RentalReadyChecklists/SaveControllerCharacterizationTest.php`, 4 scenarios (all-ready, damaged-precedence, optional-unanswered, required-unanswered), asserting status, `is_complete`, all 6 counts, `equipment.current_status`, `EquipmentStatusLog` rows, the consolidated question-log row, and the exact API response shape. This suite was written and passing against the **unmodified** mobile controller before any extraction began, and has not had its assertions changed since.

### Stage 2 — Calculator extraction, mobile path only
Created:
- `app/Services/ChecklistManagement/RentalReadyCompletionCalculator.php` — `calculate(array $questions): RentalReadyCompletionResult`. Stateless: no DB access, no `Log::` calls, no `auth()`/`request()` dependencies anywhere in the class.
- `app/Services/ChecklistManagement/RentalReadyCompletionResult.php` — immutable result object (`counts`, `hasDamaged`, `hasMaintenance`, `allRentalReady`, `status`, `anyAnswerMissing`).

Wired **only** `SaveController.php` to call it — the inline `$counts`/`$anyAnswerMissing`/`$hasDamaged`/`$hasMaintenance`/`$allRentalReady`/`$status` block was replaced with one calculator call, destructured into the same local variable names. Nothing else in the method changed. The Stage 1 suite was re-run **unmodified** afterward and passed with identical assertions — the concrete proof the extraction was mechanical, not a behavior change.

Added `tests/Unit/Services/ChecklistManagement/RentalReadyCompletionCalculatorTest.php` — 8 focused unit tests on the calculator in isolation (no DB, no Laravel bootstrap — plain `PHPUnit\Framework\TestCase`, matching this codebase's convention for stateless service tests).

### Stage 3 — Admin-web observability (D2: observability-first)
**Client/product decision on D2:** use observability-first behavior for the admin Rental Ready workflow — do not enforce the server-computed result yet; compute it in parallel and log disagreements so real-world frequency can be reviewed before any enforcement decision.

Implemented in `StoreController.php`:
- A new private `resolveNormalizedQuestionsFromPayload()` method builds the calculator's input **from the database** (`RentalReadyChecklistQuestion.required_question`, `RentalReadyChecklistQuestionAnswer.type`) — never from the submitted JSON's own `required`/`status` fields, per the same "don't trust client-supplied business facts" principle D2 is about.
- A new private `logCompletionMismatchIfAny()` method calls the calculator (the exact same class Stage 2 built, unmodified), compares its `status` and all 6 `counts` against what the client submitted, and — only when something disagrees — logs one structured warning to the `api_errors` channel with `equipment_id`, `equipment_unique_id`, `submitted_equipment_status`, `submitted_template_status`, `computed_status`, `submitted_counts`, `computed_counts`, `mismatched_count_fields`, and `actor_id`.
- **Persistence was not changed.** `EquipmentRentalReadyTemplate.status`/counts and `equipment.current_status` are still written from the client-submitted `$templateStatus`/`$counts`/`$status`, exactly as before this PR.
- **`EquipmentStatusService` was deliberately not wired in yet.** Routing the admin-web status write through it now — before D2's enforcement decision — would introduce a real behavior/audit side effect ahead of that decision. Deferred to the enforcement stage.

Added `tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php` (4 tests at the time, now 5 — see Stage 4), proving: the warning fires on a status mismatch, fires on a counts-only mismatch, does not fire on an honest submission, and — critically — that the calculator's output never overwrites what gets persisted.

### Stage 4 — Finalization (this stage)
- Fixed the `insepectorSlect` → `inspectorSelect` typo in `StoreController.php`'s "create new template" branch (line 200), which previously nulled `employee_id` on every first-time admin-web inspection.
- Added `test_first_time_inspection_correctly_saves_employee_id` to `StoreControllerObservabilityTest.php`, exercising the exact "create new template" branch and asserting `employee_id`/`employee_name` are now correctly populated.
- Consolidated all prior stage reports into this final document.

**No enforcement, Blade/JS, or PR-B4 work was done in Stage 4** — strictly the typo fix, its regression test, and documentation consolidation, per this stage's explicit scope.

---

## 4. Files changed (cumulative, all 4 stages)

| File | Stage | Change |
|---|---|---|
| `app/Services/ChecklistManagement/RentalReadyCompletionCalculator.php` | 2 (new) | The shared completion algorithm. Unmodified since Stage 2. |
| `app/Services/ChecklistManagement/RentalReadyCompletionResult.php` | 2 (new) | Immutable result object. Unmodified since Stage 2. |
| `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php` | 2 | Import added; inline calc block replaced with a calculator call. Unmodified since Stage 2. |
| `app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/StoreController.php` | 3, 4 | Stage 3: import + comparison/logging call + 2 new private methods (persistence untouched). Stage 4: one-line typo fix (`insepectorSlect` → `inspectorSelect`). |
| `tests/Feature/RentalReadyChecklists/SaveControllerCharacterizationTest.php` | 1 (new) | 4 characterization tests. Unmodified since Stage 1. |
| `tests/Unit/Services/ChecklistManagement/RentalReadyCompletionCalculatorTest.php` | 2 (new) | 8 calculator unit tests. Unmodified since Stage 2. |
| `tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php` | 3 (new), 4 | Stage 3: 4 tests. Stage 4: +1 typo-fix regression test (5 total). |
| `docs/checklist-system-audit/PR-B2_READINESS_REVIEW.md` | pre-1 | Investigation, no code. |
| `docs/checklist-system-audit/PR-B2_CALCULATOR_DESIGN.md` | pre-2 | Design, no code. |
| `docs/checklist-system-audit/PR-B2_STAGE2_CALCULATOR_EXTRACTION.md` | 2 | Stage report. |
| `docs/checklist-system-audit/PR-B2_STAGE3_ADMIN_OBSERVABILITY.md` | 3 | Stage report. |
| `docs/checklist-system-audit/PR-B2_RENTAL_READY_CALCULATOR.md` | 4 (this file) | Final consolidated document. |

**Never touched across all 4 stages:** `EquipmentStatusService.php`, any Blade/JS file, anything under PR-B4's scope.

---

## 5. Explicit documentation of current state (per Stage 4 requirement)

- **Mobile path now uses the shared calculator.** `SaveController.php` computes `counts`/`hasDamaged`/`hasMaintenance`/`allRentalReady`/`status` via `RentalReadyCompletionCalculator::calculate()`, not inline logic. Behavior is unchanged from before PR-B2 (proven by the unmodified Stage 1 suite passing identically before and after the extraction).
- **Admin path computes and logs mismatches only.** `StoreController.php` now also calls the same calculator, built from DB-resolved `required_question`/answer `type` values (never trusted from the client JSON), and logs a structured `api_errors` warning whenever the computed result disagrees with what the client submitted. It does not act on that disagreement.
- **Current admin-submitted values are still persisted.** `EquipmentRentalReadyTemplate.status`, all 6 count fields, and `equipment.current_status` are still written exactly as the client submitted them — the calculator's output is observational only, confirmed by `test_calculator_result_does_not_enforce_or_overwrite_persisted_values`.
- **D2 enforcement remains deferred pending telemetry/client approval.** No code change in this PR switches the admin-web path to trust the server-computed result. That is a separate, future stage, gated on reviewing the `api_errors` warning frequency this stage now produces, and on an explicit go-ahead — mirroring the same staged pattern used for PR-A4 and PR-B3.
- **The client-side JS drift remains a separate fast-follow.** `index.blade.php`'s `computeStatusSummary()` still gates its "Mark Ready" button on *all* questions being answered, not just required ones — a real drift from the calculator's required-only rule, identified in the readiness review. Not touched by PR-B2; recommended as its own small follow-up whenever the UI is next revisited.
- **`EquipmentObserver` already creates the admin status log.** `StoreController.php`'s plain (non-quiet) `Equipment::update()` call was already triggering `EquipmentObserver`, which writes an `equipment_status_logs` row on any `current_status` change — this is pre-existing behavior, not introduced by PR-B2, and is independent of `EquipmentStatusService` (which the admin path still does not call). This was surfaced and confirmed while writing Stage 3's tests (an initial test assumption of "zero status-log rows" was wrong and corrected before reporting results — see `PR-B2_STAGE3_ADMIN_OBSERVABILITY.md` §3).

---

## 6. Commands run (Stage 4, full consolidated regression pass)

```bash
php -l app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/StoreController.php
php -l tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php

php artisan test --env=testing \
  tests/Unit/Services/ChecklistManagement/RentalReadyCompletionCalculatorTest.php \
  tests/Feature/RentalReadyChecklists/SaveControllerCharacterizationTest.php \
  tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php \
  tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php \
  tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php \
  tests/Unit/Equipment/EquipmentStatusServiceLogTest.php
```

## 7. Pass/fail counts

```
RentalReadyCompletionCalculatorTest (PR-B2 Stage 2)     →  8 passed
SaveControllerCharacterizationTest (PR-B2 Stage 1)      →  4 passed (64 assertions)
StoreControllerObservabilityTest (PR-B2 Stage 3+4)      →  5 passed (40 assertions)
ValidationGuardObservabilityTest (PR-B3)                →  6 passed (19 assertions)
ChecklistAssignmentServiceTest (PR-B1)                  →  7 passed
EquipmentStatusServiceLogTest (EquipmentStatusService)  → 10 passed

Tests:    42 passed (198 assertions)
Duration: 86.38s
```

**Total: 42/42 passing, 0 failures** — spanning all of PR-B2 (Stages 1-4), PR-B3, PR-B1, and the underlying `EquipmentStatusService` suite. No regressions anywhere in the checklist-system domain.

---

## 8. Final PR-B2 readiness verdict

## ✅ Complete for this scope — observability-first implementation shipped, enforcement explicitly deferred

PR-B2 as scoped (extract the shared calculator, wire the mobile path unchanged, wire the admin-web path for observability per D2, fix the co-located typo) is **done and green**. This is not "PR-B2 fully closed forever" — it is "PR-B2's approved scope (observability-first) is complete"; a future, separately-scoped enforcement stage remains open by design, gated on:

1. **Telemetry review** — enough real production time observing the `api_errors` "Admin Rental Ready submission disagrees with server-computed completion result" warning to know how often, and in what direction, admin-web submissions actually disagree with computed reality.
2. **Client/product approval** to move from observe to enforce (and to decide whether that's a straight cutover or its own further staged rollout).
3. **A decision on the client-side JS drift** (`index.blade.php`) — fix it as part of the eventual enforcement stage's UI work, or file it separately; not blocking today's observability-only state.

**No blocking issues found for the work actually shipped in this PR.** All four stages' test suites pass together with zero regressions against the closest-related existing suites (PR-B1, PR-B3, `EquipmentStatusService`).

---

## 9. Explicitly not done in PR-B2 (any stage)

- Admin-web persistence is not server-computed — still client-submitted, by design (D2: observability-first).
- Admin-web status writes do not go through `EquipmentStatusService` — deferred to the enforcement stage to avoid introducing an audit/behavior side effect ahead of D2's enforcement decision.
- No Blade/JavaScript changes — the JS drift is a documented, separate fast-follow.
- No PR-B4 work (duplicated CRUD logic reduction) — next in the Phase 2 sequence, not started.

---

## Confirmation

Stage 4 files changed: `app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/StoreController.php` (one-line typo fix), `tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php` (one new regression test), this document (new).
No enforcement logic, Blade/JS file, or PR-B4 work was introduced in Stage 4.

**Stopping after Stage 4 as instructed.** PR-B2 (observability-first scope) is complete. Awaiting direction before PR-B4 or a future D2-enforcement stage.
