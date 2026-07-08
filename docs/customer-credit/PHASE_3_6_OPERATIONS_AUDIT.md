# Phase 3.6 — Customer Resolution Operations Center: Pre-Implementation Audit

Last updated: 2026-07-06

---

## 1. What Was Reviewed

Per this phase's mission, before writing any code the following existing systems were read in full or in relevant part: the Resolution Center itself (`ResolutionCase`, `ResolutionCenterService`, all seven existing controllers/routes/views), Dispatch (`Dispatch/IndexController.php`, `dispatch/index.blade.php`, `OrderProduct`'s priority/status/assignment columns, `UpdateProductScheduleController`), Schedule Conflicts (`ScheduleConflicts/IndexController.php`, its Blade view), the admin Dashboard (`Dashboard/IndexController.php` and its partials), the CRM Customers list (`Crm/Customers/IndexController.php` and `index.blade.php`), and the shared front-end filter/pagination utilities (`resources/shared/js/{app,pagination,api}.js`). Full findings are recorded below; this section states only the conclusion each comparison led to.

## 2. Confirmed: `resolution_cases` Has None of the Fields This Phase Needs

Direct read of all three existing migrations confirms the table has **no `priority`, no status richer than `outcome` (pending/completed/cancelled), no assignment column, and no store-location column**. `responsible_person_id` exists but is set once at case creation (§Phase 3.3/3.4 design — "who opened this case," not "who currently owns working it") and is not a reassignable field. Everything this phase needs is new, additive schema.

## 3. UI Pattern Recommendation

**Recommendation: the AJAX-filtered data table pattern already used by Dispatch and CRM Customers — not the Schedule Conflicts full-page-reload pattern, and not a new kanban/board paradigm.**

Three relevant existing patterns were found, not one:

1. **Dispatch** (`Dispatch/IndexController.php` + `dispatch/index.blade.php`) — driver-workload cards at the top, an AJAX-refreshed data table below, filters posted via `fetch()` returning `{success, html, total}` JSON with server-rendered partial HTML, filter state persisted to `localStorage` via a shared `FilterFreezer` utility, pagination via a shared `Paginator.init()` utility.
2. **CRM Customers** (`Crm/Customers/IndexController.php` + `index.blade.php`) — the identical AJAX-partial-refresh convention, same three shared JS utilities (`FilterFreezer`, `Paginator`, `apiFetch`), same `{html, total}` response shape.
3. **Schedule Conflicts** (`ScheduleConflicts/IndexController.php`) — a full-page `GET` form (`onchange="this.form.submit()"`), no AJAX, no `localStorage` filter persistence, five hardcoded, differently-shaped problem sections each rendered as its own table.

The mission is explicit that this is "an operational work queue," not a report — that maps directly onto (1)/(2)'s single-entity, filterable, paginated, no-full-reload convention, not (3)'s stacked-report shape. This phase therefore reuses the Dispatch/CRM-Customers convention exactly: one `OperationsIndexController::__invoke(Request $request)` branching on `$request->ajax()`, the same three shared JS utilities wired the same way, one queue table (not five hardcoded sections). No kanban/drag-drop board exists anywhere in this codebase to model instead, and inventing one would be scope expansion the mission does not ask for.

