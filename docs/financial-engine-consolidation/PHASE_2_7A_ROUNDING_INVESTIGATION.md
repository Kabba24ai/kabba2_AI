# Phase 2.7A — Production Rounding Investigation

Report date: 2026-07-02
Branch: `raj_development`
Status: **Complete — investigation only. Zero production behavior, code, architecture, Financial Truth Table, or Policy Manual changes made.**
Question investigated: does the `customer_accounts.type='charge'` + `sales_tax_type='add'` rounding discrepancy found in Phase 2.7 survive persistence into the actual database, or is it normalized by the database's `DECIMAL` storage behavior?

---

## 1. Executive Summary

Phase 2.7's write-path validation found that `CustomHelper::updateCreditBalance()`'s `'charge'`+`'add'` branch computes `$record->amount + $record->amount * $record->sales_tax` and never rounds the result, while `LedgerBalanceService`'s `amountWithTax()` rounds to 2 decimals. This phase's job was narrow and specific: not to re-confirm the PHP-level difference (already proven), but to determine whether that difference **survives being written to and read back from the database**, since a difference that only exists in an intermediate PHP variable and gets normalized by column storage would mean no real customer balance is ever affected.

**MySQL — the actual production database engine — is not available in this environment.** Checked directly, again, for this phase: no `mysql` binary, no `mysqld` binary, no MySQL entry in `brew services list`, no `docker` binary at all, and `.env` is hard-configured to `DB_CONNECTION=sqlite`. Per this mission's explicit instruction, this is stated plainly rather than guessed around, and SQLite's behavior is not presented as proof of what MySQL does.

**Using the only engine available (SQLite) as a partial signal**: 6 synthetic `'charge'`+`'add'` transactions were run through both the real, unmodified `CustomHelper::updateCreditBalance()` and the new `LedgerBalanceService::applyTransaction()`, inside a rolled-back transaction, with every value read back from the database via a fresh query afterward — not from PHP's in-memory state. **In every one of the 6 scenarios, the persisted value was bit-for-bit identical to the pre-persistence PHP calculation. SQLite applied zero rounding or truncation.** This means 5 of the 6 scenarios showed a real, persisted discrepancy between old and new code (the 6th coincidentally matched because its raw product was already exactly 2 decimals).

**Outcome classification: Category C — Inconclusive.** SQLite's `NUMERIC` column affinity enforces no fixed scale, so this result cannot be extrapolated to MySQL's `DECIMAL` type, which behaves fundamentally differently (fixed-scale, rounds on write). The evidence gathered here rules out one comforting assumption — "persistence probably fixes this regardless of engine" is false, since it doesn't in SQLite — but it cannot answer the actual production question. A MySQL-backed (or equivalent production-like) re-validation is required before Phase 2.8 may include the `'charge'`+`'add'` sub-case.

## 2. Issue Description

`CustomHelper::updateCreditBalance()`, the current production-serving implementation of ledger balance updates, computes the tax-inclusive amount for a `'charge'`-type transaction with `sales_tax_type='add'` as:

```php
$record->sales_tax = $salesTaxRate;
$amountWithTax = $record->amount + $record->amount * $record->sales_tax; // no round() call
$newBalance += $amountWithTax;
$record->balance = $newBalance;
$customer->available_credit_balance = $newBalance;
```

`LedgerBalanceService::amountWithTax()` (Phase 2.6) computes the same conceptual value via `TaxCalculationService::addTaxToExclusiveAmount()`:

```php
$taxAmount = round($amount * $rate, 2);
$totalAmount = round($amount + $taxAmount, 2);
```

For most dollar/rate combinations, `$amount + $amount * $rate` (unrounded) and `$amount + round($amount * $rate, 2)` (rounded) are not the same 64-bit float, differing in the third decimal place or beyond. The question this phase exists to answer is whether that difference matters once it reaches a real column, or whether it is an artifact that disappears at the database boundary.

## 3. Transaction Branch Tested

