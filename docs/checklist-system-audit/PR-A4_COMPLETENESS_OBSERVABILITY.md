# PR-A4 — Completeness Observability Logging

**Date:** 2026-07-07
**Depends on:** `CORRECTION_PHASE1_PLAN.md` Issue #3, `CHECKLIST_SYSTEM_AUDIT.md` §8/§12.
**Code modified:** Yes. **No behavior change** — observability only, per the plan's explicit design.

---

## 1. Problem being made visible (not fixed)

`SaveDeliveryController` and `SaveReturnController` set `delivery_status`/`pickup_status = 'Completed'` **unconditionally**, regardless of whether:
- a signature was actually captured,
- every `required_question = 1` master question has a selected answer,
- a checklist array was submitted at all.

`CORRECTION_PHASE1_PLAN.md` deliberately scoped the fix for this as **observability, not enforcement**, for this phase: this system has zero prior automated test coverage and an unknown population of real mobile client versions in the field, so hard-blocking now risks breaking real deliveries/returns in production with no way to quantify that risk in advance. This PR makes the gap measurable so a future phase can decide whether/how to enforce it, based on real production telemetry rather than a guess.

---

## 2. Files changed

| File | Change |
|---|---|
| `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php` | Added `use Illuminate\Support\Facades\Log;`. Computes `$missingRequiredQuestionCount` inside the existing checklist-processing block (stays `null` if no checklist was submitted at all). Added a call to a new private `logIfDeliveryIncomplete()` method right before the event dispatch/success response. |
| `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` | Same pattern: `$missingRequiredQuestionCount` computed inside the existing checklist block; new private `logIfReturnIncomplete()` method called before the event dispatch/success response. |
| `tests/Feature/CustomerChecklists/CompletenessObservabilityLoggingTest.php` *(new)* | 8 tests — see §4. |

**Nothing else was touched.** `delivery_status`/`pickup_status`, the HTTP response shape, and every other line of existing logic in both controllers are byte-for-byte unchanged. Every test in this PR explicitly asserts the response is still `200 OK` and the status is still `'Completed'`, precisely to prove this.

---

## 3. What gets logged and when

Both new private methods follow the identical shape — compute three booleans/counts, skip logging entirely if all three indicate a genuinely complete submission:

```php
$hasSignature       = !empty($orderProductData['delivery_signature_media_id']); // or pickup_...
$checklistSubmitted = isset($validated['checklist']) && !empty($validated['checklist']);
$hasMissingRequired = $missingRequiredQuestionCount !== null && $missingRequiredQuestionCount > 0;

if ($hasSignature && $checklistSubmitted && !$hasMissingRequired) {
    return; // fully complete — no log noise on the happy path
}

Log::channel('api_errors')->warning('Delivery marked Completed despite incomplete checklist submission', [
    'order_product_id'               => $orderProduct->id,
    'order_id'                       => $orderProduct->order_id,
    'signature_present'              => $hasSignature,
    'checklist_submitted'            => $checklistSubmitted,
    'missing_required_question_count'=> $missingRequiredQuestionCount,
]);
```

