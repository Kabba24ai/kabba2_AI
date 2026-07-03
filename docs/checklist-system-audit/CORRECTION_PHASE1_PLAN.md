# Checklist System — Correction Phase 1 Plan

**Date:** 2026-07-03
**Status:** Planning only. **No code has been modified to produce this document.**
**Scope:** The five issues explicitly confirmed by runtime testing in `PHASE3_RESULTS.md`, and only those. Everything else in `CHECKLIST_SYSTEM_AUDIT.md` / `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` (duplicate CRUD stacks, dead routes, response-envelope inconsistencies, category cross-validation, etc.) is explicitly **out of scope** for this phase and should not be touched alongside it.
**Explicit exclusions per instruction:** no refactoring, no cleanup work, no UI changes unless a fix genuinely requires one (none in this plan do), no schema/migration changes unless truly unavoidable (one item below discusses why a schema change is *not* being proposed here, in favor of a safer interim approach).

This plan assumes the correction work will be done by an engineer with access to a real staging/production-adjacent environment and CI. It does not implement anything — it specifies what to build, in what order, and how to know it's safe.

---

## Issue 1 — Billing idempotency bug: second rental cycle's damage/fuel charge is silently dropped

### Confirmed evidence
`PHASE3_RESULTS.md` §1 (V8 idempotency test) and §2 (N3): a full delivery→return cycle on order product 77 correctly created a `billing_charges` damage row ($150, key `mobile_checklist:77:damage`) and fuel row ($25, key `mobile_return_fuel:77:60`), plus a `customer_accounts` fuel row. A **second, independent** delivery→return cycle on the **same order product** (different equipment, new damaged answer, $200 damage / $15 fuel) returned HTTP 200 but produced **zero new rows** anywhere — the damage charge collided on the `mobile_checklist:{order_product_id}:damage` key, and the fuel charge was silently absorbed by `ChargeService::createFromOrderProduct()`'s duplicate guard, which has no reading- or cycle-level disambiguator at all. This is a reproduced, certain bug, not a theoretical risk.

