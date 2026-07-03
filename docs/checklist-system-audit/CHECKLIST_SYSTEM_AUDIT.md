# Checklist System & Master Checklist System — Full Audit Report

**Audit date:** 2026-07-03
**Audit type:** Read-only code/schema audit. No functionality changes, no migrations, no refactors, no UI changes, no API changes, no route changes were made.
**Code modified:** **No.**
**Branch audited:** `raj_development`

> **Phase 2 addendum available:** `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` in this same folder covers route caller mapping, literal end-to-end call graphs, a model dependency/blast-radius map, event/listener/observer chain tracing, a business-rule enforcement matrix, a runtime validation test plan, risk impact/likelihood scoring, and an explicit refactor-readiness verdict. Read it before planning a correction phase — it identifies that `equipment_status_logs` is silently never populated by any mobile-driven equipment status change (a `saveQuietly()` bug bypassing `EquipmentObserver`), that checklist answers are a direct, unguarded billing input, and that no automated tests or seed data exist for this system.
>
> **Phase 3 addendum available:** `PHASE3_RUNTIME_VALIDATION_SQL_PLAN.md` turns every open finding above into an executable test plan — exact API calls, sample payloads, expected DB changes, ready-to-run SQL verification queries for all ten confirmed/suspected defects, a prioritized run order, and an explicit go/no-go framework for the correction phase.
>
> **Phase 3 has been executed** (against the local dev environment, with explicit authorization to mutate its database) — results are in `PHASE3_RESULTS.md`. Headline outcome: **NO-GO on billing code changes** until finance reconciles a reproduced, confirmed bug where a legitimate second damage/fuel charge on a re-rented order product is silently dropped by an over-scoped idempotency key; **GO with elevated urgency** on the `equipment_status_logs` audit-trail gap (confirmed at 100% failure for every mobile-driven status transition tested); and a new, larger-than-expected finding that 33% of all delivered order products (876 of 2,665, including ones created in the week before this audit) have zero checklist trail at all.
>
> **Correction Phase 1 plan available:** `CORRECTION_PHASE1_PLAN.md` scopes a safe, minimal-risk fix plan for exactly the five issues confirmed by Phase 3 — evidence, affected files, root cause, safest fix approach, required tests, rollback risk, data-cleanup needs, priority, and implementation order for each. No refactoring, no schema changes, and no enforcement of new business rules are included in this phase by design — see the plan's own rationale for why (e.g., the completeness/signature gap gets observability-only logging in this phase, with actual enforcement explicitly deferred). No code has been changed yet.
>
> **Issue #5's investigation has been completed:** `ISSUE5_INVESTIGATION_FINDINGS.md` traces all 875 delivered-but-checklist-less order products to two fully-explained root causes — 95% are a legitimate, by-design admin "Close as Completed" workflow (not a bug, but a data-interpretation trap for anything treating "delivered" as a proxy for "checklist completed"), and 5% are a real, confirmed gap where the mobile app can complete a delivery with an empty `checklist` array. The investigation also found a **third site** of the Issue #2 `saveQuietly()` bug, in `UpdateProductScheduleController.php`, now folded into that issue's scope.

---

## 1. Executive Summary

The Checklist System is actually **three loosely-related subsystems** wearing one name:

1. **Rental Ready checklist tree** — equipment-condition question bank (`rental_ready_checklist_*` tables), used to inspect equipment and set `equipment.current_status`.
2. **Customer Admin checklist tree** — customer delivery/return question bank (`customer_admin_*` tables), used to build the customer-facing delivery/return checklist on an order.
3. **`ChecklistMaster`** — a thin routing/composition record that pairs exactly one Rental Ready template + one Customer Admin template + an equipment category, and is the only place the two trees are joined. It holds no question content of its own.

Both content trees are **structurally duplicated, independently coded, and already diverging** (different soft-delete behavior, different validation, different admin UI code, no shared service layer). They are connected only by a one-time, one-directional "also create a matching category" checkbox fired once at category-creation time — nothing keeps them in sync afterward.

Layered on top of this is a **third, independent checklist-like mechanism**: flat `dispatch_checklist` (JSON) and `delivery_/pickup_*_status` string columns bolted directly onto `order_products`, with zero FK/schema relationship to either checklist tree, driven by its own controllers (`DriverChecklistController`, `UpdateDeliveryPickupInputsController`). This is where the audit found the sharpest risk: **four of its status columns (`tnc_status`, `drivers_license_status`, `video_status`, `checklist_status`) accept arbitrary client-supplied strings with no enum constraint and no cross-check against whether the license/video/checklist actually exists.** They are currently write-only (nothing reads them yet), so there is no live impact today — but the moment a dashboard or gating rule starts trusting them, this becomes a real data-integrity and business-logic risk.

The core delivery/return checklist workflow (`SaveDeliveryController`/`SaveReturnController`) is architecturally sound in one important respect: **it correctly snapshots** master-template question/answer text onto order-level rows at delivery time, so later edits to the master templates do not retroactively corrupt historical order checklists. However, **"completion" is not actually enforced anywhere on the server** — `delivery_status`/`pickup_status` are unconditionally set to `'Completed'` regardless of whether required questions were answered, a signature was captured, or a driver's license/video exists. All such enforcement lives client-side only.

Equipment-to-checklist assignment has **three independent, uncoordinated code paths** writing the same `equipment.checklist_master_id` column, with no shared service layer — the most dangerous being a bulk "unassign everyone, then reassign" step embedded in the `ChecklistMaster` edit screen, unrelated to and unaware of the other two.

The Rental Ready ↔ Master Checklist connection itself, which this audit was specifically asked to verify following the prior Rental Ready audit's findings, is **not a source of duplication or drift** — `ChecklistMaster` references `RentalReadyChecklistTemplate` by live FK, not by copy, so there is one true content source for Rental Ready question data. The duplication problem in this system is between the *two checklist trees* (Rental Ready vs. Customer Admin), not between Rental Ready and its Master Checklist wrapper.

Two commented-out server-side validation guards were found still sitting in shipped, live-routed controllers (a "required questions answered" check in the mobile Rental Ready save flow, and an "invalid equipment status" guard in its index endpoint) — both disabled, meaning the checks they describe do not currently run.