**Exactly one branch, per this phase's explicit scope**: `customer_accounts.type = 'charge'`, `sales_tax_type = 'add'`. No other transaction type and no other `sales_tax_type` value was investigated. Payment, Order, Refund, Discount, Credit, Debit, Account Invoice, and Extension Charge are all out of scope for this phase.

## 4. Test Methodology

1. Two independently-seeded synthetic customers (identical starting `available_credit_balance = 0`, both `tax_status = 'Taxable'`) and two synthetic `customer_accounts` rows (identical `amount`, `type='charge'`, `sales_tax_type='add'`) were created per scenario, inside one outer `DB::beginTransaction()`.
2. The real, unmodified `CustomHelper::updateCreditBalance()` ran on customer A's record.
3. `LedgerBalanceService::applyTransaction()` ran independently on customer B's record.
4. **Both implementations' persisted results were read back via fresh `DB::table('customer_accounts')->where('id', ...)->value('balance')` / `DB::table('customers')->where('id', ...)->value('available_credit_balance')` queries** — genuinely re-querying the database, not reading PHP's already-in-memory Eloquent model attributes, which could theoretically mask a difference between what was *assigned* in PHP and what the database actually *stored*.
5. `DB::rollBack()` ran unconditionally in a `finally` block. Confirmed via a direct post-rollback query that zero synthetic rows of any kind (customers, customer_accounts, settings) remained.

Six scenarios were tested, at a fixed tax rate of 8.87% (`0.0887`):

| Amount | Reason | Purpose |
|---|---|---|
| $100.00 | Fuel Charge | Control — `100 × 0.0887 = 8.87` is already exactly representable at 2 decimals |
| $350.00 | Damages | Repeated from Phase 2.7, for continuity |
| $40.00 | Late Return Fee | Repeated from Phase 2.7 |
| $90.00 | Fuel Charge | Repeated from Phase 2.7 |
| $12.34 | Fuel Charge | New — probes a value whose raw product lands mid-cent (`13.434558`) |
| $999.99 | Damages | New — probes a larger, non-round amount |

## 5. Database Engine Used

**SQLite** (`database/database.sqlite`), via the project's existing local development database. This is the *only* engine available in this environment.

## 6. Environment Limitations

