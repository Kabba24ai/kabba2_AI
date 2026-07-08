# gary_dev Deployment Plan — Financial Engine + Customer Credit Platform + Resolution Center

Last updated: 2026-07-06
Status: **PLANNING ONLY — nothing has been committed, pushed, merged, or deployed.** No application code was changed to produce this document. `gary_dev` is treated throughout as the real staging environment (real MySQL, not this sandbox's SQLite) — every validation step below assumes it runs there, not locally.

This plan builds directly on `docs/release-readiness/RELEASE_READINESS_AUDIT.md` (the full file/PR/migration/permission inventory) — that document is the source of truth for *what* is changing; this document is about *how it lands on `gary_dev` and what proves it's safe to go further*. Where the two overlap, this document doesn't repeat full detail, it cross-references.

---

## 1. Current Branch Status

- **Local work lives on `raj_development`**, entirely uncommitted (confirmed via `git status` — see the audit's §1 for the full file inventory). `raj_development` is in sync with `origin/raj_development` (HEAD `ec31ac5a`, PR #546 already merged — the one piece of this initiative already live).
- **`gary_dev` exists on the remote** (`origin/gary_dev`, HEAD `e0113473`) but is **133 commits behind `raj_development`** — confirmed via `git rev-list --count origin/gary_dev..raj_development` and `git merge-base --is-ancestor`. `gary_dev`'s last sync from `raj_development` was its own commit `b6a46584` ("Merge remote-tracking branch 'raj/raj_development' into gary_dev"), dated 2026-06-26; `raj_development` has moved on since (through 2026-07-01) with unrelated work — home page redesign, Supplier/Equipment Management UI fixes, fuel/damage report reconnection, and PR #546 itself.
- **Practical consequence**: branching new PRs off current `raj_development` and merging them into `gary_dev` will bring `gary_dev` those 133 unrelated commits *as well as* this initiative's work, in the same merge. This is very likely the intended outcome (staging should track `raj_development`), but it is a real, non-trivial side effect worth confirming with whoever owns `gary_dev` before merging — it is not scoped or reviewed by this plan, only disclosed.
- **Nothing from this initiative exists on `gary_dev` today.** Every migration, permission, service, and view described in the audit is entirely new to that environment.

---

## 2. Splitting Local Work Into PRs / Commits

Identical grouping to `RELEASE_READINESS_AUDIT.md` §2 — 7 PRs, each branched from current `raj_development` (which already includes PR #546), each targeting `gary_dev` instead of `raj_development`/`main` for this phase:

| # | PR | Depends on |
|---|---|---|
| 1 | Platform Stabilization Sprint 1 (`Module`/`Permission` model defaults + `ModuleSeeder` mechanism fix) | — |
| 2 | Financial Engine Modernization (Phases 2.1–2.8A) | — (independent of 1 to run, but sequenced first per §3) |
| 3 | Customer Credit Service Foundation (`CustomerCreditService`, `CustomerCredit`, 3 migrations, `customer_credit` module) | PR 1 |
| 4 | Customer Credit Administration (CRM tab) | PR 3 |
| 5 | Order Entry Credit Integration | PR 3 |
| 6 | Customer Resolution Center (Phases 3.3–3.5) | PR 1, PR 3 |
| 7 | Resolution Operations Center (Phase 3.6) | PR 6 |

**Exclude from every PR** (per the audit's §1.1/§9): the ~140 unrelated `storage/app/public/**`/`.gitignore` file changes already present in the working tree, and the stray empty `artisan-output.txt`. Stage only the files each PR actually owns.

**Commit message convention**: since none of this has been committed yet, use one commit per PR (or a small number of logically-grouped commits within it) referencing the phase docs already written (e.g. `docs/customer-credit/PHASE_3_6_COMPLETION_REPORT.md`) so reviewers can find the full rationale without it being repeated in the commit body.

---

## 3. Recommended Merge Order Into `gary_dev`

1. **Sync `gary_dev` with `raj_development` first**, before any new PR — either merge `raj_development` into `gary_dev` directly or rebase the 7 new branches on top of current `raj_development` and let each merge bring the sync in incrementally. Either way, do this as its own deliberate step, not silently inside PR 1's merge — see §1's disclosure.
2. **PR 1 — Platform Stabilization Sprint 1.** Merge and deploy to `gary_dev` alone first. Run `ModuleSeeder` on `gary_dev` immediately after (§5) — this proves the seeder mechanism works against real MySQL before anything else depends on it.
3. **PR 2 — Financial Engine Modernization.** Independent of PR 1's files; can merge in parallel or immediately after. Run the Financial Validation Checklist (§8) once deployed.
4. **PR 3 — Customer Credit Service Foundation.** Re-run `ModuleSeeder` after merging (idempotent, picks up the `customer_credit` module).
5. **PR 4 and PR 5** — Customer Credit Administration and Order Entry Integration, either order (both depend only on PR 3).
6. **PR 6 — Customer Resolution Center.** Re-run `ModuleSeeder` after merging (picks up `resolution_center`).
7. **PR 7 — Resolution Operations Center.** Last, since it depends on everything above.

Each step should be individually validated on `gary_dev` before the next begins — this plan intentionally does not treat the 7 PRs as one atomic deployment, so a problem found at step 4 doesn't invalidate the already-proven steps 1–3.

---

## 4. Migration Validation Plan on MySQL

This is the single highest-priority validation in this entire plan — **every migration and every live-transaction validation claim in every phase's completion report was proven against local SQLite only.** `gary_dev` is the first time any of this touches a real MySQL database.

1. Confirm `gary_dev`'s environment (`.env` on that deployment) points at a real MySQL instance, and take a schema snapshot (or a full backup, if `gary_dev` carries any data worth preserving) before running anything.
2. `php artisan migrate:status` on `gary_dev` **before** merging any PR — establish the baseline (expect all 8 new migrations absent/pending).
3. After each PR that carries migrations (PRs 3, 6, 7 per the dependency chain in the audit's §3) merges, run `php artisan migrate` and capture full output. Confirm each migration reports `DONE`, in this exact order (Laravel enforces this by filename automatically, but confirm it wasn't reordered by a bad merge):
   - `2026_07_03_141905_create_customer_credits_table`
   - `2026_07_03_180828_add_effective_date_and_internal_comments_to_customer_credits_table`
   - `2026_07_04_090000_add_order_id_to_customer_credits_table`
   - `2026_07_04_120000_create_resolution_cases_table`
   - `2026_07_04_150000_add_scenario_key_to_resolution_cases_table`
   - `2026_07_04_180000_add_issue_category_to_resolution_cases_table`
   - `2026_07_06_090000_add_operations_fields_to_resolution_cases_table`
   - `2026_07_06_090100_create_resolution_case_activity_logs_table`
4. **Specifically confirm on real MySQL** (things SQLite could not tell us, per the audit's §6):
   - `customer_credits.amount`, `resolution_cases.balance_snapshot`/`store_credit_snapshot` persist as real `DECIMAL(15,2)`, not text.
   - The foreign keys in `add_operations_fields_to_resolution_cases_table` (`assigned_to_user_id` → `users`, `store_id` → `stores`) and `create_resolution_case_activity_logs_table` (`resolution_case_id` → `resolution_cases`, `user_id` → `users`) actually enforce referential integrity under MySQL's stricter FK handling — attempt an invalid insert and confirm it's rejected.
   - `customers.available_credit_balance`'s real column type on `gary_dev` (expected `DECIMAL(15,2)` if the pre-existing MySQL-only migration for it has already run there).
5. **Test rollback on `gary_dev` before moving on**: run `php artisan migrate:rollback` for the last-applied batch, confirm all new tables/columns/FKs are cleanly removed with no orphaned rows, then re-run `php artisan migrate` to restore state. Do this while `gary_dev` still has no real production-meaningful data in these tables — it is the one environment where this test is actually safe to perform for real.
6. Confirm presence (or genuine absence) of `order_payments.deleted_at`, `order_products.deleted_at`, `users.deleted_at`, `users.employee_code` on `gary_dev`'s real schema (the audit's §6 item 3) — this sandbox could never determine whether these are a real gap or just a local artifact of an unrun MySQL-only migration. If genuinely missing, `CustomerCreditService`/`ResolutionCenterService` will error on first real use; add the missing migration(s) before proceeding to §9/§10 validation.

---

## 5. Seeder Validation Plan

Only `database/seeders/Iam/ModuleSeeder.php` is affected (modified, not new — see audit §4).

1. **Do not run it until PR 1 is merged** — without the `Module`/`Permission` model defaults, it will fail on `gary_dev` with the same `NOT NULL` constraint violations Platform Stabilization Sprint 1 found and fixed locally (this is a genuine, pre-existing defect, reproduced against the untouched `personnel` module — it will reproduce identically on real MySQL).
2. Snapshot `gary_dev`'s current `modules`/`permissions`/`role_has_permissions` tables before running it, so the next step's "before/after" comparison is real, not assumed.
3. Run `php artisan db:seed --class="Database\Seeders\Iam\ModuleSeeder"` (or the project's equivalent invocation) on `gary_dev` after PR 1 merges.
4. Confirm via direct query: no existing module/permission row was altered (row counts for every pre-existing module unchanged; `id`s stable).
5. **Run it a second time immediately** — confirm idempotency for real (no duplicate `customer_credit`/`resolution_center` rows, no error) before trusting it in the sequence described in §3.
6. After PR 3 merges, re-run and confirm the `customer_credit` module (6 permissions: `view`, `grant`, `redeem`, `reverse`, `delete`, `view_audit_history`) now exists.
7. After PR 6 merges, re-run and confirm the `resolution_center` module (4 permissions: `view`, `use`, `override`, `view_audit_history`) now exists.
8. Confirm `Master Admin` role received all 10 new permissions automatically (`setPermissionToMasterAdmin()` — verified by reading the seeder, but confirm the real effect on `gary_dev`'s actual `Master Admin` role, not just trust the code).

---

## 6. Permission Assignment Plan

Deploying the code and re-running `ModuleSeeder` only grants the 10 new permissions to `Master Admin` (automatic, per the seeder's own logic) — **every other role that should use these features needs manual assignment**, and this has no code path in this release (per the audit's §5/§9).

1. Before merging PR 4/PR 5 (Customer Credit UI), confirm with the business owner which real `gary_dev` test roles/users should exercise Grant/Redeem/view credit history, and assign `customer_credit.view`/`.grant`/`.redeem`/`.view_audit_history` to them via the existing Roles UI (not a script — none exists for this).
2. Before merging PR 6/PR 7 (Resolution Center + Operations Center), similarly assign `resolution_center.view`/`.use`/`.override`/`.view_audit_history` to whichever test roles will drive the Browser/UI Validation Checklist (§7) and the Resolution Center Validation Checklist (§10) — in particular, at least one test account **without** `.override` is needed to validate the negative-permission paths (Manager Override fields hidden, Operations Center manager buttons absent).
3. Leave `customer_credit.reverse` and `customer_credit.delete` unassigned to any non-Master-Admin role for now — nothing in the codebase calls them yet (confirmed via repository-wide grep); assigning them prematurely only invites confusion about what they're supposed to unlock.
4. Record exactly which `gary_dev` test accounts received which permissions, so the Go/No-Go review (§12) can confirm real positive-and-negative-path testing happened, not just Master-Admin-only clicking.

---

## 7. Browser/UI Validation Checklist

Every screen below was verified in this sandbox only via Blade-compilation (a real container catching syntax errors) and live service-layer calls inside a rolled-back database transaction — **never an actual browser**. This is the first real opportunity to close that gap.

- [ ] CRM Customer record → new **Customer Credit tab** renders: summary cards, Grant dialog, Redeem dialog, filterable/sortable history table
- [ ] Grant dialog: submit a real grant, confirm balance updates and a new history row appears without a page reload issue
- [ ] Redeem dialog: submit a real redemption ≤ available balance (succeeds) and one > available balance (rejected with a clear error, not a 500)
- [ ] Negative path: a test account **without** `customer_credit.*` permissions sees no Customer Credit tab at all
- [ ] Order Edit screen → **Customer Credit panel**: Apply Credit and Remove Applied Credit both work against a real order, respecting the order's balance
- [ ] Order Edit screen → **"Start Resolution Case" panel**: the scenario picker (added in Phase 3.5) lists both `Cancellation / Refund` and `Manual Resolution`; starting a case under each produces the correct downstream flow
- [ ] Resolution Center case detail (Cancellation/Refund scenario): full 3-step wizard (reschedule → credit → payment method), recommendation display, "Approve & Issue Store Credit" form, Employee Decision form (including Manager Override fields, visible only to `.override` holders), Audit Trail section populated
- [ ] Resolution Center case detail (Manual Resolution scenario): the single-step "Categorize & Resolve" form, recommendation echo-back with contextual next-step text, same Issue Credit/Decision/Audit Trail sections
- [ ] Resolution Center → **history list** (`/resolution-center`) and the link to the new **Operations Center** both work
- [ ] **Operations Center** (`/resolution-center/operations`): all 8 stat cards render with real numbers; every filter (Status, Priority, Assigned To, Scenario, Issue Category, Store, Date Range) narrows the queue correctly, individually and combined; pagination works
- [ ] Operations Center manager actions: Assign (shared modal), Escalate, Close, Reopen all work from the queue without a full page reload, and the queue/stat cards reflect the change on next refresh
- [ ] Negative path: a test account **without** `resolution_center.override` sees no Assign/Escalate/Close/Reopen buttons in the Operations Center row actions
- [ ] Cross-browser sanity (at minimum, the browser the CRM team actually uses) — no console errors on any of the above screens

---

## 8. Financial Validation Checklist

Covers PR 2 (Financial Engine) — independent of Customer Credit/Resolution Center, but must pass before treating `gary_dev` as validated overall, since later features (Resolution Center's Store Credit issuance, in particular) sit downstream of the same customer-balance concepts.

- [ ] One real Payment recorded through each of the 7 migrated call sites (`CustomerAccount/PaymentStoreController`, `Invoice/PaymentStoreController` admin, `Dashboard/PaymentStoreController`, front-end `Invoice/PaymentStoreController`, `ChargeService::recordPayment()`, `AddToAccountPaymentController`, `Front/Checkout/PostController`) — confirm the resulting ledger row and `customers.available_credit_balance` match what the pre-migration `CustomerHelper::updateCreditBalance()` would have produced (spot-check the arithmetic by hand for at least one case per site)
- [ ] One real Order created through front-end checkout — confirm `LedgerBalanceService::applyTransaction()`'s Order path behaves identically to before
- [ ] Billing Summary "Last Payment" column displays the correct, non-double-taxed amount (the original Phase 1 fix, PR #546 — already in production, but re-confirm it still holds after this batch's `CustomHelper` changes)
- [ ] Dashboard revenue figures (`Dashboard\IndexController::getRevenueRows()`) match manual calculation for a known period — confirms the Phase 2.3 division-based tax-extraction fix still holds
- [ ] `SalesReportEngineV2` totals for a known period match pre-migration figures (or, if no pre-migration baseline is available on `gary_dev`, match hand-calculated expected totals)
- [ ] Invoice payment flow: `paid_amount`/`open_amount`/`invoice_status` update correctly after a real invoice payment (the `InvoiceCalculationService` migration) — check both the admin and front-end invoice payment controllers
- [ ] No error-log entries referencing `TaxCalculationService`, `InvoiceCalculationService`, or `LedgerBalanceService` after exercising all of the above

---

## 9. Customer Credit Validation Checklist

Covers PRs 3–5.

- [ ] `CustomerCreditService::createFinancialCredit()` produces a real `customer_credits` row with correct `type='grant'`, amount, reason, and `responsible_person_id`
- [ ] `CustomerCreditService::redeem()` correctly rejects an amount exceeding `remainingBalance()` and correctly succeeds for a valid amount
- [ ] Idempotency key protection: submitting the same grant/redemption request twice with the same idempotency key does not double-apply it
- [ ] `remainingBalance()`/`summaryForCustomer()` match a hand-computed sum of grants minus redemptions for a real test customer with multiple entries
- [ ] Order Entry integration: `appliedToOrder()` correctly reflects net redemptions minus reversals for a specific order after Apply/Remove cycles
- [ ] Confirmed **zero interaction** with `LedgerBalanceService`/`CustomHelper`/`customers.available_credit_balance` for any Customer Credit operation (a grant/redemption must not move the customer's existing running balance) — this is the architectural boundary every phase report insists on; verify it holds under real MySQL, not just by reading the code
- [ ] CRM Credit tab's history table sorts/filters correctly against a customer with more than one page of history

---

## 10. Resolution Center Validation Checklist

Covers PRs 6–7.

- [ ] A Cancellation/Refund case reaches all four terminal recommendations (Free Reschedule; Issue Store Credit; Standard Refund; Waive Refund Fee) correctly depending on the answers given
- [ ] A Manual Resolution case, for at least 3 of the 10 issue categories and at least 3 of the 7 resolution outcomes, correctly echoes back the selected resolution and its contextual next-step text
- [ ] Store Credit issuance (either scenario) correctly calls `CustomerCreditService::createFinancialCredit()`, referencing the originating order, and correctly flips the case to `outcome=completed`/`status=completed`/`completed_at` set
- [ ] Attempting to issue Store Credit for a case whose recommendation is **not** a service-executable action is correctly rejected
- [ ] Reopen (`markOutcome(OUTCOME_PENDING)`) correctly resets `status` to `open` and clears `completed_at` on a previously-completed case
- [ ] Assign/Reassign/Unassign all correctly update `assigned_to_user_id` and log to the Audit Trail; assigning a real user to an `open` case moves it to `in_progress`; unassigning does **not** force status backward
- [ ] Mark Waiting correctly distinguishes "waiting on customer" vs. "waiting on employee" (both the case record and the Operations Center's two corresponding stat cards)
- [ ] Escalate correctly moves a case to `escalated` and appears under "Manager Review Required" on the Operations Center
- [ ] The Audit Trail on a case that has been through several of the above actions shows a complete, correctly-ordered history — every mutation, not just the newest one
- [ ] `dashboardMetrics()`'s "Store Credit Issued Today" figure matches the sum of real credits issued through the Resolution Center that day
- [ ] Confirm the store-location snapshot (`resolution_cases.store_id`) is correctly populated for a single-store order and correctly `null` for a genuinely multi-store order (per the audit's disclosed limitation — confirm it fails safe, not silently wrong)

---

## 11. Rollback Plan

`gary_dev` being staging (not production) means rollback here is lower-stakes than the production rollback concerns in the audit's §7 — but the plan should still be exercised for real here, since this is the one environment where doing so is safe.

- **Schema rollback**: all 8 migrations have reversible `down()` methods (verified). On `gary_dev`, actually run `php artisan migrate:rollback` for a given batch as part of §4's validation, not just trust the code — confirm no orphaned FK references or leftover data after rollback.
- **Code rollback**: since each of the 7 PRs is a separate, ordered merge into `gary_dev`, reverting is a normal `git revert`/branch-reset of the most recent PR(s) — because of the dependency order in §2, always roll back in exact reverse merge order (e.g. don't revert PR 3 while PR 6/7 are still merged, since they depend on it).
- **Seeder rollback**: `ModuleSeeder` has no automatic "un-seed" (it's not a migration) — if the new permission modules need to be removed from `gary_dev`, that is a manual deletion of the `customer_credit`/`resolution_center` rows from `modules`/`permissions` and any `role_has_permissions` rows referencing them. Write this down as a manual runbook step if `gary_dev` needs a clean reset, not something to improvise under pressure.
- **Full-environment reset option**: since `gary_dev` is staging, if validation reveals a serious problem, a full database restore-from-snapshot (per §4 step 1's pre-change backup) is a legitimate, simpler alternative to piecemeal rollback — prefer this if multiple PRs have already merged and a clean re-validation from scratch is more valuable than surgical reversal.
- **What rollback does NOT need to cover here**: real customer financial data. `gary_dev` should only ever contain test/synthetic data for this validation — confirm this is true before starting (if `gary_dev` has been used for other, unrelated testing with data worth preserving, coordinate the backup/restore boundary with whoever owns that data first).

---

## 12. Go/No-Go Checklist Before Production

All of the following must be true before any of this work proceeds past `gary_dev` toward `raj_development`/production:

- [ ] All 8 migrations applied cleanly on `gary_dev`'s real MySQL, in order, with `migrate:status` confirming `Ran` for each (§4)
- [ ] Migration rollback tested for real on `gary_dev` with no orphaned data or broken FKs (§4)
- [ ] The `order_payments`/`order_products`/`users` schema gaps discovered in local sandbox testing are confirmed resolved (columns exist) or a fixing migration has been added and validated (§4)
- [ ] `ModuleSeeder` validated idempotent against real, pre-existing `gary_dev` data — run twice, zero duplicates, zero disturbance to pre-existing modules (§5)
- [ ] All 10 new permissions confirmed present and correctly scoped; Master Admin confirmed to hold them automatically; at least one additional real test role assigned and exercised for both positive and negative paths (§6)
- [ ] Every item in the Browser/UI Validation Checklist passes in a real browser against `gary_dev` (§7)
- [ ] Every item in the Financial Validation Checklist passes, with at least one hand-verified arithmetic spot-check (§8)
- [ ] Every item in the Customer Credit Validation Checklist passes, including the "zero interaction with `LedgerBalanceService`" architectural boundary check (§9)
- [ ] Every item in the Resolution Center Validation Checklist passes, for both scenarios and the Operations Center (§10)
- [ ] No unresolved error-log entries referencing any of the new services/classes after a full validation pass
- [ ] The 133-commit sync between `raj_development` and `gary_dev` (§1) has been surfaced to and acknowledged by whoever owns `gary_dev`, so merging isn't a surprise
- [ ] A rollback rehearsal (§11) has actually been performed at least once on `gary_dev`, not only planned
- [ ] Explicit sign-off recorded (who validated what, and when) for each of the 9 checklists above — not just "it looked fine"

**Only once every box above is checked** should this work be considered for merge into `raj_development` and, from there, production — per this mission's explicit instruction, that decision and that merge are both out of scope for this document.
