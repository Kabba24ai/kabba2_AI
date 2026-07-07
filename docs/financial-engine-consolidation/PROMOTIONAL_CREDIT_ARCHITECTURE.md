# Promotional Credit / Campaign Architecture

Document date: 2026-07-03
Status: **Architecture and planning only. No code implemented.**
Depends on: `CUSTOMER_CREDIT_ARCHITECTURE.md` §4 (ledger-facing half of a promotional grant — this document covers the campaign/marketing half that produces that grant).

---

## 1. Why This Document Exists

`CUSTOMER_CREDIT_ARCHITECTURE.md` established that a Promotional Credit grant is, at the ledger level, identical in mechanism to a Financial Credit grant (a `customer_accounts.type='credit'` row), differing only in origin and expiration behavior. This document covers what that earlier one deliberately left out: how a campaign *decides* who gets a grant, for how much, and when — the marketing/business layer sitting above the ledger mechanism.

## 2. What Already Exists (Confirmed, Not Assumed)

This phase's research confirmed the following infrastructure already exists in this codebase and should be reused, not rebuilt:

| Capability | Existing component |
|---|---|
| SMS sending | `App\Services\TwilioService` — encrypted credentials, already integrated |
| SMS campaign grouping | `App\Models\Customers\SmsBroadcast`, `SmsCategory`, `SmsFunnel` |
| SMS delivery logging | `App\Models\Global\SMSLog` |
| Email templates | `App\Models\Customers\EmailTemplate`, `EmailCategory` |
| Email campaign UI (partial) | `Admin\Crm\SalesFunnels\QuickCreate\EmailTemplateController` |

**Formal "promotions"/"campaigns" as a first-class feature do not exist** — a route name (`admin.promotions.index`) is referenced in at least one place as a redirect target, suggesting it was anticipated but never built, which is consistent with this document's premise rather than contradicting it. **Gift Cards, campaign-specific reporting, and customer segmentation logic are all confirmed absent** — this document designs into genuinely open space for those three, while designing to *reuse* the SMS/Email infrastructure for delivery.

## 3. Campaign Capability Design

A campaign, at minimum, needs:

1. **A definition** — name, description, credit amount (fixed or a rule, e.g. "10% of last order total" for a "we miss you" campaign), expiration policy (`CUSTOMER_CREDIT_ARCHITECTURE.md` §4), start/end dates.
2. **A targeting rule** — who receives it. See §4, Customer Segmentation.
3. **A delivery channel** — SMS (via `TwilioService`), Email (via `EmailTemplate`), or CRM-only (a grant with no outbound notification, e.g. a manually-triggered VIP promotion applied directly by staff).
4. **An issuance mechanism** — `PromotionalCreditService` (new, not built) resolves the targeting rule to a customer list, then calls `CustomerCreditService` once per recipient to actually create the `credit` ledger row, exactly mirroring how `ChargeService::createFromOrderProduct()` already creates one `CustomerAccount` row per applicable OrderProduct today (a proven, existing pattern for "one business decision, many resulting ledger rows," not a new architectural shape).

### Campaign types mapped to mechanism

| Mission example | Targeting rule | Delivery |
|---|---|---|
| Marketing campaign | Broad segment (e.g., all customers in a region) | SMS or Email |
| Grand opening | New-location proximity segment | SMS or Email |
| Birthday reward | Automated: customers whose `dob` (already a `customers.dob` column) falls in the current period | Email (low-urgency) or SMS |
| Referral bonus | Triggered by an event (a referred customer's first completed order) — not a scheduled campaign at all, an event-driven single grant | CRM-only or Email confirmation |
| VIP promotion | Manually curated segment (e.g., top N customers by lifetime spend) | Staff-applied via CRM, optionally with an Email/SMS notification |
| "We miss you" campaign | Automated: customers with no order in N days (this is directly adjacent to `Customer::getDaysSinceLastPaymentAttribute()`'s existing days-since-activity pattern, though that specific method measures *payment* recency, not *order* recency — a related but distinct query this campaign type would need) | SMS or Email |

## 4. Customer Segmentation

Not built anywhere in this codebase today — this is new. Proposed minimum viable segmentation criteria, all derivable from data that already exists on the `Customer` model or its relationships, requiring no new customer-facing data collection:

- Geographic (existing `CustomerAddress` data)
- Recency (last order/payment date — partially available via `Customer::getDaysSinceLastPaymentAttribute()`, extendable to last-order-date)
- Lifetime value (sum of historical payments — a straightforward, already-provable-safe read-only aggregation, following the same `SalesReportEngineV2`-style pattern already proven in this codebase)
- Manual/curated list (staff explicitly selects customers — needed for VIP promotions regardless of any automated segmentation)
- Tax/credit status (`Customer::getTaxStatus()`, `is_credit_account` — both already exist, potentially relevant for eligibility rules, e.g. excluding non-credit-account customers from a Store-Credit-based campaign if that's ever a business requirement)

## 5. Expiration

Governed by `CUSTOMER_CREDIT_ARCHITECTURE.md` §4 — every promotional grant carries an `expires_at` set by its campaign definition. This document adds one campaign-specific requirement: **the campaign definition, not the individual grant, is the source of truth for the expiration *rule*** (e.g., "90 days from issuance"), so that changing a not-yet-launched campaign's expiration policy doesn't require touching already-issued grants, and so reporting (§6) can group expirations by campaign.

## 6. Campaign Reporting

- **Campaign Performance** — grants issued vs. grants redeemed vs. grants expired unused, per campaign, over time. A direct extension of the "Credit Usage"/"Credit Expiration" reports already scoped in `CUSTOMER_CREDIT_ARCHITECTURE.md` §7, filtered by campaign.
- **Cost of unredeemed promotional liability** — the flip side of the same report: grants that expired without ever being redeemed represent a marketing cost that never converted, valuable for evaluating whether a campaign type is worth repeating.

## 7. Financial Engine Integration

`PromotionalCreditService` (new) owns campaign definitions, targeting, scheduling, and delivery-channel selection. It **never writes to `customer_accounts` directly** — every grant it decides to issue is a call into `CustomerCreditService` (per `CUSTOMER_CREDIT_ARCHITECTURE.md` §10), which is the only component with the authority to create a `credit`-type ledger row, keeping the "who decides vs. who writes" separation this initiative has maintained everywhere else (e.g., `ChargeService` decides *when* a charge should exist; `LedgerBalanceService` will decide *how* that charge affects a balance once migrated).

## 8. Open Business Decisions

1. Can a customer opt out of promotional campaigns entirely (marketing-consent question, likely with legal/compliance implications this document does not attempt to resolve)?
2. Is there a maximum promotional credit a single customer can accumulate at once (to bound liability exposure)?
3. Do referral bonuses require the referring customer to redeem before the referred customer's activity is confirmed, or is issuance unconditional on the referral event alone?
4. Who approves a new campaign before it goes live (a single marketing role, or the same manager-tier approval used for Resolution Wizard overrides in `CUSTOMER_RESOLUTION_ARCHITECTURE.md` §7)?
