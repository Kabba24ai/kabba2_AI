# Checklist System Correction — Milestone Tracker

**Last updated:** 2026-07-11
**Purpose:** a quick, high-level status view for both engineering and the client, without needing to open the full technical documents. Update the status column as each milestone moves — don't let this drift out of sync with reality.

---

| Milestone | Status | Notes |
|---|---|---|
| Audit (Phases 1-3 + Issue #5 investigation) | ✅ Complete | `CHECKLIST_SYSTEM_AUDIT.md` → `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` → `PHASE3_RESULTS.md` → `ISSUE5_INVESTIGATION_FINDINGS.md` |
| Phase 1 implementation (PR-A1 – A5) | ✅ Complete | Tagged `checklist-system-phase1-complete`. See `PHASE1_RELEASE_NOTES.md` for full detail. |
| Phase 1 finance reconciliation (PR-A3 production gate) | ⏳ Pending | Owned by finance/accounting, not engineering. Blocks PR-A3's *production* deploy only — staging is unaffected. |
| Phase 1 telemetry window (gates PR-A6) | ⏳ Pending | PR-A4 logging needs 2-4 weeks of production data before PR-A6 (enforcement) can be scoped. Not started until Phase 1 is deployed to production. |
| Phase 2 planning (Track B) | ✅ Complete | `PHASE2_IMPLEMENTATION_PLAN.md` |
| Phase 2 decision matrix (engineering detail) | ✅ Complete | `PHASE2_DECISION_MATRIX.md` |
| Phase 2 decision sheet sent to client | ✅ Complete | `PHASE2_DECISION_MATRIX_SUMMARY.md` |
| Phase 2 decisions approved (D1-D5) | ✅ Complete | Client approved Phase 2 as recommended. D1, D3, D4 individually re-confirmed in the decision log as each PR consumed them. |
| PR-B3 — Review disabled validation guards | ✅ Complete | `PR-B3_VALIDATION_GUARDS.md` — both guards resolved (one staged as logging, one's obsolete half removed, one's live half staged as logging) |
| PR-B1 — Consolidate Checklist Assignment | ✅ Complete | `PR-B1_ASSIGNMENT_CONSOLIDATION.md` — `ChecklistAssignmentService` now the single write path for `equipment.checklist_master_id`; D1 logging implemented; delete-time dangling-reference gap closed |
| PR-B2 — Shared Rental Ready completion calculator | ✅ Complete (observability-first) | `PR-B2_RENTAL_READY_CALCULATOR.md` — `RentalReadyCompletionCalculator` now the single completion algorithm; mobile path uses it directly; admin-web path computes it in parallel and logs disagreements only (D2: observability-first, enforcement deferred); `insepectorSlect` typo fixed |
| PR-B4.1 — Category CRUD duplication reduction | ✅ Complete | `PR-B4_1_CATEGORY_REFACTOR.md` — `CategoryCrudService` now the single write path for both trees' category CRUD; Rental Ready soft-delete and Customer Admin hard-delete/cascade both preserved exactly, per explicit decision |
| PR-B4.2 — Question CRUD duplication reduction | ✅ Complete | `PR-B4_2_QUESTION_REFACTOR.md` — `QuestionCrudService` now the single write path for both trees' question CRUD; also shipped `PR-B4_2_BUGFIX_DD_REMOVAL.md` (removed a live `dd()` in Customer Admin's Store catch block) as a prerequisite fix |
| PR-B4.3 — Templates CRUD duplication reduction | ✅ Complete | `PR-B4_3_TEMPLATE_REFACTOR.md` — `TemplateCrudService` now the single write path for both trees' template CRUD; Rental Ready soft-delete + real equipment_category FK, and Customer Admin hard-delete/cascade + no FK, both preserved exactly; PR-B4 (all 3 sub-PRs) now complete |
| Phase 2 release notes | ⏳ Pending | To be written next, mirroring `PHASE1_RELEASE_NOTES.md`'s format |

---

## Current blocking item

**Phase 2 release notes have not been written.** PR-B4 (Categories, Questions, Templates) is now fully complete — all three sub-PRs shipped with passing characterization suites and no regressions. Release notes are the next and final Phase 2 milestone.

## PR-B2 follow-ups (open, not blocking)

- **D2 enforcement** — switching the admin-web path from client-trusted to server-computed persistence — remains a separate, future stage, gated on reviewing the `api_errors` disagreement telemetry PR-B2 now produces, plus explicit client/product approval.
- **Client-side JS drift** (`index.blade.php`'s `computeStatusSummary()` gating on all questions instead of required-only) — documented as a fast-follow, not yet scheduled.

## PR-B4.1/PR-B4.2/PR-B4.3 follow-ups (open, not blocking)

- **Customer Admin hard-delete/cascade risk** — confirmed at Category, Question, Answer, Template, and TemplateQuestion level (deleting anything in that tree is unrecoverable and silently destroys everything beneath it). Flagged to product/ops as its own separate decision, independent of the CRUD refactors, which were explicitly scoped to preserve this behavior rather than fix it.
- **Misleading `{unique_id}` route-parameter name** on Question Update/Copy routes (both actually do a numeric-PK lookup) — a readability/footgun risk, not a live bug; not renamed per that PR's explicit scope.
- **`equipment_category_id` FK asymmetry** — Rental Ready Templates have a real FK with `nullOnDelete()`; Customer Admin Templates have none (still a plain string column). Worth product/ops awareness; not a data-loss risk on the scale of the hard-delete finding.
- **Customer Admin Template Copy's `unique_id` prefix quirk** — `CopyController` regenerates a copied template's `unique_id` with the `'TQS'` prefix (Rental Ready's own creation-time prefix) instead of `CustomerAdminTemplate`'s own boot()-time prefix (`'CATQS'`) — a pre-existing inconsistency, preserved exactly per PR-B4.3's explicit scope (no `unique_id`-behavior changes authorized). Worth a tiny, separately-reviewed one-line fix if ever revisited.
- **Dead `'required'` field on TemplateQuestion updates** — submitted but silently dropped by both models (not in `$fillable`, no such DB column in either tree). Harmless, but confusing dead code.

## Phase 2 remaining work

1. Write Phase 2 release notes mirroring `PHASE1_RELEASE_NOTES.md`'s format, covering PR-B3, PR-B1, PR-B2, and PR-B4.1/B4.2/B4.3.
