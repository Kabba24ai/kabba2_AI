# Phase 3.6 — Customer Resolution Operations Center: Design

Last updated: 2026-07-06

---

## 1. Pre-Implementation Findings

See `PHASE_3_6_OPERATIONS_AUDIT.md` for the full audit — this section only restates the conclusions that shaped the design. The Resolution Center (Phases 3.3-3.5) had no operational fields beyond `outcome` (a financial-lifecycle field, not a triage one), no way to assign a case's current owner, no priority, and no store-location concept reachable from a case at all. Three different existing UI conventions were found (Dispatch/CRM Customers' AJAX-partial-refresh, Schedule Conflicts' full-page-GET, and the Dashboard's hand-built stat tiles); this phase adopted the first for the work queue and the third's plain-markup approach for stat cards, since no shared component exists for either.

## 2. Architecture

**No changes to `outcome`, `LedgerBalanceService`, `CustomerCreditService`, or any Financial Engine file.** Everything this phase adds is additive:

- `resolution_cases` gained `priority`, `status`, `waiting_on`, `assigned_to_user_id`, `store_id`, `completed_at` (see audit §4 for why each was necessary and why none could reuse an existing column).
- A new `resolution_case_activity_logs` table backs the Audit Trail, following the `{action, field, old_value, new_value, user_id}` shape already proven by `DispatchAuditLog`/`TaskActivityLog`.
- `ResolutionCenterService` gained: `assignCase()`, `setPriority()`, `markWaiting()`, `escalate()`, `closeCase()`/`reopenCase()` (thin wrappers around the existing `markOutcome()`), `dashboardMetrics()`, and a private `logActivity()`/`resolveStoreId()` pair. Every existing mutating method (`startCase`, `recordAnswers`, `recordDecision`, `approveAndIssueCredit`, `markOutcome`) now also writes an activity-log row — the Audit Trail covers every case mutation, not only the new operational actions.
- `markOutcome()` and `approveAndIssueCredit()` were extended (not rewritten) to mirror `status`/`completed_at` whenever `outcome` reaches a terminal state — see audit §5 for the exact, deliberate rule.

## 3. The Work Queue

`OperationsIndexController` (`GET /resolution-center/operations`) follows the exact Dispatch/CRM-Customers convention: `__invoke(Request $request)`, branch on `$request->ajax()`, filtered/paginated `ResolutionCase` query, render `operations/partials/_table.blade.php`, return `{html, total}` JSON. The full-page load additionally computes `ResolutionCenterService::dashboardMetrics()` and passes filter-option data (registered scenarios, active employees, active stores, issue categories).

Each queue row shows exactly the columns the mission specified: Case Number, Customer, Order, Resolution Scenario, Issue Category, Assigned Employee, Status, Recommended Resolution, Priority, Created Date, Last Activity (`updated_at`), Age (`created_at` diff), and row actions gated on `resolution_center.override` (Assign, Escalate, Close/Reopen).

## 4. Filters

Status, Assigned To, Scenario, Issue Category, Priority, Store Location, and a Created-date range — all implemented as plain `$request->filled()` conditionals on `OperationsIndexController`'s query, the same hand-rolled convention every existing filtered list page in this app already uses (there is no shared filter trait/scope to call into — see audit §"Existing filtering framework"). Filter state persistence and pagination reuse the existing shared JS utilities (`FilterFreezer`, `Paginator`, `apiFetch`) exactly as Dispatch/CRM Customers already wire them.

## 5. Manager Functions

| Function | Route | Permission | Implementation |
|---|---|---|---|
| Assign / Reassign Case | `POST .../{unique_id}/assign` | `resolution_center.override` | `ResolutionCenterService::assignCase()` — copies Dispatch's FK-plus-dropdown modal pattern exactly (a single shared modal, not a per-row inline control). |
| Mark Waiting | `POST .../{unique_id}/wait` | `resolution_center.override` | `markWaiting()` — requires `waiting_on` (customer/employee), optional note. |
| Escalate | `POST .../{unique_id}/escalate` | `resolution_center.override` | `escalate()` — optional reason, logged as its own activity row. |
| Close Case | `POST .../{unique_id}/close` | `resolution_center.override` | Thin wrapper around the existing `markOutcome(OUTCOME_COMPLETED)` — no new business logic. |
| Reopen Case | `POST .../{unique_id}/reopen` | `resolution_center.override` | Thin wrapper around the existing `markOutcome(OUTCOME_PENDING)`. |
| Manager Override / Internal Notes | *(unchanged)* | `resolution_center.override` / `.use` | Already fully supported by the existing case-detail page (`DecisionController`) since Phase 3.3 — not duplicated here, only surfaced via the queue's link to the case detail screen. |

**No automatic financial actions anywhere in this phase** — Assign/Reassign/Mark Waiting/Escalate/Close/Reopen never call `CustomerCreditService`. The only Store-Credit-issuing path remains the existing "Approve & Issue" action on the case detail page.

## 6. Dashboard Metrics

`ResolutionCenterService::dashboardMetrics()` computes, on every request: Open Cases, Waiting on Customer, Waiting on Employee, Manager Review Required, Completed Today, Average Resolution Time (hours, only over cases completed today), Oldest Open Case (age in days), and Store Credit Issued Today (summed from `customer_credits` rows reachable via `resolution_cases.credit_issued_id`). These are current-queue-state aggregates only — no date-range picker, no export, no trend chart — deliberately, per the mission's "not historical reporting" boundary.

## 7. Case Detail Additions

`show.blade.php` gained a second summary-card row (Status, Priority, Assigned To, Store) and a new "Audit Trail" section (gated on `resolution_center.view_audit_history`) rendering `$case->activityLogs` — every mutation this or any prior Resolution Center phase makes, in one place. No existing section of this page was removed or restructured; the Cancellation/Refund wizard and Manual Resolution form (Phase 3.5) are unchanged.

## 8. Permissions

No new permission module. `resolution_center.view_audit_history` gates the Operations Center itself (a superset of the same "view many cases" capability the existing history page already gates the same way); `resolution_center.override` gates every manager action this phase adds, matching the mission's own "Manager Functions" framing.

## 9. Out of Scope (unchanged from the mission)

No AI recommendations, no automatic assignment or prioritization, no Promotional Credit/Gift Cards/Customer Portal/Collections/Payment Plans/Bad Debt work, and no historical reporting or analytics beyond the eight named operational metrics above.
