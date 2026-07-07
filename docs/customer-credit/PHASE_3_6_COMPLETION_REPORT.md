# Phase 3.6 — Customer Resolution Operations Center: Completion Report

Last updated: 2026-07-06

---

## Executive Summary

The Customer Resolution Operations Center is complete: an internal work-queue dashboard for monitoring, prioritizing, assigning, and completing Resolution Cases across both existing scenarios (Cancellation/Refund, Manual Resolution). Unlike Phase 3.5, this phase found no pre-existing work — it was built from a full pre-implementation audit through to a validated, tested feature. See `PHASE_3_6_OPERATIONS_AUDIT.md` and `PHASE_3_6_OPERATIONS_CENTER.md` for the audit and design detail this report summarizes.

## Files Created

- `docs/customer-credit/PHASE_3_6_OPERATIONS_AUDIT.md`, `docs/customer-credit/PHASE_3_6_OPERATIONS_CENTER.md`, this file.
- `database/migrations/customers/2026_07_06_090000_add_operations_fields_to_resolution_cases_table.php` — adds `priority`, `status`, `waiting_on`, `assigned_to_user_id`, `store_id`, `completed_at` to `resolution_cases`.
- `database/migrations/customers/2026_07_06_090100_create_resolution_case_activity_logs_table.php` — the Audit Trail's table.
- `app/Models/Customers/ResolutionCaseActivityLog.php`.
- `app/Http/Controllers/Admin/ResolutionCenter/Operations{Index,Assign,Priority,MarkWaiting,Escalate,Close,Reopen}Controller.php` (7 files).
- `app/Http/Requests/Admin/ResolutionCenter/Operations{Assign,Priority,MarkWaiting,Escalate}Request.php` (4 files).
- `resources/views/admin/resolution_center/operations/index.blade.php` and `operations/partials/_table.blade.php`.
- `tests/Unit/Services/ResolutionCenterOperationsTest.php` — 4 DB-free tests covering `isValidStatus()`/`isValidPriority()`/`isValidWaitingOn()` and the deliberate `status`/`outcome` constant-overlap invariant.

## Files Modified

- `app/Models/Customers/ResolutionCase.php` — new fillable fields, `completed_at` cast, `assignedTo()`/`store()`/`activityLogs()` relations.
- `app/Services/ResolutionCenterService.php` — new constants (`STATUS_*`, `WAITING_ON_*`, `PRIORITY_*`), `isValidStatus()`/`isValidPriority()`/`isValidWaitingOn()`, `logActivity()`/`resolveStoreId()` (private), `assignCase()`, `setPriority()`, `markWaiting()`, `escalate()`, `closeCase()`/`reopenCase()`, `dashboardMetrics()`; `startCase()` now snapshots `store_id` and an explicit `priority`, and logs `case_opened`; `recordAnswers()`/`recordDecision()`/`approveAndIssueCredit()` now log activity; `markOutcome()`/`approveAndIssueCredit()` now mirror `status`/`completed_at` on terminal `outcome` values.
- `app/Http/Controllers/Admin/ResolutionCenter/ShowController.php` — eager-loads `assignedTo`, `store`, `activityLogs.user`.
- `resources/views/admin/resolution_center/show.blade.php` — added a Status/Priority/Assigned/Store summary row and an Audit Trail section; no existing section restructured.
- `resources/views/admin/resolution_center/index.blade.php` — added a link to the new Operations Center.
- `routes/admin/resolution_center/routes.php` — 7 new routes (`operations`, `assign`, `priority`, `wait`, `escalate`, `close`, `reopen`), `/operations` deliberately registered before the `/{unique_id}` wildcard.

## Existing Workflow Preserved

Verified live (see Validation Results): a case started with no `scenarioKey` argument still defaults to `cancellation_refund`, still gets `status=open`/`priority=normal`, and its own `recommend()` (`can_reschedule=true` → `free_reschedule`) is byte-for-byte unchanged. `outcome`'s three values and every existing caller of `markOutcome()`/`recordDecision()`/`approveAndIssueCredit()` are unmodified in meaning — only additionally mirrored into the new `status` field, per the explicit, documented rule in `PHASE_3_6_OPERATIONS_AUDIT.md` §5.

