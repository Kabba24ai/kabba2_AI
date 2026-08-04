# Gift Cards — Phase 0 Audit

**Date:** 2026-08-03
**Branch:** `feature/goodwill-production-rebaseline`
**Status:** Audit complete. Two decisions required before Phase 1 schema is written.
**Nothing has been implemented. No migration written, no production data touched.**

---

## 1. The headline finding

**Gift Card already exists as a payment method, and it is already additive and already
everywhere the prompt asks it to be.** What does not exist is any *value* behind it.

`App\Enums\Orders\OrderPaymentMethod::GiftCard` is a live case, is included in
`canonical()` (it is not in the exclusion list), and therefore already appears in every
payment dropdown and validation rule built from that provider. It is already mapped in:

| Site | Line |
|---|---|
| `ReceivePaymentController` | 227 |
| `Admin\Dashboard\PaymentStoreController` | 657 |
| `Api\...\Orders\PaymentController` | 223 |
| `RefundPaymentController::mapPaymentMethod()` | 53 |
| `ReceiptService` (→ `gift_card`) | 379 |
| `Api\...\PaymentRequest` (documented enum) | 94, 97 |

It is also already a value in three native DB enums — `order_payments.payment_method`,
`receipts.payment_method`, `customer_accounts.payment_type` — added by
`2026_07_13_193529_add_canonical_payment_methods.php`.

**What it does today:** an employee selects "Gift Card" and types the card number into
`payment_note`. There is no balance, no validation, no ledger, no issuance, and nothing
stops the same card being typed twice.

**Existing data:** zero. Verified directly:

```
order_payments  payment_method = 'GiftCard'   → 0
receipts        payment_method = 'gift_card'  → 0
customer_accounts payment_type = 'GiftCard'   → 0
tables matching 'gift'                        → none
```

This is a clean slate with no legacy rows to migrate — but also a **live, selectable
tender that currently records unbacked value**. See §4.

### Consequence for the plan

