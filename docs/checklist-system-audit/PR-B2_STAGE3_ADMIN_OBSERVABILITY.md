# PR-B2 Stage 3 — Admin-Web Observability (D2: Observability-First)

**Date:** 2026-07-11
**D2 decision:** Observability-first. The admin-web Rental Ready workflow keeps its current client-trusted behavior; the server now also computes the true result and logs a warning when it disagrees, so real-world disagreement frequency can be reviewed before any enforcement decision.
**Depends on:** `PR-B2_STAGE2_CALCULATOR_EXTRACTION.md` (the calculator this stage reuses, unmodified), `PR-B2_READINESS_REVIEW.md`, `PR-B2_CALCULATOR_DESIGN.md` §6.
**Scope:** wire `RentalReadyCompletionCalculator` into `EquipmentManagement\StoreController` for comparison-and-logging only. No enforcement, no persisted-value change, no `EquipmentStatusService` routing, no Blade/JS change, no `insepectorSlect` fix, no PR-B4.

---

## 1. Files changed

| File | Change |
|---|---|
| `app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/StoreController.php` | One `use` import added. One call added inside the existing `Equipment::find(...)` success branch: `$this->logCompletionMismatchIfAny(...)`. Two new **private** methods added at the end of the class: `logCompletionMismatchIfAny()` and `resolveNormalizedQuestionsFromPayload()`. Nothing else in the method body changed — the same `$templateStatus`/`$counts` values are still what gets persisted to `EquipmentRentalReadyTemplate` and the same raw `$status` still drives the direct `Equipment::update()` call. |
| `tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php` *(new)* | 4 tests — see §4. |

