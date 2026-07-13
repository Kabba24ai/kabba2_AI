# PR-B2 Stage 2 — `RentalReadyCompletionCalculator` Extraction (Mobile Path Only)

**Date:** 2026-07-11
**Depends on:** `PR-B2_READINESS_REVIEW.md`, `PR-B2_CALCULATOR_DESIGN.md`, `PR-B2_STAGE1` characterization suite (`tests/Feature/RentalReadyChecklists/SaveControllerCharacterizationTest.php`).
**Scope:** extract the completion/status algorithm out of `SaveController.php` into a standalone, stateless service, and wire only the mobile Rental Ready path to it. **Admin-web `StoreController.php` is untouched** — it still trusts client-submitted `equipment_status`/`counts.*` exactly as before this stage. D2 (admin trust-model decision), the `insepectorSlect` typo fix, JS/Blade changes, and PR-B4 are all explicitly out of scope for this stage.

---

## 1. Files changed

| File | Change |
|---|---|
| `app/Services/ChecklistManagement/RentalReadyCompletionCalculator.php` *(new)* | `calculate(array $questions): RentalReadyCompletionResult` — the extracted algorithm, copied verbatim from `SaveController.php`'s former inline logic. |
| `app/Services/ChecklistManagement/RentalReadyCompletionResult.php` *(new)* | Immutable result object: `counts`, `hasDamaged`, `hasMaintenance`, `allRentalReady`, `status`, `anyAnswerMissing`. |
| `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php` | One `use` import added; the inline `$counts`/`$anyAnswerMissing`/`$hasDamaged`/`$hasMaintenance`/`$allRentalReady`/`$status` block (formerly lines 136-201) replaced with a single call to the calculator, then destructured into the exact same local variable names. Everything downstream (persistence, `$logArray`, the PR-B3 warning log, the `EquipmentStatusService` dispatch, the JSON response) is byte-for-byte unchanged. |
| `tests/Unit/Services/ChecklistManagement/RentalReadyCompletionCalculatorTest.php` *(new)* | 8 focused unit tests — see §3. |

**Not touched, confirmed:** `StoreController.php`, `EquipmentStatusService.php`, any JavaScript/Blade file, `insepectorSlect`, anything under PR-B4's scope.

---

## 2. What moved and what didn't

**Moved (verbatim, no logic invented):**
- All 6 count formulas (`total_questions` through `damaged_items`).
- `$anyAnswerMissing` computation.
- `$hasDamaged` / `$hasMaintenance` / `$allRentalReady` flags.
- The `Damaged` → `Rental Ready` → `Draft` status precedence `if/elseif/else`.

**Did not move (still lives in `SaveController.php`, unchanged):**
- Building `$newQuestions` from the `checklist[]` request payload (lines 89-134) — this is request-hydration/model-resolution work, not completion math, and it already produces the calculator's exact input shape as a superset.
- `$logArray` construction, the PR-B3 warning `Log::channel('api_errors')->warning(...)` call itself (only its trigger condition now reads `$result->anyAnswerMissing` instead of a local variable of the same name).
- The `EquipmentRentalReadyTemplate`/`EquipmentRentalReadyChecklistQuestion`/`EquipmentRentalReadyChecklistQuestionLog` persistence block.
- The `EquipmentStatusService::markDamagedFromRentalReady()` / `markAvailableFromRentalReady()` / `markMaintenanceFromRentalReady()` dispatch.
- The JSON response.

This is why the readiness review called this path "behavior-preserving by construction" — the calculator call is a drop-in replacement for 5 local variables, nothing else in the method changed.

---

## 3. Calculator design confirmation

