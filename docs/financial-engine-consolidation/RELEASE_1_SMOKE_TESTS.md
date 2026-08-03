# Release 1 — production smoke tests

**For the operator. No code reading required.**

Run these on production **after** the migration and verification gates pass. Use real orders you are willing to keep or void afterwards. **Record every order number.**

Reference tax rate below is **9.75%** and special tax **2%** — substitute your configured rates and recompute the expected figures the same way. Where a value depends on your configuration, the *relationship* is what matters, and it is stated in words next to every number.

**Test 3 is the decisive one.** It proves the defect this release exists to fix.

---

## Before you start

| | |
|---|---|
| Customer with Store Credit | need at least **$150.00** available |
| Product without special tax or fees | for tests 1, 2, 5, 6, 7, 9 |
| Product with **Apply Special Tax** enabled | for test 3 |
| Product with **Apply Added Fees** enabled | for test 4 |
| Product marked **Tax Free Item** | for test 2 |

Record for each test: order number · what you entered · what the screen showed · pass/fail.

---

## Test 1 — Store Credit on an ordinary taxable order

1. Create an order for **$200.00** of ordinary taxable merchandise. Do not pay it.
2. Note the totals.
3. Apply **$50.00** Store Credit.

| Field | Before | After | Why |
|---|---|---|---|
| Subtotal | 200.00 | **200.00** | Gross never changes — the original sale value is preserved |
| Store Credit / pre-tax discount | 0.00 | **50.00** | The concession is explicit |
| Sales tax | 19.50 | **14.63** | Tax follows the reduced basis: 150.00 × 9.75% |
| Order total | 219.50 | **164.63** | 200.00 − 50.00 + 14.63 |

✅ **Pass if** subtotal is unchanged at 200.00 and tax dropped in proportion to the discount.
❌ **Fail if** subtotal changed, or tax stayed at 19.50.

---

## Test 2 — Mixed taxable and tax-free merchandise

1. Create an order with **$100.00 taxable** + **$100.00 tax-free** merchandise. Do not pay.
2. Apply **$50.00** Store Credit.

| Field | Before | After |
|---|---|---|
| Subtotal | 200.00 | **200.00** |
| Sales tax | 9.75 | **7.31** |
| Order total | 209.75 | **157.31** |

✅ **Pass if** tax fell to roughly three-quarters of its original value — the taxable line was reduced by 25%, so its tax fell 25%.
❌ **Fail if** tax became 0.00, or did not move, or the tax-free item appears to have been taxed.

> The tax-free line **does** receive a share of the discount. That is correct — it is still merchandise. It simply carries no tax either before or after.

---

## Test 3 — Order with special tax ★ THE DECISIVE TEST

1. Create an order for **$200.00** using a product with **Apply Special Tax** enabled. Do not pay.
2. Confirm the order shows special tax of **$4.00** (200.00 × 2%).
3. Apply **$50.00** Store Credit.

| Field | Before | After | Why |
|---|---|---|---|
| Subtotal | 200.00 | **200.00** | Gross unchanged |
| Sales tax | 19.50 | **14.63** | Follows the basis |
| **Special tax** | 4.00 | **3.00** | **150.00 × 2% — this is the fix** |
| Order total | 223.50 | **167.63** | |

✅ **Pass if special tax dropped from 4.00 to 3.00.**
❌ **FAIL — STOP AND ROLL BACK — if special tax stayed at 4.00.**

> Before this release special tax was **not** reduced, so the customer was charged special tax on merchandise value they were never billed for. A special tax that does not move is the defect still present.

---

## Test 4 — Order with added fees

1. Create an order for **$200.00** using a product with **Apply Added Fees** enabled (say a **$6.00** flat fee). Do not pay.
2. Apply **$50.00** Store Credit.

| Field | Before | After | Why |
|---|---|---|---|
| Subtotal | 200.00 | **200.00** | |
| Sales tax | 19.50 | **14.63** | Basis-derived: scales |
| **Added fees** | 6.00 | **6.00** | **Flat: protected, never scales** |
| Order total | 225.50 | **170.63** | |

✅ **Pass if added fees are unchanged at 6.00.**
❌ **Fail if added fees were reduced** — a flat fee is not derived from merchandise value and must not move.

---

## Test 5 — Stacked Store Credits

1. Create an order for **$200.00** ordinary taxable merchandise. Do not pay.
2. Apply **$30.00** Store Credit.
3. Apply a further **$20.00** Store Credit.

| After both | Value |
|---|---|
| Subtotal | **200.00** |
| Pre-tax discount total | **50.00** |
| Sales tax | **14.63** |
| Order total | **164.63** |