## Business Policies Enforced

- No automatic financial actions: Assign, Reassign, Mark Waiting, Escalate, Close, and Reopen never call `CustomerCreditService`. The only Store-Credit-issuing path remains the pre-existing "Approve & Issue" action.
- No automatic assignment or prioritization: the system never chooses an assignee or a priority — it only reflects that a manager did (including the one deliberate inference — assigning a still-`open` case moves it to `in_progress`, documented in the audit as "reflects ownership," not "the system decided who").
- No new permission module: every action reuses the four existing `resolution_center.*` permissions, per the mission's "follow the existing permission model, no hardcoded roles."

## Validation Results

Ran live against the real, unmodified/extended `ResolutionCenterService` (and `CustomerCreditService`), inside a rolled-back `DB::transaction()`, using synthetic customer/order/employee rows. The two new migrations were run for real against the local dev database first (matching this project's established precedent for schema that previous phases' own migrations also ran for real, not only inside the rolled-back script). Three pre-existing, unrelated local-database schema gaps (`order_payments.deleted_at`, `order_products.deleted_at`, `users.deleted_at`/`employee_code`) were patched for this script only, inside the same transaction, and rolled back with everything else.

Checks performed, all passed:

1. `startCase()` with the Manual Resolution scenario → `status=open`, `priority=normal` (explicit, not just relying on the DB column default — see the sub-finding below), `store_id=null` (correctly, since the synthetic order had no line items to snapshot a store from).
2. `assignCase()` to a real user on an `open` case → `assigned_to_user_id` set, `status` moved to `in_progress`.
3. `setPriority()` → `urgent` persisted.
4. `markWaiting('customer', ...)` → `status=waiting`, `waiting_on=customer`, note written to `notes`.
5. `escalate()` → `status=escalated`.
6. `recordAnswers()` + `approveAndIssueCredit()` → `outcome=completed`, **`status` correctly mirrored to `completed`**, `completed_at` set.
7. `reopenCase()` (→ `markOutcome(OUTCOME_PENDING)`) → `outcome=pending`, `status` reset to `open`, `completed_at` cleared.
8. `closeCase()` (→ `markOutcome(OUTCOME_COMPLETED)`) → `outcome=completed`, `status=completed`.
9. **Audit Trail**: 12 activity-log rows present after the above sequence, covering every mutation from case-open through the final close — not only the new operational actions.
10. `assignCase(null, ...)` (unassign) → correctly logged as `unassigned`, and — correctly — did **not** force `status` back to `open` (only assignment *to* a real user does that).
11. `dashboardMetrics()` ran without error mid-sequence and reflected the transaction's own state accurately (`completed_today=1`, `store_credit_issued_today=25`, matching the $25 credit issued in step 6).
12. A case started with **no** `scenarioKey` argument (the pre-Phase-3.6 call shape) → `scenario_key=cancellation_refund`, `status=open`, `priority=normal`; its own `recordAnswers(['can_reschedule' => true])` → `free_reschedule`, unchanged.
13. `setPriority()` with an invalid value (`'critical'`) → correctly threw `InvalidArgumentException`.
14. `markWaiting()` with an invalid `waiting_on` (`'manager'`) → correctly threw `InvalidArgumentException`.
15. Post-rollback: `customers`, `orders`, `resolution_cases`, `resolution_case_activity_logs`, `users`, `customer_credits` all confirmed `0` via direct SQL; the transient schema patches confirmed absent from the real schema via `PRAGMA table_info`; the two real, intended migrations confirmed present.

