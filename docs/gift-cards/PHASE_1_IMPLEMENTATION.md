# Gift Cards — Phase 1 Implementation

**Date:** 2026-08-04 · **Status:** Complete, uncommitted, awaiting review.
**Not committed, not pushed, not deployed. No production migration run.**

The stored-value financial engine: ledger, issuance, redemption, refund
routing, lifecycle, adjustment, authorization, and the cash-basis reporting
treatment. **No UI, controllers, routes, QR, PDF, email, wallet page or online
purchase flow** — deferred by instruction (§11).

Supersedes the pre-permissions checkpoint of 2026-08-03.

---

## 1. Schema

Three migrations, `database/migrations/gift_cards/`. All additive and
reversible. Money is `decimal(12,2)`, matching `order_payments`; all arithmetic
is in integer cents.

### `gift_cards` (`2026_08_03_100000`)

| Group | Columns |
|---|---|
| Identity | `id`, `unique_id`, `card_number` (U), `lookup_token` (U), `pin_hash` |
| Classification | `issuance_class`, `grant_reason_code`, `grant_reason_category`, `grant_note` |
| Money | `original_value`, `cached_balance`, `currency` |
| State | `status`, `activated_at`, `fully_redeemed_at`, `cancelled_at` |
| Suspension audit | `suspended_at`, `suspension_reason`, `suspended_by` |
| Reinstatement audit | `reinstated_at`, `reinstatement_reason`, `reinstated_by` |
| Parties | `purchaser_customer_id`, `recipient_customer_id`, `recipient_name`, `recipient_email`, `sender_name`, `message` |
| Provenance | `issued_by_store_id`, `template_version`, `replaced_by_gift_card_id` |
| Audit | `created_by_*`, `updated_by_*`, timestamps, `deleted_at` |

**Indexes** — `card_number` U · `lookup_token` U · `unique_id` U ·
`gc_class_status_idx` (issuance_class, status) · `gc_status_idx` ·
`gc_purchaser_idx` · `gc_recipient_idx` · `gc_store_activated_idx`
(issued_by_store_id, activated_at).

**CHECK** — `gc_issuance_class_valid` (`purchased`|`granted`) ·
`gc_original_value_positive` · `gc_cached_balance_within_bounds`
(`>= 0 AND <= original_value`).

### `gift_card_transactions` (`2026_08_03_100001`) — canonical ledger

`gift_card_id` (RESTRICT), `type`, `amount` **signed**, `balance_before`,
`balance_after`, `order_id`, `order_payment_id`, `funding_payment_method`,
`funding_transaction_id`, `funding_cash_amount`, `funding_voided_at`,
`reverses_transaction_id`, `idempotency_key` (U), `reason`, `note`,
`created_by_*`, `created_at`. **No `updated_at`** — append-only.

**Indexes** — `idempotency_key` U · `gct_card_seq_idx` (gift_card_id, id) ·
`gct_type_date_idx` · `gct_order_idx` · `gct_payment_idx` ·
`gct_funding_date_idx` (funding_payment_method, created_at) — Stream E's path.

**CHECK** — `gct_type_valid` · `gct_amount_non_zero` ·
`gct_balance_after_non_negative` · `gct_funding_method_is_real_tender`
(≠ `GiftCard`) · `gct_granted_has_no_funding`.

### `add_status_audit_to_gift_cards` (`2026_08_03_100002`)

The five suspension/reinstatement columns above. They exist because suspension
changes what a card may **do** without changing what it is **worth**, so it
writes no ledger row — and `amount <> 0` means a zero-value placeholder cannot
be written either. Blocking a customer's money must not be anonymous, so the
facts the ledger would have carried are recorded on the card.

Cancellation and replacement **do** move value and are audited by their ledger
rows; they need nothing here.

### `DATETIME`, never `TIMESTAMP`

Production runs MariaDB 10.5 with `explicit_defaults_for_timestamp` **OFF**,
where the first `TIMESTAMP NOT NULL` without an explicit default silently gains
`ON UPDATE CURRENT_TIMESTAMP`. On `activated_at` that would rewrite a financial
instrument's issuance date on every row update — and would reproduce **in
production only**, since MySQL 8 ships the flag ON.

---

## 2. Permissions

`App\Services\GiftCards\GiftCardPermissions` — nine, and the only authority
check gift cards may use.

