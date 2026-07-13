# Checklist System — Phase 2 Decision Matrix

**Date:** 2026-07-07
**Status:** Planning only. **No code has been modified to produce this document.**
**Source:** extracted and expanded from `PHASE2_IMPLEMENTATION_PLAN.md` §6. That plan identified 4 stakeholder-approval items; this matrix splits PR-B3's two independent guards into separate decisions (D3 and D4), since each has its own file, its own history, and could be decided differently from the other — 5 decisions total.
**Purpose:** a single place for non-engineering stakeholders (product/ops/UX) to review and decide, without needing to read the full implementation plan. Engineering should not begin implementation on the affected PR until its decision below is recorded.

---

## Summary table

| # | Decision | Affects | Business impact if changed | Risk if left unchanged | Recommendation |
|---|---|---|---|---|---|
| D1 | Warn before bulk equipment unassignment? | PR-B1 | Low — one extra admin click | Medium — silent equipment loss, discovered only when a delivery/inspection later fails | **Add the warning** |
| D2 | Server-compute Rental Ready status instead of trusting admin-web client input? | PR-B2 | Medium — may block an undocumented staff override workflow | High — admin-web and mobile can disagree about whether equipment is safe to rent | **Observe first, enforce later** |
| D3 | Re-enable "required questions answered" guard (mobile)? | PR-B3 | High — could reject real in-flight mobile submissions | Medium — incomplete rental-ready inspections pass silently today | **Observe first, enforce later** |
| D4 | Re-enable "invalid equipment status" guard (mobile)? | PR-B3 | High — could reject real in-flight mobile submissions | Low-Medium — unclear how often the invalid state actually occurs | **Investigate git history first, then decide** |
| D5 | Confirm NO full schema/model unification in Phase 2? | PR-B4 | None if confirmed as scoped; High if silently expanded | Low — but scope creep risk if not confirmed explicitly | **Confirm: logic-sharing only, no schema change** |

---

## D1 — Warn before bulk equipment unassignment in `ChecklistMaster` edit?

**Where:** `ChecklistMaster\UpdateController.php`, the bulk "unassign everyone, then reassign" step that runs on every edit of a Checklist Master.

