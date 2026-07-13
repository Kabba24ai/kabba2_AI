# PR-B2 — `RentalReadyCompletionCalculator` Design

**Date:** 2026-07-10
**Type:** Design only. **No production code was written or modified.** Method signatures below are design pseudocode for review, not an implementation.
**Depends on:** `PR-B2_READINESS_REVIEW.md` (current-state investigation this design builds on), `PHASE2_IMPLEMENTATION_PLAN.md` (§PR-B2), `PHASE2_DECISION_MATRIX.md` (D2 — still unresolved; this design does not require D2 to be answered, but **implementation** does, per the readiness review's verdict).

---

## 1. Public methods

```
namespace App\Services\ChecklistManagement;

class RentalReadyCompletionCalculator
{
    public function calculate(array $questions): RentalReadyCompletionResult;
}
```

**Design decisions and why:**

- **One public method, not several.** Both current implementations (`SaveController.php` lines 136-201, and what `StoreController.php` *should* be doing but isn't) run the exact same sequence — counts, then damaged/maintenance/ready flags, then status precedence — over the same normalized question list. There's no natural seam to split this into two calls without callers having to re-assemble intermediate state themselves. A single `calculate()` mirrors `EquipmentStatusService`'s existing style: static-feeling, stateless, one call in, one result out.
- **Stateless, no constructor dependencies.** No DB access, no `Log::` calls, no `auth()` calls inside the calculator itself — every current implementation's logging (`$anyAnswerMissing` warning, PR-B3's D3 observability) and status-transition side effects (`EquipmentStatusService::markXFromRentalReady()`) stay in the controllers, which already know their own request/actor context. This keeps the calculator trivially unit-testable with plain arrays and no framework bootstrapping, and keeps it honestly a pure function — computing the same output for the same input regardless of when or by whom it's called, matching what both controllers actually need it to be.
- **Takes the *normalized* question list, not either controller's raw payload.** Mobile's `checklist[]` and admin-web's `rental_ready_all_qa_json.questions[]` are shaped differently (see §4/§5) — pushing that translation into the calculator would force it to understand two unrelated wire formats and grow branchy `if (isset($payload['checklist']))`-style dispatch. Each controller already builds (or, for admin-web, must be changed to build) a per-question array in the course of its own request-object hydration; the calculator's contract is that shared intermediate shape, not either controller's original request body.
- **Not a Data Transfer Object class for the input** — a plain `array<int, array>` is deliberately used instead of a typed `RentalReadyQuestion` value object, to match the existing codebase's convention (both current controllers work with plain arrays/collections throughout, no DTO classes exist anywhere in this domain today per the readiness review's file inventory). Introducing a new DTO class here would be scope creep relative to "extract the existing logic," not something PR-B2 needs.
- **Output IS a small typed result object** (`RentalReadyCompletionResult`), not a bare array, specifically because both controllers currently destructure the *same* five values (`$counts`, `$hasDamaged`, `$hasMaintenance`, `$allRentalReady`, `$status`) from separately-computed local variables — naming them explicitly on one return object removes the risk of a caller accidentally reading `$result['coutns']` or similar, and makes the precedence rule (`status` is derived, not independently settable) visible as a constructor-computed property rather than a fourth thing every caller must separately re-derive. This is the one place this design intentionally goes slightly beyond "mechanical extraction," because the current bug class (payload-shape mismatches, admin-web trusting field names verbatim) is exactly a "stringly-typed array" problem.

---

## 2. Input shape

```
$questions: array<int, array{
    required_question: bool,
    selected_answer: array{ type: string, ... }|null,
}>
```

Only two fields the calculator actually reads:
- `required_question` (bool) — drives `required_questions`/`optional_questions` counts and scopes `allRentalReady`.
- `selected_answer.type` (string|null: `'Rental Ready'`, `'Maint. Hold'`, `'Damaged'`, or absent/null for unanswered) — drives every other count and both boolean flags.

Every other field either controller's `$newQuestions`/`questions[]` arrays currently carry (`id`, `unique_id`, `question_name`, `category_id`, `note`, `answers[]`) is **not read by the calculator** — it's persistence detail the controllers still own directly (writing `EquipmentRentalReadyChecklistQuestion` rows, building the `rental_ready_qa_json` log blob). The calculator's input is intentionally the minimal slice both controllers can produce without lossy translation.