| Permission | Grants |
|---|---|
| `gift-card.view` | View cards |
| `gift-card.sell` | Sell (purchase) a card |
| `gift-card.grant` | **Create value from nothing** |
| `gift-card.redeem` | Spend value; refund back to card |
| `gift-card.suspend` | Suspend / reinstate |
| `gift-card.cancel` | Cancel and write off |
| `gift-card.replace` | Replace and transfer |
| `gift-card.adjust` | **Manual balance adjustment; off-card refunds** |
| `gift-card.reports` | Gift-card reporting |

**`hasPermissionTo()` only.** `AppServiceProvider::gatesRegistration()`
registers `Gate::before(fn () => true)`, so every Gate-routed check — `@can`,
`can()`, and Spatie's own `PermissionMiddleware` (which calls `canAny()`) —
returns true for any signed-in user. Route middleware may exist as defence in
depth; nothing may depend on it.

**An unregistered permission is a denial, not a crash.** `PermissionDoesNotExist`
is caught, logged as the deployment fault it is, and treated as "no" — because
on the endpoint that issues money, treating an unanswerable question as consent
is the failure that matters.

**Seeder:** `Database\Seeders\Iam\GiftCardPermissionSeeder`. Additive only — no
DELETE of any kind, safe to run repeatedly, deliberately separate from the
destructive `ModuleSeeder`. It grants **Master Admin and no other role**.
Who else may issue money is a roles-screen decision, never a seeder default.

---

## 3. Service-boundary authorization

Enforcement lives in `GiftCardService`, not in a controller. Every public
mutating method resolves an actor (explicit `?Authenticatable $actor`, falling
back to `auth()->user()`) and asserts before doing anything. A null actor holds
no permissions and is refused.

This is the point of the design: *"any caller reaching `grant()` can create
value"* was the blocker, and a controller-level guard would not have closed it —
a queued job, console command or future endpoint would each have had to
remember. `SELL` and `GRANT` are separate for the same reason: taking $500 and
issuing $500 is a clerk's job; issuing $500 from nothing is not.

**Value-changing operations must state why.** `GRANT`, `ADJUST`, `CANCEL`,
`REPLACE` reject an empty reason and record user + reason + timestamp. The
ledger row *is* the audit entry — the same discipline `CustomerCreditService`
applies to Store Credit, and the reason there is no separate audit table free to
disagree with the ledger.

---

## 4. Flows

**`purchase()`** — `SELL`. Rejects `GiftCard` as funding (a card cannot fund
itself; the storage layer refuses it too). Returns the existing card on a seen
idempotency key. One transaction: create card (`Active`), append
`issuance_purchased` carrying the **real tender**, processor reference and
`funding_cash_amount`. **No order, no `order_product`, no catalogue row** — so
`CollectedRevenueQuery::qualifyingPayments()`, which requires order lines,
structurally cannot see it. Not a predicate anyone must remember to write.

**`grant()`** — `GRANT`. As above minus funding; requires reason code and
category. Storage refuses funding columns on a granted row.

**`redeem()`** — `REDEEM`. ① lock the card ② status gate ③ **sum the ledger
under the lock** ④ lock the order, read `balance_due`, bound by **both** card
balance and order balance ⑤ create an ordinary settled `OrderPayment` (method
`GiftCard`) ⑥ append the negative ledger row **linked to that payment** ⑦
refresh the projection. All inside one transaction.

Ordinary on purpose: the payment must appear in history, on the receipt and in
the order's settled total like any tender. Only the *cash reporting* differs,
resolved downstream by reading the link — never by treating the payment as
special at the point of sale.

**`refundToCard()`** — `REDEEM`. Value returns to the card it came from.
Bounded by what *that order actually took from that card*, cumulative across
partial refunds, so two $300 refunds against a $500 redemption is refused.
Idempotent. A payment with no redemption raises `NothingToRefund`. A card
spent to zero becomes `PartiallyRedeemed` again.

**`suspend()` / `reinstate()`** — `SUSPEND`. Blocks or restores without touching
value. Reason mandatory, actor recorded. Terminal cards cannot be suspended.
Reinstatement runs the projection so a card spent to zero while suspended
returns as `FullyRedeemed`, not falsely `Active`.

