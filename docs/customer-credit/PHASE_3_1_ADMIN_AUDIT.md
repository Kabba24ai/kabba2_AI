# Phase 3.1 — Customer Credit Administration: Pre-Implementation Audit

Date: 2026-07-03
Branch: `raj_development`
Scope: identify exactly where and how the Customer Credit administration UI should integrate with existing CRM infrastructure, before writing any code.

---

## 1. Existing CRM Integration Points

**Customer record screen**: `resources/views/admin/crm/customers/view.blade.php`, rendered by `App\Http\Controllers\Admin\Crm\Customers\ViewController` (route `GET /admin/crm/customers/{unique_id}/view`, name `admin.customers.view`).

**Tab system**: Alpine.js, not a component library or Livewire. The whole tab strip lives inline in `view.blade.php` (lines ~102-200):
- One `<div x-data="{ activeTab: '{{ session('active_tab', 'dashboard') }}' }">` wrapper holds all tab state.
- Each tab is a `<button>` with `x-bind:class="activeTab === 'name' ? <active classes> : <inactive classes>"` and `x-on:click="activeTab = 'name'"`.
- Each tab's content is a `<div x-show="activeTab === 'name'">@include('admin.crm.customers.partials._tab_name')</div>`.
- Five tabs exist today: `dashboard`, `orders`, `credit` (this is the *existing ledger/transaction* tab, `_tab_credit.blade.php`, already touched in Phase 2.5 of the Financial Engine work — **not** to be confused with the new Customer Credit tab this phase adds), `invoices`, `account`.

**Recommended integration**: add a sixth tab, `store_credit`, following the exact same button/div pattern, with its own new partial `_tab_store_credit.blade.php`. This is the cleanest possible integration — no new screen, no new navigation concept, matches this document's audit findings exactly.

**Controller data**: `ViewController::__invoke()` already eager-loads `accounts.responsibleUser` on the `Customer` model and passes `$customer` to the view as a whole. The new tab's data (credit summary + history) will be added the same way: a new `customerCredits` relationship on `Customer` (mirroring the existing `accounts()` relationship), eager-loaded here, plus a call to `CustomerCreditService::remainingBalance()` for the summary figures.

## 2. Existing Customer Screens / Financial Tabs / Billing Summary

- `_tab_dashboard.blade.php`, `_tab_orders.blade.php`, `_tab_credit.blade.php` (the ledger tab), `_tab_invoices.blade.php`, `_tab_account.blade.php` — all under `resources/views/admin/crm/customers/partials/`, all simple Blade partials receiving `$customer` from the parent view's scope (no separate controller call per tab — they render from data the parent `ViewController` already loaded).
- Billing Summary: `resources/views/admin/crm/billing_summary/index.blade.php` + `partials/_table.blade.php`, backed by `Admin\Crm\Customers\IndexController`-style AJAX filtering (see §7). This is a **cross-customer** list screen, structurally different from a per-customer tab. This phase's primary deliverable (the CRM tab) does not need this pattern; a possible future cross-customer "all customer credit activity" report would.

## 3. Existing Customer Account Screens (the pattern this phase's Grant/Redeem dialogs will mirror)

`app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/` contains `PaymentStoreController`, `RefundStoreController`, `DiscountStoreController`, `ChargeStoreController`, `UpdateController`, `DeleteController` — each a single-purpose, `__invoke()`-only controller taking a `FormRequest`, wrapping the write in `DB::beginTransaction()`/`DB::commit()`. Their modals live inline inside `_tab_credit.blade.php` (Payment: lines ~658-858, Refund: ~860-977, Discount: ~979-1139, Charge: ~1141-1285) — fixed-position overlay divs (`id="xModalWrapper"`, `style="display:none"`, toggled via plain JS `element.style.display = 'flex'`/`'none'`), a form using this app's `html()->form()` helper with `data-parsley-validate` for client-side validation, and a submit button that shows a spinner while an AJAX (or full POST) submission is in flight.

**Recommended pattern for Grant Credit / Redeem Credit dialogs**: two new modals following this exact shape, added to the new `_tab_store_credit.blade.php` partial, each posting to a new single-purpose controller.

## 4. Existing Permission Model — Important Finding

This required the most careful verification, since the mission explicitly says "follow the existing HRM permission model" and "do not hardcode Admin/Master Admin" — and the actual state of this infrastructure is more nuanced than it first appears:

- `App\Models\Iam\Personnel\User` uses Spatie's `HasRoles` and `HasPermissions` traits.
- `App\Models\Iam\AccessControl\Role extends Spatie\Permission\Models\Role` — a real customization of Spatie's own Role model, stored in Spatie's standard `roles` table (confirmed: `protected $table = 'roles'`), with added columns (`unique_id`, `short_name`, `color`, `description`, `status`).
- `App\Models\Iam\AccessControl\Permission` — **does not** extend Spatie's Permission model; it's a plain `Model`, but uses the same table name Spatie's own config expects (`permissions`), with added columns (`module_id`, `title`, `permission_to_all`). `config/permission.php` is left at its default, still pointing `'permission' => Spatie\Permission\Models\Permission::class`. Because both classes share the same table and the same core columns (`name`, `guard_name`), Spatie's own methods (via the `HasPermissions` trait) and the custom `AccessControl` models are two different Eloquent views onto the *same* underlying data — not two competing systems. The custom classes exist for the admin UI (module-grouped display, colored role badges); Spatie's own trait methods are what's available for real authorization checks.
- Permissions are seeded via `database/seeders/Iam/ModuleSeeder.php`, in a `module_category → module → permission_names` structure. Each permission is named `{module_name}.{action}` (e.g., `personnel.view`). Only two module categories exist today: `IAM` and `Product Management` — there is no `CRM` category yet.
- **Critical finding: there is zero existing controller, route, or Blade file anywhere in this codebase that actually calls `$user->can(...)`, `$user->hasPermissionTo(...)`, `@can`, or the registered `permission:`/`role:` route middleware.** Confirmed via repository-wide search. The middleware aliases (`'permission' => PermissionMiddleware::class`, etc.) are registered in `bootstrap/app.php` and fully functional, but unused. Sensitive existing actions (`VoidPaymentController`, `DiscountStoreController`) only check `auth()->user()` for *identity* (who performed the action, for logging), never for *authorization* (whether they're allowed to).

**Conclusion, stated plainly**: there is no existing enforcement pattern to copy verbatim — only existing, real, functional infrastructure that has never been exercised. This phase will be the first real caller of it, the same situation Phase 3.0 was in for parts of `CustomerCreditService`. This is disclosed here rather than glossed over, since "follow the existing permission model" could otherwise be misread as "there's a working example to copy."

**Recommended approach**: extend `ModuleSeeder` with a new module category (`Customer Credit`), a `customer_credit` module, and six permissions (`customer_credit.view`, `.grant`, `.redeem`, `.reverse`, `.delete`, `.view_audit_history`) — following the exact existing naming convention — and enforce them via Spatie's `permission:` route middleware on the new routes (the cleanest, most idiomatic mechanism, and the one this app's own middleware registration already anticipates).

## 5. Existing Audit Logging

`CustomerAccount::customer_action_log` (a JSON column), populated by `CustomerAccount/UpdateController.php` (lines ~134-154) with entries shaped like:
```php
['id' => uniqid('log_'), 'action' => '...', 'performed_by' => ['id' => ..., 'name' => ...],
 'performed_at' => now()->toDateTimeString(), 'changes' => [...], 'note' => '...']
```
No general-purpose activity-log package (e.g. `spatie/laravel-activitylog`) is installed or used anywhere else.

**Recommended approach**: per Phase 3.0's own design, `customer_credits` (grant/redemption rows) already *is* the audit trail for Financial Credit — every grant and redemption is a permanent row recording who, when, how much, and why. This phase adds the two fields the mission explicitly asks for that aren't yet columns (`Effective Date`, `Internal Comments`, needed for the Grant Credit dialog) via a small, additive migration, and satisfies "Previous Value / New Value" by **computing and displaying a running balance in the History table**, not by storing it redundantly on each row — avoiding the exact kind of cached-value drift this entire initiative has repeatedly found and eliminated elsewhere (`PHASE_2_5A_LEDGER_READINESS_REVIEW.md`, `PHASE_2_7_COMPLETION_REPORT.md`). The `customer_action_log` JSON-array pattern is not reused verbatim, since `customer_credits` already provides row-level audit granularity `customer_action_log` exists to retrofit onto a model (`CustomerAccount`) that otherwise mutates its own rows in place — `customer_credits` rows are never mutated after creation, so there is nothing to retrofit.

## 6. Existing Search/Filter Framework

`Admin\Crm\Customers\IndexController` (lines ~20-129): filters submitted as GET query parameters, checked via `$request->filled('field')`, applied directly to an Eloquent query builder chain; an AJAX request (`$request->ajax()`) returns a JSON payload with rendered table HTML + a total count, letting the frontend swap the table body without a full page reload. `billing_summary/index.blade.php` uses the same GET-param + AJAX-refresh approach, with a `FilterFreezer` JS utility persisting filter state in the session across reloads.

**Recommended approach for this phase**: the Customer Credit History table lives **inside a single customer's tab**, so a "Customer" filter dimension is redundant (already scoped). Local filtering (date range, user, credit type, amount range) is implemented client-side via simple, dependency-free JS filtering of the already-rendered table rows (the realistic row count for one customer's credit history does not warrant a server round-trip) — this deliberately does **not** replicate the full GET-param/AJAX/`FilterFreezer` machinery, since that pattern exists to solve a different problem (paginating and filtering potentially thousands of rows across all customers), which does not apply to a single customer's history. A cross-customer administration list (where that heavier pattern would make sense) is recommended as a **Phase 3.2 candidate**, not built here — see the Completion Report's recommendation.

## 7. Existing Reporting Framework

`SalesReportEngineV2`, `PaymentReconciliationLedger`, `SalesTaxReportEngine` — all read-only, `customer_accounts`-consuming report engines, unrelated to `customer_credits`. No changes needed or made here; a future Customer Credit report (Outstanding Store Credit, per `CUSTOMER_CREDIT_ARCHITECTURE.md` §7) would follow this exact same "read-only consumer" pattern when built, not before.

## Recommendation

**Build the Customer Credit administration experience as a sixth tab on the existing CRM customer record screen** (`_tab_store_credit.blade.php`, added to `view.blade.php`'s existing Alpine tab system), with two new single-purpose controllers (Grant, Redeem) mirroring the existing `CustomerAccount` modal controllers exactly, gated by six new permissions registered through the existing `ModuleSeeder` and enforced via Spatie's already-registered-but-unused `permission:` route middleware. A cross-customer administration list screen (satisfying the "Customer" search dimension) is deferred to a recommended Phase 3.2, since the mission's explicit, detailed field list is for the per-customer tab, and building a second full screen in this phase would not be a "small diff."
