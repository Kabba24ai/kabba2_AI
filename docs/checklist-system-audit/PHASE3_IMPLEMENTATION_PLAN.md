# Phase 3 Implementation Plan — Checklist System

**Date:** 2026-07-13
**Type:** Planning only. **No code was modified to produce this document.**
**Depends on:** every document in `docs/checklist-system-audit/` — `CHECKLIST_SYSTEM_AUDIT.md`, `CORRECTION_PHASE1_PLAN.md`, `ISSUE5_INVESTIGATION_FINDINGS.md`, `CHECKLIST_EXEMPT_ADMIN_CLOSURE.md`, `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md`, `PHASE3_RESULTS.md`, `PHASE3_RUNTIME_VALIDATION_SQL_PLAN.md`, `IMPLEMENTATION_ROADMAP.md`, all PR-A1–A4 docs, `PHASE1_RELEASE_NOTES.md`, `PHASE2_DECISION_MATRIX.md` (+ summary), `PHASE2_IMPLEMENTATION_PLAN.md`, all PR-B1/B2/B3/B4.1/B4.2/B4.3 readiness+refactor+bugfix docs, `PROJECT_MILESTONE_TRACKER.md`.

---

## 0. Executive summary

Phase 1 (PR-A1–A5) and Phase 2 Track B (PR-B3, PR-B1, PR-B2, PR-B4.1, PR-B4.2, PR-B4.3) are both complete, tested, and regression-clean (138 tests / 558 assertions / 0 failures in the final combined run). What remains is a substantial backlog that was deliberately deferred, discovered-but-not-fixed, or never in scope for Phases 1–2. This document is the single register of everything still open, classified by severity and by type, with a concrete sequencing and deployment plan.

**Headline finding of this planning pass:** several items the source documents describe as "still undecided" are actually already decided and implemented — `PHASE2_DECISION_MATRIX.md`'s own decision-log table (lines 144-150) was simply never updated after D2, D3, D4, and D5 were resolved through the actual PR-B2/PR-B3/PR-B4 work. This is flagged as **DOC-1** below and should be corrected first, before it misleads anyone reading that file in isolation.

**Scale of the remaining backlog:** ~52 distinct items across 10 categories, plus 5 outstanding non-code process gates carried over from Phase 1/2. The largest single item — full schema/model unification of the Rental Ready and Customer Admin trees (**ARCH-1**) — is explicitly a candidate, not a commitment; it requires its own dedicated audit and stakeholder go-ahead before any implementation planning, and should not be allowed to block the rest of this backlog.

**Revision note (this update):** an independent review of the initial plan added two previously-missing items (**BUG-12**, **DB-6**), reclassified **DB-5** from Low to Medium severity, clarified **SEC-1**'s severity rationale, reworded the Sprint 5 description to remove an implied completion date for ARCH-2, and added a Phase 3 Decision Log (D6, D7) for the two register items that need a product/investigation decision before implementation. See §8.

**Revision note (2026-07-15):** Sprint 1's independent review of **BUG-11** (P3-1, merged) surfaced a new follow-up item, **BUG-13** — a read/write behavior inconsistency between Rental Ready `IndexController` and `SaveController` for equipment on an order with no rental-ready template yet. Added to §2.1 with a corresponding **D8** entry in the §8 Decision Log (Pending). Not implemented — awaiting a product/engineering decision, per this document's own discipline for undecided register items.

**Revision note (2026-07-15, later same day):** the independent review of **CLEAN-9** (P3-4, merged) surfaced a new follow-up item, **BUG-15** — contradictory JavaScript gating on the Checklist Master edit page's Step 3 Continue button, currently correct only by accident of script-execution order. Added to §2.1. Pre-existing, not caused by P3-4. Not implemented — small, no product decision needed, just not yet picked up.

**Revision note (2026-07-15, evening):** the independent review of **TD-13** (P3-5, merged) surfaced a new follow-up item, **TD-16** — the same `Schema::drop()`-inside-transaction test anti-pattern TD-13 diagnosed in one test also appears in 7 additional BillingEngine test files, independently corroborated by a pre-existing comment in `ChecklistTransactionTest.php`. Added to §2.2. Test-only, no production impact. Not implemented — not yet picked up.

**Revision note (2026-07-15, night):** the P3-6 readiness review (mobile-facing API cleanup — BUG-6, API-1, API-2) found BUG-6's scope was one bug short: the same array branch also returns `id` as always `0` (reads a nonexistent `main_id` key). BUG-6's entry expanded to cover both bugs together. Added three new Decision Log entries, **D9-D11**, one per P3-6 item — all three require mobile-team input that cannot be resolved by backend code inspection alone, tracked via a new `P3_6_MOBILE_COMPATIBILITY_QUESTIONNAIRE.md`. P3-6 implementation remains blocked on D9-D11 being recorded. See `P3_6_READINESS_REVIEW.md` for the full investigation.

**Revision note (2026-07-15, late night):** **BUG-15 resolved.** Consolidated the Checklist Master edit page's contradictory Step 3 JavaScript gating into a single named function, removing the earlier block's contradictory `disabled = true` assignment. See `P3_BUG15_STEP3_GATING.md` for full root cause, before/after behavior, and test results. DB-1's prerequisite audit (P3-7) also completed this session — see `P3_7_DB1_FK_READINESS.md` — verdict READY, no migration created yet per its own scope.

**Revision note (2026-07-16):** **TD-16 resolved.** Replaced all 14 `Schema::drop()`-inside-transaction failure simulations across 7 BillingEngine test files with a deterministic, non-DDL Eloquent listener technique. Also found and fixed a genuine, separate pre-existing Mockery-setup gap in one of those tests (an incomplete `Log::channel()` stub), uncovered only once the DDL-corruption noise was removed — the test's real assertion was preserved, not weakened. Zero production code changed. See `P3_TD16_TRANSACTION_SAFE_FAILURE_TESTS.md` for full detail.