Matches `PR-B2_CALCULATOR_DESIGN.md` exactly:
- **One public method**, `calculate(array $questions): RentalReadyCompletionResult`.
- **Stateless**: no constructor dependencies, no `Log::`, no `auth()`/`request()` calls anywhere in the class. Confirmed by the unit tests running under plain `PHPUnit\Framework\TestCase` (no Laravel bootstrap at all — see `phpunit.xml`'s `bootstrap="vendor/autoload.php"` and the existing convention in `tests/Unit/Services/TaxCalculationServiceTest.php`), which would be impossible if the calculator touched the DB, the container, or any framework service.
- **Input**: `array<int, array{required_question: bool, selected_answer: array{type: string}|null}>` — the minimal slice both controllers can eventually produce.
- **Output**: `RentalReadyCompletionResult` with the 6 named properties, matching §3 of the design doc.

### Calculator unit tests (`RentalReadyCompletionCalculatorTest.php`)

1. `test_all_required_and_optional_rental_ready_is_complete_and_ready` — happy path, all 6 counts asserted exactly.
2. `test_damaged_takes_precedence_over_ready` — one damaged required question wins over the ready flag.
3. `test_damaged_takes_precedence_even_when_all_required_are_also_rental_ready` — precedence is an unconditional `if/elseif`, not a computed mutual-exclusivity; guards against a future refactor inverting it.
4. `test_unanswered_optional_question_does_not_block_ready_status` — the required-questions-only completion rule, isolated from any controller/DB noise.
5. `test_unanswered_required_question_falls_back_to_draft` — Draft fallback + observability flag.
6. `test_maintenance_hold_answer_is_counted_and_falls_back_to_draft` — `hasMaintenance` flag and its count.
7. `test_empty_question_list_is_rental_ready_by_vacuous_truth` — documents the exact (pre-existing, not newly introduced) edge-case behavior of `every()` over an empty collection, so it isn't mistaken for a regression later.
8. `test_no_required_questions_with_all_optional_answered_is_rental_ready` — same vacuous-truth edge case, with a non-empty question list that has zero required questions.

---

## 4. Commands run

```bash
php -l app/Services/ChecklistManagement/RentalReadyCompletionCalculator.php
php -l app/Services/ChecklistManagement/RentalReadyCompletionResult.php
php -l app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php
php -l tests/Unit/Services/ChecklistManagement/RentalReadyCompletionCalculatorTest.php

php artisan test --env=testing tests/Unit/Services/ChecklistManagement/RentalReadyCompletionCalculatorTest.php

php artisan test --env=testing tests/Feature/RentalReadyChecklists/SaveControllerCharacterizationTest.php

php artisan test --env=testing tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
```

---

## 5. Pass/fail counts

```
RentalReadyCompletionCalculatorTest (new, unit)        → 8 passed (37 assertions)
SaveControllerCharacterizationTest (Stage 1, unchanged) → 4 passed (64 assertions)
ValidationGuardObservabilityTest (PR-B3 regression)     → 6 passed (19 assertions)
```

**Total: 18/18 passing, 0 failures.**

---

## 6. Confirmation that Stage 1 tests passed unchanged

`tests/Feature/RentalReadyChecklists/SaveControllerCharacterizationTest.php` was **not modified** in this stage — same file, same assertions, same 4 test methods, re-run against the post-extraction `SaveController.php`. Result: **4 passed, 64 assertions — identical to the Stage 1 baseline run** (also 4 passed, 64 assertions). This is the concrete proof that wiring the calculator into `SaveController.php` did not change any observable behavior: same status/counts/`is_complete` values, same `equipment.current_status` transitions, same `EquipmentStatusLog` rows, same consolidated question-log row, same API response shape, same PR-B3 warning-logging conditions.

---

## 7. What's explicitly still pending (not this stage)

- **D2** — the admin-web trust-model decision — still unresolved; `StoreController.php` still reads `equipment_status`/`counts.*` directly from the client, unchanged.
- **`StoreController.php`** itself — not touched at all in Stage 2; still bypasses `EquipmentStatusService`.
- **`insepectorSlect` typo fix** — not applied yet (bundled with the admin-web stage per the design doc's sequencing).
- **Client-side JS (`index.blade.php`)** — untouched; its drifted required-vs-all-questions rule is unchanged.
- **PR-B4** — not started.

---

## Confirmation

Files created: `app/Services/ChecklistManagement/RentalReadyCompletionCalculator.php`, `app/Services/ChecklistManagement/RentalReadyCompletionResult.php`, `tests/Unit/Services/ChecklistManagement/RentalReadyCompletionCalculatorTest.php`, this document.
Files modified: `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php` (import + one block replaced, 5 local variables re-sourced from the calculator's result — no other line changed).
Files NOT touched: `StoreController.php`, `EquipmentStatusService.php`, any Blade/JS file, `insepectorSlect`, anything under PR-B4.

**Stopping after Stage 2 as instructed.** Not proceeding to admin `StoreController.php` work, D2 implementation, or PR-B4 without further direction.
