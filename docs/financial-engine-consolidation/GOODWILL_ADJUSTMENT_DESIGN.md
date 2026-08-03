# Goodwill Adjustment — design against the deployed shared pre-tax engine

**Status: architecture APPROVED. Increment G1 built (domain + persistence only).
G2–G4 not started.**

> **Goodwill is a workflow layered on `ProductDiscount`. It is not a financial
> engine.**
>
> It decides *whether*, *why*, and *by whose authority* a concession is granted,
> and records that decision durably. Every figure — the concession amount, the
> recalculated ordinary and special tax, the protected added fees, the per-line
> allocations, the net product revenue, the receipt components — belongs to
> `app/Services/Discounts/` and is reached through the linked `ProductDiscount`.
> Goodwill stores none of them.
>
> The earlier standalone Goodwill calculation engine is discarded, not ported.

Baseline: `feature/goodwill-production-rebaseline` @ `afa84552`, which is what
`production/raj_development` currently carries (Release 1 + three fix-forward
patches).

This document **replaces** the Goodwill design authored against
`Kabba24ai/kabba2_AI`. That design carried its own calculation engine. It is
not ported, and must not be restored: every financial figure it computed is now
owned by `app/Services/Discounts/`.

Related: `SHARED_PRETAX_ADJUSTMENT_DESIGN.md` (the engine),
`GOODWILL_PRODUCTION_REBASELINE_REPORT.md` (why the earlier work was discarded),
`RELEASE_2_BACKLOG.md` (everything deliberately deferred).

---

## 1. Re-audit of the deployed branch

Every area named in the instruction, verified by reading the deployed code.

| # | Area | Verdict |
|---|---|---|
| 1 | `DiscountType::Goodwill` | **Exists, blocked.** Enum case, `receiptLabel()` = `Goodwill - Pre-Tax`, `reducesTaxableBasis()` = true, `defaultCalculationType()` = FixedAmount, `badgeColor()` present. `isOperationalInPhase1()` returns **false**, and `DiscountApplicationService::apply()` refuses on that. One deliberate switch, no new plumbing. |
| 2 | `DiscountApplicationService` | **Reusable, needs one new entry point.** Owns the txn boundary, customer + target row locks, in-txn revalidation, idempotency (pre-txn fast path *and* re-check inside the lock), persistence, and compensating reversal. Store Credit specifics are correctly isolated behind `$type->drawsDownStoreCredit()`, so a non-Store-Credit type already skips redemption and balance restoration. |
| 3 | `OrderDiscountTarget` | **Complete for Goodwill's needs.** Integer-cent `computeTotals()` shared by `previewTotals()` (read-only) and `recompute()` (writer); ordinary and special tax both scale with the surviving basis; added fees protected; other components preserved as a residual; round-trip assertion at zero discount; refuses a discount exceeding the gross basis. `applyDiscount()` asserts the calculator's `discountedProductValue` equals the writer's surviving basis and refuses the write on disagreement. |
| 4 | `PretaxDiscountAllocator` | **Complete.** Deterministic largest-remainder distribution weighted by gross `sub_total`, ties to the lower id; append-only rows stamped `reversed_at` rather than deleted; a retry never resurrects a reversed row; reconciliation identity `legacy_unallocated + Σ active = pretax_discount_total`, skipped only for line-less orders. |
| 5 | ProductDiscount reversal | **Correct and type-agnostic.** `reverse()` writes a compensating row (`status='reversed'`, `metadata.reversal_of`), flags the original, restores Store Credit **only** when `drawsDownStoreCredit()`, and calls `$target->reverseDiscount()`. Idempotent: re-reversal returns the original row. Both rows carry `status='reversed'`, so `PretaxAdjustmentPresenter` correctly stops naming the line. |
| 6 | Receipt refresh | **In-place, read-triggered.** `ReceiptService::getOrCreateReceipt()` compares six components and rewrites the snapshot on drift. **`receipts.issued_at` was never added** — draft decision C (issued receipts immutable, superseded by a new receipt) is *not implemented*. See §7. |
| 7 | Pending Payment UI | **Located.** `edit.blade.php` ~L174–240: the `PENDING PAYMENT` pill, `Partial Payment: …`, and `Balance Due: …` pills, all driven by `OrderPaymentSummary::for($order)`. `COLLECTION_PARTIALLY_PAID` + `balance_due > 0` is exactly the Goodwill window. |
| 8 | Receive Payment controller | **No change required.** Idempotency-token + cache-lock + DB-unique `order_payments.idempotency_token`; creates real payment rows only. Goodwill runs *after* it and never touches it. |
| 9 | AR binding | **`customer_accounts` rows with `type='order'`**, written per line by `AddToAccountPaymentController`, capped at `balance_due`. Already refused by `OrderDiscountTarget::isPostedToAccount()`. |
| 10 | Invoice binding | **`orders.invoice_id`** (written by `Crm/Customers/Invoice/{Store,Update}Controller`, cleared by `DeleteInvoiceController`). ⚠ **The shared engine does not check it** — see finding F-1. |
| 11 | Spatie + `Gate::before` | ⚠ **The `permission:` route middleware is fully bypassed.** Confirmed in vendor: `Spatie\Permission\Middleware\PermissionMiddleware` calls `$user->canAny($permissions)`, which routes through the Gate, and `AppServiceProvider::gatesRegistration()` registers `Gate::before(fn () => true)`. `@can` is bypassed identically. `User` uses the unmodified `HasRoles`/`HasPermissions` traits, so **`hasPermissionTo()` does not consult the Gate** and remains a genuine check. |
| 12 | Refund after a pre-tax adjustment | **Permitted, but the tax split is wrong.** `PaymentAllocationService::proportionalTaxRefund()` still derives `tax_amount / subtotal`. After any pre-tax adjustment the numerator is reduced and the denominator is gross, so the rate is understated. Unchanged since Release 1. Backlog #2/#3. |

