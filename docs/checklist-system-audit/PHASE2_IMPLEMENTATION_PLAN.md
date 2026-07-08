# Checklist System — Phase 2 (Track B) Implementation Plan

**Date:** 2026-07-07
**Status:** Planning only. **No code has been modified to produce this document.**
**Source of truth:** `CHECKLIST_SYSTEM_AUDIT.md`, `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md`, `PHASE3_RESULTS.md`, `CORRECTION_PHASE1_PLAN.md`, `ISSUE5_INVESTIGATION_FINDINGS.md`, `IMPLEMENTATION_ROADMAP.md`, `PHASE1_RELEASE_NOTES.md` — plus fresh, direct re-verification of every file/line cited below against the current codebase (post Phase 1), since Phase 1 already touched some of these areas (e.g. `EquipmentStatusLog::recordTransition()`).
**Scope:** the 5 objectives given for Phase 2 / Track B. Everything else in `IMPLEMENTATION_ROADMAP.md` Track C, and PR-A6, remain explicitly out of scope — see §7.

---

## 0. How this maps to the 5 stated objectives

| # | Objective | PR |
|---|---|---|
| 1 | Consolidate checklist assignment logic into a single source of truth | **PR-B1** |
| 2 | Reconcile Rental Ready completion logic with Checklist completion logic | **PR-B2** |
| 3 | Review and restore/remove disabled validation guards | **PR-B3** |
| 4 | Reduce duplicated business logic where appropriate | **PR-B4** |
| 5 | Improve maintainability without changing business behavior | Cross-cutting — see each PR's "behavior change" call-out; only PR-B2 has a deliberate, flagged exception |

---

## PR-B1 — Consolidate Equipment ↔ ChecklistMaster Assignment

### Objective
Route all writes to `equipment.checklist_master_id` through one shared service, so assignment/unassignment logic (including its safety guards) is defined once, not three times.

### Root cause
Three independent, uncoordinated code paths write `equipment.checklist_master_id` directly via raw Eloquent calls, verified currently present:

1. **`app/Http/Controllers/Admin/MaintenanceManagement/Equipment/AssignChecklistMasterController.php`** (~lines 36-45) — single-equipment assign. **Has** a duplicate-assignment guard:
   ```php
   if ($equipment->checklist_master_id === $checklistMaster->id) {
       return response()->json(['success'=>false,'message'=>'This Checklist Master is already assigned...'],400);
   }
   $equipment->checklist_master_id = $checklistMaster->id;
   $equipment->save();
   ```
2. **`app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/StoreController.php`** (~lines 41-44) — bulk-assigns equipment on *creation* of a new master. **No guard at all** — can silently steal equipment already assigned to a different, existing master:
   ```php
   Equipment::whereIn('id', $equipmentIds)->update(['checklist_master_id' => $checklistMaster->id]);
   ```
3. **`app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/UpdateController.php`** (~lines 39-53) — the most dangerous: bulk *unassign-then-reassign* on every edit. **No guard.** Confirmed by live testing in `PHASE3_RESULTS.md` (S5) to silently mass-unassign equipment that was assigned via a *different* path, with zero warning:
   ```php
   Equipment::where('checklist_master_id', $ChecklistMaster->id)->update(['checklist_master_id' => null]);
   $equipmentIds = explode(',', $validated['equipment_ids']);
   Equipment::whereIn('id', $equipmentIds)->update(['checklist_master_id' => $ChecklistMaster->id]);
   ```

Separately (same root category of bug): `ChecklistMaster::delete()` does not null out `equipment.checklist_master_id` first, unlike `UpdateController`'s own category-change branch — equipment can end up pointing at a soft-deleted, invisible master.

Note: `app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/AssignChecklistController.php` is **not** a fourth write path — verified it's a view-loader only (renders the assign-equipment picker), not a writer.

### Current implementation
Three controllers, each independently building an `Equipment::where(...)->update([...])` or `$equipment->save()` call, no shared method, no audit trail of *assignment* changes (contrast with `current_status`, which now has `EquipmentStatusLog` from PR-A1 — there is no equivalent log for checklist-master assignment history).

