# Phase 3 — Service Diagnostic Engine

**Governing architecture specification.** Approved 2026-07-26. This is the authoritative
Phase 3 specification, not an informal summary.

Baseline: Phase 2 symptom-library commonization, tag `service-phase-2-baseline-2026-07-26`
(commit `c267cb7f`).

---

## 1. Architectural decision

The Service Diagnostic Engine is **cause-centric**. Causes are the central reasoning
records connecting reported complaints to diagnostic findings, repair recommendations,
repair execution, and verification. Diagnostic tests remain reusable catalog definitions
with ticket-specific execution records. Findings may be captured before a cause is
identified; diagnostic conclusions are formed through explicit **supporting / refuting /
inconclusive** relationships between findings and causes. The engine preserves both the
**current state** and the **immutable history of the technician's reasoning**.

> The Service timeline answers *"what happened?"* The diagnostic engine answers *"why did
> we believe this?"* Those are different concerns. Cause transitions are therefore
> first-class domain records, not timeline projections. The timeline is a **consumer** of
> a transition, never its owner.

---

## 2. Resolved conventions

| Concern | Decision |
|---|---|
| **Actor attribution** | Every `*_by` column → `foreignId()->nullable()->constrained('users')->nullOnDelete()`, set from `auth()->id()`. `users` is the personnel/HRM roster; there is no separate employees table and no morph actor. |
| **Approval authority** | `service_tickets.approval_status` remains authoritative; `ServiceTicket::{send,approve,decline}Estimate()` is the only approval-write path. Recommendations synchronize *into* it through one mediating service. |
| **Transition history** | A dedicated, append-only `service_ticket_cause_transitions` table is the authoritative record of every cause state change. The evidence snapshot references the transition. A `service_ticket_events` timeline entry is emitted as a downstream projection. |

---

## 3. Governing principles

1. Cause-centric ≠ every record needs a cause immediately (findings and observations stand alone).
2. Diagnostic relationships are historical reasoning records, not disposable pivots.
3. Catalog definitions are separated from ticket-time snapshots.
4. Legacy prose is never converted into false structure.
5. Every graph relationship is protected by a same-ticket invariant.
6. Approval, labor, parts, responsibility, settlement, and workbench architectures remain authoritative unless explicitly commonized.
7. Both the current state and the historical path to it are preserved.
8. The diagnostic engine preserves estimates and reasoning — it never becomes a competing financial or tax authority.

---

## 4. Enums (`App\Enums\Service\`)

| Enum | Cases |
|---|---|
| `SystemGroup` | engine, fuel_system, electrical, starting_charging, hydraulic_system, drivetrain, transmission, undercarriage, steering, brakes, cooling, controls, emissions, attachment, structure, safety_system, other |
| `CauseStatus` | draft, suspected, supported, confirmed, ruled_out, superseded |
| `FindingCauseRelation` | supports, refutes, inconclusive |
| `FindingType` | measurement, pass_fail, observation, code, other |
| `FindingResult` | pass, fail, not_applicable, inconclusive |
| `ObservationStatus` | confirmed, not_observed, intermittent, unable_to_verify, not_tested |
| `ObservationOrigin` | complaint_review, technician_discovered, diagnostic_run, historical_review |
| `DiagnosticRunStatus` | planned, in_progress, completed, aborted |
| `RecommendationStatus` | draft, proposed, approved, declined, superseded |
| `RepairStatus` | planned, in_progress, completed, stopped, voided |
| `RepairType` | standard, temporary, emergency, adjustment |
| `RepairOutcome` | passed, failed, temporary, inconclusive |
| `ComplaintOutcome` | resolved, partially_resolved, unresolved, unable_to_verify, not_applicable |
| `VerificationStatus` | draft, completed, voided |
| `VerificationScope` | repair, complaint, ticket |

New `ServiceTicketEventType` cases (timeline projections): `CauseStatusChanged`,
`FindingRecorded`, `FindingCauseLinked`, `DiagnosticRunCompleted`, `RecommendationCreated`,
`RecommendationStatusChanged`, `RepairStatusChanged`, `VerificationRecorded`.

`SystemGroup` is a **stable key**, never a display string; labels come from the enum's
`label()`.

---

## 5. Schema

All tables are additive. Legend: 🔴 new table · 🟡 extend existing · ♻️ reuse.