### Findings that change the plan

**F-1 — Invoiced orders are not refused by the shared engine.**
`OrderDiscountTarget::ineligibleReason()` checks AR posting and remaining
balance only. An order with `invoice_id` set can receive a Store Credit
discount **today**, silently desyncing the invoice from the order.
Pre-existing, and out of scope to fix for Store Credit under "do not broaden".
**Goodwill enforces the invoice guard in its own policy layer** (G2), and the
gap is logged as a new backlog item for Store Credit.

**F-2 — Route-level `permission:` middleware is decorative.**
The two Store Credit routes carry `permission:customer_credit.redeem` /
`permission:customer_credit.grant`. Neither can refuse anyone today. Goodwill
therefore enforces authority **in the service**, via `hasPermissionTo()`, and
treats the middleware as defence-in-depth for the day the bypass is removed.

**F-3 — `@can` in Blade is bypassed too.**
`@can('goodwill.apply')` would show the Goodwill trigger to every signed-in
user. The UI must gate on a direct Spatie check, or operators will be offered
an action the server will refuse.

**F-4 — `hasPermissionTo()` throws when the permission row is absent.**
Spatie raises `PermissionDoesNotExist` for an unregistered name. If the
Goodwill permission is not seeded, the call throws rather than denying. It must
be caught and treated as denial, and the seeder is a hard deployment gate.

**F-5 — `ModuleSeeder` would delete a permission this feature seeds.**
Beyond the known destructive reconciliation (`Permission::where('module_id', …)
->whereNotIn('name', …)->delete()` at L314), L324 deletes **every permission
with a null `module_id`**, and L320–322 delete modules absent from the
hardcoded list. So an additive seeder must (a) create the module *and* set
`module_id` on the permission, and (b) the module + permissions must **also** be
added to `ModuleSeeder`'s declarative list, or the next run of that seeder
removes them. Only the additive seeder is ever run in production.

