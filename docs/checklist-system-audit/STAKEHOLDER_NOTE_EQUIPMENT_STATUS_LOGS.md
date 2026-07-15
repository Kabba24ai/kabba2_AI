# Stakeholder Note — `equipment_status_logs` Historical Data Gap

**Date:** 2026-07-14
**Audience:** Anyone building reports, dashboards, or analysis that reads the `equipment_status_logs` table.
**Type:** Communication only. No code or data was changed to produce this note.

---

## What happened

Before a fix shipped in **PR-A1** (Phase 1), every equipment status transition driven by the checklist system — mobile delivery, mobile return, rental-ready inspections, and several admin-web status changes — used a Laravel method called `saveQuietly()` to update the equipment record. `saveQuietly()` deliberately skips the application's normal event system, and the *only* thing listening for those events was the exact code responsible for writing a row into `equipment_status_logs`.

The practical result: **`equipment_status_logs` was silently, 100%-reliably empty for these transitions for as long as this pattern was in place** — not intermittently, not for edge cases, but for every single one of them. The table looked like it was working (no errors, no warnings visible anywhere) while actually recording nothing for these paths.

## What was fixed, and when

PR-A1 added an explicit log-write step immediately after each of these transitions, so the table is now populated correctly going forward. This fix is deployed and verified (11/11 regression tests passing, confirmed in the Phase 1 release notes).

## What this means for anyone using this table

1. **Historical data cannot be reconstructed.** There is no reliable way to backfill what equipment status transitions happened before the fix — the only source of truth for "what changed and when" was the event pipeline that never fired, and nothing else recorded the same information with the same precision (actor, exact timestamp, from/to status) elsewhere.
2. **Logs are complete and trustworthy only from the PR-A1 deployment date onward.** Any row in the table has a real `created_at` timestamp — use the deployment date, not the row dates themselves, as the cutoff when deciding whether a given historical window is trustworthy.
3. **Any report, dashboard, or audit query using this table must account for that cutoff.** Concretely:
   - Do not treat "no rows before date X" as "no status changes happened before date X" — it means the opposite: changes happened, but weren't recorded.
   - Do not compute historical trend lines, counts, or averages that span the cutoff date without clearly annotating the discontinuity — a chart that silently shows "activity" starting at the deployment date will misrepresent reality as if equipment status changes only began then.
   - If a report specifically needs pre-cutoff data, other sources may have *partial* information (e.g., `order_products.delivery_status`/`pickup_status` reconstruct some of the picture for checklist-driven transitions specifically, though not all seven of the transition types this table now covers) — check with engineering before assuming any specific historical reconstruction is possible for a given use case.

## The cutoff date

**Action needed:** confirm and record the exact date `PR-A1` was deployed to production here once known. Until that date is filled in, treat *any* current production data in this table as suspect for historical analysis — the safest working assumption is "trustworthy only from today forward" until the deployment date is confirmed and communicated.

| | |
|---|---|
| **PR-A1 code complete** | Confirmed (Phase 1, see `PHASE1_RELEASE_NOTES.md`) |
| **PR-A1 production deployment date** | *(fill in once confirmed with engineering/DevOps)* |
| **Table considered fully reliable from** | *(same as deployment date above)* |

---

## Confirmation

No code was modified and no data was changed to produce this note — it is a communication artifact only, summarizing a fix that already shipped in PR-A1.