✅ **Pass if** the result is identical to applying **$50.00** in one step (compare against Test 1).
❌ **Fail if** the totals differ from Test 1 by even one cent.

---

## Test 6 — Store Credit reversal

1. Continue from Test 5, or repeat it.
2. Reverse **only the second ($20.00)** Store Credit.

| After reversal | Value |
|---|---|
| Pre-tax discount total | **30.00** |
| Sales tax | **16.54** (170.00 × 9.75%) |
| Order total | **186.54** |
| Customer's Store Credit balance | **increased by 20.00** |

3. Now reverse the **first ($30.00)** as well.

| After full reversal | Value |
|---|---|
| Pre-tax discount total | **0.00** |
| Sales tax | **19.50** — exactly the original |
| Order total | **219.50** — exactly the original |

✅ **Pass if** full reversal restores the original figures **exactly**, and the credit balance is fully restored.
❌ **Fail if** any figure is off by a cent, or reversals must be done in a particular order.

---

## Test 7 — Product reporting reflects the discount

1. Take the order from Test 1 (200.00 gross, 50.00 Store Credit) and **pay it in full** at the reduced total.
2. Open **Reports → Product Sales Performance** for a date range covering today.

| Metric | Expected |
|---|---|
| Revenue for that product | **150.00** — gross 200.00 less the 50.00 concession |

✅ **Pass if** the product's revenue reflects the concession.
❌ **Fail if** it still shows 200.00 — that is the pre-release behaviour, where discounts never reached product reporting.

> Gross figures remain available in reports that explicitly say **gross** (Sales Report gross sales / daily gross). Those are meant to be gross and should be unchanged.

---

## Test 8 — Receipt refresh

1. Create an order for **$200.00** ordinary taxable merchandise.
2. Take a **partial payment of $100.00** — the order must keep a remaining balance.
3. View or download the receipt. Note the total: **219.50**.
4. Apply **$50.00** Store Credit.
5. View the receipt again.

| Receipt field | Expected |
|---|---|
| Subtotal | **200.00** (gross merchandise) |
| Sales tax | **14.63** |
| Total | **164.63** |

✅ **Pass if** the receipt now shows the reduced total.
❌ **Fail if** it still shows 219.50 — a stale receipt is a customer-facing error.

---

## Test 9 — Refund after Store Credit

1. Create an order for **$200.00** ordinary taxable merchandise.
2. Apply **$50.00** Store Credit → total becomes **164.63**.
3. Pay the order in full at **164.63**.
4. Open the refund screen.

| Field | Expected |
|---|---|
| Maximum refundable | **164.63** — what was actually collected, not 219.50 |
| Tax portion of a full refund | **less than 19.50**, in proportion to the reduced tax |

✅ **Pass if** the refund ceiling is the amount actually paid and the tax portion follows the reduced tax.
❌ **Fail if** the ceiling exceeds what was collected, or the tax portion equals the original 19.50.

> **Note the order of operations.** A Store Credit discount can only be applied while a balance remains. If you pay first and then try to discount, the system will correctly refuse with *"Order has no remaining balance to discount."* That is intended behaviour, not a defect.

---

## Test 10 — Legacy order behaviour

For an order that was **already discounted before this release** (pick one from the informational exposure audit output).

| Check | Expected |
|---|---|
| Order totals | **unchanged by the deployment** — the migration re-prices nothing |
| Product reporting revenue | **gross** — the concession is not attributed to products |
| Legacy unattributed figure | shown separately, **not** folded into product revenue |

✅ **Pass if** the order's totals are exactly what they were before deployment, and the unattributed concession is disclosed separately rather than silently absorbed.
❌ **Fail if** any pre-existing order's totals changed.

> This is deliberate. There is no record of which lines bore a historical concession, and inventing a distribution would produce per-product figures that look authoritative and are not. Reconstruction is a separate, optional project — see `RELEASE_2_BACKLOG.md`.

---

## Summary sheet

| # | Test | Order # | Pass | Notes |
|---|---|---|---|---|
| 1 | Ordinary taxable | | ☐ | |
| 2 | Mixed taxable / tax-free | | ☐ | |
| 3 | **Special tax ★** | | ☐ | |
| 4 | Added fees | | ☐ | |
| 5 | Stacked credits | | ☐ | |
| 6 | Reversal | | ☐ | |
| 7 | Product reporting | | ☐ | |
| 8 | Receipt refresh | | ☐ | |
| 9 | Refund after credit | | ☐ | |
| 10 | Legacy order | | ☐ | |

**Any failure on Test 3 → roll back.** Other failures: assess against `RELEASE_1_ROLLBACK.md` §"Fix forward vs roll back".

Operator ______________________  Date ______________  Time ______________