### Affected files
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` (the calling code, ~lines 132-139 for damage, ~186-223 for the fuel bridge)
- `app/Http/DataObjects/BillingChargeRequest.php` (`mobileReturnDamage()`, builds the idempotency key, ~line 186)
- `app/Services/ChargeService.php` (`createFromOrderProduct()`, duplicate guard, ~lines 27-81)
- `app/Services/BillingEngine.php` (`charge()`, idempotency short-circuit, ~lines 28-78)

### Root cause
Both idempotency mechanisms scope uniqueness to `order_product_id` alone (plus, for the `billing_charges` fuel key only, the submitted `fuel_final_reading`). Because an `OrderProduct` row is reused across multiple delivery/return rental cycles rather than being recreated per cycle, and there is no existing column that uniquely identifies "this particular rental cycle," any second legitimate charge of the same type on the same order product collides with the first and is dropped.

### Safest fix approach
**Do not add a new schema column for this phase.** A cycle-scoped disambiguator can be derived from data that already exists and is already guaranteed to change on every delivery: `SaveDeliveryController` unconditionally deletes and recreates every `order_product_checklist_questions` row on every delivery call (confirmed in Phase 2 §3 and re-confirmed live in Phase 3 §2/N1 — this happens even when the same equipment is redelivered). The freshly-generated primary key of those rows is therefore a reliable, already-present per-cycle nonce.

Recommended fix: derive a `cycleKey` as the minimum (or any single, deterministic) `id` from the order product's currently-active (non-deleted) `order_product_checklist_questions`, captured once per return submission, and fold it into both idempotency mechanisms:
- `billing_charges` damage key: `mobile_checklist:{orderProductId}:damage:{cycleKey}`
- `billing_charges` fuel key: keep the existing `fuel_final_reading` component and add `{cycleKey}` too, since two cycles could coincidentally submit the same final reading
- `ChargeService::createFromOrderProduct()`'s duplicate guard: change from "any existing charge for this order_product_id + reason with a pending/completed status" to "any existing charge for this order_product_id + reason + `{cycleKey}` (or, if `cycleKey` isn't threaded into `customer_accounts`, at minimum a `created_at` cutoff at/after the current cycle's delivery timestamp)"

This closes the bug without a migration. It is explicitly an interim fix: the more robust long-term solution is a dedicated `rental_cycle_id` (UUID) column stamped once per delivery and carried through to every checklist/billing row of that cycle — that is a schema change and is intentionally **not** part of this phase; note it here only so it isn't lost, and revisit in a later phase once this interim fix has been observed working in production.

### Tests to write before the code change
1. **Feature test — single cycle, sanity check:** deliver → return with damage + fuel → assert exactly one `billing_charges` row of each type exists, with the expected amounts. (This test does not exist today — Phase 2 confirmed zero test coverage for this domain.)
2. **Feature test — the actual bug, red before fix:** deliver → return with damage → deliver again (different equipment) → return again with a new damage answer and a new fuel reading → assert **two** `billing_charges` damage rows and **two** fuel rows exist (currently fails with only one of each — this is the regression test that proves the fix).
3. **Feature test — real duplicate submission still blocked:** replay the exact same return request twice within the same cycle → assert still only one charge of each type is created (protects against reintroducing double-charging while fixing under-charging).
4. **Unit test on `ChargeService::createFromOrderProduct()`** in isolation, asserting the duplicate guard is now cycle-aware, independent of the full HTTP flow.

### Rollback risk
**Low for the code itself** — the change is additive to the key/guard logic and does not alter the shape of `billing_charges`/`customer_accounts`. If reverted, the system returns to the current (buggy but "stable" in the sense of not erroring) behavior. **The real rollback risk is financial, not technical**: once deployed, new legitimate second-cycle charges will start being created that previously were silently dropped — if this fix is later reverted after being live for a period, any charges created in that window remain valid and do not need to be undone, but stakeholders should be aware the "silently missing charge" safety net (dropping duplicates) is being narrowed, so a bug in the new key derivation could theoretically cause an actual duplicate charge to slip through where the old, over-broad key would have blocked it. This is exactly why test #3 above is mandatory before shipping.

### Database/data cleanup needed?
**No destructive cleanup, and no proactive backfill.** Do not attempt to retroactively re-key existing `billing_charges.idempotency_key` values — they are already correctly protecting against duplicates for charges already created and reconciled. Separately from this code fix, recommend (as a distinct, non-code task, owned by finance/accounting per `PHASE3_RESULTS.md`'s own go/no-go framework) a one-time **read-only** reconciliation query across historical `order_products` with more than one delivery→return cycle, to identify whether any real customer had a legitimate second charge silently dropped historically. That investigation is out of scope for this coding phase but should happen in parallel before this fix's production deploy is considered fully "closed."

### Priority
**Critical** — confirmed, reproducible revenue-impacting bug.

### Implementation order position
**3rd to implement, but gated separately for deploy.** Build and test this fix in parallel with items 2 and 4 (no code dependency between them), but do not deploy it to production until finance has completed the reconciliation check referenced in `PHASE3_RESULTS.md` §4 ("NO-GO on billing changes until reconciled"). Building and testing against staging is not blocked by that gate — only the production release is.

---

## Issue 2 — `equipment_status_logs` never populated because of `saveQuietly()`

### Confirmed evidence
`PHASE3_RESULTS.md` §1 (V3, both delivery and return halves): two real, mobile-driven equipment status transitions (`available→rented` and `rented→damaged`) on the same equipment produced **zero** rows in `equipment_status_logs`, confirmed at 100% (2/2) in this test run, consistent with Phase 2's static finding that all six `EquipmentStatusService` transition methods use `saveQuietly()`.

### Affected files
- `app/Services/Equipment/EquipmentStatusService.php` (`markRented`, `markReturnedToMaintenance`, `markReturnedDamaged`, `markAvailableOnChecklistRemove`, `markAvailableFromRentalReady`, `markMaintenanceFromRentalReady`, `markDamagedFromRentalReady` — every method calls `$equipment->saveQuietly()`)
- `app/Observers/EquipmentObserver.php` (the code that would write `EquipmentStatusLog`, gated on the `updating` Eloquent event which `saveQuietly()` suppresses)
- `app/Providers/AppServiceProvider.php` (`Equipment::observe(EquipmentObserver::class)` — confirms this is the only observer registered on `Equipment`)
- **`app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php` (lines ~149, ~162, ~247) — a third site of the identical bug, found during the Issue #5 investigation (`ISSUE5_INVESTIGATION_FINDINGS.md` §3).** This admin controller calls `$equipment->saveQuietly()` directly, independent of `EquipmentStatusService`, whenever an admin manually transitions an order product's schedule status. Any fix for this issue must cover this controller too, not only the service class.

### Root cause
`saveQuietly()` was used (deliberately, per the surrounding code's structure) to avoid firing Eloquent model events during these status transitions — but the only registered observer on `Equipment` is exactly the one responsible for the dedicated audit table this system needs. The suppression was broader than necessary: it silenced the one listener that mattered along with everything else.

### Safest fix approach
**Do not switch to a plain `save()`.** Changing `saveQuietly()` to `save()` would re-enable *all* Eloquent events on `Equipment`, not just the one observer we've confirmed — and this audit has not exhaustively verified there are no other model-level hooks (e.g., cache invalidation, a `booted()` closure, a package's auto-registered listener) that could fire unexpectedly and change behavior in ways outside this fix's intent. That is a broader, harder-to-bound risk than necessary for what is fundamentally a one-table audit-log gap.

**Recommended fix:** keep `saveQuietly()` exactly as-is (preserving whatever behavior it was originally introduced to suppress), and add an explicit, direct call to write the same `EquipmentStatusLog` row that `EquipmentObserver::updating()` would have written, immediately after each `saveQuietly()` call inside `EquipmentStatusService`. This is a surgical, additive fix scoped to exactly the gap that was found, with no change to Equipment's broader event behavior. Concretely: capture `$fromStatus`/`$toStatus`/`$actorId` (all already available in each method, since they're used to build the existing `Log::channel('equipment_status')` line) and create one `EquipmentStatusLog` row per transition, in the same place and with the same data the observer would have used.

### Tests to write before the code change
1. **Unit test on `EquipmentStatusService::markRented()`** (and, by the same pattern, each of the other five methods): assert that after calling it, exactly one new `EquipmentStatusLog` row exists for the equipment, with the correct `from_status`/`to_status`/`changed_by`.
2. **Feature test — full delivery flow:** call `save-delivery`, then assert an `EquipmentStatusLog` row now exists for the `available→rented` transition (this is the direct regression test for the exact gap Phase 3 reproduced).
3. **Feature test — full return flow (both outcomes):** assert a row exists for both the damaged (`rented→damaged`) and non-damaged (`rented→maintenance`) return paths.
4. **Regression test confirming no double-write:** since `EquipmentObserver` still exists and is still registered, confirm that a *separate*, non-`saveQuietly()` code path elsewhere in the app (e.g., an admin controller doing a plain `equipment->save()`) still produces exactly one log row via the observer, not two — to catch any future accidental removal of `saveQuietly()` that would cause double-logging once this fix is in place.

### Rollback risk
**Very low.** This is a pure addition — a new `EquipmentStatusLog::create()` call inside a service method, using data already computed in that method. Reverting removes the extra insert and returns to today's (broken but stable) behavior. No other code reads `EquipmentStatusLog` in a way that would break if rows stop appearing again after a rollback (nothing currently depends on that table's fill rate, precisely because it's been empty for these transitions since inception).

### Database/data cleanup needed?
**No, and none is possible.** The historical gap cannot be reconstructed — those transitions already happened without being logged, and there is no reliable way to backfill exact `changed_by`/`changed_at` values after the fact from `equipment.current_status` alone (it only reflects the *current* state, not the full history). Communicate this permanently-incomplete history to any team currently relying on `equipment_status_logs` for reporting, per `PHASE3_RESULTS.md` §4 recommendation — this is a communication action, not a data-cleanup action.

### Priority
**Critical** — 100% failure rate confirmed for the transitions this system exists to serve; lowest technical risk of all five items.

### Implementation order position
**1st.** No dependencies on anything else in this plan, lowest risk, immediate value, and item 4 (transaction wrapping) should be built to include this new write inside its transaction boundary from the start — so this needs to land first or concurrently with item 4, not after it.

---

## Issue 3 — Delivery/return marked "Completed" without a signature or required-question coverage

### Confirmed evidence
`PHASE3_RESULTS.md` §1 (V1, V2, V7): a real delivery was completed with only 1 of 12 `required_question=1` template questions answered and no signature file sent at all; `delivery_status` was set to `'Completed'` and `is_delivered=1` regardless. The identical pattern was confirmed on the return side.

### Affected files
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php` (~lines 151-162, unconditional `delivery_status='Completed'`)
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` (~lines 158-171, unconditional `pickup_status='Completed'`)
- `app/Http/Requests/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryRequest.php` (~line 88, `signature_media` is `nullable`)
- `app/Http/Requests/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnRequest.php` (~line 65, same)
- `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php` (~lines 160-173, the required-answer rejection check exists in the code but is commented out)

### Root cause
Completion status was implemented as an unconditional side effect of the save operation succeeding, rather than as a computed outcome gated on the actual completeness of the submission. This appears intentional at the time it was written (the mobile client presumably enforces this client-side today), not accidental — which is exactly why this needs a careful rollout rather than an immediate hard block.

### Safest fix approach
**Do not make `signature_media` required or add a hard rejection in this phase.** This system has zero automated test coverage (confirmed in Phase 2) and an unknown-to-us population of real mobile client versions in the field. Hard-blocking now risks breaking real deliveries/returns in production for any client that currently relies on the lenient behavior, with no way to quantify that risk in advance.

**Recommended fix for this phase: observability, not enforcement.** Compute the *actual* completeness (signature present AND every `required_question=1` question has an `is_delivery_answer`/`is_return_answer` row) inside both controllers, and:
1. Log a structured warning (new log channel or an existing one such as `api_errors`) whenever a checklist is marked "Completed" despite failing this computed check, including `order_product_id`, missing-question count, and signature presence — giving the team real production data on how often this actually happens before deciding whether to enforce it.
2. Do **not** change the HTTP response, the `delivery_status`/`pickup_status` value written, or reject the request in this phase — the mobile client's experience and the data written to `order_products` remain byte-for-byte identical to today.
3. Explicitly schedule true enforcement (rejecting incomplete submissions, or introducing a distinct "Incomplete"/"Partial" status value) as **Correction Phase 2**, gated on reviewing the telemetry this phase produces — not part of this plan's scope.

This satisfies "safest fix approach" for a billing-adjacent, zero-test-coverage system: it makes the gap fully visible and measurable without touching production behavior at all, and is trivially, completely reversible.

### Tests to write before the code change
1. **Unit test on the new completeness-check helper/method** (whatever the fix introduces to compute "is this actually complete"): given a set of required questions and a partial answer set, returns `false`; given full coverage + signature, returns `true`.
2. **Feature test — logging fires correctly:** submit an incomplete delivery (as Phase 3 did), assert the new log entry is written with the expected fields, and assert `delivery_status` is still `'Completed'` (proving this phase makes no behavior change).
3. **Feature test — no log noise on legitimately complete submissions:** submit a fully-answered, signed delivery, assert no warning is logged.

### Rollback risk
**Effectively zero.** This is a pure logging addition with no behavioral or schema change. Reverting is a one-line removal of the log call.

### Database/data cleanup needed?
**No.** Historical order products remain as they are; this phase does not reclassify or touch any existing `order_products` row.

### Priority
**High** (not Critical for *this phase specifically*, because the recommended action is observability rather than enforcement — the underlying business risk remains Critical per Phase 1/2, but this phase's actual code change is deliberately low-risk by design).

### Implementation order position
**4th.** Independent of items 1, 2, and 4 — can be built in parallel with any of them. Placed after the transaction-wrapping work (item 4) only so that, if a future phase upgrades this from logging to enforcement, the rejection can cleanly throw inside an already-established transaction boundary rather than needing to retrofit one.

---

## Issue 4 — Missing DB transactions around checklist save flows

### Confirmed evidence
Phase 2 §5 (static): no `DB::transaction()` wraps any of `SaveDeliveryController`, `SaveReturnController`, `RemoveController`, or `DriverChecklistController`, and Laravel's synchronous, auto-discovered event listeners run inline, outside any transaction — so a listener exception after the main writes have already committed produces a 500 response while the data mutation persists, with the `order_history` entry silently missing. This was not separately re-triggered in Phase 3 (it requires temporary code instrumentation, explicitly deferred as S4), but the underlying non-atomicity itself is a structural fact of the current code, not a hypothesis.

### Affected files
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php`
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php`
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/RemoveController.php`
- `app/Http/Controllers/Api/Admin/V1/Orders/Schedules/DriverChecklistController.php`
- `app/Listeners/Activities/Admin/Orders/OrderCustomerChecklistListener.php` / `OrderProductDriverChecklistUpdatedListener.php` (the code that can throw *after* the main writes)

