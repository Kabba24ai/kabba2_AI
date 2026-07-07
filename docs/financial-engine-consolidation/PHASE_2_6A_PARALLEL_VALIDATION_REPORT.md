# Phase 2.6A — Parallel Financial Validation Report

Report date: 2026-07-02
Branch: `raj_development`
Status: **Complete.** Shadow validation executed. Zero production behavior changed, zero customer balances modified, zero production callers touched.
Scope: The six transaction types `LedgerBalanceService` implements as of Phase 2.6 — Payment, Order, Manual Charge, Fuel Charge, Damage Charge, Extension Charge.

---

## 1. Executive Summary

This phase asked one question: does `LedgerBalanceService::amountWithTax()` — built in Phase 2.6 by re-reading and transcribing `CustomHelper::updateCreditBalance()`'s formulas — actually agree with what that production method does when both are run against the same input? Phase 2.6's own unit tests already asserted this, but those tests compare `LedgerBalanceService` against a hand-copied restatement of the production formula, written by the same phase that wrote the class under test. That is a useful regression guard, but it cannot catch a transcription error shared by both the class and its own tests. This phase closes that gap by executing the real, unmodified `CustomHelper::updateCreditBalance()` method against synthetic data inside a database transaction that is rolled back at the end, and comparing its actual, live output to `LedgerBalanceService`'s independently-computed prediction for the same input.

**Result: 17 of 17 comparisons matched exactly. 0 mismatches. 100% behavioral equivalence** for every transaction type in scope. Nothing about `CustomHelper.php`, `LedgerBalanceService.php`, or any other application file was changed to produce this result — this report is evidence about existing code, not a description of a fix.

One scope boundary was clarified during this phase, not discovered as a defect: the mission's comparison template names "Available Credit" as a column to verify per comparison. `LedgerBalanceService` (as built in Phase 2.6) has no equivalent to `CustomHelper::getAvailableCredit()` — only `amountWithTax()` exists. There is nothing to compare for that column yet, and this report says so explicitly rather than fabricating a result. See §4 and §5.

## 2. Validation Architecture

**This is a one-time validation run, not a standing shadow-mode feature or automated regression suite.** A temporary PHP script (not committed — consistent with the precedent set in `PHASE_2_4_VALIDATION_ADDENDUM.md`) was executed via `php artisan tinker` under the same `VITE_ORIGIN_PROTOCOL=http` runtime override this initiative has used since Phase 2.4 to work around the unrelated console-bootstrap HTTPS-redirect issue.

For each comparison:

1. **Setup** — inside a single `DB::beginTransaction()`, synthetic rows were inserted directly via `DB::table(...)->insert()` (bypassing Eloquent model events, the same technique validated in Phase 2.4): one `settings` row (`sales_tax = 0.0887`), two `customers` rows (one `tax_status = 'Taxable'`, one `'Exempt'`, both starting at `available_credit_balance = 0`), and one `customer_accounts` row per scenario.
2. **Production execution** — `CustomHelper::updateCreditBalance($record, $externalTaxAmount)` — the real, completely unmodified method — was called directly on the real Eloquent `CustomerAccount` model attached to the synthetic row. Its actual effect was read back from the database afterward: the resulting `customer_accounts.balance`, the resulting `customers.available_credit_balance`, and the resulting `customer_accounts.sales_tax`.
3. **Candidate execution** — `LedgerBalanceService::amountWithTax()` was called independently, with the same `type`, `amount`, `taxRate`, `salesTaxType`, `customerTaxable`, and `externalTaxAmount` inputs, and its `TaxBreakdown` was used to predict what the balance effect *should* be (same sign convention as the production switch statement: `payment` subtracts, `charge`/`order` add).
4. **Comparison** — the production method's actual effect was compared against the candidate method's prediction, for both the customer-level balance and the ledger row's own `balance` column.
5. **Teardown** — `DB::rollBack()` in a `finally` block, unconditionally. **Confirmed via direct query immediately afterward: zero synthetic rows of any kind remain in `customers`, `customer_accounts`, or `settings`.**

**Extension Charge is a documented exception to "live execution."** It never creates a `customer_accounts` row (confirmed in Phase 2.5B), so there is no `CustomHelper` method to invoke live for it, and its actual caller (`Extension\StoreController`) requires a full HTTP request, file uploads, and Order/OrderProduct fixtures that would be well outside a "small, isolated validation" phase to construct. For these 3 comparisons, the controller's exact inline formula (`$taxAmount = $validated['add_tax'] ? round($baseAmount * $salesTaxRate, 2) : 0.00; $grandTotal = $baseAmount + $taxAmount;`, re-read directly from `Extension\StoreController.php` lines 53-55 during this phase) was reproduced independently in the validation script and compared against `LedgerBalanceService`'s output for the same input — a formula-level comparison, not a live one. This is disclosed as a weaker form of evidence than the other 14 comparisons, per the mission's own "where practical" qualifier.