**F-6 — `orders.grand_total` has no other writer.**
Verified repository-wide: only `Front\Checkout\PostController` (order creation)
and `app/Services/Discounts/` write it. No admin edit path recomputes order
totals, so an applied adjustment cannot be silently overwritten. This is what
makes "revised total equals payments accepted" durable.

**F-7 — Line-less orders cannot carry an attributable concession.**
`PretaxDiscountAllocator` returns `[]`, `propagateToLines()` returns early, and
`reconcile()` deliberately skips them. Extension children are built this way.
Goodwill **rejects orders with no lines** rather than writing an unattributable
concession.

---

## 2. Division of responsibility

| Layer | Owns | Must never |
|---|---|---|
| `app/Services/Discounts/` (unchanged) | ordinary tax, special tax, added fees, gross values, `pretax_discount_total`, line allocations, stacking, reversal recompute, net product revenue, receipt components | know what Goodwill is |
| Goodwill (new) | authorization, reason + note, accepted-payment snapshot, idempotency, payment linkage, one-active rule, AR/invoice guards, before/after snapshots, audit history, operator messages, **sizing the concession** | calculate tax, special tax, fees, allocations or net revenue |

"Sizing the concession" is a policy question — *how much* — answered entirely by
asking the shared engine what a candidate concession would produce. Goodwill
never derives a total itself.

---

## 3. Sizing the concession

Goodwill must make the revised `grand_total` equal the cumulative settled
payments. That is **not** `balance_due`: reducing the pre-tax basis by $1 also
reduces ordinary and special tax, so the grand total falls by more than $1.

From the engine's own `computeTotals()`, with everything in cents and `D` the
cumulative pre-tax discount:

```
remaining = subtotal − D
tax       = round(baseTax     × remaining ÷ subtotal)
special   = round(baseSpecial × remaining ÷ subtotal)
grand(D)  = remaining + tax + special + fees + other
```

`grand` is monotonically non-increasing in `D`, stepping down by 1–3 cents per
cent of `D`. The solver:

1. **Seed** analytically —
   `D₁ = D₀ + round(ΔG × subtotal ÷ (subtotal + baseTax + baseSpecial))`,
   where `ΔG = grand(D₀) − total_paid`.
2. **Refine** by calling `OrderDiscountTarget::previewTotals()` across a small
   window around the seed (±8 cents is provably sufficient) and selecting the
   smallest `D` with `grand(D) ≤ total_paid`.
3. **Report** the residual `total_paid − grand(D)`.

Every candidate is evaluated through `previewTotals()` — the same function the
writer uses — so the operator can never be shown a figure the writer would
contradict. Nothing is computed twice.

### The residual — APPROVED POLICY

Because `grand` steps by more than one cent where the tax rounding boundary
falls, an exact match is unreachable in roughly one case in eight. The approved
rule:

- the residual is **computed by the shared engine**, never by Goodwill;
- it is **stored explicitly** in `rounding_residual`, never absorbed into the
  concession amount;
- it is **immutable** once written, and visible in the audit history;
- `abs(rounding_residual) ≤ $0.02`. **Anything larger refuses the operation.**

A gap above two cents is not rounding. The grand total falls by at most three
cents per cent of concession, so a larger remainder means the concession was
sized against different figures than the ones it is being written with — stale
state, or a calculation that did not converge. Absorbing it would hide exactly
the condition worth stopping for.

Enforced twice, deliberately: the model refuses with an operator-readable
message, and a `CHECK` constraint on the table refuses a raw insert that never
loads the model. Both are exercised by tests.

### Rejection states

