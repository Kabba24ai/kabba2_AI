# Gift Card Architecture (Future)

Document date: 2026-07-03
Status: **Architecture and planning only. No code implemented. Confirmed greenfield — no Gift Card concept exists anywhere in this codebase today (verified via broad case-insensitive search, this phase).**
Depends on: `CUSTOMER_CREDIT_ARCHITECTURE.md` (the redemption engine this document reuses).

---

## 1. Why This Document Exists

The mission asks for a Gift Card design that "reuses the Customer Credit redemption engine wherever practical." This document takes that instruction literally: rather than designing a parallel balance/redemption system, it identifies exactly where Gift Cards are *the same* as Customer Credit and where they are genuinely *different*, and designs only the difference.

## 2. Where Gift Cards Are Identical to Customer Credit

A Gift Card, once activated, is purchasing power a customer can redeem against a future charge — functionally indistinguishable from a Financial Credit grant at the moment of redemption. **Redemption should be the exact same mechanism**: a `debit`-type ledger row, applied through `LedgerBalanceService`, shown in the same Order Entry "Apply Credit?" panel described in `CUSTOMER_RESOLUTION_ARCHITECTURE.md` §8. There is no reason to build a second redemption UI, a second balance-application flow, or a second ledger effect for Gift Cards — doing so would be exactly the kind of duplicated-mechanism drift this entire initiative exists to eliminate.

## 3. Where Gift Cards Are Genuinely Different

| Difference | Why it matters |
|---|---|
| **A Gift Card is a purchasable, transferable instrument with its own identity** (a code/number), not merely a balance tied permanently to one customer record | Customer Credit is always owned by an existing `Customer` row; a Gift Card can be bought by one person and given to another who may not have a `Customer` record yet at the time of purchase |
| **Issuance is itself a sale**, not an internal grant | Selling a $100 gift card is a real payment transaction (`type='payment'` or a new dedicated type — see §7) *and* a liability creation event simultaneously — two things Customer Credit's grant mechanism never has to represent together, since a Customer Credit grant never involves a payment on the way in |
| **Redemption may need to attach to a not-yet-existing customer** | A gift card can be redeemed by whoever holds the code, requiring a lookup-by-code path in addition to (or instead of) a lookup-by-customer path |
| **Balance can be partially redeemed across multiple future visits**, by design, indefinitely (or until an explicit expiration) | Same as Customer Credit in mechanism, but the *product expectation* (a gift card should "just work" for its full stated value over a long horizon) is stronger than a goodwill credit's |
| **Regulatory treatment differs from ordinary store credit in many US states** (gift cards are commonly subject to escheatment/unclaimed-property law with longer or no expiration windows, unlike promotional credit) | This is a **legal/compliance dependency this document does not resolve** — flagged explicitly, not assumed away |

## 4. Issuance

Proposed model: a new `gift_cards` table (code/number, initial value, current balance, status, purchaser reference, recipient reference if known, `issued_at`, `expires_at` if applicable) — **a new table, not an overload of `customer_accounts`**, because a gift card's identity (its code) exists independently of any customer record, unlike a credit grant which is always customer-scoped from the moment it's created. Purchasing a gift card is a payment transaction against the *purchaser's* account (existing `type='payment'` mechanism, no change needed there) plus a new `gift_cards` row recording the resulting liability — the two are linked by reference, not merged into one row.

## 5. Redemption

At redemption time, once a gift card code is validated and a redeeming customer is identified (or created, if a guest is redeeming for the first time — reusing whatever guest-customer creation path front-end checkout already uses for `is_guest` customers), the redemption becomes a `debit`-type `customer_accounts` row against that customer, decrementing the `gift_cards.balance` field by the same amount in the same transaction — **this dual-write (ledger row + gift card balance) must happen inside one database transaction**, mirroring the exact `DB::transaction()` discipline `CustomHelper::updateCreditBalance()`/`LedgerBalanceService::applyTransaction()` already use for their own dual-write (ledger row + customer balance) today. This is not a new pattern to invent — it is the same pattern, applied to a second balance field.