**Dashboard metrics** (Dispatch/CRM-Customers convention doesn't have these) are modeled after the admin Dashboard's inline stat-tile markup (`bg-white rounded-xl shadow-sm p-6 border` containers) — no reusable `x-stat-card` Blade component exists anywhere in `resources/views/components/` (confirmed by direct search), so the tiles are hand-built the same way the Dashboard already builds its own, rather than inventing a new shared component this phase doesn't need elsewhere.

## 4. Schema Decisions (and why each was necessary)

All additive, nullable-or-defaulted, non-breaking to any existing column or query:

| New column (on `resolution_cases`) | Purpose | Why it couldn't reuse an existing column |
|---|---|---|
| `priority` (string, default `normal`) | Filter + queue display | No existing column; `OrderProduct.delivery_priority`/`pickup_priority` (integer) is Dispatch's precedent but lives on a different table/domain entirely. |
| `status` (string, default `open`) | The mission's 5-value operational filter (Open/In Progress/Waiting/Escalated/Completed) | `outcome` (pending/completed/cancelled) already exists and is written by `recordAnswers()`/`markOutcome()`/`approveAndIssueCredit()` for financial-lifecycle purposes — **redefining its meaning or its 3 existing values would be a business-policy change**, explicitly out of scope. `status` is a new, purely operational triage field, kept in sync with `outcome`'s terminal states (see §5) rather than replacing it. |
| `waiting_on` (string, nullable: `customer`/`employee`) | The mission's dashboard wants two distinct metrics ("Waiting on Customer" vs. "Waiting on Employee") but only one `Waiting` filter value — this sub-field answers the metric without adding a 6th top-level filter status the mission didn't ask for. | No existing column distinguishes this. |
| `assigned_to_user_id` (nullable FK → `users`) | "Which employee owns each case?" / Assign / Reassign | `responsible_person_id` is set once at creation and never reassigned by any existing code path — reusing it for "current owner" would silently change what that column has meant since Phase 3.3. |
| `store_id` (nullable FK → `stores`) | "Store Location" filter | **Neither `orders` nor `customers` has a `store_id` column at all** (confirmed by reading the `orders` migration and grepping `Customer.php`) — the only store reference in this app lives at the `order_products` line-item level (`delivery_store_id`/`pickup_store_id`), and is genuinely ambiguous for a multi-line-item order. A snapshot column on `resolution_cases`, populated best-effort at case creation, follows the exact precedent already set by this same table's `balance_snapshot`/`store_credit_snapshot` columns. When an order's line items resolve to more than one distinct store, this snapshot is left `null` rather than guessing — disclosed as a known limitation (§Completion Report), not silently resolved. |
| `completed_at` (nullable timestamp) | Accurate "Average Resolution Time" and "Completed Today" metrics | Approximating with `updated_at` would be wrong the moment any other field (a note, an assignment) is edited on an already-completed case — a dedicated timestamp, set once and never overwritten, was necessary for correctness. |

`created_at` (Age, Created Date) and `updated_at` (Last Activity) already exist and are reused as-is — no new column needed for either.

A new table, `resolution_case_activity_logs`, is added for the mission's "Audit Trail" requirement, directly copying the `{action, field, old_value, new_value, user_id}` shape already independently proven by `DispatchAuditLog` and `TaskActivityLog` elsewhere in this codebase — no shared base class or package exists to extend, so the shape is copied rather than a new abstraction invented.

## 5. `status` ↔ `outcome` Relationship (explicit, so it never has to be reverse-engineered later)

- `outcome` remains exactly as it was — written only by the same existing methods, with the same three values, for the same financial-lifecycle purpose.
- `status` is mirrored, not replacing: `markOutcome(..., OUTCOME_COMPLETED)` and `markOutcome(..., OUTCOME_CANCELLED)` both also set `status = completed` and `completed_at = now()` (if not already set) — from the operations queue's point of view, a cancelled case needs no more attention, same as a completed one; `outcome` retains the finer distinction for whoever needs it. `markOutcome(..., OUTCOME_PENDING)` — the mission's "Reopen Case" — resets `status = open` and clears `completed_at` if the case had been completed.
- `assignCase()` additionally moves `status` from `open` to `in_progress` the moment a case is assigned to a real user (not on unassignment). This is a deliberate, minimal workflow inference — "someone now owns this case" — not "automatic assignment" or "automatic prioritization" (both explicitly out of scope): the system never *chooses* who to assign or what priority to set, it only reflects that assignment happened.

## 6. Permissions

**No new permission module is added.** The four existing `resolution_center.*` permissions are reused: `view_audit_history` gates seeing the Operations Center (a superset, broader view of the same "see many cases" capability the existing plain history page already gates the same way); `override` gates every mutating manager action this phase adds (Assign, Reassign, Mark Waiting, Escalate, Close, Reopen) — matching the mission's own framing of these as "Manager Functions." This satisfies "follow the existing permission model, no hardcoded roles" without inventing new permission keys this phase's mission didn't ask for.

## 7. Out of Scope, Confirmed Not Touched

No changes to `LedgerBalanceService`, `CustomerCreditService`, `TaxCalculationService`, or any Financial Engine file. No AI, no automatic assignment/prioritization, no Promotional Credit/Gift Cards/Customer Portal/Collections/Payment Plans/Bad Debt work, no historical reporting or analytics beyond the mission's named operational metrics.