| Condition | Message |
|---|---|
| No settled payment | "Record the customer's payment first — Goodwill closes the gap between what was collected and the order total." |
| `balance_due ≤ 0` | "This order has no remaining balance." (also refused by the engine) |
| Posted to the Credit Account | engine's own message |
| `orders.invoice_id` set | "This order is on invoice #N. Adjust the invoice instead." |
| Active Goodwill already exists | "A Goodwill adjustment is already applied. Reverse it before applying another." |
| Order has no lines | "This order has no merchandise lines for a pre-tax concession to reduce." |
| `total_paid` below the non-merchandise floor (`fees + other`) | "The remaining balance includes $X in fees and non-merchandise charges. A merchandise concession cannot reduce those." |
| Required `D` exceeds the eligible basis | engine's own message |
| Preview no longer matches | "This order changed since the preview was calculated. Reload and try again." |
| Caller lacks the permission | "You are not authorized to apply a Goodwill adjustment." |

---

## 4. Persistence — `order_goodwill_adjustments` (BUILT, G1)

No financial figure already owned by `product_discounts` or
`order_product_discount_allocations` is duplicated. The concession amount, the
discounted product value and the per-line shares are **read through the linked
`product_discount_id`**, never copied.

What the table does own is the **order-level** before/after snapshot, which
nothing else records: `product_discounts` carries product value and tax only —
no special tax, no fees, no grand total, no payment position.

| Column | Purpose |
|---|---|
| `order_id` | FK, indexed |
| `product_discount_id` | FK → the financial adjustment. NOT NULL |
| `reversal_product_discount_id` | FK, nullable — the compensating row |
| `status` | `applied` \| `reversed` |
| `active_order_id` | `order_id` while applied, NULL once reversed, **UNIQUE** — a database-level one-active-per-order guarantee (MySQL has no partial indexes) |
| `reason_code` | `GoodwillReason` enum value |
| `note` | text, nullable; **required** when `reason_code = other` |
| `approved_by` | the manager who authorized |
| `approved_at` | when authority was given |
| `applied_by` | the operator who executed, when different |
| `accepted_payment_total` | settled-payment total at apply time |
| `rounding_residual` | `paid − revised grand_total`, 0 on an exact close, bounded by `CHECK` |
| `before_snapshot` / `after_snapshot` | JSON: subtotal, pretax_discount_total, tax_amount, special_tax_amount, added_fees_amount, grand_total, total_paid, balance_due |
| `settled_payments_snapshot` | JSON: id, unique_id, amount, status, payment_datetime per settled row — the immutable justification |
| `payment_id` | nullable convenience pointer, set only when exactly one settled payment exists (mirrors the documented `parent_order_payment_id` convention) |
| `idempotency_key` | UNIQUE |
| `applied_at`, `reversed_at`, `reversed_by`, `reversal_reason` | audit |

`settled_payments_snapshot` is the payment linkage. It is a snapshot, not a
second allocation system: no amounts are ever read back from it for
calculation.

**No payment row is created.** Goodwill is not tender.

### Model-enforced invariants

Three rules live on the model rather than in a service, because a service can be
bypassed by the next caller:

1. **`reason_category` is derived**, rewritten from `reason_code` on every save.
   A caller supplying a contradictory category is overruled, so the reporting
   dimension can never disagree with the reason it describes.
2. **`active_order_id` is derived** from `status` — the order id while applied,
   `NULL` once reversed. It gives the `UNIQUE` index its meaning, so
   "at most one active Goodwill adjustment per order" is a database guarantee
   rather than a convention an unlocked path could miss.
3. **The audit fields are immutable after creation.** Any update touching the
   decision — reason, note, approver, accepted payment, residual, either
   snapshot, the idempotency key, the order or discount link — is refused. Only
   the reversal fields remain writable, because they are the one part of the
   story still unwritten when the row is first persisted.

The `OTHER` reason additionally refuses to persist without a non-blank note.

### `GoodwillReason` — APPROVED taxonomy

