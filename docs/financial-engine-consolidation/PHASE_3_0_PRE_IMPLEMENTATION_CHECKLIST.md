# Phase 3.0 — Pre-Implementation Checklist

Date: 2026-07-03
Branch: `raj_development`
Governing documents: `CUSTOMER_CREDIT_ARCHITECTURE.md`, `FINANCIAL_TRUTH_TABLE.md`, `FINANCIAL_DECISIONS.md`.

**Documentation discrepancy, flagged before anything else**: the mission asks this phase to follow `FINANCIAL_ENGINE_POLICY_MANUAL.md`. That file does not exist anywhere in this repository — confirmed by direct search of `docs/financial-engine-consolidation/` and the broader `docs/` tree. This is not silently worked around: the closest existing, real documents serving that role are `FINANCIAL_TRUTH_TABLE.md` (the authoritative accounting-policy reference, per its own header) and `FINANCIAL_DECISIONS.md` (the permanent business-decision log). This phase follows those two in the Policy Manual's stead and records this discrepancy here rather than inventing a document that was never actually written, or pretending one exists.

---

## Scope

Build `CustomerCreditService`, the foundation for Financial Store Credit only. This is new implementation, not a migration of existing behavior (there is no existing Financial Credit feature anywhere in this codebase to preserve equivalence with — confirmed absent during the Version 2.3 architecture research). Unlike every `LedgerBalanceService` phase so far, there is no "prove it matches production" step, because there is no production behavior yet to match. The discipline this phase applies instead: build only what is explicitly asked, document every deferred piece precisely, and never let this new service touch or duplicate what `LedgerBalanceService` already owns.

## The Core Architectural Boundary

The mission states this in the plainest possible terms: **"CustomerCreditService manages customer assets. LedgerBalanceService manages customer debt. Do not blur these responsibilities."** This has one concrete, load-bearing consequence for this phase's design: **Customer Credit is not stored in `customer_accounts`, and does not go through `LedgerBalanceService` at all.**

This is a deliberate refinement of `CUSTOMER_CREDIT_ARCHITECTURE.md` §2's original proposal (which suggested reusing the dormant `customer_accounts.type = 'credit'`/`'debit'` enum values and calling into `LedgerBalanceService`). That proposal remains architecturally sound as a *future* integration point — but it requires `LedgerBalanceService` to gain new branches first, which this mission explicitly forbids touching. Building `CustomerCreditService` against a ledger mechanism that doesn't yet exist, or half-wiring it to `customer_accounts` without `LedgerBalanceService`'s support, would blur exactly the boundary this mission draws. **This phase therefore gives `CustomerCreditService` its own, wholly separate persistence** — new tables, described below — leaving the `customer_accounts.type='credit'/'debit'` question exactly where `CUSTOMER_CREDIT_TODO.md` already left it: an open technical decision for a *future* phase, once `LedgerBalanceService` itself is ready to own that integration.

## Supported Features (this phase)

Per the mission's explicit "Implement only the Financial Credit foundation":