**One genuine, small defect found and fixed during this validation**: the first version of `startCase()` set `status` explicitly but relied on the `priority` column's database-level default (`'normal'`) rather than setting it explicitly in the `create()` call. Since Eloquent doesn't re-fetch a model after `create()` on this SQLite connection, the in-memory `$case->priority` was empty immediately after creation (only a subsequent `->fresh()` would have shown `'normal'`). Fixed by setting `'priority' => self::PRIORITY_NORMAL` explicitly, matching how `status`/`outcome` were already set. Caught by this phase's own live validation (check #1 above), not by static reading — confirms why the live-transaction validation step matters, not just Blade/PHP linting.

Both new Blade views and the case-detail additions were compiled through the real, fully-booted `BladeCompiler` (resolving `x-*` component tags via the actual service container) and linted — no syntax errors.

## Test Results

- `tests/Unit` suite: **91/91 passing** (87 pre-existing + 4 new `ResolutionCenterOperationsTest`, covering the DB-free validation guards only — `isValidStatus()`/`isValidPriority()`/`isValidWaitingOn()` and the `status`/`outcome` constant-overlap invariant).
- `tests/Feature` suite: **not run** — same standing environment limitation documented since Phase 2.1 (no configured test database).
- The DB-backed behavior (assignment, status transitions, activity logging, dashboard metrics, and the interaction with the existing Cancellation/Refund and Manual Resolution scenarios) was validated live, per the precedent established since Phase 2.4, not via a committed automated test — this project's standard `RefreshDatabase` harness cannot run cleanly here (MySQL-only migrations incompatible with SQLite).

## Risks

- **Store-location snapshotting is genuinely approximate for multi-store orders**, by design (see audit §4) — `store_id` is `null` whenever an order's line items span more than one store, rather than guessing. The "Store Location" filter will not surface such cases under any single store; this is disclosed, not silently resolved.
- **No automated Feature/browser test exists for the new UI** — the work queue, filter bar, stat cards, and manager-action buttons were verified via Blade compilation (real container) and the live service-layer validation above, not by driving the actual HTTP request/response cycle or a browser. A manual click-through in a real environment is recommended before this ships.
- **The three local-database schema gaps patched during validation** (`order_payments`/`order_products`/`users` missing columns) are pre-existing environment drift, not introduced by this phase — flagged again here since this is now the third phase to encounter and work around them (Phase 3.5 found the first two; this phase additionally found `order_products.deleted_at` missing).
- **`dashboardMetrics()` recomputes on every Operations Center request** (both full page load and each AJAX filter refresh, per `OperationsIndexController`) — cheap aggregate `COUNT`/`SUM` queries today, but worth revisiting if the `resolution_cases` table grows large enough that this becomes a real per-request cost; no caching was added, since the mission didn't ask for it and premature caching would be scope expansion.

## Overall Epic Progress

Customer Credit Platform (Epic 2): Phase 3.0 through 3.6 all complete. The Resolution Center now has a genuine operational management layer on top of the case-creation/guided-resolution foundation Phases 3.3-3.5 built — assignment, prioritization, escalation, and a real audit trail, usable across both existing scenarios and any future one without scenario-specific changes (the Operations Center reads `scenario_key`/`recommended_resolution` generically, the same way the framework's other generic surfaces already do).

## Recommendation for Phase 3.7

Confirm business priority before choosing (this phase, like 3.4/3.5 before it, deliberately does not guess):

1. **Manual, real-environment click-through of the Operations Center** — the one validation gap this phase couldn't close in this environment (no browser, no test database). Recommended before wide rollout regardless of what's built next.
2. A third `ResolutionScenario` (per Phase 3.5's own still-open recommendation list) — the Operations Center now makes a second/third scenario's cases immediately visible in the same queue with zero additional work, which may make this a more attractive next step than before.
3. Resolving the `credit_category` schema question blocking Promotional Credit, if that initiative is becoming a near-term priority.
4. A genuine reassignment-history/notification layer — this phase's Audit Trail records *that* a reassignment happened, but (matching Dispatch's own precedent, which has the same gap) does not notify the newly-assigned employee. Worth a deliberate scope decision, not an assumption, before building it.