No template versioning exists anywhere in the schema. The system instead relies on denormalization (copying text at submission time) to protect historical order records from later template edits — this works correctly for the customer checklist flow, with one confirmed exception (`is_damaged` is read live at return time rather than from a snapshot, so an admin's mid-rental edit to a master answer's damage flag can change return-time billing outcomes).

**No dead backend logic was found with functioning UI wired to nothing — the reverse was found instead**: two admin screens (`customer_admin/templates/index.blade.php`, `customer_admin/question_and_categories/index.blade.php`) are static, hardcoded mockups with non-functional buttons, one of which references a controller that does not exist on disk, and a symmetric Rental Ready controller has no registered route at all.

---

## 2. Audit Scope

Per the mission brief, this audit covers: the Master Checklist system, Checklist Template structure, the Rental Ready ↔ Master Checklist connection, the customer checklist delivery/return/driver workflow, admin checklist screens, all checklist-related API endpoints, the database schema and relationships, status/completion logic, and mobile/offline workflow risk. It excludes the Rental Ready system's own internal correctness (already audited separately — see `docs/rental-ready-customer-checklist-audit/`), except where it interacts with the Master Checklist / Checklist system.

**Method:** Static code and schema reading only. Eight independent research passes were run in parallel, each scoped to one audit area, then cross-checked and reconciled into this report. No code was executed; no database was queried at runtime; no `php artisan` commands were run against a live DB (one dependent audit used `php artisan route:list` to confirm route registration, a read-only introspection command — see §12).

---

## 3. Files and Modules Inspected

### Migrations (chronological, full list)
```
database/migrations/checklist_master/2025_08_25_175317_create_checklist_masters_table.php
database/migrations/checklist_master/2025_08_29_114816_add_soft_deletes_to_checklist_masters_table.php
database/migrations/checklist_master/2025_09_02_104936_add_customer_admin_template_id_to_checklist_masters.php
database/migrations/checklist_mangement/rental_ready/2025_08_19_122140_create_rental_ready_checklist_categories_table.php
database/migrations/checklist_mangement/rental_ready/2025_08_19_122148_create_rental_ready_checklist_questions_table.php
database/migrations/checklist_mangement/rental_ready/2025_08_19_122153_create_rental_ready_checklist_question_answers_table.php
database/migrations/checklist_mangement/rental_ready/2025_08_20_163308_create_rental_ready_checklist_templates_table.php
database/migrations/checklist_mangement/rental_ready/2025_08_20_163320_create_rental_ready_checklist_template_questions_table.php
database/migrations/checklist_mangement/rental_ready/2025_08_21_162850_update_equipment_category_in_rental_ready_checklist_templates.php
database/migrations/checklist_mangement/rental_ready/2025_08_28_151041_add_soft_deletes_to_rental_ready_tables.php
database/migrations/checklist_mangement/equipment_checklist/2025_08_29_173808_create_equipment_rental_ready_templates_table.php
database/migrations/checklist_mangement/equipment_checklist/2025_08_29_173916_create_equipment_rental_ready_checklist_questions_table.php
database/migrations/checklist_mangement/equipment_checklist/2025_08_29_173952_create_equipment_rental_ready_checklist_question_logs_table.php
database/migrations/checklist_mangement/equipment_checklist/2025_09_04_151920_add_soft_deletes_to_equipment_rental_ready_tables.php
database/migrations/checklist_mangement/equipment_checklist/2025_09_04_165620_update_equipment_hours_in_equipment_rental_ready_templates_table.php
database/migrations/checklist_mangement/equipment_checklist/2025_09_05_173427_add_is_complete_to_equipment_rental_ready_templates_table.php
database/migrations/checklist_mangement/equipment_checklist/2025_09_08_225725_alter_column_to_equipment_rental_ready_templates_table.php
database/migrations/checklist_mangement/equipment_checklist/2025_09_10_151649_update_equipment_rental_ready_checklist_question_logs_table.php
database/migrations/checklist_mangement/equipment_checklist/2025_11_04_143653_add_is_damaged_to_customer_admin_question_answers_table.php
database/migrations/checklist_mangement/equipment_checklist/2025_12_15_163837_create_equipment_status_logs_table.php
database/migrations/(customer_admin tree)/2025_08_21_231303_create_customer_admin_categories.php
database/migrations/(customer_admin tree)/2025_08_27_001953_customer_admin_questions.php
database/migrations/(customer_admin tree)/2025_08_27_105917_customer_admin_question_answers.php
database/migrations/(customer_admin tree)/2025_08_27_111450_customer_admin_templates.php
database/migrations/(customer_admin tree)/2025_08_27_112245_customer_admin_template_questions.php
database/migrations/orders/2025_09_05_020320_create_order_product_checklist_questions_table.php
database/migrations/orders/2025_09_05_021505_create_order_product_checklist_question_answers_table.php
database/migrations/orders/2026_05_06_000003_add_soft_deletes_to_order_related_tables.php
database/migrations/2026_05_31_142648_add_dispatch_checklist_to_order_products.php
database/migrations/2026_06_17_144052_add_equipment_fields_to_order_products_table.php (superseded)
database/migrations/orders/2026_06_19_120000_refactor_driver_checklist_fields_on_order_products_table.php
database/migrations/2026_06_23_193818_add_delivery_pickup_inputs_to_order_products.php
```

### Models
```
app/Models/ChecklistManagement/ChecklistMaster/ChecklistMaster.php
app/Models/ChecklistManagement/RentalReady/RentalReadyChecklistCategory.php
app/Models/ChecklistManagement/RentalReady/RentalReadyChecklistQuestion.php
app/Models/ChecklistManagement/RentalReady/RentalReadyChecklistQuestionAnswer.php
app/Models/ChecklistManagement/RentalReady/RentalReadyChecklistTemplate.php
app/Models/ChecklistManagement/RentalReady/RentalReadyChecklistTemplateQuestion.php
app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminCategory.php
app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminQuestion.php
app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminQuestionAnswer.php
app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminTemplate.php
app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminTemplateQuestion.php
app/Models/ChecklistManagement/EquipmentChecklist/EquipmentRentalReadyTemplate.php
app/Models/ChecklistManagement/EquipmentChecklist/EquipmentRentalReadyChecklistQuestion.php
app/Models/ChecklistManagement/EquipmentChecklist/EquipmentRentalReadyChecklistQuestionLog.php
app/Models/ChecklistManagement/EquipmentChecklist/EquipmentStatusLog.php
app/Models/Orders/OrderProductChecklistQuestion.php
app/Models/Orders/OrderProductChecklistQuestionAnswers.php
app/Models/MaintenanceManagement/Equipment.php (cross-reference host)
app/Models/Orders/OrderProduct.php (cross-reference host)
app/Models/Orders/Order.php (cross-reference host)
```