- **Create Financial Credit** — grant a customer a credit amount with a reason.
- **Redeem Credit** — reduce a customer's credit balance by an amount, with validation that the balance is sufficient.
- **Remaining Credit / Credit Balance** — computed live from the ledger of grants and redemptions, never a separately cached, driftable total (the same "single source of truth" discipline `LedgerBalanceService` itself follows, and the same discipline whose absence caused the divergence found in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md`).
- **Credit History** — the full list of grants/redemptions for a customer, in order.
- **Credit Validation** — redemption amount must be positive and must not exceed the current balance; grant amount must be positive.
- **Audit History** — every grant and redemption is itself a permanent, soft-deleted-not-hard-deleted row; there is no separate audit log to build, the ledger *is* the audit trail, exactly as `customer_accounts` already is for every other transaction type in this codebase.
- **Duplicate protection** — an optional, unique `idempotency_key` per grant/redemption, so a retried request (e.g., a network timeout that resubmits) cannot create the same credit twice. Modeled on the idempotency comment already present on `ChargeService::createFromOrderProduct()` ("Idempotent — will not create a duplicate if one already exists for the same OP + type").
- **Credit Expiration Support — architecture only, per the mission.** No `expires_at` column, no expiration logic, and no scheduled job are added in this phase. The service's class docblock documents exactly where expiration would need to be inserted later (at redemption-eligibility-checking time and at grant-creation time), so a future phase can add it without restructuring what's built here.

## Deferred Features (explicitly out of scope for this phase)

Promotional Credit, Gift Cards, the Customer Resolution Wizard, marketing campaigns, CRM screens, checkout/Order Entry integration, automatic credit application, loyalty programs, expiration rules, and scheduled expiration jobs. None of these are implemented, and none of their eventual schema needs (e.g., a `credit_category` column to distinguish Financial from Promotional) are added speculatively — adding them now, before they are needed, would be exactly the kind of scope expansion this phase's rules forbid.

## Dependencies

- **None on `LedgerBalanceService` or `CustomHelper`** — by design, per the core architectural boundary above. Confirmed no import of either will exist in the new service.
- **`Customer` model** — for validating a customer exists before granting/redeeming (`Customer::findOrFail()`, the same pattern `LedgerBalanceService::applyTransaction()` already uses for its own customer lookup, reused here only as a *pattern*, not as a call into that class).
- **`ModelHelper::generateUniqueID()`** — the same unique-ID generation helper `CustomerAccount` already uses, reused for the new model's `unique_id` column.
- **No dependency on this phase from any other in-flight work** — Phase 2.8B (Charge migration) and the Version 2.3 architecture phase are both unaffected by, and do not affect, this phase.

## Risk Assessment

| Risk | Assessment |
|---|---|
| Building against a persistence design that has to be reworked once `LedgerBalanceService` integration happens later | **Low, by design.** The new tables are intentionally minimal and self-contained; a future integration phase can add a bridge (e.g., a `customer_accounts` mirror row created alongside a `CustomerCreditService` grant) without altering this phase's schema or service methods. |
| Balance drift between cached and computed values | **None** — no cached balance is stored anywhere in this phase; `remainingBalance()` always sums the ledger live. |
| Duplicate credit grants from retried requests | **Mitigated** — optional unique `idempotency_key`, tested explicitly (see Testing section of the mission). |
| Scope creep toward Order Entry or UI "since it would be easy to add balance display" | **Mitigated by design** — this checklist and the completion report both name the exact 8 in-scope methods; nothing outside them is built. |
| `FINANCIAL_ENGINE_POLICY_MANUAL.md` not existing means a governing document named in the mission cannot literally be followed | **Disclosed above, not worked around.** `FINANCIAL_TRUTH_TABLE.md` and `FINANCIAL_DECISIONS.md` serve that role in practice; nothing in this phase required a policy decision those two documents don't already cover, since Financial Credit issuance/redemption involves no tax calculation and no ambiguity about which formula applies. |

## Validation Strategy

Since there is no existing production behavior to prove equivalence with (unlike every `LedgerBalanceService` phase), validation here means **comprehensive unit testing of the new service's own correctness**, not a live/rolled-back comparison against something pre-existing:

1. Credit creation produces a `customer_credits` row with the correct amount, reason, and customer.
2. Redemption reduces the computed balance correctly and is rejected outright (before any row is written) when it would exceed the current balance.
3. `remainingBalance()` correctly nets grants against redemptions across multiple transactions.
4. `history()` returns transactions in a stable, correct order.
5. Duplicate protection: submitting the same `idempotency_key` twice does not create a second row; the second call returns the original transaction instead of throwing or silently duplicating.
6. Invalid inputs (zero/negative amount, nonexistent customer) are rejected with a clear exception, never silently coerced.

All of this is testable against the local SQLite database directly (no `LedgerBalanceService`-style HTTPS/bootstrap workaround needed for the tests themselves, though the same `VITE_ORIGIN_PROTOCOL=http` override is still required to run `artisan`/`phpunit` in this environment, per the standing, already-documented infrastructure issue).

## Rollback Strategy

Two new migrations (a `customer_credits` table and, if a separate audit/redemption-detail table proves necessary during implementation, one more) plus one new model and one new service class. Rollback is: run `php artisan migrate:rollback` for the new migrations (nothing else depends on this table yet — zero existing code references it), delete the new model/service/test files. No existing file is modified in this phase, so there is no revert needed anywhere else in the codebase.