### Target implementation
A single `ChecklistAssignmentService` (namespace TBD, suggest `app/Services/ChecklistManagement/`) exposing:
- `assignSingle(Equipment $equipment, ChecklistMaster $master, ?int $actorId): void` — used by `AssignChecklistMasterController`, keeping its existing duplicate-guard behavior.
- `bulkAssign(ChecklistMaster $master, array $equipmentIds, ?int $actorId, bool $unassignExisting = false): array` — used by both `StoreController` (create, `$unassignExisting = false`, nothing to unassign yet) and `UpdateController` (edit, `$unassignExisting = true`). Returns the list of equipment IDs that were unassigned as a side effect, so the caller can log or surface it.
- A corresponding null-out call wired into `ChecklistMaster::delete()` (or its `DeleteController`) to close the separate delete-time gap noted above.

All three controllers call the service instead of raw Eloquent writes. No change to the underlying data model or FK structure.

### Files expected to change
- New: `app/Services/ChecklistManagement/ChecklistAssignmentService.php`
- `app/Http/Controllers/Admin/MaintenanceManagement/Equipment/AssignChecklistMasterController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/StoreController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/UpdateController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/DeleteController.php` (or wherever the delete action lives)

### Risks
- **Behavior-change risk (needs stakeholder input — see §6):** if any admin currently relies on the bulk-reassign silently stealing equipment from another master as an (undocumented) accepted shortcut, adding a warning/guard could surprise them.
- Medium operational risk: these are live, daily-use admin equipment-onboarding controllers — a regression here blocks real staff work, not a background job.
- Low implementation risk: the service itself is a thin, mechanical wrapper around existing Eloquent calls.

### Dependencies
None blocking. Can run in parallel with PR-B3.

### Required regression tests
1. `AssignChecklistMasterController` still rejects duplicate assignment (unchanged behavior).
2. `StoreController` creating a master with `equipment_ids` assigns equipment correctly (happy path unchanged).
3. `UpdateController` bulk reassignment: equipment previously assigned via a *different* path and excluded from the new list gets unassigned **and** that side effect is now logged/returned (new, intentional behavior).
4. `ChecklistMaster::delete()` (or its controller) now nulls `checklist_master_id` on previously-assigned equipment.
5. No equipment ends up with an invalid/dangling `checklist_master_id` after any of the above (data-integrity assertion).

### Rollback strategy
Low risk. The service is purely additive; reverting means restoring the 3 raw-write call sites. No schema change, no data migration. If a new UI confirmation step is added per the stakeholder decision and turns out unwanted, that's a UI-only revert, not a data or logic rollback.

### Deployment considerations
No migration. **No mobile impact** — 100% admin-web surface; the mobile app never calls any of these three controllers. If a confirmation-dialog UX change is approved (§6), communicate it to admin/ops staff before release (training, not just a changelog entry).

### Estimated effort
**Medium — 2-3 engineer-days**, plus UI work if the confirmation-step option is chosen (add 1-2 days).

---

## PR-B2 — Reconcile Rental Ready Completion Logic

### Objective
One shared, server-trusted completion/status calculation used identically by the mobile API path and the admin-web path, so the two surfaces cannot disagree about an equipment's Rental Ready status for the same underlying answers.

### Root cause
Two independent implementations of the same completion math, verified currently present, with different trust models:

**Mobile path** — `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php` (~lines 135-152) computes counts **from the actual submitted answers**:
```php
$counts = [
    'total_questions' => collect($newQuestions)->count(),
    'required_items_completed' => collect($newQuestions)->filter(...)->filter(fn($q)=>data_get($q,'selected_answer.type')==='Rental Ready')->count(),
    ...
];
```
Status is derived server-side (~lines 177-195) from `$hasDamaged`/`$allRentalReady` flags computed from the same data — trustworthy, not client-supplied.

**Admin-web path** — `app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/StoreController.php` (~lines 48-55) **trusts client-submitted counts verbatim**:
```php
$counts = [
    'total_questions' => data_get($qaPayload, 'counts.total_questions', 0),
    'required_items_completed' => data_get($qaPayload, 'counts.required_items_completed', 0),
    ...
];
```
and derives status from a **raw, unvalidated request field** (~lines 28-35):
```php
$status = $request->input('equipment_status') ?? 'maintenance';
$templateStatus = match ($status) { 'available'=>'Rental Ready', 'damaged'=>'Damaged', 'maintenance'=>'Draft', default=>'Draft' };
```
`Equipment.current_status` is then set directly from that same client-controlled field (~lines 293-297).