### Root cause
The controllers perform a sequence of independent writes (delete/recreate checklist rows, update `OrderProduct`, mutate `Equipment` status, fire an event whose listener writes `OrderHistory`) with no atomicity boundary. Since listeners run synchronously and inline, a failure anywhere in that chain — including in code that runs *after* the "real" work is done — currently has no way to undo what already succeeded.

### Safest fix approach
Wrap each controller's write sequence — including the `event()` dispatch, since its listener executes synchronously inline before the method returns — in a single `DB::transaction()` block. Because Laravel's default synchronous listeners are not queued, they execute *inside* the surrounding `DB::transaction()` call stack if the event is fired from within it; if a listener throws, the exception propagates and the transaction rolls back cleanly, undoing the checklist rows, the `OrderProduct` update, and the equipment status change together. The client still receives a 500 in that scenario, but it will now be an accurate 500 — nothing partially happened — rather than today's misleading "500 but the data actually changed" outcome.

**One caveat to test for explicitly, not to design around defensively in this phase:** `MediaHelper::uploadStorageFile()` (used for the optional signature upload) writes to physical storage as a side effect that is *not* covered by a DB transaction rollback. If a transaction rolls back after a signature file was already written to disk, the file becomes orphaned (the `Media` DB row itself, being a normal Eloquent `create()`, *would* correctly roll back). This is a low-severity, acceptable trade-off — an orphaned file with no DB reference is inert — and is explicitly not being engineered around in this phase (doing so would mean coordinating a two-phase commit across the filesystem and the database, which is disproportionate to the actual risk). Note it in code review so it isn't mistaken for an oversight.

