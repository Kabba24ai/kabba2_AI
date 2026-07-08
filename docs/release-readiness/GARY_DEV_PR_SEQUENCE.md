# gary_dev PR Sequence — Exact Commit/Branch Plan

Last updated: 2026-07-06
Status: **PLANNING ONLY — do not commit, push, merge, or deploy until this sequence is approved.** Nothing below has been executed.

This document is the concrete execution plan for `docs/release-readiness/GARY_DEV_DEPLOYMENT_PLAN.md`'s §2/§3 (PR grouping and merge order). It assumes that plan's context (gary_dev = Gary's own staging branch, absorbing current `raj_development` history is acceptable) and adds the exact branch names, exact file lists, and — critically — exact instructions for the handful of files whose real edit history is *shared* across two PRs and therefore cannot be staged as a whole file.

---

## 0. Pre-Sequence Fact-Check

- `origin/gary_dev` (HEAD `e0113473`) has **zero commits that aren't already in `raj_development`** — confirmed via `git log --oneline raj_development..origin/gary_dev` returning empty, and `git merge-base --is-ancestor origin/gary_dev raj_development` succeeding. Syncing `gary_dev` to `raj_development` is therefore a **pure fast-forward**, not a merge — there is nothing unique on `gary_dev` today that could be lost.
- `raj_development` HEAD is `ec31ac5a` (PR #546 merged). **Every branch below is cut from this commit.**
- All local work described here is currently **uncommitted** on `raj_development` — confirmed via `git status`.

**Step 0, before any PR branch is cut**: fast-forward `gary_dev` to `raj_development`'s current HEAD (`git push origin raj_development:gary_dev` or the equivalent through whatever remote access Gary uses for his own branch). This absorbs the 133-commit gap noted in the deployment plan. Do this once, before PR 1.

---

## 1. Recommended Branch Names

All branched from `raj_development` @ `ec31ac5a` (i.e., from `gary_dev` immediately after Step 0's fast-forward), each targeting a PR back into `gary_dev`:

| # | Branch name | PR title |
|---|---|---|
| 1 | `gary-platform-stabilization-sprint-1` | Platform Stabilization Sprint 1 — ModuleSeeder reliability fix |
| 2 | `gary-financial-engine-modernization` | Financial Engine Modernization (Phases 2.1–2.8A) |
| 3 | `gary-customer-credit-service` | Customer Credit Service Foundation |
| 4 | `gary-customer-credit-admin` | Customer Credit Administration (CRM tab) |
| 5 | `gary-order-entry-credit-integration` | Order Entry Credit Integration |
| 6 | `gary-resolution-center` | Customer Resolution Center (Phases 3.3–3.5) |
| 7 | `gary-resolution-operations-center` | Resolution Operations Center (Phase 3.6) |

Naming follows this repo's existing convention for feature branches (e.g. `gary-billing-last-payment-fix`), scoped under Gary's own name since `gary_dev` is his branch.

**Do not branch PR 2 from PR 1's branch, or PR 4/5 from PR 3's branch, etc.** — branch every one of the 7 from the same starting point (`raj_development`/post-sync `gary_dev`), and let dependency order be enforced by **merge order into `gary_dev`**, not by branch stacking. This keeps each PR's diff clean and independently reviewable, and avoids one PR's branch carrying another's commits before it's actually merged.

---

## 2. Exact PR / Commit Grouping

Same 7-PR grouping as the deployment plan, in strict dependency order. **PR 1 → PR 2 has no dependency between them and may merge in either order or in parallel; every PR from 3 onward has a hard dependency on the ones listed.**

```
PR 1  Platform Stabilization Sprint 1        (no dependency)
PR 2  Financial Engine Modernization         (no dependency)
PR 3  Customer Credit Service Foundation     (needs PR 1 merged)
PR 4  Customer Credit Administration         (needs PR 3 merged)
PR 5  Order Entry Credit Integration         (needs PR 3 merged)
PR 6  Customer Resolution Center             (needs PR 1, PR 3 merged)
PR 7  Resolution Operations Center           (needs PR 6 merged)
```

Recommend **one commit per PR** (squash-merge or a single well-described commit), since none of this has any real prior commit history to preserve — the phase completion reports already documented under `docs/` serve as the detailed rationale, so the commit message only needs to reference them, not restate them.

---

## 3. Files Included in Each PR

### PR 1 — Platform Stabilization Sprint 1

| File | Status |
|---|---|
| `app/Models/Iam/AccessControl/Module.php` | Modified (whole file) |
| `app/Models/Iam/AccessControl/Permission.php` | Modified (whole file) |
| `database/seeders/Iam/ModuleSeeder.php` | **Modified — PARTIAL, see §3.8** |
| `docs/platform/PLATFORM_SPRINT_1_MODULESEEDER.md` | New |

### PR 2 — Financial Engine Modernization

| File | Status |
|---|---|
| `app/Helpers/CustomHelper.php` | Modified (whole file) |
| `app/Models/Customers/Customer.php` | Modified (whole file) |
| `app/Services/ChargeService.php` | Modified (whole file) |
| `app/Services/Reports/PaymentReconciliationLedger.php` | Modified (whole file) |
| `app/Services/Reports/SalesReportEngineV2.php` | Modified (whole file) |
| `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/PaymentStoreController.php` | Modified (whole file) |
| `app/Http/Controllers/Admin/Crm/Customers/Invoice/PaymentStoreController.php` | Modified (whole file) |
| `app/Http/Controllers/Admin/Dashboard/IndexController.php` | Modified (whole file) |
| `app/Http/Controllers/Admin/Dashboard/PaymentStoreController.php` | Modified (whole file) |
| `app/Http/Controllers/Admin/OrderManagement/Orders/AddToAccountPaymentController.php` | Modified (whole file) |
| `app/Http/Controllers/Front/Checkout/PostController.php` | Modified (whole file) |
| `app/Http/Controllers/Front/Customer/Dashboard/Invoice/PaymentStoreController.php` | Modified (whole file) |
| `resources/views/admin/crm/customers/partials/_tab_credit.blade.php` | Modified (whole file) |
| `resources/views/admin/crm/customers/transaction_accounts_pdf.blade.php` | Modified (whole file) |
| `resources/views/admin/order_management/orders/partials/_additional_charges.blade.php` | Modified (whole file) |
| `resources/views/front/customer/dashboard/partials/_tab_credit.blade.php` | Modified (whole file) |
| `app/Services/TaxCalculationService.php`, `InvoiceCalculationService.php`, `LedgerBalanceService.php` | New |
| `app/Http/DataObjects/TaxBreakdown.php` | New |
| `tests/Unit/Services/TaxCalculationServiceTest.php`, `LedgerBalanceServiceTest.php` | New |
| `docs/CLAUDE_HANDOFF.md`, `docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md` | New |
| `docs/financial-engine-consolidation/PHASE_2_*.md` (all 24 Phase 2.x docs — architecture report, implementation plan, sign-off readiness, every `PHASE_2_*_PRE_IMPLEMENTATION_CHECKLIST.md`/`PHASE_2_*_COMPLETION_REPORT.md`, `FINANCIAL_DECISIONS.md`, `FINANCIAL_TRUTH_TABLE.md`, `PHASE_2_5A_LEDGER_READINESS_REVIEW.md`, `PHASE_2_6A_PARALLEL_VALIDATION_REPORT.md`, `PHASE_2_7A_ROUNDING_INVESTIGATION.md`, `PHASE_2_8A_PRE_MIGRATION_AUDIT.md`) | New |

No migrations in this PR — every Phase 2.x change operates on existing tables/columns only.

### PR 3 — Customer Credit Service Foundation

| File | Status |
|---|---|
| `database/migrations/customers/2026_07_03_141905_create_customer_credits_table.php` | New |
| `database/migrations/customers/2026_07_03_180828_add_effective_date_and_internal_comments_to_customer_credits_table.php` | New |
| `database/migrations/customers/2026_07_04_090000_add_order_id_to_customer_credits_table.php` | New |
| `app/Services/CustomerCreditService.php` | New |
| `app/Models/Customers/CustomerCredit.php` | New |
| `database/seeders/Iam/ModuleSeeder.php` | **Modified — PARTIAL, see §3.8** |
| `tests/Unit/Services/CustomerCreditServiceTest.php` | New |
| `docs/financial-engine-consolidation/CUSTOMER_CREDIT_ARCHITECTURE.md`, `CUSTOMER_RESOLUTION_ARCHITECTURE.md`, `PROMOTIONAL_CREDIT_ARCHITECTURE.md`, `GIFTCARD_ARCHITECTURE.md`, `PHASE_3_0_PRE_IMPLEMENTATION_CHECKLIST.md`, `PHASE_3_0_COMPLETION_REPORT.md` | New |

### PR 4 — Customer Credit Administration

| File | Status |
|---|---|
| `app/Http/Controllers/Admin/Crm/Customers/CustomerCredit/GrantController.php`, `RedeemController.php` | New |
| `app/Http/Requests/Admin/Crm/Customers/CustomerCredit/GrantStoreRequest.php`, `RedeemStoreRequest.php` | New |
| `app/Http/Controllers/Admin/Crm/Customers/ViewController.php` | Modified (whole file) |
| `resources/views/admin/crm/customers/view.blade.php` | Modified (whole file) |
| `resources/views/admin/crm/customers/partials/_tab_store_credit.blade.php` | New |
| `routes/admin/crm/customers/customer_credit/routes.php` | New |
| `routes/admin/crm/customers/routes.php` | Modified (whole file — one-line `require` addition, confirmed clean/single-purpose diff) |
| `docs/customer-credit/PHASE_3_1_ADMIN_AUDIT.md`, `PHASE_3_1_COMPLETION_REPORT.md` | New |

### PR 5 — Order Entry Credit Integration

| File | Status |
|---|---|
| `app/Http/Controllers/Admin/OrderManagement/Orders/CustomerCredit/ApplyController.php`, `RemoveController.php` | New |
| `app/Http/Requests/Admin/OrderManagement/Orders/CustomerCredit/ApplyStoreRequest.php`, `RemoveStoreRequest.php` | New |
| `resources/views/components/admin/order-management/orders/customer-credit-panel.blade.php` | New |
| `app/Http/Controllers/Admin/OrderManagement/Orders/EditController.php` | Modified — **verify at staging time** (see §3.8; believed clean/PR5-only, but confirm via `git diff` before assuming, since this file is also touched by Resolution Center work) |
| `resources/views/admin/order_management/orders/edit.blade.php` | Modified — **PARTIAL, see §3.8** |
| `routes/admin/order_management/orders/routes.php` | Modified (whole file — confirmed clean, adds only the `customer-credit.*` routes, no Resolution Center content in this file) |
| `docs/customer-credit/PHASE_3_2_ORDER_ENTRY_AUDIT.md`, `PHASE_3_2_COMPLETION_REPORT.md` | New |

### PR 6 — Customer Resolution Center (Phases 3.3–3.5)

| File | Status |
|---|---|
| `database/migrations/customers/2026_07_04_120000_create_resolution_cases_table.php`, `2026_07_04_150000_add_scenario_key_to_resolution_cases_table.php`, `2026_07_04_180000_add_issue_category_to_resolution_cases_table.php` | New |
| `app/Services/ResolutionCenterService.php` | New — **PARTIAL content, see §3.8** (author only the Phase 3.3–3.5 methods; Phase 3.6 methods belong to PR 7) |
| `app/Models/Customers/ResolutionCase.php` | New — **PARTIAL content, see §3.8** (no `priority`/`status`/`waiting_on`/`assigned_to_user_id`/`store_id`/`completed_at` fields or their relations yet) |
| `app/Services/ResolutionCenter/{ResolutionScenario,ResolutionQuestion,ScenarioAction,ResolutionRecommendation,ResolutionPolicy,ResolutionDecisionEngine,CancellationRefundScenario,ManualResolutionScenario,ResolutionScenarioRegistry}.php` | New (all 9, clean — untouched by Phase 3.6) |
| `app/Http/Controllers/Admin/ResolutionCenter/{Answer,Decision,Index,IssueCredit,ManualResolutionAnswer,Store}Controller.php` | New (6 files, clean) |
| `app/Http/Controllers/Admin/ResolutionCenter/ShowController.php` | New — **PARTIAL content, see §3.8** (eager-load `customer, order, responsiblePerson, managerOverrideUser, creditIssued` only) |
| `app/Http/Requests/Admin/ResolutionCenter/{Answer,Decision,IssueCredit,ManualResolutionAnswer,StoreCase}Request.php` | New (5 files, clean) |
| `database/seeders/Iam/ModuleSeeder.php` | **Modified — PARTIAL, see §3.8** |
| `routes/admin/resolution_center/routes.php` | New — **PARTIAL content, see §3.8** (`index`, `start`, `show`, `answer`, `manual-answer`, `decision`, `issue-credit` only) |
| `routes/admin/routes.php` | Modified (whole file — confirmed clean, adds only the `require resolution_center/routes.php` line) |
| `resources/views/admin/resolution_center/index.blade.php` | New — **PARTIAL content, see §3.8** (no Operations Center link yet) |
| `resources/views/admin/resolution_center/show.blade.php` | New — **PARTIAL content, see §3.8** (through the Phase 3.5 Manual Resolution branch; no Status/Priority/Assigned/Store row or Audit Trail section yet) |
| `resources/views/admin/order_management/orders/edit.blade.php` | Modified — **PARTIAL, see §3.8** (the "Start Resolution Case" panel block, including its Phase 3.5 scenario picker) |
| `tests/Unit/Services/ResolutionCenter/{CancellationRefundScenarioTest,ManualResolutionScenarioTest,ResolutionDecisionEngineTest,ResolutionScenarioRegistryTest}.php` | New (4 files, clean) |
| `docs/customer-credit/{PHASE_3_3_RESOLUTION_CENTER,PHASE_3_3_COMPLETION_REPORT,PHASE_3_4_COMPLETION_REPORT,OPERATIONAL_KNOWLEDGE_FRAMEWORK,PHASE_3_5_MANUAL_RESOLUTION,PHASE_3_5_COMPLETION_REPORT}.md` | New |

### PR 7 — Resolution Operations Center (Phase 3.6)

| File | Status |
|---|---|
| `database/migrations/customers/2026_07_06_090000_add_operations_fields_to_resolution_cases_table.php`, `2026_07_06_090100_create_resolution_case_activity_logs_table.php` | New |
| `app/Models/Customers/ResolutionCaseActivityLog.php` | New (clean) |
| `app/Services/ResolutionCenterService.php` | **Additive diff on top of PR 6's version, see §3.8** (`STATUS_*`/`WAITING_ON_*`/`PRIORITY_*` constants, `isValidStatus`/`isValidPriority`/`isValidWaitingOn`, `logActivity`/`resolveStoreId`, `assignCase`/`setPriority`/`markWaiting`/`escalate`/`closeCase`/`reopenCase`/`dashboardMetrics`, plus the `status`/`completed_at` mirroring added to `startCase`/`recordAnswers`/`recordDecision`/`approveAndIssueCredit`/`markOutcome`) |
| `app/Models/Customers/ResolutionCase.php` | **Additive diff on top of PR 6's version** (new fillable fields, `completed_at` cast, `assignedTo()`/`store()`/`activityLogs()` relations) |
| `app/Http/Controllers/Admin/ResolutionCenter/Operations{Index,Assign,Priority,MarkWaiting,Escalate,Close,Reopen}Controller.php` | New (7 files, clean) |
| `app/Http/Requests/Admin/ResolutionCenter/Operations{Assign,Priority,MarkWaiting,Escalate}Request.php` | New (4 files, clean) |
| `app/Http/Controllers/Admin/ResolutionCenter/ShowController.php` | **Additive diff on top of PR 6's version** (adds `assignedTo`, `store`, `activityLogs.user` to the eager-load list) |
| `routes/admin/resolution_center/routes.php` | **Additive diff on top of PR 6's version** (`operations`, `assign`, `priority`, `wait`, `escalate`, `close`, `reopen` routes, `operations` registered before the `{unique_id}` wildcard) |
| `resources/views/admin/resolution_center/operations/index.blade.php`, `operations/partials/_table.blade.php` | New (clean) |
| `resources/views/admin/resolution_center/index.blade.php` | **Additive diff on top of PR 6's version** (adds the "Operations Center" link) |
| `resources/views/admin/resolution_center/show.blade.php` | **Additive diff on top of PR 6's version** (adds the Status/Priority/Assigned/Store summary row and the Audit Trail section) |
| `tests/Unit/Services/ResolutionCenterOperationsTest.php` | New (clean) |
| `docs/customer-credit/PHASE_3_6_OPERATIONS_AUDIT.md`, `PHASE_3_6_OPERATIONS_CENTER.md`, `PHASE_3_6_COMPLETION_REPORT.md` | New |

### Deliberately deferred to the very end (not part of any of the 7 feature PRs)

- `docs/financial-engine-consolidation/FINANCIAL_ENGINE_MASTER_ROADMAP.md`, `FINANCIAL_ENGINE_TODO.md`, `CUSTOMER_CREDIT_TODO.md` — these three are living trackers whose current content already summarizes all 7 PRs' outcomes (they were updated incrementally after every phase in the original work session, but only their final, cumulative state exists now). **Recommend committing these three as their own small, dependency-free 8th commit after PR 7 merges**, rather than trying to reconstruct 7 incremental versions of documents with zero runtime effect — call this out explicitly in the PR description so it isn't mistaken for an oversight.
- `docs/release-readiness/*.md` (this file and its two predecessors) — meta-planning documents about the deployment itself, not about the product. Commit whenever convenient, no dependency on anything.
- **Excluded entirely, do not commit at all**: the ~140 unrelated `storage/app/public/**`/`.gitignore` file changes, and the stray empty `artisan-output.txt` (delete it).

### 3.8 — Files Requiring Interactive Patch Splitting (not whole-file staging)

These files accumulated changes from more than one PR's worth of work during the original session and must be split with `git add -p` / `git commit --patch` (or by hand-authoring an intermediate version of a new file) rather than staged as a complete file in one commit:

| File | Split between | How to split |
|---|---|---|
| `database/seeders/Iam/ModuleSeeder.php` | PR 1 / PR 3 / PR 6 | Three ranges: (a) PR 1 — the mechanism fix (import reordering, and supplying `'permission_to_all' => 'No'` as `Permission::firstOrCreate()`'s second argument); (b) PR 3 — the `customer_credit` module/permission block; (c) PR 6 — the `resolution_center` module/permission block. Stage and commit in that order; each is a self-contained, contiguous array block in the seeder's `$moduleList`, plus PR 1's one-time mechanism-level hunk. |
| `resources/views/admin/order_management/orders/edit.blade.php` | PR 5 / PR 6 | PR 5 owns the `<x-admin.order-management.orders.customer-credit-panel>` include block; PR 6 owns the "Resolution Center — Start Resolution Case" panel block (including its scenario-picker `<select>`, a Phase 3.5 addition bundled into the same PR 6 block since both are within PR 6's scope). Two clearly separate `<div>` blocks — verify exact line ranges via `git diff` at commit time. |
| `app/Http/Controllers/Admin/OrderManagement/Orders/EditController.php` | Likely PR 5 only | Believed to contain only the Phase 3.2 additions (`$customerCreditSummary`, `$orderAppliedCredit`, `$employees` passed to the view) — Resolution Center's "Start Resolution Case" panel needs no new controller-computed data (`$order` is already in scope). **Confirm this via `git diff` before staging** — if it turns out to carry any Resolution-Center-only lines, split the same way as the row above. |
| `app/Services/ResolutionCenterService.php` | PR 6 / PR 7 | This is a **new, uncommitted file** — there is no baseline diff to split with `git add -p`. Instead: for PR 6's commit, hand-author the file *without* the Phase 3.6 additions listed in PR 7's row above (constants, validators, `logActivity`/`resolveStoreId`, the 7 new public methods, and the `status`/`completed_at` mirroring in the 5 pre-existing methods); commit that version. For PR 7, apply the current (full) version as a diff on top. |
| `app/Models/Customers/ResolutionCase.php` | PR 6 / PR 7 | Same technique as above — author a PR-6-only version first (no `priority`/`status`/`waiting_on`/`assigned_to_user_id`/`store_id`/`completed_at` fillable entries, no `completed_at` cast, no `assignedTo()`/`store()`/`activityLogs()` methods), commit, then apply PR 7's additions as a follow-up diff. |
| `app/Http/Controllers/Admin/ResolutionCenter/ShowController.php` | PR 6 / PR 7 | Same technique — PR 6's version eager-loads `customer, order, responsiblePerson, managerOverrideUser, creditIssued`; PR 7 adds `assignedTo, store, activityLogs.user` to that list. |
| `routes/admin/resolution_center/routes.php` | PR 6 / PR 7 | Same technique — PR 6's version has `index`/`start`/`show`/`answer`/`manual-answer`/`decision`/`issue-credit` only; PR 7 adds `operations` (placed before the `{unique_id}` wildcard) and the 6 manager-action routes. |
| `resources/views/admin/resolution_center/index.blade.php` | PR 6 / PR 7 | Same technique — PR 6's version has no "Operations Center" link; PR 7 adds one line. |
| `resources/views/admin/resolution_center/show.blade.php` | PR 6 / PR 7 | Same technique — PR 6's version ends after the Employee Decision section; PR 7 appends the Status/Priority/Assigned/Store summary row (near the top) and the Audit Trail section (at the bottom) — two separate insertions, not one contiguous block. |

**Why bother with this instead of just bundling PR 6 and PR 7 together**: keeping them separate preserves the actual dependency/rollback boundary described in the deployment plan (PR 7 depends on PR 6, not vice versa) and means a problem found only in the Operations Center (PR 7) can be rolled back without touching the already-proven base Resolution Center (PR 6). If this precision isn't worth the extra care at commit time, the fallback is to **merge PR 6 and PR 7 into one combined PR** — mechanically simpler, but loses that independent rollback granularity. This is a judgment call to make at execution time, not before.

---

## 4. Migration Order

Identical dependency chain to the deployment plan's §4, now mapped to the PR that carries each file:

| Order | Migration | Carried by |
|---|---|---|
| 1 | `2026_07_03_141905_create_customer_credits_table` | PR 3 |
| 2 | `2026_07_03_180828_add_effective_date_and_internal_comments_to_customer_credits_table` | PR 3 |
| 3 | `2026_07_04_090000_add_order_id_to_customer_credits_table` | PR 3 |
| 4 | `2026_07_04_120000_create_resolution_cases_table` | PR 6 |
| 5 | `2026_07_04_150000_add_scenario_key_to_resolution_cases_table` | PR 6 |
| 6 | `2026_07_04_180000_add_issue_category_to_resolution_cases_table` | PR 6 |
| 7 | `2026_07_06_090000_add_operations_fields_to_resolution_cases_table` | PR 7 |
| 8 | `2026_07_06_090100_create_resolution_case_activity_logs_table` | PR 7 |

Laravel's filename-based ordering makes this self-enforcing *within* a single `php artisan migrate` run — but because migrations 1–3 (PR 3) must exist in the database before migration 4 (PR 6) can succeed (its `credit_issued_id` FK references `customer_credits`), **run `php artisan migrate` immediately after each PR merges**, not once at the very end after all 7 PRs land. Waiting until the end works too (Laravel will still apply all 8 in correct order in one run), but per-PR migration is how §6 validates each PR independently before moving to the next.

No migrations exist for PR 1, PR 2, PR 4, or PR 5 — nothing to run after those merge.

---

## 5. Seeder Order

Only `ModuleSeeder` (`database/seeders/Iam/ModuleSeeder.php`) is affected, and it must run **three separate times**, once after each of the three PRs that add to it:

1. **After PR 1 merges**: run `ModuleSeeder`. At this point it contains only the mechanism fix, no new modules — this run's purpose is purely to prove the fix works against `gary_dev`'s real MySQL before anything depends on it (§6.1 below). Confirm no error, and confirm it's safe to run twice in a row (idempotency check).
2. **After PR 3 merges**: run `ModuleSeeder` again. Confirm the `customer_credit` module (6 permissions) now exists and Master Admin has all 6.
3. **After PR 6 merges**: run `ModuleSeeder` again. Confirm the `resolution_center` module (4 permissions) now exists and Master Admin has all 4.

**Do not run it after PR 2, PR 4, PR 5, or PR 7** — none of those add or change anything the seeder reads.

---

## 6. Validation Steps After Each PR Lands in gary_dev

Brief, PR-specific checks — the full checklists (Browser/UI, Financial, Customer Credit, Resolution Center) live in the deployment plan and should be run in full once all 7 PRs are merged; the steps below are the minimum to confirm each PR individually didn't break anything before proceeding to the next.

- **After PR 1**: run `ModuleSeeder` twice (§5.1); confirm zero errors and zero duplicate rows. Confirm no existing module/permission/role data changed.
- **After PR 2**: run `php artisan test --filter=TaxCalculationServiceTest` and `--filter=LedgerBalanceServiceTest`; record one real Payment and one real Order through the migrated call sites and hand-verify the arithmetic (deployment plan §8, abbreviated).
- **After PR 3**: run `php artisan migrate` (3 new migrations), confirm `migrate:status` shows all 3 as `Ran`; run `ModuleSeeder` (§5.2); run `php artisan test --filter=CustomerCreditServiceTest`; perform one real grant and one real redemption via `tinker` or a temporary test route, confirming `remainingBalance()` updates correctly.
- **After PR 4**: browser check — CRM Customer Credit tab renders, Grant/Redeem dialogs work end-to-end (deployment plan §7, the Customer Credit half).
- **After PR 5**: browser check — Order Edit Customer Credit panel Apply/Remove works end-to-end.
- **After PR 6**: run `php artisan migrate` (3 new migrations), confirm `migrate:status`; run `ModuleSeeder` (§5.3); run the full `tests/Unit/Services/ResolutionCenter/*` suite; browser check — start and complete a case under each scenario (Cancellation/Refund and Manual Resolution) through the case detail page, confirm the resulting Store Credit issuance if applicable.
- **After PR 7**: run `php artisan migrate` (2 new migrations), confirm `migrate:status`; run `ResolutionCenterOperationsTest`; browser check — the Operations Center loads, stat cards show correct counts, every filter works, Assign/Escalate/Close/Reopen all work from the queue.
- **After all 7 (+ the deferred tracker-doc commit)**: run the full deployment plan checklists (§7–§10) and the Go/No-Go checklist below.

---

## 7. Rollback Plan Per PR

Reverse-order rollback, mirroring the forward dependency chain — never roll back a PR while something later that depends on it is still merged.

| PR | Schema rollback | Code rollback |
|---|---|---|
| 7 | `php artisan migrate:rollback --step=2` (the 2 Operations Center migrations) | Revert PR 7's merge commit (or, since PR 6's files carry additive diffs from PR 7 per §3.8, revert just those diffs) |
| 6 | `php artisan migrate:rollback --step=3` (the 3 base `resolution_cases`/`customer_credits`-adjacent migrations) — **only after PR 7 is already rolled back**, since PR 7's FKs depend on `resolution_cases` existing | Revert PR 6's merge commit |
| 5 | No migrations | Revert PR 5's merge commit |
| 4 | No migrations | Revert PR 4's merge commit |
| 3 | `php artisan migrate:rollback --step=3` (the 3 `customer_credits` migrations) — **only after PR 6 is already rolled back**, since `resolution_cases.credit_issued_id` FKs to `customer_credits` | Revert PR 3's merge commit |
| 2 | No migrations | Revert PR 2's merge commit |
| 1 | No migrations | Revert PR 1's merge commit — but confirm no later `ModuleSeeder` run has already created `customer_credit`/`resolution_center` rows that depend on PR 1's model defaults; if so, roll those back first (§ next row) |
| — | `ModuleSeeder`-created rows (no migration, no automatic rollback) | Manually delete the `customer_credit`/`resolution_center` rows from `modules`/`permissions` and any `role_has_permissions` referencing them, per the deployment plan's §11 |

**Full-environment reset remains the simpler alternative** on `gary_dev` specifically (per the deployment plan §11) — since it's Gary's own staging branch, a full restore-from-snapshot is often less error-prone than threading a multi-PR rollback in exact reverse order, especially once PR 6/PR 7's split-file additive diffs are involved.

---

## 8. Final Go/No-Go Checklist Before Considering raj_development/Production

This is the same gate as the deployment plan's §12, restated here as the literal exit criteria for this specific PR sequence:

- [ ] All 7 PRs merged into `gary_dev` in the order specified in §2, with every split-file instruction in §3.8 followed correctly (confirm via a final `git diff gary_dev` review, not just trust that the plan was followed)
- [ ] All 8 migrations show `Ran` via `php artisan migrate:status` on `gary_dev`'s real MySQL
- [ ] `ModuleSeeder` has been run 3 times total (§5), confirmed idempotent each time, confirmed against real pre-existing `gary_dev` data (not a fresh/empty database)
- [ ] The deferred tracker-doc commit (§3, "deliberately deferred") has landed
- [ ] Every per-PR validation step in §6 passed at the time that PR merged (not retroactively assumed)
- [ ] The full Browser/UI, Financial, Customer Credit, and Resolution Center checklists from `GARY_DEV_DEPLOYMENT_PLAN.md` all pass
- [ ] A rollback rehearsal (§7) has been performed for at least one PR on `gary_dev` for real
- [ ] No unresolved error-log entries referencing any new class/service after a full end-to-end pass
- [ ] Explicit sign-off recorded against this checklist, by name and date

**Only when every box above is checked** should merging this work into `raj_development` (and, later, production) even be discussed — that decision remains explicitly out of scope for this document, per the mission.