### Current implementation
Two controllers, two independent code paths, no shared logic — one server-trusted, one client-trusted.

### Target implementation
Extract the mobile path's calculation into a shared, stateless service — e.g. `RentalReadyCompletionCalculator::calculate(array $questions): array` returning `{counts, hasDamaged, allRentalReady, status}` — and have **both** controllers call it, computing from the actual submitted question/answer payload rather than trusting client-supplied counts or a raw status string.

**Bundled opportunistic fix (same file, same PR):** `EquipmentManagement/StoreController.php` line ~199 reads a typo'd request key (`insepectorSlect` instead of `inspectorSelect`) on the "create new template" branch, silently nulling `employee_id` on every first-time equipment inspection through the admin UI. One-line fix, included here because it's the same file under review, not because it's logically related to the completion-calculation change.

### Files expected to change
- New: `app/Services/ChecklistManagement/RentalReadyCompletionCalculator.php`
- `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php` (refactor to call the extracted service — behavior-preserving, since this is the source of the extracted logic)
- `app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/StoreController.php` (behavior **change** — see below — plus the `insepectorSlect` typo fix)

### Risks
- **This is the one deliberate business-behavior change in Phase 2.** Every other PR in this plan targets pure consolidation with no output change. If admin-web staff currently rely on manually overriding computed status via the raw `equipment_status` field for a legitimate operational reason not visible in the code (e.g., closing out an inspection despite one unanswered question, for reasons staff judge acceptable), removing that trust model could block a workflow they depend on today. **Requires a stakeholder/product decision before implementation — see §6.**
- Medium technical risk: the two controllers' request payload shapes are not identical; reconciling them into one calculator input format needs careful verification during implementation, not just a visual read.

### Dependencies
**Should land after PR-B3's decision on the commented-out "required questions answered" guard** in this same `SaveController.php` file — better to design the shared calculator once, with the final validation behavior baked in, than to build it twice.

### Required regression tests
1. Mobile `SaveController`'s existing behavior is unchanged after the extraction (same inputs → same outputs as today).
2. Admin-web `StoreController` now computes status from actual submitted answers, not the raw `equipment_status` field — submit answers that would legitimately compute "Damaged" while sending `equipment_status=available`; assert the system does **not** record "Rental Ready".
3. `$hasDamaged` still takes precedence over `$allRentalReady` in the shared calculator (preserves the one already-confirmed-correct rule from the original audit).
4. Typo-fix regression: submit a first-time inspection via the admin-web "create new template" branch; assert `employee_id` is now correctly populated (this test fails today, proving the bug, and should pass after the fix).

### Rollback strategy
**Medium risk.** If the admin-web behavior change breaks a real staff workflow in production, rollback means restoring the old client-trust code path, not just deleting an addition. **Recommend a staged rollout mirroring PR-A4's "observe before enforce" pattern**: log a warning when the client-submitted status/counts disagree with the server-computed values for a review window, before actually switching the admin-web path to use the server-computed values as the source of truth. This turns an irreversible-feeling behavior change into a two-step, reversible one.

### Deployment considerations
No migration. No direct mobile impact from the calculator extraction itself (mobile behavior is preserved by construction). If the admin-web behavior change is approved and implemented, it indirectly affects equipment availability data the mobile app later reads (equipment marked available/unavailable) — communicate to admin/ops staff before deploying that specific change.

### Estimated effort
**Medium-High — 4-6 engineer-days**, dominated by the stakeholder-decision turnaround and, if approved, building the staged (observe-then-enforce) rollout rather than a single-step change.

---

## PR-B3 — Review and Resolve the 2 Disabled Validation Guards

### Objective
For each of the two commented-out validation guards still sitting in shipped, live-routed controllers, make an explicit decision — re-enable or delete — rather than leaving dead, ambiguous validation code indefinitely.