### Controllers — Admin (session/web)
```
app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/{Index,Create,Store,Edit,AssignChecklist,Update,Copy,Delete,Fetch}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/RentalReady/{Index}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/{Store,Update,Delete}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/{Store,Update,Delete,Copy}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/{Index,Store,Update,Delete,Copy}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/RentalReady/QuestionAndCategories/IndexController.php (unrouted — dead)
app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/{Index}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/{Store,Update,Delete}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/{Store,Update,Delete,Copy}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/{Index,Store,Update,Delete,Copy}Controller.php
app/Http/Controllers/Admin/ChecklistManagement/EquipmentManagement/{Index,ChecklistQuestions,Store}Controller.php
app/Http/Controllers/Admin/MaintenanceManagement/Equipment/AssignChecklistMasterController.php
app/Http/Controllers/Admin/MaintenanceManagement/Equipment/{Index,Create,Store,Edit,Update,Delete,Fetch,AssignStore,Copy}Controller.php
```

### Controllers — API (mobile-facing, `Api\Admin\V1`)
```
app/Http/Controllers/Api/Admin/V1/CustomerChecklists/IndexController.php
app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php
app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/{SaveDelivery,SaveReturn,Remove}Controller.php
app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php
app/Http/Controllers/Api/Admin/V1/Orders/Schedules/DriverChecklistController.php
app/Http/Controllers/Api/Admin/V1/Orders/Schedules/UpdateDeliveryPickupInputsController.php
app/Http/Controllers/Api/Admin/V1/Orders/UploadMediaController.php
app/Http/Controllers/Api/Admin/V1/Orders/RemoveMediaController.php
```

### Requests / Resources / Enums / Events / Listeners
```
app/Http/Requests/Admin/ChecklistManagement/**/* (Store/Update requests for ChecklistMaster, RentalReady, CustomerAdmin, EquipmentManagement)
app/Http/Requests/Admin/MaintenanceManagement/Equipment/AssignChecklistMasterRequest.php
app/Http/Requests/Api/Admin/V1/Orders/CustomerChecklists/{SaveDelivery,SaveReturn,Remove}Request.php
app/Http/Requests/Api/Admin/V1/Orders/RentalReadyChecklists/SaveRequest.php
app/Http/Requests/Api/Admin/V1/Orders/Schedules/{DriverChecklist,UpdateDeliveryPickupInputs}Request.php
app/Http/Requests/Api/Admin/V1/Orders/UploadMediaRequest.php
app/Http/Requests/Api/Admin/V1/{CustomerChecklists,RentalReadyChecklists}/IndexRequest.php
app/Http/Resources/Api/Admin/V1/CustomerChecklistQuestions/{ListResource,CategoriesListResource,AnswersListResource}.php
app/Http/Resources/Api/Admin/V1/RentalReadyChecklistQuestions/ListResource.php
app/Http/Resources/Api/Admin/V1/Equipment/ListResource.php
app/Enums/Orders/OrderCustomerChecklistType.php, OrderMediaType, EquipmentDriverStatus, OrderProductChargeStatus
app/Events/Admin/Orders/OrderCustomerChecklistEvent.php, OrderProductDriverChecklistUpdated.php
app/Listeners/Activities/Admin/Orders/OrderCustomerChecklistListener.php, OrderProductDriverChecklistUpdatedListener.php
```
No `app/Services` or `app/Actions` classes exist for the checklist domain — all business logic lives directly in controllers.

### Views (Blade — no Vue/React/Inertia in this module)
```
resources/views/admin/checklist_management/checklist_master/{index,create,edit}.blade.php
resources/views/admin/checklist_management/rental_ready/index.blade.php (+ tab partials)
resources/views/admin/checklist_management/customer_admin/index.blade.php (+ tab partials)
resources/views/admin/checklist_management/customer_admin/templates/index.blade.php (orphaned mockup)
resources/views/admin/checklist_management/customer_admin/question_and_categories/index.blade.php (orphaned mockup)
resources/views/admin/checklist_management/equipment_management/index.blade.php
resources/views/admin/maintenance_management/equipment/index.blade.php (+ partials)
resources/views/admin/partials/sidebar.blade.php
```

---

## 4. Database Tables and Relationships

### Full table list (17 checklist-specific tables, plus checklist columns on `order_products`)
`checklist_masters`, `rental_ready_checklist_categories`, `rental_ready_checklist_questions`, `rental_ready_checklist_question_answers`, `rental_ready_checklist_templates`, `rental_ready_checklist_template_questions`, `customer_admin_categories`, `customer_admin_questions`, `customer_admin_question_answers`, `customer_admin_templates`, `customer_admin_template_questions`, `equipment_rental_ready_templates`, `equipment_rental_ready_checklist_questions`, `equipment_rental_ready_checklist_question_logs`, `equipment_status_logs`, `order_product_checklist_questions`, `order_product_checklist_question_answers` — plus `order_products.dispatch_checklist` (JSON) and 16 `delivery_/pickup_*` columns.

