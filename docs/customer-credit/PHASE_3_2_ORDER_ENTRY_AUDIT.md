# Phase 3.2 — Order Entry Integration: Pre-Implementation Audit

Date: 2026-07-04
Branch: `raj_development`

---

## 1. Order Creation

**There is no admin-side order-creation screen.** `routes/admin/order_management/orders/routes.php` has its `/create` routes commented out (`// Route::get('/create', CreateController::class)...`). Orders are created exclusively via the front-end customer checkout (`app/Http/Controllers/Front/Checkout/PostController.php`). Staff who need to place an order on a customer's behalf use `Admin\OrderManagement\Orders\Reorder\PostController`, which generates a signed impersonation URL and redirects into that same front-end checkout flow.

**Consequence for this phase**: since this mission is explicitly INTERNAL-only with "no customer portal work," the front-end checkout is out of bounds as an integration point — it is customer-facing infrastructure regardless of who happens to be driving it. The only genuinely internal, admin-only order screen is the **Order Edit screen** (below), which every order reaches immediately after creation and remains the single place staff manage it thereafter.

## 2. Checkout / Payment Flow (Order Edit Screen)

The Order Edit screen (`app/Http/Controllers/Admin/OrderManagement/Orders/EditController.php` → `resources/views/admin/order_management/orders/edit.blade.php`) is where staff take payments against an **existing** order.

- **Trigger**: a "PENDING PAYMENT" button (`#pendingPaymentBtn`, `edit.blade.php:56-60`), shown when `!$order->is_paid && $order->last_payment_status` is Pending/Failed/PartialPayment.
- **Modal**: `#processPaymentModal` / `#paymentForm` (`edit.blade.php:2154-2339`).
- **Controller**: `Admin\OrderManagement\Orders\ReceivePaymentController` — route `PUT /admin/order-management/orders/{unique_id}/receive-payment`, named `admin.order-management.orders.receive-payment`.
- **Submission pattern**: JavaScript `apiFetch()` (a shared global helper, `resources/shared/js/api.js`) posts the form, expects a JSON response shaped `{success: bool, message: string}`, and reloads the page (`window.location.reload()`) on success — **not** the `flash()`+`redirect()->back()` pattern the CRM customer screens use. Any new controller on this screen must follow this JSON convention to behave consistently with the existing payment action next to it.
- **What gets created**: an `OrderPayment` row via `$order->payments()->create([...])` — `amount`, `payment_method`, `status` (from the `OrderPaymentStatus` enum), etc. This table is **completely separate from `customer_accounts`** — order-level payment tracking never touches the CRM ledger (the sole exception, `AddToAccountPaymentController`, is a distinct, COD-specific action unrelated to this phase).

## 3. Existing Payment Summary

Located at `edit.blade.php:1555-1687`, inside a `grid grid-cols-4` layout (`edit.blade.php:1470`) alongside a conditional Terms & License panel and a Notes panel (`col-span-2`).

- **Displayed today**: Subtotal, Taxes, Grand Total (all direct DB columns on `orders`), plus a conditional Refunds breakdown (Total Refunded, Total Tax Refunded, Final Grand Total) when refunds exist.
- **No "Balance Due" is shown on this panel.** Balance due is only computed inside the payment modal's JavaScript: `modalBalanceDue = Math.max(0, modalGrandTotal - modalTotalPaid)`, sourced from `data-grand-total`/`data-total-paid` attributes on the modal element (`edit.blade.php:2156-2157`).
- **Real, already-existing `Order` model accessors** (`app/Models/Orders/Order.php`):
  - `getTotalPaidAttribute()` (line 310) — sums `OrderPayment` rows with status `Paid`/`PartialPayment`.
  - `getBalanceDueAttribute()` (line 320) — `max(0, grand_total - total_paid)`.
  - `getIsPaidAttribute()` (line 305) — whether any `Paid` payment exists.

  These accessors are the **authoritative source** for "how much of this order is paid" — this phase must read from them, not recompute the same figures independently.

## 4. Existing Billing Summary Integration

**None.** `resources/views/admin/crm/billing_summary/` is a completely separate CRM screen (an aggregate, cross-order view of a customer's account). It has zero links to or from the Order Edit screen, and zero shared controllers or data flow. Confirmed: nothing in `edit.blade.php` references Billing Summary, and nothing in Billing Summary references a specific order's edit page.

## 5. Existing Payment Application Logic

Payment application for an order is entirely self-contained within `OrderPayment` rows and the `Order` accessors in §3 — it does not write to `customer_accounts`, does not call `CustomHelper`, and does not call `LedgerBalanceService`. This confirms Order Entry integration for Customer Credit can be built as a genuinely separate concern layered on top, exactly as `CustomerCreditService` was designed from Phase 3.0 onward ("manages assets, not debt").

## 6. Existing Customer Lookup

**Not applicable on this screen.** The Order Edit screen is for an **already-placed** order — the customer is fixed (`$order->customer`, eager-loaded), not searched or selected. When this mission's spec says "when a customer is selected, display a Customer Credit panel," on this screen that condition is simply "always" — the order's customer is known from the moment the page loads. No search/select UI needs to be built.

## 7. Existing Financial Panels

Three panels currently occupy the `grid-cols-4` row at `edit.blade.php:1470-1703`:

| Panel | Columns | Visibility |
|---|---|---|
| Terms & License | 1 | Conditional (`!empty($order->pending_terms_content)`) |
| Payment Summary (§3) | 1 | Always |
| Order / Delivery Instructions (Notes) | 2 (`col-span-2`) | Always |

Immediately below this grid (`edit.blade.php:1704-1717`) is a conditional "Order Extra Payments" section (fuel/damage charges via `extraCharges`), using the pattern `<div class="bg-white rounded-xl border border-gray-200 p-4 space-y-2 shadow-sm ...">`.

## Recommendation

**Add a new "Customer Credit" panel as its own row, immediately after the existing `grid-cols-4` row and before the "Order Extra Payments" section** (i.e., right after `edit.blade.php:1703`'s closing `</div>`), using the same card styling already established there (`bg-white rounded-xl border border-gray-200 p-4 shadow-sm`). This is the cleanest integration point because:

- It does not touch or restructure the existing, working Payment Summary panel or its conditional refund logic — a smaller, safer diff than editing that panel in place.
- It sits directly beside the existing payment-taking UI, where staff are already looking when working an order's finances.
- It requires no new customer-lookup UI (§6) and no Billing Summary changes (§4), since neither exists in a form this phase needs to touch.
- The existing `ReceivePaymentController`/`apiFetch()`/JSON-response convention (§2) can be followed exactly for the new Apply/Remove Credit actions, keeping the new code idiomatically consistent with this specific screen rather than importing the CRM screens' different (`flash()`+redirect) convention.

**Design decision required for "Store Credit Applied" tracking**: `CustomerCreditService`'s `customer_credits` table currently has no reference back to a specific order — a redemption today only records `customer_id`. To show "Store Credit Applied" for *this* order (and to support "Remove Applied Credit" against *this* order specifically, and the mission's explicit audit requirement to record "Order"), `customer_credits` needs a small, additive, nullable `order_id` column. This phase adds it — the same class of small, non-disruptive extension Phase 3.1 already made twice (`effective_date`, `internal_comments`). "Remove Applied Credit" is implemented as an **offsetting grant** (via `CustomerCreditService::createFinancialCredit()`, reason "Reversed — Applied to Order #X"), not a mutation or deletion of the original redemption row — preserving the immutable-ledger/full-audit-trail discipline this initiative has maintained since Phase 2.1. `CustomerCreditService` remains the only writer to this table.
