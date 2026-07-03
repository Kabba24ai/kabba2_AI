# Checklist System Audit — Phase 2: Dependency, Call-Graph & Runtime Validation Addendum

**Audit date:** 2026-07-03
**Type:** Read-only follow-up to `CHECKLIST_SYSTEM_AUDIT.md` (Phase 1), specifically covering: route caller mapping, end-to-end call graphs, model dependency/blast-radius mapping, event/listener/job chain tracing, hidden-usage discovery, a business-rule enforcement matrix, and a runtime validation test plan.
**Code modified:** **No.**

This addendum exists because Phase 1 correctly identified architecture and risk *categories* but did not answer "who actually calls this," "what exactly executes as a consequence," or "what breaks if this changes." Phase 2 answers those questions with file:line evidence.

---

## 1. Hidden Usage — Couplings Phase 1 Could Not See From Controllers Alone

A full sweep of `app/Jobs`, `app/Observers`, `app/Policies`, `app/Traits`, `app/Notifications`, `app/Exports`, `app/Imports`, `database/seeders`, `app/Console`, and `routes/console.php` found the following. `Policies`, `Notifications`, `Exports`, and `Imports` are empty/irrelevant to this domain — everything below is real.

1. **`Equipment.php:277-282`** — a `hasOneThrough`-style relation (`customerAdminTemplates()`) chaining `Equipment → ChecklistMaster → CustomerAdminTemplate`, hard-wired to the raw column names `checklist_master_id` and `customer_admin_template_id` rather than composed from the `checklistMaster()` relation. **A rename of either column fails silently at query time, not at code-review time.**
2. **`dispatch_checklist`** (JSON on `order_products`) is a **completely separate "checklist" concept** owned entirely by `Admin\OrderManagement\Dispatch\ShowController.php` — a driver pre-delivery SOP checklist with zero relation to `ChecklistMaster`/templates. Same table, same English word, unrelated system — a real naming-collision risk for future maintainers grepping for "checklist."
3. **Four scheduled reminder jobs** (`SendDelivery/ReturnDayBefore/SameDayRentalReminderJob`, registered in `routes/console.php`) and **two `SalesFunnel*EventJob`s** filter on `delivery_status`/`pickup_status = 'Pending'` — fields the checklist Save controllers mutate. **Cron-driven SMS/email automation is implicitly checklist-dependent**, even though nothing in the checklist code base references these jobs.
4. **`app\Services\DispatchAI\DispatchContextBuilder.php`** — the AI dispatch-drafting feature builds its planning context from `delivery_status`/`pickup_status`, excluding "Completed" pickups. AI-drafted dispatch schedules are therefore implicitly checklist-dependent.
5. **Three independent write paths for `is_delivered`/`is_returned`**: the customer checklist Save controllers (expected), the separate driver `DriverChecklistController`/`UpdateDeliveryPickupInputsController` mobile flow, and plain admin schedule-editing controllers (`UpdateProductScheduleController`, `AssignEquipmentController`, `RemoveEquipmentController`) that **can flip delivered/returned state without any checklist ever being filled out** — a genuine three-way state-consistency risk.
6. **`EquipmentObserver`** (registered in `AppServiceProvider::boot()`) writes to the `equipment_status_logs` DB table on every `Equipment::current_status` change — **but never fires for any checklist-driven transition**, because `EquipmentStatusService` universally uses `saveQuietly()`, which suppresses Eloquent events. This is the single most consequential Phase 2 finding — see §5.
7. **`OrderProductObserver`**'s auto-assign-on-schedule logic is gated by `delivery_status`/`pickup_status !== 'Reschedule'` — another non-obvious coupling to checklist-mutated fields.
8. **`ChargeService`** and **`SalesTaxReportEngine`** explicitly trace checklist-originated damage/fuel charges into billing and tax reporting via a dedicated `source_module='mobile_checklist'` classification — confirming the checklist system is a **billing input pipeline**, not just an operational workflow (detailed in §4).
9. **No seeder** creates canonical `checklist_masters`/Rental-Ready/Customer-Admin template data anywhere in `database/seeders` — confirmed **no demo/test fixtures exist** for this system. Anyone writing automated tests must build their own fixtures from scratch.
10. **`delivery_checklist_status`/`pickup_checklist_status`** are fillable on `OrderProduct` but have **zero other read/write site anywhere in `app/`** — orphaned, likely vestigial columns.
11. **`app\Http\Controllers\Admin\Tests\IndexController.php`** — an oddly-namespaced controller (not under `ChecklistManagement` or `OrderManagement`) deep-eager-loads the entire checklist relation graph. Worth confirming this is not a forgotten debug route reachable in production.