### Relationship diagram
```
product_categories
  |--(nullOnDelete)--> checklist_masters.equipment_category_id
  |--(nullOnDelete)--> rental_ready_checklist_templates.equipment_category_id
  |--(NO FK, plain string — unenforced)--> customer_admin_templates.equipment_category_id

checklist_masters  ["hub" — no content of its own]
  --> rental_ready_checklist_templates (rental_ready_template_id, nullOnDelete)
  --> customer_admin_templates (customer_admin_template_id, nullOnDelete)
  <-- equipment.checklist_master_id (set null on delete)

rental_ready_checklist_templates --(cascade)--> rental_ready_checklist_template_questions --(cascade)--> rental_ready_checklist_questions
rental_ready_checklist_categories --(cascade)--> rental_ready_checklist_questions --(cascade)--> rental_ready_checklist_question_answers

customer_admin_templates --(cascade)--> customer_admin_template_questions --(cascade)--> customer_admin_questions
customer_admin_categories --(cascade)--> customer_admin_questions --(cascade)--> customer_admin_question_answers

equipment --(cascadeOnDelete)--> equipment_rental_ready_templates (one inspection instance)
  --(nullOnDelete)--> equipment_rental_ready_checklist_questions --(nullOnDelete)--> rental_ready_checklist_questions/answers (JSON snapshot: rental_ready_qa_json)
  --(nullOnDelete)--> equipment_rental_ready_checklist_question_logs (append-only audit JSON: rental_ready_all_qa_json)

orders/order_products --(cascade)--> order_product_checklist_questions --(cascade)--> order_product_checklist_question_answers
  (question_id/answer_id FKs to customer_admin_questions/answers are `set null`, but those source tables have NO soft deletes)

order_products (independent, schema-disconnected 3rd mechanism)
  -- dispatch_checklist (JSON, no shape enforcement)
  -- delivery_/pickup_ {tnc,drivers_license,video,checklist}_status (free strings, no enum, no FK)
```

### Key schema risks
- **No FK constraint at all** on `customer_admin_templates.equipment_category_id` (plain string) — inconsistent with its Rental Ready sibling, which is a real FK.
- **Hard-delete vs. soft-delete asymmetry**: the entire `customer_admin_*` tree has no soft deletes; the entire `rental_ready_*` tree does. Deleting a customer-admin question/answer is unrecoverable and hard-cascades.
- **Universal `nullOnDelete()`/`set null`** on nearly every checklist FK (a deliberate design per migration comments) means deleted parents silently orphan children rather than blocking/cascading — a `checklist_masters` row can end up with all three FKs null.
- **No composite unique constraint** on any template↔question join table (`rental_ready_checklist_template_questions`, `customer_admin_template_questions`) — duplicate question-in-template rows are not prevented at the DB layer.
- **No PHP-backed enum classes** anywhere in this domain (only `App\Enums\UserPayType` exists repo-wide) — all "status"/"type" columns are raw DB enums or free strings, validity enforced only where application code happens to check it.
- `order_products`'s `dispatch_checklist` (JSON) and `*_status` columns are schema-disconnected from every other checklist table.

---

## 5. Master Checklist System Findings

- `ChecklistMaster` (table `checklist_masters`) is **a routing/composition record, not a content repository.** It stores exactly one `rental_ready_template_id`, one `customer_admin_template_id`, and one `equipment_category_id`. It holds zero questions/answers of its own.
- Templates are **not versioned or copy-on-write** — `rental_ready_checklist_templates` and `customer_admin_templates` rows are edited in place. A `ChecklistMaster` row is a live pointer, so editing which template it points to takes effect immediately for every equipment assigned to it (correct, intended behavior — not a bug).
- **Templates are manually assigned, not automatically resolved** by product/category/equipment-type. `equipment_category_id` exists on `ChecklistMaster`, `RentalReadyChecklistTemplate`, and (unconstrained) `CustomerAdminTemplate`, but it is used only for UI filtering/labeling — never as an automatic lookup key. No code path matches `equipment.product_category_id` to `ChecklistMaster.equipment_category_id` automatically.
- **No validation that a `ChecklistMaster`'s selected templates match its own category** — the create/edit forms list *all* active templates regardless of category, so an admin can legally pair mismatched categories.
- `ChecklistMaster::delete()` is a soft delete that leaves `equipment.checklist_master_id` pointing at a now-trashed master with no cleanup.
- `ChecklistMaster\CopyController` duplicates the master's four fields but does not duplicate equipment assignments (correct/expected) or the underlying templates (also correct — copy is of the pointer, not the content).

---

## 6. Checklist Template Structure Findings

- Both content trees (Rental Ready, Customer Admin) have the identical logical shape: Category → Question → Answer, plus a Template → TemplateQuestion join with `index_number` ordering — but are **implemented as two separate table/model/controller families with zero code sharing.**
- **Ordering is preserved** via `index_number` on both `*_question_answers` and `*_template_questions`, consistently used in `orderBy` clauses across both trees.
- **Active/inactive** is a boolean (`active_template`) on both template tables; **required** is a boolean (`required_question`) on both question tables.
- **Media/signature/video/driver's-license requirements are not modeled in the template schema at all** — there is no "this template requires a signature" or "this question requires a photo" flag anywhere in `rental_ready_*` or `customer_admin_*` tables. Signature/video/license handling is entirely a runtime, order-level concept (see §8), disconnected from template definitions.
- **Historical customer checklists are protected from master template edits** via denormalization: `SaveDeliveryController` copies question/answer text onto `order_product_checklist_questions`/`_answers` at delivery time. Editing a master `CustomerAdminQuestion` afterward does not change historical order data (confirmed in code, §8). One exception: `is_damaged` is read live at return time, not snapshotted (see §8).
- **Rental Ready inspection history** is protected similarly via `EquipmentRentalReadyChecklistQuestion.rental_ready_qa_json` (per-answer snapshot) and `EquipmentRentalReadyChecklistQuestionLog.rental_ready_all_qa_json` (per-submission append-only audit log).

---

## 7. Rental Ready Connection Findings

This was the specific focus requested given the prior Rental Ready audit's findings.

