# Sprint 0 Results

**Date:** 2026-07-14
**Scope:** Phase 3, Sprint 0 only — DOC-1, DOC-2, DB-4, DB-6. No production code, no migrations, no refactoring.

---

## DOC-1 — `PHASE2_DECISION_MATRIX.md` updated

D2–D5 filled in with decider, date, final outcome, and implementation reference, reflecting decisions already implemented in Phase 2. A dated "Update (2026-07-14)" note was appended documenting this as a retroactive correction per `PHASE3_IMPLEMENTATION_PLAN.md` DOC-1.

**File changed:** `docs/checklist-system-audit/PHASE2_DECISION_MATRIX.md`
**Code/data changed:** None.

---

## DOC-2 — Stakeholder note created

New file explaining the `equipment_status_logs` historical gap: `saveQuietly()` bypassed the model observer prior to PR-A1, historical data cannot be reconstructed, the table is complete only from the PR-A1 deployment date onward, and any report using this table must account for that cutoff. The exact deployment date is left as an open action item for engineering/DevOps to confirm.

**File created:** `docs/checklist-system-audit/STAKEHOLDER_NOTE_EQUIPMENT_STATUS_LOGS.md`
**Code/data changed:** None.

---

## DB-4 — ChecklistMaster #27 / `CLM-5LNW-UPXG` verification

### SQL used (all read-only, run against `rc_kabba_7_7_26`)

```sql
SELECT id, unique_id, checklist_system_name, equipment_category_id,
       rental_ready_template_id, customer_admin_template_id, deleted_at
FROM checklist_masters
WHERE id = 27 OR unique_id = 'CLM-5LNW-UPXG';

SELECT 'rental_ready' AS tree, id, unique_id, template_name, equipment_category_id
FROM rental_ready_checklist_templates WHERE id = 16
UNION ALL
SELECT 'customer_admin' AS tree, id, unique_id, template_name, equipment_category_id
FROM customer_admin_templates WHERE id = 15;

SELECT id, unique_id, equipment_name, current_status, checklist_master_id
FROM equipment
WHERE checklist_master_id = 27
ORDER BY id;

SELECT id, unique_id, current_status, current_order_id, current_order_product_id, current_status_changed_at
FROM equipment
WHERE checklist_master_id = 27
ORDER BY id;

SELECT id, title FROM product_categories WHERE id IN (8, 23);
```

### Findings

| Item | Value |
|---|---|
| ChecklistMaster #27 | `CLM-5LNW-UPXG`, name "Attachment - Mini Skid", `equipment_category_id = 23`, not deleted |
| Linked Rental Ready template | id 16, `TQS-IMWM-TW1C`, "Mini Skids", `equipment_category_id = 8` |
| Linked Customer Admin template | id 15, `CATQS-K3WO-ATUO`, "Mini Skids", `equipment_category_id = 8` |
| Category 23 name | "Attachments - Mini Skid" |
| Category 8 name | "Mini Skids" |
| Equipment assigned | 9 units (ids 88–96): Auger, Brush Cutter, Grapple ×3, Harley Rake, Log Grapple — all Mini-Skid-attachment items |
| Currently rented | **None.** 8 of 9 are `available`; unit `EQP-QSCF-KBTI` (id 94) is `maintenance` |

**The mismatch still exists**: `ChecklistMaster #27`'s own category (23, "Attachments - Mini Skid") does not match either of its linked templates' category (8, "Mini Skids", on both the Rental Ready and Customer Admin side).

**Change from the original `PHASE3_RESULTS.md` V10 finding:** at the time of that audit, one of these units (`EQP-QSCF-KBTI`) was actively rented, which was the basis for treating this as urgent. As of this check (2026-07-14), that unit has since transitioned to `maintenance` (`current_status_changed_at = 2026-07-03 12:01:51`) and **no unit currently assigned to this ChecklistMaster is rented**. The immediate "wrong checklist could be served to a customer today" risk is not currently live — but the underlying mismatch is unresolved, and any of these 9 units could be rented again at any time, at which point the wrong (generic "Mini Skids") checklist template would apply.

### A real ambiguity, not yet resolved — needs a decision, not a fix

The data does not tell us which side is "wrong":

- **Category 23** ("Attachments - Mini Skid") closely matches the ChecklistMaster's own name ("Attachment - Mini Skid") and matches what's actually assigned to it (9 individual attachments — augers, grapples, a Harley rake — not mini skid machines themselves).
- **Category 8** ("Mini Skids") is what both linked templates are pointed at, and is a plausible deliberate choice too: it's possible attachments were intentionally set up to reuse the parent equipment's ("Mini Skids") inspection checklist content rather than maintaining separate attachment-specific templates.

Two different corrections are possible, and they are not equivalent:

1. **Change `checklist_masters.id=27.equipment_category_id` from 23 → 8**, aligning the master with its templates' category. Low risk to templates, but changes what the ChecklistMaster itself claims to categorize.
2. **Change both templates' `equipment_category_id` from 8 → 23** (`rental_ready_checklist_templates.id=16` and `customer_admin_templates.id=15`), aligning the templates with the master and its actual assigned equipment. Affects two template records; if either template is shared/reused by any other ChecklistMaster, this could have side effects (not yet checked).
3. **No fix — confirm this is intentional** (attachments deliberately reuse the generic Mini Skids checklist), in which case only the category *label* is inconsistent, not the actual checklist content served.