No test-database infrastructure gap applies here (unlike Phases 2.4-2.5's Feature-test limitations) — this validation ran directly against the same local SQLite database used throughout this initiative, inside a transaction, with no lasting effect.

## 3. Comparison Matrix

| # | Transaction Type | Scenario | Method |
|---|---|---|---|
| 1 | Payment | Taxable customer, $150.00 | Live |
| 2 | Payment | Non-taxable customer, $150.00 | Live |
| 3 | Payment | Taxable customer, $0.01 (smallest currency unit) | Live |
| 4 | Payment | Taxable customer, non-round $137.78 | Live |
| 5 | Charge — Fuel Charge | `sales_tax_type='add'`, $100.00 | Live |
| 6 | Charge — Damage Charge | `sales_tax_type='add'`, $350.00 | Live |
| 7 | Charge — Manual Charge | `sales_tax_type='add'`, $40.00, arbitrary `reason` | Live |
| 8 | Charge — Fuel Charge | `sales_tax_type='add'`, zero tax rate | Live |
| 9 | Charge — Fuel Charge | `sales_tax_type='reverse'`, $150.00 | Live |
| 10 | Charge — Damage Charge | `sales_tax_type='free'`, $75.00 | Live |
| 11 | Charge — Manual Charge | `sales_tax_type=null`, $60.00 | Live |
| 12 | Order | $200.00 + $17.74 external tax | Live |
| 13 | Order | $500.00 + $0.00 external tax | Live |
| 14 | Order | $99.99 + $8.87 external tax | Live |
| 15 | Extension Charge | $120.00 @ 5%, tax added | Formula-level |
| 16 | Extension Charge | $120.00, no tax | Formula-level |
| 17 | Extension Charge | $275.50 @ 8.87%, tax added | Formula-level |

Refund, Discount, Credit, Debit, and Account Invoice are out of scope for this phase (unimplemented in `LedgerBalanceService`) and were not compared, per the mission's explicit instruction.

## 4. Results

**17 of 17 comparisons matched. 0 mismatches.**

| # | Production balance effect | Candidate predicted effect | Match | Production `sales_tax` field | Candidate tax amount |
|---|---|---|---|---|---|
| 1 | −$150.00 | −$150.00 | ✅ | 0.0887 | $12.22 |
| 2 | −$150.00 | −$150.00 | ✅ | 0 | $0.00 |
| 3 | −$0.01 | −$0.01 | ✅ | 0.0887 | $0.00 |
| 4 | −$137.78 | −$137.78 | ✅ | 0.0887 | $11.23 |
| 5 | +$108.87 | +$108.87 | ✅ | 0.0887 | $8.87 |
| 6 | +$381.05 | +$381.05 | ✅ | 0.0887 | $31.05 |
| 7 | +$43.55 | +$43.55 | ✅ | 0.0887 | $3.55 |
| 8 | +$60.00 | +$60.00 | ✅ | 0 | $0.00 |
| 9 | +$150.00 | +$150.00 | ✅ | 0.0887 | $12.22 |
| 10 | +$75.00 | +$75.00 | ✅ | 0 | $0.00 |
| 11 | +$60.00 | +$60.00 | ✅ | 0 | $0.00 |
| 12 | +$217.74 | +$217.74 | ✅ | 0 | $17.74 |
| 13 | +$500.00 | +$500.00 | ✅ | 0 | $0.00 |
| 14 | +$108.86 | +$108.86 | ✅ | 0 | $8.87 |
| 15 | (formula) tax $6.00, total $126.00 | tax $6.00, total $126.00 | ✅ | n/a | $6.00 |
| 16 | (formula) tax $0.00, total $120.00 | tax $0.00, total $120.00 | ✅ | n/a | $0.00 |
| 17 | (formula) tax $24.44, total $299.94 | tax $24.44, total $299.94 | ✅ | n/a | $24.44 |

The resulting `customer_accounts.balance` column (the "Ledger Effect") and `customers.available_credit_balance` column (the "Customer Balance"/"Running Balance" — confirmed, again, to be the same single field in the current implementation, not two separate values) matched the candidate's prediction in every one of the 14 live scenarios as well — shown as `record_balance_matches: true` in the raw script output for every row.

**No mismatches occurred.** §5's root-cause classification therefore has nothing to classify from a difference — it instead records the one scope clarification found.

## 5. Root Cause Analysis

No behavioral mismatch was found, so there is no discrepancy to classify as production behavior / intentional rule / implementation defect / documentation gap / unresolved policy — the honest finding is that none of those categories were triggered.

One item is recorded here because it affects how future validation phases should be read, even though it is not a mismatch:

- **"Available Credit" is not comparable in this phase.** `CustomHelper::getAvailableCredit()` is a separate, independent calculation (already documented in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md` as one of four candidate methods, with its own full re-summation-over-all-accounts logic, distinct from `updateCreditBalance()`'s incremental model). `LedgerBalanceService` has no equivalent method — only `amountWithTax()` exists as of Phase 2.6. This is a **scope boundary, not a defect**: nothing was broken, and nothing needed fixing. It is recorded as a **documentation issue** (the validation template named a column that cannot yet be exercised) so that a future phase building `LedgerBalanceService::currentAvailableCredit()` knows this specific comparison still needs to be run then, not that it was already run and passed.

## 6. Production Readiness

**`LedgerBalanceService` has demonstrated behavioral equivalence with production for every transaction type it currently implements**, across 17 comparisons spanning ordinary values, a zero-rate edge case, the smallest currency unit ($0.01), a non-round decimal amount, all three `sales_tax_type` branches of the `charge` case, and both branches of the `order`/`extension` external-tax and add-tax-flag logic. 14 of the 17 comparisons exercised the actual, unmodified production method live, not a re-derived formula — the strongest form of evidence available without a full HTTP-level integration test.

This equivalence claim is scoped precisely to what was tested: the **tax-inclusion arithmetic** (`amountWithTax()`). It does not, and cannot, speak to `applyTransaction()`, `reverseTransaction()`, `rebuildForCustomer()`, or `currentAvailableCredit()` — none of which exist in `LedgerBalanceService` yet (confirmed unbuilt in `PHASE_2_6_COMPLETION_REPORT.md`'s Risks section, reconfirmed here).

## 7. Migration Readiness

## **Ready for limited caller migration — of the calculation layer only, with one explicit precondition.**

Evidence: 100% equivalence (17/17) for every transaction type already marked "Ready Now" in `FINANCIAL_TRUTH_TABLE.md` §7 (Payment, Order, Manual/Fuel/Damage Charge, Extension Charge), independently verified against live production execution for 14 of those 17, with zero business-policy ambiguity remaining for any of them.

**The explicit precondition**: no caller can actually migrate to `LedgerBalanceService` today, regardless of this validation's result, because the class has no method that writes to the database. `amountWithTax()` is a pure function; `updateCreditBalance()` is not — it also looks up the customer, resolves the tax-rate setting, computes the new balance, and persists both the ledger row and the customer row inside a retry-guarded transaction. Migrating a real caller requires building that write-path wrapper first (an `applyTransaction()`-shaped method, per the Architecture Report's original proposal). This was explicitly out of scope for Phase 2.6 ("foundation" only) and remains out of scope for this validation phase ("no production behavior changes," "no architecture redesign").

**Recommendation for Phase 2.7**: build `applyTransaction()` (the DB-write wrapper around the now-validated `amountWithTax()`), scoped to the same four already-proven branches (Payment, Order, Charge), with zero callers migrated yet — mirroring Phase 2.6's own "build it, prove it, don't wire it in yet" discipline one more time. Only after `applyTransaction()` itself is built and separately validated (a live comparison of the *write*, not just the arithmetic) should any real caller be migrated. Refund, Discount, Credit, Debit, and Account Invoice remain gated on the business/technical decisions in `FINANCIAL_TRUTH_TABLE.md` §7, unaffected by this phase.

---

## Project Documentation Updates

Recorded in `FINANCIAL_ENGINE_MASTER_ROADMAP.md` and `FINANCIAL_ENGINE_TODO.md`: Phase 2.6A completion, validation status (100% equivalence, 17/17), current Financial Engine version (2.1, unchanged — this phase validated Phase 2.6's work, it did not advance the version), and current project completion percentage.

## Final Executive Summary

- **Financial Engine Version**: **2.1** (unchanged — validation of the Phase 2.6 foundation, not a new increment).
- **Validation status**: **Complete. 100% behavioral equivalence demonstrated.**
- **Comparisons executed**: **17** (14 live production-code executions + 3 formula-level, for Extension Charge only, disclosed as such).
- **Matches**: **17**.
- **Mismatches**: **0**.
- **Behavioral equivalence percentage**: **100%**.
- **Production issues discovered**: **None.**
- **Documentation issues discovered**: **One** — the "Available Credit" comparison column cannot yet be exercised, since `LedgerBalanceService` has no equivalent to `getAvailableCredit()` as of Phase 2.6; recorded so a future phase building that method knows this specific comparison is still outstanding, not already passed.
- **Business-rule issues discovered**: **None** — no new ambiguity found for any of the six already-approved transaction types.
- **Recommendation regarding Phase 2.7**: proceed to build `applyTransaction()` (the database-write wrapper around the now-validated `amountWithTax()`), scoped to the same four proven branches, with zero callers migrated — not a full caller migration yet, since the write-path itself still needs to exist and be separately validated before any real call site can move.