---

## 2. Route Caller Map

| Route group | Caller | Confidence |
|---|---|---|
| 9 mobile `Api\Admin\V1` checklist routes (save-delivery, save-return, remove, save-rental-ready, driver-checklist, update-delivery-pickup-inputs, upload-media, + 2 list endpoints) | Mobile app only | **Presumed** — confirmed behind `auth:api_user`, zero references anywhere in `resources/views`; no mobile app source exists in this repo to directly confirm |
| All `checklist-management/{checklist-master,rental-ready,customer-admin,equipment-management}/*` CRUD routes (~25 routes) | Admin web UI (Blade/AJAX) | **Confirmed** — every action traced to an exact Blade file and line calling `route(...)` |
| `maintenance-management/equipment/checklist-master-assign` | Admin web UI (modal) | **Confirmed** — `equipment/index.blade.php:319` |
| `admin/order_management/dispatch/{unique_id}/checklist` (unrelated `dispatch_checklist` system) | Admin web UI (dispatch board) | Confirmed, per Phase 1 |

**No cron/scheduled task calls any checklist route or controller directly**, and **no internal Job/Service bypasses HTTP** to invoke checklist logic — all checklist reads/writes are routed exclusively through the documented HTTP endpoints (grepped `app/Jobs` and `app/Services` for zero hits).

**New dead-route finding beyond Phase 1**: the two `question_and_categories` routes aren't merely unlinked from UI (Phase 1's finding) — their **route files are never `require`d by the parent route group at all**, so they are not registered with the router in the first place. The only surface trace is a `Route::is()` sidebar active-state check for the rental-ready variant, referencing a route name that doesn't exist (harmless, since `Route::is()` doesn't throw, but confirms the feature was wired into the sidebar and then abandoned).

**Caveat for follow-up**: `order_management/schedule_assignment/partials/_table.blade_old.php` references `checklist-management.equipment-management.show`, but the `_old` suffix suggests this partial may itself be retired. Not verified whether it's still `@include`d anywhere — flagged for a future "which partials are actually live" pass.

---

## 3. End-to-End Call Graphs

### Workflow 1 — Delivery Checklist Submission
```
1. POST save-delivery -> SaveDeliveryController::__invoke() [SaveDeliveryController.php:28]
2. Load OrderProduct (+checklistQuestions.answers), Equipment (+checklistMaster.customerAdminTemplate...) [:33-51]
3. Guard: 409 if equipment already Rented [:63-71]
4. If checklist payload present:
   a. DELETE all existing OrderProductChecklistQuestion rows for this order product [:88]
   b. INSERT new OrderProductChecklistQuestion rows (one per template question) [:93-103]
   c. INSERT one OrderProductChecklistQuestionAnswers row per master answer option (selected + unselected) [:122-148]
5. Build $orderProductData: delivery_status='Completed' (unconditional), is_delivered=true, is_returned=false [:151-162]
6. If new/reassigned equipment: snapshot equipment_details, call EquipmentStatusService::markRented() [:175-180]
   -> sets equipment.current_status='rented' via saveQuietly() [EquipmentStatusService.php:42]
   -> saveQuietly() SUPPRESSES EquipmentObserver -> equipment_status_logs table NEVER gets this row
   -> only trace is a text line in the 'equipment_status' log channel
7. Optional signature upload -> delivery_signature_media_id [:183-188]
8. UPDATE OrderProduct with $orderProductData [:190] -- fires OrderProductObserver::updated() (auto-assign guard, normally no-op here)
9. DELETE OrderProduct->softAssignment() [:191]
10. FIRE OrderCustomerChecklistEvent('checklist_delivery') [:194-196]
11. OrderCustomerChecklistListener::handle() (auto-discovered, SYNCHRONOUS, not queued)
    -> INSERT OrderHistory row [Listener.php:38-45]
12. Return JSON success
```

