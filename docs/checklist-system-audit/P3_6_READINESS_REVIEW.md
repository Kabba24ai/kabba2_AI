# P3-6 Readiness Review — Mobile-facing API cleanup (BUG-6, API-1, API-2)

**Date:** 2026-07-15
**Type:** Investigation only. **No production code was modified to produce this document.**
**Verdict: READY WITH PREREQUISITES** — see §6.

---

## 1. BUG-6 — `unique_id` populated from numeric `id`, and `id` returns 0

### Root cause (expanded scope)

Both bugs live in the same array branch of `RentalReadyChecklistQuestions\ListResource::toArray()` ([ListResource.php:29-39](../../app/Http/Resources/Api/Admin/V1/RentalReadyChecklistQuestions/ListResource.php)) — the order-scoped, decoded-`rental_ready_qa_json` path. The object branch (checklistMaster path) is already correct.

```php
// Current (buggy)
'id'        => $this['main_id'] ?? 0,   // 'main_id' does not exist in the source array — always 0
'unique_id' => $this['id'] ?? '',       // reads the numeric id, not the real unique_id string
```

Confirmed by reading `Orders\RentalReadyChecklists\SaveController.php`'s construction of the underlying array (the exact payload later `json_encode`d into `rental_ready_qa_json`): it writes `id` and `unique_id` as separate keys — never `main_id`. **`id` is therefore always `0` for every array-shaped question in production today**, not a hypothetical risk.

**Both bugs must be fixed together** — fixing only `unique_id` while `id` still reads the nonexistent `main_id` leaves `id` broken; fixing only `id` leaves the higher-risk `unique_id` type/value change unaddressed.

### Exact mobile endpoints affected

