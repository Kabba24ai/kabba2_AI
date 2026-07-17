# P3-4 — Phase 3 housekeeping batch (CLEAN-1 through CLEAN-10)

**Date:** 2026-07-15
**Scope:** Phase 3, Sprint 1, fourth item only. No other Sprint 1/Phase 3 item started.
**Split:** into **P3-4A** (pure deletions of confirmed-dead/unreachable code — zero behavior risk to any live page) and **P3-4B** (small fixes to currently-rendered, live admin pages) per the instruction to split if the combined diff got too broad. Both are reported together here since both are complete and pass regression.

---

## Method

For every item, before touching anything: grepped/read the actual route files (including whether a `routes.php` file was ever `require`d by its parent — several were not), the controller, the target view, and every place a corresponding route name or view is referenced (`route(...)`, `@include`, `Route::is(...)`), then cross-checked against `PHASE3_IMPLEMENTATION_PLAN.md`/`CHECKLIST_SYSTEM_AUDIT.md`'s claims rather than trusting them — one claim (CLEAN-10's "server-side rejection still applies") turned out to be factually wrong on direct testing; see CLEAN-10 below.

---

## P3-4A — Deletions (confirmed dead/unreachable, no live page touched)

### CLEAN-1 & CLEAN-2 — Orphaned mockup screens / unrouted `QuestionAndCategories` controllers