### Workflow 2 — Return Checklist Submission
```
1. POST save-return -> SaveReturnController::__invoke() [:36]
2. Guard: error if equipment not currently Rented [:59-66]
3. Guard: 409 ChecklistAlreadySubmitted if returnSignatureMedia already set [:68-74]
4. If checklist payload present:
   a. Bulk-reset is_return_answer=false for all this order product's answers [:97-99] (idempotency guard)
   b. Per submitted answer: UPDATE OrderProductChecklistQuestionAnswers (is_return_answer=true, user_return_amount) [:108-112]
   c. Read live CustomerAdminQuestionAnswer.is_damaged to detect damage (NOT snapshotted) [:119-124]
   d. If damaged: sum user_return_amount -> BillingEngine::charge(mobileReturnDamage) [:132-139]
      -> idempotency key "mobile_checklist:{orderProductId}:damage" -- SCOPED TOO COARSELY (see §6, risk R-9)
      -> INSERT BillingCharge row, FIRE BillingChargeCreatedEvent -- NO LISTENER REGISTERED ANYWHERE (dead hook)
5. Build $orderProductData: pickup_status='Completed' (unconditional), is_returned=true [:158-171]
6. UPDATE OrderProduct [:184]
7. If fuel_total_charge > 0: ChargeService::createFromOrderProduct() -> legacy CustomerAccount row,
   then bridge into BillingEngine::charge() again with a SEPARATE idempotency key "mobile_return_fuel:{id}:{reading}" [:187-230]
   -- TWO independent, differently-keyed idempotency mechanisms coexist for the same fuel charge
8. EquipmentStatusService::markReturnedDamaged() or markReturnedToMaintenance() [:239-241]
   -> same saveQuietly() gap as Workflow 1 -- equipment_status_logs silently misses this transition too
9. FIRE OrderCustomerChecklistEvent('checklist_return') [:247-248] -> OrderHistory row
10. Return JSON success
```

### Workflow 3 — Rental Ready Inspection
```
1. POST save-rental-ready -> RentalReadyChecklists\SaveController::__invoke() [:30]
2. Guard: error if equipment currently Rented [:39-44]
3. Resolve questions from order-specific snapshot or fall back to checklistMaster->rentalReadyTemplate [:46-68]
4. Compute counts + status in-memory (Draft/Rental Ready/Damaged) -- $hasDamaged checked BEFORE $allRentalReady [:135-195]
5. UPSERT EquipmentRentalReadyTemplate + EquipmentRentalReadyChecklistQuestion rows [:197-267]
6. INSERT one consolidated EquipmentRentalReadyChecklistQuestionLog audit row [:270-278]
7. Branch: markDamagedFromRentalReady / markAvailableFromRentalReady / markMaintenanceFromRentalReady [:282-288]
   -> same saveQuietly() gap -- equipment_status_logs silently misses this transition too
8. Return JSON success. NO event fired -- unlike Workflows 1 & 2, there is NO order-history trail for a rental-ready inspection.
```

---

## 4. Model Dependency / Blast-Radius Map

```
ChecklistMaster (hub, no content of its own)
  ├── category() -> ProductCategory
  ├── rentalReadyTemplate() -> RentalReadyChecklistTemplate
  ├── customerAdminTemplate() -> CustomerAdminTemplate
  └── <- Equipment.checklist_master_id (belongsTo)
         └── Equipment.customerAdminTemplates() [hasOneThrough, hard-wired column names -- fragile]

If ChecklistMaster is deleted/renamed:
  BREAKS: ~12 Admin/ChecklistManagement controllers, Maintenance Management Equipment
          CRUD/Worksheet (dropdowns + exists: validation + 2 relations), Rental Ready
          equipment-management checklist builder, mobile Equipment API resource field.
  TRANSITIVELY BREAKS: CustomerAdminTemplate and RentalReadyChecklistTemplate become
          unreachable from Equipment (no other path exists).

If equipment.checklist_master_id is removed:
  BREAKS: Equipment::checklistMaster()/customerAdminTemplates() relations, Equipment
          Store/Update validation, Equipment Worksheet bulk-update, Equipment index filters,
          API Equipment ListResource field.
  DOES NOT BREAK: Orders/Billing/Reports -- the *runtime* order checklist
          (OrderProductChecklistQuestion) has NO FK to checklist_master_id at all; it's
          populated independently from CustomerAdminTemplate.templateQuestions at the moment
          equipment is assigned to an order product.

If order_products.dispatch_checklist JSON shape changes:
  BREAKS: only Admin\OrderManagement\Dispatch\ShowController.php + its one Blade view.
          Smallest blast radius of anything audited -- confirms it is a fully separate system.
```