**`cancel()`** — `CANCEL`. Appends an explicit negative `cancellation` row for
whatever remains, then marks the card `Cancelled`. The write-off is the audit
entry; silently zeroing a column would make money disappear with no record of
who removed it.

**`replace()`** — `REPLACE`. **Two** ledger rows — value leaves the old card and
arrives on the new — so money never appears to vanish from one side. The
replacement **inherits the original's issuance class**, so a replaced granted
card cannot quietly become purchased liability. The original becomes `Replaced`
and terminal, so a recovered card cannot be spent alongside its successor.

**`adjustIncrease()` / `adjustDecrease()`** — `ADJUST`. The same power as
`grant()` in a different shape, carrying the same weight. Decrease cannot drive
a balance negative. An increase raises `original_value` in step, the one path
permitted to move it and only upward — otherwise
`gc_cached_balance_within_bounds` would reject the projection.

### Off-card refunds require `ADJUST`

Refunding gift-card value to **cash or card** converts stored value into money:
the customer holds $500 of cash for $500 of stored value, and the business has
turned a liability into cash it never took for that transaction. A granted card
would convert promotional value the customer never paid for into money.

So it is gated behind `ADJUST` — manufacture/destroy value — **not** `REDEEM`,
which merely spends it. `requiresRefundToCard()` lets a refund screen know to
route back to the card by default.

---

## 5. Reporting

### Purchased liability vs granted promotional value

Both spend identically and both leave the order fully taxed. They are never the
same in the accounts:

- **Purchased** — real money was received; the business owes goods. A
  **liability**.
- **Granted** — no cash ever existed; the business gave value away. **Merchant-
  funded promotional value**, an expense.

Summing them would present money owed and money given away as one obligation.
Classification comes from the **card's** `issuance_class`, reached through the
redemption's ledger link — never inferred from the payment row, which can only
say "GiftCard".

### `CollectedRevenueQuery`

Two additive row fields: `is_cash_tender`, `non_cash_tender_class`
(`gift_card_purchased` | `gift_card_granted`). Allocation untouched.

**No `GiftCard !=` predicate was added, and none may be.** Store Credit is
excluded because it is a pre-tax *discount* whose reduction is already in the
order's totals. A gift card reduces nothing: excluding it would make allocation
see a $0 order and delete the revenue *and* the sales tax owed on it. A $550
order would collapse from $500/$50 to $44.42/$4.33. Guarded by
`test_excluding_the_redemption_row_would_destroy_revenue_and_tax`.

Rows with no ledger link (hand-entered legacy) remain cash — history unchanged.
Absent tables degrade to today's behaviour rather than failing a report.

### `SalesReportEngineV2`

```
gross_collections = gross_sales + tax_collected − discounts
                    − gift_card_redeemed      (revenue, not cash)
                    + gift_card_funding_cash  (cash, not revenue)
```

The two terms pull opposite ways on purpose. Each dollar is counted exactly
once across a card's life: as **cash at funding**, as **revenue at redemption**.

New KPIs: `gift_card_redeemed`, `gift_card_liability_issued`,
`gift_card_liability_redeemed`, `gift_card_liability_outstanding`,
`promotional_value_issued`, `promotional_value_redeemed`,
`gift_card_funding_cash`. Present-and-zero in the empty snapshot, so a consumer
never distinguishes "no gift cards" from "no data". `liability_outstanding` is
point-in-time as at window end — a liability is a position, not a flow.

### `SalesTaxReportEngine`

**Unchanged, deliberately.** A gift-card-funded sale is a taxable sale; a
granted card does not reduce taxable receipts under the approved tender-with-tax
model. Pinned by test so a future exclusion fails loudly.

### Stream E — `PaymentReconciliationLedger`

Gift cards span two streams because their cash and revenue are different events
in different periods:

| | Stream | `grand_total` | `base_amount` | `tax_amount` |
|---|---|---|---|---|
| Funding | **E** `gift_card` | funding cash | 0 | 0 |
| Redemption | A `order` | **0** | real | real |

Stream E carries the real tender and processor reference so it reconciles to an
Authorize.Net settlement; it **excludes voided funding** (a reversed charge is
not money kept) and **granted cards entirely** (no cash ever existed). Scoped
out of `pod`/`account` views and of line-filtered views — a card sale has no
product lines.

