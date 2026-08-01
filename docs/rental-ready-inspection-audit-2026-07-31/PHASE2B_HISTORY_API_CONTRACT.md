# Phase 2B — Rental Ready History / Detail API Contract (for mobile)

> 2026-08-01. **Specification only.** Phase 2B built the READ-ONLY web history +
> detail pages. It did NOT build these API endpoints and did NOT modify the
> mobile app. This document defines the contract so the mobile team can build
> and consume it in a later increment. The data source is the SAME immutable
> Phase 2A inspection records — no new storage.

## Principles

- **Read-only.** No writes, no lifecycle transitions.
- **Faithful reconstruction.** The detail endpoint MUST render answers from each
  inspection's stored snapshot (`equipment_rental_ready_checklist_questions.
  rental_ready_qa_json`), NEVER the live checklist master — so a historical
  inspection stays correct after questions/sections/answers are later edited.
- **Lifecycle vs result are separate fields** (mirror the web).
- Auth: `auth:api_user` (same as the existing Rental Ready endpoints).
- Bind by `unique_id` (equipment and inspection), matching the existing API.

## 1. History list

```
GET /api/admin/v1/equipment/{equipment_unique_id}/rental-ready-history
    ?page=1&per_page=20
```

Returns every canonical inspection for the unit — Draft, Completed, Voided,
Superseded, Abandoned — newest first; the inspection header is the row of record.

```json
{
  "success": true,
  "equipment": { "unique_id": "EQP-…", "equipment_name": "…", "equipment_id": "KUB-…" },
  "data": [
    {
      "unique_id": "ERTL-…",              // inspection UUID (use for the detail call)
      "inspection_date": "2026-07-28",
      "inspection_time": "15:22",
      "inspector": "Gary Jezorski",        // employee_name snapshot
      "equipment_hours": 122.9,
      "lifecycle_status": "completed",     // draft|completed|voided|superseded|abandoned
      "result": "rental_ready",            // rental_ready|maintenance_hold|damaged|null (draft)
      "order_number": "3488",              // nullable (no order context)
      "completed_at": "2026-07-28T15:22:00Z", // nullable
      "counts": {
        "total_questions": 12,
        "required_questions": 8,
        "required_items_completed": 8,
        "items_requiring_maintenance": 0,
        "damaged_items": 0
      }
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 47 }
}
```

## 2. Inspection detail

```
GET /api/admin/v1/equipment/{equipment_unique_id}/rental-ready-history/{inspection_unique_id}
```

The inspection scoped to that equipment (404 otherwise). Answers come from the
frozen snapshot, grouped by the snapshot's section, in the snapshot's order.

```json
{
  "success": true,
  "equipment": { "unique_id": "EQP-…", "equipment_name": "…", "equipment_id": "KUB-…" },
  "inspection": {
    "unique_id": "ERTL-…",
    "lifecycle_status": "completed",
    "result": "maintenance_hold",
    "is_draft": false,
    "inspector": "Gary Jezorski",
    "equipment_hours": 122.9,
    "inspection_date": "2026-07-28",
    "inspection_time": "15:22",
    "created_at": "2026-07-28T15:10:00Z",
    "completed_at": "2026-07-28T15:22:00Z",
    "order_number": "3488",                 // nullable
    "general_notes": "…",                   // nullable
    "counts": { "total_questions": 12, "required_items_completed": 8, "items_requiring_maintenance": 1, "damaged_items": 0 },
    "sections": [
      {
        "section_name": "Hydraulics",       // from the snapshot, not the live master
        "questions": [
          {
            "question_name": "Fuel Level",
            "required_question": true,
            "question_order": 1,
            "note": "…",
            "selected_answer": { "answer_name": "Full", "type": "Rental Ready" },
            "answers": [
              { "answer_name": "Full", "type": "Rental Ready", "is_selected": true },
              { "answer_name": "Low",  "type": "Maint. Hold",  "is_selected": false }
            ]
          }
        ]
      }
    ]
  }
}
```

## Notes for the implementer

- Source rows: `EquipmentRentalReadyTemplate` (header, Phase 2A columns
  `lifecycle_status`/`result`/`completed_at`/…) + its `checklistQuestions()`
  children (decode `rental_ready_qa_json` per row for `sections`/`questions`).
  The web controllers `RentalReadyHistoryController` /
  `RentalReadyHistoryShowController` are the reference implementations of the
  exact query + reconstruction to mirror.
- `is_draft` = `lifecycle_status === 'draft'`; a draft has `result: null` and
  must be clearly labelled incomplete on the mobile UI.
- Order number resolution: `orderProduct.order.order_number`, else
  `Order::find(order_id)->order_number`.
- Do NOT read the current master tables for answer text/type/section — only the
  snapshot — or old inspections will drift when the master is edited.
- Legacy rows (pre-Phase-2A) may have thinner snapshots; fall back gracefully
  (as the web detail controller does) and never fabricate missing text.
```