**Sequencing with Issue 2:** implement Issue 2's new `EquipmentStatusLog::create()` call so that it executes inside the same transaction boundary this fix introduces, from the start — do not land Issue 2 first and then retrofit it into the transaction separately, since that would mean testing the transaction wrap twice.

### Tests to write before the code change
1. **Feature test — happy path unaffected:** confirm a normal, fully successful delivery/return still commits every expected write (no regression from adding the transaction wrapper itself).
2. **Feature test — the actual fix, red before fix:** force `OrderCustomerChecklistListener::handle()` to throw (e.g., via a test double or a temporary invalid state) during a delivery call, and assert that **none** of the checklist rows, the `OrderProduct` update, or the equipment status change persisted — i.e., a full rollback occurred. This is the direct regression test proving the fix; today, per Phase 2's static analysis, this same test would show the writes persisting despite the 500.
3. **Feature test — `DriverChecklistController`'s swallowed-exception behavior**, run alongside this fix: confirm that once wrapped in a transaction, a listener failure in that controller also now rolls back `$schedule->save()`'s effects rather than committing them silently before returning its generic 500 (this controller's separate bug — logging the swallowed exception — is a good candidate to fix in the same pass since it's directly adjacent, but is not itself one of the five listed issues; flag it to the implementer as a low-cost addition, not a scope requirement of this plan).