Redemption rows are **retained** with `grand_total = 0` and an explanatory note:
an operator reconciling the order must still see how it was paid, but no
processor will ever settle it. A non-zero total would leave the ledger
permanently short against the bank by exactly the amount redeemed.

The invariant `Σ ledger grand_total == kpis()['net_collections']` changes on
both sides consistently and remains the best single regression test.

---

## 6. Concurrency

READ COMMITTED for the next transaction only (no `SESSION`/`GLOBAL`), so a
pooled connection is unaffected. Chosen over REPEATABLE READ because InnoDB gap
locks there would block inserts for *unrelated* cards; READ COMMITTED confines
contention to the row genuinely contended.

Deadlock (`40001`) retried ×5 at 100 ms — expected under load, not exceptional.
Lock-wait timeout (`1205`) surfaced as `Contended` and **not** retried: someone
has held the card long enough that the operator should be told.

**Lock order:** `gift_cards` → `orders` → `order_payments`, without exception.
No cycle with Goodwill (`customers` → `orders` → …): both reach `orders` from a
different first lock, so one waits.

**Idempotency:** unique `idempotency_key`. Purchase returns the existing *card*;
redeem and refund return the existing *transaction*, so a replay never produces
a second `OrderPayment`.

### Scope of each locking test — stated precisely

`tests/Feature/GiftCards/GiftCardConcurrencyTest.php`, two real connections,
wrapping transaction disabled (`$connectionsToTransact = []`) because a second
connection cannot see uncommitted work.

| Test | Proves | Does **not** prove |
|---|---|---|
| `test_the_card_balance_is_read_under_a_locking_read` | The `gift_cards` read carries `FOR UPDATE`, **and** the lock precedes the ledger sum. Asserted on emitted SQL. | Behaviour under real contention. |
| `test_a_redemption_blocks_while_another_connection_holds_the_card` | Contention is **handled**: the call blocks and surfaces as `Contended`, not a raw DB error. Nothing is written while blocked. | That the *balance read* is locked. |
| `test_a_committed_redemption_is_visible_to_the_next_transaction` | The service re-reads committed state rather than trusting a stale object. One spend, one payment, one ledger row. | That the re-read is locked. |

**Why the SQL assertion exists.** The two interleave tests were written first
and passed — then passed again with `lockForUpdate()` deleted. Under READ
COMMITTED an unlocked `SELECT` is a non-locking consistent read: it does not
block, and the transaction instead blocks later on the `UPDATE`. Contention is
still surfaced, so neither test can distinguish a locking service from a
non-locking one. The property that actually prevents an overspend — that the
balance is read *under* the lock — is asserted directly on the SQL, verified to
fail with the lock removed and pass with it restored.

### Test isolation — permanent requirement

`GiftCardConcurrencyTest` **commits for real**; that is the point of disabling
the wrapper, and nothing rolls back for it.

Running `tests/Feature/GiftCards` as a directory initially failed three tests
that passed individually, because those rows survived into later tests
asserting **global** counts. Both halves were fixed:

1. `GiftCardConcurrencyTest::tearDown()` deletes its own rows, child-first
   (the ledger FK is RESTRICT, and `replaced_by_gift_card_id` is nulled first).
2. The three assertions were scoped to the entity under test — a global count
   silently depends on every test that ever wrote a card.

**This discipline is permanent.** Any future test in that class must clean up
after itself, and no gift-card test should assert an unscoped global count.

---

## 7. Test results

| Suite | Result | Baseline | Δ |
|---|---|---|---|
| **GiftCards** | **42 / 0** | new | — |
| Reports | 141 / 12E / 11F | 132 / 12E / 11F | **+9 pass, 0 regress** |
| Goodwill | 129 / 0 | 129 / 0 | none |
| Orders (`--filter=Payment`) | 179 / 6F | 179 / 6F | none |
| Unit/Services | 210 / 3F | 210 / 3F | none |

The Reports baseline was taken by **stashing this work and re-running clean** —
identical 12 errors and 11 failures both ways.

### Pre-existing failures, unchanged and unrelated

- **Orders ×6** — three assert the retired Store Credit *tender* (`dd369166`),
  one the retired Bank Transfer (`f0a75303`), two are fixture gaps (a missing
  `payment_api_public_key` Setting; a 404 route).
- **Reports 12E / 11F** — refund-netting, billing-attribution and line-
  attribution fixtures, incl. a missing `stores` row breaking a `delivery_store_id`
  FK.
