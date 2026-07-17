# P3-2 / SEC-1 — Enum validation for delivery/pickup status fields

**Date:** 2026-07-15
**Scope:** Phase 3, Sprint 1, second item only. No other Sprint 1 items (P3-3+) were started.

---

## Root cause

`UpdateDeliveryPickupInputsRequest` (validated by `UpdateDeliveryPickupInputsController`, route `POST /api/admin/v1/orders/schedules/update-delivery-pickup-inputs`) accepted `tnc_status`, `drivers_license_status`, `video_status`, and `checklist_status` as plain `nullable|string|max:255` — any arbitrary string was accepted and persisted directly onto `order_products.{delivery,pickup}_{tnc_status,drivers_license_status,video_status,checklist_status}` (all plain `VARCHAR` columns, no DB-level enum constraint either).

Per the original audit finding (`PHASE3_IMPLEMENTATION_PLAN.md` SEC-1): **nothing currently reads these fields** — they're write-only today — but the audit explicitly flagged this as a closing window: "the moment a dashboard or gating rule starts trusting them, this becomes a real data-integrity and business-logic risk."

## Investigation of currently accepted values

- **Live production data (`rc_kabba_7_7_26`):** all four fields (delivery + pickup variants, 8 columns total) currently contain **zero non-null rows** — no mobile or admin client has ever actually sent a value yet. The migration adding these columns (`2026_06_23_193818_add_delivery_pickup_inputs_to_order_products.php`) is recent.
- **Codebase search:** no frontend/mobile source in this repo references these four field names outside the `FormRequest`/`fillable` declarations — confirming there is no existing client contract to preserve or accidentally break.
- **Existing sibling convention found:** `App\Enums\Orders\OrderTermsStatus` already exists and is used elsewhere (`Order::terms_status`) for the exact same "terms and conditions" concept as `tnc_status` — reused directly rather than inventing a new enum. `App\Enums\Orders\EquipmentDriverStatus` (used by the sibling `DriverChecklistRequest` via `Rule::enum(...)`) was used as the structural template for the three new enums, since no existing enum covered driver's-license verification, video, or checklist-completion status specifically.

## Changes made

**New enums** (mirroring `EquipmentDriverStatus`'s shape — backed cases, `label()`, `getValues()`):
- `app/Enums/Orders/DriversLicenseStatus.php` — `Pending`, `Verified`, `Rejected`
- `app/Enums/Orders/VideoInputStatus.php` — `Pending`, `Completed`, `Skipped`
- `app/Enums/Orders/ChecklistInputStatus.php` — `Pending`, `Completed`, `Skipped`

**`tnc_status`** reuses the existing `App\Enums\Orders\OrderTermsStatus` (`Accepted`, `Declined`, `Pending`, `Exempt`) — no new enum created for it.

**`app/Http/Requests/Api/Admin/V1/Orders/Schedules/UpdateDeliveryPickupInputsRequest.php`:**
- `tnc_status`, `drivers_license_status`, `video_status`, `checklist_status` changed from `'nullable|string|max:255'` to `['nullable', Rule::enum(...)]`, one enum per field, matching the exact pattern already used by the sibling `DriverChecklistRequest::equipment_driver_status` rule.
- `bodyParameters()` docblocks updated to list the allowed values via each enum's `getValues()` (mirroring `DriverChecklistRequest`'s existing `implode(', ', EquipmentDriverStatus::getValues())` pattern) and examples updated to a real enum value.

**No controller logic was changed.** `UpdateDeliveryPickupInputsController.php` is untouched — it already reads `$validated[$key]` as a plain string and writes it to the corresponding column; a validated enum value flows through identically to how any string did before.

## Preserved behavior

- `nullable` retained on all four fields — omitting them still succeeds exactly as before.
- The success response envelope (`{"status": true, "message": "..."}`) and the validation-failure envelope (`{"success": false, "message": "Validation failed.", "errors": {...}}`, from `ApiBaseFormRequest::failedValidation()`) are both unchanged — this fix only tightens which values reach that logic, not the shapes around it.
- `type`, `order_product_unique_id`, `inputs_date` rules untouched.

## Tests added

`tests/Feature/Orders/Schedules/UpdateDeliveryPickupInputsStatusValidationTest.php` (10 tests):

1-4. One test per field confirming its correct enum value is accepted (200, persisted to the corresponding `delivery_*` column).
5. All four valid values sent together still succeed, with the exact unchanged response envelope asserted.
6-9. One test per field confirming an arbitrary invalid string is rejected (422, `assertJsonValidationErrors` on that field, and for `tnc_status` also confirming nothing was persisted).
10. Omitting all four fields still succeeds (nullable behavior preserved).

### A pre-existing, unrelated bug found during test-writing (not fixed, out of scope)

While writing these tests, setting `delivery_by` on the fixture (so the controller's completion branch would run) caused every "valid value" test to 500 — but the cause was **not** related to SEC-1's validation change. `UpdateDeliveryPickupInputsController` loads its `OrderProduct` via a narrow `->select(['id','order_id','unique_id','delivery_by','pickup_by','is_delivered','is_returned'])`. When `delivery_status` transitions to `'Completed'`, `OrderProduct`'s own `static::updated` listener calls `FunnelLifecycleService::stopDeliveryReminderFunnels($model, ...)`, which reads `$model->product_id` — a column excluded from that `select()`, so it's `null`, and `getFunnelsForProduct(int $productId, ...)`'s strict `int` type hint throws a `TypeError` on any real "mark delivery complete" call through this endpoint. Confirmed via temporary debug logging (reverted; `git diff` on the controller is empty) that this reproduces independent of the enum change. **This is a real, live, likely-production-reproducible bug, but it is unrelated to SEC-1 and out of scope for this fix — recommend filing as its own backlog item** (candidate name: BUG-14) rather than silently fixing it here. The test fixture was adjusted to leave `delivery_by` null (still exercising the exact validation rules under test) specifically to avoid this unrelated crash path.

## Exact commands run

```
php artisan test --filter=UpdateDeliveryPickupInputsStatusValidationTest
```

Result: **10 passed, 33 assertions, 0 failures.**

## Files changed

- `app/Http/Requests/Api/Admin/V1/Orders/Schedules/UpdateDeliveryPickupInputsRequest.php` (edited — enum validation + docblock updates)
- `app/Enums/Orders/DriversLicenseStatus.php` (new)
- `app/Enums/Orders/VideoInputStatus.php` (new)
- `app/Enums/Orders/ChecklistInputStatus.php` (new)
- `tests/Feature/Orders/Schedules/UpdateDeliveryPickupInputsStatusValidationTest.php` (new, 10 tests)
- `docs/checklist-system-audit/P3_2_SEC1_STATUS_VALIDATION.md` (new — this file)

No migrations, no schema changes, no controller logic changed.

## Pass/fail summary

- New test file: **10 passed**, 33 assertions, 0 failures.

## Verdict

**PASS.** SEC-1's enum-validation gap is closed for all four fields, reusing the existing `OrderTermsStatus` enum where available and following the codebase's established `Rule::enum()` convention (mirrored from `DriverChecklistRequest`) for the three new enums. No existing behavior changed for valid or omitted values; only previously-permissive arbitrary strings are now rejected. One unrelated pre-existing bug was discovered and documented, not fixed, per scope. Stopping here — no other Sprint 1 item started.