### 5.1 🔴 `service_diagnostic_tests` — reusable catalog
`id` · `code` (unique) · `name` · `system_group` (SystemGroup, nullable) · `description`
(text) · `default_result_type` (FindingType, nullable) · `expected_unit` (nullable) ·
`is_active` · `display_order` · timestamps · soft deletes.
Indexes: unique(`code`) · (`is_active`,`display_order`) · (`system_group`).
Seeded by an **audited mapping** from `DiagnosticStepType` (see §9), not 1:1 — only true
reusable diagnostic procedures become catalog rows; workflow/documentation enum cases are
excluded and preserved as legacy values.

### 5.2 🔴 `service_ticket_observations`
`id` · `service_ticket_id` (FK, cascade) · `service_symptom_id` (→ `service_symptoms`,
nullable) · `symptom_label` (nullable snapshot for ad-hoc) · `complaint_id` (→
`service_ticket_complaints`, nullable) · `origin` (ObservationOrigin) · `observation_status`
(ObservationStatus) · `notes` (text) · `observed_by` (users) · `observed_at` · timestamps ·
soft deletes.
Indexes: (`service_ticket_id`) · (`service_symptom_id`) · (`complaint_id`) · (`origin`).
An observation may exist without a complaint (a technician-discovered condition); `origin`
makes the distinction explicit and queryable rather than inferred from a null FK.

### 5.3 🔴 `service_ticket_causes` — the spine
`id` · `service_ticket_id` (FK, cascade) · `description` (text) · `system_group_key`
(SystemGroup, nullable) · `component_label` (free text, nullable) · `status` (CauseStatus,
**default `draft`**) · `confidence` (tinyint unsigned, nullable; never overrides state) ·
`superseded_by_cause_id` (self, nullable) · `created_by` (users) ·
`confirmed_by`/`confirmed_at`/`confirmation_rationale` (nullable) · `confirmation_override`
(bool, default false) · `confirmation_override_reason` (nullable) ·
`ruled_out_by`/`ruled_out_at`/`ruled_out_rationale` (nullable) ·
`reopened_by`/`reopened_at`/`reopened_reason` (nullable) · timestamps · soft deletes.
Indexes: (`service_ticket_id`,`status`) · (`status`) · (`system_group_key`) ·
(`superseded_by_cause_id`).

> The mutable audit columns on the cause are a **read convenience** reflecting the latest
> transition. The authoritative record of *every* transition is the immutable
> `service_ticket_cause_transitions` row and its evidence snapshot (§5.10–5.11).