## 6. Balance

`gift_cards.balance` is the authoritative remaining value on a specific card. It is **not** the same figure as the Customer Credit balance described in `CUSTOMER_CREDIT_ARCHITECTURE.md` §6 — a customer could hold both a Customer Credit balance and one or more active gift cards simultaneously, and the Order Entry panel (`CUSTOMER_RESOLUTION_ARCHITECTURE.md` §8) would need to show both, or a combined "available purchasing power" figure, as a **UI decision to be made when this is implemented**, not decided here.

## 7. Expiration Policy

**Not decided here — this is the single largest open legal/compliance question in this entire framework.** Many US states restrict or forbid gift card expiration entirely, while promotional store credit (an internally-generated, marketing-driven grant) is generally more freely allowed to expire. This document explicitly does not assume gift cards inherit Promotional Credit's default-expires behavior from `CUSTOMER_CREDIT_ARCHITECTURE.md` §4 — that would be a compliance risk if wrong. **A legal review is a prerequisite for finalizing this section, not an implementation detail to defer.**

## 8. Transfer Policy

A gift card's code-based identity (§3) makes transfer a first-class capability that Customer Credit does not need: a card can be given to someone else simply by handing over the code, with no system action required, or (for a more controlled future digital-gift-card flow) an explicit "reassign this code to a different customer record" action. **Whether transfer requires any system-recorded action, or is purely physical/informal (whoever has the code can redeem it), is an open business decision** — the informal model is simpler and matches how physical gift cards have always worked, but forecloses fraud protections (e.g., disabling a lost card) that a formal transfer/registration model would allow.

## 9. Reporting

- **Outstanding Gift Card Liability** — sum of `gift_cards.balance` across all active cards, the gift-card-specific analog of "Outstanding Store Credit."
- **Gift Card Redemption Rate** — issued value vs. redeemed value vs. expired/unredeemed value, mirroring Campaign Performance's shape in `PROMOTIONAL_CREDIT_ARCHITECTURE.md` §6.
- **Breakage** (an accounting term for gift card value that will likely never be redeemed) — a specialized report finance teams commonly want; flagged as a future need, not designed in detail here, since it depends on the expiration/legal decisions in §7.

## 10. Financial Engine Integration

| Responsibility | Owner |
|---|---|
| Gift card code generation, activation, purchase transaction, balance tracking | **`GiftCardService`** (new, not built) |
| Validating a redemption code and locating/creating the redeeming customer | **`GiftCardService`** |
| Applying the resulting ledger effect (the `debit` row and running balance impact) | **`LedgerBalanceService`**, called by `GiftCardService` exactly the way `CustomerCreditService` calls it for an ordinary credit redemption — **the same call, not a parallel one** |
| Deciding whether a gift card purchase itself is taxable | **Not decided here** — flagged as a new Truth Table question, likely "no, gift card sales are not taxable events; tax applies at redemption against a taxable good/service," which is the common retail treatment, but this document does not assert it as settled policy |

This is the concrete realization of the mission's "reuse the Customer Credit redemption engine wherever practical" instruction: `GiftCardService` is a *thin* service whose only Financial-Engine-facing responsibility is calling into the same `LedgerBalanceService` path `CustomerCreditService` already uses — it does not reimplement balance arithmetic, transaction wrapping, or retry logic, all of which already exist and are already validated.

## 11. Open Business Decisions

1. Expiration/legal treatment (§7) — the highest-priority open item in this entire document.
2. Transfer model — informal (code-is-ownership) vs. formal (registered transfer) (§8).
3. Whether gift card purchases are refundable, and if so, whether the refund reverses the sale entirely or converts the remaining balance to Customer Credit (a natural application of `CUSTOMER_CREDIT_ARCHITECTURE.md`'s "Refund converted to credit" source).
4. Digital vs. physical card issuance — out of scope for this document, but affects the exact shape of "activation."