Two categories, because a concession granted for an operational failure and one
granted to win business look identical in the accounts and mean opposite things.
One measures how often the operation breaks; the other measures what the
business chose to invest in a relationship. Aggregating them answers neither
question.

| Category | Codes |
|---|---|
| **Service Recovery** | `SERVICE_FAILURE`, `EQUIPMENT_ISSUE`, `DAMAGED_PRODUCT`, `BILLING_ERROR`, `DELIVERY_PICKUP_ISSUE` |
| **Business Courtesy** | `REPEAT_CUSTOMER`, `CUSTOMER_LOYALTY`, `CUSTOMER_RETENTION`, `MULTIPLE_ITEMS`, `LARGE_ORDER`, `PRICE_MATCH`, `PROMOTIONAL_COURTESY`, `MANAGER_COURTESY` |
| **Other** | `OTHER` — mandatory note |

The backing value is a **stable uppercase code**, never a display string.
`label()` is presentation and may be reworded freely; the code may not. A report
grouped on a label silently re-groups the day the copy changes, splitting a
historical series in two with nothing to indicate it happened.

`category()` on the enum is the single authoritative mapping.
`reason_category` on the table is denormalised from it purely so a report can
`GROUP BY` a plain indexed column — the requirement that the category be
queryable **without parsing labels**. No reporting work is in scope for G1; the
dimension exists so future reports need no hard-coded grouping.

---

## 5. Authorization

```php
$user->hasPermissionTo('goodwill.apply')   // NOT can() — the Gate is bypassed
```

- Wrapped so `PermissionDoesNotExist` is caught and returns **false** (F-4).
- The route keeps `permission:goodwill.apply` as defence-in-depth, with a
  comment recording that it cannot refuse anyone today (F-2).
- Blade gates on the same direct check, not `@can` (F-3).
- Reversal requires `goodwill.reverse`.
- The **authorizing manager** is a distinct field from the acting operator, and
  the manager's id is validated to hold `goodwill.apply` — authority to receive
  a payment must never imply authority to reduce revenue.

### Seeder

A dedicated additive seeder creates the `goodwill` module and its permissions,
sets `module_id`, supplies `permission_to_all = 'No'`, and grants both
permissions to the **Master Admin** role. It deletes nothing.

`ModuleSeeder` is **never run in production** and its declarative list is
updated in the same commit so a future run does not delete what was seeded
(F-5).

---

## 6. Apply and reverse

### Apply

Inside `DB::transaction`, in order:

1. Authorize (Spatie, direct) — before anything is read.
2. Idempotency fast path: same key → return the existing adjustment.
3. Lock the order (`lockForUpdate` via the shared target's `lockAndRefresh()`).
4. Re-read settled payments **under the lock**.
5. Re-check idempotency under the lock.
6. Guards: no-payment, fully-paid, AR, invoice, one-active, line-less.
7. **Re-derive the required concession under the lock** and require it to equal
   the submitted amount to the cent. A mismatch is stale state — reject. This
   is stronger than a state hash because it re-answers the actual question
   rather than detecting that some input moved.
8. `DiscountApplicationService::applyGoodwill(...)` → `ProductDiscount` +
   `OrderDiscountTarget::applyDiscount()` (tax, special tax, fees, allocations,
   reconciliation — all engine-owned).
9. Persist `order_goodwill_adjustments` with both snapshots.
10. Commit.
11. **After commit**, refresh the receipt via
    `ReceiptService::getOrCreateReceipt($order->fresh())`.

Step 11 is deliberately outside the transaction. `getOrCreateReceipt()` swallows
its own write failures (backlog #8), so a failure inside the transaction would
neither roll it back nor surface — and the refresh is idempotent and
self-healing on the next read, whereas the ledger write is not.

`applyGoodwill()` is a **new dedicated entry point** on
`DiscountApplicationService`, mirroring `applyStoreCredit()`. The generic
`apply()` keeps its type gate, so no other caller can introduce a Goodwill
adjustment; the gate is restated as a per-entry-point allowlist rather than a
"Phase 1" flag, since Phase 1 has shipped.

### Reverse

1. Authorize `goodwill.reverse`.
2. Lock the order; re-read.
3. Refuse when incompatible later activity exists: any refund recorded after
   `applied_at`, AR posting, or `invoice_id` now set.
4. `DiscountApplicationService::reverse()` on **the Goodwill `ProductDiscount`
   only**. Store Credit adjustments on the same order are untouched — the
   allocator reverses by `product_discount_id`, and the engine recomputes the
   remaining cumulative discount from the `*_before_discount` snapshot.
5. Stamp the Goodwill row: `status='reversed'`, `active_order_id = NULL`,
   `reversed_at`, `reversed_by_user_id`, `reversal_reason`.
6. After commit, refresh the receipt.

Reversal re-opens a balance on an order previously shown as paid in full. That
is correct and intended, and the operator is told so before confirming.

---

## 7. Receipts

Goodwill uses the **deployed** receipt model: in-place refresh of the snapshot
components. `PretaxAdjustmentPresenter` already renders `Goodwill - Pre-Tax`
when Goodwill is the only active type, and the truthful generic
`Pre-Tax Discounts` when it is stacked with Store Credit.

Draft decision **C** (an issued receipt is immutable; adjustment produces a
superseding receipt) is **not implemented** — `receipts.issued_at` was never
added, and the instruction for this work specifies the current receipt model.
C is therefore **deferred, not satisfied**, and moves to the backlog rather
than being quietly dropped. Two consequences to accept knowingly:

- a receipt already printed or emailed will restate itself on the next read;
- a stacked order's receipt cannot attribute the total per type, because
  `receipts.pretax_discount_total` carries no attribution.

---

## 8. Interaction with the live refund defect

A refund after Goodwill **succeeds**, and the customer-facing total is correct.
Its **tax/base split is not**: `proportionalTaxRefund()` divides a reduced
`tax_amount` by a gross `subtotal`, understating the rate (backlog #2, resolver
#3). Goodwill does not cause this and does not worsen it beyond what Store
Credit already does — but it does make it reachable on more orders.

The G4 refund test therefore pins **current** behaviour with an explicit
comment that the split is known-wrong, rather than asserting a correctness this
codebase does not yet have. **Recommendation: fix #3 then #2 before or
alongside Goodwill.** Not a blocker; a disclosure.

---

## 9. Increments

| | Scope | Gate |
|---|---|---|
| **G1 ✅ built** | Migration, model, `GoodwillReason` + `GoodwillReasonCategory`, `GoodwillException`, `GoodwillPermissions`, additive permission seeder + `ModuleSeeder` list entry | Table shape and enum approved; seeder proven additive by running it twice against a populated database, deleting nothing |
| **G2** | `GoodwillAdjustmentService` — solver, preview, apply, reverse; `applyGoodwill()`/`reverseGoodwill()` on `DiscountApplicationService`; preview + apply + reverse controllers and routes | Every rejection state exercised; preview and apply proven to share one calculation |
| **G3** | `_goodwill_adjustment_panel.blade.php` + Pending Payment trigger, gated on a direct Spatie check | Displays the full breakdown of §3; requires reason, note-for-Other, manager, idempotency token |
| **G4** | Lifecycle tests | All twelve required scenarios plus exact-close, residual-close, and unreachable-floor |

Each increment stops before commit for review, with files changed, exact test
results, migration effects, unresolved findings, and confirmation that no
production database or configuration was touched.

## 10. Constraints held throughout

- `orders.subtotal` and `order_products.sub_total` are never written.
- No second line-allocation system; no second receipt calculation path.
- Gift Card stays out of `DiscountType` — it is tender.
- No reliance on Gate authorization.
- `ModuleSeeder` is never run in production.
- Goodwill creates no payment row.