Classification: a single controlled `system_group_key` provides stable grouping/reporting;
`component_label` carries machine-level detail (e.g. "left final drive", "lift cylinder rod
seal"). No second broad tier in Phase 3 — a canonical component taxonomy is deferred (§13)
and can later supplement/migrate `component_label` without changing `system_group_key`.

### 5.4 🔴 `service_ticket_diagnostic_runs` — a test performed on a ticket
`id` · `service_ticket_id` (FK, cascade) · `service_diagnostic_test_id` (→ catalog,
nullable = ad-hoc) · `test_code_snapshot` · `test_name_snapshot` · `procedure_snapshot`
(nullable) · `expected_unit_snapshot` (nullable) · `performed_by` (users) ·
`procedure_notes` (text) · `test_conditions` (text) · `equipment_hours` (decimal, nullable)
· `tool_reference` (nullable) · `status` (DiagnosticRunStatus, default `completed`) ·
`started_at`/`completed_at` · timestamps · soft deletes.
Indexes: (`service_ticket_id`) · (`service_diagnostic_test_id`).
The `*_snapshot` columns freeze what the technician was instructed to do at run time, so
later edits to the catalog definition never rewrite completed history.

### 5.5 🔴 `service_ticket_findings` — result of a run; may precede any cause
`id` · `service_ticket_id` (FK, cascade) · `diagnostic_run_id` (→ runs, nullable = finding
from a photo/observation) · `result_type` (FindingType) · `summary` (string) ·
`measured_value` (decimal, nullable) · `measured_unit` (nullable) · `expected_min`
(nullable) · `expected_max` (nullable) · `expected_text` (nullable) · `comparison_operator`
(nullable) · `specification_source` (nullable) · `is_within_spec` (bool, nullable) ·
`result` (FindingResult, nullable) · `detail` (text) · `recorded_by`/`recorded_at` ·
timestamps · soft deletes.
Indexes: (`service_ticket_id`) · (`diagnostic_run_id`).
**No `cause_id`** — cause linkage exists only via `service_ticket_finding_causes` (§5.9).

### 5.6 🔴 `service_ticket_repair_recommendations`
`id` · `service_ticket_id` (FK, cascade) · `title` · `description` (text) ·
`estimated_labor_hours` · `estimated_labor_total` · `estimated_parts_total` ·
`estimated_misc_total` · `estimated_tax` · `estimated_total` (all decimal, nullable) ·
`status` (RecommendationStatus, default `draft`) · `superseded_by_recommendation_id` (self,
nullable) · `created_by` (users) · timestamps · soft deletes.
Indexes: (`service_ticket_id`,`status`).
Estimate figures are computed by the **existing tax engine** when the recommendation is
created or recalculated, then frozen as historical snapshots after approval (§8). Approval
state is not written here directly (§8).

### 5.7 🔴 `service_ticket_repairs` — execution
`id` · `service_ticket_id` (FK, cascade) · `repair_recommendation_id` (→ recommendations,
**nullable** = immediate/emergency repair with no estimate cycle) · `work_summary` (text) ·
`status` (RepairStatus, default `planned`) · `repair_type` (RepairType, default `standard`)
· `completion_notes` (nullable) · `stopped_reason` (nullable) · `void_reason` (nullable) ·
`performed_by` (users) · `started_at`/`completed_at` · timestamps · soft deletes.
Indexes: (`service_ticket_id`) · (`repair_recommendation_id`) · (`status`).
`repair_type` describes the nature of the repair; `status` describes its operational state.
A repair is not "executed" merely because the row exists.

🟡 Additive nullable `service_ticket_repair_id` FK on `service_ticket_labor_entries` and
`service_ticket_parts`, so a repair can claim its labor/parts while those remain canonical
in their existing tables.

### 5.8 🔴 `service_ticket_verifications`
`id` · `service_ticket_id` (FK, cascade) · `repair_id` (→ repairs, nullable) ·
`complaint_id` (→ complaints, nullable) · `verification_scope` (VerificationScope) ·
`repair_outcome` (RepairOutcome, nullable) · `complaint_outcome` (ComplaintOutcome,
nullable) · `verification_method` (nullable) · `follow_up_required` (bool, default false) ·
`notes` (text) · `sequence` (uint, default 1, **service-assigned**) · `verification_status`
(VerificationStatus, default `draft`) · `supersedes_verification_id` (self, nullable) ·
`verified_by`/`verified_at` · timestamps · soft deletes.
Indexes: (`service_ticket_id`) · (`repair_id`) · (`complaint_id`) ·
unique(`service_ticket_id`,`repair_id`,`complaint_id`,`sequence`) as a backstop.
Verification answers two separate questions — did the repair correct the technical failure,
and did it resolve the reported complaint. Scope rules: `scope=repair` requires `repair_id`
+ `repair_outcome`; `scope=complaint` requires `complaint_id` + `complaint_outcome`;
`scope=ticket` requires neither FK. `not_applicable` is never stored merely to compensate
for an absent relationship. Multiple verifications over time are normal historical events;
`supersedes_verification_id` is used only when a prior verification was entered incorrectly
or formally replaced.

### 5.9 Relationship tables — audited reasoning records

A relationship change (e.g. `supports` → `refutes`, or removal) is a new audited event,
never a silent overwrite; the evolution of the diagnosis is itself part of the record.

- 🔴 **`service_ticket_finding_causes`** (highest audit priority): `id` · `finding_id`
  (cascade) · `cause_id` (cascade) · `relationship` (FindingCauseRelation) ·
  `created_by`/`created_at` · `updated_by`/`updated_at` (nullable) · `removed_by` (nullable)
  + `deleted_at` (SoftDeletes) · `change_reason` (nullable).
  Indexes: unique(`finding_id`,`cause_id`) among non-deleted · (`cause_id`,`relationship`).
- 🔴 **`service_ticket_cause_complaints`**, 🔴 **`service_ticket_repair_causes`**,
  🔴 **`service_ticket_recommendation_causes`**, 🔴 **`service_ticket_verification_findings`**:
  the two FKs (cascade) · `created_by`/`created_at` · SoftDeletes (`removed_by` nullable) ·
  `change_reason` (nullable) · unique(pair) among non-deleted.

`service_ticket_recommendation_causes` and `service_ticket_verification_findings` are
genuinely many-to-many (one recommendation may address several causes on one harness; one
verification may cite several findings).

### 5.10 🔴 `service_ticket_cause_transitions` — authoritative transition history
`id` · `service_ticket_id` (FK, cascade — denormalized for ticket-scoped queries and the
same-ticket invariant) · `service_ticket_cause_id` (FK, cascade) · `from_status`
(CauseStatus, nullable — null on the very first record) · `to_status` (CauseStatus) ·
`transitioned_by` (users, nullable) · `transitioned_at` (timestamp) · `rationale` (text,
nullable) · `override_used` (bool, default false) · `override_reason` (text, nullable) ·
`metadata` (json, nullable) · timestamps.
**Append-only — no updates, no soft deletes.**
Indexes: (`service_ticket_cause_id`) · (`service_ticket_id`) · (`to_status`).
This is the first-class business object for warranty analysis, OEM exports, recurring-failure
reporting, technician-quality review, and future AI retrieval — independent of timeline
infrastructure.

### 5.11 🔴 `service_ticket_cause_transition_findings` — immutable evidence snapshot
`id` · `service_ticket_cause_transition_id` (→ `service_ticket_cause_transitions`, cascade)
· `service_ticket_finding_id` (→ findings) · `relationship_snapshot` (FindingCauseRelation,
frozen at decision time) · timestamps.
Indexes: unique(`service_ticket_cause_transition_id`,`service_ticket_finding_id`) ·
(`service_ticket_finding_id`).
On every `confirmed` / `ruled_out` / `reopened` / `superseded` transition, the engine writes
one row per active relevant finding relationship, capturing the evidence relied upon **at
that moment**. These rows are immutable and never change when the live graph later changes —
relationally queryable, referential-integrity-backed, independent of JSON. The transition's
`metadata` JSON may still carry contextual detail, but finding identity is relational.

### 5.12 Evidence & media — ♻️ reuse
Reuse `service_ticket_media.attachable` polymorphic morph (point it at causes, findings,
runs, repairs, verifications, observations). Three new categories only:
`diagnostic_evidence`, `repair_evidence`, `verification_evidence`; add `verification` to
`ServiceMediaWorkflowStage`. The `attachable_type` conveys the rest of the meaning; the
category vocabulary stays conservative until retrieval needs prove otherwise.

---

## 6. State machines

**Cause:**
```
draft ⇄ suspected → supported → confirmed
suspected | supported | confirmed → ruled_out      (mandatory rationale)
supported | confirmed            → superseded       (sets superseded_by_cause_id)
ruled_out → suspected            (REOPEN — mandatory reason)
confirmed → ruled_out            (PRIVILEGED correction — policy-gated, mandatory rationale)
```
- New causes default to `draft` in the database; a "Add suspected cause" UI action creates
  the record directly as `suspected`.
- **Confirm guard:** a cause may become `confirmed` only with ≥1 active
  `finding_causes.relationship = supports`, unless `confirmation_override = true` with a
  reason and policy authorization.
- **No terminal trap:** a `confirmed` cause can still be superseded or corrected to
  `ruled_out`; the history always shows it was once confirmed and later invalidated.
- Confirmation and complaint resolution are **fully decoupled** (§7).

**Recommendation:** `draft → proposed → approved | declined`; `→ superseded`.
**Repair:** `planned → in_progress → completed | stopped | voided`.
**Diagnostic run:** `planned → in_progress → completed | aborted`.
**Verification:** `draft → completed | voided`.

### Transition write order (every cause transition)
```
Cause  →  Cause Transition (authoritative)  →  Evidence Snapshot  →  Timeline Event (projection)
```

---

## 7. Cause confirmation ≠ complaint resolution

These transitions are independent and the service layer never conflates them. Confirming a
cause never auto-resolves a complaint; a complaint may resolve while root cause remains
uncertain; a confirmed cause may exist while the complaint remains unresolved; one complaint
may require repairs against several causes. `complaint_outcome` lives only on verifications
(§5.8), never derived from cause status. The presenter may *display* the relationship; it is
never inferred in the data.

---

## 8. Approval synchronization & estimate aggregation

- **Authority:** `service_tickets.approval_status` via `ServiceTicket::{send,approve,decline}Estimate()`
  remains the single approval event. No controller writes recommendation approval directly;
  one `RecommendationApprovalService` mediates and calls the existing model methods.
- **Estimate computation:** the existing tax engine computes a recommendation's
  labor/parts/misc/tax/total when the recommendation is created or recalculated. **After
  approval those amounts are historical snapshots and are never recomputed** — protecting
  historical estimates from later changes to tax rates, taxability rules, store
  configuration, line classification, or the tax engine itself.
- **Ticket-level summary (derived):** the sum of **active, approved, non-superseded**
  recommendation snapshots — labor = Σ labor, parts = Σ parts, misc = Σ misc,
  **tax = Σ tax snapshots (never recomputed)**, total = Σ total. Declined and superseded
  recommendations are excluded. If no structured recommendation exists, the existing
  manually-entered ticket summary is retained untouched.
- **Boundary:** actual billing and settlement tax remain governed by the existing
  billing/tax architecture. The engine preserves an estimate, not a competing tax authority.

---

## 9. Backward-compatibility rules

1. Free-text `technician_diagnosis` / `root_cause` / `recommended_repair` / `repair_summary`
   columns on `service_tickets` remain; removal is deferred.
2. Ticket-level estimate columns remain as derived summaries (§8); none are removed.
3. `service_ticket_diagnostic_steps` is **not** repurposed into the catalog:
   - **3.3A** — introduce runs/findings; stop new writes to the old table where practical;
     display existing rows read-only under a "Legacy Diagnostic History" section; classify
     historical data. No speculative migration.
   - **3.3B** — migrate only rows that can be mapped deterministically; leave ambiguous
     narrative rows as legacy. Never invent finding semantics from prose to empty the table.
4. The catalog is seeded from an audited mapping of `DiagnosticStepType`, not a blind 1:1
   seed; unmapped enum cases are preserved as legacy values.
5. Labor and parts stay canonical in their existing tables; only a nullable
   `service_ticket_repair_id` FK is added.
6. Responsibility and settlement architecture are untouched.
7. Every Phase 3 migration is additive (new tables, nullable columns, new enum cases).

---

## 10. Service invariants (non-negotiable)

`ServiceDiagnosticEngine` is the **sole write path** and enforces:

- **Same-ticket integrity** — identical `service_ticket_id` — before creating any link:
  finding↔cause, cause↔complaint, recommendation↔cause, repair↔cause, verification↔finding,
  verification↔complaint, repair↔recommendation, cause↔transition, and media attachments.
  Individually-valid foreign keys are insufficient; this guard prevents cross-ticket graph
  corruption.
- All state machines (§6), the confirm guard, and service-assigned verification `sequence`.
- Writing, for each cause transition, the authoritative `service_ticket_cause_transitions`
  row, its evidence snapshot, and the downstream timeline event — in that order.

---

## 11. Presenter — operational quality signals

`ServiceDiagnosticPresenter` (read-only) computes and surfaces, as non-blocking signals:
complaints with no linked cause · suspected causes with no findings · supported causes
lacking confirmation · confirmed causes with no repair or recommendation · completed repairs
without verification · unresolved complaints after verification · findings linked to no
cause · causes with only inconclusive evidence.

---

## 12. Implementation sequence

| Step | Scope |
|---|---|
| **3.0** | Commit and tag the Phase 2 symptom-library commonization baseline. *(Done — `service-phase-2-baseline-2026-07-26`.)* |
| **3.1** | Enums + `SystemGroup` controlled keys + `service_diagnostic_tests` catalog (audited mapping, not blind seed). |
| **3.2** | Observations, causes, cause↔complaint links, `service_ticket_cause_transitions` + evidence snapshot, policies, same-ticket invariants. No UI. |
| **3.3A** | Runs, findings, finding↔cause links, legacy audit. No speculative migration. |
| **3.3B** | Deterministic legacy migration only. |
| **3.4** | Recommendations, recommendation↔cause links, approval-sync service, explicit estimate aggregation. |
| **3.5** | Repairs + lifecycle, labor/parts linkage, verifications + sequences + verification↔findings. |
| **3.6** | Presenter + minimal integration into the existing Diagnostic stage. No broad workbench redesign. |

Each step is independently shippable, static-verified, and delivered with prepared tests
(server test runs when MySQL is reachable).

---

## 13. Deferred scope

Equipment recurring-failure dashboards · AI-assisted probable-cause suggestions · OEM
diagnostic procedure libraries · diagnostic decision trees · automatic confidence scoring ·
full Warranty workbench commonization · removal of legacy prose fields · a canonical
component-taxonomy catalog (a future supplement/migration of `component_label`, leaving
`system_group_key` stable).

---

## 14. Final locked decisions

1. **Tax:** aggregate `estimated_tax` by summing approved, non-superseded recommendation
   snapshots; never recompute at ticket aggregation time (§8).
2. **Confirmation evidence:** a relational immutable snapshot,
   `service_ticket_cause_transition_findings` (§5.11), referencing the authoritative
   `service_ticket_cause_transitions` row — not JSON-only.
3. **Classification:** a single controlled `system_group_key` plus free-text
   `component_label` (§5.3); no second broad classification tier in Phase 3.
4. **Cause transitions are first-class:** `service_ticket_cause_transitions` is the
   authoritative history; the Service timeline is a consumer, not the owner (§2, §5.10, §6).

---

## Boundary (in force for all of Phase 3)

- No migrations until this document is finalized (it is, as of 2026-07-26).
- No workbench redesign during Phase 3.
- Additive schema only.
- Preserve all current operational, financial, labor, settlement, and approval
  architectures unless explicitly commonized.
