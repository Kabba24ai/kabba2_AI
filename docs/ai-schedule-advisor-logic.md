# AI Schedule Advisor: Logic Overview

## Core Logic Used

### 1. Candidate Equipment Selection
- Finds all equipment matching the order product's category and name.
- Uses flexible matching (category, name words, equipment ID).

### 2. Equipment Eligibility Evaluation
- For each candidate equipment:
  - Checks for conflicts (overlapping assignments for the requested window).
  - Evaluates status: damaged, maintenance hold, rented, etc.
  - Checks for open service tasks.
  - Compares equipment location/store to order's delivery store.
  - Assigns a score (starting at 100, with deductions for each issue).
  - Flags and reasons are recorded for each deduction.

### 3. Issue Detection
- If no equipment is assigned, adds an 'UNASSIGNED' warning.
- Other issues are detected based on assignment and eligibility checks.

### 4. Context Building
- Gathers all relevant data (order, product, assignments, issues, candidates, policy).
- No legacy AI rules are used; only Equipment Assignments and current business logic.

### 5. AI/Heuristic Recommendation
- The advisor (OpenAI or heuristic) receives the context and must:
  - Recommend the best equipment (primary, upgrade, downgrade, or none).
  - Specify relationship type and actions required.
  - Provide reasoning, warnings, and alternatives.
  - Follow strict schema for output (JSON with required fields).
  - Follow business rules (e.g., upgrades notify, downgrades require review).

### 6. Output Structure
- The output includes:
  - `recommended_equipment_id`, `recommended_equipment_name`
  - `relationship_type` (primary/upgrade/downgrade/unknown)
  - `decision` (recommended/review_required/no_safe_recommendation)
  - `actions_required` (array)
  - `reasoning` (array)
  - `alternatives` (array of equipment options)
  - `warnings` (array)

## Validation Points
- Output is correct if:
  - All logic above is followed.
  - Output matches schema and business rules.
  - All issues, warnings, and alternatives are surfaced.
- Output is wrong if:
  - Legacy AI rules are used.
  - Output is missing required fields or is inconsistent with business logic.

---

**Summary:**
The AI Schedule Advisor uses a combination of candidate selection, eligibility scoring, issue detection, and strict schema-based AI/heuristic recommendation to ensure equipment assignments are operationally sound and compliant with business rules.
