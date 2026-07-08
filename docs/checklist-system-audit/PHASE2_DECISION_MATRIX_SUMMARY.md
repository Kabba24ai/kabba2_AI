# Checklist System — Phase 2 Decisions: Approval Sheet

**Date:** 2026-07-07
**Audience:** Client / Product Owner sign-off. For full engineering detail behind each row, see `PHASE2_DECISION_MATRIX.md`.
**Status:** No code has been written. Implementation of PR-B3 (the first PR in the agreed sequence) will not begin until this sheet is returned with decisions marked.

---

## Why this exists

Five areas of the planned Phase 2 work depend on a business decision, not just an engineering one. This sheet summarizes each one in plain terms. Please mark a box for each row and return.

**No functionality will change until these decisions are approved.** This document is intended to ensure business behavior is agreed before implementation begins.

---

| # | Decision | Current behavior | Proposed change | Recommendation | Client decision |
|---|---|---|---|---|---|
| D1 | Warn before moving equipment between Checklist Masters? | Reassigning a Checklist Master silently unassigns equipment that was set up elsewhere, with no warning. | Show a confirmation listing what will be unassigned before it happens. | ✅ Recommend approving | ☐ Approve change &nbsp; ☐ Keep current behavior |
| D2 | Should the system calculate Rental Ready status itself, or let staff override it? | Admin staff can mark equipment "Rental Ready" manually, regardless of what the actual inspection answers show. | Have the system compute status from the real inspection answers first; start by only logging when staff input disagrees, before deciding whether to enforce it. | ✅ Recommend approving the staged (log-first) approach | ☐ Approve change &nbsp; ☐ Keep current behavior |
| D3 | Review the currently disabled safety checks in the mobile inspection workflow. | Two validation checks exist in the code but are turned off — incomplete inspections and invalid equipment states are not currently blocked. | Investigate why they were turned off, then either restore them gradually (log first, enforce later) or remove them for good. | ✅ Recommend investigate-then-decide, not immediate enforcement | ☐ Approve investigation &nbsp; ☐ Leave as-is |
| D4 | Reduce duplicated logic between the two checklist systems (Rental Ready and Customer Admin)? | The two systems are built as separate, independently-coded copies of the same idea — a fix in one doesn't apply to the other. | Share the common logic between them through one place in the code, without merging their underlying data. | ✅ Recommend approving | ☐ Approve change &nbsp; ☐ Keep current behavior |
| D5 | Merge the two systems' data into one, or keep them separate for now? | Rental Ready and Customer Admin use separate database tables today. | Confirm this phase will **not** merge them — only the logic (D4), not the data. | ✅ Recommend deferring any data merge to a later phase | ☐ Approve (defer merge) |

---

## What happens after this is signed

```
This sheet approved
        ↓
PR-B3  (disabled safety checks — smallest, lowest-risk change)
        ↓
   Review & confirm
        ↓
PR-B1  (single place for equipment/checklist assignment)
        ↓
   Review & confirm
        ↓
PR-B2  (one shared Rental Ready status calculation)
        ↓
   Review & confirm
        ↓
PR-B4  (reduce duplicated logic — split into smaller pieces)
        ↓
Phase 2 Release Notes
```

Each step is reviewed and confirmed working before the next one starts — the same process used successfully in Phase 1.

---

## Timeline expectation

- **Engineering work:** approximately 2-4 weeks.
- **Overall calendar:** may run longer than that if any decision above takes time to finalize, or if a "log first, decide later" step (D2, D3) needs a longer observation window before a final call is made.

---

**Signed off by:** _______________________ **Date:** _______________

No code has been modified to produce this document.