- **No MySQL available under any method checked**: no `mysql` client binary, no `mysqld` server binary, no MySQL service registered in `brew services list`, and no `docker` binary to run a containerized instance. `.env` is configured `DB_CONNECTION=sqlite` throughout this entire initiative.
- **SQLite's `NUMERIC` type affinity enforces no fixed decimal scale.** `customer_accounts.balance` is declared via Laravel's `decimal('balance')` schema builder, which on SQLite produces a column with `NUMERIC` affinity — SQLite stores whatever value is written, at full floating-point precision, applying no rounding or truncation at all. MySQL's `DECIMAL(p,s)` type is fundamentally different: it is a fixed-scale type that rounds any inserted value to the declared number of decimal places. **A finding from SQLite cannot be used to predict MySQL's behavior** — it demonstrates the "no enforcement at all" case, not a stand-in for production's actual column type.
- **`customers.available_credit_balance` is currently a plain `varchar` in this local database** (confirmed by inspecting `sqlite_master`), not the `DECIMAL(15,2)` that migration `2026_06_20_100001_fix_available_credit_balance_to_decimal.php` would make it — that migration uses MySQL-only `MODIFY COLUMN` syntax and has never been run in this SQLite sandbox (consistent with this initiative's standing practice of never faking migration bookkeeping to work around an incompatible migration). This means even this environment's *local* evidence for that specific column is weaker than it might appear: a `varchar` column enforces no numeric formatting whatsoever.
- No staging or production-like MySQL instance was available to substitute for a genuine test against the real engine.

**Per this phase's explicit instruction: SQLite's behavior is not presented as proof of MySQL's behavior anywhere in this report.**

## 7. Raw Calculation Comparison

Computed directly in PHP, before any database interaction:

| Amount | Old raw calculation (`amount + amount × rate`, unrounded) | New raw calculation (`round(amount × rate, 2)` then add) | Match? |
|---|---|---|---|
| $100.00 | 108.87 | 108.87 | ✅ (coincidence — exactly representable) |
| $350.00 | 381.045 | 381.05 | ❌ |
| $40.00 | 43.548 | 43.55 | ❌ |
| $90.00 | 97.983 | 97.98 | ❌ |
| $12.34 | 13.434558 | 13.43 | ❌ |
| $999.99 | 1088.689113 | 1088.69 | ❌ |

## 8. Persisted Value Comparison

Read back via fresh database queries, **after** persistence, not from PHP's in-memory state:

| Amount | Old **persisted** `customer_accounts.balance` | New **persisted** `customer_accounts.balance` | Old **persisted** `customers.available_credit_balance` | New **persisted** `customers.available_credit_balance` | Persisted values match? |
|---|---|---|---|---|---|
| $100.00 | 108.87 | 108.87 | 108.87 | 108.87 | ✅ |
| $350.00 | **381.045** | **381.05** | **381.045** | **381.05** | ❌ |
| $40.00 | **43.548** | **43.55** | **43.548** | **43.55** | ❌ |
| $90.00 | **97.983** | **97.98** | **97.983** | **97.98** | ❌ |
| $12.34 | **13.434558** | **13.43** | **13.434558** | **13.43** | ❌ |
| $999.99 | **1088.689113** | **1088.69** | **1088.689113** | **1088.69** | ❌ |

**In every single scenario, the persisted value is bit-for-bit identical to the raw, pre-persistence calculation — SQLite performed zero normalization on write.** The `sales_tax` column (which stores the rate, e.g. `0.0887`, not a dollar amount) matched perfectly across all 6 scenarios in both implementations — this field is unaffected by the issue, as expected, since it is a direct rate assignment with no multiplication involved.

**Total comparisons: 6. Matching persisted values: 1 (coincidental). Mismatches: 5.**

## 9. Root Cause Analysis

Walking through every candidate source named in this phase's scope, with evidence for each:

- **PHP floating-point precision** — **Contributing factor, not the root cause.** `100.00 × 0.0887` happens to be exactly representable in IEEE-754 double precision at 2 decimal places; `350.00 × 0.0887`, `40.00 × 0.0887`, `90.00 × 0.0887`, `12.34 × 0.0887`, and `999.99 × 0.0887` do not, producing results with 3+ significant decimal digits (e.g. `31.045`, `3.548`, `7.983`, `1.094558`, `88.699113`). This is normal, expected floating-point behavior, not a bug in PHP itself — the bug is in what happens *next*.
- **`TaxCalculationService`** — **Not the source of the discrepancy; behaving correctly.** `addTaxToExclusiveAmount()` explicitly calls `round($amount * $rate, 2)` before returning, which is precisely the transaction-safe rounding policy established in Phase 2.1 and formalized in Phase 2.3A. Verified directly in source, unchanged since Phase 2.6.
- **`LedgerBalanceService`** — **Not the source of the discrepancy; behaving correctly.** `amountWithTax()` delegates to `TaxCalculationService` with no independent arithmetic of its own for this branch (confirmed by direct source read); `applyTransaction()` persists exactly what `amountWithTax()` returns, with no additional transformation.
- **`CustomHelper::updateCreditBalance()`** — **This is the root cause.** Its `'charge'`+`'add'` branch computes `$record->amount + $record->amount * $record->sales_tax` and passes the result directly into `$record->balance = $newBalance` and `$customer->available_credit_balance = $newBalance`, with **no `round()` call anywhere in the method for this branch** (confirmed by direct, complete source read during Phase 2.7 and re-confirmed during this phase — the method has not changed). This is the single point where the unrounded value is produced.
- **MySQL `DECIMAL` normalization** — **Not evaluated; cannot be evaluated in this environment.** No MySQL instance was available to test whether its `DECIMAL(p,s)` rounding-on-insert would normalize `CustomHelper`'s output before it becomes a genuine problem in production. This is the one candidate source this report explicitly cannot rule in or out.
- **Database column precision (SQLite specifically)** — **Confirmed NOT a mitigating factor in this environment.** `customer_accounts.balance` (`NUMERIC` affinity) and `customers.available_credit_balance` (currently `varchar` in this sandbox) both store the unrounded value exactly as computed, with zero normalization, confirmed by direct read-back in all 6 scenarios.
- **Existing production implementation, generally** — Consistent with the point above: this is a genuine characteristic of code that has been running in production, not a hypothetical.
- **Another source** — None identified. No other file, service, or database trigger was found to intervene between `CustomHelper`'s calculation and the final `->save()` calls.

**Conclusion**: the root cause is unambiguously `CustomHelper::updateCreditBalance()`'s missing `round()` call in its `'charge'`+`'add'` branch. The only remaining open question is whether MySQL's column-level rounding behavior, in the actual production database, has been incidentally masking this defect's real-world impact — a question this environment cannot answer.

## 10. Outcome Classification

## **Category C — Inconclusive**

Per the mission's own definition: "Environment cannot prove production MySQL behavior." That is exactly the situation here. This is not a refusal to reach a conclusion — sections 7-9 above reach firm, evidence-based conclusions about everything that *could* be tested in this environment. It is a precise statement of the one thing that could not be tested: MySQL's actual column-level behavior for these exact values, in production.

This does not default to Category A or Category B by convenience. Category A (Storage-Normalized) would require persisted values to match — they did not, in the only engine tested. Category B (Confirmed Production Defect) would require proof that *production's* persisted values differ — this report has proof that the *pre-normalization* computation is defective and proof that *at least one* real database engine fails to normalize it, but not proof about MySQL specifically.

## 11. Production Impact

**Unknown, and stated as unknown rather than assumed.** If MySQL's `DECIMAL` columns do round on write in a way that happens to always land on the same 2-decimal value `TaxCalculationService` would independently compute, then the actual customer-facing impact is zero — this would be a harmless implementation detail in code that's being replaced anyway. If MySQL's rounding does not always align (for example, if PHP's PDO driver transmits enough of the unrounded float's precision that MySQL's own rounding lands on a different cent than `round()` would, in some boundary cases), then real customer account balances could differ from what `LedgerBalanceService` would compute for the identical transaction, by fractions of a cent per affected `'charge'`+`'add'` transaction — small per-transaction, but a real, cumulative discrepancy across the full transaction history for any customer where the pattern occurs. **This report does not claim either outcome is true for production** — it states plainly that the question remains open.