All four reach the array branch whenever `isRentedAndDelivered` is true (confirmed by tracing each controller's branch condition):

| Endpoint | Field |
|---|---|
| `POST /api/admin/v1/rental-ready-checklists` | `rental_ready_checklist_questions` (top-level) |
| `POST /api/admin/v1/orders` | `rental_ready_checklist_questions` (nested per order product) |
| `POST /api/admin/v1/orders/details` | `rental_ready_checklist_questions` (nested) |
| `POST /api/admin/v1/equipment` | `rental_ready_checklist_questions` (nested) |

### Current vs. proposed JSON (per question, array branch only)

```jsonc
// Current (live today)
{ "id": 0, "unique_id": 47, "question_name": "...", "category_id": 3, "required_question": true, "answers": [...], "selected_answer": null, "note": "" }

// Proposed
{ "id": 47, "unique_id": "QST-AB12-CD34", "question_name": "...", "category_id": 3, "required_question": true, "answers": [...], "selected_answer": null, "note": "" }
```

`id`'s change (0 → real id) carries essentially no external risk — nothing meaningful can currently depend on a constant `0`. `unique_id`'s change is a **type change** (`int` → `string`) and a **value change** — the real risk this whole item is about.

### Mobile client risk

**Cannot be confirmed from this backend repository** — no mobile client source is available here. This is the blocking prerequisite; see the questionnaire (§7).

### Existing test coverage

`Bug11NullGuardCharacterizationTest.php` is the only test exercising the array branch of the `rental-ready-checklists` endpoint. It asserts `success: true` only — it never asserts the actual `id`/`unique_id` values in the response body. **No existing test locks in the current (buggy) shape**, meaning implementation won't require rewriting an existing assertion, but also that there is no safety net today.

---

## 2. API-1 — `{success,message}` vs. `{status,message}` envelopes

### Exact mobile endpoints affected

Confirmed by reading both controllers directly — exactly 2 endpoints use `{status, message}`; every other endpoint checked this session uses `{success, message}` (including the shared validation-failure envelope from `ApiBaseFormRequest::failedValidation()`):

| Endpoint | Current envelope |
|---|---|
| `POST /api/admin/v1/orders/schedules/driver-checklist` | `{status, message}` |
| `POST /api/admin/v1/orders/schedules/update-delivery-pickup-inputs` | `{status, message}` |

### Current vs. proposed JSON

```jsonc
// Current
{ "status": true, "message": "Driver checklist updated successfully." }
// Proposed
{ "success": true, "message": "Driver checklist updated successfully." }
```

This is a **key rename**, not a value/type change — a smaller-shaped change than BUG-6, but still breaks any client checking `response.status === true` literally.

### Mobile client risk

Cannot be confirmed from this repository. Same blocking prerequisite as BUG-6.

---

## 3. API-2 — Resource shape inconsistencies

### Confirmed discrepancies

Read `CustomerChecklistQuestions\ListResource` against `RentalReadyChecklistQuestions\ListResource` directly:

| Field | Customer resource | Rental Ready resource |
|---|---|---|
| `required_question` default | `?? true` | `?? false` |
| Selected answer | `deliverAnswer` + `returnAnswer`: full nested answer objects | `selected_answer`: bare string or `null` |

The selected-answer discrepancy is a **different field name and a different shape entirely**, not just a default-value flip — the most invasive of the three P3-6 items.

### Exact mobile endpoints affected

| Endpoint | Field |
|---|---|
| `POST /api/admin/v1/customer-checklists/question-answers` | top-level |
| `POST /api/admin/v1/orders` | `customer_checklist_questions` (nested) |
| `POST /api/admin/v1/equipment` | `checklist_qas` (nested) |
| (Rental Ready side: same 4 endpoints listed under BUG-6) | `rental_ready_checklist_questions` |

### Mobile client risk

Cannot be confirmed from this repository. Same blocking prerequisite.

---

## 4. Safest backward-compatible rollout approach

- **BUG-6 & API-2:** add new, correctly-shaped fields alongside the existing ones for a transition window (e.g. a temporary `question_unique_id` alongside `unique_id`) rather than mutating an existing key's type/value in place. See **D9** (BUG-6) and **D11** (API-2) in `PHASE3_IMPLEMENTATION_PLAN.md` §8.
- **API-1:** dual-key emission (`{success, status, message}` together) for a transition window is lower-risk than a hard rename, since it is purely additive. See **D10**.
- **Full API versioning** would be the safest option of all but is disproportionate to these 3 items' scope on its own — recommend dual-field/dual-key first; escalate to versioning only if the mobile-team questionnaire reveals active client dependence that a transition window can't safely bridge.

## 5. Exact tests required (before implementation, not now)

1. Resource-level unit tests asserting the exact `id`/`unique_id` values and types for `RentalReadyChecklistQuestions\ListResource` in both branches (none exist today).
2. Endpoint-level tests for all 4 BUG-6-affected endpoints confirming the dual-field envelope during any transition window.
3. Envelope-shape tests for the 2 API-1 endpoints confirming both `success` and `status` keys are present and equal during any transition window.
4. Resource-shape tests for API-2 confirming `required_question` defaults and the standardized answer shape on both trees.

## 6. Verdict

**READY WITH PREREQUISITES.** The backend-side investigation is complete: exact endpoints, exact current/proposed JSON, and exact root causes are all confirmed by direct code inspection, not assumption. What blocks implementation is **not** more backend investigation — it's mobile-team input, which no amount of further code reading can substitute for. Three items require an explicit decision (**D9, D10, D11** in `PHASE3_IMPLEMENTATION_PLAN.md` §8), none of which should be recorded until the mobile-team questionnaire (§7 / `P3_6_MOBILE_COMPATIBILITY_QUESTIONNAIRE.md`) has a response.

## 7. Mobile-team questionnaire

See `docs/checklist-system-audit/P3_6_MOBILE_COMPATIBILITY_QUESTIONNAIRE.md` for the concise, send-as-is questionnaire covering all three items.

---

## Confirmation

**NO PRODUCTION CODE WAS MODIFIED** to produce this document or its companion questionnaire. This is planning/investigation only.
