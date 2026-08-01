# Phase 2A — Append-Only QA Log Recovery Audit (report only)

> 2026-07-31. READ-ONLY analysis. NO legacy logs were imported or converted in
> this increment (per the Phase 2A instruction). This documents what history is
> recoverable from `equipment_rental_ready_checklist_question_logs`, where it is
> ambiguous, and the proposed migration rules for a LATER increment.

---

## 1. The source of truth for legacy history

`equipment_rental_ready_checklist_question_logs` is **append-only** — one row
was `create()`d on every save (mobile and web), never updated or deleted. It is
therefore the only faithful record of inspections that the pre-Phase-2A
overwrite behavior otherwise destroyed on the `equipment_rental_ready_templates`
/ `_checklist_questions` rows.

Columns: `id`, `unique_id`, `equipment_rental_ready_template_id` (nullable,
`nullOnDelete`), `rental_ready_all_qa_json` (JSON), `action_by`,
`action_user_name`, `inspector_name`, `inspection_date`, `equipment_hours`,
`created_at`.

The JSON blob (`rental_ready_all_qa_json`) differs by writer:

| Origin | Blob builder | Shape | Fidelity |
|---|---|---|---|
| Mobile (`Api\...\RentalReadyChecklists\SaveController`) | **server-built** `{counts, questions}`; each question has `question_name`, `category_id`, `required_question`, `note`, `answers[]` (`answer_name`, `type`, `is_selected`, `id`, `unique_id`), `selected_answer` | rich, self-describing | **HIGH** |
| Web (`Admin\...\EquipmentManagement\StoreController`, pre-2A) | **client payload verbatim** `json_encode($qaPayload)` → `{counts, questions, order_id?, order_product_id?}`; question shape is whatever the FE sent (`id`=unique_id, `main_id`, `selected_answer.id`, `note`) | variable | **LOW–MEDIUM** |

Post-Phase-2A logs additionally carry `lifecycle_status`, `result`, and `source`
(server-built for both paths) — those need no reconstruction.

---

## 2. Reconstructability

**Per-submission event recovery (both origins):** every log row yields a
point-in-time event with a trustworthy `inspection_date`, `equipment_hours`,
`inspector_name`, and `action_by`. These four are columnar (not inside the
blob) and reliable.

**Answer/result recovery:**

- **Mobile logs → fully reconstructable.** The blob carries each question's
  text, the selected answer's text + `type` (classification), and the required
  flag. The Phase 2A result can be recomputed exactly with the SAME rule the new
  writer uses (any `Damaged` → Damaged; else all required `Rental Ready` →
  Rental Ready; else all required answered → Maintenance Hold; else Draft).
- **Web logs → partially reconstructable.** The blob reliably carries `counts`
  and the selected answer *id*s, but answer `type`/`text` and the `required`
  flag are only present if the FE included them. Where absent, they must be
  joined to the **current** master tables (`rental_ready_checklist_questions` /
  `_question_answers`) by id — which may have been edited or soft-deleted since,
  so the reconstructed classification is *inferred*, not observed.

**Provenance chain:** `equipment_rental_ready_template_id` links a log to a
template, but that template row was REUSED/overwritten, so its current header no
longer matches older logs pointing at it. Reconstruction must therefore treat
each **log row** (not each template) as the historical inspection event, and use
the blob's own `counts` — never the template's current columns.

---

## 3. Ambiguities / data-loss caveats

1. **No `source` marker on legacy logs** → mobile-vs-web fidelity must be
   inferred from blob shape (presence of `answers[].type` / `selected_answer`
   objects vs. bare ids). Heuristic, not certain.
2. **Web id-only logs depend on drifted master tables.** If a question or answer
   option was renamed/retyped/deleted after the inspection, the recovered
   classification reflects TODAY's master, not what the inspector saw. This is
   exactly the risk the Phase 2A snapshot now prevents going forward.
3. **Section name + question order were never captured** (only `category_id` and
   implicit array position). Historical section grouping/order can only be
   inferred from the current master and is low-confidence.
4. **Lifecycle vs result did not exist.** Legacy 'Draft' conflates
   "maintenance hold" and "genuinely incomplete." Recomputation from answer
   types disambiguates *only* when the required flags are known (mobile: yes;
   web id-only: inferred).
5. **Retries created duplicate logs** (no idempotency pre-2A). Near-identical
   consecutive logs for the same template are the same real event and must be
   de-duplicated, or history will overcount inspections.
6. **`action_by` (auth account) ≠ `inspector_name`/`employee` (device-selected)**
   can disagree — both should be preserved; neither silently dropped.
7. **`nullOnDelete` template FK:** logs whose template was hard-deleted have a
   null `equipment_rental_ready_template_id`; the equipment must then be
   recovered from within the blob or is unrecoverable (flag as orphaned).

---

## 4. Proposed migration rules (for a LATER increment — NOT run now)

Backfill immutable historical inspections from the logs, **flagged as
reconstructed and never authoritative over live post-2A rows**:

1. **One reconstructed inspection per log row** (not per template). Carry
   `inspection_date`, `equipment_hours`, `inspector_name`, `action_by` from the
   log columns.
2. **Classify origin** by blob shape; stamp a `reconstruction_confidence`
   (`high` = mobile full snapshot; `medium` = web with types present; `low` =
   web id-only requiring master join; `indeterminate` = unparseable).
3. **Recompute `result` + `lifecycle`** with the canonical Phase 2A rule from
   the blob's answer types + required flags. Prefer the blob's own
   `required_question`; fall back to the master join only for `low` confidence
   and record that it was inferred.
4. **Enrich `section_name` + `question_order`** by joining to the master WHERE
   the question still exists; where it doesn't, leave null and mark `drifted`.
   Never fabricate.
5. **De-duplicate retries**: collapse consecutive logs for the same
   `equipment_rental_ready_template_id` whose `questions` payloads are identical
   within a short window (e.g. ≤120s) into one event.
6. **Provenance columns** on the reconstructed rows: `reconstructed_from_log_id`,
   `reconstruction_confidence`, `reconstruction_notes`. Set
   `lifecycle_status='completed'` only when a definitive result was recomputed;
   otherwise `result=null` + `indeterminate`, surfaced for manual review and
   never authoritative.
7. **Orphaned logs** (null template FK and no equipment resolvable from the blob)
   → parked in a review bucket, not imported as inspections.
8. **Idempotent + reversible import**: keyed on `reconstructed_from_log_id` so it
   can be re-run safely and rolled back without touching live rows.

**Recommended pre-migration step:** run a one-off READ-ONLY profiling query over
the real `rental_ready_all_qa_json` corpus (distribution of blob shapes, % with
`answers[].type` present, % web-with-order-context, retry clusters) to size each
confidence bucket before writing the importer. That profiling is itself part of
the *next* increment, not this one.
