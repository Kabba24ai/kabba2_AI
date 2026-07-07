# Phase 3.3 — Customer Resolution Center Foundation: Design

Date: 2026-07-04
Branch: `raj_development`

---

## 1. Pre-Implementation Findings

Before designing the decision tree's execution paths, the following was verified against real code (not assumed):

| Mission assumption | Verified reality |
|---|---|
| Step 1 "Free Reschedule" | Confirmed: `Admin\OrderManagement\Orders\UpdateProductScheduleController` reschedules delivery/pickup dates on an order product. **Rescheduling has no fee concept anywhere in this codebase** — every reschedule is already free, so "Free Reschedule" is simply the existing, only behavior, not a new policy this phase introduces. |
| Step 3 "Waive Refund Fee (per existing policy)" | **No refund fee, restocking fee, or cancellation fee exists anywhere in this codebase** — confirmed via repository-wide search (`app/`, order management views, all migrations, all Financial Engine docs). `RefundPaymentController` processes a plain refund amount with no fee deduction of any kind. Per this phase's explicit rule — "Follow the approved Financial Engine policies exactly. Do not invent new policy." — **this phase does not invent a refund fee mechanism.** "Standard Refund" and "Waive Refund Fee" are both surfaced as the two labeled options the decision tree specifies, but since no fee is ever actually charged today, they currently resolve to the same real-world action (a plain refund via the existing `RefundPaymentController`). This is disclosed here, not silently reinterpreted or invented around. |
| Payment method detection | Confirmed: `OrderPaymentMethod` enum (`COD`, `Account`, `Card`, `Cash`, `Online`, `Cheque`, `Other`); `$order->last_payment_type` accessor and `$order->lastPaidPayment` relation both exist and are read-only, used here for display/branching only. |
| Customer account status | Confirmed: `CustomHelper::getCustomerAccountStatus(Customer $customer): array` returns `status`/`badge`/`alert` — reused for display only, not modified. |

## 2. Architecture

**Core principle enforced throughout**: the engine only ever *recommends*. No code path in this phase executes a refund, a reschedule, or an automatic credit issuance without an explicit, separate employee action that names exactly what is about to happen.

```
ResolutionPolicy          — the ordered priority (Reschedule → Store Credit → Refund), as constants/labels only, no logic
ResolutionDecisionEngine  — pure function: (canReschedule, creditWouldSatisfy, paymentMethod) -> ResolutionRecommendation
ResolutionRecommendation  — value object: key, label, nextStepText, requiresManagerOverride
ResolutionCenterService   — orchestration + persistence; the ONLY class that touches the database or calls CustomerCreditService
ResolutionCase (model)    — one row per guided session; doubles as its own audit trail (same pattern as `customer_credits`)
```

`ResolutionDecisionEngine` has zero database access and zero side effects — every branch of the mission's decision tree is a pure, fully unit-testable function.

## 3. Decision Tree → Code

Implemented exactly as specified, no additional branches:

1. `canReschedule === true` → recommend `free_reschedule`, next step: "Use the existing reschedule option on this order's Edit screen. No fee applies." STOP.
2. `canReschedule === false`, `creditWouldSatisfy === true` → recommend `issue_store_credit`, next step: "Approve below to issue Financial Store Credit via CustomerCreditService." STOP.
3. `canReschedule === false`, `creditWouldSatisfy === false`, `paymentMethod === Card` → recommend `standard_refund` **or** `waive_refund_fee` (both offered, per §1's disclosed caveat that these are currently equivalent in effect) — employee picks one. STOP.
4. `canReschedule === false`, `creditWouldSatisfy === false`, `paymentMethod !== Card` → recommend `standard_refund` only. STOP.

## 4. Execution Boundaries — What This Phase Does and Does Not Execute

| Recommendation | This phase's action |
|---|---|
| Free Reschedule | **Records the decision only.** Points the employee to the existing Order Edit reschedule control. Does not call `UpdateProductScheduleController` on their behalf — that remains a distinct, manual action on a different screen, consistent with "nothing happens automatically." |
| Issue Store Credit | **Executes, but only behind an explicit, separate "Approve & Issue" action** — a second, deliberate employee click naming the exact amount, distinct from merely viewing the recommendation. Calls `CustomerCreditService::createFinancialCredit()` with the order reference (the `order_id` column Phase 3.2 already added), exactly as the mission's decision tree specifies ("Call CustomerCreditService"). This is not automation: nothing is issued until an employee explicitly approves a specific amount. |
| Standard Refund / Waive Refund Fee | **Records the decision only.** Points the employee to the existing `RefundPaymentController` flow on the Order Edit screen. This phase does not process a refund on the employee's behalf. |

## 5. Data Model

New table `resolution_cases` (one row per guided session — this doubles as the full audit trail, the same "the ledger is the log" pattern `customer_credits` already established):

`unique_id`, `customer_id`, `order_id`, `issue` (free text — what the customer is asking for), snapshot columns captured at case-open time (`balance_snapshot`, `store_credit_snapshot`, `payment_method_snapshot` — informational only, never a competing live source of truth; current figures are always re-read live from `CustomHelper`/`CustomerCreditService`/`Order` at display time), `can_reschedule`, `credit_would_satisfy`, `recommended_resolution`, `recommended_next_step`, `employee_decision` (followed the recommendation vs. overrode it), `employee_decision_detail`, `manager_override_user_id`, `manager_override_reason`, `notes`, `outcome` (pending/completed/cancelled), `credit_issued_id` (nullable FK to the `customer_credits` row this case's approved credit created, if any), `responsible_person_id`, timestamps, soft deletes.

## 6. Permissions

New `ModuleSeeder` category "Resolution Center", module `resolution_center`: `view`, `use` (start a case, answer questions, record a decision), `override` (fill in the Manager Override field), `view_audit_history`. Enforced via the same `permission:` route middleware pattern established in Phase 3.1, now proven reliable end-to-end since Platform Stabilization Sprint 1.

## 7. Out of Scope (unchanged from the mission)

Equipment exchanges, damage claims, Bad Debt/Collections, Payment Plans, Promotional Credits, Gift Cards, AI recommendations, Customer Portal, any automatic financial action. Also out of scope for this specific phase: a new refund-fee policy (none exists to encode — see §1) and executing reschedule/refund on the employee's behalf (both remain manual, on their existing screens).