**Most consequential finding in this section: checklist answers are a live billing input, not just an operational record.**
`SaveReturnController` converts `OrderProductChecklistQuestionAnswers.user_return_amount` directly into `damage_charge`/`fuel_total_charge` on `OrderProduct`, which `ChargeService`/`BillingEngine` post as real `CustomerAccount`/`BillingCharge` ledger entries, formally classified via `BillingSourceModule::MobileChecklist`. `SalesTaxReportEngine` explicitly accounts for `source_module='mobile_checklist'` charges in tax totals, and `NewDamageAlerts`/`FuelChargeAlerts` admin reports source their alert lists from these same checklist-driven charges. **Renaming or restructuring checklist-answer fields risks silently breaking real customer billing and tax reporting, not just a display screen.**

Other confirmed dependency risks:
- **Three unsynchronized `equipment_category_id` columns** exist on `ChecklistMaster`, `RentalReadyChecklistTemplate`, and `CustomerAdminTemplate` independently, with no DB constraint or app code forcing agreement.
- **`ChecklistMaster::delete()` does not null out `equipment.checklist_master_id` first** (unlike its own `UpdateController`, which does on category change) — a direct delete leaves equipment pointing at a soft-deleted/nonexistent master while still displaying as "has checklist" in index/worksheet filters.
- Fuel/damage charge amounts are **entirely client-supplied with no server-side recomputation** from the underlying meter readings — a buggy or malicious mobile client can set an arbitrary billed amount, and there is no enforced "staff review" gate despite a code comment implying one exists.

---

## 5. Event / Listener / Observer / Job Chains

- **No `EventServiceProvider` exists** — Laravel 11 auto-discovery wires every event to its listener purely by the listener's `handle()` type-hint. There is nothing to misconfigure, but also nothing centrally documenting the wiring.
- Only two checklist-specific events exist: `OrderCustomerChecklistEvent` (delivery/return/remove) and `OrderProductDriverChecklistUpdated` (driver ready-to-go/arrived). **No event exists for equipment status changes** — `EquipmentStatusService` mutates `current_status` via direct, synchronous method calls with no event dispatched at all.
- **Both checklist listeners are synchronous (not `ShouldQueue`)**, running inline before the HTTP response returns — despite `QUEUE_CONNECTION=database` being configured, **no job anywhere in the checklist system is ever queued.** Checklist submission is 100% synchronous, single-request, end-to-end.
- **Critical, independently-confirmed finding (found by two separate research agents)**: every one of `EquipmentStatusService`'s six status-transition methods calls `Equipment::saveQuietly()`, which suppresses Eloquent model events. This means **`EquipmentObserver` never fires** for any checklist-driven, delivery-driven, return-driven, or rental-ready-driven equipment status change — so the dedicated `equipment_status_logs` DB table is **silently never populated** by the mobile workflows that are this system's primary purpose. The service's own docblock claims transitions are "auditable," but the only actual audit trail is a text line in the `equipment_status` log **file** channel — a fundamentally different, harder-to-query, and easier-to-lose record than a database table built and migrated specifically for this purpose.
- **No transaction wraps any checklist controller.** Because listener execution happens synchronously and outside any transaction, if `OrderCustomerChecklistListener::handle()` throws (e.g., an invalid `OrderCustomerChecklistType::from()` enum value, or a DB error), the exception propagates into a 500 response **after** the `OrderProduct` update and `EquipmentStatusService` status change have already committed with no rollback path. The client sees total failure; the data change and equipment status change already happened; only the `OrderHistory` audit-log entry is silently missing.
- `DriverChecklistController` makes this worse: its entire body is wrapped in a bare `try/catch(\Throwable)` that returns a generic 500 **without logging the underlying exception at all** — a listener bug in this specific path would be completely invisible in production logs while still returning a false-negative failure to the mobile app, and `OrderProductDriverChecklistUpdatedListener` accesses `$data['order_product']['product_name']` without a null-safe guard, meaning a missing key is a concrete, reachable trigger for exactly this failure mode.
- `BillingChargeCreatedEvent` (fired inside `BillingEngine::charge()`) currently has **zero registered listeners anywhere** — a dispatch-and-forget hook with no present-day behavioral effect.