- **`ChecklistMaster` connects to Rental Ready via a live FK (`rental_ready_template_id`), not a copy.** There is exactly one canonical Rental Ready question/answer bank; `ChecklistMaster` never duplicates its content.
- **Editing a Rental Ready template's content is immediately visible everywhere it's referenced** — no propagation step exists or is needed, because there is nothing to keep in sync (aside from the per-inspection JSON snapshots, which are deliberately frozen).
- **Editing a `ChecklistMaster` row only changes which template it points to** — it never writes into the Rental Ready content tables. This separation is correct and matches the "assignment vs. content" distinction the system is designed around.
- **No automatic category/machine-type-based resolution exists.** Equipment-to-template assignment is fully manual and per-equipment (`equipment.checklist_master_id`).
- **Three uncoordinated write paths** set `equipment.checklist_master_id` with no shared service layer (see §10, Risk #1) — this is a Master-Checklist/assignment problem, not a Rental Ready content problem.
- **Rental Ready status computation and the Customer/Order checklist do not duplicate logic against each other** — Rental Ready has its own completion/percentage math (`SaveController.php:135-195`); the Customer/Order checklist flow has no equivalent calculator at all. They diverge in capability rather than duplicating the same algorithm.
- **The Rental Ready system itself has an internal duplication risk** (not a Rental Ready↔Master-Checklist issue): the mobile API path (`RentalReadyChecklists\SaveController`) *computes* completion counts from submitted answers (server-trusted), while the parallel admin-web path (`EquipmentManagement\StoreController`) *trusts client-submitted counts verbatim* and derives status from a raw `equipment_status` request field via a different code path entirely. These two flows could disagree about status for the same underlying answers.
- **No dead/orphaned code** was found in the Rental Ready↔ChecklistMaster connection itself; the only literal dead code is a commented-out duplicate `customerAdminTemplate()` relationship method in `ChecklistMaster.php`.

---

## 8. Customer Checklist Workflow Findings

- **Delivery flow (`SaveDeliveryController`)**: resolves the equipment's live master template, wipes any existing `order_product_checklist_questions` for the order product (to clear stale rows from a prior equipment reassignment — a recent, deliberate fix per commit `7b3462ba`), then snapshots every question and **every answer option** (not just the selected one) onto order-level rows. Sets `delivery_status = 'Completed'` **unconditionally**.
- **Return flow (`SaveReturnController`)**: reuses the delivery-time snapshot (does not re-read the master template), idempotently resets-then-sets `is_return_answer` flags (update, not insert — no duplicate rows), computes damage via `CustomerAdminQuestionAnswer.is_damaged` (read **live**, not snapshotted), and sets `pickup_status = 'Completed'` **unconditionally**. Has the only duplicate-submission guard in the whole flow: 409 if a return signature already exists.
- **Signature flow**: inline `signature_media` file in the same request as the checklist save; **`nullable`** in both delivery and return FormRequests — a checklist can be marked `Completed` with zero signature.
- **Driver's license flow**: entirely separate — uploaded via `UploadMediaController` (type=`license`), decoupled from checklist save. No cross-check anywhere that a license was actually uploaded before any "license status" can be set.
- **Delivery/return video flow**: also via `UploadMediaController` (mp4/mov/avi extensions permitted for the same `delivery`/`pickup` media types as photos — video and photo share a type, distinguished only by file extension). Decoupled from checklist save.
- **Completion status logic — the central finding**: `delivery_status`/`pickup_status = 'Completed'` is a hardcoded, unconditional literal set on every successful call, with **no check that required questions were answered, a signature exists, or license/video exist.** The one endpoint that would represent granular completion (`UpdateDeliveryPickupInputsController`, driving `tnc_status`/`drivers_license_status`/`video_status`/`checklist_status`) accepts **arbitrary unvalidated strings** with zero enum constraint and zero cross-check against real license/video/checklist records.
- **Partial completion**: `checklist` is `nullable|array` on both delivery and return saves — a checklist can be saved with no questions answered at all, and completion is still granted.
- **Save/update/delete behavior**: delivery is a destructive delete-then-recreate (not `updateOrCreate`); return is an idempotent in-place update. `RemoveController` only reverts delivery-side fields, leaving `pickup_*`/`is_returned` untouched if a product was already returned — an inconsistent partial revert.
- **Two different "delivered" concepts** coexist with different gating and no cross-validation: `OrderProduct.is_delivered` (checklist-completion semantics) vs. `OrderProduct.delivery_is_delivered` (driver-arrival telemetry, set merely by a status enum transition in `DriverChecklistController`).
- **No re-submission guard on delivery** — repeated delivery saves fully wipe and rebuild the checklist snapshot, which is destructive if a return already happened in between.
- **Offline/mobile sync**: no idempotency key, client UUID, or conflict-resolution timestamp anywhere in the checklist submission path. `prepareForValidation()` in both save requests contains defensive parsing for malformed JSON from "weird iOS" clients — evidence of a flaky mobile client with no formal sync contract.

---

## 9. Admin UI Findings

Stack: pure Blade + Alpine.js/jQuery/SortableJS/Parsley — **no Vue/React/Inertia** for this module.

Four separate sidebar entries confirm four distinct screens: **Rental Ready Mgt.** (operational inspection tool), **Checklist Master** (composition wizard), **Rental Ready Admin** (template/question/category editor), **Customer Admin** (a structurally identical but independently-coded clone of Rental Ready Admin).

- All three specifically-named backend controllers (`AssignChecklistController`, `ChecklistQuestionsController`, `AssignChecklistMasterController`) **do have real, functioning UI entry points** — no dead backend among them.
- **One dead UI control**: a "Customer Admin" `<button>` in `checklist_master/create.blade.php` has no `href`/handler, unlike its correctly-wired sibling link.
- **Two fully orphaned mockup screens** with hardcoded fake data and non-functional buttons: `customer_admin/templates/index.blade.php` (routed, but never linked from nav) and `customer_admin/question_and_categories/index.blade.php` (its intended controller doesn't exist on disk, and the route file that would register it is never `require`d — confirmed absent from `php artisan route:list`).
- A **symmetric dead backend** exists on the Rental Ready side: `RentalReady\QuestionAndCategories\IndexController` exists on disk but has no registered route and no view.
- **Edit-mode validation bypass**: the Checklist Master wizard force-enables Step 3's "Continue" button in edit mode regardless of selection state (low risk — server-side `required` still applies).
- **Client-side gap**: the Rental Ready/Customer Admin template builder's hidden `questions` input has no "must not be empty" client check; an empty-template submit only fails server-side with no inline warning.
- **A second, independent research pass confirmed and extended the above with concrete bugs**, verified against `php artisan route:list`:
  - `ChecklistMaster\CopyController` and its route are fully functional server-side, but the only UI trigger (a Copy button in `checklist_master/partials/_table.blade.php`) is **HTML-commented-out** — unlike the equivalent, live Copy buttons on the Rental Ready and Customer Admin template/question tabs.
  - `admin.checklist-management.rental-ready.templates.index` is registered with no blade link anywhere and its target view **does not exist on disk** (would throw `ViewNotFoundException` if ever hit).
  - `admin.checklist-management.customer-admin.templates.index` is registered and its view exists, but is unlinked from any nav and is the same static hardcoded mockup noted above.
  - **Debug code left in a production path**: `CustomerAdmin\Question\StoreController`'s catch block calls `dd($e->getMessage(), $e->getTraceAsString())` — a failed question save halts the request and dumps a raw debug page to the admin user, instead of the graceful flash-error redirect every sibling controller uses.
  - **Confirmed data-integrity bug**: `EquipmentManagement\StoreController`'s "create new template" branch reads `$request->input('insepectorSlect')` (typo — should be `inspectorSelect`), silently saving `employee_id = null` on every first-time equipment inspection even though `employee_name` is captured correctly nearby. The update/existing-record path uses the correct key, so this only affects new inspections.
  - Route-parameter naming is inconsistent (functionally harmless): `rental-ready.questions.update`/`.copy` and `customer-admin.questions.update`/`.copy` declare a `{unique_id}` parameter but the Blade forms actually pass the numeric `id`; it only "works" because the controllers do `find($id)` rather than `where('unique_id', ...)`.
  - Leftover dead scaffolding in the sidebar itself: an unused `$customerChecklistActive` variable pointing at a placeholder route `admin.checklist-management.customer_checklist.*`, with a comment "Add the correct route for customer checklist when ready" — evidence of a fourth checklist concept that was never built.
  - The two mobile-facing list endpoints (`Api\Admin\V1\CustomerChecklists\IndexController`, `Api\Admin\V1\RentalReadyChecklists\IndexController`) have zero references anywhere in `resources/views` — confirming they exist solely for the mobile/external client, not for any admin screen.

---

## 10. API Endpoint Findings

9 mobile-facing checklist/delivery-workflow routes exist, all under `routes/api/admin/v1/**`, all POST, all gated by `auth:api_user`. Admin-web session routes (~20+) manage only templates/questions and have no functional overlap with the mobile endpoints.

| # | Route | Purpose |
|---|---|---|
| 1 | `POST customer-checklists/question-answers` | List delivery/return questions |
| 2 | `POST rental-ready-checklists/` | List rental-ready questions |
| 3 | `POST orders/customer-checklists/remove` | Revert delivery state |
| 4 | `POST orders/customer-checklists/save-delivery` | Save delivery checklist |
| 5 | `POST orders/customer-checklists/save-return` | Save return checklist |
| 6 | `POST orders/rental-ready-checklists/save-rental-ready` | Save equipment inspection |
| 7 | `POST orders/schedules/driver-checklist` | Driver fuel/key/status fields |
| 8 | `POST orders/schedules/update-delivery-pickup-inputs` | Granular status flags (unvalidated) |
| 9 | `POST orders/upload-media` | License/video/signature file upload |

- **No orphaned routes or controller@action mismatches** — every route resolves to a real method.
- **Two commented-out validation guards found in live, routed controllers**: a required-questions-answered check in `RentalReadyChecklists/SaveController.php` (lines 161-173), and an invalid-equipment-status guard in `RentalReadyChecklists/IndexController.php` (lines 33-42). Both are currently disabled.
- **Response-shape inconsistencies** between the two "question list" resources: `required_question` defaults to `true` in the customer resource but `false` in the rental-ready resource (opposite fallback for the same field name); `selected_answer` is a nested object in one and a bare string in the other; and the rental-ready resource's `unique_id` JSON key is populated from the record's numeric `id`, not an actual unique-id string — a likely bug that would break any client expecting a stable string identifier there.
- **Inconsistent success/error envelopes**: most endpoints use `{success, message}`; `DriverChecklistController` uniquely uses `{status, message}` with hardcoded English strings instead of the shared `ApiResponseHelper`/translation pattern.
- **Validation verdict**: required questions, signature, video, and driver's license are **not enforced server-side** anywhere in this API surface — only file MIME/extension whitelisting and `exists:` FK checks are genuinely enforced.
- **Conceptual fragmentation, not duplication**: the "delivery step" is split across four uncoordinated endpoints (save-delivery, driver-checklist, update-delivery-pickup-inputs, upload-media) with no server-side reconciliation between them.

---

## 11. Mobile / Offline Workflow Findings

- Checklist submission is **one multipart POST per checklist** (full Q&A array + signature file together) — not per-question calls.
- **No `DB::transaction`** wraps checklist-save + signature-upload + order-product-update in either save controller — a crash/timeout mid-request can leave answers persisted but the order product never marked delivered/returned.
- **No idempotency mechanism** (no client UUID, no client timestamp) anywhere in the checklist submission path. Delivery uses destructive delete-then-recreate (effectively idempotent for row state, but re-uploads the signature and re-fires side effects on retry); return has an explicit 409 guard but only for signature-already-present.
- **No template versioning/snapshot field** exists anywhere in the schema (confirmed via full grep). The system instead denormalizes current template content at submission time, which safely absorbs reordering/text edits — but a stale reference to a **deleted** question/answer triggers a hard 422 rejecting the entire batch, not a graceful partial-accept.
- License images and delivery/return videos go through a separate `UploadMediaController` endpoint, decoupled from checklist-save — this reduces blast radius, but signature capture is inline inside the checklist-save request itself, coupling that specific failure mode to checklist completion.
- **Unconfirmed, needs runtime testing**: exact 422 response shape for stale question/answer references; whether `markRented()`/event listeners double-fire safely on naive retry; behavior of `MediaHelper::uploadStorageFile()` on partial disk/DB failure; real mobile-app call sequencing between video upload and checklist-save; mobile-side offline-queue/replay/dedup behavior (entirely client-side, invisible from this repo); upload size/timeout limits under real network conditions.

---

## 12. Status and Completion Logic Findings

| Concern | Enforced server-side? |
|---|---|
| Checklist questions present at all | Partial — required for Rental Ready (`checklist => required\|array`); **not required** for delivery/return (`nullable\|array`) |
| Every required question answered | **No** — the only check is commented out |
| Signature image required | **No** — `nullable\|image` on both save endpoints |
| Video required/verified | **No** — free-text self-reported status only |
| Driver's license required/verified | **No** — free-text self-reported status only; upload endpoint validates file type but nothing requires a file be sent |

- `delivery_status`/`pickup_status = 'Completed'` is a hardcoded literal, unconditionally set.
- The four granular status fields (`tnc_status`, `drivers_license_status`, `video_status`, `checklist_status`) are **currently write-only** — grepped across `app/`, nothing else reads them yet. This means today's lack of validation has no live downstream effect, but it will become a real integrity problem the moment anything (a dashboard, a gating rule) starts trusting them.
- Rental-ready "completion" is represented purely by `equipment.current_status = 'available'` plus the latest `EquipmentRentalReadyTemplate.status`/`is_complete` — no dedicated `is_rental_ready` boolean exists.
- Consistency across admin/API/mobile: **not consistent** — the admin-web Rental Ready save path trusts client-submitted completion counts, while the mobile API path recomputes them from validated answers (see §7).

---

## 13. Data Integrity Risks

1. **No versioning/copy-on-write for templates** — templates are mutated in place; the only protection for historical data is denormalization/snapshotting, which is inconsistently applied (`is_damaged` is the one confirmed live-read exception).
2. **Hard-delete vs. soft-delete asymmetry** between `customer_admin_*` (no soft deletes) and `rental_ready_*` (soft deletes) — deleting a customer-admin question is unrecoverable and can null out historical order-checklist FK links (though denormalized text is retained).
3. **Universal `nullOnDelete()`** on checklist FKs (by design) means deleted parents silently orphan children rather than blocking — `checklist_masters` rows, inspection records, and order-checklist rows can all end up detached from their real templates/questions/answers with only denormalized text (where present) as a hint.
4. **`customer_admin_templates.equipment_category_id`** has zero FK constraint (bare string) — inconsistent with its Rental Ready sibling and effectively unenforceable at the DB layer.
5. **`equipment.checklist_master_id`** is written from three independent, uncoordinated code paths with no shared service layer — the ChecklistMaster edit screen's bulk unassign-then-reassign step is the most dangerous, as it can silently mass-unassign equipment set via the other two paths with no confirmation or audit trail.
6. **No composite unique constraint** on template↔question join tables — duplicate question-in-template rows are possible at the DB level.
7. **Client-writable, unvalidated status strings** (`tnc_status`, `drivers_license_status`, `video_status`, `checklist_status`) with zero enum constraint and zero cross-check against real records — currently inert (write-only) but a latent risk.

---

## 14. Dead Code / Duplicate Logic / Disconnected Logic

**Dead code confirmed:**
- `RentalReady\QuestionAndCategories\IndexController` — exists on disk, no route, no view.
- `CustomerAdmin\QuestionAndCategories\IndexController` — referenced by a route file, but the controller **does not exist on disk**, and the route file is never `require`d.
- `customer_admin/templates/index.blade.php` — routed, but a static hardcoded mockup with non-functional buttons, never linked from any nav.
- Commented-out duplicate `customerAdminTemplate()` method in `ChecklistMaster.php`.
- Commented-out required-questions-answered check in `RentalReadyChecklists/SaveController.php`.
- Commented-out invalid-equipment-status guard in `RentalReadyChecklists/IndexController.php`.
- Dead `prepareForValidation` scaffolding in `SaveDeliveryRequest.php`.
- Dead "Customer Admin" button (no handler) in `checklist_master/create.blade.php`.
- `ChecklistMaster`'s Copy button is HTML-commented-out in `_table.blade.php` even though the route/controller work.
- `admin.checklist-management.rental-ready.templates.index` route points at a view file that doesn't exist on disk.
- A leftover `dd()` debug call in `CustomerAdmin\Question\StoreController`'s error handler.
- An unused `$customerChecklistActive`/placeholder route reference left in the sidebar partial for a checklist concept that was never built.

**Confirmed bug (not just dead code):**
- `EquipmentManagement\StoreController` reads a typo'd request key (`insepectorSlect` instead of `inspectorSelect`) on the "new inspection" branch, silently nulling `employee_id` on every first-time equipment inspection submitted through the admin UI.

**Duplicate logic confirmed:**
- Rental Ready and Customer Admin CRUD stacks (categories/questions/templates) are near-line-for-line duplicated controller logic operating on separate models — any future fix must be applied twice.
- Two divergent implementations compute Rental Ready completion counts/status: one server-trusted (mobile API `SaveController`), one client-trusted (admin web `EquipmentManagement\StoreController`).
- Three separate code paths write `equipment.checklist_master_id` with duplicated raw assignment logic instead of a shared service method.

**Disconnected logic confirmed:**
- The `dispatch_checklist`/`delivery_*_status` mechanism on `order_products` is schema- and logic-disconnected from both checklist content trees.
- The one-directional "create matching folder" sync checkbox between Rental Ready and Customer Admin categories fires only at creation time — renames/deletes never propagate, so matching categories will drift.

---

## 15. Confirmed Working Areas

- Historical order checklist snapshotting at delivery time correctly insulates past orders from later master-template text edits (with the one `is_damaged` exception noted).
- Return-answer updates are properly idempotent (reset-then-set, not insert).
- Billing-related charges (fuel, damage) use explicit or internally-managed idempotency keys.
- File-type validation (MIME/extension whitelisting) on `UploadMediaController` is real and server-enforced.
- The Rental Ready ↔ `ChecklistMaster` connection is a single, live, non-duplicated FK relationship — no drift risk there.
- The single-equipment `AssignChecklistMasterController` path has a genuine duplicate-assignment guard.
- All three named, specifically-audited admin controllers (`AssignChecklistController`, `ChecklistQuestionsController`, `AssignChecklistMasterController`) have real, functioning UI entry points.
- No orphaned mobile API routes or controller@action mismatches were found anywhere in the checklist API surface.

---

## 16. Confirmed Broken or Risky Areas

- Server-side completion enforcement for delivery/return checklists is effectively absent (§8, §12).
- Client-writable, unvalidated status fields with no cross-check against real license/video/checklist state (§8, §12, §13).
- Three uncoordinated equipment-assignment write paths, one of which is destructively bulk-reassigning with no guard (§10 of the admin audit, summarized in §13 #5).
- Two commented-out server-side validation guards in live, routed controllers (§10, §14).
- Rental Ready and Customer Admin trees are independently editable and already schema-diverged, with no ongoing sync (§9, §14).
- Response-shape inconsistencies between similar API resources, including a likely bug where `unique_id` is populated from a numeric `id` (§10).
- No transactional safety around checklist-save + file-upload + order-update — partial failure leaves inconsistent state (§11).
- Two orphaned/dead admin screens with non-functional controls, one referencing a non-existent controller (§9, §14).

---

## 17. Unknowns / Areas Requiring Runtime Validation

The following could not be confirmed from static code alone and require live/staging verification:

1. Exact 422 response payload shape when a mobile client submits a stale/deleted question or answer reference, and whether the mobile app can recover gracefully.
2. Whether `EquipmentStatusService::markRented()` and `OrderCustomerChecklistEvent` listeners are safe against a naive client retry of `save-delivery` (no 409 guard exists there, unlike `save-return`).
3. Actual behavior of `MediaHelper::uploadStorageFile()` under partial failure (disk write succeeds, DB row fails, or vice versa) — orphan risk not verifiable statically.
4. Real mobile-app call sequencing between video upload and checklist-save (the mobile client source is not in this repository).
5. Mobile app's offline-queue/replay/dedup behavior — entirely client-side, invisible from this backend.
6. Upload size/timeout limits (`php.ini`, web-server config) for large video uploads over throttled connections.
7. Whether any current production data already has `checklist_masters` rows with all three FKs nulled out (orphaned by a parent delete) — would require a live database query, not performed in this audit.
8. Whether `RentalReadyChecklistTemplate`/`CustomerAdminTemplate` category-name pairs created via the one-time sync checkbox have already drifted apart in production data.

---

## 18. Recommended Fix Phases (for planning only — no fixes made in this audit)

**Phase 1 — Critical, no schema changes required:**
- Add server-side enforcement of required-question coverage, signature presence, and (where applicable) license/video presence before setting `delivery_status`/`pickup_status = 'Completed'`.
- Re-enable or explicitly remove the two commented-out validation guards in `RentalReadyChecklists/SaveController.php` and `IndexController.php`.
- Add `Rule::in()` constraints to `tnc_status`/`drivers_license_status`/`video_status`/`checklist_status` in `UpdateDeliveryPickupInputsRequest`, since they are currently write-only and safe to tighten without breaking any reader.

**Phase 2 — High, consolidate duplicated logic:**
- Introduce a single service method for equipment↔checklist-master assignment and route all three write paths through it.
- Reconcile the two divergent Rental Ready completion-calculation implementations (mobile API vs. admin web) into one shared, server-trusted calculation.
- Fix the `unique_id`/`id` mismatch in `RentalReadyChecklistQuestions\ListResource`.

**Phase 3 — Medium:**
- Decide the fate of the two orphaned Customer Admin mockup screens and the two unrouted `QuestionAndCategories` controllers (delete or finish).
- Add a composite unique constraint on both template↔question join tables.
- Add a real FK constraint to `customer_admin_templates.equipment_category_id`.
- Standardize soft-delete behavior across `customer_admin_*` to match `rental_ready_*`, or document why the asymmetry is intentional.

**Phase 4 — Low:**
- Remove dead/commented-out code (duplicate `customerAdminTemplate()` method, dead UI button, dead `prepareForValidation` scaffolding).
- Standardize API response envelopes (`success` vs `status`) across all checklist endpoints.
- Add category-consistency validation between a `ChecklistMaster` and its selected templates.

---

## 19. Priority Ranking

| Priority | Item |
|---|---|
| **Critical** | No server-side enforcement of checklist completion (required questions, signature, license, video) before marking delivery/return "Completed" |
| **Critical** | Unvalidated, client-writable status fields with no cross-check against real records |
| **High** | Three uncoordinated equipment-assignment write paths, one destructive with no guard |
| **High** | Two commented-out validation guards left active in shipped code |
| **High** | No transactional safety around checklist-save + file-upload + order-update |
| **Medium** | Rental Ready vs. Customer Admin schema/logic drift (no soft-delete parity, no sync beyond category creation) |
| **Medium** | Two divergent Rental Ready completion-calculation implementations |
| **Medium** | API response-shape inconsistencies, including likely `unique_id`/`id` bug |
| **Low** | Orphaned mockup screens and unrouted controllers |
| **Low** | Miscellaneous dead/commented-out code |

---

## 20. Final Conclusion

The Master Checklist system itself — the `ChecklistMaster` hub and its connection to Rental Ready — is architecturally sound: it is a thin, correctly-designed composition layer with one true content source per tree and no drift risk in that specific relationship, directly addressing the concern that prompted this audit. The real risk in this system lies elsewhere: in the **unenforced completion/status logic** across the customer delivery/return workflow, in the **duplicated and already-diverging Rental Ready/Customer Admin content trees**, and in **uncoordinated write paths** for equipment-checklist assignment. None of these are currently causing visible production incidents (the riskiest field set is write-only today), but each is a live latent risk that will surface the moment a new feature starts trusting data this system does not actually guarantee.

## Classification: **B — Functional but with important risks**

The system works for its primary path (delivery/return checklists are captured, snapshotted correctly, and billing is idempotent), but has multiple confirmed, unenforced completion-integrity gaps, real architectural duplication between two checklist trees, and uncoordinated assignment logic — all fixable without a rewrite, but requiring a deliberate correction phase before the system can be trusted as a single source of truth for checklist completion.

---

## Appendix: Verification Record

- **Files inspected:** see §3 (full list of migrations, models, controllers, requests, resources, enums, events/listeners, views).
- **Routes/endpoints found:** 9 mobile API routes (§10), ~30+ admin-web routes across ChecklistMaster/RentalReady/CustomerAdmin/EquipmentManagement/Equipment-assignment (full route tables preserved in the underlying research transcripts).
- **Tables found:** 17 checklist-specific tables plus checklist-related columns on `order_products` (§4).
- **Commands run:** `php artisan route:list` (read-only route introspection, used to confirm two routes are unregistered) — no other commands executed. No migrations run, no database writes, no artisan commands that mutate state.
- **Code modified:** **No.** This was a strictly read-only audit; zero files were changed.
