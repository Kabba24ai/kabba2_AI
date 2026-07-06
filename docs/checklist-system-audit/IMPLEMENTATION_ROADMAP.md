# Checklist System — Implementation Roadmap

**Date:** 2026-07-06
**Type:** Planning document. Synthesizes `CHECKLIST_SYSTEM_AUDIT.md` (Phase 1), `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md`, `PHASE3_RUNTIME_VALIDATION_SQL_PLAN.md` + `PHASE3_RESULTS.md`, `CORRECTION_PHASE1_PLAN.md`, and `ISSUE5_INVESTIGATION_FINDINGS.md` into a single, ordered backlog of git-sized tasks.
**Code modified to produce this document:** No. This is a planning artifact only — no PR listed below has been implemented.
**Status of underlying audit:** Closed. Classification **B — Functional but with important risks**. No further audit phases are planned; this document is the handoff from audit to correction work.

---

## 0. How to read this document

Each task below is sized to be one PR: one reviewable diff, one deploy decision, one rollback unit. Tasks are grouped into three tracks that map to the priority tiers already established in `CORRECTION_PHASE1_PLAN.md` §Summary and `CHECKLIST_SYSTEM_AUDIT.md` §18:

- **Track A — Confirmed bugs (Correction Phase 1 scope).** These five issues were reproduced by runtime testing in `PHASE3_RESULTS.md`. Fix these first.
- **Track B — Structural/consolidation work (Phase 2 of the original audit's fix-phase plan).** Real risks, not yet runtime-confirmed as active bugs, but load-bearing for future safety.
- **Track C — Cleanup (Phase 3/4 of the original audit's fix-phase plan).** Low-risk, low-urgency; dead code, response-shape consistency, schema hygiene.

Two items are explicitly **not PRs**: a finance data-reconciliation query (blocks one PR's production deploy, not its code) and a one-time data-correction task (fixes existing mismatched rows, not code). Both are called out where relevant.

---

## Track A — Confirmed Bugs (do first)

### PR-A1 — Populate `equipment_status_logs` for `saveQuietly()` transitions

**Priority:** Critical · **Depends on:** nothing · **Blocks:** PR-A2 (must land concurrently or PR-A2 retrofits it)

**Scope:** Add an explicit `EquipmentStatusLog::create()` call immediately after every `saveQuietly()` call that changes `Equipment.current_status`. Do **not** change `saveQuietly()` to `save()` — that would re-enable all Eloquent events on `Equipment`, an unbounded blast radius beyond this fix's intent.

**Files:**
- `app/Services/Equipment/EquipmentStatusService.php` — all 6 methods (`markRented`, `markReturnedToMaintenance`, `markReturnedDamaged`, `markAvailableOnChecklistRemove`, `markAvailableFromRentalReady`, `markMaintenanceFromRentalReady`, `markDamagedFromRentalReady`)
- `app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php` — lines ~149, ~162, ~247 (the third, previously-uncatalogued site found in `ISSUE5_INVESTIGATION_FINDINGS.md` §3)

**Regression tests:**
1. Unit test per `EquipmentStatusService` method: exactly one new `EquipmentStatusLog` row with correct `from_status`/`to_status`/`changed_by`.
2. Feature test: `save-delivery` → assert an `available→rented` log row exists.
3. Feature test: `save-return` (both damaged and non-damaged paths) → assert a log row for each outcome.
4. Feature test on `UpdateProductScheduleController`'s "Close as Completed" and plain "Completed" branches → assert log rows exist there too.
5. Regression guard: a plain (non-`saveQuietly`) `Equipment::save()` elsewhere still produces exactly one log row via `EquipmentObserver`, not two.

**Rollback plan:** Revert is a pure removal of the new `create()` calls — no schema change, no other code depends on this table filling (it's been empty for these transitions since inception). Rollback risk: **very low**.

**Deployment order:** 1st. No feature flag needed — additive, side-effect-free from every other system's perspective.

---

### PR-A2 — Wrap checklist controllers in `DB::transaction()`

**Priority:** Critical · **Depends on:** PR-A1 (land concurrently so the new log write is inside the boundary from day one) · **Blocks:** PR-A3 (billing fix should be built on top of this boundary)

**Scope:** Wrap each controller's full write sequence — including the `event()` dispatch, since its listener runs synchronously inline — in one `DB::transaction()` block, so a listener exception rolls back the checklist rows, the `OrderProduct` update, and the equipment status change together instead of leaving a partial commit behind a 500 response.

**Files:**
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php`
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php`
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/RemoveController.php`
- `app/Http/Controllers/Api/Admin/V1/Orders/Schedules/DriverChecklistController.php` (low-cost bonus: also add exception logging to its bare `try/catch(\Throwable)`, which currently swallows errors silently — flagged in `CORRECTION_PHASE1_PLAN.md` §Issue 4 as a good adjacent fix, not a scope requirement)

**Known accepted trade-off (do not engineer around in this PR):** `MediaHelper::uploadStorageFile()` writes to physical storage outside the DB transaction; a rollback after a signature file is written orphans that file on disk (the `Media` DB row itself rolls back correctly). Low severity, explicitly out of scope — note it in the PR description so reviewers don't flag it as a miss.

**Regression tests:**
1. Happy-path test: normal delivery/return still commits every expected write (no regression from wrapping alone).
2. Red-before-fix test: force `OrderCustomerChecklistListener::handle()` to throw during a delivery call → assert **none** of the checklist rows, `OrderProduct` update, or equipment status change persisted (full rollback). Today this same test shows the writes persisting despite the 500 — this is the proof the fix works.
3. `DriverChecklistController` variant of the same rollback test.

**Rollback plan:** Revert removes the transaction wrapper, returning to today's non-atomic (but not visibly broken under normal load) behavior. Check in code review that nothing implicitly depends on today's partial-commit behavior as a feature — Phase 1/2 found no such dependency, but confirm before merging. Rollback risk: **low-to-moderate**.

**Deployment order:** 2nd, immediately after PR-A1.

---

### PR-A3 — Fix billing idempotency key scope (damage + fuel charge under-counting on re-rental)

**Priority:** Critical (confirmed, reproduced revenue-loss bug) · **Depends on:** PR-A2 (build on the new transaction boundary) · **Blocks:** production deploy is gated separately — see below

**Scope:** A second legitimate delivery→return cycle on the same `order_product_id` (different equipment, new damage, new fuel reading) is silently dropped because both idempotency mechanisms key only on `order_product_id` (+ `fuel_final_reading` for the fuel `billing_charges` key). Derive a per-cycle disambiguator from data that already changes on every delivery — the freshly-generated primary key of the order product's currently-active `order_product_checklist_questions` rows (recreated on every `save-delivery` call) — and fold it into:
- `billing_charges` damage key: `mobile_checklist:{orderProductId}:damage:{cycleKey}`
- `billing_charges` fuel key: keep `fuel_final_reading`, add `{cycleKey}`
- `ChargeService::createFromOrderProduct()`'s duplicate guard: scope to `order_product_id + reason + {cycleKey}` instead of `order_product_id + reason + status IN (pending, completed)`

**Explicitly not in this PR:** no new schema column (a `rental_cycle_id` UUID is the correct long-term fix but is a separate, later migration-bearing PR — note it in the PR description so it isn't lost, but don't scope-creep this fix into it).

**Files:**
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` (~132-139 damage, ~186-223 fuel bridge)
- `app/Http/DataObjects/BillingChargeRequest.php` (`mobileReturnDamage()`, ~line 186)
- `app/Services/ChargeService.php` (`createFromOrderProduct()`, ~27-81)
- `app/Services/BillingEngine.php` (`charge()`, ~28-78)

**Regression tests:**
1. Single-cycle sanity: deliver → return with damage + fuel → exactly one `billing_charges` row of each type, correct amounts.
2. **Red-before-fix, the actual bug:** deliver → return with damage → deliver again (different equipment) → return again with new damage + new fuel reading → assert **two** damage rows and **two** fuel rows exist. Today this produces only one of each — this is the proof.
3. Real-duplicate-still-blocked: replay the identical return request twice within the same cycle → still only one charge of each type.
4. Unit test on `ChargeService::createFromOrderProduct()`'s duplicate guard in isolation.

**Rollback plan:** Additive change to key/guard logic, no schema change. If reverted, returns to today's stable-but-under-charging behavior. Watch item: once live, new legitimate second-cycle charges will start being created that were previously silently dropped — if reverted after being live, charges already created in that window remain valid and don't need reversal. Rollback risk: **low technically; the real risk is financial, which is why test #3 is mandatory.**

**Deployment order:** 3rd for code/merge — build and test in parallel with PR-A2/PR-A4, no code dependency blocks that. **Production deploy is separately gated**: do not release to production until finance/accounting completes a read-only reconciliation query (see "Non-PR task: Finance reconciliation" below) confirming whether any real customer had a legitimate second charge historically dropped. This is a deploy gate, not a code-review gate — staging deploys are not blocked.

---

### Non-PR task — Finance reconciliation (blocks PR-A3's production deploy only)

Not an engineering PR. A read-only SQL query across historical `order_products` with more than one delivery→return cycle, run by finance/accounting, to determine whether any real customer was affected by the idempotency bug before it's fixed in production. `PHASE3_RESULTS.md` §4 established this as a **NO-GO on billing changes until reconciled**. Track as a parallel workstream item, not a sprint task.

---

### PR-A4 — Completeness observability logging (no enforcement)

**Priority:** High (business risk is Critical; this PR's actual change is deliberately low-risk) · **Depends on:** PR-A2 (land after, so a future enforcement upgrade has a transaction boundary to throw inside) · **Blocks:** nothing directly, but its telemetry gates PR-B1 (see Track B)

**Scope:** Compute actual completeness (signature present AND every `required_question=1` question has an answer row) inside `SaveDeliveryController`/`SaveReturnController`, and log a structured warning when a checklist is marked "Completed" despite failing this check. **Do not** change the HTTP response, the `delivery_status`/`pickup_status` value, or reject the request — this phase is purely observational so the mobile client experience is unaffected while real production data is collected on how often this happens.

**Files:**
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php` (~151-162)
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` (~158-171)

**Regression tests:**
1. Unit test on the completeness-check helper: partial coverage → `false`; full coverage + signature → `true`.
2. Feature test: submit an incomplete delivery (as Phase 3 did) → assert the new log entry fires with expected fields, and `delivery_status` is still `'Completed'` (proves no behavior change).
3. Feature test: fully-answered, signed delivery → assert no warning logged (no log noise on the happy path).

**Rollback plan:** Pure logging addition, no behavior or schema change. Revert is a one-line removal of the log call. Rollback risk: **effectively zero.**

**Deployment order:** 4th.

---

### PR-A5 — Document Root Cause A ("Close as Completed") as an intentional architectural rule

**Priority:** Medium (process/documentation hygiene, not a bug fix) · **Depends on:** nothing · **Blocks:** nothing

**Scope:** `ISSUE5_INVESTIGATION_FINDINGS.md` confirmed 831 of 875 (95%) "delivered with no checklist" rows come from the admin `'Close as Completed'` status path in `UpdateProductScheduleController`, and that this is **by design**, not a bug. The risk is not the behavior — it's that nobody six months from now will know that, and will "fix" it by force-generating checklist rows or blocking the admin workflow. Add:
1. A short docblock/comment directly above the `'Close as Completed'` branch in `UpdateProductScheduleController.php` stating explicitly that this path is intentionally checklist-exempt and why (administrative closure without a physical delivery/return event).
2. A short architectural note (a new `docs/checklist-system-audit/ARCHITECTURE_NOTES.md` or an addition to this roadmap's appendix) stating: "`checklist exists` is not a valid proxy for `was genuinely delivered` — ~31% of delivered rental order products are administratively closed via this path." Any future report/dashboard/billing logic that assumes checklist-presence implies delivery must account for this.

**Files:** `app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php` (comment only), one new or amended doc file.

**Regression tests:** None — documentation/comment only, no behavior change.

**Rollback plan:** Trivial; comment/doc removal only. Risk: **none.**

**Deployment order:** Anytime, independent of everything else. Recommend bundling with PR-A1 since both touch `UpdateProductScheduleController.php`.

---

### PR-A6 — Fix Root Cause B: mobile delivery with an empty/omitted checklist array

**Priority:** Medium-High, but **explicitly gated, not immediate** · **Depends on:** PR-A4's telemetry (let the observability logging run in production for a review window — recommend 2–4 weeks — before deciding the exact fix shape) · **Blocks:** nothing

**Scope:** 44 of 875 rows (5%) are genuine mobile-side gaps: `SaveDeliveryController` is called with `checklist` omitted or empty, and still sets `delivery_status='Completed'`/`is_delivered=true` with zero checklist trail. `ISSUE5_INVESTIGATION_FINDINGS.md` explicitly recommends deferring the exact fix until PR-A4's telemetry shows whether 44-and-counting is a stable low background rate or an accelerating problem, since the fix shape (reject the request vs. mark a distinct "Incomplete" status vs. something else) should be informed by real frequency data, not decided speculatively.

**Action for this task now:** do not write the fix yet. Schedule a follow-up review after the PR-A4 telemetry window closes, using the accumulated log data to decide between (a) hard rejection, (b) a new non-"Completed" status value, or (c) accepting the current rate as tolerable and documenting it like Root Cause A. Convert this into a fully-scoped PR at that review point.

**Files:** TBD, pending the above decision.

**Regression tests:** TBD.

**Rollback plan:** N/A — not yet scoped.

**Deployment order:** Deferred; revisit after PR-A4 has been in production 2–4 weeks.

---

### Non-PR task — Data correction: mismatched `ChecklistMaster` (V10)

Not a code PR. `PHASE3_RESULTS.md` confirmed `ChecklistMaster` `CLM-5LNW-UPXG` (id 27, `equipment_category_id=23`) is paired with a Rental Ready template and a Customer Admin template that both actually belong to `equipment_category_id=8`, and 9 real equipment units (one currently rented) are assigned to it. This is a one-time admin data correction — reassign the master's templates or its category via the existing admin UI — not a schema or code change. Do this promptly since one affected unit is actively rented with the wrong checklist template attached; sequence it independent of any PR above.

---

## Track B — Structural / Consolidation Work

These address real, audit-confirmed architectural risks that are not (yet) active runtime bugs. Do after Track A lands.

### PR-B1 — Consolidate equipment↔ChecklistMaster assignment into one service method

**Priority:** High · **Depends on:** none, but informed by whatever PR-A6's review decides about mobile-side writes to the same equipment record

**Scope:** Three independent code paths currently write `equipment.checklist_master_id` with duplicated logic and no shared service: the single-equipment `AssignChecklistMasterController` (has a genuine duplicate-assignment guard), the `ChecklistMaster` edit screen's bulk "unassign everyone, then reassign" step (confirmed in `PHASE3_RESULTS.md` S5 to silently mass-unassign equipment set via other paths, with no warning), and `ChecklistMaster::delete()` (does not null `equipment.checklist_master_id` first, unlike its own `UpdateController`). Introduce a single service method (e.g., `ChecklistAssignmentService`) that all three paths call, so the unassign/reassign semantics are defined once and a confirmation/diff step can be added centrally.

**Files:**
- `app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/{AssignChecklist,Update,Delete}Controller.php`
- `app/Http/Controllers/Admin/MaintenanceManagement/Equipment/AssignChecklistMasterController.php`
- New: `app/Services/ChecklistManagement/ChecklistAssignmentService.php` (or similar)

**Regression tests:**
1. Existing single-equipment assign path still blocks duplicate assignment (regression guard for the one thing that already works correctly).
2. Bulk reassignment via the new service requires either an explicit confirmation flag or produces a diff of what will be unassigned (whichever the team decides during implementation) — test that equipment assigned via a *different* path is not silently dropped without at least a logged warning.
3. `ChecklistMaster::delete()` nulls `equipment.checklist_master_id` for all previously-assigned equipment before/at delete time.

**Rollback plan:** Behavior-preserving refactor for the two paths that already work; the bulk-unassign path gains new guard behavior that could be reverted independently if it blocks a legitimate admin workflow. Rollback risk: **low-moderate** — test the bulk path thoroughly since it's the one gaining new constraints.

**Deployment order:** Independent of Track A; sequence after Track A only for reviewer bandwidth reasons, not a technical dependency.

---

### PR-B2 — Reconcile the two divergent Rental Ready completion-calculation implementations

**Priority:** Medium · **Depends on:** none

**Scope:** The mobile API path (`RentalReadyChecklists\SaveController`) computes completion counts from validated, submitted answers (server-trusted); the admin-web path (`EquipmentManagement\StoreController`) trusts client-submitted counts verbatim and derives status from a raw `equipment_status` request field via an entirely different code path. These can disagree about status for the same underlying answers. Extract the mobile path's server-trusted calculation into a shared method/service and have the admin-web path call it instead of trusting client input.

**Files:**
- `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php` (~135-195, source of truth logic to extract)
- `app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/{Index,ChecklistQuestions,Store}Controller.php`

**Regression tests:**
1. Given the same set of submitted answers, both the mobile and admin-web paths now produce the same computed status (Draft/Rental Ready/Damaged).
2. `$hasDamaged` is still checked before `$allRentalReady` in the shared logic (preserve the one confirmed-correct business rule from Phase 2 §6 Rule 10).

**Rollback plan:** Revert restores admin-web's independent (bug-prone but currently "working") calculation. Rollback risk: **low.**

**Deployment order:** Independent; no dependency on Track A or other Track B items.

---

### PR-B3 — Re-enable or explicitly remove two commented-out validation guards

**Priority:** High (they were shipped disabled, meaning they represent intended-but-inactive checks) · **Depends on:** none

**Scope:** Two guards exist in live, routed controllers but are commented out: a required-questions-answered check in `RentalReadyChecklists/SaveController.php` (~161-173), and an invalid-equipment-status guard in `RentalReadyChecklists/IndexController.php` (~33-42). Decide per-guard whether to re-enable (if the original reason for disabling no longer applies) or delete (if it was disabled for a reason that still holds) — do not leave them as dead commented code either way.

**Files:**
- `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php`
- `app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php`

**Regression tests:** Depends on the re-enable/delete decision — if re-enabled, add a test proving the guard now rejects the invalid case it's meant to catch; if deleted, no test needed but note the decision in the PR description for future readers.

**Rollback plan:** Low risk either direction, but re-enabling a previously-disabled guard could reject requests a mobile client currently relies on succeeding — treat like PR-A4/A6's caution about unknown mobile client versions in the field. Investigate git blame/commit history for why it was disabled before deciding.

**Deployment order:** Independent.

---

## Track C — Cleanup (low urgency, low risk)

These are all independently shippable, small PRs. No dependencies between them or on Track A/B. Batch opportunistically.

| PR | Scope | Files | Priority |
|---|---|---|---|
| PR-C1 | Fix `insepectorSlect` typo silently nulling `employee_id` on first-time equipment inspections | `EquipmentManagement\StoreController.php` | High (confirmed data-integrity bug, trivial fix) |
| PR-C2 | Remove `dd()` debug call in error handler, replace with the standard flash-error redirect pattern used by sibling controllers | `CustomerAdmin\Question\StoreController.php` | High (production debug-dump exposure) |
| PR-C3 | Fix `unique_id` populated from numeric `id` instead of an actual unique-id string | `RentalReadyChecklistQuestions\ListResource.php` | Medium (likely breaks any client expecting a stable string id) |
| PR-C4 | Add `Rule::in()` enum constraints to `tnc_status`/`drivers_license_status`/`video_status`/`checklist_status` — confirmed write-only today, safe to tighten | `UpdateDeliveryPickupInputsRequest.php` | Medium (safe now, becomes harder to add once a reader depends on the current permissiveness) |
| PR-C5 | Standardize `{success,message}` vs `{status,message}` response envelopes across `DriverChecklistController` and `UpdateDeliveryPickupInputsController` | Both controllers | Low |
| PR-C6 | Decide fate of orphaned mockup screens (`customer_admin/templates/index.blade.php`, `customer_admin/question_and_categories/index.blade.php`) and unrouted `QuestionAndCategories` controllers (both Rental Ready and Customer Admin sides) — delete or finish, don't leave half-wired | Views + controllers listed in Phase 1 §14 | Low |
| PR-C7 | Add composite unique constraint on `rental_ready_checklist_template_questions` and `customer_admin_template_questions` (template_id, question_id) | New migration | Low (schema change — needs a data-dedup check first per `PHASE3_RESULTS.md` V6, which found 0 existing duplicates, so safe to add now) |
| PR-C8 | Add a real FK constraint to `customer_admin_templates.equipment_category_id` (currently a bare string) | New migration | Low (schema change — verify no non-numeric values exist first, per the caveat in `PHASE3_RUNTIME_VALIDATION_SQL_PLAN.md` V10) |
| PR-C9 | Add category-consistency validation between a `ChecklistMaster` and its selected templates at create/edit time | `ChecklistMaster\{Store,Update}Request.php` | Low (prevents recurrence of the V10-class mismatch going forward) |
| PR-C10 | Wire the dead "Customer Admin" button in `checklist_master/create.blade.php`; un-comment the working Copy button in `_table.blade.php` | Blade views | Low |

---

## Summary: Full Dependency & Order Graph

```
PR-A1 (equipment_status_logs) ─┬─> PR-A2 (transactions) ─┬─> PR-A3 (billing idempotency) ──[finance gate]──> prod deploy
                                │                          └─> PR-A4 (completeness logging) ──[telemetry window]──> PR-A6 (empty-checklist fix, scoped later)
PR-A5 (doc Root Cause A) ───────┘ (bundle with A1, no hard dependency)

[data task] Reassign ChecklistMaster #27 — independent, do promptly

Track B (PR-B1, B2, B3) — independent of Track A and each other; sequence after Track A for review bandwidth only
Track C (PR-C1 … PR-C10) — fully independent; batch opportunistically at any point
```

**Recommended sprint-level grouping:**
- **Sprint 1:** PR-A1 + PR-A5 (bundled), then PR-A2. Also land PR-C1 and PR-C2 as one quick side-batch (they're trivial and unrelated to sequencing).
- **Sprint 2:** PR-A3 (code/tests; production deploy held for finance sign-off) + PR-A4. Data task: reassign ChecklistMaster #27.
- **Sprint 3:** Track B (PR-B1, B2, B3) as bandwidth allows.
- **Ongoing/backlog:** Track C batched into any sprint with spare review capacity.
- **Revisit point:** 2–4 weeks after PR-A4 ships, review its telemetry and scope PR-A6 properly.

No code has been written or modified to produce this roadmap.
