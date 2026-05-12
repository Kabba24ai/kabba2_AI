# AI Schedule Advisor: Process Overview

## How It Works

1. **Triggering the Advisor**
   - User opens the Schedule Assignment UI and launches the AI Schedule Advisor modal for a specific order product.

2. **Data Fetching**
   - Frontend sends a request to the backend endpoint handled by `AIScheduleAdvisorController` with the order product ID.

3. **Context Building**
   - Controller uses `AIContextBuilderService` to gather all necessary data:
     - Order details, product info, equipment assignments, operational constraints.
   - No legacy AI assignment rules are used; only Equipment Assignments.

4. **AI/Heuristic Processing**
   - Advisor logic analyzes the context:
     - Checks available equipment, delivery/pickup windows, operational issues.
     - Generates a recommendation (equipment, timing, actions required).
     - Identifies issues, warnings, and alternatives.

5. **Response Construction**
   - Backend returns a structured JSON response:
     - `assistant`: Contextual info (windows, issues, etc.)
     - `ai`: Recommendation, reasoning, warnings, alternatives, errors.

6. **Frontend Rendering**
   - Modal receives the response and displays:
     - Delivery/Pickup dates (formatted per app config)
     - Decision, recommended equipment, reasoning, warnings, alternatives.
     - Operational issues or errors.

7. **Validation & Output**
   - **Correct Output:**
     - Dates/times formatted as per config.
     - Equipment assignments reflect current business logic.
     - All issues, warnings, and alternatives are shown.
     - No legacy AI rule logic present.
   - **Incorrect Output:**
     - Dates/times are hardcoded or inconsistent.
     - Old AI rule logic is used.
     - Equipment assignments are missing or incorrect.
     - Errors are not surfaced to the user.

---

**Summary:**
The AI Schedule Advisor builds context from Equipment Assignments, processes recommendations, and returns structured, config-driven output. The process is correct if it follows these steps and all UI displays are consistent and accurate. Any deviation (legacy logic, wrong formatting, missing info) indicates an issue.