---

## 3. Output structure

```
class RentalReadyCompletionResult
{
    public readonly array $counts;        // {total_questions, required_questions, optional_questions,
                                           //  required_items_completed, items_requiring_maintenance, damaged_items}
    public readonly bool  $hasDamaged;
    public readonly bool  $hasMaintenance;
    public readonly bool  $allRentalReady;
    public readonly string $status;       // 'Damaged' | 'Rental Ready' | 'Draft'
    public readonly bool  $anyAnswerMissing; // PR-B3 (D3) observability flag — see §6
}
```

`$counts` keys and formulas — lifted verbatim from `SaveController.php` lines 136-153 (the confirmed-correct source implementation per the readiness review):

| Key | Formula |
|---|---|
| `total_questions` | `count($questions)` |
| `required_questions` | `count` where `required_question === true` |
| `optional_questions` | `count` where `required_question === false` |
| `required_items_completed` | `count` where `required_question === true` AND `selected_answer` not null AND `selected_answer.type === 'Rental Ready'` |
| `items_requiring_maintenance` | `count` where `selected_answer.type === 'Maint. Hold'` |
| `damaged_items` | `count` where `selected_answer.type === 'Damaged'` |

`$status` precedence — lifted verbatim from `SaveController.php` lines 195-201 (confirmed in the readiness review as the one non-negotiable business rule): `$hasDamaged` → `'Damaged'`, else `$allRentalReady` → `'Rental Ready'`, else `'Draft'`. This ordering must not change; it's the one rule every characterization test in §8 anchors on.

`$anyAnswerMissing` is included in the result (not left as a controller-local computation) so `StoreController.php` — which today has no equivalent check at all per the readiness review's §5 — gets it for free, consistent with PR-B3's existing observability-only pattern in `SaveController.php`, rather than needing its own separate reimplementation.

---

## 4. Mapping from the Mobile payload (`SaveController.php`)

Mobile's `checklist[]` request body (`{question_unique_id, answer_unique_id, note}` per item, per `SaveRequest.php`) is already transformed by the controller's existing lines 89-134 into `$newQuestions` — an array of `{id, unique_id, question_name, category_id, required_question, note, answers[], selected_answer}`. This transformation **does not move into the calculator** — it stays exactly where it is, because it's doing real work unrelated to completion math (resolving which question/answer objects the payload refers to, building the `answers[]` array for the persisted `rental_ready_qa_json` log). It's already the calculator's input shape as a strict superset — the calculator simply reads `required_question` and `selected_answer.type` off the same array the controller already built and continues to need for persistence.

**Concretely:** after `SaveController.php` finishes building `$newQuestions` (unchanged), replace lines 136-201 (`$counts`, `$anyAnswerMissing`, `$hasDamaged`, `$hasMaintenance`, `$allRentalReady`, `$status` derivation) with:

```
$result = app(RentalReadyCompletionCalculator::class)->calculate($newQuestions);
```

Everything downstream (`$template->total_questions = $result->counts['total_questions']`, the `EquipmentStatusService::markXFromRentalReady()` dispatch keyed on `$result->hasDamaged`/`$result->allRentalReady`, the PR-B3 warning log keyed on `$result->anyAnswerMissing`) reads from `$result` instead of the local variables it reads from today. No change to `$newQuestions`'s construction, no change to what gets persisted to `EquipmentRentalReadyChecklistQuestion`/`EquipmentRentalReadyChecklistQuestionLog` — this is why the readiness review calls this path "behavior-preserving by construction."

---

## 5. Mapping from the Admin-web payload (`StoreController.php`)

This is where real translation work is needed, because today's admin-web payload does not carry a reliable `selected_answer.type` per question — the readiness review confirmed `StoreController.php` currently reads `equipment_status` and `counts.*` directly rather than deriving them, so there is no existing "build `$newQuestions`-equivalent from the admin payload" code to reuse. This must be written as part of PR-B2, not assumed to already exist.