### Rollback risk
**Low-to-moderate.** Reverting removes the transaction wrapper and returns to today's non-atomic (but currently "working" in the sense of not visibly failing under normal conditions) behavior. The main thing to watch during rollout: if any *other* code, anywhere in the system, was implicitly relying on the checklist rows being written even when the history-write listener fails (i.e., relying on today's partial-commit behavior as a feature rather than a bug) — Phase 1/2's audit found no evidence of this, but it should be explicitly checked in code review before merging, since it's the one way this fix could introduce a new regression rather than only fixing an existing one.

### Database/data cleanup needed?
**No.** This is a pure code-behavior change with no effect on existing data.

### Priority
**Critical** — this is the structural fix that makes issue 1's and issue 2's fixes (and any future enforcement work from issue 3) actually safe and atomic, rather than each being one more independent write that could partially fail.

### Implementation order position
**2nd**, immediately after Issue 2. Building the transaction boundary before finishing Issue 1's idempotency-key fix means the new billing-charge logic is naturally covered by the same atomicity guarantee from day one, rather than being added to an already-shipped, non-transactional flow.

---

## Issue 5 — Investigation: 876 delivered order products with zero checklist rows

### Confirmed evidence
`PHASE3_RESULTS.md` §1 (V9): 876 of 2,665 (33%) `order_products` rows with `is_delivered=1` have no `order_product_checklist_questions` at all, sampled as recent/ongoing (not legacy data predating the checklist system).

### This is explicitly an investigation, not a fix, in this phase
Per the user's instruction, this item produces a diagnosis, not a code change. No file is "affected" by a fix here because no fix is being proposed yet — the investigation's job is to determine whether a fix is even needed, and if so, which of several plausible root causes it should target.

