# PR-B3 — Review and Resolve Disabled Validation Guards

**Date:** 2026-07-08
**Depends on:** `PHASE2_IMPLEMENTATION_PLAN.md` (PR-B3 definition), `PHASE2_DECISION_MATRIX.md` (D3/D4), `PHASE2_DECISION_MATRIX_SUMMARY.md` (client approval), `PHASE1_RELEASE_NOTES.md` (the PR-A4 observability pattern this reuses).
**Code modified:** Yes.
**Scope:** exactly the two disabled validation guards identified in the Phase 2 audit. No PR-B1, PR-B2, or PR-B4 work included. No database migrations.

---

## 1. Root cause

Two commented-out validation guards were found still sitting in shipped, live-routed controllers during the original Phase 1 audit (`CHECKLIST_SYSTEM_AUDIT.md` §10, §14):

1. A "required questions answered" check in `RentalReadyChecklists\SaveController.php` (the mobile endpoint that **submits** a rental-ready inspection).
2. An "invalid equipment status" guard in `RentalReadyChecklists\IndexController.php` (the mobile endpoint that **lists** rental-ready checklist questions for a piece of equipment).

Neither check has run in production. Phase 2's decision D3 (approved by the client via `PHASE2_DECISION_MATRIX_SUMMARY.md`) directed: investigate why each was disabled, then decide per-guard — restore, remove, or stage with logging — without immediately enforcing anything or changing mobile-facing behavior.

---

## 2. Historical git findings

### Guard 1 — `SaveController.php`, required-questions-answered check

```bash
git log -p --follow -- app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php
```

**Finding: this guard was never live.** It was introduced **already commented out**, in the same commit that first built this endpoint:

| Commit | Date | Message |
|---|---|---|
| `edaf9ef4` | 2025-09-11 00:32 | "Rental ready list and save rental ready api add" |

The diff for that commit shows the `$anyAnswerMissing` check being added with `+` markers — as a comment, from the start. There is no earlier version of this file where the check ran as live code. This rules out "it broke something in production and was disabled" — it reads as a cautious placeholder the original developer wrote but never turned on, most likely because they weren't confident enforcing it immediately during initial development.

**No evidence was found that the check's logic is incorrect.** It rejects a submission if any question has no `selected_answer` — a straightforward, still-relevant completeness rule that doesn't conflict with anything else in the method.

### Guard 2 — `IndexController.php`, invalid-equipment-status guard

```bash
git log -p --follow -- app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php
```

**Finding: this guard WAS live for about 1.5 days**, then was deliberately disabled as part of a same-day refactor:

| Commit | Date | Event |
|---|---|---|
| `7d18c926` | 2025-09-10 01:46 | Guard added as **live** code: reject if `$orderProduct->equipment->current_status->isRented() OR isAvailable()` |
| `879649980` | 2025-09-10 03:03 | Guard's condition lightly refactored, still live |
| `061dc499` | 2025-09-11 19:40 | Guard **disabled** — commented out in the same commit that changed the endpoint's lookup key from `order_product_unique_id` to `equipment_unique_id`, and that **added new logic (now line 83) explicitly designed to handle `isAvailable()` equipment as a normal case** |

The full diff of `061dc499` shows the guard's condition was *rewritten to match the new `$equipment`-based lookup* even while being commented out — it wasn't abandoned as dead debris, it was kept up to date, suggesting the developer intended to revisit it later but never did.

