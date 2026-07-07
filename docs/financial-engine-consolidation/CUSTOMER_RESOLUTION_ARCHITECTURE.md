# Customer Resolution Wizard Architecture

Document date: 2026-07-03
Status: **Architecture and planning only. No code implemented.**
Depends on: `CUSTOMER_CREDIT_ARCHITECTURE.md` (Store Credit as the wizard's middle resolution option), existing `RefundStoreController`/`RefundPaymentController` (the wizard's Refund option), existing `UpdateProductScheduleController` (the wizard's Reschedule option — confirmed already exists, see §3).

---

## 1. Why This Document Exists

Today, when a customer has a service problem — a late delivery, damaged equipment, a billing dispute — the resolution path depends entirely on which employee handles it and what they happen to remember about company policy. This wizard exists to make the *correct, policy-compliant, lowest-cost-to-the-business* resolution the *easiest* one to click through, without requiring the employee to memorize an escalation policy. This is a training-reduction and consistency tool as much as a technical one.

## 2. Resolution Priority (Business Rule, Not a Suggestion)

The mission specifies a strict order: **1. Reschedule → 2. Store Credit → 3. Refund.** This ordering is a deliberate cost hierarchy — a reschedule costs the business nothing but a calendar slot; a store credit costs the business a future sale it will very likely recoup; a refund is a completed loss with no guarantee of a future sale. The wizard's entire design exists to make employees try step 1 before step 2, and step 2 before step 3 — not to let them jump straight to a refund because it is the fastest click.

## 3. Decision Tree

```
START: Employee opens Resolution Wizard for a customer issue
  │
  ├─ Step 0: What is the issue category?
  │    (Late delivery / Equipment problem / Damage dispute / Billing dispute / Other)
  │    → Selecting a category pre-fills likely resolution options in later steps,
  │      but does not skip any step. Every issue still walks the full 1→2→3 order.
  │
  ├─ Step 1: RESCHEDULE
  │    Can this be resolved by changing a delivery/pickup date or rental period?
  │    → Uses the EXISTING UpdateProductScheduleController mechanism (confirmed
  │      already live in this codebase — sets delivery_status/pickup_status to
  │      'Reschedule'). This is not a new capability; the wizard's job here is
  │      to surface it as the FIRST offered option, not to build it.
  │    → YES, customer accepts: resolution complete. Log outcome. END.
  │    → NO (not applicable, or customer declines): proceed to Step 2.
  │
  ├─ Step 2: STORE CREDIT
  │    Offer a credit per CUSTOMER_CREDIT_ARCHITECTURE.md — amount and reason
  │    selected by the employee within policy limits (see §5 Business Rules).
  │    → YES, customer accepts: CustomerCreditService issues a Financial Credit
  │      grant (reason mapped from the issue category selected in Step 0, e.g.
  │      "Manager Goodwill" or a new "Service Resolution" reason). Log outcome. END.
  │    → NO (customer declines, or amount exceeds employee's policy limit):
  │      proceed to Step 3.
  │
  └─ Step 3: REFUND
       → Employee selects: ledger refund (RefundStoreController, today's CRM
         credit-balance refund) or gateway refund (RefundPaymentController,
         today's Authorize.Net refund) — the wizard routes to whichever is
         correct for how the original payment was made; it does not invent
         a third refund mechanism.
       → If the requested amount or the "no reschedule/no credit accepted"
         pattern exceeds the employee's authority (see §5): MANAGER OVERRIDE
         REQUIRED before this step can complete.
       → Resolution complete. Log outcome. END.
```

**Every path terminates in a logged outcome**, whether or not a financial transaction occurred — this is what makes the "Customer Lifetime Savings" and future resolution-analytics reporting possible, and what lets a manager audit "how many issues could have been resolved by reschedule but went straight to refund."

## 4. Wizard Screens

| # | Screen | Purpose | Data shown |
|---|---|---|---|
| 1 | Issue Intake | Capture what happened | Customer, order/product reference, issue category, free-text notes |
| 2 | Reschedule Offer | Attempt lowest-cost resolution first | Existing delivery/pickup schedule, available reschedule dates (reuses whatever `UpdateProductScheduleController`'s existing date-selection UI already provides) |
| 3 | Store Credit Offer | Second-tier resolution | Customer's current Store Credit balance (`CUSTOMER_CREDIT_ARCHITECTURE.md` §6), employee's proposed grant amount, reason, expiration (if promotional-sourced) |
| 4 | Refund Offer | Last-resort resolution | Original payment method (determines ledger vs. gateway refund routing), amount, reason |
| 5 | Manager Override | Only shown when policy limits are exceeded | Requested action, requesting employee, policy limit exceeded, override reason (required text field), manager's credential/approval |
| 6 | Confirmation | Final summary before committing | Everything selected across screens 1-4/5, in plain language, before the ledger-affecting action fires |
| 7 | Outcome Log | Post-resolution record | What was offered, what was accepted, at which step resolution occurred — feeds future resolution-analytics reporting |

## 5. Business Rules

- **Reschedule must always be offered before Store Credit; Store Credit must always be offered before Refund.** The wizard's screen sequence enforces this by construction — there is no "skip to refund" button on screen 1.
- **Every employee has a per-resolution dollar authority ceiling** for Store Credit grants and Refunds, mirroring the same tiered-authority concept already implicit in this codebase's separation of `ChargeStoreController` (any staff) vs. whatever approval `DiscountStoreController`/`RefundStoreController` currently require at the UI/role level — **the exact ceiling values are a business decision, not specified here**.
- **A declined Reschedule offer must be logged with a reason** (customer declined / not applicable to this issue) before the wizard proceeds to Store Credit — this is what makes the "could this have been a reschedule" audit meaningful later.
- **A Refund cannot be the first action selected** — the UI must show Reschedule and Store Credit as attempted-or-explicitly-declined before Refund becomes selectable. This is a UI-enforced policy, not merely a suggestion, matching the mission's explicit priority ordering.

## 6. Permission Requirements

Per this phase's research into the existing codebase: permissions are modeled via `spatie/laravel-permission` with custom `App\Models\Iam\AccessControl\Role`/`Permission` models, and sensitive financial actions (`DiscountStoreController`, `VoidPaymentController`) are currently gated at the controller/action level via `auth()->user()` checks against the authenticated admin's assigned permissions — a real, extensible mechanism, not something this initiative needs to invent.

Proposed new permissions (to be created the same way existing ones are, via the `Permission` model — **exact permission names/keys are an implementation detail for the eventual pre-implementation checklist**):

| Action | Minimum permission tier (proposed) |
|---|---|
| Open the Resolution Wizard at all | Any CRM/order-management staff role |
| Offer a Reschedule | Same as today's existing reschedule capability — no new permission needed |
| Offer Store Credit within policy limit | Standard CRM staff permission (mirrors today's `ChargeStoreController`/`DiscountStoreController` staff-level access) |
| Offer Store Credit above policy limit | **Manager Override required** (see §7) |
| Offer a Refund at all | Same tier `RefundStoreController`/`RefundPaymentController` already require today — this document does not propose loosening or tightening existing refund access, only routing to it through the wizard |
| Offer a Refund above policy limit | **Manager Override required** |
| Approve a Manager Override | A distinct, higher permission tier — a manager overriding their own action is a control gap, so the override approver should be a different authenticated user than the requesting employee wherever practically enforceable |

## 7. Manager Override Points

Manager override exists as a **release valve for the policy ordering, not a bypass of it.** The override never skips Reschedule or Store Credit being offered first — it exists only at the point where an amount or a repeated pattern exceeds a line employee's authority. Two distinct override triggers:

1. **Dollar-limit override** — the Store Credit or Refund amount an employee wants to offer exceeds their assigned ceiling (§5/§6). The wizard blocks progression past the Confirmation screen until a manager-tier user approves, with the requested amount and reason visible to the approver.
2. **Pattern override** — a business rule this document flags but does not fully specify: if the *same customer* has required 2+ resolutions within a rolling window (e.g., 90 days), a manager should be notified even if each individual resolution was within a normal employee's dollar authority, since a pattern of repeat issues may indicate a root cause worth escalating beyond the transaction itself. **The exact threshold is a business decision, not specified here** — flagged as a "Future Automation Opportunity" (§9) if not adopted immediately.

## 8. Order Entry Integration

This phase's research confirmed there is **no existing admin-side order-creation/checkout screen** — orders are created via the front-end customer checkout (`Front\Checkout\PostController`), and the admin panel's order-management area has only an Edit/View screen (`OrderManagement/Orders/EditController.php` → `edit.blade.php`), where staff manage an already-placed order's payments, equipment, and schedule. This is the realistic, concrete integration point for the mission's "customer financial summary shown during checkout" mockup — not a hypothetical future checkout screen that does not exist today.

**Proposed placement: a new panel on the existing Order Edit screen** (`edit.blade.php`), visible wherever staff already take a payment or make a charge against that order — the same place `PaymentStoreController`/`ChargeStoreController`-driven actions already happen today. A secondary, longer-term integration point is the front-end customer checkout itself, so a customer can see and apply their own Store Credit before finishing checkout — flagged as Phase 2 of this integration, not required for an initial rollout, since it requires front-end UI design work well beyond this document's scope.

### Information displayed

Matching the mission's mockup, mapped to what already exists vs. what is new:

```
------------------------------------------------
⚠ CUSTOMER STATUS
Past Due          [NEW — does not exist today]
Bad Debt          [EXISTS — CustomHelper::getCustomerAccountStatus(), returns
                    'Good Standing' | 'Bad Debt' today]
Payment Plan       [NEW — no payment-plan concept exists in this codebase]
Good Standing      [EXISTS — same method, default state]
------------------------------------------------
💰 AVAILABLE STORE CREDIT
Current Balance    [NEW — CustomerCreditService, per CUSTOMER_CREDIT_ARCHITECTURE.md §6]
Apply Credit?      [NEW]
Amount to Apply    [NEW]
Remaining Credit   [NEW]
------------------------------------------------
```

**This is a meaningful, concrete finding, not a formality**: `CustomHelper::getCustomerAccountStatus()` (`CustomHelper.php:685`) already computes exactly two of the mission's four statuses (`Good Standing`, `Bad Debt`, via a `≥60 days since last payment OR (not approved + no credit limit + balance > 0)` rule) plus a payment-aging alert (`0-30`/`30-45`/`>45` days, yellow/orange/red). **This existing method is the correct foundation to extend, not replace** — "Past Due" is naturally the aging-alert states already computed (`30-45` or `>45` days) reframed as a named status rather than only a color, and "Payment Plan" is the only genuinely new status concept, requiring a payment-plan feature that does not exist anywhere in this codebase today (out of scope for this document; flagged in §9).

### Workflow

1. Staff opens an existing order (Order Edit screen).
2. The new panel loads `CustomHelper::getCustomerAccountStatus()`'s existing output (no change to that method needed for the status half of the panel) plus the new Customer Credit balance query.
3. If Store Credit is available and the order has an outstanding amount, staff (or, in the Phase 2 front-end integration, the customer) can choose to apply some or all of it.
4. Applying credit creates a `debit` redemption row (`CUSTOMER_CREDIT_ARCHITECTURE.md` §5), reducing the order's outstanding balance by the applied amount, through the same `LedgerBalanceService` path any other redemption would use.
5. "Remaining Credit" reflects the post-application balance immediately — read from the same balance query as step 2, not a separately maintained running total, to avoid the exact kind of two-methods-disagree drift this initiative has repeatedly found and fixed elsewhere (Phase 2.5A's four-method divergence, Phase 2.7's rounding discrepancy).

### Permissions

Viewing the Customer Status panel: no new permission needed, it is read-only and uses an already-public-within-staff computation. Applying Store Credit: same tier as §6's "Offer Store Credit within policy limit," since applying existing credit at Order Entry is a lower-risk action than *granting new* credit via the Resolution Wizard — **this distinction (granting vs. applying already-granted credit) should be reflected in two separate permissions when implemented, not one shared permission,** so that broad access to "let a customer spend their own credit" does not implicitly also grant "create new credit out of nothing."

### Customer Experience

For the initial (staff-only, Order Edit screen) rollout: transparent to the customer except that their invoice/receipt reflects the applied credit as a line item, the same way a discount line item is already shown today. For the Phase 2 front-end integration: the customer sees their own balance and an opt-in control before completing checkout — explicitly **not** an opt-out or auto-applied credit, to avoid a customer being surprised that credit they wanted to save was silently spent.

## 9. Future Automation Opportunities

- **Automatic Reschedule suggestion**: proactively suggest a reschedule before a customer even reports an issue (e.g., a known equipment shortage), rather than waiting for the wizard to be opened reactively.
- **Pattern-override automation** (§7.2): auto-flag repeat-resolution customers for manager review rather than relying on a human noticing the pattern.
- **Auto-expiring promotional credit reminders**: notify a customer via the existing SMS/Email infrastructure (`TwilioService`, `EmailTemplate` — both already built and reusable, confirmed in this phase's research) before a promotional grant expires, to maximize genuine redemption (a customer experience win) rather than quiet forfeiture (a liability the business would otherwise just keep).
- **Resolution outcome analytics feeding policy limits**: if data shows Store Credit offers are declined at a certain amount threshold significantly more than at a slightly higher one, that's a signal the dollar-limit policy itself could be tuned — this requires the Outcome Log (§4, screen 7) to exist and accumulate real data first.