Section 2 of the brief ("Add Gift Card to the existing payment selection as a purely
additive payment method") is **already satisfied**. The work is not adding a method; it
is putting a ledger behind one that already exists, and closing the reporting hole that
method currently sits in.

---

## 2. The authoritative rule is already written into the codebase

The brief states: *"Store Credit and Goodwill change the price. Gift Card pays the price."*

`App\Enums\Discounts\DiscountType` already says exactly this, unprompted:

> Gift Card is intentionally absent from this enum. It is PURCHASED VALUE — a form of
> tender — not a pre-tax adjustment: it does not reduce the taxable merchandise basis
> and must never be grouped with the types below.

and `reducesTaxableBasis()` exists specifically so that

> a future tender type (Gift Card) cannot be added to this enum without the question
> being asked explicitly.

**There is no conflict between the brief and the deployed architecture.** The calculation
order the brief specifies is the order the engine already implements: pre-tax adjustments
run through `OrderDiscountTarget`, tax is recomputed from the reduced basis, and tender is
applied to the resulting total. Gift Card must stay out of `DiscountType` and out of
`app/Services/Discounts/` entirely.

---

## 3. ⚠ DECISION 1 — This application is not multi-tenant

The brief requires tenant scoping in fourteen places: "tenant/store ownership",
"tenant-scoped card validation", "cross-tenant card cannot be viewed or redeemed",
"tenant-configurable prefix", "tenant-branded wallet page".

**No such architecture exists.** Verified:

- No tenancy package in `composer.json` (no `stancl`, no `spatie/laravel-multitenancy`).
- No `store_id` / `tenant_id` on `orders`, `order_payments`, or `customers`.
- `stores` (3 rows) are **physical locations** — name, address, phone, `is_primary`. They
  are not tenants and do not scope financial records.
- `Setting` is a single global table. "Company Settings" is one brand, not one per tenant.

This is a **single-brand application with three physical store locations**.

Implementing `tenant_id` would mean inventing an ownership axis the rest of the system
does not have, on a financial table — a column every future query would have to honour and
nothing would populate meaningfully.

### Recommendation

| Brief requirement | Recommended resolution |
|---|---|
| Tenant branding on the card | **Company Settings** — already the single brand source, already used by `DocumentMergeCodes`. Works as-is. |
| Tenant-configurable card prefix | **A Company Setting** (`gift_card_prefix`, default `RNK`). Correct and trivial. |
| Tenant-scoped validation / cross-tenant redemption | **Not applicable — omit.** There is one brand. Adding it invents architecture. |
| Tenant/store ownership column | **Replace with `issued_by_store_id`** (nullable FK to `stores`) — *which location sold it*. This is real, meaningful for reporting, and does not pretend to be tenancy. |

**I need your confirmation before writing the schema**, because this changes columns.
If Kabba is expected to become genuinely multi-tenant later, say so and I will design the
table so a tenant key can be added without a rewrite — but I will not ship a dead column.

---

## 4. ⚠ DECISION 2 — Granted cards have a sales-tax consequence, not just an accounting one

The brief anticipates this and asks me to stop here. It is a real fork.

**Purchased card — unambiguous, no decision needed.** Money in at issuance, no tax, no
revenue, liability created; redemption is tender, order tax computed normally, liability
drawn down. This is the standard model and I will implement it as specified.

**Granted card — genuinely ambiguous.** A manager comps a $50 card. The customer rents $50
of equipment. Real revenue occurred. No cash was ever received. Two treatments:

**Option A — Contra-revenue / promotional expense.**
Recognise $50 revenue, $50 promotional expense, **and charge sales tax on the full $50.**
Redemption behaves as tender, exactly like a purchased card. One code path, one
redemption experience, clean separation in reporting.

**Option B — Treat granted redemption as a merchant-funded discount.**
The comp'd portion reduces taxable receipts, so **no sales tax is charged on it**.
Financially this is a discount wearing a Gift Card label.

### Why this is yours and not mine

This is not a preference — it is a **sales-tax filing position**. In most jurisdictions a
*purchased* gift card redemption does not reduce taxable receipts (the store received
consideration), while a *merchant-issued comp* often does (no consideration was received).
Which applies to Kabba depends on your state's rules and your accountant's position.

Choosing Option A when your jurisdiction expects B means **remitting tax you did not
collect**. Choosing B when it expects A means **under-collecting tax on real sales.**

I will not guess this. Note also that Option B partially contradicts the brief's own rule
("Gift Card pays the price"), which is itself a signal the brief did not anticipate the
tax dimension of the granted case.

### Recommendation

**Option A**, for three reasons: it keeps one redemption path and one tender semantic; it
never under-collects tax; and it matches the brief's stated rule. Option B can be layered
later as a distinct concession type through the *existing Goodwill engine* — which already
does merchant-funded pre-tax concessions correctly, with authorization and audit.

**Please confirm A, or route granted cards to Goodwill instead.**

Note: the schema is safe either way — `issuance_class` (`purchased` | `granted`) is
required under both options. Only the *reporting and tax* treatment differs.

---

## 5. ⚠ Live defect found: gift-card tender is double-counted as collected revenue

`CollectedRevenueQuery` is "the single source of truth for collected revenue per payment
event". At line 283 it excludes retired Store Credit:

```php
->where('op.payment_method', '!=', 'StoreCredit')
```

**`GiftCard` is not excluded.** A `GiftCard` payment row counts as collected revenue.

Today that is harmless only because zero such rows exist. The moment issuance ships:

| Event | Cash | Recorded as collected |
|---|---|---|
| Customer buys $500 card by credit card | **+$500** | $500 |
| Customer redeems the $500 card | $0 | **$500** |
| **Total** | **$500** | **$1,000** |

$500 of real money reported as $1,000 collected — the exact error the brief lists first.
It also inflates Authorize.Net settlement expectations, since the redemption has no
gateway transaction behind it.

**The precedent for the fix already exists.** Store Credit's retirement excluded it at
four sites; Gift Card needs the same treatment at the same four:

- `CollectedRevenueQuery.php:283`
- `SalesReportEngineV2.php:665, 788`
- `SalesTaxReportEngine.php:279`
- `PaymentReconciliationLedger.php:327`

The *funding* payment (the $500 card charge) must stay in collections and stay
reconcilable to Authorize.Net — it is real money. Only the *redemption* is excluded.

This is the single highest-risk item in the feature, and it is a reporting change, not a
gift-card change. It should ship in the same release as issuance, never after.

---

## 6. ⚠ Doc contradiction to resolve

`CustomerCreditService` says:

> a future `PromotionalCreditService` or **`GiftCardService`** would call into this class
> rather than writing that table directly

i.e. gift cards on `customer_credits`. **This should not be followed.** That table is the
Store Credit ledger — the *pre-tax discount* mechanism — and:

1. It would mix gift-card liability with Store Credit, which the brief explicitly names as
   an error to prevent, and which §2 above establishes as a category error.
2. `customer_credits.customer_id` is required. A gift card frequently has **no customer
   record** — bought for a recipient who has never rented from Kabba.

**Recommendation:** separate `gift_cards` + `gift_card_transactions`, and amend that
docblock so the next reader is not misled. The *pattern* of `CustomerCreditService`
(live-computed balance, immutable rows, idempotency key, offsetting entries instead of
mutation) is excellent and should be copied — the *table* should not.

---

## 7. Infrastructure inventory

| Need | Status |
|---|---|
| PDF | ✅ `barryvdh/laravel-dompdf ^3.1`, in use at 5 sites |
| Email | ✅ `MailService` + Event/Listener pattern (`SendReceiptEmailListener` is the closest model) |
| Merge fields | ✅ `DocumentMergeCodes` — plain-text, Blade-escaped, unknown codes left visible |
| Branding | ✅ Company Settings: `company_name`, `company_main_phone`, `company_website`, `company_logo_media_id` |
| Permissions | ✅ Spatie. **Use `hasPermissionTo()` directly** — `Gate::before` returns true app-wide, so `@can` and `permission:` middleware never refuse. `GoodwillPermissions` is the working precedent. |
| Ledger pattern | ✅ `CustomerCreditService` (balance model) + `OrderGoodwillAdjustment` (audit model) |
| Concurrency | ✅ `GoodwillAdjustmentService` — READ COMMITTED + ordered locking + deadlock retry. Directly reusable. |
| **QR code** | ❌ **No package installed.** Needs `endroid/qr-code` or `simplesoftwareio/simple-qrcode`. |
| Barcode | ✅ None present — and per the brief, none will be added. |

---

## 8. Test baseline (recorded before any change)

`tests/Feature/Orders --filter=Payment` → **179 tests, 543 assertions, 6 failures.**

All six are **pre-existing and unrelated to gift cards**, verified on a clean tree:

| # | Test | Cause |
|---|---|---|
| 1 | `test_bank_transfer_payment_records_online_method` | Bank Transfer retired in `f0a75303` |
| 2 | `test_a_store_credit_payment_logs_store_credit_applied…` | Store Credit tender retired in `dd369166` |
| 5 | `test_store_credit_payment_redeems_the_real_balance` | same |
| 6 | `test_duplicate_store_credit_submission…` | same |
| 3 | `test_customer_order_view_shows_a_badge_for_partially_paid…` | 404 — fixture/route gap |
| 4 | `test_order_details_page_renders_the_payment_details_button…` | 500 — fixture omits `payment_api_public_key` Setting |

**#4 was checked specifically** because it renders the order-details page touched by
commit `a70f6fde`. It is not caused by that commit: the `payment_api_public_key`
requirement in `edit.blade.php` predates it (`0f2ed2f4`, `1dbaad50`, `f9541dcf`), and
`OrderSummaryComponentDisplayTest` renders the same page green because its `setUp()`
creates that Setting. `PaymentExperienceTimelineTest` does not.

These six are **not mine to fix inside this feature** (they assert deliberately retired
behaviour). Flagging them as separate cleanup.

---

## 9. Recommended schema — for approval, not yet written

Contingent on Decisions 1 and 2.

**`gift_cards`** — one row per card.
`id`, `unique_id`, `card_number` (unique, `PREFIX-NNNN-NNNN`), `lookup_token` (unique,
opaque, for QR), `pin_hash` (nullable), `issuance_class` (`purchased`|`granted`),
`grant_reason` / `grant_reason_category` (nullable), `original_value`, `currency`,
`status`, `purchaser_customer_id` (nullable), `recipient_customer_id` (nullable),
`recipient_name`, `recipient_email`, `sender_name`, `message`, `issued_by_store_id`
(nullable — see §3), `funding_order_payment_id` (nullable), `template_version`,
`activated_at`, `fully_redeemed_at`, `cancelled_at`, `replaced_by_gift_card_id`,
`created_by_*`, `updated_by_*`, timestamps, soft deletes.

**`gift_card_transactions`** — append-only, the canonical balance source.
`id`, `gift_card_id`, `type`, `amount` (signed), `balance_before`, `balance_after`,
`order_id` (nullable), `order_payment_id` (nullable), `reverses_transaction_id`
(nullable), `idempotency_key` (unique), `reason`, `note`, `created_by_*`, `created_at`.

**Deliberately excluded from v1:** `expires_at` (brief defers it), `tenant_id` (§3),
`gift_card_templates` (single design; `template_version` string suffices).

**Balance is computed from the ledger,** as `CustomerCreditService::remainingBalance()`
does — no editable balance column. Money in `decimal(10,2)` matching `order_payments`,
with all arithmetic in integer cents as `OrderFinancialHistory` and the Goodwill solver do.

---

## 10. What I recommend building first

Once Decisions 1 and 2 land, in this order:

1. **The reporting exclusion (§5) — first, alone, with its own regression tests.** It is
   the highest-risk item and is independent of everything else.
2. Schema + `GiftCardService` ledger + concurrency, modelled on `GoodwillAdjustmentService`.
3. Issuance: admin sell, admin grant, permissions, audit.
4. Redemption through the existing payment path.
5. Refund restoration through `PaymentAllocationService`.
6. Card render (Blade/dompdf), QR, wallet page, email.

Reconstructing the approved design as Blade is straightforward — the uploaded artifact is a
bundled React/base64 runtime and **none of that should enter the application**; only the
layout, the 300×250 logo clear zone, the CR80 geometry, and the merge-field set carry over.
