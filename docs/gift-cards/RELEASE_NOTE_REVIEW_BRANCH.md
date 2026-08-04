# Gift Cards — First Visible Release (review branch)

**Branch:** `feature/gift-cards-release`, cut from `raj_development`.
**Purpose:** put the gift card engine and workspace in front of a reviewer,
isolated from every other in-flight change.

---

## What this branch contains

The gift card increment and nothing else:

- the stored-value engine (ledger, issuance, redemption, refund routing,
  lifecycle, adjustment, permissions);
- the administrative workspace and the approved card artwork;
- gift card redemption in the order payment workflow;
- refund-to-card routing.

## What this branch deliberately does NOT contain

Nothing from `feature/goodwill-production-rebaseline` other than the two gift
card commits. In particular **no Goodwill code was brought over, replaced or
removed** — `raj_development`'s Goodwill implementation is the one that ships
here, untouched.

---

## The one thing that could not come with it

### Company-wide reporting integration is deferred

On `feature/goodwill-production-rebaseline`, gift cards integrate with
financial reporting through `CollectedRevenueQuery` and
`ProportionalPaymentSplit`. **Neither class exists on `raj_development`.** They
were introduced by:

```
7fb51595  reports: convert financial reporting to payment-date cash basis
          +1008 / −317 across SalesReportEngineV2, PaymentReconciliationLedger,
          SalesTaxReportEngine, and two new support classes
```

That commit is a large, unrelated change to the reporting core. Bringing it
would have meant deploying the whole cash-basis conversion under the heading of
"gift cards", so it was left out — and with it, the gift card reporting
treatment that was written against it.

**Consequence, stated plainly.** On this branch:

| Where | Behaviour |
|---|---|
| Gift Cards → Reporting | **Complete and correct.** Reads the gift card ledger directly; independent of the reporting core. |
| Payment Reconciliation Ledger | A gift card redemption appears as an ordinary payment row **carrying a settlement expectation**. No processor will settle it — the cash arrived when the card was funded. |
| Payment Reconciliation Ledger | Gift card **funding cash does not appear at all** — a card sale creates no order, so no stream sees it. |
| Sales Summary | Unchanged. `total_collected` here is order-derived, so redemption is not double-counted as a payment; funding cash is simply absent. |
| Sales Tax Report | Unchanged, and correct — a gift-card-funded sale is a taxable sale. |

**Why it was left as a gap rather than patched.** A partial fix is worse than
either extreme. `PaymentReconciliationLedger` documents that its rows sum to
`SalesReportEngineV2::kpis()['total_collected']`. Zeroing the redemption row
alone breaks that identity in one direction; adding a funding stream alone
breaks it in the other. Restoring it properly means changing what
`total_collected` means for the whole business — inside a reporting model that
the cash-basis conversion is already slated to replace.

So no shared reporting file is touched on this branch. Every existing figure is
exactly what it was before gift cards arrived.

**What closes the gap.** Deciding what happens to `7fb51595`. If the cash-basis
conversion is adopted, the reporting treatment on
`feature/goodwill-production-rebaseline` applies unchanged — it is already
written and tested there (9 tests). If it is not, the treatment needs
re-implementing against whichever reporting model wins.

---

## Deployment

```bash
php artisan migrate --force        # 3 gift card migrations
php artisan db:seed --class="Database\Seeders\Iam\GiftCardPermissionSeeder" --force
```

Never `ModuleSeeder` — it is destructive and deletes permissions.

The permission seeder is additive-only: it creates what is missing, issues no
`DELETE`, and is safe to run repeatedly. It now **reports what it did**, because
a seeder that grants nine permissions to nobody and prints nothing looks exactly
like success:

```
Gift card permissions: 9 registered.
Granted to role [Master Admin] — now holds 9 of 9.
```

or, when that role is absent:

```
Role [Master Admin] was not found.
The 9 gift card permissions exist, but NO ROLE HOLDS THEM, so every gift card
operation will be refused. Assign them in the roles screen, or grant them to a
user directly.
```

**Until this seeder runs, every gift card operation is refused by design** — an
unregistered permission is treated as a denial, never as consent.

---

## Known limitations in this increment

- Company-wide reporting integration, as above.
- Online customer purchase, processor webhooks, email delivery, PDF, QR,
  public wallet page, Apple/Google Wallet, multi-card redemption and expiration
  are all out of scope and not started.
- Card funding is an administrative entry: the money is taken first and
  recorded here. Selecting "Credit / Debit Card" as the funding method records
  a card payment that already happened; it does not charge a card.