**No data has been changed.** Per your instruction, this is being reported for a decision, not silently corrected. Before any fix is applied, please confirm:
- Which side is actually correct (or that option 3 applies), and
- Whether template id 16/15 are used by any other ChecklistMaster (I have not yet checked this — happy to check as part of applying whichever fix is approved).

---

## DB-6 — Category-name drift investigation (Rental Ready ↔ Customer Admin)

### SQL used (read-only, run against `rc_kabba_7_7_26`)

```sql
SELECT rr.id AS rr_id, rr.unique_id AS rr_unique_id, rr.category_name AS rr_name,
       rr.description AS rr_desc, rr.created_at AS rr_created_at,
       ca.id AS ca_id, ca.unique_id AS ca_unique_id, ca.category_name AS ca_name,
       ca.description AS ca_desc, ca.created_at AS ca_created_at,
       ABS(TIMESTAMPDIFF(SECOND, rr.created_at, ca.created_at)) AS seconds_apart
FROM rental_ready_checklist_categories rr
JOIN customer_admin_categories ca
  ON ABS(TIMESTAMPDIFF(SECOND, rr.created_at, ca.created_at)) <= 120
WHERE rr.deleted_at IS NULL
ORDER BY rr.created_at;
```

(Run first with a 5-second window, then widened to 120 seconds to check for near-misses — both returned the same result set.)

### Matched pairs (mirrored via the one-time-sync checkbox — near-identical `created_at`)

| RR id | RR name | CA id | CA name | Seconds apart | Name match | Description match |
|---|---|---|---|---|---|---|
| 9 | Excavators | 6 | Excavators | 0 | Yes | Yes (both NULL) |
| 10 | Cab Machines | 7 | Cab Machines | 0 | Yes | Yes ("Items universal to Cabs") |
| 12 | Trenchers | 16 | Trenchers | 0 | Yes | Yes (both NULL) |
| 16 | Wood Chippers | 18 | Wood Chippers | 0 | Yes | Yes (both NULL) |

**4 mirrored pairs found. No drift** — every mirrored pair has an identical `category_name` and identical `description` on both sides.

### Non-mirrored categories (created independently, not via the mirror checkbox)

Rental Ready has 17 categories with no timestamp match on the Customer Admin side; Customer Admin has 16 categories with no timestamp match on the Rental Ready side (21 RR total, 20 CA total, 4 mirrored pairs each). Several of these have similar or identical names despite being created independently at different times (e.g., "Boom Lifts", "Trailers", "Universal", "Skid Steer - Forestry", "Scissor Lift", "Mowers"/"Pressure Washer(s)", "Stump Grinders"/"Stump Grinder - Barreto") — these are **not** in scope for "drift" since they were never mirrored; any resemblance is coincidental or the result of manual duplication in both trees. Full lists are in the query output; not reproduced here for brevity but available on request.

### Conclusion and recommended follow-up

- **No drift exists among actually-mirrored category pairs.** The one-time-sync feature (PR-B4.1), where checked, produced exact copies and nothing has diverged since.
- The wider category lists diverging in each tree (17 RR-only, 16 CA-only) reflect independent category management in each admin area, not a defect in the mirror feature — this is expected given there is no ongoing sync (per D5, confirmed logic-sharing only, no schema/behavior unification).
- **Recommended follow-up:** none required to fix drift (there isn't any). Optionally, if the two category lists are meant to represent the same real-world equipment categories, a one-time manual reconciliation pass across the full 21/20 lists (outside the mirrored 4) could be considered — but this is a data-hygiene/product decision, not a defect, and is out of scope for Sprint 0.

---

## Sprint 0 — Final Report

**Files changed:**
- `docs/checklist-system-audit/PHASE2_DECISION_MATRIX.md` (edited — D2–D5 filled in, update note appended)
- `docs/checklist-system-audit/STAKEHOLDER_NOTE_EQUIPMENT_STATUS_LOGS.md` (created)
- `SPRINT0_RESULTS.md` (created — this file)

**Exact SQL/commands run:** all listed above under DB-4 and DB-6 (all `SELECT`-only, via direct `mysql` CLI against `rc_kabba_7_7_26`).

**Was any database data modified?** No. Every query executed was a read-only `SELECT`. No `INSERT`/`UPDATE`/`DELETE` was run.

**Was any production code modified?** No. No PHP, JS, migration, or config file was changed.

**Verdict: PASS** for Sprint 0 as scoped (DOC-1, DOC-2, DB-4 investigation, DB-6 investigation all complete).

**Outstanding decision needed before Sprint 1 can safely include a DB-4 fix:** which side of the ChecklistMaster #27 category mismatch is correct (see DB-4 section above) — this is a business/data decision, not an engineering one, and no fix has been applied pending your answer.