**Not touched, confirmed:** `SaveController.php` (mobile path, Stage 2's extraction), `RentalReadyCompletionCalculator.php`/`RentalReadyCompletionResult.php` (reused as-is, zero changes), `EquipmentStatusService.php`, any Blade/JS file, `insepectorSlect`, anything under PR-B4's scope.

---

## 2. What was built

### `resolveNormalizedQuestionsFromPayload(array $qaPayload): array`
Rebuilds the calculator's input **from the database, not the client JSON's own business-fact fields** — per the explicit requirement that `required_question`/answer `type` must never be trusted from the submitted payload:

- Resolves each submitted question against `RentalReadyChecklistQuestion` (by `main_id` numeric id first, then `unique_id`/`id` fallback — the same two-step lookup `StoreController.php`'s own persistence loop already uses, so no new resolution strategy was invented).
- Resolves the selected answer against `RentalReadyChecklistQuestionAnswer` (by numeric id or `unique_id`/`id` fallback) the same way.
- Emits `{'required_question': (bool) $questionModel->required_question, 'selected_answer': $answerModel ? ['type' => $answerModel->type] : null}` — `required_question` and `selected_answer.type` come **only** from the resolved Eloquent models, never from `data_get($q, 'required')` or `data_get($q, 'status')`/`data_get($q, 'selected_answer.type')` in the payload.
- A question that can't be resolved against the DB is skipped (mirrors the existing persistence loops' `continue` behavior) rather than guessed at.

### `logCompletionMismatchIfAny(...)`
1. Calls `resolveNormalizedQuestionsFromPayload()`, then `RentalReadyCompletionCalculator::calculate()` — the exact same calculator Stage 2 wired into the mobile path, reused with zero modification.
2. Compares `$result->status` against `$submittedTemplateStatus` (the already-computed `$templateStatus` local variable — same vocabulary: `'Rental Ready'`/`'Damaged'`/`'Draft'`, so this is a direct string comparison, not a remapping).
3. Compares each of the 6 `$result->counts` keys against the corresponding submitted `$counts` key, collecting the mismatched key names.
4. If **either** the status disagrees **or** any count key disagrees, logs one structured warning to the `api_errors` channel (same channel PR-A4/PR-B3 already use for this kind of disagreement/observability logging) containing:
   - `equipment_id`, `equipment_unique_id`
   - `submitted_equipment_status` (the raw `available`/`damaged`/`maintenance` field) and `submitted_template_status` (the mapped label, for direct comparison against `computed_status`)
   - `computed_status`
   - `submitted_counts` (all 6 keys) and `computed_counts` (all 6 keys)
   - `mismatched_count_fields` (array of just the keys that disagree)
   - `actor_id` (`auth()->id()`, the logged-in staff member submitting the form)
5. If nothing disagrees, returns silently — no log noise on the happy path, matching PR-B3's/PR-A4's existing observability convention.

**Placement:** the call sits immediately after the existing `Equipment::update([...])` call inside the `if ($equipment = Equipment::find(...))` branch — reusing the already-resolved `$equipment` instance rather than issuing a second `Equipment::find()` query.

**What deliberately did NOT change:** `$templateStatus` and `$counts` are still exactly what gets written to `EquipmentRentalReadyTemplate` (both the "update existing" and "create new" branches, untouched). The direct `Equipment::update(['current_status' => match($status) {...}])` call is still exactly as it was — **not** rerouted through `EquipmentStatusService`, per instruction §6: doing so now would introduce a real audit/behavior side effect (an `EquipmentStatusLog` row keyed on `EquipmentStatusService`'s own transition semantics) before D2's enforcement decision is actually made, which would conflate "start observing" with "start behaving differently." That step is explicitly deferred to the enforcement stage.

---

## 3. A pre-existing behavior surfaced during testing (not a Stage 3 change)

The first test-writing pass assumed `StoreController.php` produces **zero** `equipment_status_logs` rows today, since it doesn't call `EquipmentStatusService`. That assumption was wrong and the test failed — not because of a Stage 3 defect, but because `EquipmentObserver` (a model observer, unrelated to `EquipmentStatusService`) already logs any `current_status` change on a normal, non-quiet `Equipment::update()` call, which is exactly what `StoreController.php` has always used, before and after this stage. The test was corrected to assert the **pre-existing** single observer-driven row, and to confirm Stage 3 doesn't cause a *second* row (which would happen if it were mistakenly wired through `EquipmentStatusService` too). No production code changed as a result of this — it was a test-authoring correction, caught and fixed before reporting results.

---

## 4. Tests added (`StoreControllerObservabilityTest.php`)

1. `test_logs_mismatch_when_submitted_status_disagrees_with_computed_status` — real answer is Damaged, client submits `equipment_status=available`; asserts the warning fires with the exact `equipment_id`/`equipment_unique_id`/`submitted_equipment_status`/`submitted_template_status`/`computed_status`/`actor_id` fields, **and** that the persisted template/equipment status still reflect the submitted (wrong) values.
2. `test_logs_mismatch_when_submitted_counts_disagree_with_computed_counts` — status label agrees, but the submitted counts blob is wrong; asserts the warning fires, `mismatched_count_fields` names exactly the disagreeing keys, `submitted_counts`/`computed_counts` are both logged in full, and the persisted template still has the wrong (submitted) counts.
3. `test_does_not_log_when_submitted_status_and_counts_match_computed_values` — a fully honest submission; asserts no warning at all.
4. `test_calculator_result_does_not_enforce_or_overwrite_persisted_values` — a disagreeing submission where the client's status label happens to also be "wrong" in the same direction; asserts the persisted `EquipmentRentalReadyTemplate`/`Equipment.current_status` are still exactly the submitted values (not the calculator's), and that no second `equipment_status_logs` row was introduced by this stage's change (see §3).

All 4 also assert `$response->assertRedirect(...)` to the unchanged `equipment-management.show` route, confirming the redirect/response behavior is untouched.

---

## 5. Commands run

```bash
php -l app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/StoreController.php
php -l tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php

php artisan test --env=testing tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php

php artisan test --env=testing \
  tests/Unit/Services/ChecklistManagement/RentalReadyCompletionCalculatorTest.php \
  tests/Feature/RentalReadyChecklists/SaveControllerCharacterizationTest.php \
  tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
```

---

## 6. Pass/fail counts

```
StoreControllerObservabilityTest (new)                  → 4 passed (36 assertions)
RentalReadyCompletionCalculatorTest (Stage 2, unchanged) → 8 passed (unit)
SaveControllerCharacterizationTest (Stage 1, unchanged)  → 4 passed (64 assertions)
ValidationGuardObservabilityTest (PR-B3, unchanged)      → 6 passed (19 assertions)
```

**Total: 22/22 passing, 0 failures.** None of the pre-existing suites (Stage 1, Stage 2, PR-B3) had their assertions modified — all re-run verbatim against the post-Stage-3 codebase.

---

## 7. What's explicitly still pending (not this stage)

- **Enforcement** — actually switching `StoreController.php` to persist `$result`'s status/counts instead of the client-submitted ones, and routing the status write through `EquipmentStatusService`. Deferred until the observability window (this stage) produces enough real disagreement-frequency data to make that call.
- **`insepectorSlect` typo fix** — still not applied; bundled with the enforcement stage per the original design doc's sequencing.
- **Client-side JS (`index.blade.php`)** — untouched; its drifted required-vs-all-questions gating rule is unchanged.
- **PR-B4** — not started.

---

## Confirmation

Files created: `tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php`, this document.
Files modified: `app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/StoreController.php` (one import, one call-site addition, two new private methods — no existing line's behavior changed).
Files NOT touched: `SaveController.php`, `RentalReadyCompletionCalculator.php`, `RentalReadyCompletionResult.php`, `EquipmentStatusService.php`, any Blade/JS file, `insepectorSlect`, anything under PR-B4.

**Stopping after Stage 3 as instructed.** Not proceeding to Stage 4 (enforcement) without further direction.