- **Unit/Services ×3** — pre-existing.

None are in scope for this feature; they assert deliberately retired behaviour
or have fixture gaps. Flagged as separate cleanup.

---

## 8. Uncommitted files

**Modified (3)**

```
app/Services/Reports/CollectedRevenueQuery.php
app/Services/Reports/PaymentReconciliationLedger.php
app/Services/Reports/SalesReportEngineV2.php
```

**New (16)**

```
app/Enums/GiftCards/GiftCardIssuanceClass.php
app/Enums/GiftCards/GiftCardStatus.php
app/Enums/GiftCards/GiftCardTransactionType.php
app/Models/GiftCards/GiftCard.php
app/Models/GiftCards/GiftCardTransaction.php
app/Services/GiftCards/GiftCardException.php
app/Services/GiftCards/GiftCardFailure.php
app/Services/GiftCards/GiftCardPermissions.php
app/Services/GiftCards/GiftCardService.php
database/migrations/gift_cards/2026_08_03_100000_create_gift_cards_table.php
database/migrations/gift_cards/2026_08_03_100001_create_gift_card_transactions_table.php
database/migrations/gift_cards/2026_08_03_100002_add_status_audit_to_gift_cards_table.php
database/seeders/Iam/GiftCardPermissionSeeder.php
tests/Feature/GiftCards/GiftCardServiceTest.php
tests/Feature/GiftCards/GiftCardConcurrencyTest.php
tests/Feature/Reports/GiftCardCashBasisReportingTest.php
```

**Docs (3)** — `docs/gift-cards/PHASE_0_AUDIT.md`,
`REPORTING_TREATMENT.md`, `PHASE_1_IMPLEMENTATION.md`.

---

## 9. Deployment note

`GiftCardPermissionSeeder` **must run before any gift-card operation succeeds**
— every check denies until the permissions exist, by design. Run it, never
`ModuleSeeder`, which is destructive.

The three migrations are additive and reversible, and no production migration
has been run.

---

## 10. Deferred — explicitly not built

Not started, and **not** to be inferred as working:

- Admin UI: list, detail, sell/grant dialogs, ledger view
- Controllers, routes, FormRequests, API endpoints
- **Online customer purchase flow** and processor webhook handling
- QR code generation (no package installed) and camera scanning
- Public balance / wallet page and its rate limiting
- Card rendering (CR80 Blade), PDF output, email delivery
- Expiration — deliberately absent from the schema, so no reader assumes it
- Multi-gift-card redemption on one order (the service is structured for it)

---

## 11. Phase 1 acceptance checklist

| # | Criterion | Status | Evidence |
|---|---|---|---|
| 1 | No fake product or order for issuance | ✅ | Standalone card + ledger row; no `order_products`, so `qualifyingPayments()` cannot see it |
| 2 | No tax or revenue at purchased issuance | ✅ | `test_purchased_issuance_creates_cash_and_liability_but_no_revenue_or_tax` |
| 3 | Full tax and revenue at redemption | ✅ | `test_redemption_recognises_revenue_and_tax_but_collects_no_new_cash`; `test_granted_redemption_is_fully_taxed_revenue_but_not_liability` |
| 4 | No double-counted collections | ✅ | `test_funding_and_redemption_together_never_double_count` — $550 across the card's life, not $1,050 |
| 5 | No phantom settlement expectation | ✅ | Redemption `grand_total = 0`; funding cash only in Stream E |
| 6 | No cash conversion through ordinary refunds | ✅ | `refundToCard()` is the default; off-card requires `ADJUST`; bounded by amount redeemed |
| 7 | No unauthorized grant or adjustment path | ✅ | Service-boundary assertion; `test_granting_value_requires_its_own_permission`, `test_selling_a_card_does_not_confer_the_power_to_grant_one`, `test_an_unauthenticated_caller_can_do_nothing` |
| 8 | No overspend under contention | ✅ | Locking read asserted on SQL (fails without the lock); balance summed under the lock; both bounds enforced |
| 9 | Purchased and granted reported separately | ✅ | `test_purchased_and_granted_redemption_are_never_merged` — $300 liability / $250 promotional, never merged |

**Not yet acceptable for production use** until §10's controller and UI layer
exists — the engine is complete and green, but nothing yet exposes it safely.
