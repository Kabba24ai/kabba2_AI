# Release Readiness Audit — Financial Engine + Customer Credit Platform + Resolution Center

Last updated: 2026-07-06
Branch: `raj_development`
Status: **AUDIT ONLY — nothing has been committed, pushed, or deployed.** No application code was changed to produce this document.

---

## 0. How to Read This Document

Everything described below is **local, uncommitted work** on `raj_development`. The only thing from this entire body of work already live in production is Phase 1 (the Billing Summary display-bug fix, PR #546, already merged before any of this started). Section numbers below match the 12 items requested in the mission.

---

## 1. Full Git Status

### 1.1 Tracked, modified files (`M`) — real code changes

**Financial Engine (core consolidation track):**
- `app/Helpers/CustomHelper.php` (120 lines removed, net simplification — now delegates to the new services below)
- `app/Models/Customers/Customer.php`
- `app/Services/ChargeService.php`
- `app/Services/Reports/PaymentReconciliationLedger.php`
- `app/Services/Reports/SalesReportEngineV2.php`
- `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/PaymentStoreController.php`
- `app/Http/Controllers/Admin/Crm/Customers/Invoice/PaymentStoreController.php`
- `app/Http/Controllers/Admin/Dashboard/IndexController.php`
- `app/Http/Controllers/Admin/Dashboard/PaymentStoreController.php`
- `app/Http/Controllers/Admin/OrderManagement/Orders/AddToAccountPaymentController.php`
- `app/Http/Controllers/Front/Checkout/PostController.php`
- `app/Http/Controllers/Front/Customer/Dashboard/Invoice/PaymentStoreController.php`
- `resources/views/admin/crm/customers/partials/_tab_credit.blade.php`
- `resources/views/admin/crm/customers/transaction_accounts_pdf.blade.php`
- `resources/views/admin/order_management/orders/partials/_additional_charges.blade.php`
- `resources/views/front/customer/dashboard/partials/_tab_credit.blade.php`

**Customer Credit / Resolution Center (touches existing files):**
- `app/Http/Controllers/Admin/Crm/Customers/ViewController.php` (adds the 6th CRM tab)
- `app/Http/Controllers/Admin/OrderManagement/Orders/EditController.php` (Customer Credit panel + Resolution Center panel + scenario picker)
- `resources/views/admin/crm/customers/view.blade.php`
- `resources/views/admin/order_management/orders/edit.blade.php`
- `routes/admin/routes.php`, `routes/admin/crm/customers/routes.php`, `routes/admin/order_management/orders/routes.php`

**Platform Stabilization Sprint 1:**
- `app/Models/Iam/AccessControl/Module.php` — adds `protected $attributes = ['need_set_permissions' => 'No']`
- `app/Models/Iam/AccessControl/Permission.php` — adds `protected $attributes = ['permission_to_all' => 'No']`
- `database/seeders/Iam/ModuleSeeder.php` — adds the `customer_credit` and `resolution_center` module/permission blocks, plus supplies `permission_to_all` explicitly to Spatie's own `Permission::firstOrCreate()` call (the model-level default above does **not** reach this call — see §5).

**Noise, not part of this release (pre-existing, unrelated — confirmed by every phase's own audit since the original CRM Billing Audit):**
- `bootstrap/cache/.gitignore`, `storage/app/**/.gitignore`, and ~140 files under `storage/app/public/**` and `storage/app/private/**` (images/videos/PDFs). These are pre-existing local working-tree changes (permissions/mtimes on binary assets and `.gitignore` files), **not touched by any phase of this work**, and were explicitly flagged as out-of-scope in the very first `CLAUDE_HANDOFF.md`. **Recommend excluding these from any commit** for this release — stage only the files listed above and below.

### 1.2 Untracked files (`??`) — all new

**Migrations** (8 files, `database/migrations/customers/`) — see §3.

**Seeders** — no new seeder files; one existing seeder modified (`ModuleSeeder.php`, listed above under tracked/modified).

**Documentation** (this is the majority of the untracked file count — ~65 files) — the complete audit trail under `docs/CLAUDE_HANDOFF.md`, `docs/crm-billing-audit/`, `docs/financial-engine-consolidation/` (28 files), `docs/customer-credit/` (13 files), `docs/platform/` (1 file). None of these affect runtime behavior.

**Tests** (8 files, `tests/Unit/Services/**`) — see §8.

**Service classes** (new):
- `app/Services/TaxCalculationService.php`, `app/Services/InvoiceCalculationService.php`, `app/Services/LedgerBalanceService.php` (Financial Engine)
- `app/Services/CustomerCreditService.php`, `app/Services/ResolutionCenterService.php`
- `app/Services/ResolutionCenter/{ResolutionScenario,ResolutionQuestion,ScenarioAction,ResolutionRecommendation,ResolutionPolicy,ResolutionDecisionEngine,CancellationRefundScenario,ManualResolutionScenario,ResolutionScenarioRegistry}.php` (9 files)
- `app/Http/DataObjects/TaxBreakdown.php`

**Models (new):**
- `app/Models/Customers/CustomerCredit.php`, `app/Models/Customers/ResolutionCase.php`, `app/Models/Customers/ResolutionCaseActivityLog.php`

**Controllers/Requests/Routes/Views (new)** — Customer Credit Admin (CRM tab: `Grant`/`RedeemController` + requests), Order Entry Integration (`Apply`/`RemoveController` + requests, the credit panel component, its routes file), Resolution Center (12 controllers, 10 requests, 1 routes file, 4 view files including the Operations Center's 2 new files).

**Stray file — flag for cleanup, not for commit:**
- `artisan-output.txt` (repo root) — **empty (0 bytes)**, an accidental captured-output artifact from a console command. Not referenced by any code or doc. **Delete before committing** — do not stage it.

---

## 2. Logical PR / Commit Grouping

Recommended as **7 separate PRs**, in this order (dependency order, not just topical grouping — each PR after #1 depends on the ones before it):

### PR 1 — Platform Stabilization Sprint 1
`app/Models/Iam/AccessControl/{Module,Permission}.php`, the `ModuleSeeder.php` diff's *mechanism* fix only (the `permission_to_all` explicit value and the two model `$attributes` defaults) — **can technically be split from PR 3's new module blocks, but has no independent value without them**, so in practice ship together with PR 3 or immediately before it. Listed first because every other PR's permissions depend on this fix working.

### PR 2 — Financial Engine Modernization (Phases 2.1–2.8A)
`TaxCalculationService`, `InvoiceCalculationService`, `LedgerBalanceService`, `TaxBreakdown`, plus the `CustomHelper`/`SalesReportEngineV2`/`PaymentReconciliationLedger`/`Dashboard\IndexController`/`ChargeService` migrations onto them, plus the 7 one-line Payment/Order call-site swaps. Fully independent of everything else in this release — **could ship alone**, and per the earlier conversation in this session, Epic 1 work is otherwise paused (Phase 2.8B not started).

### PR 3 — Customer Credit Service Foundation
`CustomerCreditService`, `CustomerCredit` model, the 3 `customer_credits` migrations, `ModuleSeeder`'s `customer_credit` module block. Depends on PR 1 (seeder fix) to deploy its permissions correctly.

### PR 4 — Customer Credit Administration (CRM tab)
`Grant`/`RedeemController` + requests, the CRM `ViewController`/`view.blade.php`/`_tab_store_credit.blade.php` changes, the `customer_credit` routes file. Depends on PR 3.

### PR 5 — Order Entry Credit Integration
`Apply`/`RemoveController` + requests, the `customer-credit-panel` Blade component, the `EditController`/`edit.blade.php` changes for this panel, the order-routes diff for `customer-credit.*`. Depends on PR 3.

### PR 6 — Customer Resolution Center (Phases 3.3–3.5)
`ResolutionCenterService`, `ResolutionCase` model, all `ResolutionCenter/*` scenario classes, the 3 base `resolution_cases` migrations, `ModuleSeeder`'s `resolution_center` module block, the case-creation/answer/decision/issue-credit controllers+requests, `resolution_center/index.blade.php` and `show.blade.php`, the Order Edit "Start Resolution Case" panel, the resolution-center routes file (minus the Operations-Center-specific routes/controllers). Depends on PR 1 and PR 3 (calls `CustomerCreditService`).

### PR 7 — Resolution Operations Center (Phase 3.6)
The 2 operations-fields/activity-log migrations, the 7 `Operations*Controller`s + 4 requests, `ResolutionCaseActivityLog` model, the `ResolutionCenterService` operational methods, the 2 new Operations Center views + the `show.blade.php`/`index.blade.php` additions. Depends on PR 6.

**Documentation** (`docs/**`) can ride along with each corresponding PR (e.g. `PHASE_3_6_*.md` with PR 7) or land as one final documentation-only PR — either is safe since none of it is executable.

**Tests** ride along with the PR that introduces the class they test (e.g. `CustomerCreditServiceTest.php` with PR 3).

---

## 3. Database Migrations and Required Deployment Order

8 new migration files, all under `database/migrations/customers/`. **Laravel applies migrations in filename/timestamp order automatically** — because every file below is already correctly named for its real dependency, a single `php artisan migrate` run (with all 8 files present) will apply them in the right order with no manual intervention. The dependency chain, confirmed by reading every `foreign()` call in each file:

```
customers, users, orders, stores        (pre-existing tables — not part of this release)
        │
        ▼
2026_07_03_141905  create_customer_credits_table            (FK → customers, users)
        │
2026_07_03_180828  add_effective_date_and_internal_comments_to_customer_credits_table   (no FK)
        │
2026_07_04_090000  add_order_id_to_customer_credits_table    (FK → orders)
        │
        ▼
2026_07_04_120000  create_resolution_cases_table             (FK → customers, orders, users, customer_credits)
        │
2026_07_04_150000  add_scenario_key_to_resolution_cases_table          (no FK)
        │
2026_07_04_180000  add_issue_category_to_resolution_cases_table        (no FK)
        │
2026_07_06_090000  add_operations_fields_to_resolution_cases_table     (FK → users, stores)
        │
        ▼
2026_07_06_090100  create_resolution_case_activity_logs_table (FK → resolution_cases, users)
```

**All 8 are currently applied only in the local development SQLite database** (confirmed via `migrate:status`, batches 6–15) — **none have ever run against MySQL or any staging/production database.** This is the single most important pre-production validation item in this entire audit (see §8).

**No destructive migrations.** Every migration in this set is additive (`create` or `add column`/`add foreign key`) — nothing drops a column, changes a column's type, or deletes data. Rollback (`down()`) is defined and reversible for all 8 (verified by reading each file).

---

## 4. Seeders That Must Run in Production

Only one seeder is affected: **`database/seeders/Iam/ModuleSeeder.php`** (modified, not new). It must be re-run in production after the migrations above (it does not depend on them, but the new permissions it creates are meaningless — nothing checks them — until PRs 3/6 that gate routes on them are also deployed).

Running it adds exactly two new module/permission blocks:
- `customer_credit` module → 6 permissions (`view`, `grant`, `redeem`, `reverse`, `delete`, `view_audit_history`)
- `resolution_center` module → 4 permissions (`view`, `use`, `override`, `view_audit_history`)

It is **idempotent** (`firstOrCreate` throughout, confirmed by reading the seeder) — safe to run against a production database that already has other modules/permissions; it will not duplicate or disturb them. This was specifically verified end-to-end during Platform Stabilization Sprint 1 (`docs/platform/PLATFORM_SPRINT_1_MODULESEEDER.md`): running it twice produces no duplicates, and running it against the untouched, pre-existing `personnel` module reproduced (and then confirmed fixed) the two defects below.

**Do not run `ModuleSeeder` in production before deploying PR 1** (the `Module`/`Permission` model defaults). Without PR 1, running the seeder against a production database will fail with the same `NOT NULL` constraint violations Platform Sprint 1 found and fixed locally — this is not a local-environment quirk, it is a genuine defect in the base seeder logic that predates all of this work (reproduced against the existing, untouched `personnel` module) and will reproduce identically in production.

---

## 5. Permissions That Must Be Created and Assigned

| Module | Permission key | Used by |
|---|---|---|
| `customer_credit` | `view` | CRM Credit tab display |
| `customer_credit` | `grant` | Grant Credit dialog; also gates Resolution Center's "Approve & Issue Store Credit" and Order Edit's "Remove Applied Credit" |
| `customer_credit` | `redeem` | Redeem Credit dialog; also gates Order Edit's "Apply Credit" |
| `customer_credit` | `reverse` | Reserved — no current caller (confirmed via grep; not yet wired to any controller) |
| `customer_credit` | `delete` | Reserved — no current caller |
| `customer_credit` | `view_audit_history` | Credit history table visibility |
| `resolution_center` | `view` | Viewing a single case |
| `resolution_center` | `use` | Starting a case, answering questions, recording decisions, and every Operations Center manager action (Assign/Reassign/Mark Waiting/Escalate/Close/Reopen are all gated on `.override`, not `.use` — see next row) |
| `resolution_center` | `override` | Manager-only actions: recording a manager override reason, and all 6 Operations Center manager quick-actions |
| `resolution_center` | `view_audit_history` | The case-history list page and the new Operations Center dashboard (both gated on this same permission) |

**Assignment**: `ModuleSeeder::setPermissionToMasterAdmin()` automatically grants every permission it creates to the `Master Admin` role — confirmed by reading the seeder. **No other role receives these permissions automatically.** Whoever should actually use the Customer Credit and Resolution Center features in production (likely CSRs/managers, not just Master Admin) needs those roles' permissions assigned manually after the seeder runs — this is an explicit action item, not automatic, and is **not** covered by any code in this release.

**Two reserved permissions with no caller today**: `customer_credit.reverse` and `customer_credit.delete` exist in the seeder but nothing in the codebase currently checks them. Not a bug — flagged so whoever assigns roles doesn't wonder why granting them has no visible effect yet.

---

## 6. Production Risks

Ranked by severity:

1. **Nothing in this entire body of work has ever run against MySQL.** The local development database is SQLite; production is MySQL (confirmed repeatedly throughout the Financial Engine docs — e.g. Phase 2.7A's explicit "MySQL was unavailable to test"). SQLite enforces no fixed decimal scale and has looser NOT NULL/foreign-key enforcement in places MySQL is strict — every "validated live" claim in every phase's completion report was proven against SQLite only. **This is a blocking risk for every PR in this release, not just the Financial Engine ones.**
2. **The Phase 2.7A rounding question remains genuinely unresolved** — `CustomHelper::updateCreditBalance()`'s `'charge'`+`sales_tax_type='add'` branch has a real, pre-existing (not introduced by this work) sub-cent rounding discrepancy whose behavior under MySQL's `DECIMAL` columns has never been confirmed. This does **not** block PR 2 as scoped (Payment/Order migration only, which this discrepancy doesn't touch) but must not be forgotten before any future Charge-family migration.
3. **Schema drift between this sandbox's local database and any real environment is already known, not hypothetical**: this session's own live validations had to patch `order_payments.deleted_at`, `order_products.deleted_at`, and `users.deleted_at`/`employee_code` — columns missing locally — before `CustomerCreditService`/`ResolutionCenterService` code would even run. **It is not confirmed whether production already has these columns** (via already-deployed, MySQL-only migrations this SQLite sandbox never ran) **or whether they are genuinely missing everywhere**, which would mean this new code breaks on first use in production. This must be checked against the actual production/staging schema before deploying PRs 3, 5, 6, or 7.
4. **`customers.available_credit_balance` is reportedly `varchar` in this local database**, per Phase 2.7A — the migration making it `DECIMAL(15,2)` is MySQL-only and was never run here. If production has already applied that migration (likely, since it predates this batch of work), this is a non-issue; if not, several of the Financial Engine's numeric assumptions about that column should be re-checked.
5. **No automated Feature/browser test exists for any UI added in this release** (Customer Credit tab, Order Edit panels, Resolution Center, Operations Center) — every UI claim of correctness in the phase completion reports rests on Blade-compilation checks (real container, catches syntax errors only) plus manual reasoning about rendered output, not an actual browser click-through. Flagged as an explicit staging-validation requirement in §8.
6. **Role/permission assignment is a manual, easy-to-forget step** (§5) — deploying the code and running the seeder does not, by itself, make any of this usable by anyone except Master Admin.
7. **`ModuleSeeder` must run after PR 1's model fixes, not before or without them** — running it against production without PR 1 deployed first will fail outright (§4), not silently degrade.

---

## 7. Rollback Concerns

- **All 8 migrations have reversible `down()` methods** (verified) — a `php artisan migrate:rollback` targeting this batch is mechanically safe from a schema-definition standpoint.
- **Rolling back is not risk-free once real data exists**: once `customer_credits`, `resolution_cases`, or `resolution_case_activity_logs` rows are created in production (real credit grants, real resolution cases), rolling back their migrations **drops those tables and destroys that data** — there is no data-migration-out step. Rollback is only truly safe before any real usage; after that, a rollback decision must weigh data loss, not just schema reversibility.
- **`CustomHelper`'s Financial Engine changes (PR 2) reduce a 120-line method** to delegate to new services — rolling back PR 2 alone (without also rolling back PR 3+) is safe in isolation (nothing later depends on `CustomHelper`'s internals changing), but rolling back PR 2 while PRs 3–7 remain deployed is untested; recommend rolling back in exact reverse deployment order if a rollback is ever needed.
- **`ModuleSeeder` rollback has no clean path**: seeders are not migrations — there is no automatic "un-seed." If the new permission modules need to be removed post-deploy, that requires a manual cleanup (delete the `customer_credit`/`resolution_center` rows from `modules`/`permissions`, and any role-permission pivot rows) — not covered by any script in this release.
- **The Platform Sprint 1 model-default fix (PR 1) is the one change in this release with the broadest blast radius**: `Module`/`Permission`'s new `$attributes` defaults apply to **every** module/permission created from now on, not just Customer Credit/Resolution Center's — a low-risk, additive default (confirmed to change no runtime behavior — `permission_to_all` is not read by any authorization check today), but worth naming explicitly since it's the one piece of this release that isn't scoped to a single feature.

---

## 8. Items That Require Staging Validation Before Production

1. **Run all 8 migrations against a real MySQL staging database** (or an environment as close to production's engine/version as possible) — the single highest-priority item in this entire audit. Confirm no `DECIMAL`/`enum`/FK-constraint behavior differs from what SQLite validated.
2. **Confirm whether `order_payments.deleted_at`, `order_products.deleted_at`, and `users.deleted_at`/`employee_code` already exist in the real production/staging schema.** If they don't, `CustomerCreditService`/`ResolutionCenterService` will fail the first time either is exercised against a real order/user — this must be resolved (either confirm the columns exist, or add the missing migrations) before PRs 3/5/6/7 go live.
3. **Run `ModuleSeeder` against a staging copy of the real `modules`/`permissions` tables** (not a fresh/empty database) to reconfirm idempotency against real, pre-existing data — the local validation used only the untouched `personnel` module as a proxy.
4. **A genuine manual click-through of every new screen** in a real browser against staging: the CRM Customer Credit tab (Grant/Redeem), the Order Edit Customer Credit panel (Apply/Remove) and Resolution Center panel (start a case under each scenario), the Resolution Center case-detail page (both scenarios' full flow, including Issue Credit), and the new Operations Center (filters, stat cards, Assign modal, quick-actions). Every phase's own completion report explicitly named this as unverified in this sandboxed environment.
5. **Assign the new permissions to whichever real production roles need them** (§5) — not just confirm Master Admin has them.
6. **Confirm `customers.available_credit_balance`'s real column type in production** (§6 item 4) before relying on any Financial Engine arithmetic against it in a high-volume environment.
7. **Re-run the full `tests/Unit` suite (91/91 locally) in the actual CI/staging environment**, plus attempt `tests/Feature` there — this sandbox could never run the Feature suite at all (a separate, pre-existing, undocumented-until-Phase-2.1 environment limitation, not something this release introduced or can fix).

---

## 9. Items That Should NOT Be Deployed Yet

- **`artisan-output.txt`** — stray, empty, accidental file at the repo root. Not a feature, not referenced anywhere. Delete it; do not stage or commit it.
- **The ~140 modified `storage/app/public/**` / `.gitignore` files** (§1.1) — pre-existing, unrelated to this work, already flagged as out-of-scope since the very first audit in this initiative. Do not include in any of the 7 PRs above.
- **Financial Engine Phase 2.8B (Charge-family migration)** — not built at all (no code exists for it), so there is nothing to withhold, but worth stating explicitly: nothing in this release should be interpreted as unblocking or starting that work. It remains gated on the unresolved Phase 2.7A MySQL question and open `FINANCIAL_TRUTH_TABLE.md` §7 business decisions.
- **Anything from `docs/billing-engine-audit/`** — a separate, pre-existing, unrelated initiative (post-order charge consolidation) that happens to share a codebase with this work but is not part of any of the 7 PRs above and was never touched by any phase described in this document.
- **The two reserved-but-uncalled permissions** (`customer_credit.reverse`, `customer_credit.delete`) are harmless to deploy (they're just permission rows) but should not be assigned to any role yet, since granting them currently has no effect and would only create confusion about what they're for.

---

## 10. Recommended Safe Release Sequence

1. **Pre-flight** (no deploy yet): resolve §8 items 1–3 in staging (MySQL migration run, schema-gap confirmation, seeder idempotency against real data). Delete `artisan-output.txt` and confirm the `storage/**` noise is excluded from every PR's diff before staging any commit.
2. **PR 1 — Platform Stabilization Sprint 1**, merged and deployed alone first. Run `ModuleSeeder` immediately after — confirms the seeder mechanism itself works in production before anything depends on it.
3. **PR 2 — Financial Engine Modernization**, merged and deployed. Fully independent; can happen in parallel with step 2 if preferred, since neither touches the other's files. Manually verify one real Payment and one real Order transaction post-deploy (the 7 migrated call sites).
4. **PR 3 — Customer Credit Service Foundation**, merged and deployed. Run `ModuleSeeder` again (idempotent — picks up the `customer_credit` module).
5. **PR 4 — Customer Credit Administration** and **PR 5 — Order Entry Credit Integration**, in either order (both depend only on PR 3, not on each other) — can even ship as one combined PR if preferred, since they're both thin UI layers over the same service.
6. **PR 6 — Customer Resolution Center**, merged and deployed. Run `ModuleSeeder` again (picks up the `resolution_center` module).
7. **PR 7 — Resolution Operations Center**, merged and deployed last, since it depends on everything above.
8. **Assign roles** (§5) to the real production users/roles who need Customer Credit and Resolution Center access — do this as its own explicit, tracked step, not an assumption baked into "deploy the code."
9. **Post-deployment validation** — run the checklist in §12 immediately after step 7, before considering the release complete.

Each numbered step above is independently revertible (per §7) if a problem is found before the next step begins — this sequence deliberately does not require all 7 PRs to succeed atomically.

---

## 11. Pre-Production Validation Checklist

- [ ] All 8 new migrations run cleanly against a real MySQL staging database, in order, with no errors
- [ ] `migrate:rollback` tested against that same staging database for at least the last-deployed batch, confirming clean reversal with no orphaned rows
- [ ] Confirmed presence (or added via new migration) of `order_payments.deleted_at`, `order_products.deleted_at`, `users.deleted_at`, `users.employee_code` in the real schema
- [ ] Confirmed real column type of `customers.available_credit_balance` (expected `DECIMAL(15,2)`)
- [ ] `ModuleSeeder` run against a staging copy of real `modules`/`permissions` data — confirmed idempotent, confirmed `customer_credit`/`resolution_center` modules created correctly, confirmed no existing module/permission disturbed
- [ ] `artisan-output.txt` deleted from the working tree
- [ ] Only the files listed in §1.1/§1.2 (not the `storage/**`/`.gitignore` noise) are staged across the 7 PRs
- [ ] Full `tests/Unit` suite passes in the staging/CI environment (target: 91/91)
- [ ] `tests/Feature` suite attempted in staging (even if it cannot run in this sandbox — confirm whether it can run there)
- [ ] Manual click-through, in a real browser against staging, of: CRM Credit tab (Grant + Redeem), Order Edit Credit panel (Apply + Remove), Order Edit "Start Resolution Case" (both scenarios), Resolution Center case detail (full flow including Issue Credit, for both scenarios), Resolution Operations Center (filters, stat cards, Assign, Escalate, Close, Reopen)
- [ ] At least one real Payment and one real Order transaction manually verified post-PR-2, confirming `LedgerBalanceService`-migrated call sites behave identically to pre-migration behavior
- [ ] Roles beyond Master Admin that need Customer Credit/Resolution Center access identified and confirmed with the business owner

## 12. Post-Deployment Validation Checklist

- [ ] `php artisan migrate:status` in production shows all 8 new migrations as `Ran`, in the expected batch order
- [ ] `ModuleSeeder` re-run in production (idempotent — safe to run again if uncertain) and confirmed via `migrate:status`-equivalent (a `modules`/`permissions` table check) that `customer_credit` and `resolution_center` both exist with their full permission sets
- [ ] Master Admin role confirmed to have all 10 new permissions
- [ ] Any additional roles from the pre-production checklist's last item confirmed to have been granted the correct subset of permissions
- [ ] One real Grant and one real Redemption performed against a real (or designated test) customer in production, confirming `CustomerCreditService::remainingBalance()` reflects it correctly and the CRM Credit tab displays it
- [ ] One real Resolution Case started, answered, and completed end-to-end in production for **each** scenario (Cancellation/Refund and Manual Resolution), confirming the case detail page, Audit Trail, and (if Store Credit was selected) the resulting credit issuance all appear correctly
- [ ] The Resolution Operations Center loads in production with correct stat-card counts matching the case(s) just created, and its filters correctly narrow the queue
- [ ] No error-log entries referencing `CustomerCreditService`, `ResolutionCenterService`, `LedgerBalanceService`, `TaxCalculationService`, or `InvoiceCalculationService` in the period immediately following deployment
- [ ] Spot-check that pre-existing, unrelated features (Dispatch, Schedule Conflicts, the CRM Customers list, the admin Dashboard) still function normally — confirming none of the 7 PRs' `ModuleSeeder`/model-default changes had unintended side effects on the broader permission system
- [ ] Confirm the `storage/**` noise from §1.1/§9 was not accidentally included in the deployed artifact
