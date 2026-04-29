# AI Schedule Advisor – Client Guide & Example

This guide explains how the AI Schedule Advisor assigns equipment, the logic rules it follows, and how the ChatGPT API (OpenAI) is used in the process.

---

## 1. Step-by-Step Assignment Process

### Step 1: User Action
- The user opens the Schedule Assignment page and requests an AI recommendation for a specific order product.

### Step 2: Data Gathering
- The system collects all relevant data:
  - Order details (product, dates, locations)
  - Current equipment assignments
  - Equipment assignment rules (from Equipment Assignments)
  - List of all eligible equipment

### Step 3: Candidate Evaluation
- Each equipment candidate is scored based on:
  - Availability (no overlapping assignments)
  - Status (damage  d, maintenance hold, rented, etc.)
  - Open service tasks
  - Location match with order
  - Assignment rules (primary, upgrade, downgrade)
- Issues and warnings are flagged (e.g., unassigned, conflicts, maintenance).

### Step 4: Context Building
- All gathered data and evaluation results are packaged into a structured context object.

### Step 5: ChatGPT (OpenAI) Recommendation
- The context is sent to the ChatGPT API with a strict prompt and schema:
  - The prompt instructs ChatGPT to follow business rules (see below).
  - ChatGPT analyzes the context and returns a structured recommendation (JSON):
    - Recommended equipment, relationship type, actions required, reasoning, alternatives, warnings.

### Step 6: Result Display
- The system displays the AI’s recommendation, reasoning, and any warnings or alternatives to the user.

---

## 2. Logic Rules (Detailed)

- **Primary Assignment:**
  - If a primary equipment match is available and ready, recommend it with no action required.
- **Upgrade:**
  - If only an upgrade is available, recommend it and notify internally (no customer approval needed unless policy says so).
- **Downgrade:**
  - If only a downgrade is available, recommend it but require review and customer approval.
- **Operational Risks:**
  - Avoid equipment with conflicts, damage, maintenance hold, or open service tasks unless no other options exist.
- **Location:**
  - Prefer equipment at the correct location/store.
- **Alternatives:**
  - List other possible equipment options with their pros/cons.
- **Warnings:**
  - Clearly state if all choices are weak or risky.

---

## 3. Example Scenario

**Order:**
- Product: Excavator
- Delivery: 2026-05-01 09:00
- Pickup: 2026-05-05 17:00
- Delivery Store: Main Yard

**Equipment Assignments:**
- Primary Pool: [EXC-101, EXC-102]
- Upgrade Path: [EXC-201]
- Downgrade Path: [EXC-001]

**Available Equipment:**
- EXC-101: Available, Main Yard
- EXC-102: Rented, Main Yard
- EXC-201: Available, Secondary Yard
- EXC-001: Available, Main Yard, but due for maintenance

**AI Recommendation (Sample Output):**
```json
{
  "recommended_equipment_id": "EXC-101",
  "recommended_equipment_name": "Excavator 101",
  "relationship_type": "primary",
  "decision": "recommended",
  "actions_required": [],
  "reasoning": [
    "EXC-101 is in the primary pool, available, and at the correct location."
  ],
  "alternatives": [
    {
      "equipment_id": "EXC-201",
      "equipment_name": "Excavator 201",
      "relationship_type": "upgrade",
      "actions_required": ["Internal notification"],
      "summary": "Upgrade unit, available but at a different yard."
    },
    {
      "equipment_id": "EXC-001",
      "equipment_name": "Excavator 001",
      "relationship_type": "downgrade",
      "actions_required": ["Review and customer approval"],
      "summary": "Downgrade unit, available but due for maintenance."
    }
  ],
  "warnings": []
}
```

---

## 4. What ChatGPT API Does
- Receives all context and rules in a structured prompt.
- Applies business logic to recommend the best equipment.
- Explains its reasoning, flags risks, and lists alternatives.
- Returns only valid, structured JSON (no free text).

---

## 5. Summary
- The AI Schedule Advisor ensures recommendations are always based on your business’s equipment assignment rules and operational realities.
- ChatGPT is used as a logic engine, not just a chatbot—it follows strict instructions and schema to ensure reliable, explainable results.