**Source data available in `$qaPayload['questions'][]`** (per `StoreController.php` lines 109-166's existing per-question loop, which already resolves each question against `RentalReadyChecklistQuestion`/`RentalReadyChecklistQuestionAnswer` models for persistence purposes):
- `data_get($q, 'id')` — the question's unique_id or numeric id (resolved to `$questionModel` already).
- `data_get($q, 'required')` or `data_get($q, 'required_question')` — **must be confirmed against the actual client payload during implementation**; the readiness review did not find this key referenced anywhere in `StoreController.php`'s current code (it only reads `selected_answer.id`, never a required flag), meaning **the admin-web payload's `required` bit must be resolved from `$questionModel->required_question` (the authoritative DB column already loaded via `$questionModel`), not trusted from the request** — this is a second, smaller instance of the same "don't trust client-supplied business facts" principle D2 is about, and should not be quietly skipped.
- `data_get($q, 'status')` or `data_get($q, 'selected_answer.type')` — the answer type string, but per the readiness review this **must be re-resolved from `$answerModel->type`** (the model already looked up at line 138/140/250/252 for `$selectedAnswerId`) rather than trusted from the JSON blob's `status`/`selected_answer.type` field, for the same reason.

**Concretely, the new mapping step** (added to `StoreController.php`, replacing its trust of `$qaPayload['counts']`):

```
$normalizedQuestions = collect(data_get($qaPayload, 'questions', []))
    ->map(function ($q) use (/* resolved $questionModel, $answerModel per existing loop */) {
        return [
            'required_question' => (bool) $questionModel->required_question,   // from DB, not payload
            'selected_answer'    => $answerModel
                ? ['type' => $answerModel->type]                                // from DB, not payload
                : null,
        ];
    })
    ->all();

$result = app(RentalReadyCompletionCalculator::class)->calculate($normalizedQuestions);
```

This reuses the exact `$questionModel`/`$answerModel` resolution `StoreController.php` already performs in its existing per-question loop (lines 118-150 and 229-259) — no new model-lookup logic is introduced, only a shift in *which* already-resolved values feed the calculator versus which get discarded. The raw `equipment_status` field and `$qaPayload['counts']` are no longer read for computing status/counts at all (they may still be logged for the disagreement-observability step below, per D2's staged-rollout option).

---

## 6. Interaction with `EquipmentStatusService`

The calculator **never calls** `EquipmentStatusService` — it has no knowledge of `Equipment`, actors, or persistence, consistent with §1's stateless-pure-function design. Both controllers, after calling `calculate()`, dispatch exactly as `SaveController.php` already does today (lines 288-294):

```
if ($result->hasDamaged) {
    EquipmentStatusService::markDamagedFromRentalReady($equipment, $actorId);
} elseif ($result->allRentalReady) {
    EquipmentStatusService::markAvailableFromRentalReady($equipment, $actorId);
} else {
    EquipmentStatusService::markMaintenanceFromRentalReady($equipment, $actorId);
}
```

For `StoreController.php`, this **replaces** its current direct `Equipment::update(['current_status' => match($status) {...}])` block (lines 291-306) — per the readiness review's §4/§7, this is in scope for PR-B2 specifically because it closes the `EquipmentStatusLog` audit-trail gap as a side effect of fixing the trust-model issue, not as separate unrelated cleanup. `$actorId` for the admin-web path is `auth()->id()` (the logged-in staff member), matching the `updated_by`/`created_by` values `StoreController.php` already sets elsewhere in the same method.

**D2's staged-rollout option, if approved**, sits entirely in the controller, not the calculator: before switching to `$result`-driven behavior, `StoreController.php` would compute `$result` and **compare** it against the raw `equipment_status`/`counts` the client sent, logging a warning (mirroring PR-B3's/PR-A4's existing observability pattern) when they disagree, while still persisting the client-trusted values for the observation window. This comparison-and-log step is controller-level orchestration, not a calculator concern — the calculator's contract stays the same either way.

---

## 7. Sequence of extraction

1. **Do not start until D2 is resolved** (per `PR-B2_READINESS_REVIEW.md`'s verdict — this design can be reviewed and agreed now, but the first commit should wait).
2. **Write characterization tests against `SaveController.php`'s current behavior** (§8, tests 1-4) — committed first, against **today's code**, before any extraction. These must pass unmodified before and after step 3; if any of them fails before touching the calculator, that means the test was written wrong, not that the code is broken.
3. **Create `RentalReadyCompletionCalculator` + `RentalReadyCompletionResult`**, with the formulas in §3 copied verbatim from `SaveController.php` lines 136-201. No behavior invented — a pure lift.
4. **Wire `SaveController.php`** to call the calculator per §4. Re-run the characterization tests from step 2 — they must pass with zero changes to their assertions. This is the checkpoint that proves the extraction was mechanical.
5. **Write the new admin-web mapping step** in `StoreController.php` per §5 (the `$normalizedQuestions` translation), resolving `required_question`/`selected_answer.type` from the already-loaded `$questionModel`/`$answerModel`, not from the request JSON.
6. **Wire `StoreController.php`** to call the calculator and use `$result`, in whichever mode D2 approved (straight cutover vs. observe-then-enforce per §6). Route the status write through `EquipmentStatusService` per §6.
7. **Write the admin-web behavior-change tests** (§8, tests 5-7) against the new code — these are expected to newly pass (they'd fail against pre-PR-B2 code, which is the point).
8. **Apply the `insepectorSlect` typo fix** (`StoreController.php` line 199) in the same PR, per the existing implementation plan — mechanically unrelated to the calculator, bundled only because it's the same file already under change.
9. **Update `PROJECT_MILESTONE_TRACKER.md`** and write `PR-B2_RENTAL_READY_CALCULATOR.md` documenting the shipped PR, following the PR-B1/PR-B3 documentation pattern.

---

## 8. Characterization tests required

**Baseline (write against current code, before any extraction — step 2 above):**
1. Mobile `checklist[]` submission where all required questions answered `'Rental Ready'` and all optional questions also answered → assert stored `EquipmentRentalReadyTemplate.status === 'Rental Ready'`, `is_complete === true`, and `equipment.current_status === 'available'`.
2. Mobile submission with one required question answered `'Damaged'` and the rest `'Rental Ready'` → assert `status === 'Damaged'` (precedence over ready), `equipment.current_status === 'damaged'`.
3. Mobile submission with all required answered `'Rental Ready'` but one **optional** question left unanswered → assert `status === 'Rental Ready'` still (proves optional questions don't block completion — the exact rule that's drifted from the JS in `index.blade.php` per the readiness review's §2, pinned down here before it's touched anywhere else).
4. Mobile submission with a required question unanswered → assert `status === 'Draft'`, and (per PR-B3/D3) a warning is logged but the request still succeeds (`$anyAnswerMissing === true`, no rejection).

**Post-extraction, same assertions re-run unchanged (step 4 above)** — these are the same 4 tests, not new ones; the point is that the test file doesn't change, only the production code path underneath it does.

**New, admin-web behavior-change tests (step 7 above — expected to fail before this PR, pass after):**
5. Admin-web submission with real answers that would compute `'Damaged'`, while the client sends `equipment_status=available` → assert the system does **not** record `'Rental Ready'` (records `'Damaged'` instead) — the existing plan's regression test #2, now made concrete against the calculator's output.
6. Admin-web submission with a required question left unanswered but `equipment_status=available` sent → assert `status !== 'Rental Ready'` (server-computed, ignores the client's claim).
7. Admin-web submission through the new path → assert an `EquipmentStatusLog` row is created (closing the audit-trail gap identified in the readiness review's §7) — this did not happen before this PR and must after.

**Typo-fix regression (bundled per step 8):**
8. First-time inspection via the admin-web "create new template" branch → assert `EquipmentRentalReadyTemplate.employee_id` is correctly populated (fails today against the `insepectorSlect` typo, passes after the one-line fix).

All 8 map directly onto the existing implementation plan's 4 "required regression tests," expanded here to the granularity needed once the calculator's exact contract (§1-§3) is fixed.

---

## Confirmation

**No production code was written or modified.** This document contains design pseudocode only (method/class shapes for review), added solely as `docs/checklist-system-audit/PR-B2_CALCULATOR_DESIGN.md`. No files under `app/`, `tests/`, `resources/`, or `routes/` were touched.