- Logged to the existing `api_errors` channel (already used elsewhere in this codebase, e.g. `ApiResponseHelper`) — no new log channel was added.
- `missing_required_question_count` computation: for delivery, counts master `CustomerAdminQuestion` rows (from the equipment's checklist-master template) where `required_question` is `true` or `null` (mirroring the mobile resource layer's existing null→required default, per the Phase 1 audit's own caveat) and no `is_delivery_answer=true` row exists for that question. The return side mirrors this using `is_return_answer` and the order-level snapshot's `question` relation back to the master record. When no checklist was submitted at all, this count is left `null` rather than computed against stale/absent data — `checklist_submitted=false` is already the dominant signal in that case.

---

## 4. Tests: proving logging happens, and that the happy path doesn't log

`tests/Feature/CustomerChecklists/CompletenessObservabilityLoggingTest.php` — 8 tests, 4 per controller:

1. `test_delivery_logs_when_signature_missing` / `test_return_logs_when_signature_missing` — full checklist answered (including the required question), no `signature_media` sent. Asserts the warning fires with `signature_present=false`, `checklist_submitted=true`, `missing_required_question_count=0`.
2. `test_delivery_logs_when_required_question_unanswered` / `test_return_logs_when_required_question_unanswered` — signature present, checklist submitted, but only the optional question answered. Asserts `missing_required_question_count=1`.
3. `test_delivery_logs_when_checklist_omitted` / `test_return_logs_when_checklist_omitted` — signature present, `checklist` key omitted entirely. Asserts `checklist_submitted=false`, `missing_required_question_count=null`.
4. `test_delivery_happy_path_does_not_log` / `test_return_happy_path_does_not_log` — signature present, every question (including the required one) answered. Asserts **zero** warning records on the `api_errors` channel — proving the happy path produces no log noise.

Every test also asserts `$response->assertOk()` and that `delivery_status`/`pickup_status` is still `'Completed'` — direct proof this phase changes no behavior.

**Test technique:** a real Monolog `TestHandler` is attached to the `api_errors` channel's underlying logger before each request (`Log::channel('api_errors')->getLogger()->pushHandler($handler)`), rather than mocking the `Log` facade. This was a deliberate choice: both controllers already log to other channels in the same request in various code paths (`billing_engine`, `equipment_status`), and fully mocking the `Log` facade would require stubbing every one of those call sites too. Listening only on the target channel's real logger avoids that entirely — a pattern worth reusing for any future logging-observability test in this codebase.

**Test fixtures:** the delivery-side tests build a real `ChecklistMaster → CustomerAdminTemplate → CustomerAdminTemplateQuestion → CustomerAdminQuestion → CustomerAdminQuestionAnswer` chain with one required and one optional question, matching exactly what `SaveDeliveryController` reads via `Equipment.checklistMaster.customerAdminTemplate.templateQuestions.question.answers`. The return-side tests instead seed a pre-existing order-level `order_product_checklist_questions`/`_answers` snapshot directly (the state a prior delivery would have produced), since `SaveReturnController` operates on that snapshot rather than the master template.

---

## 5. Test results

```
php artisan test --env=testing tests/Feature/CustomerChecklists/CompletenessObservabilityLoggingTest.php
→ 8 passed (42 assertions)

php artisan test --env=testing \
  tests/Feature/CustomerChecklists/ChecklistTransactionTest.php \
  tests/Feature/BillingEngine/MobileReturnCycleIdempotencyTest.php \
  tests/Feature/CustomerChecklists/CompletenessObservabilityLoggingTest.php
→ 19 passed (102 assertions)   — PR-A2 (transaction rollback) and PR-A3 (billing
                                   idempotency) suites unaffected by this change.
```

All 8 new tests passed on the first real run against the implementation (no logic bugs found during test-writing this time, unlike PR-A1/A2/A3's sessions).

---

## 6. Rollback notes

**Rollback risk: effectively zero.** This is a pure logging addition — no schema change, no behavioral branch, no change to what's written to `order_products` or any other table. Reverting is a clean removal of the two new private methods and their call sites; nothing else depends on this logging existing.

---

## 7. What's deliberately deferred

- **No enforcement.** Per `CORRECTION_PHASE1_PLAN.md`, true enforcement (rejecting incomplete submissions, or introducing a distinct "Incomplete"/"Partial" status) is explicitly scheduled as a later phase, gated on reviewing the telemetry this phase produces in production — not part of this PR.
- **`IMPLEMENTATION_ROADMAP.md`'s PR-A6** (the 44-row "mobile omits checklist entirely" gap from `ISSUE5_INVESTIGATION_FINDINGS.md`) remains explicitly deferred until this logging has run in production for a review window (2–4 weeks was the roadmap's suggestion), so its eventual fix shape can be chosen from real frequency data rather than guessed now.
- No UI changes, no PR-A5-or-later work started beyond what's already documented in prior PR docs.

No code has been committed as of this document.