### Root cause
Both confirmed still present as dead comments:

1. **`app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php`** (~lines 160-173) — a "required questions answered" check:
   ```php
   // $anyAnswerMissing = collect($newQuestions)->contains(function ($q) { return empty($q['selected_answer']); });
   // if ($anyAnswerMissing) { return response()->json([...'all_questions_unanswered'...], 422); }
   ```
2. **`app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php`** (~lines 33-42) — an "invalid equipment status" guard:
   ```php
   // if ($equipment && $equipment->orderProduct &&
   //     ($equipment->current_status->isRented() || $equipment->current_status->isAvailable())) {
   //     return response()->json(['success'=>false,'message'=>trans('...invalid_equipment_status')], 404);
   // }
   ```

### Current implementation
Both exist as commented-out blocks; neither check runs today. No record was found (in the docs audited) of *why* either was disabled.

### Target implementation
Per guard, independently:
1. Run `git log -p` / `git blame` on the surrounding lines to find the commit that disabled it and its message/PR context — understand *why* before deciding.
2. Decide: re-enable as-is, re-enable with modification, or delete permanently. This decision belongs with product/ops, informed by (not dictated by) the git history — see §6.
3. Implement the decision. If re-enabling, use the same observability-first staging pattern as PR-A4/PR-B2 rather than flipping straight to hard enforcement, since this is mobile-facing (see §6.2, "areas that could affect the mobile application").
4. If deleting, this is pure dead-code removal with no behavior change — no special rollout needed.

### Files expected to change
- `app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php`
- `app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php`

### Risks
- **If re-enabled, both are potential mobile-breaking changes** — rejecting requests the current mobile app relies on succeeding, with no advance warning unless staged.
- If the decision is "delete" for either, risk is near-zero.
- The git-archaeology step may not conclusively explain the original disabling — flag ambiguity honestly rather than guessing a rationale.

### Dependencies
**Should run early in Phase 2** — its outcome for the `SaveController.php` guard directly informs PR-B2's shared calculator design (see PR-B2 §Dependencies).