### Root cause — candidate hypotheses to test, in this order
1. **Product-type false positive (test this first, before anything else):** the checklist system may only be intended for **rental** products, not sale/service products. If a meaningful share of the 876 rows belong to non-rental order products, they are not a bug at all — they were never supposed to have a checklist. Phase 3's V9 query did not filter by product type; this must be corrected before drawing further conclusions.
2. **Legitimate non-checklist delivery paths setting `is_delivered` directly:** Phase 2 §1 identified `Admin\OrderManagement\Orders\UpdateProductScheduleController.php`, `AssignEquipmentController.php`, and `RemoveEquipmentController.php` as admin-side controllers that toggle `is_delivered`/`is_returned` without going through the checklist Save controllers, and separately `DriverChecklistController`/`UpdateDeliveryPickupInputsController` as a mobile driver-app flow that can promote legacy completion fields under certain conditions (`delivery_by` already non-null) without a checklist ever being submitted.
3. **A genuine gap** where a mobile or admin action marks a rental product delivered with no checklist captured at all, which would be the only sub-case actually warranting a Correction Phase 2 fix.

### Safest investigation approach (read-only SQL only, no code or data changes)
1. Re-run the 876-row query joined against the `products` table's type/category classification (however "rental vs. sale/service" is actually modeled in this schema — confirm the exact column via `SHOW COLUMNS FROM products` before writing the query, since earlier attempts during Phase 3 found no `product_category_id` column directly on `products`) and exclude anything that isn't a rental product. Report the corrected count.
2. For the remaining (confirmed-rental) rows, join against `order_history` to see whether an entry exists around the `delivery_date`/`created_at` timestamp, and inspect its `action` value — this should reveal whether the row was touched by `UpdateProductScheduleController`/`AssignEquipmentController` (which presumably log their own history actions) versus something with no history trail at all.
3. Cross-reference against `order_products.delivery_by` — per Phase 2's citation of `UpdateDeliveryPickupInputsController`'s own guard logic, `delivery_by` is only ever set by the real checklist Save controllers; a delivered row with `delivery_by IS NULL` is strong evidence it was set by one of the non-checklist paths.
4. Bucket the (now hopefully much smaller) remaining "unexplained" set by `created_at` date to see whether they cluster around specific deploys/releases (further narrowing which code path is responsible) or are evenly spread (suggesting an always-present, low-frequency path rather than a recent regression).
5. Produce a short findings memo (not a fix) with the corrected count and the responsible code path(s) for whatever remains, to scope an actual Correction Phase 2 item if warranted.

### Tests to write before the code change
**None for this phase** — there is no code change proposed here. If the investigation concludes a real fix is warranted, the specific fix (and its own test plan) becomes a new, separately-scoped item for Correction Phase 2, not an addition to this plan.

### Rollback risk
**None** — read-only investigation.

### Database/data cleanup needed?
**Not yet, and not in this phase.** Whether any cleanup is warranted (e.g., backfilling a "no checklist required" flag, or leaving genuinely gapped rows as-is) depends entirely on the investigation's outcome and is explicitly deferred, consistent with the instruction that this phase excludes cleanup work.

### Priority
**Medium-High** — not a confirmed code bug yet (unlike items 1, 2, and 4), but the scale (33% of all deliveries) means it must be understood before anyone treats "checklist exists" as a reliable proxy for "delivery happened," which multiple other parts of the system implicitly assume.

### Implementation order position
**Run first, in parallel with everything else, but block on nothing.** This is pure investigation with zero engineering risk and zero dependency on items 1-4 — there's no reason to sequence it after any code work. Its findings may, however, usefully inform the *next* phase's scope, so it should be completed and written up before Correction Phase 2 is planned.

---

## Summary: Priority and Implementation Order

| Order | Issue | Priority | Blocks on |
|---|---|---|---|
| 1 (parallel) | #5 Investigate 876-row gap | Medium-High | Nothing — read-only, run immediately |
| 1 | #2 `equipment_status_logs` fix | Critical | Nothing |
| 2 | #4 DB transaction wrapping | Critical | Land after/alongside #2 so the new audit-log write is included in the transaction from the start |
| 3 | #1 Billing idempotency fix | Critical | Code/tests can proceed in parallel with #2/#4; **production deploy gated on finance reconciliation** per `PHASE3_RESULTS.md` §4 |
| 4 | #3 Completeness observability (logging only, no enforcement) | High | Land after #4 so a future enforcement upgrade has a transaction boundary to throw inside; otherwise independent |

**Explicitly not in this phase:** any hard enforcement of signature/required-question rules (deferred to Correction Phase 2 pending telemetry from item #3), any schema change (the idempotency fix uses existing data rather than a new column, by design), any UI change (none of the five issues require one), and any refactoring or dead-code cleanup (tracked separately in Phase 1/2's own recommendations, explicitly excluded here).