**Revision note (2026-07-17, later same day, after P3-10):** **P3-11 resolved** — **BUG-5** fixed. `RemoveController` reset the delivery-side fields (and `is_returned`) on checklist removal, but never the pickup-side counterparts `SaveReturnController` actually writes on a return — leaving a removed order product with `is_returned=false` sitting alongside a still-`'Completed'` `pickup_status`, a still-set `pickup_by`, and a stale `damage_status`. Fixed by extending the existing reset array with the pickup-side counterpart of every delivery-side field already reset (`pickup_by`, `pickup_notes`, `pickup_signature_media_id` + file cleanup, `pickup_status` → `'Pending'`, `end_hours`), plus `damage_status` (return-only, no delivery counterpart). Fields with no already-reset delivery-side counterpart (scheduling/fuel/billing fields, the separate driver-checklist subsystem's pickup fields) were deliberately left untouched, mirroring the delivery side's own existing (curated) reset pattern rather than introducing a new, undecided one — no ambiguity requiring a stop was found. Verified the reset, the media cleanup, and the preserved-fields boundary each with a dedicated test, plus a full regression run (69 passed). BUG-3, `SaveDeliveryController`, `SaveReturnController`, API format, and architecture were not touched; P3-9 and P3-10 behavior unaffected. See `P3_11_BUG5_RETURN_STATE_RESET.md`.

**Revision note (2026-07-17, later same day, after P3-12):** **P3-12A resolved — BUG-3 now fully closed.** A complete application search for every place that deletes `order_product_checklist_questions`/`order_product_checklist_question_answers` found four more call sites beyond `RemoveController` (already fixed in P3-12) with the identical uncascaded-soft-delete gap: `SaveDeliveryController.php:121` (the literal re-delivery scenario the original N2 audit finding described), `AssignEquipmentController.php:71`, `RemoveEquipmentController.php:51`, and both branches of `UpdateProductScheduleController.php` (`:209`/`:250`). All five fixed with the same minimal two-line pattern (fresh question-id lookup + bulk answer soft-delete, before the existing question soft-delete) — no shared helper/refactor introduced, per this PR's explicit scope. Two other locations were confirmed to already cascade correctly and needed no fix: `OrderProduct`'s own `deleting`/`restoring` model hooks (the precedent every fix mirrors) and `BulkDeleteController`, which relies on that same event chain. Added 6 new tests (`ChecklistAnswerCascadeTest.php`) covering all four newly-fixed controllers/branches plus cross-order-product isolation, 1 new `SaveDeliveryController` test proving the destructive-rebuild cascade, and 1 new `ChecklistTransactionTest` test proving the cascade rolls back correctly on a forced failure. Verified via 82 passed / 311 assertions. One pre-existing, unrelated gap (no transaction wrapper in three of the four admin controllers) was found and documented as a follow-up item only, not fixed. See `P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md`.

**Revision note (2026-07-17, later same day, after P3-11):** **P3-12 resolved (partially closes BUG-3 — see that entry's scope note)** — `RemoveController` soft-deleted checklist questions via a bulk query-builder call but never cascaded the soft-delete to their child `OrderProductChecklistQuestionAnswers` rows, leaving live, orphaned answers under a soft-deleted parent question. Fixed by adding one bulk soft-delete of the child answer rows (looked up from the request's already eager-loaded `checklistQuestions.answers` relation) immediately before the existing question soft-delete, inside the same transaction — mirroring an already-correct precedent already present in `OrderProduct`'s own `deleting`/`restoring` model hooks. Scoped explicitly to `RemoveController` only, per this PR's brief: four other call sites with the identical gap (`AssignEquipmentController`, `RemoveEquipmentController`, `UpdateProductScheduleController` ×2, and `SaveDeliveryController`'s own destructive-rebuild statement — the literal scenario the original N2 audit finding described) were found and documented, not fixed. Verified via 71 passed / 254 assertions, including two new tests proving no active answers remain after removal and that removing one order's checklist doesn't touch another order's answers. BUG-2, BUG-4, BUG-5, `SaveDeliveryController`, `SaveReturnController`, API work, and architecture were not touched. See `P3_12_BUG3_SOFT_DELETE_CHECKLIST_ANSWERS.md`. This was the last item in the P3-9–P3-12 delivery/return bug-fix wave.

**Revision note (2026-07-17, later same day, after P3-9):** **P3-10 resolved** — **BUG-4** fixed. `SaveDeliveryController` had no guard against a duplicate delivery submission for an already-delivered, not-yet-returned order product — a resubmission (accidental or via an equipment status changed by some other means without a genuine return) silently deleted and rebuilt the checklist snapshot, re-uploaded media, and re-ran equipment assignment, with no rejection. Fixed by adding a guard, placed right after the existing `isRented()` 409 check and before any destructive step: `is_delivered=true && is_returned=false` now returns `409` with the same response shape and translation key `SaveReturnController` already uses for its own already-submitted guard. This combination reliably distinguishes a same-cycle duplicate from a legitimate new cycle, since `is_returned` is only ever set `true` by a genuine return — no ambiguity requiring a stop was found. Verified a rejected duplicate leaves the checklist rows, signature media, equipment status, and status-log count completely unchanged, and that a genuine post-return re-delivery cycle still succeeds (composing correctly with P3-9's fix). Two of P3-9's own BUG-2 test fixtures were updated to also set `is_returned=true` (matching what a real return actually does) so they continue to represent a legitimate new cycle under the new guard — P3-9's production code was not touched. See `P3_10_BUG4_DUPLICATE_DELIVERY_GUARD.md`. BUG-3, BUG-5, API normalization (P3-6), and architecture work remain untouched, per this PR's explicit scope.

**Revision note (2026-07-17, later same day):** **P3-9 resolved** — **BUG-2** fixed. `SaveDeliveryController` skipped `EquipmentStatusService::markRented()` on any re-delivery of already-assigned equipment (the "new assignment" `if` condition was false whenever `equipment_id` already matched), leaving a stale `current_status` (e.g. `'damaged'`) on equipment that had just been successfully redelivered, with no new `equipment_status_logs` row. Fixed by splitting the assignment-metadata write from the equipment-status transition: the former stays conditional as before, the latter (`equipment_hours` + `markRented()`) now runs unconditionally on every successful delivery — safe because the controller's own `isRented()` 409 guard already ensures execution only reaches that point when the equipment is not currently rented. BUG-2's own P3-8 characterization test was flipped from asserting the bug to asserting the fix, plus one new regression test for the `maintenance`-status re-delivery path. No billing, checklist-answer, media-upload, transaction, or idempotency behavior changed — confirmed via `MobileReturnCycleIdempotencyTest` (3 passed) alongside the full CustomerChecklists/equipment-status regression surface (66 passed). See `P3_9_BUG2_REDELIVERY_STATUS_FIX.md`. BUG-3, BUG-4, BUG-5, and API/architecture work remain untouched, per this PR's explicit scope.

**Revision note (2026-07-17):** **P3-8 resolved** — baseline characterization tests added for `SaveDeliveryController`, `SaveReturnController`, and `RemoveController` (PR order item 8), the explicit prerequisite for P3-9–P3-12 and later ARCH-2. 25 new tests across three new files pin the controllers' not-found/conflict/already-submitted response branches, damage/fuel billing side effects, and — most importantly — the exact current (buggy) behavior of **BUG-2**, **BUG-3**, and **BUG-4** (BUG-12 was not separately characterized: `OrderProduct` has no category column at all to assert against, so its gap is structural/absent-validation rather than a specific wrong-value behavior a characterization test could pin). Writing **BUG-5**'s test also surfaced that this document's own BUG-5 write-up was slightly inaccurate — corrected in place below (see that entry). No production code changed. See `P3_8_BASELINE_CHARACTERIZATION_TESTS.md` for full detail.

**Revision note (2026-07-16, follow-up):** **P3-2A resolved** — a corrective follow-up to **SEC-1** (P3-2, merged). SEC-1's `bodyParameters()` docblock update called `OrderTermsStatus::getValues()`, a method that did not exist on that (reused) enum — a genuine `Error: Call to undefined method` that shipped past SEC-1's own test suite and was only discovered via a real production invocation, reported directly by the user. Root cause of the coverage gap: `bodyParameters()` is documentation-only, invoked solely by Scribe's `scribe:generate` command, never by the real HTTP validation path or by any existing test. Fixed the missing method, then closed the gap with three independent layers of coverage: a direct `bodyParameters()` test, a generic reflection-based scan asserting all 61 `FormRequest` classes that override `bodyParameters()` don't throw, and an end-to-end `scribe:generate` smoke test. All three were individually verified to catch the exact original bug via revert-and-reproduce. See `P3_2_SEC1_STATUS_VALIDATION.md`'s "P3-2A follow-up" section for full detail. **Process improvement adopted:** every future `FormRequest` change must test both the real HTTP validation path (`rules()`) and documentation-only methods (`bodyParameters()`), not just the former.

---

## 1. Outstanding Phase 1/2 process gates (not new Phase 3 work — carried forward, tracked here so they aren't lost)

| # | Item | Owner | Status |
|---|---|---|---|
| G1 | **Finance reconciliation for PR-A3** (billing idempotency fix) — a read-only query across historical multi-cycle order products to check for legitimate second charges silently dropped by the pre-fix bug. Blocks PR-A3's *production* deploy only; code/tests/staging are unblocked. | Finance/Accounting | Pending — no engineering action possible |
| G2 | **PR-A4 telemetry window** (2–4 weeks of production logging) before PR-A6 (completeness enforcement) can be scoped. | Engineering + Product | Status unconfirmed — verify whether PR-A4 has actually been in production long enough; if yes, **MOB-1**/**MOB-2** below can start now |
| G3 | **Communicate the `equipment_status_logs` historical gap** (permanently incomplete before the PR-A1 fix) to any team consuming that table for reporting. | Engineering (comms only) | Not confirmed done — cheap, do immediately |
| G4 | **Write Phase 2 release notes** (mirroring `PHASE1_RELEASE_NOTES.md`) — the explicit next milestone-tracker item. | Engineering | Not started — should precede or accompany Phase 3 kickoff communication, not itself Phase 3 engineering work |
| G5 | **PHASE2_DECISION_MATRIX.md decision log correction** — see DOC-1 below. | Engineering | Cheap, do immediately |

---

## 2. Master issue register

Each item lists: root cause, current behavior, desired behavior, business impact, technical impact, dependencies, recommended solution, estimated effort, risk.

### 2.1 Bugs

---

**BUG-1 — Fuel/damage charge amounts entirely client-trusted, no server-side recomputation** — **High**
- **Root cause:** The mobile return flow accepts client-submitted `fuel_total_charge`/damage amounts and bills them as-is; no server-side recomputation from meter readings or damage-answer schedule exists, despite a code comment implying a staff-review gate.
- **Current behavior:** Confirmed via `PHASE3_RESULTS.md` S10/N3 — a fuel level that physically *increased* between readings still produced an accepted $15 charge with no rejection.
- **Desired behavior:** Server recomputes (or at minimum sanity-bounds) the charge amount from the submitted meter readings/damage answers before billing; client-submitted amounts become an input to a calculation, not the final value.
- **Business impact:** Real financial exposure — a buggy or malicious mobile client can set an arbitrary billed amount.
- **Technical impact:** Requires new recomputation logic in `ChargeService`/`BillingEngine`; touches the same surface as the already-shipped PR-A3 idempotency fix.
- **Dependencies:** None blocking; benefits from PR-A3's cycle-key work already being in place.
- **Recommended solution:** Add a server-side amount-validation/recomputation step; log-only (warn on disagreement) first, mirroring the PR-A4/PR-B2 observability-first pattern this project has used consistently, before any hard rejection.
- **Estimated effort:** Medium (3-5 days, mostly test-writing since zero coverage exists for this exact scenario today).
- **Risk:** Financial-domain change — treat with the same finance-gate discipline as PR-A3.

---

**BUG-2 — Re-delivering with the SAME equipment never calls `markRented()` again (N1)** — **High** — ✅ **RESOLVED (2026-07-17, P3-9)**
- **Root cause:** `SaveDeliveryController`'s "new assignment" branch only fires when `empty(equipment_details) OR equipment_id differs` from the current assignment; re-delivering the *same* equipment matches neither condition.
- **Previous behavior:** Confirmed via `PHASE3_RESULTS.md` N1 — order product shows `delivery_status='Completed'` (freshly delivered) while its equipment `current_status` remains `'damaged'` from the prior return — a state combination the system's own logic shouldn't allow to coexist.
- **Resolution:** Split the "new assignment" `if` block into two concerns — the assignment-metadata write (`equipment_id`/`equipment_details`/`assigned_by`/`assigned_at`) stays gated on the original condition, unchanged; the equipment-hours assignment and `EquipmentStatusService::markRented()` call were moved out to run unconditionally on every successful delivery. Safe because the controller's own `isRented()` 409 conflict guard, evaluated earlier in the same method, already guarantees execution only reaches this point when the equipment is not currently rented. No other behavior (billing, checklist answers, media uploads, transaction boundary, idempotency) changed. See `P3_9_BUG2_REDELIVERY_STATUS_FIX.md` for full detail.
- **Business impact:** Equipment can no longer appear "delivered" to a customer while still flagged damaged/in-maintenance in the system.
- **Technical impact:** One `if` block split in `SaveDeliveryController`; no other files touched besides its own characterization test.
- **Files changed:** `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php`; `tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php` (BUG-2 test flipped to assert the fix, 1 new regression test added).
- **Estimated effort:** Small (contained to one file) — matched actual effort.
- **Risk:** Low — verified via 66 passed / 219 assertions across the CustomerChecklists/equipment-status regression surface, plus 3 passed / 20 assertions on `MobileReturnCycleIdempotencyTest` (the closest existing billing-idempotency regression to this exact re-delivery scenario).
- **Estimated effort:** Medium (2-3 days: fix + baseline characterization tests for `SaveDeliveryController`, which don't exist yet).
- **Risk:** Low technical risk; verify no downstream logic relies on the current (buggy) skip-if-same-equipment behavior.

---

**BUG-3 — Soft-deleted checklist questions leave live child answer rows behind (N2)** — **Medium** — ✅ **FULLY RESOLVED (2026-07-17, P3-12 + P3-12A)**
- **Root cause:** No cascading soft-delete logic between `order_product_checklist_questions` and `order_product_checklist_question_answers`.
- **Previous behavior:** Confirmed via `PHASE3_RESULTS.md` N2 — after a re-delivery soft-deletes the old question row, its child answer rows remain fully live (`deleted_at IS NULL`) with `is_return_answer=1` still set, orphaned under a parent no longer in the active checklist.
- **Resolution (P3-12):** Fixed the cascade inside `RemoveController::__invoke()` — the checklist-removal path — by soft-deleting every child answer row immediately before the existing bulk question soft-delete, inside the same transaction.
- **Resolution (P3-12A, this follow-up):** A complete application search (relationship deletes, query-builder deletes, model-instance deletes, `forceDelete()`, raw SQL, migrations, console commands) confirmed four more affected call sites, all fixed with the same minimal two-line soft-delete-first pattern: `SaveDeliveryController.php:121` (the literal re-delivery scenario N2 originally described), `AssignEquipmentController.php:71`, `RemoveEquipmentController.php:51`, and `UpdateProductScheduleController.php:209`/`:250` (the 'Reschedule' and 'Pending' branches). Two additional locations were confirmed to already cascade correctly with no fix needed: `OrderProduct`'s own `deleting`/`restoring` model hooks (the precedent every fix mirrors), and `BulkDeleteController.php:330` (relies on that same model-event chain via `$order->delete()`). See `P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md` for the full per-site classification and reasoning.
- **Business impact:** Closed — no confirmed downstream consumer reads orphaned rows today, but the latent data-integrity risk for any future reporting/audit feature is now eliminated across every code path, not just checklist removal.
- **Technical impact:** Five call sites across four controllers, each a two-line addition (fresh `pluck('id')` + `whereIn(...)->delete()`) plus one new import — no shared helper/trait introduced, per this PR's explicit instruction to avoid refactoring solely for deduplication.
- **Files changed:** `RemoveController.php` (P3-12); `SaveDeliveryController.php`, `AssignEquipmentController.php`, `RemoveEquipmentController.php`, `UpdateProductScheduleController.php` (P3-12A); plus `RemoveControllerCharacterizationTest.php` (P3-12, 2 new tests), `SaveDeliveryControllerCharacterizationTest.php` (1 new test), `ChecklistTransactionTest.php` (1 new rollback test), and new `ChecklistAnswerCascadeTest.php` (6 tests) (P3-12A).
- **Estimated effort:** Small (1-2 days) for P3-12's `RemoveController` scope, plus this small follow-up for the remaining sites — matched actual combined effort.
- **Risk:** Low — verified via 71 passed / 254 assertions (P3-12) and 82 passed / 311 assertions (P3-12A) across the full CustomerChecklists/equipment-status/billing-idempotency regression surface, including dedicated cross-order-product isolation tests and a transaction-rollback test proving the new cascade is covered by `SaveDeliveryController`'s existing atomicity guarantee. One pre-existing, unrelated gap (no transaction wrapper in `AssignEquipmentController`/`RemoveEquipmentController`/`UpdateProductScheduleController`) was found and documented as a follow-up item only, not fixed.

---

**BUG-4 — No re-delivery guard equivalent to save-return's 409 duplicate-signature guard** — **Medium** — ✅ **RESOLVED (2026-07-17, P3-10)**
- **Root cause:** `SaveReturnController` has a 409 guard against re-submitting when a signature already exists; `SaveDeliveryController` has no equivalent guard, so repeated delivery saves fully wipe/rebuild the checklist snapshot destructively.
- **Previous behavior:** A naive client retry (or a genuine but unintended re-delivery) silently destroys and rebuilds the prior checklist snapshot, re-uploads the signature, and re-fires all side effects.
- **Resolution:** Added a guard immediately after the existing `isRented()` 409 check, before any destructive action: `if ($orderProduct->is_delivered && !$orderProduct->is_returned) { return 409 checklist_already_exists; }`. `is_delivered`/`is_returned` are already-authoritative fields written unconditionally by `SaveDeliveryController`/`SaveReturnController` respectively, so this combination reliably identifies "an active, unreturned delivery already exists" without any schema change or new nonce/cycle-key field — a genuine new cycle (post-return, `is_returned=true`) is unaffected and still succeeds. Reuses `SaveReturnController`'s exact response envelope and translation key (`checklist_already_exists`) for consistency. See `P3_10_BUG4_DUPLICATE_DELIVERY_GUARD.md` for full detail, including how the duplicate-vs-legitimate-cycle distinction was verified not to be ambiguous.
- **Business impact:** Data loss from an unintended or retried duplicate delivery submission is no longer possible; a genuine return-then-redeliver cycle is unaffected.
- **Technical impact:** One new guard clause in `SaveDeliveryController`; two of P3-9's own BUG-2 test fixtures were updated (not weakened) to set `is_returned=true` alongside their equipment-status change, since that's what a real return actually does.
- **Files changed:** `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php`; `tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php` (BUG-4 test flipped, 1 new test, 2 P3-9 fixtures updated).
- **Estimated effort:** Small-Medium (2 days) — matched actual effort.
- **Risk:** Low — verified via 70 passed / 250 assertions across the full CustomerChecklists/equipment-status/billing-idempotency regression surface, including a dedicated test proving a legitimate post-return re-delivery cycle still succeeds.

---

**BUG-5 — `RemoveController` only reverts delivery-side fields** — **Medium** — ✅ **RESOLVED (2026-07-17, P3-11)**
- **Root cause:** The checklist-removal controller resets `delivery_*`/`is_delivered`-family fields and `is_returned`, but leaves `pickup_status`/`pickup_by` (and every other `pickup_*`/`damage_status` field) completely untouched if a product was already returned.
- **Correction (2026-07-17, found while writing P3-8's characterization test):** the original write-up above stated `is_returned` was left untouched — confirmed via a real HTTP characterization test (`RemoveControllerCharacterizationTest::test_bug5_pickup_status_and_pickup_by_are_not_reverted_even_though_is_returned_is`) that this is not quite right: `RemoveController`'s `$orderProductData` array does explicitly reset `is_returned => false`. The actual gap is narrower but arguably worse — `is_returned` flips to `false` while `pickup_status` still reads `'Completed'` and `pickup_by` is still populated, a self-contradictory combination rather than a simple "untouched" field.
- **Previous behavior:** An inconsistent partial revert — a removed/undone delivery after a prior return leaves `pickup_status='Completed'`/`pickup_by` set alongside a freshly-reset `is_returned=false`.
- **Resolution:** Extended `$orderProductData` with the pickup-side counterpart of every delivery-side field already reset — `pickup_by`, `pickup_notes`, `pickup_signature_media_id` (+ file deletion, mirroring the existing delivery-signature cleanup), `pickup_status` (→ `'Pending'`), `end_hours` — plus `damage_status` (return-only, no delivery counterpart, but unambiguously return-completion metadata). Fields with no already-reset delivery-side counterpart (`pickup_date`/`pickup_time`/`pickup_store_id`, fuel/billing fields, the separate driver-checklist subsystem's pickup fields) were deliberately left untouched, for consistency with the delivery side's own pre-existing (curated, not exhaustive) reset pattern — no product decision was needed since this mirrors an already-accepted precedent in the same file rather than introducing a new one. See `P3_11_BUG5_RETURN_STATE_RESET.md` for the full field-by-field mapping and reasoning.
- **Business impact:** A checklist removal can no longer leave an order product in a self-contradictory delivery/return state.
- **Technical impact:** Contained to `RemoveController` — two eager-loaded relations added, six fields added to the existing reset array, and a mirrored media-cleanup block.
- **Files changed:** `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/RemoveController.php`; `tests/Feature/CustomerChecklists/RemoveControllerCharacterizationTest.php` (BUG-5 test flipped and expanded, 2 new tests).
- **Estimated effort:** Small-Medium (2-3 days incl. tests) — matched actual effort.
- **Risk:** Low — verified via 69 passed / 245 assertions across the full CustomerChecklists/equipment-status regression surface, including a dedicated test proving the intentionally-preserved fields remain untouched.

---

**BUG-6 — `unique_id` populated from numeric `id`, and `id` returns 0, in `RentalReadyChecklistQuestions\ListResource`'s array branch** — **Medium**
- **Root cause (expanded during the P3-6 readiness review):** Two bugs in the same array branch (the order-scoped, decoded-`rental_ready_qa_json` path — the object/checklistMaster branch is unaffected):
  1. `'unique_id' => $this['id'] ?? ''` — populated from the record's numeric `id`, not the actual `unique_id` string, even though the source array already carries a correct `unique_id` key alongside it.
  2. `'id' => $this['main_id'] ?? 0` — reads a `main_id` key that **does not exist anywhere in the array** (confirmed by reading `SaveController.php`'s construction of the underlying `rental_ready_qa_json` payload — it only ever writes `id` and `unique_id`, never `main_id`). As a result, **`id` is always `0` for every array-shaped question in production today**, not just a latent risk.
- **Current behavior:** Any mobile client expecting a stable string identifier in `unique_id` receives a numeric value instead; any client reading `id` always receives `0` regardless of the real question.
- **Desired behavior:** `unique_id` reflects the model's actual `unique_id` column; `id` reflects the model's actual numeric `id` (reading the array's existing `id` key, not the nonexistent `main_id`).
- **Business impact:** Low today (latent for `unique_id`, and `id`'s constant-`0` bug has had no reported impact since nothing meaningful can depend on an always-zero value) — but breaks silently the moment any client starts trusting either field.
- **Technical impact:** Two one-line resource fixes in the same branch, **both must be addressed together** — fixing only `unique_id` while leaving `id` reading `main_id` would still return a broken `id`, and fixing only `id` without also fixing `unique_id` would leave the original, higher-risk part of this bug in place. Both changes belong in the same PR/decision, not sequenced separately.
- **Dependencies:** **Requires mobile-team coordination before deploy** — confirm no mobile client build currently parses `unique_id` as numeric. The `id`-always-`0` fix is lower risk (nothing can meaningfully depend on a constant), but ship it in the same decision, not silently ahead of it.
- **Recommended solution:** Fix both resource lines; coordinate a mobile-app version check before shipping `unique_id`'s correction; consider a brief additive-field transition period if any client dependency on the numeric `unique_id` value is found. See **D9** in §8.
- **Estimated effort:** Small (1 day code — both lines are in the same file — but coordination overhead for `unique_id`).
- **Risk:** Medium — the risk is entirely about *external* client assumptions on `unique_id`, not the codebase itself; the `id`/`main_id` fix carries no meaningful external risk on its own.

---

**BUG-7 — Two different "delivered" concepts coexist with no cross-validation** — **Low**
- **Root cause:** `OrderProduct.is_delivered` (checklist-completion semantics) and `OrderProduct.delivery_is_delivered` (driver-arrival telemetry, set by a status-enum transition in `DriverChecklistController`) are independent fields with different gating and no reconciliation.
- **Current behavior:** The two can disagree with nothing to catch it.
- **Business impact:** Low, mostly a reporting-clarity issue.
- **Recommended solution:** Document the intended distinction clearly (if both are meant to exist), or add a reconciliation check; low urgency.
- **Estimated effort:** Small (investigation + doc, 1 day).
- **Risk:** Low.

---

**BUG-8 — Stale/deleted question or answer reference causes a hard 422 rejecting the entire batch** — **Low**
- **Root cause:** Unconfirmed exact mechanism — flagged in the original audit as an "unknown requiring runtime validation," never actually run.
- **Current behavior:** Unknown/unverified.
- **Recommended solution:** Run the deferred runtime scenario (submit a checklist referencing a soft-deleted question/answer) in a controlled staging test; decide on graceful partial-accept vs. current all-or-nothing rejection based on actual behavior observed.
- **Estimated effort:** Small (investigation, 1 day) + Medium if a fix is warranted.
- **Risk:** Unknown until investigated — treat as investigation-first work.

---

**BUG-9 — `delivery_checklist_status`/`pickup_checklist_status` orphaned fillable columns** — **Low**
- **Root cause:** Fillable on `OrderProduct` but zero other read/write site anywhere in the codebase.
- **Current behavior:** Likely vestigial.
- **Recommended solution:** Confirm truly unused (grep + git blame), then either wire them up to a real purpose or remove from `$fillable` with a note (no migration needed either way unless dropping the columns).
- **Estimated effort:** Small (investigation, half a day).
- **Risk:** Low.

---

**BUG-10 — Unconfirmed production reachability of `Admin\Tests\IndexController`** — **Low / Security-adjacent**
- **Root cause:** An oddly-namespaced controller (not under `ChecklistManagement`/`OrderManagement`) that deep-eager-loads the entire checklist relation graph — never confirmed whether it's reachable outside local/dev environments.
- **Recommended solution:** Confirm route registration/middleware gating; remove or properly gate behind an environment check if it's a leftover debug route.
- **Estimated effort:** Small (half a day).
- **Risk:** Low-Medium if it turns out to be reachable in production (information-disclosure risk) — verify promptly. See also **SEC-3**.

---

**BUG-11 — `IndexController.php:60` null-pointer when equipment has an order product but no rental-ready template** — **Medium**
- **Root cause:** `Call to a member function filter() on null` — reachable when `$equipment->orderProduct` exists but `equipmentRentalReadyTemplate` is null. Discovered during PR-B3's test-writing (had to seed a real template/question just to route around it in test fixtures), explicitly flagged then as "file as a separate Track C bug," but never actually tracked as its own item until now.
- **Current behavior:** A confirmed, reachable, pre-existing null-pointer error under a realistic data shape (equipment assigned to an order but never given a rental-ready template).
- **Desired behavior:** Graceful handling (empty result or explicit "no template" response) instead of a fatal error.
- **Business impact:** Admin-facing 500 error under a plausible real-world data state.
- **Technical impact:** Small, contained null-guard fix.
- **Dependencies:** None.
- **Recommended solution:** Add a null-safe guard; add the regression test that PR-B3 worked around instead of writing.
- **Estimated effort:** Small (1 day incl. test).
- **Risk:** Low.

---

**BUG-12 — No validation that delivered equipment belongs to the order product's category** — **Medium**
- **Root cause:** `CHECKLIST_SYSTEM_AUDIT.md` §6 (audit item 24c) identified that delivery-time assignment never cross-checks the equipment being delivered against the equipment category the order product was actually booked under — no `FormRequest` rule, no controller-level check, no DB constraint enforces this relationship anywhere in `SaveDeliveryController` or the admin equipment-assignment controllers.
- **Business impact:** A customer could receive equipment from an entirely different category than what they booked and paid for (e.g., booked a mini-excavator, delivered a skid steer) with no system-level check to catch the mismatch before or during delivery — a real customer-experience and billing-accuracy risk, not merely cosmetic.
- **Current behavior:** Any equipment record can be attached to any order product's delivery, regardless of category alignment.
- **Desired behavior:** Delivery assignment (mobile and/or admin-side) validates that the equipment's category matches the order product's booked category before allowing the delivery to proceed, or at minimum warns loudly if it doesn't.
- **Technical impact:** Touches `SaveDeliveryController` and any admin equipment-assignment controller that can set `current_order_product_id`; a moderate, contained validation addition, not a schema change.
- **Recommended implementation approach:** Follow this project's established observe-first discipline — add a structured warning log (mirroring PR-A4's pattern) when a mismatch is detected, without blocking the delivery, for an initial review window; only move to hard rejection once real-world mismatch frequency is known. This avoids blocking a legitimate override workflow that may exist today but isn't documented anywhere in the reviewed audit material.
- **Dependencies:** Same investigation/code area as **BUG-2**/**BUG-4** (delivery-time controllers) — sequence together in the same pass for efficiency, though independently shippable. Also relevant context for **ARCH-1**'s eventual feasibility study, since category-matching logic would need to be designed once, not per-tree, if the Rental Ready/Customer Admin trees are ever unified.
- **Estimated effort:** Small-Medium (2-3 days including regression tests, since delivery-controller characterization tests are already being written for BUG-2/BUG-4/BUG-5 — see PR order item 8).
- **Priority:** Medium — real business risk, but no confirmed live incident found in any of the reviewed audit/runtime-validation documents; treat as "close the gap," not "emergency fix."

---

**BUG-13 — Rental Ready `IndexController`/`SaveController` fallback inconsistency for "order product, no template yet"** — **Medium**
- **Root cause:** Surfaced during the independent review of **BUG-11**'s fix (P3-1). `RentalReadyChecklists\IndexController` now safely returns a `404 no_questions_found` when equipment has an order product but that order product's `EquipmentRentalReadyTemplate` is null (no inspection recorded yet). But the sibling write path, `Orders\RentalReadyChecklists\SaveController`, already treats this exact state as normal: when `equipmentRentalReadyTemplate` is not set, it falls back to `checklistMaster->rentalReadyTemplate->templateQuestions` and creates a fresh `EquipmentRentalReadyTemplate` from those questions. The read path (Index) and the write path (Save) disagree on what "no template yet" means for the same equipment state.
- **Current behavior:** For equipment on an order with no prior order-scoped inspection, `GET`-style listing (Index) reports "no questions found," while the corresponding `POST` save (Save) would succeed immediately using checklistMaster's template as a fallback.
- **Desired behavior:** Index and Save agree on the same fallback convention for this state — either both use checklistMaster's template as a fallback, or Save is changed to match Index's stricter behavior. Which direction is correct is a product/engineering decision, not a null-guard.
- **Business impact:** An admin screen driven by the Index endpoint could show "no checklist found" for equipment that could actually be inspected right now via Save — a confusing, avoidable UX gap, not a crash or data-integrity issue.
- **Technical impact:** `RentalReadyChecklistQuestions\ListResource` already handles both the raw-model shape (checklistMaster path) and the decoded-JSON shape (order-product path), so implementing a fallback in Index is structurally straightforward once the direction is decided.
- **Dependencies:** Builds on **BUG-11** (P3-1); do not begin until a decision is recorded — see the Decision Log entry to be added in §8.
- **Recommended solution:** Not yet decided — see dependency note above. **Do not implement BUG-13 now.**
- **Estimated effort:** Small (1-2 days, once a direction is decided).
- **Risk:** Low — read-only listing behavior change, no schema or write-path risk either way.

---

**BUG-15 — Checklist Master edit Step 3 contains contradictory JavaScript gating** — **Medium** — ✅ **RESOLVED (2026-07-15)**
- **Root cause:** Surfaced during the independent review of **CLEAN-9** (P3-4). `checklist_master/edit.blade.php` has an earlier `DOMContentLoaded` script block that sets `continueStep3Btn.disabled = true` precisely when `customer_admin_template_id` is already present and restored (the opposite of the analogous Step 2 line right above it, `continueStep2Btn.disabled = false`, which looks like a copy-paste boolean-flip bug). CLEAN-9's fix added a second, later `DOMContentLoaded` block that correctly re-enables the button whenever a template is assigned, which currently overrides the earlier block's incorrect disable.
- **Previous behavior:** The button ended up in the correct (enabled) state only because browsers fire multiple `DOMContentLoaded` listeners in registration order, and the earlier (incorrect) block happened to run before the later (correct) one. This was not a designed guarantee — it was an accident of script ordering.
- **Resolution:** Consolidated both script blocks' Step-3-button logic into a single named function, `setStep3ContinueButtonState()`, called from both the initial-restore block and the radio-change handler. The earlier block's contradictory `continueStep3Btn.disabled = true` line was removed entirely (that block now only restores the selected radio and summary text, nothing about button state). No ordering dependency exists anymore — the consolidated function is the only code that ever touches `continue3Btn.disabled`. See `P3_BUG15_STEP3_GATING.md` for full before/after detail.
- **Business impact:** None currently live — the net visible behavior was already correct for every real record; this closed the latent risk of a future script reorder silently reintroducing a genuinely disabled Continue button.
- **Technical impact:** Contained to one Blade file (`edit.blade.php`); no server-side code involved.
- **Dependencies:** None — pre-existing, **not caused by P3-4**.
- **Files changed:** `resources/views/admin/checklist_management/checklist_master/edit.blade.php`; `tests/Feature/ChecklistManagement/ChecklistMasterEditStep3ValidationTest.php` (extended, 3 new tests).
- **Estimated effort:** Small (half a day, contained to one file) — matched actual effort.
- **Risk:** Low — a JS-only consolidation, no behavior change for any currently-valid record; confirmed by 5 passing render-level tests. See `P3_BUG15_STEP3_GATING.md` for the one honest limitation (no browser/JS test runner in this project, so the actual click-blocking behavior is verified by DOM-semantics reasoning and render-level assertions, not an executed browser test).

---

### 2.2 Technical debt

---

**TD-1 — Three independent write paths for `is_delivered`/`is_returned`** — **Medium**
- **Root cause:** Customer-checklist Save controllers, driver-checklist/mobile-input controllers, and admin schedule-editing controllers (`UpdateProductScheduleController`, `AssignEquipmentController`, `RemoveEquipmentController`) can each flip delivered/returned state independently. PR-B1 consolidated only `equipment.checklist_master_id` assignment — this broader delivered/returned write-path fragmentation was never addressed.
- **Desired behavior:** A single service akin to `ChecklistAssignmentService` owning all delivered/returned-state writes.
- **Business impact:** Ongoing risk of the exact class of bug PR-B1 fixed for checklist-master assignment, but for delivery/return state instead.
- **Dependencies:** Should follow the Wave 3 bug fixes (BUG-2, BUG-4, BUG-5) since they touch the same surface; see **ARCH-2**.
- **Estimated effort:** Large (this is effectively "PR-B1 but for delivered/returned state") — 5-8 days including baseline characterization tests (none exist today).
- **Risk:** Medium — highest-blast-radius item short of ARCH-1, touches multiple controllers across admin and mobile surfaces.

---

**TD-2 — No template versioning; `is_damaged` read live instead of snapshotted** — **Medium**
- **Root cause:** Every other part of the customer-checklist flow correctly denormalizes/snapshots master content at delivery time; `CustomerAdminQuestionAnswer.is_damaged` is the one confirmed exception, read live at return time.
- **Current behavior:** An admin's mid-rental edit to a master answer's damage flag can change return-time billing outcomes for an in-progress rental.
- **Desired behavior:** Snapshot `is_damaged` at delivery time like everything else.
- **Business impact:** Billing correctness risk for edge-case mid-rental master edits.
- **Dependencies:** None blocking; touches the same conceptual area as ARCH-1 (if the trees are ever unified, this should be designed in from the start).
- **Estimated effort:** Medium (3-4 days).
- **Risk:** Low-Medium, financial-adjacent.

---

**TD-3 — Fragile `hasOneThrough` relation hard-wired to raw column names** — **Low**
- **Location:** `Equipment.php:277-282`, `customerAdminTemplates()`.
- **Root cause:** Chains `Equipment → ChecklistMaster → CustomerAdminTemplate` using raw column names instead of composing from the existing `checklistMaster()` relation.
- **Recommended solution:** Rewrite to compose from existing relations so a column rename fails at code-review time, not silently at query time.
- **Estimated effort:** Small (half a day).
- **Risk:** Low.

---

**TD-4 — `dispatch_checklist` naming collision** — **Low**
- **Root cause:** A completely separate "checklist" concept (driver pre-delivery SOP, `order_products.dispatch_checklist` JSON) shares the English word "checklist" with zero relation to `ChecklistMaster`/templates.
- **Recommended solution:** Rename or clearly document the distinction to prevent maintainer confusion (a real risk when grepping "checklist").
- **Estimated effort:** Small (naming/doc only, unless renaming the column — then Medium with a migration).
- **Risk:** Low.

---

**TD-5 — Cron jobs and AI dispatch-drafting implicitly checklist-dependent** — **Low**
- **Root cause:** Reminder cron jobs and `DispatchContextBuilder` filter on `delivery_status`/`pickup_status`, fields mutated by checklist Save controllers, with no explicit acknowledgment of this coupling.
- **Recommended solution:** Document the coupling explicitly in both places; no behavior change needed unless TD-1/ARCH-2 changes how these fields are written.
- **Estimated effort:** Small (documentation, half a day).
- **Risk:** Low.

---

**TD-6 — `OrderProductObserver` auto-assign coupling** — **Low**
- **Root cause:** Auto-assign-on-schedule logic gated by `delivery_status`/`pickup_status !== 'Reschedule'` — a non-obvious coupling to checklist-mutated fields.
- **Recommended solution:** Document; revisit if TD-1/ARCH-2 changes these fields' write path.
- **Estimated effort:** Small.
- **Risk:** Low.

---

**TD-7 — `BillingChargeCreatedEvent` has zero registered listeners** — **Low**
- **Root cause:** Dispatched inside `BillingEngine::charge()` with no listener anywhere.
- **Recommended solution:** Either remove the dead dispatch or use it for the observability/notification hooks this project has built elsewhere (e.g., a future finance-facing audit trail).
- **Estimated effort:** Small.
- **Risk:** Low.

---

**TD-8 — Checklist listeners' synchronous nature is a silent, undocumented load-bearing assumption** — **Medium**
- **Root cause:** PR-A2's entire transaction-rollback guarantee depends on `OrderCustomerChecklistListener`/`OrderProductDriverChecklistUpdatedListener` never becoming `ShouldQueue`. Confirmed true today, but nothing prevents a well-intentioned future performance change from silently breaking this.
- **Recommended solution:** Add an explicit code comment on both listener classes warning against this; consider a lightweight automated check (e.g., a test asserting neither implements `ShouldQueue`) as a tripwire.
- **Estimated effort:** Small (comment + tripwire test, 1 day).
- **Risk:** Low to add the safeguard; the risk being guarded against is Medium-High if ever silently violated.

---

**TD-9 — No domain event for equipment status changes** — **Low**
- **Root cause:** `EquipmentStatusService` mutates status via direct method calls with logging, but no actual `Event` class is dispatched — anything wanting to react to a status change must hook into the service directly.
- **Recommended solution:** Consider a lightweight `EquipmentStatusChanged` event for extensibility, only if a real consumer need emerges; not urgent on its own.
- **Estimated effort:** Small-Medium.
- **Risk:** Low.

---

**TD-10 — Misleading `{unique_id}` route-parameter name on Question Update/Copy routes** — **Low**
- **Root cause:** (Confirmed during PR-B4.2) Both trees declare a `{unique_id}` route parameter but Blade forms pass the numeric `id`; controllers use `findOrFail($id)`, not a `unique_id` lookup. Functionally harmless but a footgun — a future dev could "fix" it by switching to a real `unique_id` lookup and break the app.
- **Recommended solution:** Rename the route parameter to `{id}` to match actual behavior (cosmetic, no functional change) — small, isolated PR.
- **Estimated effort:** Small (1 day, touches 2 route files + confirms no other consumer assumes the current name).
- **Risk:** Low.

---

**TD-11 — Dead `'required'` field silently dropped on TemplateQuestion updates** — **Low**
- **Root cause:** (Confirmed during PR-B4.3) Both trees' Update controllers submit a `'required'` key when rebuilding template-question rows, but neither `TemplateQuestion` model has it in `$fillable`, and neither table has such a column — the value is silently dropped every time.
- **Recommended solution:** Either add the column/fillable entry and wire it up for real (a genuine feature), or remove the dead field from the request payload/controller code. Needs a product decision on whether "required" at the template-question level is a real, wanted feature.
- **Estimated effort:** Small (cleanup) or Medium (if implementing for real, needs a migration).
- **Risk:** Low either way.

---

**TD-12 — Customer Admin Template Copy's `unique_id` prefix quirk** — **Low**
- **Root cause:** (Confirmed during PR-B4.3) `CopyController` regenerates a copied Customer Admin template's `unique_id` with prefix `'TQS'` (Rental Ready's own creation-time prefix) instead of `CustomerAdminTemplate`'s actual creation-time prefix `'CATQS'` — a pre-existing inconsistency, deliberately preserved (not fixed) during the PR-B4.3 refactor per its explicit scope.
- **Recommended solution:** Small, isolated one-line fix once a decision is made on whether the `'TQS'`-prefixed IDs already in production need any special handling (unlikely, since prefixes are cosmetic/informational only).
- **Estimated effort:** Small (half a day).
- **Risk:** Low.

---

**TD-13 — Pre-existing Mockery test flake** — **Low**
- **Root cause:** `MobileReturnFuelBridgeTest > billing engine failure is logged to billing engine channel` fails intermittently with a call-count mismatch (`should be called exactly 1 times but called 2 times`), reproduced independently across multiple PR review sessions, confirmed unrelated to any of this project's changes (reproduces even against `git stash`-reverted code).
- **Recommended solution:** Investigate and fix the flaky mock expectation (likely a duplicate log call or an assertion needing `atLeast()`/relaxed count); currently has no assigned owner across any of the reviewed documents.
- **Estimated effort:** Small (1-2 days investigation + fix).
- **Risk:** Low, but erodes trust in CI green/red signal until fixed.

---

**TD-14 — No atomic `saveQuietly()` + audit-log helper** — **Low**
- **Root cause:** (From PR-A1 review) The current `EquipmentStatusLog::recordTransition()` pattern still requires every caller to remember to invoke it after `saveQuietly()` — nothing prevents a 9th call site being added without logging, the same class of gap PR-A1 had to fix reactively.
- **Recommended solution:** Add a shared `Equipment::transitionStatusQuietly($to, $actorId)` model method/trait bundling both operations atomically.
- **Estimated effort:** Small-Medium (2 days, touches all current call sites to migrate them).
- **Risk:** Low.

---

**TD-15 — Duplicated `$old`/`$oldRaw` derivation across all 7 `EquipmentStatusService` methods** — **Low**
- **Recommended solution:** Derive `$old = $oldRaw ?? 'unknown'` from one shared capture. Purely cosmetic simplification.
- **Estimated effort:** Trivial (half a day).
- **Risk:** None.

---

**TD-16 — Audit and replace `Schema::drop()`-inside-transaction failure simulations across BillingEngine tests** — **Medium** — ✅ **RESOLVED (2026-07-16)**
- **Root cause:** Surfaced during the independent review of **TD-13** (P3-5). 7 test files — `MobileReturnFuelBridgeTest`, `RentalExtensionBridgeTest`, `FuelChargeBridgeTest`, `DashboardDamageChargeBridgeTest`, `FuelAlertChargeBridgeTest`, `CrmDamageChargeBridgeTest`, `CrmFuelChargeBridgeTest`, `DamageAlertChargeBridgeTest` — simulated a billing-charge database failure by calling `Schema::drop('billing_charges')` in the middle of a test, while already inside nested `DB::transaction()` calls (the outer test-framework transaction, the controller's own transaction, and `BillingEngine::charge()`'s internal transaction). MySQL DDL statements (including `DROP TABLE`) always issue an implicit `COMMIT`, which silently desynchronized Laravel's PHP-side transaction-nesting/savepoint counter from the database's real state. This was independently corroborated by an explicit comment already present in `tests/Feature/CustomerChecklists/ChecklistTransactionTest.php`, written by whoever discovered the same mechanism while building that test and deliberately avoided the pattern there.
- **Resolution:** Replaced all 14 `Schema::drop()` call sites (2 per file × 7 files) with a deterministic, self-disarming Eloquent `BillingCharge::creating()` listener that throws once, forcing `BillingEngine::charge()`'s insert to fail without any DDL. **A separate, genuine pre-existing bug was found and fixed along the way** (not caused by this change, uncovered only once the DDL-corruption noise was removed): `MobileReturnFuelBridgeTest`'s `test_billing_engine_failure_is_logged_to_billing_engine_channel` legitimately exercises 3 different `Log::channel()` calls in one request (`billing_engine`, `equipment_status`, `api_errors`) but its Mockery setup only stubbed one — the other two caused Mockery itself to throw, and that throw was in turn logged via `Log::error()`, inflating the expected 1 call to 2. Fixed by broadening the channel stub to accept any channel name (the test's real assertion, `error()->once()`, was left exactly as strict as before — not weakened). See `P3_TD16_TRANSACTION_SAFE_FAILURE_TESTS.md` for full detail.
- **Business impact:** None — this was a test-suite reliability issue only; no production code or behavior was involved.
- **Technical impact:** Contained to the 8 test files; **zero application code changed**.
- **Dependencies:** None.
- **Files changed:** all 8 files listed above (`Schema::drop()` calls replaced, unused `Schema` import removed, a `forceBillingChargeCreationFailure()` helper added to each) plus `docs/checklist-system-audit/P3_TD16_TRANSACTION_SAFE_FAILURE_TESTS.md` (new).
- **One out-of-scope, related finding documented, not fixed:** `tests/Feature/WaitList/EquipmentWaitListTest.php` uses `DB::statement('DROP TABLE equipment_wait_list_alerts')` in `test_matcher_errors_do_not_break_returns`. Classified as **unrelated to this issue** — its underlying code path (`EquipmentStatusService::markReturnedToMaintenance()`) contains no nested `DB::transaction()` call, so the savepoint-corruption mechanism this item addresses does not apply. Left unchanged as explicitly outside TD-16's BillingEngine/checklist scope; noted here for visibility, not folded in.
- **Estimated effort:** Medium (1-2 days) — matched actual effort.
- **Risk:** Low — test-only change confirmed by re-running the full `BillingEngine` suite (206 passed), `ChecklistTransactionTest` (8 passed), and broader `CustomerChecklists`/`WaitList`/`OrderManagement` regression (259 passed total) — no production behavior affected either way.

---

### 2.3 Architecture improvements

---

**ARCH-1 — Full schema/model unification of Rental Ready and Customer Admin trees** — **Medium-High (candidate, not committed)**
- **Root cause:** Two fully independent Eloquent model/table trees (Category→Question→Answer→Template→TemplateQuestion) share an identical logical shape but zero code/schema sharing — "any future fix must be applied twice," confirmed true across all three of PR-B4.1/B4.2/B4.3's investigations (soft-delete asymmetry, FK asymmetry, dead fields, copy-prefix quirks — all symptoms of the same root fork).
- **Current behavior:** Two parallel, drifting implementations; D5 (Phase 2) explicitly confirmed this would NOT be touched at the schema level in Phase 2 — only logic-sharing (the `*CrudService` classes) was done.
- **Desired behavior:** A single, type-discriminated schema (e.g., one `checklist_categories` table with a `tree` enum column) if the business truly needs these to behave identically going forward — or an explicit, ratified decision that they should remain separate because they're allowed to diverge (see **ARCH-3**).
- **Business impact:** High potential payoff (eliminates an entire class of "fixed in one tree, forgot the other" bugs — already the *cause* of most of the Bugs/Technical Debt items in this document) but also the highest-risk, highest-effort item in the entire backlog — a live schema migration across two currently-independent trees with real production data in both.
- **Technical impact:** Full migration plan, dual-write/backward-compatible transition period, extensive regression testing (the existing PR-B4.1/4.2/4.3 characterization suites — 66 tests total — become the acceptance-test baseline for this work).
- **Dependencies:** Requires its own dedicated audit (mirroring the rigor of the original `CHECKLIST_SYSTEM_AUDIT.md`) and explicit stakeholder go/no-go **before any implementation planning**, per D5's own recommendation. Should not begin until Waves 1-4 below are stable, since this work would touch every controller already refactored in PR-B4.1-4.3.
- **Recommended solution:** Commission a standalone "Rental Ready / Customer Admin Unification Feasibility Study" as the first deliverable — not a code PR — covering: exact target schema, migration/backfill strategy, dual-write transition window, rollback plan, and a hard estimate. Only after that study is reviewed should implementation be scheduled.
- **Estimated effort:** Large — the feasibility study itself is 1-2 weeks; implementation (if approved) is likely 4-8+ weeks across several PRs, not a single one.
- **Risk:** High if rushed; this is explicitly the one item in this backlog that should NOT be squeezed into a normal sprint.

---

**ARCH-2 — Consolidate delivered/returned write paths** — **Medium**
- See **TD-1** for full detail — this is TD-1's proposed structural fix, listed separately here since it's genuinely an architecture-level change (a new service), not merely "debt."
- **Recommended solution:** A `DeliveryReturnStatusService` (naming to match this project's established `*CrudService`/`ChecklistAssignmentService` convention) as the single write path for `is_delivered`/`is_returned` and related fields, following the exact "characterization tests first, extract only identical mechanics, preserve all domain-specific behavior" discipline used throughout PR-B4.1-4.3.
- **Estimated effort:** Large (5-8 days).
- **Dependencies:** Sequence after Wave 3's bug fixes (BUG-2, BUG-4, BUG-5), which touch the same controllers and should land first as focused, independently-reviewable fixes rather than being bundled into a larger refactor.

---

**ARCH-3 — Ratify (or reject) standardizing soft-delete behavior across Customer Admin to match Rental Ready** — **Low-Medium**
- **Root cause:** Three separate PR-B4.x readiness reviews all found and preserved (not fixed) the same asymmetry: Rental Ready soft-deletes everywhere; Customer Admin hard-deletes-with-cascade everywhere. Each PR flagged this to product/ops as "its own separate decision" — but no formal decision has actually been ratified anywhere in the reviewed documents.
- **Recommended solution:** A short, explicit decision (mirroring the `PHASE2_DECISION_MATRIX.md` D1-D5 format) — either "standardize Customer Admin to soft-delete" (a migration + model change) or "confirm this asymmetry is permanent and acceptable" (a documentation-only closure, like D5). This decision should precede or accompany ARCH-1, since a unified schema would need one soft-delete policy anyway.
- **Estimated effort:** Decision itself: trivial. If "standardize" is chosen: Medium (migration + `SoftDeletes` trait additions + full regression suite re-run across Category/Question/Answer/Template/TemplateQuestion).
- **Risk:** Low for the decision; Medium for implementation if standardization is chosen, given the DB-level cascade behavior actively relied upon today would change (hard deletes currently destroy children immediately; soft-deleting instead means children survive until a separate purge).

---

**ARCH-4 — Queue checklist listeners (remove the synchronous-only constraint)** — **Low**
- **Root cause:** See **TD-8** — the listeners are synchronous today, and PR-A2's transaction guarantee depends on that remaining true.
- **Recommended solution:** If performance ever demands it, introduce `ShouldQueue` with an explicit transactional-outbox or after-commit-dispatch pattern so queuing doesn't silently defeat PR-A2's rollback guarantee. Not urgent — only pursue if a real performance need emerges (see **PERF-1**).
- **Estimated effort:** Medium (3-5 days, plus re-verifying every PR-A2 regression test under the new async model).
- **Risk:** Medium — must not regress the rollback guarantee; treat as its own dedicated PR with its own full regression pass, not a quick change.

---

### 2.4 Performance

---

**PERF-1 — Checklist listeners are 100% synchronous end-to-end** — **Low**
- **Root cause:** No job in the checklist system is ever queued (`QUEUE_CONNECTION=database` is configured elsewhere in the app but unused here).
- **Current behavior:** Every mobile save is fully blocking on DB + listener execution (SMS/email side effects, wait-list evaluation, etc. all happen inline).
- **Business impact:** No confirmed complaint or measured latency problem today — this is a forward-looking observation, not an active issue.
- **Recommended solution:** Only pursue via **ARCH-4** if real latency data justifies it. Do not queue speculatively.
- **Estimated effort:** N/A until justified.
- **Risk:** N/A.

---

**PERF-2 — `RemoveController`'s per-order-product loop** — **Low**
- **Root cause:** Calls `markAvailableOnChecklistRemove()` individually inside a `foreach`, one DB insert per iteration.
- **Current behavior:** Minor N+1-shaped cost; already flagged and explicitly accepted as "no action needed now" during PR-A1's review.
- **Recommended solution:** Revisit only if order-product counts per removal grow substantially; not worth batching preemptively.
- **Estimated effort:** N/A.
- **Risk:** None at current scale.

---

### 2.5 Security

---

**SEC-1 — Client-writable, unvalidated status fields** — **High**
- **Root cause:** `tnc_status`, `drivers_license_status`, `video_status`, `checklist_status` on `order_products` accept arbitrary client-supplied strings with no `Rule::in()` constraint.
- **Current behavior:** Currently write-only (nothing reads them yet), so no live impact today — but "the moment a dashboard or gating rule starts trusting them, this becomes a real data-integrity and business-logic risk," per the original audit's own words, and it only gets harder to tighten the longer it's left permissive.
- **Desired behavior:** `Rule::in()` enum constraints on all four fields.
- **Business impact:** Currently low (latent); becomes real the moment anything reads these fields.
- **Technical impact:** Small, isolated `FormRequest` change.
- **Dependencies:** None — safe to do now specifically because nothing depends on the current permissiveness.
- **Recommended solution:** Add the enum constraints immediately, before any feature starts reading these fields (do this before, not after, any dashboard/reporting work that would consume them).
- **Estimated effort:** Small (1 day).
- **Risk:** Low — this is the cheapest possible fix for a finding rated High mainly because of its "the window to fix this cheaply is closing" character, not its current live severity.
- **Severity clarification note:** The High severity reflects the increasing cost of delaying the fix before downstream consumers begin depending on the affected behavior, rather than the current production impact. Priority is unchanged from the original plan — this note only clarifies the rationale, since the original wording could read as internally inconsistent (High severity paired with "currently low" impact).

---

**SEC-2 — No enforcement of completeness status against actual signature/required-question data** — **Medium**
- Cross-references **MOB-1**/**MOB-2** — this is the enforcement half of the PR-A4 observability work, still gated on the telemetry window (**G2**).
- **Business impact:** Compliance/legal exposure (deliveries/returns marked complete without signature or required answers).
- **Recommended solution:** See MOB-1.
- **Estimated effort:** See MOB-1.
- **Risk:** See MOB-1.

---

**SEC-3 — Confirm `Admin\Tests\IndexController` is not reachable in production** — **Medium**
- Cross-references **BUG-10** — listed again here because of its information-disclosure angle (deep-eager-loads the full checklist relation graph).
- **Recommended solution:** Verify route/middleware gating immediately; this is a quick check, not a project.
- **Estimated effort:** Small (half a day).
- **Risk:** Unknown until verified — treat as urgent-to-check even though likely low-severity.

---

### 2.6 Mobile

---

**MOB-1 — PR-A6: completeness enforcement decision and implementation** — **Medium-High**
- **Root cause:** PR-A4 shipped observability-only logging for delivery/return completeness (missing signature, unanswered required questions); real enforcement was deliberately deferred until real production telemetry could shape the decision.
- **Current behavior:** Still logging-only; no enforcement exists.
- **Desired behavior:** A decision (informed by the telemetry) on whether to hard-reject incomplete submissions, introduce a new "Incomplete"/"Partial" status, or leave as observability permanently.
- **Business impact:** Compliance/legal exposure if truly incomplete deliveries continue to be treated as fully complete indefinitely.
- **Dependencies:** **Blocked on G2** — confirm the 2-4 week PR-A4 production telemetry window has actually elapsed before scoping this. If PR-A4 was deployed to production shortly after Phase 1 closed, this window may already be satisfied — verify deployment date first.
- **Recommended solution:** Pull the accumulated `api_errors` warning logs from PR-A4, quantify real-world frequency and severity, then make the same kind of staged, product-approved decision this project used for D2/D3/D4 (observe-first pattern, then a explicit go/no-go on enforcement).
- **Estimated effort:** Medium (analysis: 2-3 days; implementation once shape is decided: 3-5 days).
- **Risk:** Medium — could reject real in-flight mobile traffic if not staged carefully; use the same feature-flag/staged-rollout discipline as D2's admin-web observability stage.

---

**MOB-2 — The 44-row (5%) genuine mobile-checklist-omission gap** — **Medium**
- **Root cause:** From `ISSUE5_INVESTIGATION_FINDINGS.md` — 44 of 875 delivered-with-zero-checklist-rows order products are a genuine, unexplained mobile-side gap (checklist array omitted entirely, real mobile flow signature, equipment properly configured).
- **Dependencies:** Same telemetry gate as MOB-1 (**G2**) — deliberately bundled, since PR-A4's logging will have been quantifying this exact scenario's ongoing rate.
- **Recommended solution:** Address alongside MOB-1's implementation, once real frequency data is available.
- **Estimated effort:** Small-Medium (likely folds into MOB-1's implementation effort).
- **Risk:** Low-Medium.

---

**MOB-3 — No general offline/mobile-sync idempotency mechanism** — **Low**
- **Root cause:** No client UUID or timestamp exists in the checklist submission path generally (beyond the billing-specific PR-A3 fix); delivery is destructive delete-then-recreate on any retry.
- **Recommended solution:** Consider a client-supplied idempotency token on delivery/return submissions, following the same pattern PR-A3 established for billing. Lower urgency than MOB-1/MOB-2 since no confirmed incident exists yet.
- **Estimated effort:** Medium (3-5 days).
- **Risk:** Low.

---

**MOB-4 — Mobile app's offline-queue/replay/dedup behavior is unknown** — **Low**
- **Root cause:** Entirely client-side, invisible from this backend's codebase.
- **Recommended solution:** Requires mobile-team involvement to document actual client behavior; not something backend engineering can resolve alone.
- **Estimated effort:** N/A (cross-team investigation).
- **Risk:** Unknown.

---

**MOB-5 — Upload size/timeout limits for large video uploads unverified** — **Low**
- **Root cause:** Infra-level (php.ini/web-server config) unknown, not investigated as part of any prior phase.
- **Recommended solution:** Confirm current limits against realistic mobile upload sizes over throttled connections; adjust if needed.
- **Estimated effort:** Small (half a day investigation).
- **Risk:** Low.

---

### 2.7 API

---

**API-1 — Standardize `{success,message}` vs. `{status,message}` response envelopes** — **Medium**
- **Root cause:** Most endpoints use `{success, message}`; `DriverChecklistController` and `UpdateDeliveryPickupInputsController` both independently use `{status, message}` with hardcoded strings instead of the shared `ApiResponseHelper`/translation pattern.
- **Current behavior:** Confirmed inconsistency on both controllers (the second one discovered only during Phase 3 runtime testing, not in the original audit).
- **Recommended solution:** Standardize both onto the shared helper/pattern; requires mobile-team confirmation that no client parses the `{status}` key specifically (a breaking-change risk if they do). See **D10** in §8.
- **Estimated effort:** Small-Medium (2-3 days incl. mobile coordination).
- **Risk:** Medium — coordinate with mobile before deploying, same caution as BUG-6.

---

**API-2 — Response-shape inconsistencies between Customer/Rental-Ready checklist question list resources** — **Low**
- **Root cause:** `required_question` defaults to `true` in the customer resource but `false` in the rental-ready resource (opposite fallback, same field name); the selected-answer field is a full nested object (`deliverAnswer`/`returnAnswer`) in the customer resource vs. a bare string-or-null (`selected_answer`) in the rental-ready resource — different field names *and* different shapes, not just a default-value difference.
- **Recommended solution:** Standardize both resources' shapes; requires mobile coordination since this changes response shape. See **D11** in §8 for whether to standardize now, version, or defer.
- **Estimated effort:** Small-Medium (2-3 days incl. coordination).
- **Risk:** Medium (client-facing shape change).

---

**API-3 — `unique_id`/`id` mismatch** — cross-reference **BUG-6**. No separate entry.

---

### 2.8 Database

---

**DB-1 — Add FK constraint to `customer_admin_templates.equipment_category_id`** — **Low**
- **Root cause:** Currently a plain string column with no FK, unlike its Rental Ready sibling (a real `foreignId` with `nullOnDelete()`, confirmed during PR-B4.3).
- **Recommended solution:** Add the FK — but first run the non-numeric-value audit query `PHASE3_RUNTIME_VALIDATION_SQL_PLAN.md` itself flagged as a prerequisite (a `CAST(...AS UNSIGNED)` comparison silently produces `0` for non-numeric values, which would need cleanup before a real FK could be added).
- **Estimated effort:** Small (1-2 days incl. data audit).
- **Risk:** Low, once the pre-check is done.

---

**DB-2 — Add composite unique constraint on template↔question join tables** — **Low**
- **Root cause:** Neither `rental_ready_checklist_template_questions` nor `customer_admin_template_questions` prevents duplicate question-in-template rows at the DB layer.
- **Current behavior:** Confirmed 0 existing duplicates in both tables (`PHASE3_RESULTS.md` V6) — safe to add now.
- **Recommended solution:** Add the constraint to both tables in one migration.
- **Estimated effort:** Small (1 day).
- **Risk:** Very low — confirmed safe by the V6 data check.

---

**DB-3 — Universal `nullOnDelete()`/set-null FK design leaves silent orphaning possible** — **Low**
- **Root cause:** Deliberate design choice across nearly every checklist FK — deleted parents silently null out children's FKs rather than blocking or cascading.
- **Current behavior:** No current occurrence found (`PHASE3_RESULTS.md` V4/V5: 0 orphaned `checklist_masters` rows), but the mechanism that could produce one is unverified as prevented.
- **Recommended solution:** No urgent action; monitor. Revisit if ARCH-1 changes the schema shape anyway.
- **Estimated effort:** N/A.
- **Risk:** Low, latent.

---

**DB-4 — Reassign `ChecklistMaster` #27 (real production category mismatch)** — **Medium (data task, not code)**
- **Root cause:** `ChecklistMaster` `CLM-5LNW-UPXG` (id 27, `equipment_category_id=23`) is paired with a Rental Ready template and a Customer Admin template both actually belonging to `equipment_category_id=8` — confirmed real, pre-existing data error (`PHASE3_RESULTS.md` V10).
- **Current behavior:** 9 real equipment units assigned to this mismatched master; **one unit is currently rented** with the wrong template.
- **Business impact:** An active rental may be using an incorrect inspection template right now.
- **Recommended solution:** Reassign/correct this specific `ChecklistMaster` record promptly — this is a data-correction task independent of any code PR, do it immediately rather than waiting for any sprint.
- **Estimated effort:** Trivial (an admin UI edit or a one-off script), but time-sensitive.
- **Risk:** Low technically; the risk is in *not* doing it promptly given the actively-rented unit.

---

**DB-5 — No reusable seed data/fixtures for the checklist domain** — **Medium** *(revised from Low — see note)*
- **Root cause:** No seeder ever created `checklist_masters`/Rental-Ready/Customer-Admin baseline data; every PR in this project's history (PR-A1 through PR-B4.3) built its own inline test fixtures from scratch.
- **Severity note (why Medium, not Low):** The specific *action* here (extract a shared factory/seeder set) is genuinely small effort — but `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` itself calls the underlying gap **"the single biggest risk in this codebase for this module"** and one of only two blockers to safe refactoring. Shared fixtures are a prerequisite for safe long-term maintenance and refactoring — every large item in this backlog (ARCH-2, ARCH-1) depends on being able to write tests quickly and correctly against realistic data. Rating this Low understated how load-bearing it actually is; the effort estimate is unchanged, only the severity label.
- **Recommended solution:** Extract a shared factory/seeder set from the now-substantial body of inline fixtures across all the characterization test suites, to speed up future test-writing (especially for **ARCH-2**/**ARCH-1**, which will need extensive new fixtures).
- **Estimated effort:** Medium (3-4 days to extract and generalize) — **unchanged**.
- **Risk:** Low — pure test-infrastructure investment, no production behavior change.

---

**DB-6 — Verify whether Rental Ready and Customer Admin category-name pairs have drifted apart in production** — **Low (investigation)**
- **Purpose:** `CHECKLIST_SYSTEM_AUDIT.md` §17 (unknown #7) flagged that categories created via PR-B4.1's one-time mirror checkbox (`create_customer_folder`/`create_rental_folder`) are never kept in sync after creation — a rename or edit in one tree never propagates to its mirrored counterpart. Whether this has actually happened in production data has never been checked; this is a live, answerable SQL question, not speculation.
- **Suggested SQL validation:** A read-only query joining `rental_ready_checklist_categories` and `customer_admin_categories` on their creation timestamps (categories mirrored via the checkbox should have near-identical `created_at` values, within a few seconds of each other) and comparing `category_name`/`description` for exact matches. Any pair with matching creation timestamps but differing names/descriptions is a confirmed drift case. (Mirrors the query style already used in `PHASE3_RUNTIME_VALIDATION_SQL_PLAN.md` for the equivalent V10 equipment-category-mismatch check.)
- **Expected outcomes:** Either (a) zero drifted pairs found — the mirror feature's one-time-sync design hasn't caused a practical problem yet, useful evidence for **ARCH-3**'s soft-delete-policy discussion and **ARCH-1**'s feasibility study; or (b) one or more drifted pairs found — real evidence that the two-tree design is already causing user-visible inconsistency, which would strengthen the case for prioritizing ARCH-1's feasibility study sooner rather than later.
- **Follow-up actions if drift exists:** (1) Manually reconcile the specific drifted pairs found (a data-correction task, same category as **DB-4** — do promptly, not schedule-dependent). (2) Feed the finding into **ARCH-1**'s feasibility study as concrete evidence of the current design's real-world cost. (3) Consider whether the one-time mirror checkbox should be upgraded to keep syncing on every edit (a scoped, much smaller fix than full schema unification) as an interim measure — a new, separate backlog item if this path is chosen.
- **Estimated effort:** Small (half a day — one read-only query plus manual review of results).
- **Risk:** None — purely a read-only investigation.

---

### 2.9 Documentation

---

**DOC-1 — `PHASE2_DECISION_MATRIX.md`'s decision log was never updated for D2-D5** — **Low (do immediately)**
- **Root cause:** The decision-log table (lines 144-150) only has D1 filled in. D2 (observability-first, per the user's explicit Stage-3 instruction during PR-B2), D3/D4 (resolved via PR-B3's git-archaeology — D3's guard staged as logging, D4's obsolete half permanently removed and its live half staged as logging), and D5 (implicitly confirmed by PR-B4.1/4.2/4.3 all proceeding as logic-sharing-only, no schema change) were all actually decided and implemented — just never recorded in this specific table.
- **Business impact:** None currently, but this file is misleading to anyone reading it in isolation — it looks like Phase 2 is still blocked on open decisions when it's actually fully shipped.
- **Recommended solution:** Fill in the four blank rows with decider/date/outcome, cross-referencing `PR-B2_RENTAL_READY_CALCULATOR.md`, `PR-B3_VALIDATION_GUARDS.md`, and `PR-B4_1_CATEGORY_REFACTOR.md` respectively.
- **Estimated effort:** Trivial (under an hour).
- **Risk:** None — pure documentation accuracy fix.

---

**DOC-2 — Communicate the `equipment_status_logs` historical gap to stakeholders** — cross-reference **G3**.

---

**DOC-3 — Add explicit "do not queue" warning comments on the two checklist listeners** — cross-reference **TD-8**.

---

**DOC-4 — Write Phase 2 release notes** — cross-reference **G4**.

---

**DOC-5 — File `IndexController.php:60`'s null-pointer as its own tracked bug** — resolved by this document itself; see **BUG-11**.

---

### 2.10 Cleanup

---

**CLEAN-1 — Orphaned mockup screens** — **Low**
- `customer_admin/templates/index.blade.php` (routed, never linked from nav, hardcoded fake data) and `customer_admin/question_and_categories/index.blade.php` (its intended controller doesn't exist on disk). **Recommended:** delete or finish — don't leave half-wired. **Effort:** Small. **Risk:** Low (confirm truly unused first).

**CLEAN-2 — Unrouted `QuestionAndCategories` controllers** (both Rental Ready and Customer Admin sides) — same disposition as CLEAN-1, likely same PR.

**CLEAN-3 — Dead "Customer Admin" button** (no href/handler) in `checklist_master/create.blade.php`. **Effort:** Trivial.

**CLEAN-4 — `ChecklistMaster\CopyController`'s working Copy button left HTML-commented-out** in `_table.blade.php`, despite the route/controller working fully server-side. **Effort:** Trivial — likely just uncomment, verify still works, per a quick smoke test.

**CLEAN-5 — `admin.checklist-management.rental-ready.templates.index` route registered but its target view doesn't exist on disk** — would throw `ViewNotFoundException` if ever hit. **Effort:** Small — either create the view or remove the route.

**CLEAN-6 — Commented-out duplicate `customerAdminTemplate()` relationship method** in `ChecklistMaster.php`. **Effort:** Trivial (delete).

**CLEAN-7 — Leftover dead sidebar scaffolding** (`$customerChecklistActive` pointing at a placeholder route, comment "Add the correct route for customer checklist when ready"). **Effort:** Trivial.

**CLEAN-8 — Unverified retired Blade partial** `_table.blade_old.php` — confirm whether still `@include`d anywhere; delete if not. **Effort:** Small (verification + deletion).

**CLEAN-9 — Edit-mode validation bypass** in Checklist Master wizard Step 3's "Continue" button (server-side `required` still applies — low risk). **Effort:** Small.

**CLEAN-10 — Client-side gap:** template builder's hidden `questions` input has no "must not be empty" client-side check; server-side rejection still applies with no inline warning. **Effort:** Small.

**Recommended batching:** CLEAN-1 through CLEAN-10 are all independent, low-risk, and small — batch into 1-2 "housekeeping" PRs rather than tracking individually; use them as sprint-gap filler work.

---

## 3. Dependency graph

```
G5 (DOC-1: fix decision log)  ── no deps, do first, trivial
G3 (DOC-2: comms)              ── no deps, do immediately
G4 (Phase 2 release notes)     ── no deps, do before/alongside Phase 3 kickoff comms
G1 (finance reconciliation)    ── external, non-engineering, track only
G2 (PR-A4 telemetry window)    ── external clock, verify elapsed before MOB-1/MOB-2

BUG-11 (IndexController null)  ── independent
SEC-1 (enum constraints)        ── independent
DB-2 (unique constraint)        ── independent, confirmed safe
CLEAN-1..10                     ── independent, batch opportunistically
TD-3,4,6,7,9,13,14,15           ── independent, opportunistic

BUG-6 (unique_id fix) ──requires──> mobile coordination
API-1 (envelope std.) ──requires──> mobile coordination
API-2 (resource shape) ──requires──> mobile coordination
        └── these three can be coordinated together as one "mobile-facing API cleanup" wave

DB-1 (FK constraint) ──requires──> non-numeric-value data audit (small, first)

DB-4 (reassign ChecklistMaster #27) ── independent, TIME-SENSITIVE, do immediately
DB-6 (category-drift SQL check)     ── independent, read-only, do anytime; feeds ARCH-1/ARCH-3

BUG-2, BUG-4 (same controller: SaveDeliveryController) ──sequence together
BUG-5 (RemoveController)        ── independent of BUG-2/4 but same investigation pass
BUG-3 (cascading soft-delete)   ── independent
BUG-12 (equipment-category cross-check) ──sequence with──> BUG-2, BUG-4 (same
        delivery-controller investigation pass), independently shippable
        └── all five (including BUG-12) benefit from writing baseline
            characterization tests for SaveDeliveryController/SaveReturnController/
            RemoveController FIRST (none exist today) ──> TD-1/ARCH-2 also needs
            these same tests, so write them once, reuse for both

TD-1 / ARCH-2 (consolidate delivered/returned writes)
    ──requires──> BUG-2, BUG-4, BUG-5 already fixed (same surface, fix small bugs
                  before the larger refactor, matching this project's established
                  discipline of "characterization tests → small fixes → refactor")

MOB-1, MOB-2 (PR-A6 scoping + 44-row gap)
    ──requires──> G2 (telemetry window elapsed)
    ──informs──> SEC-2 (same item, security lens)

TD-8 / ARCH-4 (queue listeners)
    ──requires──> TD-8's tripwire test exists first (cheap, do regardless of ARCH-4)
    ──only pursue if──> PERF-1 justifies it with real data

ARCH-3 (soft-delete policy ratification)
    ──should precede or accompany──> ARCH-1 (unified schema needs one policy)

ARCH-1 (full schema unification)
    ──requires──> dedicated feasibility study (its own deliverable)
    ──requires──> explicit stakeholder go/no-go
    ──should follow──> all of the above being stable (touches everything already
                        refactored in PR-B4.1-4.3)
    ──benefits from──> DB-5 (shared fixtures) existing first
```

---

## 4. Recommended PR order

| Order | PR | Items | Rationale |
|---|---|---|---|
| 0 | Doc/data housekeeping (no code review needed) | DOC-1 (G5), DOC-2 (G3), DB-4, DB-6 | Trivial, time-sensitive (DB-4), zero-risk, or read-only investigation (DB-6) — do before Sprint 1 even starts |
| 1 | P3-1 — Bug fix | BUG-11 | Small, isolated, high-confidence fix |
| 2 | P3-2 — Security hardening | SEC-1 | Cheap now, expensive later |
| 3 | P3-3 — DB constraint | DB-2 | Confirmed safe, cheap |
| 4 | P3-4 — Housekeeping batch | CLEAN-1 through CLEAN-10 (1-2 PRs) | Independent, fills sprint gaps |
| 5 | P3-5 — Debt cleanup batch | TD-3, TD-4, TD-6, TD-7, TD-9, TD-13, TD-14, TD-15 | Independent, opportunistic, mostly small |
| 6 | P3-6 — Mobile-facing API cleanup | BUG-6, API-1, API-2 | Bundle since all three need the same mobile-team coordination |
| 7 | P3-7 — DB constraint (after audit) | DB-1 | After its own prerequisite data-audit sub-task |
| 8 | P3-8 — Baseline tests | Characterization tests for `SaveDeliveryController`/`SaveReturnController`/`RemoveController` | Prerequisite for P3-9–P3-12 and later ARCH-2 |
| 9 | P3-9 — Bug fix | BUG-2 (N1 same-equipment re-delivery) | |
| 10 | P3-10 — Bug fix | BUG-4 (re-delivery guard) | Same controller as P3-9 |
| 11 | P3-11 — Bug fix | BUG-5 (RemoveController partial revert) | Needs a product decision first (see item detail) |
| 12 | P3-12 — Bug fix | BUG-3 (cascading soft-delete) | |
| 12a *(newly added — inserted here to preserve items 13-20's existing numbering)* | P3-12a — Bug fix | BUG-12 (equipment-category cross-check) | Same delivery-controller investigation pass as items 9-10 (BUG-2/BUG-4); independently shippable |
| 13 | P3-13 — Mobile enforcement decision + implementation | MOB-1, MOB-2, SEC-2 | Gated on G2 — verify telemetry window elapsed first |
| 14 | P3-14 — Fast-follow | PR-B1's deferred UI confirmation dialog (bulk-unassign warning) | Carried-over Phase 2 fast-follow, still open |
| 15 | P3-15 — D2 enforcement decision | PR-B2's admin-web observe→enforce transition | Gated on reviewing its own accumulated `api_errors` telemetry |
| 16 | P3-16 — Architecture | TD-8 tripwire test (cheap, standalone) | Do regardless of whether ARCH-4 is ever pursued |
| 17 | P3-17 — Architecture (large) | ARCH-2 / TD-1 (consolidate delivered/returned writes) | After P3-9–P3-12 stabilize the same surface |
| 18 | P3-18 — Decision | ARCH-3 (soft-delete policy ratification) | Before or alongside ARCH-1 planning |
| 19 | P3-19 — Feasibility study | ARCH-1 (unification study, not code) | Own deliverable, gates any further schema-unification work |
| 20 | P3-20+ — Implementation (if approved) | ARCH-1 implementation | Separately scoped multi-PR project after P3-19's go-ahead |

---

## 5. Sprint grouping (2-week sprints assumed)

- **Sprint 0 (pre-work, not a full sprint):** PR order item 0 (doc/data housekeeping, now including DB-6's read-only SQL check). Can happen the same day this plan is approved.
- **Sprint 1:** PR order items 1-5 (bug fix, security, DB constraint, cleanup batches, debt batch). Fast wins, low risk, builds momentum.
- **Sprint 2:** PR order items 6-8 (mobile API cleanup + FK constraint + baseline test-writing for delivery/return controllers).
- **Sprint 3:** PR order items 9-12 plus 12a (the five delivery/return bug fixes, including the newly-added equipment-category cross-check, built on Sprint 2's new baseline tests).
- **Sprint 4:** PR order items 13-15 (mobile enforcement decisions — contingent on G2's telemetry window; if not yet elapsed, use this sprint for ARCH-1's feasibility study instead and slip Sprint 4's original scope to Sprint 5).
- **Sprint 5:** PR order items 16-18 — specifically: the **TD-8 tripwire test** (completes this sprint), **ARCH-2 begins** (does *not* complete this sprint — it is a Large, 5-8 day item and continues into Sprint 6-7, see below), and the **ARCH-3 decision** (completes this sprint, a decision only). Sprint 5 should not be read as delivering a finished ARCH-2.
- **Sprint 6-7+ (separately scoped, not part of this plan's core estimate):** **ARCH-2 continues and completes here**, then ARCH-1's feasibility study and (if approved) its own multi-sprint implementation project.

---

## 6. Timeline

- **Weeks 1-2 (Sprint 1):** Fast wins — bugs, security, DB constraint, cleanup. Low risk, high visible progress.
- **Weeks 3-4 (Sprint 2):** Mobile coordination items + baseline test-writing investment.
- **Weeks 5-6 (Sprint 3):** Delivery/return bug fixes, built on the new test baseline.
- **Weeks 7-8 (Sprint 4):** Mobile enforcement decisions (PR-A6/D2), contingent on external telemetry-window timing (G2) — **this is the one sprint most likely to slip or reorder based on facts outside engineering's control.**
- **Weeks 9-10 (Sprint 5):** Architecture prep — TD-8 tripwire test (completes), ARCH-2 begins (does not complete this sprint), ARCH-3 decision (completes).
- **Weeks 11+ (Sprint 6-7, separately scoped):** ARCH-2 continues and completes here.
- **Beyond (own timeline, gated on stakeholder approval):** ARCH-1 feasibility study (1-2 weeks) → if approved, implementation (estimated 4-8+ weeks, not included in this plan's core 10-week estimate since it is explicitly a candidate, not a commitment).

**Total core-backlog estimate (excluding ARCH-1 implementation): ~10-11 weeks of engineering effort**, consistent with this project's established pattern of separating "engineering effort" from "calendar risk" (per `PHASE2_IMPLEMENTATION_PLAN.md`'s own framing) — actual calendar time will extend beyond 10-11 weeks to the extent G1/G2's external gates and any mobile-team coordination take longer than engineering alone controls.

---

## 7. Deployment strategy

- **Sprint 1-2 items (bugs, security, DB constraints, cleanup):** Standard staged rollout — no feature flags needed. Follow this project's now-established discipline: characterization tests before any behavior change, PASS/CHANGES-REQUIRED review before merge, regression suite re-run after.
- **Mobile-facing changes (BUG-6, API-1, API-2):** Coordinate with the mobile team on client-version compatibility *before* deploying. Consider a brief dual-behavior window (old + new shape simultaneously) if any current client dependency is confirmed. Do not deploy silently.
- **Delivery/return bug fixes (BUG-2, BUG-4, BUG-5, BUG-3, BUG-12):** Deploy behind the same "tests-first, no flag needed" pattern used throughout Phase 2 — but monitor `api_errors`/`equipment_status` logs closely for the first few days post-deploy given these touch live rental-status logic. BUG-12 specifically should ship in its observe-first (logging-only) form per its own recommended approach, not as a hard rejection.
- **Mobile enforcement decisions (MOB-1/MOB-2/SEC-2, and PR-B2's D2 enforcement):** Follow the exact staged pattern this project already validated with PR-A4→(eventual)PR-A6 and PR-B2's observe-then-enforce design — ship behind a config toggle or staged rollout allowing instant reversion to logging-only if mobile clients react badly. Do not hard-cut from observability straight to enforcement without a rollback lever.
- **ARCH-2 (delivered/returned consolidation):** Standard staged rollout once its own characterization-test baseline is green, mirroring PR-B1's rollout.
- **ARCH-1 (schema unification), if and only if approved after its feasibility study:** This is **not** a normal deploy. Requires its own written migration plan (mirroring this project's `PHASE3_RUNTIME_VALIDATION_SQL_PLAN.md` rigor), a dual-write/backward-compatible transition window, a load-tested rollback plan, and likely a shadow-table or blue/green approach given two independent trees of live production data are involved. Do not fold this into any other release. Treat it, in effect, as its own phase regardless of the "Phase 3" label.

---

## 8. Phase 3 Decision Log

Two register items need an explicit stakeholder/product decision before implementation can be scoped precisely — the same pattern `PHASE2_DECISION_MATRIX.md` used for D1-D5. Added here per this update's review findings, mirroring that document's exact table format.

| # | Decision | Related item | Cost if changed | Risk if unchanged | Recommendation |
|---|---|---|---|---|---|
| D6 | Should `RemoveController` cascade its revert to return-side fields (`pickup_*`/`is_returned`), or explicitly refuse to remove a checklist when a return has already occurred? | BUG-5 | Low-Medium — a product/UX call on what staff expect this button to do in an edge case | Medium — confusing, contradictory order-history state persists until decided | **Decide before implementing BUG-5** — no engineering default is obviously correct here |
| D7 | For a checklist submission referencing a stale/deleted question or answer, should the system reject the entire batch (current, unconfirmed behavior) or partially accept the valid portions? | BUG-8 | Low — investigation-first, no behavior changes until the current mechanism is actually observed | Low-Medium — unclear until BUG-8's investigation step runs | **Investigate first (BUG-8's own recommended first step), then decide** — same "investigate before deciding" pattern used for D4 in Phase 2 |
| D8 | Should Rental Ready `IndexController`'s read path adopt `SaveController`'s checklistMaster-template fallback for "order product, no template yet," or should `SaveController` be tightened to match Index's stricter (no-fallback) behavior instead? | BUG-13 | Low — read-only listing behavior either way, no schema/write-path risk | Low-Medium — a UX/consistency gap persists (Index and Save disagree) but no crash, data-loss, or security exposure | **Decide before implementing BUG-13** — both directions are technically safe; this is a product/UX call on which endpoint's convention should win |

**P3-6 compatibility decisions** (added per the P3-6 readiness review — none of these three can be resolved by backend code inspection alone; all require mobile-team input, tracked in `P3_6_MOBILE_COMPATIBILITY_QUESTIONNAIRE.md`):

| # | Decision | Related item | Cost if changed | Risk if unchanged | Recommendation |
|---|---|---|---|---|---|
| D9 | Should BUG-6 ship an additive transition field (e.g. a temporary `question_unique_id` alongside the existing `unique_id`) before correcting `unique_id` in place, or is a direct in-place fix safe? | BUG-6 | Low — an additive field is a small, reversible addition either way | Medium — if a mobile client currently parses `unique_id` as numeric and it silently starts returning a string, that client could crash or misbehave with no advance warning | **Decide only after mobile-team questionnaire response** — do not fix `unique_id` in place until confirmed safe |
| D10 | Should API-1 emit both `success` and `status` keys simultaneously during a transition period, or cut over directly to `{success, message}` on both endpoints? | API-1 | Low — dual-key emission is a trivial additive change | Medium — a client checking `response.status === true` literally would break on direct cutover with no warning | **Decide only after mobile-team questionnaire response** |
| D11 | Should API-2's resource-shape standardization (field names and `required_question`/selected-answer shapes) happen now, be shipped behind a new API version, or be deferred entirely? | API-2 | Low-Medium depending on direction chosen | Medium — the two resources will keep drifting further apart the longer this is deferred, but forcing a shape change without mobile confirmation risks a live break | **Decide only after mobile-team questionnaire response** — this is the most invasive of the three P3-6 items in terms of shape change, so lean toward versioning or deferral unless mobile confirms tolerance |

### Decision log (fill in once decided)

| # | Decision | Decided by | Date | Outcome |
|---|---|---|---|---|
| D6 | RemoveController cascade vs. refuse-if-returned? | | | **Pending** |
| D7 | Partial-accept vs. reject-entire-batch on stale reference? | | | **Pending** |
| D8 | Index fallback-to-checklistMaster vs. tighten Save to match Index? | | | **Pending** |
| D9 | BUG-6: additive transition field vs. direct in-place fix? | | | **Pending** |
| D10 | API-1: dual-key transition vs. direct cutover? | | | **Pending** |
| D11 | API-2: standardize now, version, or defer? | | | **Pending** |

No PR touching BUG-5, BUG-8, or BUG-13 should begin implementation ahead of its corresponding decision above being recorded in this log, consistent with this project's established Phase 2 discipline. **The same rule applies to BUG-6, API-1, and API-2 for D9-D11** — none of P3-6 should begin implementation until its corresponding decision here is recorded, and none of D9-D11 should be recorded until the mobile-team questionnaire response is in hand.

---

## Confirmation

**NO CODE WAS MODIFIED.** This document is planning only, synthesized from every checklist-system-audit document listed at the top. Where this document's findings correct a stale claim in an existing document (see DOC-1), that correction is noted here but the underlying file has not been edited as part of this task — updating `PHASE2_DECISION_MATRIX.md` itself is listed as its own small, trivial action item (DOC-1) for a future PR, not performed now.

**This revision applied accepted independent-review findings to this document only** — two new register items (BUG-12, DB-6), a severity reclassification (DB-5), a clarifying note (SEC-1), a reworded Sprint 5 description, and a new §8 Decision Log (D6, D7). No implementation order was changed, no existing item was removed, and no engineering estimate was altered except where directly tied to the two new items themselves. **No code was modified in this revision either.**

**Stopping after this planning document as instructed.** Not beginning Phase 3 implementation.
