# Phase 3 — Split Payments & Payment Allocation Architecture

**Status: investigation and design only. No code, migrations, or schema changes have been made. Nothing in this document has been committed, pushed, merged, or deployed.**

Scope: eliminate the codebase's reliance on "an order has one primary payment" and design a durable architecture for multiple payments, mixed payment methods, multiple refunds, and accurate per-payment refund attribution.

Audit method: four parallel read-only codebase sweeps (Order Details/refunds/timeline/card-fee; payment-entry & checkout surfaces; receipts/Store Credit/Gift Card/charges/CRM/Resolution Center; reporting/APIs), plus a direct reading of every `order_payments`-family migration, the `Order`/`OrderPayment`/`OrderHistory` models, `OrderPaymentStatus`/`OrderPaymentMethod` enums, and `AuthorizeNetService`'s public surface.

Pre-check: searched the repo and `docs/` for any existing `PaymentAllocation`/`RefundAllocation`/`order_payment_refund_allocations` work before starting — none exists. This is genuinely greenfield design, not a resumption of prior work. (One related-but-distinct prior audit exists: `docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md`, which covers CRM ledger tax-math bugs in `customer_accounts`/`invoices` — complementary to this document, not overlapping.)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Phase 3A — Single-Payment Assumption Inventory](#2-phase-3a--single-payment-assumption-inventory)
3. [Phase 3B — Current Schema Analysis](#3-phase-3b--current-schema-analysis)
4. [Phase 3C — Recommended Allocation Architecture](#4-phase-3c--recommended-allocation-architecture)
5. [Proposed Schema & Relationships](#5-proposed-schema--relationships)
6. [Phase 3D — Canonical Balance Formulas](#6-phase-3d--canonical-balance-formulas)
7. [Phase 3E — Refund Allocation Strategy](#7-phase-3e--refund-allocation-strategy)
8. [Phase 3F — Gateway Transaction Strategy](#8-phase-3f--gateway-transaction-strategy)
9. [Phase 3G — Payment and Refund Statuses](#9-phase-3g--payment-and-refund-statuses)
10. [Phase 3H — Receive Payment UX](#10-phase-3h--receive-payment-ux)
11. [Phase 3I — Receipt Design](#11-phase-3i--receipt-design)
12. [Phase 3J — Payment Timeline and Order Details](#12-phase-3j--payment-timeline-and-order-details)
13. [Phase 3K — Card Processing Fee Retention](#13-phase-3k--card-processing-fee-retention)
14. [Phase 3L — Reporting](#14-phase-3l--reporting)
15. [Phase 3M — Backward Compatibility](#15-phase-3m--backward-compatibility)
16. [Phase 3N — Migration and Rollout](#16-phase-3n--migration-and-rollout)
17. [Phase 3O — Testing Strategy](#17-phase-3o--testing-strategy)
18. [Expected Changed-File List](#18-expected-changed-file-list)
19. [Risks and Unresolved Business Decisions](#19-risks-and-unresolved-business-decisions)

---

## 1. Executive Summary

The good news first: **the ledger itself is not broken.** `order_payments` has always been `hasMany` per order, and three accessors already aggregate correctly across every row on an order — `Order::getTotalPaidAttribute()`, `Order::getTotalRefundedAttribute()`, `Order::getBalanceDueAttribute()`. The redesign does not need to change how money is stored.

What's broken is the **read/business-logic layer**: roughly 40 call sites across refunds, voids, the Order Details page, Receive Payment, the mobile API, reporting, and the Resolution Center collapse the payment history down to "the last payment" or "the last paid payment" (`Order::lastPayment()`, `lastPaidPayment()`) when the correct behavior is to reason over the full set. This is why a single-card-payment order works fine today and a Card+Cash order silently misbehaves in a dozen different ways depending on which screen you're looking at.

Two concrete, previously-suspected defects are now **confirmed** by direct code reading:

1. **`parent_order_payment_id` is written from the wrong relation.** `RefundPaymentController.php:320` sets it from `$order->lastPayment->id` (status-agnostic "highest id row"), not `$order->lastPaidPayment` (the actual funded payment). On an order's *second* refund, `lastPayment` now resolves to the *first refund row* — so the second refund's `parent_order_payment_id` points at a refund, not the original charge. The audit chain is already silently wrong today for any order with two or more refunds, even on single-payment orders.
2. **`Order::remaining_amount` (`grand_total − total_refunded`) is used as the refund cap**, but on a partially-paid order this is anchored to the wrong base — it should be capped by dollars actually collected (`total_paid − total_refunded`), not the order's full grand total. This has been masked so far because most orders are fully paid before anyone refunds them; it will produce real over-refunds once partial payments are common.

Recommended architecture (detailed in §4): **Option B — a dedicated `order_payment_refund_allocations` table**, with `order_payments.parent_order_payment_id` demoted to a derived convenience column populated *from* the allocation table (not independently). This is the smallest schema addition that fully solves "one refund can span multiple original payments" and "how much of payment X is still refundable," without building the general-purpose ledger (Option C) the codebase doesn't need yet — and which would actually conflict with this project's own established precedent of keeping `CustomerCreditService`/`LedgerBalanceService`/`BillingEngine` as deliberately separate, narrow ledgers (see `customer_credits` migration docblock).

Recommended rollout is four sequential phases (§16), each independently shippable and testable, none of which simultaneously touches payment entry, refunds, receipts, gateways, and reports in one release.

---

## 2. Phase 3A — Single-Payment Assumption Inventory

40 locations were found. Grouped by the mission's audit areas below. `Order::lastPayment` = highest-`id` row, any status. `Order::lastPaidPayment` = highest-`id` row with status `Paid`. Both are single-row `hasOne` accessors built on top of the correctly-`hasMany` `payments()` relation.

### 2.1 Order Details, Refunds, Voids, Card-Fee Retention, Order History/Timeline

| Location | Current Assumption | Risk | Required Future Behavior |
|---|---|---|---|
| `RefundPaymentController.php:320` | `parent_order_payment_id` set from `$order->lastPayment->id` | **Confirmed bug.** On an order's 2nd+ refund, points at the previous refund row, not the original payment — corrupts the audit chain | Must reference the specific original payment being refunded, resolved via explicit selection, not "latest row" |
| `RefundPaymentController.php:194-199` | `$order->lastPaidPayment` treated as "the" payment being refunded; drives gateway-vs-manual branch and the gateway `transaction_id` used | On a mixed/multi-card order, only the most recent Paid row is ever reachable; an earlier card transaction's funds cannot be refunded through this flow at all | Refund must accept/resolve an explicit target `order_payment_id` (or set), with gateway logic keyed to that row |
| `RefundPaymentController.php:60-64` (`resolveRefundCalculation`, CC-fee branch) | Hard-rejects the CC-fee-retained shortcut unless `payments()->where('status','Paid')->count() === 1` | Correctly *blocks* (rather than silently mis-executes) but denies a legitimate single-eligible-card-leg case on a mixed order | Evaluate eligibility per target payment, not per order |
| `edit.blade.php:2601-2637` | Client-side re-derivation of the same single-paid-payment gate for the CC-fee button | Duplicate logic; a server-only fix leaves the button wrongly disabled | Same fix, single source of truth (server-supplied per-payment eligibility) |
| `VoidPaymentController.php:40-58` | `$payment = $order->lastPaidPayment`; `VoidRequest` has no payment identifier at all | A genuinely voidable same-day Card payment that isn't the *most recent* Paid row is unreachable — Void silently targets the wrong (or no) row | Void must target an explicit `order_payment_id`, validated for voidability independently of recency |
| `edit.blade.php:99-121` (`$canVoid`) | Voidability + the Void button's `data-amount` computed from `lastPaidPayment` only; exactly one Void button per order | Same blind spot, plus no UI mechanism exists to pick *which* payment to void | Per-payment Void action (one control per voidable row) |
| `VoidPaymentController.php:86-95` vs `:122-132` | The "already voided upstream, sync locally" path omits `order_payment_id` on the history row; the normal success path includes it | Two code paths for the same action inconsistently link back to the payment; ambiguous history once an order has multiple void events | Every `TransactionVoided` history row must set `order_payment_id` |
| `PaymentConfirmedListener.php:21-29`, `PaymentAddedToAccountListener.php:22-30` | Both have `$payment` in scope but omit `order_payment_id` on the history write | "Payment confirmed"/"Added to account" timeline rows can't be traced to which payment once an order can have more than one COD/pending row | Add `order_payment_id` to both writes, matching `RefundInitiateListener` |
| `Order.php:337-340` (`getIsPaidAttribute`) | `payments()->where('status','Paid')->exists()` — requires **one row** individually marked `Paid` | Contradicts the order's own correctly-summed `total_paid`: two `PartialPayment` rows that together equal `grand_total` never flip `is_paid` true. Drives the "Paid in Full" badge and Void/Refund visibility | Derive from `total_paid >= grand_total` (or `isSettled()` over the set), not a single row's status |
| `Order.php:325-335` (`last_payment_type`/`last_payment_status`) | Both `$appends`-ed accessors, derived solely from `lastPayment` (any status, including refund/void rows) | **The single most-consumed leak point in the codebase** — ~20 additional call sites outside this "core" list alone (scheduling/dispatch boards, schedule conflicts, CRM customer tabs, customer-facing dashboard, checkout thank-you page) display order payment state from one row | Needs a multi-payment-aware order-level payment summary; every consumer migrates to it (see §12) |
| `edit.blade.php:81-88` | Header "Paid In Full Via –" badge renders `last_payment_type->label()` | Mixed-method order shows only whichever method was entered last | Composite label ("Cash + Card") or full breakdown, sourced from `payments()` |
| `edit.blade.php:2838-2847` | Refund modal only offers "Card" as a refund destination `if ($order->last_payment_type === Card)` | Card option vanishes if Card wasn't the *final* payment, even though a refundable card charge exists | Offer Card whenever any eligible Paid Card row exists, or whenever the *target* payment is Card |
| `edit.blade.php:2999-3093` (Payment Details modal) | `$pdPayment = $order->lastPaidPayment ?? $order->lastPayment` — entire modal (status, method, date, employee, card/check reference, note) built from one row | Earlier payments' details (card last-4, auth code, transaction id, cheque #, note, employee) are invisible once more than one payment exists | Modal must enumerate all `payments()` rows |
| `PaymentDescriptionPresenter.php:117-130` (`termsLabel`) | Reads `last_payment_type` to decide whether "Pay on Delivery" terms text shows | A failed/refund/void row landing last (by id) can flip the terms label though it has nothing to do with payment terms | Derive from the order's original/COD-establishing row, not the newest row |
| `ConfirmPaymentController.php:32`, `AddToAccountPaymentController.php:30` | Both `payments()->pending()->cod()->latest('id')->first()` | Two pending COD rows on one order → the older is permanently orphaned with no UI path to resolve it | Target a specific `order_payment_id` once multiple pending COD rows can coexist |
| `OrderPayment.php` (whole model) | No `parentPayment()`/`childRefunds()` relations defined despite `parent_order_payment_id` existing and being written to | No model-layer way to traverse the payment/refund graph — exactly how finding #1 went unnoticed | Add self-referencing relations (see §5) |
| ~20 view/report call sites (`schedule_assignment`, `dispatch`, `schedule_conflicts`, CRM `_tab_orders`/`_tab_dashboard`, `front/checkout/thankyou.blade.php`, `front/customer/dashboard/order_view.blade.php`) | All render order payment status/method via `last_payment_type`/`last_payment_status` | The `lastPayment` leak is not contained to "core" screens — it's app-wide | One order-level payment-summary accessor/service, migrate every call site to it rather than patching `lastPayment` in place |

### 2.2 Receive Payment, POD, Online Payment Links, Website Checkout, Mobile API, Dashboard Quick Payment

Confirmed routing: the Order Details "Receive Payment" modal submits to `ReceivePaymentController::__invoke()` (`PUT orders/{unique_id}/receive-payment`) — a separate single-action controller, not part of `EditController`.

| Location | Current Assumption | Risk | Required Future Behavior |
|---|---|---|---|
| `Api/Admin/V1/Orders/PaymentController.php:43` | `$amount = $order->grand_total` — mobile "record payment" always charges/records the **full order total** | **Confirmed double-payment risk**, not theoretical: any prior payment on the order (admin manual payment, prior partial, another mobile submit) means this endpoint overcharges/over-records by the full total again | Compute from `grand_total − total_paid` (or a caller-supplied tender amount capped at remaining balance), same as `ReceivePaymentController` |
| `ReceivePaymentController.php:124-128` and `Api/.../PaymentController.php:106-110` | Gateway-failure `DB::rollback()`/error-return is **commented out**; falls through, commits an `order_payments` row anyway, returns success | A declined card charge is recorded as a real payment and reported as success | Failure must roll back and return an error for any tender line, gateway or manual, never commit or report success |
| `ReceivePaymentController.php` (whole controller) | One `payment_type` per submit — one tender line per modal session | Not unsafe today, but the concrete blocker for split-tender entry; no concept of a multi-line "payment session" | Multi-line tender API: N `{method, amount}` lines validated together against remaining balance before any gateway call (§10) |
| `ReceivePaymentController.php:88-224`, `Api/.../PaymentController.php` | `idempotency_token` submitted by the modal is used **only** to namespace the Store Credit redemption key — never written onto the `order_payments` row itself, no `Cache::lock` | Double-click/retry of Cash/Card/Cheque/TapToPay/Other has **zero** duplicate protection today (unlike the refund flow, which already has this) — can double-create a Paid row or double-charge a card | Adopt the exact `RefundPaymentController` pattern: lock + persisted token + DB-unique backstop, for every method |
| `Dashboard/PaymentStoreController.php` (whole controller / `PaymentStoreRequest`) | **No `idempotency_token` field at all** | Dashboard "quick payment" (including live gateway card charges) has zero duplicate-submit protection whatsoever | Same idempotency pattern before any gateway call or ledger write |
| `Dashboard/PaymentStoreController.php:139-264,324` | `StoreCredit` is a selectable method but `CustomerCreditService::redeem()` is **never called** in the main charge-paid flow (unlike `ReceivePaymentController`/API `PaymentController`, which both call it) | Selecting Store Credit here marks the charge paid but never decrements the customer's real credit balance — violates "cash means cash" | Must call `CustomerCreditService::redeem()` identically to the other two entry points |
| `OrderPaymentMethod` enum / all entry controllers | `GiftCard` is selectable everywhere `StoreCredit` is, but **no backing service exists** (confirmed by repo-wide search — no `GiftCardService`, no balance ledger) | Gift Card tenders are trusted at face value with no verification, unlike Store Credit | Either build a real `GiftCardService` mirroring `CustomerCreditService`, or explicitly document Gift Card as unverified/manual |
| `RefundPaymentController.php:194-195,199,320` | Same as §2.1 — repeated here because it's also a payment-entry/refund-boundary issue: `RefundRequest` has no `payment_id` field, so the employee never selects which tender to refund | Once multi-tender exists, refund always targets the single most-recent *paid* row | Explicit target `order_payment_id` on refund requests |
| `edit.blade.php` header + `Order.php:325-335` | Status banner text ("Paid In Full Via – {method}") from `last_payment_type`; displayed *amount* correctly aggregates all rows | Method label and amount become inconsistent the moment >1 method is used (e.g. "Paid In Full Via – Card · $100.00" when it was $50 Cash + $50 Card) | Method label from the full tender-line set |
| `Front/Checkout/OrderPaymentController.php:36-119` (POD/online link) | `balance_due` is correctly aggregate-computed, but no lock/idempotency around check→gateway→write; no re-check immediately before the DB write | Double-submit or a race with a concurrent admin manual payment can double-charge the full balance | `Cache::lock` + idempotency token, same pattern, plus a locked re-check of `balance_due` immediately before the write |
| `OrderPaymentController.php:70-73`, `PodPaymentLink.php` | `pod_status` is a single flat enum per order; nothing re-verifies it isn't already `Completed`, nothing locks the order against a concurrent admin payment | Customer can pay a POD link at the same moment an admin records a manual payment — worst case both succeed | Shared per-order lock across POD/online/manual entry points |
| `Front/Checkout/PostController.php:384-508` | Always charges `grand_total` — safe *today* only because the order is brand-new at this point and cannot have prior payments | Low risk now; same pattern as the mobile API bug if this code is ever reused for an existing order | No change needed now; flag if reused for reorders |
| `AddToAccountPaymentController.php:58-77` | Iterates order products and pushes the *full* product subtotal to the customer's Account balance, independent of `order_payments` already collected | If the order had a partial payment via another channel before conversion, the full subtotal is still pushed — double-counts the already-collected portion | Net out `total_paid` before pushing to Account |

### 2.3 Receipts, Store Credit, Gift Card, Extensions/Fuel/Damage, Customer Accounts, CRM Invoices, Resolution Center

| Location | Current Assumption | Risk | Required Future Behavior |
|---|---|---|---|
| `Receipt.php` fillable + `2026_07_13_193529_add_canonical_payment_methods.php` | `receipts.payment_method` is a single nullable enum column | A split-tender order can never be represented on its own receipt row | Multi-tender breakdown (child rows or always-live computation), not a stored scalar |
| `ReceiptService.php:33-48` (`getOrCreateReceipt`) | Snapshots `payment_method` from `lastPayment` only | Stored receipt method reflects only whichever payment was recorded most recently | Snapshot (or compute) the full per-tender breakdown |
| `ReceiptService.php:156-165` (`currentPaymentMethodLabel`) | Live label from `lastPaidPayment ?? lastPayment` | Collapses every multi-tender order to one line on *every* receipt render (PDF + email) | Route through `paymentMethodBreakdown()` (next row) |
| `ReceiptService.php:177-192` (`paymentMethodBreakdown`) | **A correct multi-method breakdown already exists** but is explicitly documented as "not wired into the live receipt view yet, per Phase 2 scope" | Dead code — the fix is half-built | Wire into `print_receipt.blade.php` |
| `print_receipt.blade.php:403-418` | Renders exactly one `Payment Method: {label}` line, no loop | Printed/emailed receipt never itemizes split-tender orders | Loop over `paymentMethodBreakdown()` |
| `print_receipt.blade.php:426-466` (totals) | No "Refunded"/"Net Paid" line anywhere, despite `total_refunded`/`total_paid` being multi-payment-safe already | Refunded order's receipt still shows the original total with no indication money was returned | Add a Refunds section + net "Amount Paid" line |
| `BillingEngine.php:87-98` (`markPaid`), `Dashboard/PaymentStoreController.php:403-412,438` | A `BillingCharge` (fuel/damage/extension) has one binary paid/not-paid state; *any* payment against it flips it fully paid regardless of amount | A $10 payment against a $150 fuel charge marks it "Paid" — no partial-settlement tracking, no link from the charge to which payment(s) actually paid it | Compare cumulative applied payments to the charge amount before flipping status; add a charge→payment(s) link |
| `Invoice.php:29` fillable + `Invoice/UpdateController.php:42-58` | `invoices.payment_method`/`paid_amount` are flat scalars, directly overwritten by manual-edit form input, independent of the real `customer_accounts` ledger | Manual invoice edit can desync from the ledger it's supposed to summarize | Route through `InvoiceCalculationService::recomputeSummary()` (already used correctly by `Invoice/PaymentStoreController`), never accept raw form values |
| `ResolutionCenterService.php:166` (`startCase`) | Snapshots `payment_method_snapshot` from `last_payment_type` — one value | Incomplete audit trail for a split-tender order, in exactly the framework meant to make refund decisions auditable | Snapshot the full per-tender breakdown |
| `ResolutionCenter/CancellationRefundScenario.php:54-60` | Guided question asks "What payment method **was** used?" as a single-select | Employee must arbitrarily pick one method for a split-tender order; the entire recommendation is driven by that one guess | Multi-select, or auto-derive from `paymentMethodBreakdown()` instead of asking |
| `ResolutionCenter/ResolutionDecisionEngine.php:33-62` (`recommend`) | Branches on one `?string $paymentMethod` param | Same downstream consequence — a mixed order gets a single Card-shaped or non-Card-shaped recommendation for its entire refund | Accept a per-tender amount map, produce a recommendation set |
| `CustomerCreditService`, `CustomHelper::getAvailableCredit/updateCreditBalance/reverseTransactionEffect/fixTheRunningBalance` | *(Contrast — correct pattern.)* All correctly sum the full ledger, never pick a single "last" row | None — cited as the template to follow | Use this sum-over-rows pattern for every fix above |

**Receipt-specific answers:** the receipt template shows exactly one payment-method field, not a per-tender list; it shows no refund section at all; and print + email are structurally guaranteed to match (both call `ReceiptService::getOrCreateReceipt()` → the same `print_receipt.blade.php` via DomPDF, email just attaches the generated PDF) — so there is no drift risk between channels, only the shared single-payment limitation.

### 2.4 Sales Tax Report, Payment Reconciliation Ledger, Sales/Store Reports, APIs/Exports

| Location | Current Assumption | Risk | Required Future Behavior |
|---|---|---|---|
| `PaymentReconciliationLedger.php:87-91` (`streamA`) | Joins to `order_payments` via `op.id = (SELECT MAX(op2.id) ...)` and attributes the **entire `grand_total`** to that one row's method | For a split-payment order, only the last method is reported and it absorbs the whole total — both undercounts the true methods and overcounts the last one. Directly contradicts the class's own "one row per payment event" docblock, and is inconsistent with `streamB` (refunds), which already does this correctly | Emit one row per `order_payments` row, each carrying only its own amount — mirror `streamB` |
| `SalesReportingService.php:105-152` (`applyFilters`, used by **every** report via `baseQuery()`) | The `paid`/`pod`/`account` filter is the same `MAX(id)` single-row pattern | Every report built on `baseQuery()` (Sales Report V2, Store Sales, Product Ranking/Performance, Sales Trend, Employee Performance, Pure Sales Summary) classifies an order's payment status from whichever row is most recent — orders can silently disappear from or wrongly appear in "Paid"/"POD"/"Account" views | Determine `payment_status` from an aggregate over all rows |
| `EmployeePerformanceEngine.php:215-221` | Same `MAX(id)` pattern, independently reimplemented | Employee POD-conversion metrics inherit the same misclassification, and now there are two divergent implementations to keep in sync | Same fix; ideally one shared resolver instead of duplicating the SQL |
| `SalesTaxReportEngine.php:39-83` | `whereHas('lastPayment', ...)`/`whereRelation('lastPayment', 'payment_method', ...)` scopes and filters the report; displayed tax/subtotal are the whole order's totals | A split-tender order's full tax is attributed to whichever payment was last; filtering by "Cash" drops an order that was partly paid in cash | Split tax/subtotal proportionally across contributing payment rows before applying method filters |
| `PaymentReconciliationLedger.php:438-483` | Payment-method lookup tables (`paymentMethodOptions`, `paymentMethodOrder`, `mapOrderPaymentMethod`) only enumerate `Card/Cash/Cheque/COD/Other/billing_engine` | `TapToPay`/`StoreCredit`/`GiftCard`/`ZelleVenmo` rows fall through to `'Other'`/undefined sort order and are **absent from the filter dropdown entirely** — Store Credit/Gift Card redemptions are invisible as a distinct bucket | Extend lookup tables to cover all canonical `OrderPaymentMethod` cases; treat credit-like methods as a distinguishable, non-cash revenue stream |
| `OrderPayment.php` (`cc_fee_retained` column) + all report files | Column is real, cast, and populated (by `RefundPaymentController`) but **never selected, summed, or surfaced by any report** | Retained card-processing fees are invisible to every report; finance cannot reconcile "refunded to customer" vs "fee kept" from any export | Add `cc_fee_retained` to the Reconciliation Ledger's refund stream and any refund-focused export |
| `Api/.../ListResource.php:34,45-52,60,90-105` | `payment_type`/`payment_status`/`payment_type_label`/`payment_status_label`/`paymentReferenceForApi()` all derived from one row; only `amount_paid`/`balance_due` are true aggregates | Mobile/API clients cannot see "Cash + Card" — only whichever payment was last-paid/last-recorded, including its single card-last-4/check-# reference | Add a full `payments[]` array; keep the scalar fields as a documented "primary/most-recent convenience" only |
| `Api/.../IndexController.php:40-42,56` (and admin equivalent, `IndexController.php:86,91`) | `status`/`payment_method` filters via `whereHas('lastPayment', ...)` | Searching "Cash" misses an order where Cash was an earlier, non-last payment | Filter via `whereHas('payments', ...)` (any matching row) |
| `PaymentReconciliationLedger.php` (whole class) | No gateway/processor transaction table is joined; "reconciliation" is purely internal, and (per the `streamA` bug) collapses to one row per order | Cannot verify N gateway transactions on one order match N ledger rows today, because only 1 synthetic row exists per order | Once `streamA` emits one row per payment, join each against its own gateway reference |

Store-level and trend reports don't touch `order_payments` directly for revenue (they sum `order_products`), so they don't duplicate order totals themselves — but they inherit the `payment_status` single-row-filter defect from `SalesReportingService::applyFilters()`, which decides whether an order is in scope at all.

---

## 3. Phase 3B — Current Schema Analysis

### 3.1 `orders` (payment-relevant columns)

`grand_total`, `subtotal`, `tax_amount` — the order's own totals, never mutated by a payment or refund. `invoice_id` (nullable FK to `invoices`), `receipt_status`. No payment/refund state is stored directly on `orders` — everything is derived live from `payments()`. This is correct and should stay this way.

### 3.2 `order_payments` — the core ledger, `hasMany` per order

One row represents either an **original payment** or a **refund/void event** — both live in the same table, distinguished by `status`. Full column inventory as it exists today, assembled from all 16 migrations that have touched this table:

| Column | Type | Purpose | Reliability |
|---|---|---|---|
| `id` | bigint PK | | Reliable |
| `parent_order_payment_id` | bigint, nullable, FK → `order_payments.id`, `onDelete('set null')` | Intended to link a refund row to its original payment | **Unreliable today** — written from `Order::lastPayment` (§2.1 finding #1), not the actual target; wrong on any order's 2nd+ refund |
| `unique_id` | string, unique | `ORD-PAY-*` | Reliable |
| `order_id` | FK → `orders`, cascade delete | | Reliable |
| `payment_method` | enum (native MySQL ENUM) | `COD, Account, Card, Cash, Online*, Cheque, Other, TapToPay, StoreCredit, GiftCard, ZelleVenmo` (*`Online`/Bank Transfer is `@deprecated`, excluded from `canonical()`) | Reliable as a per-row fact |
| `payment_datetime` | datetime, nullable | | Reliable |
| `refunded_at` | timestamp, nullable | Added 2026-06-21, backfilled from `payment_datetime`/`created_at` for historical refund rows | Reliable going forward; historical backfill is a best-effort approximation |
| `voided_at` | timestamp, nullable | Added 2026-06-29 | Reliable |
| `transaction_id` | string, nullable | Original charge's gateway transaction ID **and historically also used for refund transaction IDs** — ambiguous by design until `gateway_refund_id` was added | Reliable for original charges; ambiguous on old refund rows |
| `gateway_refund_id` | string, nullable | Added 2026-06-21, specifically for refund gateway IDs, backfilled from `transaction_id` for historical refund rows | Reliable going forward |
| `card_number`, `card_first_name`, `card_last_name`, `auth_code` | strings, nullable | Card display data | Reliable |
| `customer_profile_id`, `payment_profile_id` | strings, nullable | Authorize.Net CIM profile references | Reliable |
| `cheque_number` | string(50), nullable | Added 2026-01-07 | Reliable |
| `payment_note` | text, nullable | | Reliable |
| `payment_response` | json, nullable | Added 2026-02-18, raw gateway response | Reliable |
| `amount` | decimal(10,2) | Original payment amount | Reliable |
| `status` | native MySQL ENUM, 13 values (see §3.4) | | Reliable per-row, but the value set conflates status and method for the legacy `Invoice*` cases |
| `refund_amount` | decimal(10,2) | Renamed from `refunded_amount` 2025-08-27 | Reliable |
| `tax_refunded` | decimal(10,2), default 0 | Added 2026-06-21 — the tax portion of a refund, previously untracked | Reliable going forward; **0 on every refund row created before this migration** |
| `refund_note` | text, nullable | | Reliable |
| `refund_calculation_type` | string→enum cast, nullable | Added 2026-07-14 — `standard \| card_processing_fee_retained \| sales_tax_only` | Reliable going forward; null on all pre-existing rows |
| `cc_fee_retained` | decimal(10,2), nullable | Added 2026-07-14 | Reliable going forward; null on all pre-existing rows |
| `idempotency_token` | string, nullable, **unique** | Added 2026-07-14 | Reliable, and currently only meaningfully populated by the refund flow (§2.2 — Receive Payment doesn't write it yet) |
| `processed_by_id/name`, `processed_reason_code/label/other` | Added 2026-07-07 | Who physically processed a refund/void + why | Reliable going forward |
| `created_by_*`, `updated_by_*` (morphs) | | Logged-in-user audit (distinct from `processed_by_*`, which is the verified-employee audit) | Reliable |

### 3.3 Original vs. refund rows — how they're distinguished today

**There is no dedicated `type` or `is_refund` column.** A row is a refund purely by virtue of its `status` being `Refunded`/`Partial Refund`/`Voided` — this is workable (the `scopeRefund()`/`scopePaid()` model scopes already encode it) but means every consumer has to know the status taxonomy rather than checking one obvious boolean. **Refunds are separate positive rows**, not negative amounts on the original row — `refund_amount` is stored as a positive number on its own new `order_payments` row, and `parent_order_payment_id` is the (currently broken) pointer back to the original. This is the right shape to build on; it just needs the pointer fixed and, per §4, supplemented with a proper allocation table for the multi-original-payment case.

### 3.4 `status` enum — 13 values, a real landmine for any "sum settled payments" logic

```
Pending, Paid, Account, Partial Refund, Refunded, Failed, Partial Payment,
Invoice Card, Invoice Cash, Invoice Online, Invoice Cheque, Invoice Other, Voided
```

The five `Invoice*` values fuse status and method (`"Invoice Cash"` means *paid, via cash, through the legacy Invoice workflow*) — a real design smell, but `OrderPaymentStatus::isSettled()` (`isPaid() || isInvoice()`) and `impliedMethod()` already exist specifically to unwind this fusion, and should become the canonical "did this row contribute real settled money" check everywhere, replacing the scattered hardcoded `where('status', 'Paid')` filters found throughout §2's inventory (this is also the direct fix for the `getIsPaidAttribute()` bug in §2.1).

### 3.5 `order_histories.order_payment_id`

Added 2026-07-14, nullable, FK → `order_payments.id`, `nullOnDelete()`. Links a history/timeline entry back to the specific payment it describes. **Used inconsistently today** — `RefundInitiateListener` sets it correctly; `PaymentConfirmedListener` and `PaymentAddedToAccountListener` have the `$payment` in scope but never set it (§2.1); `VoidPaymentController`'s two code paths for the same action disagree on whether they set it. This column is the right foundation for a multi-payment timeline (§12) — it just needs consistent adoption everywhere a history row is tied to a specific payment.

### 3.6 Gateway representation

There is **no dedicated gateway-transaction table**. Gateway state lives entirely as columns on `order_payments` (`transaction_id`, `gateway_refund_id`, `auth_code`, `payment_response` JSON, `customer_profile_id`/`payment_profile_id`). `AuthorizeNetService` is a stateless service class (`refundOrder(string $paymentId, float $amount, array $options)`, `voidOrder(string $paymentId, ...)`, `getTransactionDetails(string $paymentId)`, etc.) — it does not persist anything itself; every call site is responsible for writing the result onto an `order_payments` row. This is adequate for the proposed design (§8) as long as every gateway call is scoped to one `order_payments` row (original or allocation), which is exactly what §5's new table enables.

### 3.7 `receipts` / `receipt_items`

`receipts.payment_method` is a single nullable native enum column (`cash, card, online, cheque, other, tap_to_pay, store_credit, gift_card, zelle_venmo` after the 2026-07-13 canonical-methods migration) — one method per receipt row, snapshotted at receipt-creation time. `receipt_items` is unrelated to payment allocation — it's a line-item table (`type: charge|discount|refund|order`) for what was purchased/adjusted, not how it was paid. Receipts have `order_id` (nullable FK) and `invoice_id` (nullable FK), so a receipt can represent either an order or a CRM invoice payment.

### 3.8 `customer_credits` (Store Credit ledger — Phase 3.0 of the separate Customer Credit Platform initiative)

One row per grant or redemption event (`type: grant|redemption`), `amount`, `idempotency_key` (unique), `order_id` (nullable FK, added Phase 3.2). **No `order_payment_id` column** — a Store Credit redemption used as a tender line on an order has no FK back to the specific `order_payments` row it funded, only to the order as a whole. This is a real gap for the proposed allocation model (§5 proposes closing it).

### 3.9 `customer_accounts` (CRM billing ledger — a *separate* system from `order_payments`)

`type: charge|payment|discount|credit|debit|refund`, `payment_type` enum, `amount`, `sales_tax` (a **rate**, not a dollar amount — documented unit-mismatch risk in `docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md`), `order_id` (nullable FK), `invoice_id` (added later). This ledger is for CRM-level customer billing (invoices, account charges), distinct from order-level payments — the two systems currently only connect via each row's optional `order_id`. Out of primary scope for this redesign except where §2.3 flags direct interactions (Account conversion, invoice payment display).

### 3.10 `billing_charges` (fuel/damage/extension charges — the newer `BillingEngine`)

`amount`, `tax_amount`, `tax_type`, binary `status` (`pending`/`paid`), `paid_at`, `customer_account_id` (legacy bridge FK), `idempotency_key` (unique). No link to the specific `order_payments`/`customer_accounts` row(s) that paid it — confirmed gap, flagged in §2.3.

### 3.11 Summary: what's reliable vs. what needs fixing before Phase 3.1 can build on it

**Reliable, build on directly:** `payments()` hasMany relation itself; `amount`/`refund_amount`/`tax_refunded`/`cc_fee_retained` on individual rows; `total_paid`/`total_refunded` aggregate accessors; `order_payment_id` on `order_histories` (schema is right, adoption is incomplete); `idempotency_token` + its unique constraint (schema is right, adoption is incomplete outside refunds).

**Needs fixing as part of this redesign, not worked around:** `parent_order_payment_id`'s write path (§2.1 #1); `Order::remaining_amount` used as a refund cap on partial-payment orders (§1, §6); `getIsPaidAttribute()`'s single-row status check; the `Invoice*` status/method fusion wherever a plain `=== 'Paid'` check is used instead of `isSettled()`.

---

## 4. Phase 3C — Recommended Allocation Architecture

### 4.1 Option comparison

| | **A — Direct Parent Link** | **B — Refund Allocation Table** | **C — General Allocation Ledger** |
|---|---|---|---|
| Schema change | None (column exists) | +1 table | +1-2 broad tables |
| One refund, one source payment | ✅ | ✅ | ✅ |
| One refund spanning multiple original payments | ❌ impossible without multiple refund rows for what the customer experiences as one refund | ✅ native | ✅ native |
| Per-payment remaining-refundable balance | Only by re-deriving from scattered `parent_order_payment_id` pointers (fragile, exactly the bug we found) | ✅ direct query | ✅ direct query |
| Per-allocation gateway status (partial success across multiple card transactions) | ❌ no place to store it | ✅ native (`status` per allocation row) | ✅ native |
| Extends to future payment-to-order / credit / transfer allocation | N/A | Not designed for it, but doesn't block adding a separate table later | ✅ by design |
| Conflicts with existing architectural precedent | — | — | **Yes** — this codebase has already deliberately kept `CustomerCreditService` (asset ledger), `LedgerBalanceService`/CRM `customer_accounts` (debt ledger), and `BillingEngine`/`billing_charges` (charge ledger) as separate, narrow-scope systems specifically to avoid one shared mega-ledger (see `customer_credits` migration docblock). A general allocation ledger would fight that precedent for no current business requirement. |
| Complexity vs. requirement fit | Under-fits (can't do the thing the mission explicitly asks for) | **Fits exactly** | Over-fits — speculative scope ("future transfers and adjustments") the mission doesn't ask for |

### 4.2 Recommendation

**Option B, with Option A demoted to a derived convenience column rather than removed.**

- Add `order_payment_refund_allocations` (§5.2) as the **single source of truth** for "which original payment(s) funded this refund and how much." Every refund writes at least one allocation row — even the common single-source case writes exactly one row, so there is only ever one code path, not a fast-path/slow-path split (the project's own audit history — the CRM billing ledger's four independently-drifting tax-inclusion implementations — is a direct cautionary example of what happens when a "simple case" and a "general case" are computed by two different pieces of code).
- Keep `order_payments.parent_order_payment_id`, but stop writing it independently. Populate it automatically at refund-creation time **from** the allocation rows just written: if exactly one allocation row was created, set `parent_order_payment_id` to that allocation's `original_order_payment_id`; if more than one, leave it `null` (a `null` here becomes a meaningful signal — "this refund is split, consult the allocation table" — rather than an error state). This keeps simple single-source refunds fast to query (`OrderPayment::find($refund)->parent_order_payment_id`) without maintaining two independently-computed facts.
- Do **not** build Option C now. If a genuine cross-ledger allocation need emerges later (e.g., Store Credit and order payments needing a shared transfer record), it should be its own explicitly-scoped table at that time, consistent with how `customer_credits` was deliberately kept separate from `customer_accounts`.

---

## 5. Proposed Schema & Relationships

### 5.1 `order_payments` — fixes and additions (no destructive changes)

| Change | Detail |
|---|---|
| Fix `parent_order_payment_id` write path | Application-layer fix (§4.2) — populate from allocations, not `lastPayment`. No schema change. |
| Add `payment_session_id` | `char(36)`, nullable, indexed. Groups multiple `order_payments` rows entered together in one Receive Payment submission (§10) — one UUID per session, generated client-side exactly like the existing `idempotency_token` pattern. Not a new table: a session is just "all rows sharing this value," which is enough for display grouping, idempotency scoping, and a single timeline entry ("2 payments recorded — Cash $300, Card $500"). |
| Add `parentPayment()` / `childRefunds()` relations to the model | `parentPayment(): BelongsTo` (self-referencing on `parent_order_payment_id`), `childRefunds(): HasMany` (inverse). Closes the "no model-layer way to traverse the refund graph" gap found in §2.1. |
| Add `refundAllocations()` relation | `HasMany` to the new table, keyed on `refund_order_payment_id` — "which original payments funded *this* refund row." |
| Add `receivedAllocations()` relation | `HasMany` to the new table, keyed on `original_order_payment_id` — "which refunds have drawn against *this* original payment," used for the per-payment remaining-refundable calculation (§6). |

### 5.2 New table: `order_payment_refund_allocations`

```php
Schema::create('order_payment_refund_allocations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('refund_order_payment_id')
        ->constrained('order_payments')->cascadeOnDelete();
    $table->foreignId('original_order_payment_id')
        ->constrained('order_payments')->restrictOnDelete(); // never allow the funded payment to vanish out from under an allocation
    $table->decimal('allocated_amount', 10, 2);
    $table->decimal('allocated_base_amount', 10, 2)->default(0);
    $table->decimal('allocated_tax_amount', 10, 2)->default(0);
    $table->decimal('processing_fee_retained', 10, 2)->nullable();
    $table->string('gateway_transaction_id')->nullable(); // this allocation's own gateway refund id, when it required a separate call
    $table->enum('status', ['pending', 'succeeded', 'failed'])->default('pending');
    $table->string('failure_reason')->nullable();
    $table->timestamps();

    $table->index(['original_order_payment_id', 'status']);
});
```

One row per (refund row × original payment it draws from). A simple single-source refund produces exactly one row. A refund spanning two card payments (§8's worked example) produces two rows, each independently tracking its own gateway call and status — this is what makes partial gateway success/failure representable at all (§8).

### 5.3 Closing the Store Credit traceability gap

```php
Schema::table('customer_credits', function (Blueprint $table) {
    $table->foreignId('order_payment_id')->nullable()
        ->after('order_id')->constrained('order_payments')->nullOnDelete();
});
```

Mirrors the `order_histories.order_payment_id` pattern. Lets a Store-Credit-funded tender line (an `order_payments` row with `payment_method = StoreCredit`) be traced precisely to the `customer_credits` redemption event that funded it — needed so a refund back to Store Credit can identify and reverse the correct ledger entry, and so `getAvailableCredit()`-style balance checks can be cross-verified against actual order tenders.

### 5.4 Deferred: Gift Card

No schema change proposed for Gift Card in this phase — §2.3 found there is no real balance ledger behind it today (unlike Store Credit). Building `GiftCardService` + a ledger table is real, separable work; bundling it into the payment-allocation migration would blur two unrelated schema changes into one risky release. Recommend scoping it as its own follow-up once a business owner confirms Gift Card needs real balance tracking (see §19).

### 5.5 Entity-relationship summary

```
orders 1───* order_payments *───1 order_payments        (self-referencing: parent_order_payment_id,
                    │                                     derived from allocations, §4.2)
                    │
                    ├──* order_payment_refund_allocations *──┐
                    │        (refund_order_payment_id)       │
                    │                                        │ (original_order_payment_id)
                    └────────────────────────────────────────┘
                    │
                    └──1 customer_credits.order_payment_id   (nullable, new FK)

order_payments 1───* order_histories   (order_payment_id, existing column, adoption fixed)
```

No changes proposed to `orders`, `receipts`, `receipt_items`, `billing_charges`, or `customer_accounts` structure in this phase — those are read-layer/UX changes (§10-§14), not schema changes.

---

## 6. Phase 3D — Canonical Balance Formulas

The mission's own draft formula for "Order Balance" conflates two genuinely different questions the current codebase (correctly) already keeps separate — **how much is still owed** vs. **how much can still be refunded**. Recommendation: keep them separate and name them distinctly, rather than merging into one "Order Balance." A third, currently-missing figure (**Net Paid**) is added to satisfy Phase 3G's "Paid $1,000 / Refunded $200 / Net paid $800" example.

### 6.1 Collection Balance (what the customer still owes)

```
Collection Balance Due = max(0, grand_total − Settled Payments)

Settled Payments = Σ order_payments.amount
                    WHERE status.isSettled() is true   (Paid, Partial Payment, or any Invoice* status)
```

Same shape as today's `getBalanceDueAttribute()`, **fixed** to use `isSettled()` instead of the current hardcoded `[PartialPayment, Paid]` list — the fix required so a legacy `Invoice Cash` row correctly counts as settled money (today it silently doesn't, per §3.4). Pending, Failed, and Voided rows never count. Refunds do **not** reduce Settled Payments — a refund doesn't mean the customer owes the order again; that's a separate dimension (§6.3, §9).

### 6.2 Remaining Refundable Amount — per payment (the corrected formula)

```
Remaining Refundable (payment X) =
    X.amount
    − Σ(order_payment_refund_allocations.allocated_amount WHERE original_order_payment_id = X.id AND status != 'failed')
    − (X.amount if X.status == Voided else 0)
```

A voided payment's full amount is immediately non-refundable (it was already reversed at the gateway). A `failed` allocation does not consume refundable balance — it must be retryable (§8, §17).

### 6.3 Overall Order Refundable Balance — the confirmed-bug fix

```
Order Refundable Balance = max(0, Total Settled Payments − Total Successful Refund Allocations)
```

**This replaces `grand_total − total_refunded`.** As shown in §1, capping against `grand_total` is wrong for a partially-paid order — a $1,000 order with only $400 collected must never allow refunding more than $400, regardless of what `grand_total` says. On a fully-paid order the two formulas coincide (which is why this hasn't surfaced as a visible bug yet); they diverge exactly when a partial payment exists, which is the entire point of this phase. The system must enforce **both** this order-level cap and the per-payment cap from §6.2 — a refund request must never exceed whichever is smaller.

### 6.4 Net Paid (new — satisfies Phase 3G's display example)

```
Net Paid = Total Settled Payments − Total Successful Refund Allocations
```

Identical formula to the numerator of §6.3, exposed as its own named accessor (`Order::getNetPaidAttribute()`) because it answers a different question for display purposes ("how much of this order's money is currently, actually with the business") rather than "how much more can be refunded."

### 6.5 Handling the specific cases the mission asks about

| Case | Rule |
|---|---|
| Pending payments | Never counted in Settled Payments, Refundable Balance, or Net Paid. Purely informational until they resolve to Paid/Failed. |
| Failed payments | Never counted anywhere. No balance impact. |
| Voided payments | Fully excluded from Settled Payments retroactively (as if it never happened) the moment `voided_at` is set — matches current void semantics (reverses in place). |
| Refunded payments | The **original** row stays in Settled Payments (the customer *did* pay it) — it's the refund allocation, not the original row, that reduces Net Paid/Refundable Balance. This is what makes §6.2's per-payment tracking necessary instead of just decrementing the original row's `amount`. |
| Store Credit | Counted in Settled Payments like any other method once the `customer_credits` redemption succeeds (§5.3 links them) — a Store-Credit tender is real money to the business the moment it's redeemed. |
| Gift Cards | Same treatment as Store Credit for balance purposes today, with the caveat from §5.4 that there's no real balance verification behind it yet — a business-process gap, not a formula gap. |
| Account conversion | `AddToAccountPaymentController` converts unpaid balance into a CRM `customer_accounts` charge — this should net against `Collection Balance Due` at conversion time (the §2.2 fix), not re-derive from product totals. |
| Extension payments | A `billing_charges` row, tracked by the formulas in this section only once `BillingEngine` gains partial-settlement tracking (§2.3) — out of `order_payments`' formulas directly. |
| Overpayments | Not currently prevented anywhere audited. Recommend: allow at Receive Payment (do not hard-block), but display "Overpayment: $X" distinctly rather than a negative/confusing balance — this is a business decision to confirm, not silently assume (§19). |
| Manual adjustments | No dedicated concept exists today (a manual balance correction would currently have to be modeled as an ad-hoc payment or refund). Out of scope for this phase; flagged in §19. |

---

## 7. Phase 3E — Refund Allocation Strategy

### 7.1 Recommendation: the user's stated preference, confirmed as the right call

> Show all original payments and their remaining refundable balances → employee selects the source payment(s) → system auto-suggests a sensible allocation → explicit confirmation required before processing.

This is recommended over pure auto-allocation (original-payment-first, latest-first, card-first, or proportional) for one concrete reason surfaced by the audit: **the Card-Processing-Fee-Retained calculation is payment-specific** (§13) — an automatic rule can't know, without asking, whether the employee wants the fee retained against a specific card leg or not. A silent deterministic rule also removes the accounting judgment call this codebase has consistently required a human to make elsewhere (e.g. Resolution Center's explicit "Approve & Issue" gate for Store Credit).

### 7.2 Worked example (from the mission brief)

```
Order Total: $1,000.00
Card ending 4242    $600.00   (remaining refundable: $600.00)
Cash                 $400.00   (remaining refundable: $400.00)

Refund Requested: $700.00
```

**Auto-suggested allocation** (default shown to the employee, not silently applied): original-payment-order first — draw down the earliest payment first, since it's the simplest mental model and matches how a customer is likely to think about "my first payment"). That suggests Card $600.00 (fully exhausted) + Cash $100.00. The employee sees this pre-filled, can edit either line (subject to each line's own remaining-refundable cap and the order-level cap from §6.3), and must explicitly confirm before any gateway call or write.

### 7.3 UX shape

- A "Refund" step in the existing refund modal expands into a table: one row per **Paid** original payment with a remaining refundable balance > 0, columns: Method, Original Amount, Already Refunded, Remaining Refundable, **Refund From This Payment** (editable amount input, pre-filled by the suggestion).
- Live total of the edited allocation rows must equal the requested refund amount before Confirm is enabled (mirrors the existing modal's live-breakdown pattern already built for the CC-fee/Sales-Tax-Only shortcuts).
- The existing Standard / Card-Processing-Fee-Retained / Sales-Tax-Only shortcuts remain — they now operate *per selected original payment* rather than implicitly against `lastPaidPayment`. A single-payment order (the overwhelming common case today) sees no extra UI — the table has one row and behaves exactly as it does now.

---

## 8. Phase 3F — Gateway Transaction Strategy

### 8.1 Worked example: partial gateway failure

```
Refund $800:
Card Transaction A → $500 succeeds
Card Transaction B → $300 fails
```

### 8.2 Recommendation: one parent refund operation, child allocations with independent status

- The customer-facing **refund row** (`order_payments`, `status = Refund`/`Partial Refund`) represents the whole $800 the employee requested and is created once, up front, but its own `status` is **not** finalized until every allocation resolves.
- Each allocation row (§5.2) tracks its own `status` and `gateway_transaction_id` independently. Card Transaction A's allocation becomes `succeeded`; Card Transaction B's becomes `failed` with `failure_reason` populated from the gateway response.
- The parent refund row's displayed status becomes a **derived** roll-up, not a stored guess:
  - all allocations `succeeded` → refund row `Refund`/`Partial Refund` (fully processed)
  - some `succeeded`, some `failed` → refund row status becomes a new explicit state, **`Partially Processed`** (added to `OrderPaymentStatus`), and the UI must show exactly which allocation failed and why
  - all `failed` → the refund row itself is not created as a settled refund at all; treat as a failed attempt (consistent with the existing all-or-nothing refund's current behavior), safely retryable with the same idempotency token
- **The system must never report "refund completed" while any allocation is still `pending`/`failed`.** This directly satisfies the mission's explicit requirement ("must not falsely report the entire refund as completed").
- Recovery: the failed $300 allocation stays retryable independent of the succeeded $500 one — retrying re-attempts only the failed allocation(s), never re-issues a second gateway call against Transaction A's already-succeeded $500.

### 8.3 Why one parent operation, not N independent visible refund transactions

Showing the employee/customer three separate unrelated "refund transactions" for what was one refund request is confusing and breaks the receipt/timeline narrative ("a $700 refund was processed" is one sentence, not three). A parent-with-child-allocations model gives both: one coherent customer-facing event, and full per-transaction gateway granularity underneath for reconciliation (§14) and safe partial-failure recovery.

### 8.4 Gateway rules carried forward unchanged

- Each refund allocation targets exactly one original payment's own `transaction_id`/`gateway_refund_id` — never a synthesized "the" transaction.
- One allocation must never exceed its original payment's own remaining refundable balance (§6.2) — enforced before any gateway call, not just at the database-write step.
- Duplicate-submission protection (the existing `Cache::lock` + persisted `idempotency_token` + DB-unique-constraint pattern) extends unchanged to the parent refund operation — a retried request with the same token must return the existing (possibly partially-processed) result, never re-attempt a `succeeded` allocation.

---

## 9. Phase 3G — Payment and Refund Statuses

### 9.1 Recommendation: three separate dimensions, not one fused status

The mission's own examples (Partially Refunded vs. Partially Paid vs. Paid in Full with Refund) are exactly the symptom of forcing three independent facts into one field — the existing `Invoice*` status/method fusion (§3.4) is direct evidence of what happens when this isn't kept separate, and it's already caused real ambiguity (`isSettled()`/`impliedMethod()` exist purely to unwind it after the fact). Recommend not repeating that mistake for the multi-payment redesign.

| Dimension | Values | Derived from |
|---|---|---|
| **Collection Status** | `Unpaid`, `Partially Paid`, `Paid in Full` | Settled Payments vs. `grand_total` (§6.1) |
| **Refund Status** | `None`, `Partially Refunded`, `Fully Refunded` | Total Successful Refund Allocations vs. Settled Payments (§6.4) |
| **Balance Status** *(display convenience, not stored)* | Combines the two into the single line the mission's examples want, e.g. *"Paid in Full · Partially Refunded"*, *"Partially Paid"*, *"Paid in Full"* | Pure presentation logic over the two stored dimensions above |

`Order::getIsPaidAttribute()` becomes `collection_status === PaidInFull` (fixing §2.1's bug). The existing per-row `OrderPaymentStatus` enum (`Pending/Paid/PartialPayment/PartialRefund/Refund/Failed/Voided`, plus the legacy `Invoice*`/`Account` values) stays exactly as-is — it correctly describes one *row's* state. What's new is that the *order-level* status shown in the UI stops being "whatever `lastPayment.status` says" and becomes this explicit two-dimension roll-up.

---

## 10. Phase 3H — Receive Payment UX

### 10.1 Recommendation: multi-line tender session, committed together

The mission's own example (Store Credit $200 → Card $500 → Cash $300, remaining balance updating live after each line) is a single **session** with multiple tender lines, not three separate modal round-trips. Recommend:

- One `payment_session_id` (§5.1) generated client-side when the modal opens, shared by every `order_payments` row created in that session — mirrors the existing `idempotency_token`/refund-modal UUID pattern already established.
- Each tender line is validated against the **live remaining balance** (starting balance minus every prior line in the same session, not just the order's stored balance at modal-open time) before being added — "no payment exceeds the remaining balance unless overpayment is explicitly supported" (§6.5 flags overpayment as a business decision, §19).
- Gateway calls happen **per line**, in the order entered, exactly as today — a card decline on line 2 must not erase the already-succeeded Store Credit line 1 (this is the exact multi-payment analogue of §2.2's finding that gateway failure currently isn't even handled safely for a *single* line).
- The whole session shares one idempotency lock — a retried submission of the same session must not re-process already-succeeded lines, mirroring §8's refund recovery model.
- One Order History/Timeline entry per line (each carrying its own `order_payment_id`, per §3.5's fix), plus an optional session-level grouping in the UI ("2 payments recorded in this session") for readability — not a new history-table concept.

### 10.2 Why not "one transaction at a time" (reopen the modal per tender)

Reopening the modal per tender is what the system effectively does today by omission, and it's exactly what makes split-tender slow and error-prone for staff (no live running balance across tenders, no single point of confirmation, no natural place to enforce the overall order-level cap before any of the individual gateway calls fire). A session model costs one new nullable column and delivers the UX the mission actually describes.

---

## 11. Phase 3I — Receipt Design

### 11.1 Recommendation

- Wire the **already-written** `ReceiptService::paymentMethodBreakdown()` into `print_receipt.blade.php` (§2.3) — this alone fixes the multi-tender display gap with no new code, just connecting existing code.
- Add a **Refunds** section (method, date, amount, and — for card refunds only — the masked gateway reference) whenever `total_refunded > 0`, plus a **Net Paid** line (§6.4), sourced from `payments()` filtered to refund-status rows. Currently entirely absent (§2.3).
- Gateway references (masked card last-4, cheque number) appear per tender line, matching what's already shown for the single-payment case today — nothing new to design, just applied per-line instead of once.
- Store Credit / Gift Card lines show as their own tender line like any other method (e.g. "Store Credit $150.00") — no internal allocation detail (which original payment a refund was drawn from) belongs on a customer-facing receipt; that's an internal/Order-Details-only concept (§12). Customer-facing receipts should never expose `order_payment_refund_allocations` rows directly.
- Printed and emailed receipts continue sharing the exact same generation path (`ReceiptService::getOrCreateReceipt()` → `print_receipt.blade.php` → DomPDF) — confirmed no drift risk exists today (§2.3), and nothing in this proposal changes that shared path, so the fix applies identically to both channels automatically.

### 11.2 Worked outputs (from the mission brief)

Paid-in-full, multi-method — already achievable once §11.1's wiring lands:
```
Payments:
Credit / Debit Card      $500.00
Cash                     $250.00
Store Credit             $150.00
Gift Card                $100.00
Total Paid:            $1,000.00
Balance Due:               $0.00
```
Refunded — the new section:
```
Payments:               $1,000.00
Refunds:                  $200.00
Net Paid:                  $800.00
```

---

## 12. Phase 3J — Payment Timeline and Order Details

### 12.1 Timeline

Already largely correct in structure (`PaymentDescriptionPresenter::timelineEntry()` iterates `payments()`, not a single row) — the fixes needed are the ones already identified in §2.1/§3.5: every history-writing listener/controller must set `order_payment_id`, and `RefundPaymentController`'s `parent_order_payment_id` must be fixed so refund timeline entries can correctly say "Applied to original card payment" pointing at the *right* payment (the mission's own worked example). No new timeline architecture is needed — this is entirely an adoption/consistency fix on the existing `order_payment_id` column.

### 12.2 Order Details financial summary and Payment Details modal

- The header badge (§2.1) becomes a composite/breakdown, not a single `last_payment_type` label.
- The Payment Details modal (§2.1 — currently built from one row) must enumerate every `payments()` row, each showing its own method/date/employee/reference/note — matching what the Timeline section already does correctly today. This is a UI change, not a data-model change; the underlying data has always supported it.
- The Refund modal's payment-type selector (§2.1) must offer every method with an eligible Paid row, not just whichever was last.

Order Details' "current state" (financial summary) vs. Timeline's "how it got there" distinction the mission asks for already exists structurally in the page — it just needs the summary layer to stop reading from `lastPayment`/`lastPaidPayment` and start reading from the same aggregate accessors (`total_paid`, `total_refunded`, the new §6 formulas, and per-payment enumeration) that the Timeline layer already mostly uses correctly.

---

## 13. Phase 3K — Card Processing Fee Retention

Answering the mission's four specific questions:

| Question | Answer |
|---|---|
| Per original card payment, or per order? | **Per original card payment.** This is a direct consequence of §4/§5 — the fee-retained amount now lives on the allocation row (`processing_fee_retained`), scoped to one `original_order_payment_id`. An order with two card payments could retain a fee against each independently if both are separately refunded. |
| How do prior card refunds affect the eligible amount? | The eligible base for a new fee calculation is that specific card payment's **remaining refundable amount** (§6.2) — i.e., already-refunded dollars (whether or not a fee was retained on that earlier refund) are excluded from the base the fee percentage applies to. This naturally falls out of computing the fee against the allocation's `allocated_amount`, not the original payment's full `amount`. |
| Behavior when only part of the order is being canceled? | Unchanged from today's model, generalized: the fee applies only to the portion of *that specific card payment* being refunded in *this* allocation, not to the whole order or the whole original payment. |
| Can the fee ever apply twice to the same original payment? | **No — this must be an explicit invariant**, enforced by summing `processing_fee_retained` across all of that original payment's existing allocations before computing a new one, and capping so the total retained fee can never exceed (fee % × original full amount). This is a new integrity check that doesn't exist today (today's design only had to consider a single refund against a single-payment order) and should be covered directly in the test matrix (§17). |

The existing "single-Paid-payment orders only" eligibility gate (§2.1) is relaxed to "the specific target payment has no more than one prior fee-retained allocation and sufficient remaining refundable balance" — mixed-payment orders become eligible for the shortcut on whichever of their legs qualifies, closing the gap §2.1 flagged.

---

## 14. Phase 3L — Reporting

Every fix below follows directly from §2.4's findings — no new reporting requirement is introduced, this section is the remediation plan for what was found.

| Report | Current defect | Fix |
|---|---|---|
| Payment Reconciliation Ledger (`streamA`) | Attributes the whole order to one payment row | Emit one row per `order_payments` row (mirror `streamB`, which already does this correctly) |
| Payment Reconciliation Ledger (method lookups) | `TapToPay`/`StoreCredit`/`GiftCard`/`ZelleVenmo` fall through to `'Other'`, absent from filters | Extend `paymentMethodOptions()`/`paymentMethodOrder()`/`mapOrderPaymentMethod()` to cover all canonical methods |
| Payment Reconciliation Ledger (refund stream) | `cc_fee_retained` never surfaced | Add to `streamB`'s output columns |
| Sales Report V2 / Store / Product / Trend / Employee Performance (via `SalesReportingService::applyFilters()`) | `payment_status` decided by one row (`MAX(id)`) | Aggregate over all rows: an order counts as "Paid" once `Collection Status === PaidInFull` (§9), "POD" if it has any COD-origin row, etc. — one shared resolver, not per-report reimplementation |
| Sales Tax Report | Tax/subtotal attributed entirely to `lastPayment`; method filter drops split-tender orders | Split tax/base proportionally across contributing payment rows before filtering (mirrors `PaymentReconciliationLedger::streamB`'s already-correct pattern) |
| Admin + API order list filters | `whereHas('lastPayment', ...)` misses non-last matching payments | `whereHas('payments', ...)` |
| API `ListResource` | Single `payment_type`/`payment_status`/`payment_reference` fields | Add a `payments[]` array; keep the scalars as documented "primary/most-recent" convenience fields only, never remove (backward compatibility for existing API consumers, §15) |

**Explicit anti-duplication requirement** (mission's own words): once `streamA` emits one row per payment, the *sum* of a payment-method report across all methods for one order must still equal that order's own totals — this becomes a concrete, testable invariant (§17) rather than an implicit hope.

---

## 15. Phase 3M — Backward Compatibility

### 15.1 Historical rows: treat as implicitly self-allocated, never fabricate

For every existing refund row (`status` in `Refund`/`Partial Refund`) that has a currently-correct `parent_order_payment_id` (true for **first** refunds on any order — the bug in §1/§2.1 only corrupts the *second and later* refund on a given order) **or** where the order has exactly one Paid original payment (the overwhelming majority of historical orders, per every audit finding above): backfill exactly one `order_payment_refund_allocations` row per historical refund, with `allocated_amount = refund_amount`, `allocated_tax_amount = tax_refunded` (0 for pre-June-2026 rows, per §3.2's reliability note), `allocated_base_amount = refund_amount − tax_refunded`, `status = succeeded`, `gateway_transaction_id = gateway_refund_id`. This is not fabrication — it's a mechanical 1:1 restatement of a fact that was already unambiguous for single-payment orders.

### 15.2 Genuinely ambiguous historical rows

For any order with **multiple** Paid original payments **and** one or more refund rows where `parent_order_payment_id` cannot be trusted (multi-payment orders are rare today per the whole premise of this phase, but they exist wherever a manual correction or an Account/Invoice-flow order created more than one Paid row historically) — **do not guess which original payment funded the refund.** Leave the allocation unbacked and render exactly the mission's suggested fallback:

```
Allocation unavailable for legacy transaction
```

wherever a per-payment refundable balance would otherwise be shown for that specific original payment, and exclude such orders from any new per-payment-refund UI element (the old order-level Standard refund path — unaffected by this whole redesign — remains available for them).

### 15.3 Backfill mechanics

A dedicated one-time backfill Artisan command (not a data migration inside a schema migration file, consistent with this repo's existing convention — see `2026_06_30_000002_backfill_billing_charges_paid_at_and_store_id.php` as precedent for a *separate* backfill migration following the schema change): iterate every historical refund row, apply §15.1's rule where unambiguous, otherwise leave unbacked per §15.2. Idempotent and safely re-runnable (skip rows that already have an allocation).

### 15.4 Read-path fallback

Every new per-payment-refund-balance UI element and formula (§6.2, §7.3, §13) must fall back gracefully to "Allocation unavailable" rather than throwing or silently showing $0 when a payment has no allocation rows *and* the order has more than one original payment (the ambiguous case). A payment with zero allocation rows on a **single**-original-payment order is not ambiguous — it simply has never been refunded, and its full amount is refundable (§6.2's formula already produces this correctly with an empty sum).

---

## 16. Phase 3N — Migration and Rollout

### 16.1 Sequencing review

The mission's own proposed 3.1→3.4 sequencing is sound and is adopted with one adjustment: idempotency hardening for Receive Payment (§2.2's most severe finding — zero duplicate protection on live gateway calls today) is moved *earlier*, into 3.1, rather than waiting for 3.3, because it's a standalone correctness fix with no dependency on the allocation schema and represents real money-risk that shouldn't wait for split-tender UX to ship.

### Phase 3.1 — Allocation Foundation
- `order_payment_refund_allocations` table (§5.2), `customer_credits.order_payment_id` (§5.3), `payment_session_id` (§5.1)
- Fix `parent_order_payment_id` write path (§4.2); add `parentPayment()`/`childRefunds()`/`refundAllocations()`/`receivedAllocations()` relations
- Fix `Order::getIsPaidAttribute()`, add `Order::getNetPaidAttribute()`, fix the refund-cap formula (§6.3 — this alone should ship even before the rest of Phase 3.1, as it's a standalone correctness fix for the confirmed bug)
- **Idempotency hardening for `ReceivePaymentController`, API `PaymentController`, `Dashboard/PaymentStoreController`** — the `Cache::lock` + persisted-token pattern, moved up from 3.3 per above
- Historical backfill (§15.3)
- Backward-compatible reads only — no UI changes yet, existing single-payment flows behave identically

### Phase 3.2 — Accurate Refund Allocation
- Refund modal's source-payment selection UI (§7.3), auto-suggestion + explicit confirmation
- Per-payment remaining-refundable display (§6.2)
- Gateway routing to the correct per-payment transaction, partial-success handling, `Partially Processed` status (§8)
- Card-Processing-Fee-Retained generalized to per-payment eligibility (§13)
- Void generalized to target a specific `order_payment_id` (§2.1)

### Phase 3.3 — Split Payment Entry
- Multi-line tender session UX (§10)
- Store Credit redemption wired into `Dashboard/PaymentStoreController` (closing the §2.2 gap where it's currently silently skipped)
- Gift Card: explicitly scoped as a follow-up decision (§5.4, §19), not bundled here unless a business owner confirms real balance tracking is needed first

### Phase 3.4 — Receipts and Reporting
- Wire `paymentMethodBreakdown()` into receipts + add Refunds/Net Paid sections (§11)
- Timeline/Order Details consistency fixes (§12)
- Reporting remediation (§14) — `streamA` fix, method-lookup extension, `payment_status` aggregate resolver, Sales Tax Report proportional split, API `payments[]` array

### 16.2 Rollback strategy

Every schema addition in this design is purely additive (new nullable columns, one new table) — no existing column is renamed, retyped, or dropped, and no existing enum value is removed. Each phase's migrations have a symmetric `down()`. Because `order_payments`/`order_histories`/`customer_credits` already carry the SQLite-table-rebuild hazard documented in prior phases (combining `ADD COLUMN` + `ADD FOREIGN KEY`/`UNIQUE` in one `Schema::table()` call), every new migration in this plan must follow the same two-call split already established as this repo's convention. If a phase needs to be rolled back after real data has been written (e.g., allocation rows created), rollback is data-preserving only up to dropping the additive columns/table — it does not attempt to reconstruct pre-migration state, consistent with how every prior phase in this codebase has handled rollback.

### 16.3 Why not one release

Each phase above is independently testable and independently valuable — 3.1 alone fixes two confirmed live-money bugs (parent-pointer corruption, refund-cap-vs-partial-payment) with zero UI change and zero user-facing risk. Bundling all four into one release would mean a single regression anywhere (gateway routing, receipt rendering, report totals, or the new UX) blocks shipping the two urgent standalone fixes — exactly the "single high-risk release" the mission explicitly warns against.

---

## 17. Phase 3O — Testing Strategy

All tests create their own records and run against an isolated, current MySQL test database (per the mission's explicit instruction) — consistent with this repo's established Feature-test pattern (`RefreshDatabase`, `$this->mock(AuthorizeNetService::class, ...)`) already used successfully for the refund/idempotency work in the prior phase.

| # | Scenario | Key assertions |
|---|---|---|
| 1 | One payment, standard refund | Unchanged behavior — existing single-payment test suite must still pass unmodified |
| 2 | Multiple payments, same method (2× Cash) | `total_paid` correct; each row independently refundable |
| 3 | Mixed payment methods (Card + Cash) | Order-level method label shows both; `payments()` enumerates both correctly everywhere audited in §2 |
| 4 | Partial payment, not yet full | `Collection Status = Partially Paid`; `is_paid` false even with one `PartialPayment` row |
| 5 | Full payment via split tender | `Collection Status = Paid in Full`; `getIsPaidAttribute()` true from aggregate, not single-row status (regression test for §2.1's confirmed bug) |
| 6 | Overpayment prevention (or explicit allow, per §19's resolved decision) | Behavior matches whatever the business decides — test encodes the decision, not an assumption |
| 7 | Multiple card transactions on one order | Each has its own `transaction_id`; refund can target either independently |
| 8 | Multiple refunds against one order | 2nd refund's `parent_order_payment_id`/allocation correctly targets the original payment, not the 1st refund row (direct regression test for the confirmed bug in §1) |
| 9 | Refund spanning several original payments | One refund row, 2 allocation rows, sum of allocations == refund row's `refund_amount` |
| 10 | Insufficient per-payment refundable balance | Rejected even though order-level balance would allow it (§6.2 cap enforced independently of §6.3 cap) |
| 11 | Store Credit + Card split payment | Both settle; `customer_credits.order_payment_id` correctly links the Store Credit leg |
| 12 | Gift Card + Cash split payment | Recorded per current (unverified) trust model; explicitly not asserting real balance deduction unless §19's Gift Card decision changes scope |
| 13 | Tax allocation across a multi-source refund | `allocated_tax_amount` sums correctly across allocations; Sales Tax Report reflects the split, not one lump sum |
| 14 | Retained card-processing fee, single payment | Unchanged from existing passing tests |
| 15 | Retained card-processing fee, cannot double-apply | Second refund attempt against the same original payment cannot exceed the fee-eligible cap (§13's new invariant) |
| 16 | Gateway partial failure ($500 succeeds / $300 fails) | Refund row status becomes `Partially Processed`; succeeded allocation is never re-attempted on retry; failed allocation is retryable |
| 17 | Duplicate submission — split payment session | Same `payment_session_id`/token twice → no duplicate rows, no duplicate gateway calls (extends the existing idempotency test pattern to multi-line sessions) |
| 18 | Duplicate submission — multi-source refund | Same refund idempotency token twice → allocations not double-created, gateway not double-called |
| 19 | Legacy unallocated refund | Renders "Allocation unavailable for legacy transaction"; does not throw; does not fabricate a value |
| 20 | Receipt — split tender | `paymentMethodBreakdown()` output matches every tender line; Refunds section appears only when `total_refunded > 0` |
| 21 | Receipt — print vs. email parity | Both render from the identical generated PDF — no drift (regression guard, not new risk) |
| 22 | Reports — no revenue duplication | Sum of a split-payment order's rows across every payment-method report bucket equals the order's own `total_paid` (direct test of §14's anti-duplication requirement) |
| 23 | API `ListResource` — `payments[]` array | New array present and accurate; legacy scalar fields (`payment_type`, `payment_status`) still populate for backward compatibility |
| 24 | Rollback / down-migration | Every new migration's `down()` cleanly reverses on a copy of a post-backfill database |

---

## 18. Expected Changed-File List

This is a projection for planning purposes — actual scope will be confirmed at each phase's own implementation-review gate, not committed to in advance.

**New files**
- `database/migrations/orders/*_create_order_payment_refund_allocations_table.php`
- `database/migrations/orders/*_add_payment_session_id_to_order_payments_table.php`
- `database/migrations/customers/*_add_order_payment_id_to_customer_credits_table.php`
- `database/migrations/orders/*_backfill_order_payment_refund_allocations.php` (or an Artisan command, per §15.3)
- `app/Models/Orders/OrderPaymentRefundAllocation.php`
- `app/Services/Orders/PaymentAllocationService.php` (or similar — the balance-formula/allocation-resolution logic from §6-§8, centralized rather than re-implemented per controller, learning directly from the CRM billing ledger's documented mistake of re-implementing the same math 4+ times)
- `app/Services/Orders/OrderPaymentSummary.php` (or a DTO/service for the §9 status roll-up and §2.1's "order-level payment summary" recommendation)
- Tests per §17 (`tests/Feature/Orders/PaymentAllocationTest.php`, `RefundAllocationTest.php`, `SplitPaymentEntryTest.php`, `PaymentReportingAggregationTest.php`, etc.)

**Modified — models**
`app/Models/Orders/Order.php`, `app/Models/Orders/OrderPayment.php`, `app/Models/Customers/CustomerCredit.php`

**Modified — controllers**
`RefundPaymentController.php`, `VoidPaymentController.php`, `ReceivePaymentController.php`, `ConfirmPaymentController.php`, `AddToAccountPaymentController.php`, `Api/Admin/V1/Orders/PaymentController.php`, `Api/Admin/V1/Orders/IndexController.php`, `Admin/Dashboard/PaymentStoreController.php`, `Front/Checkout/OrderPaymentController.php`

**Modified — listeners**
`PaymentConfirmedListener.php`, `PaymentAddedToAccountListener.php`, `RefundInitiateListener.php` (already mostly correct)

**Modified — services**
`ReceiptService.php`, `PaymentDescriptionPresenter.php`, `PaymentReconciliationLedger.php`, `SalesReportingService.php`, `SalesTaxReportEngine.php`, `EmployeePerformanceEngine.php`, `ResolutionCenterService.php`, `ResolutionCenter/ResolutionDecisionEngine.php`, `ResolutionCenter/CancellationRefundScenario.php`

**Modified — views**
`resources/views/admin/order_management/orders/edit.blade.php` (largest single change — header badge, Payment Details modal, refund modal allocation UI, Void per-payment controls, Receive Payment multi-line session), `print_receipt.blade.php`, and the ~10 report/dispatch/CRM partials enumerated in §2.1's last row (migrated to the new order-level payment-summary accessor rather than `last_payment_type`)

**Modified — API resources**
`app/Http/Resources/Api/Admin/V1/Orders/ListResource.php`

**Not modified in this redesign**
`orders`, `receipts`, `receipt_items`, `billing_charges`, `customer_accounts` table structures; `OrderPaymentStatus`/`OrderPaymentMethod` enum value sets (only their *consumers'* logic changes, per §9).

---

## 19. Risks and Unresolved Business Decisions

These require an explicit business-owner decision before or during Phase 3.1/3.2 implementation — none should be silently assumed, consistent with this project's standing rule to surface rather than guess.

1. **Overpayment**: allow and display distinctly, or hard-block at Receive Payment? (§6.5, §10.1) Current code has no consistent answer either way.
2. **Gift Card real balance tracking**: build a `GiftCardService` now (real ledger, parity with Store Credit), or continue treating it as an unverified label field? (§5.4, §16 Phase 3.3) This is a scope decision with real cost either way — recommend deferring the decision itself to a short, separate business conversation, not silently picking one.
3. **Manual balance adjustments**: no concept exists today (§6.5). Is this in scope for Phase 3 at all, or a separate future initiative?
4. **Auto-allocation default rule**: this document recommends original-payment-order-first as the *suggested* default (§7.2) — confirm this matches business/accounting preference before it's hard-coded as the suggestion algorithm (the employee can always override, but the default anchors behavior).
5. **`Partially Processed` as a new order-payment status** (§8.2): confirm this is an acceptable new customer/employee-facing state, and whether it needs its own alerting/follow-up workflow (e.g., should an order with a stuck `Partially Processed` refund surface on some kind of exceptions queue?) — this document proposes the data model for it but does not propose an operational monitoring process.
6. **Legacy ambiguous refunds** (§15.2): confirm "Allocation unavailable for legacy transaction" is acceptable to show to employees indefinitely for old multi-payment orders, versus some manual reconciliation effort to resolve them.
7. **`parent_order_payment_id` fix timing**: because this bug (§1) already affects live data for any order with 2+ historical refunds, confirm whether a corrective backfill pass for *already-corrupted* rows (versus only fixing the write path going forward) is wanted, and whether that's safe to attempt automatically or needs manual review given it would be inferring intent from ambiguous historical data (directly in tension with §15's "do not fabricate" rule — recommend leaving already-corrupted historical rows as `Allocation unavailable` rather than guessing, but this is exactly the kind of call that should be confirmed, not assumed).
8. **CC-fee-retained cross-payment cap** (§13): confirm the "fee can never exceed fee% × original amount, summed across all allocations against that payment" invariant matches actual accounting policy before it's enforced as a hard constraint.
9. **API backward compatibility window**: `ListResource`'s existing scalar `payment_type`/`payment_status` fields are proposed to stay (not be removed) alongside the new `payments[]` array (§14, §18) — confirm how long mobile API consumers need that backward-compatible field to remain before it can eventually be deprecated.