## 12. Recommendation

**Validate against a staging or production-like MySQL instance before Phase 2.8 includes the `'charge'`+`'add'` sub-case**, using the same methodology as this report (create synthetic `'charge'`+`'add'` transactions, run both `updateCreditBalance()` and `applyTransaction()`, read persisted values back via fresh queries, compare). That re-validation would produce a definitive Category A or Category B result, replacing this report's Category C.

Until that happens:
- **Payment and Order callers are unaffected by this question** (confirmed structurally in Phase 2.7: neither path performs any multiplication, so no rounding risk exists regardless of database engine) and may proceed on their own timeline, gated only by the pre-existing items in `FINANCIAL_TRUTH_TABLE.md` §7.
- **The `'charge'`+`'add'` sub-case (and, by the same unrounded-arithmetic pattern observed by inspection in `discount`'s `'add'` branch and `refund`'s unconditional branch, though neither was independently tested here) should not migrate** until either a MySQL-backed re-validation produces a definitive classification, or the business/technical decision-makers explicitly accept the residual risk and document that acceptance.
- **If a future MySQL-backed re-validation confirms Category B (Confirmed Production Defect)**, the appropriate response is a separate, narrowly-scoped, separately-approved bug-fix PR to `CustomHelper.php` — not folded into any `LedgerBalanceService` migration PR, and not applied as a side effect of any future Financial Engine phase.
