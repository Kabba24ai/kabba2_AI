# Mobile-Team Compatibility Questionnaire — Phase 3 API Cleanup (P3-6)

**Purpose:** we want to fix three small, confirmed backend bugs/inconsistencies. Before we ship any of them, we need to know whether the mobile app currently depends on the exact broken/inconsistent values below. Please answer the 3 questions at the bottom — that's all we need to proceed safely.

---

## 1. BUG-6 — `unique_id` and `id` fields on rental-ready checklist questions

**Affected endpoints:**
- `POST /api/admin/v1/rental-ready-checklists`
- `POST /api/admin/v1/orders`
- `POST /api/admin/v1/orders/details`
- `POST /api/admin/v1/equipment`

**Current JSON** (each item in `rental_ready_checklist_questions`, when the equipment is currently rented and delivered):
```json
{
  "id": 0,
  "unique_id": 47,
  "question_name": "Tire pressure check",
  "category_id": 3,
  "required_question": true,
  "answers": [ ... ],
  "selected_answer": null,
  "note": ""
}
```

**Proposed JSON:**
```json
{
  "id": 47,
  "unique_id": "QST-AB12-CD34",
  "question_name": "Tire pressure check",
  "category_id": 3,
  "required_question": true,
  "answers": [ ... ],
  "selected_answer": null,
  "note": ""
}
```

**What's changing:** `id` goes from always `0` to the real numeric question id. `unique_id` changes from a number to a string (its real, stable identifier).

---

## 2. API-1 — Response envelope on two endpoints

**Affected endpoints:**
- `POST /api/admin/v1/orders/schedules/driver-checklist`
- `POST /api/admin/v1/orders/schedules/update-delivery-pickup-inputs`

**Current JSON:**
```json
{ "status": true, "message": "Driver checklist updated successfully." }
```

**Proposed JSON:**
```json
{ "success": true, "message": "Driver checklist updated successfully." }
```

**What's changing:** the `status` key is renamed to `success`, matching every other endpoint in the API.

---

## 3. API-2 — Checklist question resource shape (Customer vs. Rental Ready)

**Affected endpoints:**
- `POST /api/admin/v1/customer-checklists/question-answers`
- `POST /api/admin/v1/orders` (`customer_checklist_questions` and `rental_ready_checklist_questions`)
- `POST /api/admin/v1/equipment` (`checklist_qas` and `rental_ready_checklist_questions`)

**Current JSON (Customer Checklist question):**
```json
{
  "id": 12,
  "unique_id": "CAQST-XX11-YY22",
  "required_question": true,
  "deliverAnswer": { "id": 5, "unique_id": "...", "answer_name": "Yes" },
  "returnAnswer": null
}
```

**Current JSON (Rental Ready question, for comparison):**
```json
{
  "id": 47,
  "unique_id": "QST-AB12-CD34",
  "required_question": false,
  "selected_answer": "ANS-ZZ99-WW88"
}
```

**Proposed:** standardize both to the same field name and shape for the selected answer, and the same default for `required_question`. Exact target shape is not yet finalized — pending your answer to Question 3 below.

---

## Questions requiring your confirmation

**Q1 (BUG-6).** Does any currently-shipped mobile app version read `unique_id` on a rental-ready checklist question and treat it as a number (e.g., for comparison, storage, or offline caching keyed by that value)? If yes, which app version(s), and can they be updated to expect a string before this ships?

**Q2 (API-1).** Does any currently-shipped mobile app version check `response.status` specifically (as opposed to just checking the HTTP status code, or ignoring this field) on the driver-checklist or update-delivery-pickup-inputs endpoints?

**Q3 (API-2).** Which shape should the standardized checklist-question resource use — the Customer style (`deliverAnswer`/`returnAnswer` nested objects) or the Rental Ready style (`selected_answer` bare string), or a new third shape? And should this ship now, behind a new API version, or be deferred until a later phase?

---

## Recommended rollout, pending your answers

- **BUG-6 / API-2:** if any client depends on the current numeric `unique_id` or current answer shape, we'll add the corrected field alongside the existing one for a transition window (no existing field removed or changed until you confirm it's safe) rather than changing it in place.
- **API-1:** we'll emit both `success` and `status` together for a transition window regardless of your answer, since this is a low-cost, purely additive safety margin.
- Nothing ships until we have your answers — these three items are explicitly blocked pending this questionnaire (see Decision Log entries D9-D11 in `PHASE3_IMPLEMENTATION_PLAN.md`).