---

## 6. Business Rule Enforcement Matrix

| # | Rule | Enforced end-to-end? | Evidence |
|---|---|---|---|
| 1 | Signature required before delivery/return "Completed" | **NOT ENFORCED** | `signature_media` nullable on both save requests; status set unconditionally |
| 2 | Driver's license required before `drivers_license_status`="verified" | **NOT ENFORCED** | Free-text string, no cross-check against uploaded media |
| 3 | Video required before `video_status`="completed" | **NOT ENFORCED** | Same pattern as #2 |
| 4 | All required questions answered before checklist complete | **NOT ENFORCED** | Not checked at all for customer checklist; the one Rental Ready guard that would check this is commented out |
| 5 | No re-delivery without an intervening return | **NOT ENFORCED (gap)** | Delivery only blocks if the *same* equipment is currently rented — a second delivery call with *different* equipment succeeds and destructively rebuilds the checklist snapshot |
| 6 | No return before delivery | **ENFORCED** | Equipment must be `Rented`, which only `markRented()` (called only from delivery) sets |
| 7a | Equipment can't have two checklist masters at once | **ENFORCED (trivially)** | Single scalar FK column |
| 7b | `checklist_master_id` can't be silently cleared by an unrelated operation | **NOT ENFORCED — confirmed bug** | `ChecklistMaster\UpdateController` bulk-nulls **all** equipment currently pointing at a master before reassigning, regardless of which of the three assignment paths originally set it |
| 8 | ChecklistMaster's two templates must share its equipment category | **NOT ENFORCED** | No cross-validation exists in either FormRequest or controller |
| 9 | Damage charges applied once per damaged answer | **PARTIALLY ENFORCED — over-scoped** | Idempotency key is `order_product_id`-scoped only, with no return-cycle disambiguator; correctly blocks retries but would also wrongly suppress a legitimate second damage charge on a re-rented order product (a real scenario given rule #5's gap) |
| 10 | Rental-ready inspection can't be "Rental Ready" status if any answer is "Damaged" | **ENFORCED** | `$hasDamaged` is checked before `$allRentalReady` in the status derivation |

**8 of 10 audited rules are not enforced end-to-end.** Additional implicit rules discovered: fuel/total charge amounts are entirely client-trusted with no server recomputation from readings; damage charge amounts bill immediately at the client-submitted value despite a code comment implying a staff-review gate; there is no equipment-category vs. order-product-category cross-check at delivery time; and `RemoveController` only reverts delivery-side fields, leaving return-side state inconsistent if a return already occurred.

---

## 7. Runtime Validation Test Plan

The following scenarios cannot be confirmed from static code alone and should be executed against a staging environment before any correction phase begins.

**T1 — Template edit after delivery (snapshot integrity)**
1. Create a Customer Admin template with question Q1/answer A1.
2. Assign to equipment via a ChecklistMaster; deliver an order product with Q1/A1 selected.
3. Edit the master question's text and the answer's `is_damaged` flag.
4. Return the order product with the same answer selected.
Expected: delivered checklist text is unchanged (confirmed by Phase 1 static reading); **damage billing decision reflects the edited `is_damaged` value**, not the value at delivery time (confirmed live-read, not snapshotted) — verify this produces the expected/intended charge outcome, not an accidental one.

**T2 — Double delivery without return**
1. Deliver an order product with Equipment A.
2. Call `save-delivery` again for the same order product with Equipment B (not currently rented).
Expected per static analysis: succeeds, destructively deletes/rebuilds the checklist snapshot, re-runs `markRented`. Verify whether this is intended behavior (equipment reassignment) or an exploitable double-delivery gap, and what state the original Equipment A is left in.

**T3 — `equipment_status_logs` audit gap**
1. Deliver, then return, then run a rental-ready inspection on the same equipment.
2. Query `equipment_status_logs` for that equipment.
Expected per static analysis: **zero rows** from any of the three transitions (all use `saveQuietly()`). Confirm this against the `equipment_status` log channel to verify the divergence, and determine whether any admin screen currently reads `equipment_status_logs` and is silently showing incomplete history.

**T4 — Listener failure / non-atomicity**
1. Force `OrderCustomerChecklistListener` to throw (e.g., temporarily invalid history data) during a `save-delivery` call.
2. Observe the HTTP response and the resulting DB state.
Expected per static analysis: client receives a 500; `OrderProduct` and `Equipment` status changes are already committed; the `OrderHistory` row is missing. Confirms the "partial success reported as total failure" finding.

**T5 — Bulk unassign via ChecklistMaster edit**
1. Assign Equipment X to Checklist Master M via the single-equipment `AssignChecklistMasterController` modal.
2. Separately edit Checklist Master M through its own edit screen with a *different* equipment list that excludes X, with `assign_equipment` enabled.
3. Check Equipment X's `checklist_master_id`.
Expected per static analysis: Equipment X is silently unassigned with no warning. Confirm and assess real-world likelihood given actual admin workflows.

**T6 — Re-rental damage-charge idempotency**
1. Deliver, return with a damaged answer (charge created).
2. Re-deliver and re-return the same order product with a damaged answer again (a legitimate second rental cycle).
Expected per static analysis: the second damage charge is **silently suppressed** by the coarse idempotency key (`order_product_id:damage`, no cycle disambiguator). Confirm whether this actually happens and quantify potential revenue impact.

**T7 — Fuel/damage amount tampering**
1. Submit a return checklist with a `fuel_total_charge`/damage `user_return_amount` value inconsistent with the submitted fuel readings.
Expected per static analysis: the client-submitted amount is billed as-is with no server-side recomputation or sanity check. Confirm and assess whether any downstream reconciliation catches this.

**T8 — Category mismatch**
1. Create a ChecklistMaster with `equipment_category_id` = Category A but assign a `rental_ready_template_id` whose own `equipment_category_id` = Category B.
2. Assign equipment of Category A to this master and run an inspection.
Expected per static analysis: no rejection anywhere; the mismatched template is used without warning. Confirm actual behavior and whether this produces a nonsensical but "successful" inspection.

---

## 8. Risk Impact / Likelihood Ranking

| Risk | Impact | Likelihood | Recovery |
|---|---|---|---|
| No server-side completion enforcement (signature/required questions/license/video) | High (compliance/legal exposure on delivery/return records) | High (silent — no failure signal today) | Moderate — add validation without schema changes |
| `equipment_status_logs` silently never populated for checklist-driven transitions | Medium-High (any report/audit relying on this table is materially wrong) | Certain (100% of mobile-driven transitions, confirmed by two independent static passes) | Easy — remove `saveQuietly()` or dual-write |
| Checklist answers feed billing/tax directly with no server-side amount validation | High (real money — incorrect customer charges, incorrect tax totals) | Medium (requires a buggy/malicious client, but no safeguard exists) | Hard — requires new server-side recomputation logic |
| Bulk unassign in `ChecklistMaster\UpdateController` silently clears unrelated equipment assignments | Medium (equipment loses its checklist unexpectedly, discovered only when an inspection/delivery fails) | Medium (requires an admin editing a master with equipment assigned via a different path) | Easy — add a diff/confirmation step |
| Double-delivery gap (no 409 guard, unlike return) | Medium (destructively rebuilds an already-completed checklist) | Low-Medium (requires re-delivering with different equipment) | Easy — add the same guard `SaveReturnController` already has |
| Non-atomic checklist save + listener failure = partial success reported as total failure | Medium (silent audit-log gaps; client-visible 500s for otherwise-successful operations) | Medium (any listener exception, e.g. bad enum value or DB blip) | Moderate — wrap in `DB::transaction()` and/or catch-and-log around the event dispatch |
| Damage-charge idempotency key too coarse for re-rental cycles | Medium (silently suppresses a legitimate second charge — lost revenue) | Low (requires the same order product to be re-rented and re-damaged) | Easy — add a cycle/date component to the idempotency key |
| Three unsynchronized `equipment_category_id` columns / no cross-validation | Low-Medium (produces confusing but not immediately harmful mismatched assignments) | Medium (no guardrail stops an admin from doing this today) | Easy — add validation |
| Orphaned dead routes/controllers/mockup screens | Low (no functional risk, maintenance/confusion cost only) | N/A (already latent) | Trivial — delete or finish |
| `dispatch_checklist` naming collision with the real checklist system | Low (developer confusion risk, not a runtime bug) | N/A | Trivial — rename or document |

---

## 9. Refactor Readiness Verdict

**Can this system safely be refactored right now? No — not without first closing two specific gaps.**

The architecture itself (ChecklistMaster as a thin hub, snapshot-based historical protection, category-tree separation) is sound enough to refactor around. The two blockers are not architectural — they are missing safety nets that a refactor could easily make worse without anyone noticing:

1. **There is no automated test coverage and no seed data for this system** (confirmed in §1, item 9) — any refactor's correctness cannot be verified by existing tests, because none exist for this domain. Refactoring against zero regression coverage on a system that directly drives customer billing (§4) is the single biggest risk in this codebase for this module.
2. **The `equipment_status_logs` audit gap (§5) means there is currently no reliable ground truth** for what equipment status transitions actually happened historically. A refactor that touches `EquipmentStatusService` or the checklist controllers cannot be validated against this table today, because it has been silently incomplete since inception for every mobile-driven transition.

**Recommended order of operations before a correction/refactor phase:**
1. Fix the `saveQuietly()`/`EquipmentObserver` gap first (§5) — this is a small, isolated change that makes the audit trail trustworthy again, which every subsequent verification step depends on.
2. Write baseline regression tests/fixtures for the three core workflows (delivery, return, rental-ready inspection) using the call graphs in §3 as the test-case script — there is no seed data today, so this must be built from scratch.
3. Add the missing business-rule enforcement (§6) behind those new tests, one rule at a time, verifying against the runtime test plan in §7.
4. Only then proceed to the structural fixes already identified in Phase 1 (consolidating the three equipment-assignment write paths, reconciling the two Rental Ready completion-calculation implementations, etc.).

Skipping steps 1-2 and going straight to structural refactoring would mean refactoring a billing-adjacent system with no tests and a broken audit trail — exactly the condition most likely to produce an undetected regression.

---

## Appendix: Files Newly Read in Phase 2 (beyond Phase 1's file list)

```
app/Observers/EquipmentObserver.php
app/Observers/OrderProductObserver.php
app/Providers/AppServiceProvider.php (observer registration)
app/Services/Equipment/EquipmentStatusService.php (read in full)
app/Services/BillingEngine.php
app/Services/ChargeService.php
app/Http/DataObjects/BillingChargeRequest.php
app/Services/Reports/SalesTaxReportEngine.php
app/Services/DispatchAI/DispatchContextBuilder.php
app/Jobs/SendDelivery{DayBefore,SameDay}RentalReminderJob.php
app/Jobs/SendReturn{DayBefore,SameDay}RentalReminderJob.php
app/Jobs/SalesFunnel{Before,After}EventJob.php
app/Http/Controllers/Admin/OrderManagement/Dispatch/ShowController.php
app/Http/Controllers/Admin/OrderManagement/Orders/{AssignEquipment,RemoveEquipment,UpdateProductSchedule}Controller.php
app/Livewire/Dashboard/ScheduleSection.php
app/Http/Controllers/Admin/Tests/IndexController.php
database/seeders/Dev/DevDataSeeder.php
routes/console.php
Every checklist-related Blade partial re-verified for exact route() call sites (see §2)
```

No commands were run against a live database in this pass. No code was modified.