**Critical finding, confirmed by reading the current file directly:** the guard's `isAvailable()` half is **objectively wrong today**, not just disabled out of caution. Line 83 of the current file:
```php
if($equipment->current_status->isAvailable()){
    $equipmentRentalReadyData = null;
}else{
    $equipmentRentalReadyData = $equipment->lastRentalReadyTemplate;
}
```
This code — added in the *same commit* that disabled the guard — explicitly treats "available" equipment as a normal, expected case to list questions for (it's the exact state equipment is in before its first rental-ready inspection). Re-enabling the guard's `isAvailable()` condition as written would make this endpoint reject the very state its own downstream logic is designed to handle.

This is independently corroborated by `SaveController.php`'s own docblock comment (unchanged, still present, lines 37-38):
> "Rental Ready cannot overwrite equipment status while it is actively rented to a customer. **Available, Maintenance, and Damaged are all valid states for a pre-rental inspection.**"

The `isRented()` half of the guard is a different story — `SaveController.php` (the write/submit endpoint) has its own **live, currently-enforced** guard rejecting rented equipment (line 39: `if ($equipment && $equipment->current_status->isRented())`, returning HTTP 403). It's a legitimate, open question whether the read/list endpoint should mirror that same rule — unlike the `isAvailable()` half, this one isn't contradicted by anything in the current code, just unresolved.

---

## 3. Current behavior (before this PR)

| Guard | Current behavior |
|---|---|
| Guard 1 (`SaveController.php`) | A rental-ready inspection can be submitted and saved successfully even if some questions have no selected answer. No error, no log, no trace. |
| Guard 2 (`IndexController.php`) | Rental-ready checklist questions can be listed for equipment in any status, including currently-rented equipment. No error, no log, no trace. |

---

## 4. New behavior (after this PR)

**No HTTP response, status code, or saved data changes for either endpoint.** Both guards now compute their original condition and log a structured warning when it would have triggered — nothing is rejected.

| Guard | Verdict | New behavior |
|---|---|---|
| Guard 1 (`SaveController.php`) | **Still valuable — staged as observability, per D3.** No evidence it's wrong; kept for a future enforcement decision informed by real telemetry. | Logs `'Rental Ready checklist saved despite unanswered questions'` to the `api_errors` channel with `equipment_id`, `equipment_unique_id`, `total_questions`, and `unanswered_count` whenever `$anyAnswerMissing` is true. Response is unchanged — still `200 OK`, still saves. |
| Guard 2, `isRented()` half | **Still an open question — staged as observability, per D3.** Mirrors `SaveController.php`'s own live enforcement; worth knowing the real frequency before deciding whether the read endpoint should match. | Logs `'Rental Ready checklist questions listed for currently-rented equipment'` with `equipment_id`, `equipment_unique_id`, `order_product_id` whenever the condition is met. Response is unchanged — still `200 OK`, still lists the questions. |
| Guard 2, `isAvailable()` half | **Confirmed obsolete — permanently removed, per D3's "remove" option.** Contradicted by the endpoint's own downstream logic (line 83) and by `SaveController.php`'s own documented design. | Deleted entirely. Not logged, not staged — there is nothing to observe, since resurrecting it in any form (even as a log) would misrepresent a normal, expected case as a rejection candidate. |

---

## 5. Files changed

| File | Change |
|---|---|
| `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php` | Added `use Illuminate\Support\Facades\Log;`. Replaced the commented-out guard block with live code that computes `$anyAnswerMissing` (unchanged logic) and logs a warning instead of returning an error response. |
| `app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php` | Added `use Illuminate\Support\Facades\Log;`. Replaced the commented-out guard block: the `isAvailable()` condition was removed permanently; the `isRented()` condition was kept and now logs a warning instead of returning an error response. |
| `tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php` *(new)* | 6 tests — see §7. |

No other files were touched. No unrelated refactoring was performed in either controller.

---

## 6. Commands run

```bash
git log --follow --oneline -- app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php
git log -p --follow -- app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php
git show <commit> -- app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php
git log --follow --oneline -- app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php
git log -p --follow -- app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php
git show --format="%H%n%an <%ae>%n%ad%n%s" -s <commit>   # per commit, to build the timeline in §2
php -l app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php
php -l app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php
php -l tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
php artisan test --env=testing tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
php artisan test --env=testing tests/Unit/Equipment/EquipmentStatusServiceLogTest.php   # regression check
```

---

## 7. Tests added

`tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php` — 6 tests:

1. `test_save_logs_when_a_question_is_left_unanswered` — submits a checklist answering only the optional question, leaving the required one out. Asserts the response is still `200 OK` / `success: true`, and asserts the warning is logged with the correct `equipment_id` and `unanswered_count: 1`.
2. `test_save_happy_path_all_answered_does_not_log` — both questions answered. Asserts `200 OK` and **zero** warning records.
3. `test_save_still_rejects_currently_rented_equipment` — confirms `SaveController.php`'s own separate, live, already-enforced guard (equipment currently rented → HTTP 403) is **untouched** by this PR.
4. `test_index_still_returns_questions_for_rented_equipment_but_logs` — lists questions for currently-rented equipment with an active order product. Asserts `200 OK` (behavior unchanged — the guard was already disabled before this PR) and asserts the new warning is logged.
5. `test_index_available_equipment_is_returned_normally_and_does_not_log` — confirms the removed `isAvailable()` condition is gone for good: available equipment (the normal pre-inspection state) is listed successfully with **no** log noise.
6. `test_index_non_rented_non_ordered_equipment_does_not_log` — equipment in `maintenance` status, no order product. Confirms the `isRented()` logging condition doesn't false-positive on unrelated statuses.

### A note on a pre-existing, unrelated bug found while building the test fixtures

While building the fixture for test #4, a request to `IndexController.php` crashed with `Error: Call to a member function filter() on null` at line 60. Root cause: when `$equipment->orderProduct` exists but that order product has no `equipmentRentalReadyTemplate` (i.e., no rental-ready inspection has ever been recorded against it), `optional($equipment->orderProduct->equipmentRentalReadyTemplate?->checklistQuestions)->pluck(...)` returns `null` (not an empty collection), and the immediately-chained `->filter()` call on that `null` throws. **This bug pre-dates this PR and is unrelated to either validation guard** — it was not introduced by, and is not fixed by, this change. The test fixture was adjusted to seed a real `EquipmentRentalReadyTemplate` + `EquipmentRentalReadyChecklistQuestion` so it exercises the same success path a real prior inspection would, rather than tripping this separate bug. **Recommend filing this as its own small bug-fix PR** (likely Track C scope — a one-line `?->filter()` or `?? collect()` fix) rather than folding it into PR-B3, per the "keep this PR focused" instruction.

---

## 8. Test results

```
php artisan test --env=testing tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
→ 6 passed (19 assertions)

php artisan test --env=testing tests/Unit/Equipment/EquipmentStatusServiceLogTest.php
→ 11 passed (13 assertions)   — regression check: SaveController.php calls
   EquipmentStatusService::markAvailableFromRentalReady/markMaintenanceFromRentalReady/
   markDamagedFromRentalReady; confirms this PR didn't disturb those call sites.
```

**Total: 17/17 passing, 0 failures, 0 new regressions.**

---

## 9. Rollback strategy

**Very low risk.** Both changes are additive logging (or, for the `isAvailable()` half, a deletion of code that was never live and is now proven wrong) — no response shape, status code, or database write changed for any request. Reverting either controller change is a clean, isolated revert with no data cleanup required. If the `isAvailable()` removal is ever second-guessed, `git log` on this PR's commit shows exactly what was removed and why, with the supporting evidence in §2 above.

---

## 10. Deployment considerations

- **No database migrations.**
- **No mobile-facing behavior change of any kind.** Every response shape, status code, and success/failure outcome for both endpoints is identical before and after this PR — confirmed by tests #1, #2, #4, #5, #6 above, and by test #3 proving the one guard that *is* live and enforced (`SaveController.php`'s own rented-equipment check) is untouched.
- The two new log messages land in the existing `api_errors` channel (already used elsewhere in this codebase — no new channel, no new infrastructure).
- Safe to deploy directly to production with no staged rollout needed for the deployment itself — the "staging" in this PR is the observability-before-enforcement pattern (§11), not a deployment-risk mitigation.

---

## 11. Recommendation for future enforcement

Per Phase 2 decision D3 (client-approved: "investigate then decide, observability-first, not immediate enforcement"), **do not enforce either guard yet.** Recommended next steps, mirroring exactly how Phase 1's PR-A4 → PR-A6 relationship was structured:

1. **Let both log points run in production for a review window** (suggest the same 2-4 weeks used for PR-A4's telemetry plan — these can share the same review cycle, since both live in the `api_errors` channel).
2. **Review the `api_errors` log for both messages** and answer: how often does each guard's condition actually occur in real traffic? Is it a handful of edge cases, or a meaningful fraction of requests?
3. **For Guard 1** (required questions answered): if occurrences are rare, re-enabling as a hard rejection is likely low-risk — proceed to a follow-up PR that flips the log into a `422` rejection, with its own tests proving the rejection fires correctly and doesn't affect complete submissions. If occurrences are common, that signals either a client-side gap (some mobile version allows incomplete submission) worth fixing at the source, or that the rule itself needs reconsidering before enforcing it.
4. **For Guard 2's `isRented()` half**: same evaluation. If it turns out equipment is essentially never listed while rented, enforcing costs nothing and closes the asymmetry with `SaveController.php`'s existing rule. If it's common, investigate *why* — legitimate use case (e.g., staff reviewing a checklist for equipment about to be returned) vs. a client bug — before deciding to reject it.
5. **The `isAvailable()` half needs no further action or decision.** It's confirmed wrong and has been removed permanently — there is nothing to observe or revisit.

This PR's own follow-up (if either guard is re-enabled) should be scoped as its own small, focused PR — not bundled with PR-B1, PR-B2, or PR-B4.

No code has been committed as of this document.