### Current behavior
When an admin edits a Checklist Master and submits a new equipment list, the system silently unassigns **every** piece of equipment currently pointing at that master — including equipment that was assigned via a completely different screen (`AssignChecklistMasterController`'s single-equipment picker) — and reassigns only the equipment in the new list. No warning, no confirmation, no diff shown. Confirmed via live testing (`PHASE3_RESULTS.md` S5): 6 of 7 previously-assigned equipment units were silently unassigned in a single test edit, one of which represented an independently-assigned unit.

### Proposed behavior
Before committing the bulk unassign, show the admin a diff/confirmation: "This will unassign N pieces of equipment currently using this checklist master: [list]. Continue?" — or, at minimum, log the side effect so it's discoverable after the fact even without a UI change.

### Business impact
**Low if implemented, but currently invisible.** Adding a confirmation step costs staff one extra click on an already-infrequent admin action (editing a Checklist Master's equipment list). The upside is preventing a real, already-reproduced failure mode: equipment silently losing its checklist assignment, which surfaces later as a confusing "why does this equipment have no checklist" support/ops question rather than an immediate, attributable error.

### Technical impact
Small. The `ChecklistAssignmentService` (PR-B1) already needs to compute "which equipment will be unassigned" to implement the safer bulk-reassign method — surfacing that same list to a confirmation dialog is incremental UI work on top of already-planned backend work, not a separate large effort.

### Risk if unchanged
**Medium.** This is not hypothetical — it already happened in a controlled test. In production, an admin editing one Checklist Master could unknowingly strip checklist coverage from equipment that was carefully assigned elsewhere, and nobody would know until that equipment's next inspection or delivery fails for an unrelated-seeming reason.

### Recommendation
**Add the confirmation/warning step.** The cost is trivial (one dialog, infrequent screen) and the downside it prevents has already been demonstrated. If a full UI change can't be scheduled immediately, ship the logging-only version first (already planned in PR-B1 regardless) and follow with the UI confirmation as a fast-follow — but don't treat logging alone as the final answer, since a log entry nobody reads doesn't prevent the mistake, only explains it after the fact.

---

## D2 — Should admin-web Rental Ready inspections stop trusting client-submitted completion status?

**Where:** `EquipmentManagement\StoreController.php` (admin-web Rental Ready inspection save), compared against `RentalReadyChecklists\SaveController.php` (mobile).

### Current behavior
The mobile app's inspection submission has its completion counts and Rental Ready/Damaged/Draft status **computed server-side** from the actual submitted answers — the server does not trust the client's claims. The admin-web inspection screen does the opposite: it accepts whatever `counts.*` values and `equipment_status` string the browser sends and writes them directly, with no server-side recomputation from the underlying answers. **The two surfaces can therefore disagree** about whether the same set of answers means "Rental Ready" — a real business-behavior inconsistency, not just a code-style one.

### Proposed behavior
Extract the mobile path's proven, answer-driven calculation into a shared service, and have the admin-web path call it too — computing status from the actual submitted answers instead of trusting the browser's claimed counts/status field.

### Business impact
**Potentially medium, and this is the crux of the decision.** If admin-web staff have ever used the current trust-the-client behavior deliberately — for example, closing out an inspection as "Rental Ready" despite one technically-unanswered question, because a staff member visually confirmed the equipment was fine — switching to fully server-computed status would block that judgment call going forward. Engineering cannot see from the code whether this override behavior is actually used, or how often; only ops/product can answer that.

### Technical impact
Medium. The admin-web and mobile payload shapes differ today and need reconciling into one calculator input format. Not a rewrite, but not a trivial find-and-replace either.

### Risk if unchanged
**High, and already partially realized.** Because status can silently diverge between the two entry points, equipment could be marked "Rental Ready" on one surface while the same underlying answers would have computed "Damaged" or "Draft" on the other. Since equipment status directly gates whether it's rentable, this is a real operational/safety-adjacent inconsistency, not a cosmetic one.

### Recommendation
**Do not flip straight to hard enforcement.** Recommend the same pattern already proven in Phase 1's PR-A4: ship the shared calculator, but for an initial period only **log** a warning when the admin-web-submitted status/counts disagree with what the server would have computed — don't change what gets written yet. Review that telemetry, then decide (with product/ops, informed by real frequency data) whether to switch admin-web to fully server-computed status, add a "staff override, with reason" explicit escape hatch, or something else. This mirrors exactly how PR-A4 was deliberately scoped as observation-only before PR-A6's enforcement decision.

---

## D3 — Re-enable the "required questions answered" guard in the mobile Rental Ready save flow?

**Where:** `RentalReadyChecklists\SaveController.php` (~lines 160-173), commented out.

### Current behavior
A mobile rental-ready inspection can be submitted and saved even if some questions have no selected answer. The code that would reject such a submission (`all_questions_unanswered`, HTTP 422) exists but is commented out — it does not run.

### Proposed behavior
Re-enable the guard so an incomplete inspection is rejected at submission time, forcing the mobile client to either complete it or handle the rejection.

### Business impact
**High, and uncertain in direction.** If the current mobile app version already guarantees completeness client-side before submitting (likely, since this looks like a server-side guard that was disabled after client-side validation was added — but this is a guess, not confirmed), re-enabling costs nothing in practice. If any mobile client version in the field can still submit incomplete inspections (e.g., an older app version, a client bug, or an intentional "save partial progress" feature), re-enabling would start rejecting those submissions with no warning.

### Technical impact
Low to re-enable (the code already exists, commented). The real cost is in *how* it's rolled out safely (see recommendation).

### Risk if unchanged
**Medium.** Incomplete rental-ready inspections can currently be saved and treated as done, meaning equipment could be marked available/ready based on a partially-answered inspection. Lower urgency than D2 because this is at least an explicit, known gap (not a silent cross-surface disagreement), and CORRECTION_PHASE1_PLAN.md already treated the equivalent gap on the customer-checklist side (PR-A4) as "log, don't block" for exactly this reason.

### Recommendation
**Observe before enforcing**, same as D2: before re-enabling as a hard rejection, add logging (or reuse the PR-A4 pattern directly) to see how often a real mobile submission today would have been rejected by this guard. If the answer is "never" or "extremely rarely" over a reasonable window, re-enabling is low-risk. If it's common, that's a sign the guard's original disabling may have been a deliberate, still-valid response to real client behavior — worth understanding via git history (see D4's approach) before flipping it back on.

---

## D4 — Re-enable the "invalid equipment status" guard in the Rental Ready list endpoint?

**Where:** `Api\Admin\V1\RentalReadyChecklists\IndexController.php` (~lines 33-42), commented out.

### Current behavior
The endpoint that lists rental-ready checklist questions for a piece of equipment does not check whether that equipment is currently in a state (rented, available) where starting a new inspection wouldn't make sense. The guard that would reject such a request exists but is commented out.

### Proposed behavior
Re-enable the guard so the endpoint returns 404 (or a more specific error) when equipment is in an invalid state for a new inspection.

### Business impact
**High, same category of risk as D3** — a mobile client could be relying on being able to call this endpoint in a state the guard would now reject, with no advance warning.

### Technical impact
Low to re-enable. As with D3, the risk is in the rollout method, not the code change itself.

### Risk if unchanged
**Low-Medium.** Unlike D3 (which affects data integrity of a saved inspection), this guard is about *listing* questions for an inspection that arguably shouldn't be starting — a UX/workflow correctness issue more than a data-integrity one. It's unclear from static reading how often equipment is actually in the "invalid" state when this endpoint is called in practice.

### Recommendation
**Start with git history, not a rollout plan.** Unlike D3 (where an observe-then-enforce staged rollout is clearly the right default), D4's guard condition (equipment currently rented or available) suggests it may have been disabled because it was actively *wrong* or too broad at the time — worth understanding the original commit's context before assuming re-enabling is even the right target state. If the history is inconclusive, fall back to the same observe-first approach as D3.

---

## D5 — Confirm no full schema/model unification of Rental Ready and Customer Admin trees in Phase 2

**Where:** applies to PR-B4 (CRUD duplication reduction) as a whole.

### Current behavior
Rental Ready and Customer Admin are two separate, fully-independent Eloquent model/table trees (`RentalReadyChecklistCategory`/`CustomerAdminCategory`, etc.) that happen to share an identical logical shape. PR-B4 as planned only shares *controller logic* (via traits) between them — it does not touch the underlying data model.

### Proposed behavior
No proposed change here — this decision is a **confirmation**, not a change. The question for stakeholders is whether Phase 2 should stay scoped to logic-sharing only, explicitly ruling out a deeper schema-level merge of the two trees into one shared, type-discriminated model for this phase.

### Business impact
**None if confirmed as scoped.** A schema-level merge would be a materially larger, riskier undertaking (data migration, dual-write/backfill period, re-testing every consumer of both trees) that was never part of what Track B asked for. Confirming the boundary now prevents mid-implementation scope expansion.

### Technical impact
None for Phase 2 if confirmed. If a future phase later decides to pursue the deeper merge, that becomes its own dedicated audit and plan — not a Phase 2 add-on.

### Risk if unchanged (i.e., if this boundary is never explicitly confirmed)
**Low probability, but high cost if it happens:** without an explicit sign-off, there's a real risk that mid-implementation, someone (engineering or a stakeholder) suggests "since we're already in here, why not merge the tables" — which would blow out PR-B4's estimated effort and risk profile significantly, and turn a maintainability PR into a data-migration project without anyone having deliberately decided to take that on.

### Recommendation
**Confirm explicitly, in writing, before PR-B4 begins:** Phase 2 is logic-sharing only. A schema-level merge, if ever pursued, is Phase 3-or-later work requiring its own audit and plan.

---

## Decision log (to be filled in by stakeholders)

| # | Decision | Decided by | Date | Outcome |
|---|---|---|---|---|
| D1 | Warn before bulk unassignment? | Client (Phase 2 approval) | 2026-07-08 | Approved recommended option — implemented as `api_errors` log warning in PR-B1 (`ChecklistAssignmentService::bulkAssign()`); UI confirmation dialog explicitly deferred as a fast-follow, see `PR-B1_ASSIGNMENT_CONSOLIDATION.md` §5 |
| D2 | Server-compute admin-web Rental Ready status? | | | |
| D3 | Re-enable required-questions guard? | | | |
| D4 | Re-enable invalid-equipment-status guard? | | | |
| D5 | Confirm no schema unification in Phase 2? | | | |

No PR in `PHASE2_IMPLEMENTATION_PLAN.md` should begin implementation ahead of its corresponding decision(s) above being recorded in this log.

No code has been modified to produce this document.