**Investigation:**
- `routes/admin/checklist_management/customer_admin/question_and_categories/routes.php` and the equivalent `rental_ready/question_and_categories/routes.php` both define real routes — but grepped every parent `routes.php` in the `checklist_management` tree and neither file is ever `require`d anywhere. Confirmed unroutable.
- The Rental Ready side's controller (`RentalReady\QuestionAndCategories\IndexController`) exists on disk but returns a view (`rental_ready.question_and_categories.index`) that does not exist — would throw `ViewNotFoundException` even if the route were ever wired up.
- The Customer Admin side's route references `CustomerAdmin\QuestionAndCategories\IndexController`, which does **not** exist on disk at all.
- The Customer Admin side does have an orphaned mockup view (`customer_admin/question_and_categories/index.blade.php`) with hardcoded "Questions (14)" / "Categories (14)" placeholder tabs and no real controller behind it.
- Grepped the sidebar and all Blade files for any link/`route()` call to either `question_and_categories.*` name — none found. Only found the route pattern referenced in three now-dead `Route::is([...])` variable computations in the sidebar (see CLEAN-7 note below), which were themselves never rendered anywhere.
- Separately, `routes/admin/checklist_management/customer_admin/templates/routes.php` and its Rental Ready equivalent each had a `GET /` → `templates.index` route pointing to a real controller. The Customer Admin one rendered a fully hardcoded mockup view (fake "Heavy Equipment Standard" / "Compact Equipment Standard" cards, a "Save Template" button with no real submit handler — the JS never calls the real `templates.store` route). The Rental Ready one pointed to a view that **does not exist on disk at all** (this is CLEAN-5, folded in here since it's the same pattern). Grepped for `route('...templates.index')` anywhere in the views — no hits; the real, live Templates UI on both trees is the tab-based `_tab_templates.blade.php` partial rendered directly on each tree's own `index.blade.php`, which only ever calls `.templates.store/.update/.delete/.copy` — never `.index`.

**Disposition:** delete. Confirmed unreachable by any route table entry (question_and_categories) or confirmed unlinked-and-mockup/broken (templates.index), with the real functioning UI proven to live elsewhere.

**Files deleted:**
- `routes/admin/checklist_management/rental_ready/question_and_categories/routes.php`
- `routes/admin/checklist_management/customer_admin/question_and_categories/routes.php`
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/QuestionAndCategories/IndexController.php`
- `resources/views/admin/checklist_management/customer_admin/question_and_categories/index.blade.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/IndexController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/IndexController.php`
- `resources/views/admin/checklist_management/customer_admin/templates/index.blade.php`

**Files edited (route line removed, store/update/delete/copy routes preserved unchanged):**
- `routes/admin/checklist_management/customer_admin/templates/routes.php`
- `routes/admin/checklist_management/rental_ready/templates/routes.php`

### CLEAN-6 — Commented-out duplicate `customerAdminTemplate()` relationship

**Investigation:** `app/Models/ChecklistManagement/ChecklistMaster/ChecklistMaster.php` had the exact same `customerAdminTemplate()` `belongsTo` method defined twice — once commented out, once live and in active use (confirmed via multiple controllers/tests calling `$checklistMaster->customerAdminTemplate`).

**Disposition:** delete the commented-out duplicate. Zero risk — it never executed.

**Files edited:** `app/Models/ChecklistManagement/ChecklistMaster/ChecklistMaster.php`

### CLEAN-7 — Dead sidebar scaffolding

**Investigation:** `resources/views/admin/partials/sidebar.blade.php` computed `$customerChecklistActive` from a `Route::is(['admin.checklist-management.customer_checklist.*'])` call (a route name that has never existed), with the comment "Add the correct route for customer checklist when ready." Grepped the whole file for any other use of that variable — none. Also found, while investigating CLEAN-2, three more dead variables in the same file: `$rentalReadyActive`, `$rentalReadyquestion`, `$rentalReadytemplates` — all computed from `Route::is()` calls referencing the just-deleted `question_and_categories` route name (plus `templates.*`, which is real but the variable was still never used anywhere in the rendered markup). Confirmed via grep that none of these four variables are referenced anywhere else in the file beyond their own assignment.

**Disposition:** delete all four dead variable computations. The actual live "Checklist Management" nav section renders using `$checklistManagementActive` (still present, still used) and direct `route()`/`Route::is()` calls per-link further down — none of the deleted variables fed into any visible markup.

**Files edited:** `resources/views/admin/partials/sidebar.blade.php`

### CLEAN-8 — Unverified retired Blade partial `_table.blade_old.php`

**Investigation:** `resources/views/admin/order_management/schedule_assignment/partials/_table.blade_old.php` sits alongside a real, actively-`@include`d `_table.blade.php` in the same directory. Laravel's `@include('...partials._table')` call resolves to files with a literal `.blade.php` extension — a file named `_table.blade_old.php` is never matched by that view-name resolution, so this file was **not just unlinked, but structurally unreachable by Blade's own naming convention**, regardless of whether it was ever intended to be temporary. Grepped for any `blade_old` reference in `app/` — none.

**Disposition:** delete.

**Files deleted:** `resources/views/admin/order_management/schedule_assignment/partials/_table.blade_old.php`

### P3-4A regression tests

Ran the full existing `ChecklistManagement` test suite plus a fresh route-list/view-compile check after all P3-4A deletions:

```
php artisan route:list --name=checklist-management   # 37 routes, no errors — question_and_categories.* and templates.index gone from both trees; store/update/delete/copy intact
php artisan view:clear
php artisan test --filter="ChecklistManagement|ChecklistMaster"
```

Result: **104 passed, 371 assertions, 0 failures.**

---

## P3-4B — Small fixes to live, currently-rendered pages

### CLEAN-3 — Dead "Customer Admin" button

**Investigation:** `resources/views/admin/checklist_management/checklist_master/create.blade.php` had a plain `<button>...Customer Admin</button>` next to a working `<a href="{{ route('admin.checklist-management.rental-ready.index') }}">Rental Ready Admin</a>` link — no `href`, no `onclick`, no JS hook of any kind (grepped the whole file). The same file already links to `customer-admin.index` elsewhere (a "Cancel" link a few hundred lines down), confirming that destination is the correct, already-safe, already-used target.

**Disposition:** finish, not delete — converted the dead `<button>` into an `<a href="{{ route('admin.checklist-management.customer-admin.index') }}">`, mirroring the sibling Rental Ready link's exact markup pattern. Not "activating hidden functionality" — linking to an already-fully-built, already-linked-elsewhere, safe destination.

**Files edited:** `resources/views/admin/checklist_management/checklist_master/create.blade.php`

### CLEAN-4 — `ChecklistMaster\CopyController`'s Copy button left commented-out

**Investigation:** the form/button in `_table.blade.php` was fully HTML-commented-out. Read `CopyController.php` end-to-end: looks up the master, duplicates it (name + category + both template FKs) inside a transaction, flashes success/error, redirects — no bugs found. Confirmed the model regenerates a fresh `unique_id` on create (no collision risk) and the route (`checklist-master.copy`) is registered and otherwise unused elsewhere.

**Disposition:** uncomment. No existing test covered this controller at all — added one.

**Files edited:** `resources/views/admin/checklist_management/checklist_master/partials/_table.blade.php`
**Files added:** `tests/Feature/ChecklistManagement/ChecklistMasterCopyTest.php` (2 tests: successful copy creates a second row with a new `unique_id` and the expected fields; copying a nonexistent `unique_id` flashes an error and creates nothing)

### CLEAN-9 — Edit-mode Step 3 "Continue" validation bypass

**Investigation:** in `edit.blade.php`, the Step 3 "Continue" button had **no `disabled` HTML attribute at all** (unlike `create.blade.php`'s equivalent button, which does) — only CSS classes cosmetically suggested a disabled look. A JS block then unconditionally forced the enabled-looking classes on and set `.disabled = false`, with the comment "Always keep Step 3 Continue button enabled for Edit mode." Because the button's own inline `onclick="goToStep(4)"` was never actually gated by a real `disabled` attribute, the JS "fix" was cosmetic — the button was clickable in edit mode regardless. Checked live data: `customer_admin_template_id` is a nullable column, but **0 of 37 existing `checklist_masters` rows currently have it null** (it's `required` on both create and update), so this was not a live incident, but a genuine latent gap for any hypothetical edge-case record.

**Disposition:** fixed. The JS now only pre-enables the button (and sets `.disabled = false`) when `hiddenCustomerTemplateId` already has a value (the normal case); otherwise it explicitly sets `.disabled = true`, which for the first time actually gates the click. The radio-selection handler was also updated to enable the button once a template is picked, closing the gap for the edge case. **No server-side code was touched** — `UpdateRequest`'s `customer_admin_template_id => required` rule is unchanged and remains the real enforcement boundary either way.

**Files edited:** `resources/views/admin/checklist_management/checklist_master/edit.blade.php`
**Files added:** `tests/Feature/ChecklistManagement/ChecklistMasterEditStep3ValidationTest.php` (2 render-level tests: a record with a template assigned renders the "pre-enable" script branch and the correct hidden-input value; a record with `customer_admin_template_id = null` renders the "stay disabled" branch). **Limitation:** this project has no browser/JS test runner (no Dusk, no Jest/Cypress) — these tests confirm the correct markup/script is rendered for each data state, not that a real browser click is actually blocked. Manual verification in a browser is recommended before this specific sub-item is considered fully closed, though the fix itself mirrors Create mode's already-proven pattern exactly.

### CLEAN-10 — Client-side gap: template builder has no "must not be empty" check

**Investigation — and a correction to the prior audit finding:** `CHECKLIST_SYSTEM_AUDIT.md` states "an empty-template submit only fails server-side with no inline warning," implying server-side rejection exists. **This was verified directly and found to be incorrect**: wrote a probe test posting `questions: '[]'` to the real `rental-ready.templates.store` route — it returned a 302 redirect and created a template with 0 questions. Read `TemplateCrudService::store()` and `StoreRequest::rules()` end-to-end: neither has any check for an empty question list (`'questions' => 'required'` only requires the JSON *string* to be non-empty — `"[]"` satisfies that trivially). **Server-side does not reject an empty template today, on either tree.**

**Disposition, per explicit instruction not to change server-side business behavior:** added the client-side "must not be empty" inline warning only (an `id="questionsRequiredWarning"` message plus a `submit` listener on `templateForm` that blocks submission via `preventDefault()` when `window.templateData` is empty), to both `rental_ready/partials/_tab_templates.blade.php` and `customer_admin/partials/_tab_templates.blade.php`. **The actual server-side gap (a template can still be saved with 0 questions) was not fixed here** — that would be a business-logic change beyond a housekeeping item, and is flagged below as a recommended follow-up, not silently rolled into this PR.

**Files edited:**
- `resources/views/admin/checklist_management/rental_ready/partials/_tab_templates.blade.php`
- `resources/views/admin/checklist_management/customer_admin/partials/_tab_templates.blade.php`

**Files added:** `tests/Feature/ChecklistManagement/Templates/EmptyTemplateClientValidationTest.php` (3 tests: the new warning element + guard script are present on both index pages; a direct HTTP probe confirming the server still accepts an empty-questions template unchanged today). Same browser-test-runner limitation as CLEAN-9 applies to the actual submit-blocking behavior.

**Recommended follow-up (not implemented, not silently expanded into this PR):** file a new backlog item for the actual server-side empty-template gap — `TemplateCrudService::store()`/`updateWithReplacedQuestions()` should probably reject (or the FormRequest should validate) a genuinely empty `questionRows` array, on both trees, once a product decision on the right response shape is made.

### P3-4B regression tests

```
php artisan test --filter=ChecklistMasterCopyTest
php artisan test --filter=ChecklistMasterEditStep3ValidationTest
php artisan test --filter=EmptyTemplateClientValidationTest
php artisan test --filter="ChecklistManagement"   # combined P3-4A + P3-4B regression
```

Result: **111 passed, 398 assertions, 0 failures** (combined final run, includes every test added across P3-4A and P3-4B plus the full pre-existing ChecklistManagement suite).

---

## Deferred items and reasons

- **CLEAN-10's underlying server-side gap** (empty-template templates can be saved) — not implemented; flagged as a follow-up requiring a product decision, per "do not change server-side business behavior."
- **CLEAN-9/CLEAN-10's actual browser click/submit-blocking behavior** — not executed by an automated test; this project has no Dusk/Jest/Cypress configured. Recommend a manual smoke test in a browser before merge, though both fixes mirror already-proven patterns elsewhere in the same files (Create mode's disabled-button pattern; a standard `submit` + `preventDefault()` guard).

## Exact files changed (all of P3-4A + P3-4B)

**Deleted:**
- `routes/admin/checklist_management/rental_ready/question_and_categories/routes.php`
- `routes/admin/checklist_management/customer_admin/question_and_categories/routes.php`
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/QuestionAndCategories/IndexController.php`
- `resources/views/admin/checklist_management/customer_admin/question_and_categories/index.blade.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/IndexController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/IndexController.php`
- `resources/views/admin/checklist_management/customer_admin/templates/index.blade.php`
- `resources/views/admin/order_management/schedule_assignment/partials/_table.blade_old.php`

**Edited:**
- `routes/admin/checklist_management/customer_admin/templates/routes.php`
- `routes/admin/checklist_management/rental_ready/templates/routes.php`
- `app/Models/ChecklistManagement/ChecklistMaster/ChecklistMaster.php`
- `resources/views/admin/partials/sidebar.blade.php`
- `resources/views/admin/checklist_management/checklist_master/create.blade.php`
- `resources/views/admin/checklist_management/checklist_master/partials/_table.blade.php`
- `resources/views/admin/checklist_management/checklist_master/edit.blade.php`
- `resources/views/admin/checklist_management/rental_ready/partials/_tab_templates.blade.php`
- `resources/views/admin/checklist_management/customer_admin/partials/_tab_templates.blade.php`

**Added:**
- `tests/Feature/ChecklistManagement/ChecklistMasterCopyTest.php`
- `tests/Feature/ChecklistManagement/ChecklistMasterEditStep3ValidationTest.php`
- `tests/Feature/ChecklistManagement/Templates/EmptyTemplateClientValidationTest.php`
- `docs/checklist-system-audit/P3_4_HOUSEKEEPING.md` (this file)

No migrations. No production data changed. No server-side validation/business-rule behavior changed anywhere in this batch.

## Tests run — final summary

| Suite | Result |
|---|---|
| `route:list --name=checklist-management` | 37 routes, clean |
| `ChecklistManagement\|ChecklistMaster` (P3-4A only) | 104 passed, 371 assertions |
| `ChecklistManagement` (combined P3-4A + P3-4B, final) | **111 passed, 398 assertions, 0 failures** |

## Ready for review

**Yes**, with one caveat: CLEAN-9 and CLEAN-10's client-side behavior should get a quick manual browser smoke test before merge, since this project has no automated JS/browser test runner to verify the actual click/submit-blocking behavior end-to-end. Every other item (P3-4A's six deletions, CLEAN-3, CLEAN-4) is fully verified by the automated suite above. Stopping here per instructions — no P3-5 or later item started.