### Required regression tests
- If re-enabled: a test proving the guard now rejects the case it's meant to catch (e.g., missing required answers → 422), plus a regression test that a genuinely complete submission still succeeds unchanged.
- If deleted: no functional test needed; document the decision and rationale in the PR description (mirrors PR-A5's documentation-only pattern from Phase 1).

### Rollback strategy
Low-to-medium. If re-enabled and it turns out to reject legitimate mobile traffic in production, rollback is a fast revert to the permissive (commented-out) state — low technical risk, but potentially real business impact (failed mobile submissions) if not caught quickly. **Recommend the same observability-first rollout used in PR-A4**: log what *would* have been rejected for a review window before actually enforcing.

### Deployment considerations
**This is the clearest mobile-impact item in Phase 2** if either guard is re-enabled as a hard rejection — see §6.2. No migration needed either way.

### Estimated effort
**Low for investigation + delete-only outcome (1-2 days).** **Medium-High (add 3-5 days) if either guard is re-enabled**, to build the staged observability-then-enforce rollout and its own follow-up enforcement decision point (structurally identical to how PR-A6 was deferred after PR-A4).

---

## PR-B4 — Reduce Duplicated CRUD Business Logic (Rental Ready ↔ Customer Admin)

### Objective
Reduce (not eliminate) the near-line-for-line duplicated controller logic between the Rental Ready and Customer Admin CRUD stacks, so a future bug fix doesn't need to be applied twice — without merging the two domains into one data model (that is a larger, later-phase decision — see §7).

### Root cause
Both trees implement the identical logical shape (Category → Question → Answer, Template → TemplateQuestion) as two entirely separate, independently-coded controller families with zero shared code. Confirmed: 13 near-identical controllers per tree (26 total) — `Categories/{Store,Update,Delete}Controller.php`, `Question/{Store,Update,Delete,Copy}Controller.php`, `Templates/{Store,Update,Delete,Copy,Index}Controller.php`, plus a top-level `IndexController.php` each. Verified representative pair: `Categories/StoreController.php` on the Rental Ready side creates a `RentalReadyChecklistCategory` and, if `create_customer_folder` is checked, mirrors into `CustomerAdminCategory`; the Customer Admin side is the exact inverse. Logic, transaction handling, flash messages, and error handling are otherwise identical.

### Current implementation
26 independent controllers, no shared base class or trait, the cross-tree "mirror" checkbox logic duplicated in both directions.

### Target implementation
**Deliberately conservative for Phase 2** — logic-sharing only, not data-model unification:
- Extract the genuinely identical pieces (validation-rule shape, transaction handling, flash-message patterns, the cross-tree mirror logic) into shared traits — e.g. a `MirrorsToOppositeChecklistTree` trait used by both `Categories/StoreController` variants.
- **Explicitly do not** attempt to unify the underlying Eloquent model trees (`RentalReadyChecklistCategory` vs. `CustomerAdminCategory`, etc.) into one polymorphic/shared table. That is a schema-level change requiring its own dedicated audit and stakeholder sign-off — out of scope here, see §7.
- Land as **several smaller PRs, one per CRUD resource** (Categories, then Questions, then Templates) rather than one 26-file PR — see Rollback strategy.

### Files expected to change
Illustrative — exact set finalized after a line-by-line diff pass across all 26 controllers during implementation kickoff:
- New: `app/Http/Controllers/Admin/ChecklistManagement/Concerns/*` (shared traits)
- All 26 controllers under `RentalReady/{Categories,Question,Templates}/*` and `CustomerAdmin/{Categories,Question,Templates}/*`

### Risks
- **Highest blast-radius PR in Phase 2 by file count**, even though each individual change should be small and mechanical.
- Risk of subtle behavior drift if the two trees' "identical" logic actually has small, undocumented differences a mechanical extraction misses — requires careful side-by-side diffing, not just visual-similarity judgment.
- **Zero existing test coverage** for most of these admin CRUD controllers today (confirmed in the original Phase 1/2 audit) — this PR effectively requires writing baseline characterization tests *first*, before any refactor, to have confidence the extraction didn't change behavior.

### Dependencies
Should run **last** in Phase 2 — highest effort, highest file count, and benefits from the team having already re-established a testing rhythm on this codebase via B1-B3 (and Phase 1).

### Required regression tests
1. Baseline characterization tests for all 26 controllers' *current* behavior, written **before** refactoring (create/update/delete for category/question/template on both trees, including the cross-tree mirror checkbox) — these don't exist today and must be added regardless of whether the refactor proceeds.
2. After extraction: the same tests must pass unchanged, proving the trait extraction was behavior-preserving.

### Rollback strategy
Low technical risk per individual controller (each is a small, mechanical, reversible diff), but rolling back a single 26-file PR is operationally tedious. **Recommend splitting into 3 smaller PRs (one per CRUD resource)** specifically so a problem in one slice doesn't block or force rollback of the others.

### Deployment considerations
No migration. **No mobile impact** — 100% admin-web CRUD surface, never touched by the mobile app. Pure internal refactor with no visible behavior change if done correctly — which is exactly what the baseline characterization tests exist to prove.

### Estimated effort
**High — 6-10 engineer-days**, dominated by writing baseline tests for previously-untested code, not by the refactor itself.

---

## 5. Updated Dependency Graph

```
PR-B3 (guard investigation + decision) ──────┐
                                              ├──> PR-B2 (shared completion calculator)
PR-B1 (assignment consolidation) ── [independent, no dependency] ── can run in parallel with B3

PR-B4 (CRUD duplication reduction) ── depends on nothing technically, but sequenced last
                                       to run after B1-B3 are stable (team bandwidth / risk
                                       management, not a hard code dependency)

[Stakeholder decisions gate the START of implementation for B1's UX step, all of B2's
 behavior-change branch, and B3's re-enable-vs-delete choice per guard — see §6]
```

**No dependency on Phase 1 PRs remains open** — Phase 1 (A1-A5) is closed and merged; Track B builds on top of it (e.g., PR-B1's audit-trail gap parallels the now-fixed `EquipmentStatusLog` pattern from PR-A1, and PR-B3's staged-rollout recommendation directly reuses PR-A4's observe-before-enforce pattern).

---

## 6. Items Requiring Stakeholder Approval Before Implementation

1. **PR-B1:** should the bulk "unassign then reassign" workflow in `ChecklistMaster\UpdateController` gain a confirmation/warning UI step (a genuine UX change), or is silent logging of the side effect sufficient? Needs UX/product input, not an engineering default.
2. **PR-B2:** should the admin-web Rental Ready inspection flow stop trusting client-submitted completion counts/status and instead be fully server-computed? This is a real business-behavior change that could affect an existing staff override workflow not visible in the code. Needs product/ops sign-off before implementation begins, not just engineering judgment. **Recommend presenting the staged (observe-then-enforce) option as the default ask**, matching Phase 1's PR-A4 pattern.
3. **PR-B3:** for each of the 2 disabled guards, re-enable (a mobile-facing behavior change) or delete (accepting today's permissive behavior permanently)? A product decision about desired mobile behavior, informed by — not decided by — the git-history investigation.
4. **PR-B4:** explicit confirmation that a full model/schema-level unification of the Rental Ready and Customer Admin trees is **not** wanted for Phase 2 (logic-sharing only). Worth a deliberate sign-off so scope doesn't quietly expand mid-implementation.

### 6.2 Areas that could affect the mobile application
- **PR-B3, both guards, if re-enabled** — the highest-risk, most direct mobile impact in this phase. A newly-enforced rejection could break real in-flight mobile traffic with no warning unless staged.
- **PR-B2's shared calculator** — should be zero mobile impact if the extraction is purely mechanical (mobile's `SaveController` behavior is the *source* of the extracted logic), but this must be verified by regression test #1 under PR-B2, not assumed.
- **PR-B1 and PR-B4** — no mobile impact. Both are 100% admin-web surfaces; the mobile app does not call any of the files touched by either PR.

### 6.3 Database changes that may become necessary
- **None required for Phase 2 as scoped.** All four PRs are logic-only consolidations against the existing schema — no migrations planned.
- **Possible future Phase 3 item, explicitly not part of this plan:** if PR-B4's implementation-time investigation finds the two-tree duplication severe enough to justify true model unification (not just logic-sharing), that would require a schema migration merging `rental_ready_checklist_categories`/`customer_admin_categories` (and the parallel question/answer/template tables) into a shared, type-discriminated structure. Flagging so it isn't lost, not proposing it now.
- Also carried over from `IMPLEMENTATION_ROADMAP.md` Track C (unchanged, still not part of Phase 2): the missing FK on `customer_admin_templates.equipment_category_id`, and missing composite unique constraints on the template↔question join tables. Both require migrations and remain explicitly out of scope until a Track C pass is scheduled.

### 6.4 Areas that should remain out of scope until Phase 3
- Full schema/model unification of the Rental Ready and Customer Admin trees (PR-B4's target implementation explicitly excludes this).
- The two orphaned Customer Admin mockup screens and the two unrouted `QuestionAndCategories` controllers (`IMPLEMENTATION_ROADMAP.md` Track C, unrelated to any of the 5 Phase 2 objectives).
- API response-envelope standardization (`{success}` vs. `{status}`) across endpoints (Track C).
- Composite unique constraints / FK schema-hygiene items (Track C — all require migrations, see §6.3).
- **PR-A6** (completeness enforcement) — still gated on Phase 1's PR-A4 telemetry review window; unrelated to Track B and not accelerated by this plan.
- The deferred 44-row genuine mobile-checklist-omission gap (`ISSUE5_INVESTIGATION_FINDINGS.md`) — same telemetry gate as PR-A6.

---

## 7. Recommended PR Sequence

**Revised to a strict sequence (not parallel B1/B3) per stakeholder review.** Although PR-B1 and PR-B3 have no *code* dependency on each other, running them strictly in order — smallest/lowest-risk first — keeps only one architectural change in flight at a time, which matters more here than shaving a few days off the calendar: it keeps each PR easy to reason about in isolation and reduces the chance that an unrelated regression surfaces while two consolidation efforts are being validated simultaneously.

| Order | PR | Rationale |
|---|---|---|
| 1 | **PR-B3** — guard investigation & decision | Smallest scope, cheapest to start (git archaeology + a decision). Its outcome also feeds PR-B2's design, so resolving it first removes a downstream unknown. |
| 2 | **PR-B1** — assignment consolidation | Introduces the single `ChecklistAssignmentService`; behavior stays unchanged unless D1's approved decision requires the confirmation-step UX. |
| 3 | **PR-B2** — shared completion calculator | The only Phase 2 PR with a deliberate business-behavior change (D2) — deliberately sequenced after the lower-risk consolidation work in B3/B1, not alongside it. |
| 4 | **PR-B4** — CRUD duplication reduction | Highest effort, highest file count; benefits from the testing rhythm re-established by B3/B1/B2, and split internally into 3 sub-PRs (Categories, Questions, Templates). |

All four decisions in `PHASE2_DECISION_MATRIX.md` (D1-D5) should be resolved before PR-B1 begins — D3/D4 gate PR-B3 specifically, but D1, D2, and D5 are cheap to decide early and gate B1/B2/B4 respectively, so there's no reason to resolve them one PR at a time as each comes up.

---

## 8. Estimated Timeline

**How to communicate this externally:** state engineering effort and calendar risk as two separate numbers, not one combined promise. Several items in this timeline depend on stakeholder decisions and staged-observation windows that engineering does not control the pace of — bundling them into a single date range implies a commitment the team can't actually guarantee.

> **Engineering work: approximately 2-4 weeks.**
> **Overall calendar: may extend beyond that** if product/operations decisions (§6 / `PHASE2_DECISION_MATRIX.md`) take longer to land, or if a staged observation period (D2, and D3/D4 if a guard is re-enabled) runs longer than the minimum review window before an enforcement decision is made.

Internal planning detail behind that external framing:

| PR | Engineering effort | Calendar considerations |
|---|---|---|
| PR-B3 | 1-2 days (delete-only outcome) to 5-7 days (re-enable + staged rollout) | Git archaeology + D3/D4 decision turnaround before implementation starts |
| PR-B1 | 2-3 days, +1-2 days if the D1 confirmation-step UX is approved | D1 can be decided in parallel with PR-B3's implementation, since B1 runs second |
| PR-B2 | 4-6 days engineering + staged-rollout follow-up | Blocked on D2 before implementation starts; do not begin coding until that decision is made |
| PR-B4 | 6-10 days, split across 3 sub-PRs | Mostly bounded by baseline-test-writing effort, not refactor complexity; D5 should be confirmed before this starts |

Total engineering effort across all four PRs: roughly 13-24 days of work — but per the framing above, present this internally as a planning range, not as the external commitment. The external commitment is the 2-4 week engineering estimate with an explicit calendar caveat.

**Suggested calendar-week breakdown**, per the strict B3 → B1 → B2 → B4 sequence in §7:
- **Week 1:** All of D1-D5 requested from stakeholders immediately (cheap to decide early, even though only D3/D4 block the first PR). PR-B3 investigation begins in parallel.
- **Week 2:** PR-B3 implementation completes and ships (or enters its observability window if a guard was re-enabled per D3/D4). PR-B1 implementation begins once D1 has landed.
- **Week 3:** PR-B1 completes and ships. PR-B2 implementation begins once D2 has landed.
- **Week 4-5:** PR-B2 completes and ships (or enters its observability window per D2). PR-B4's baseline-test-writing phase begins once D5 is confirmed.
- **Week 5-6:** PR-B4's 3 sub-PRs (Categories, Questions, Templates) implemented and shipped sequentially.

If any staged observation window (PR-B2 or PR-B3, depending on D2/D3/D4) needs to run for its own multi-week review period before an enforcement decision, that extends the overall calendar beyond week 6 — the engineering work itself would be done, but the milestone (full enforcement decided) would not be closed yet.

---

## 9. Explicit Non-Goals for This Planning Document

- No code was written, modified, or refactored to produce this plan.
- PR-B1 was not started.
- This plan does not itself constitute stakeholder approval for any of the §6 items — it identifies what needs a decision and by whom (product/ops/UX, not engineering alone), and each PR's implementation should not begin until its corresponding decision (if any) is made.
