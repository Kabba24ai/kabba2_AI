# Phase 2.5 — Pre-Implementation Checklist

Checklist date: 2026-07-01
Branch: `raj_development`
Status: **Investigation complete. Reflects a discovered exclusion (the `account_invoice` transaction type) — read before implementation.**

---

## Pre-implementation finding (must be read before the Scope section makes sense)

Direct reads of all four target files (via research agent, cross-checked against the formulas already proven in `TaxCalculationService`) found that the per-row tax breakdown in three of the four files (`_tab_credit.blade.php` admin, `_tab_credit.blade.php` front-end, `transaction_accounts_pdf.blade.php`) branches on **three** cases, not two:

1. **Tax already included in `amount`** — when `sales_tax > 0` and (`type === 'payment'` or (`type` in `['charge','discount']` and `sales_tax_type === 'reverse'`)). Uses division: `base = amount/(1+rate)`, `tax = amount - base`. **This is exactly `TaxCalculationService::extractTaxFromInclusiveAmount()`.**
2. **`type === 'account_invoice'`** — a distinct case that does **not** use either existing formula. It computes `base = amount - sales_tax` (direct subtraction) and displays `sales_tax` directly as the tax amount. This only makes sense if, for this specific transaction type, `sales_tax` is stored as a **dollar amount**, not a rate — the same unit-mismatch class the original CRM Billing Audit flagged (§4.6) between `customer_accounts.sales_tax` (normally a rate) and `invoices.sales_tax`/`invoice_items.tax` (dollar amounts). **Neither `TaxCalculationService` method is correct for this branch** — `extractTaxFromInclusiveAmount()` would divide by `(1 + sales_tax)` where `sales_tax` might be, e.g., `13.33` (a dollar amount), producing nonsense.
3. **Everything else with `sales_tax > 0`** (tax added on top) and **`sales_tax <= 0`** (no tax) — both correctly collapse onto `TaxCalculationService::addTaxToExclusiveAmount()`, which already returns `base=amount, tax=0, total=amount` when the rate is `≤ 0`, exactly matching the current "no tax" display.

**Consequence for scope:** the `account_invoice` branch must be **excluded** from this migration and left exactly as it is today (direct subtraction, unchanged). Migrating it into either `TaxCalculationService` method would silently produce wrong numbers for that one transaction type. This is not a gap in `TaxCalculationService`'s design — it is a distinct calculation that was never one of the two formulas the service was built to centralize, and forcing it to fit would repeat the exact "unit mismatch" bug class this whole initiative exists to eliminate.

The fourth file (`_additional_charges.blade.php`) has only one condition (`sales_tax_type === 'add'`) and already maps directly and completely onto `addTaxToExclusiveAmount()` — no exclusions needed there.

## Why no new large-scale equivalence sweep is needed here

Unlike Phases 2.2–2.4, this phase does not require a new numerical equivalence proof. The formulas in scope are **character-for-character identical** to the ones already exhaustively proven equivalent to production behavior:

- The division formula (`amount - amount/(1+rate)`, computed without intermediate rounding, rounded only once for display) is the same formula validated in Phase 2.2's 5,000,000-combination sweep for `extractTaxFromInclusiveAmount()`.
- The multiplication formula (`amount*rate`, `amount+tax`) is the same formula validated in Phase 2.1's 3,000,000-combination sweep for `addTaxToExclusiveAmount()`.
- Unlike the Dashboard (Phase 2.3) and `SalesTaxReportEngine` (Phase 2.2) cases, **these four Blade views display one transaction's values per row, independently — there is no cross-row summation or aggregation happening in any of them.** This is squarely the "displaying an already-recorded transaction" case in `PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md`'s decision tree (§5, node 2) — the canonical transaction-safe use case, with none of the aggregation-order risk that drove the extra validation work in Phases 2.2–2.4.

## Objectives

- Replace the triplicated per-row conditional (currently repeated three times per file — once for the Amount cell, once for the Tax cell, once for the Total cell — each re-deriving which branch applies) with a single `TaxCalculationService` call per row, feeding all three display cells from one `TaxBreakdown` result.
- Preserve the `account_invoice` branch exactly as-is, untouched.
- Migrate `_additional_charges.blade.php`'s single-condition formula onto `addTaxToExclusiveAmount()`.
- Change no displayed value for any existing transaction.

## Scope

**In scope:**
- `resources/views/admin/crm/customers/partials/_tab_credit.blade.php` — the three display cells (Amount, Sales Tax, Total), lines ~424-484.
- `resources/views/front/customer/dashboard/partials/_tab_credit.blade.php` — the same three cells, lines ~318-368.
- `resources/views/admin/crm/customers/transaction_accounts_pdf.blade.php` — the equivalent `@php` computation block, lines ~104-128.
- `resources/views/admin/order_management/orders/partials/_additional_charges.blade.php` — the single tax calculation, lines ~32-35.

**Explicitly out of scope:**
- The `account_invoice` branch in all three `_tab_credit`/PDF files — left untouched, per the finding above.
- The JavaScript mirrors of this formula (`_tab_credit.blade.php:1407-1423` admin, `:508-524` front-end) — already documented as accepted, out-of-scope duplication in `FINANCIAL_ENGINE_TODO.md` Deferred Work, since JS cannot call a PHP service directly. Not touched in this phase.
- `SalesTaxReportEngine.php` — not touched.
- Any invoice, ledger, payment-allocation, or customer-balance write path — this phase touches display-only Blade code, not any write path.
- Any other column or section of these four Blade files (badges, action buttons, other table columns, PDF header/footer, etc.).

## Files expected to change

| File | Change |
|---|---|
| `resources/views/admin/crm/customers/partials/_tab_credit.blade.php` | Three cells' triplicated conditional replaced with one `@php` block computing a `TaxBreakdown` + three simple echoes; `account_invoice` branch preserved unchanged |
| `resources/views/front/customer/dashboard/partials/_tab_credit.blade.php` | Same consolidation, front-end mirror |
| `resources/views/admin/crm/customers/transaction_accounts_pdf.blade.php` | Same consolidation, PDF version |
| `resources/views/admin/order_management/orders/partials/_additional_charges.blade.php` | Single formula replaced with one `addTaxToExclusiveAmount()` call |

## Files out of scope

- `app/Services/TaxCalculationService.php` — no changes needed; both required methods already exist and are already proven correct for these exact formulas.
- `app/Services/Reports/SalesTaxReportEngine.php`
- `app/Services/InvoiceCalculationService.php`, `app/Helpers/CustomHelper.php`, and every ledger/invoice/balance write path.
- The JS mirrors named above.
- Any other Blade view not named above.

## Risk assessment

**Low.** This is the lowest-risk phase since Phase 2.1: no write path is touched, no aggregation-order question applies, and both formulas being centralized are already exhaustively proven correct by prior phases' numerical sweeps. The one thing that keeps this from being "very low" is the `account_invoice` exclusion — a careless implementation that missed this distinction (as a naive "just replace the formula" pass might) would silently break that one transaction type's display. This has been identified and documented before writing any code specifically to prevent that.

## Validation strategy

- **Formula-identity confirmation** (not a new numerical sweep): confirm, by direct comparison, that the Blade formulas being replaced are textually the same as the formulas already proven in Phase 2.1 (`addTaxToExclusiveAmount`) and Phase 2.2 (`extractTaxFromInclusiveAmount`) — done above, in the Pre-implementation finding.
- **Rendered-output snapshot**: since no test database is available in this environment (consistent with every prior phase), a live before/after render comparison cannot be executed here. Instead, each edited file's diff will be reviewed line-by-line to confirm the new `@php` block's branch conditions are logically identical to the three (now consolidated) conditions they replace, including the explicit preservation of the `account_invoice` branch.
- **What cannot be validated in this environment**: an actual rendered page/PDF comparison against real ledger data. Recommend a manual visual check (a staging customer's Credit tab, front-end portal, and a downloaded transaction PDF, for a mix of payment/charge/discount/refund/account_invoice rows) before merging, consistent with how every prior phase has flagged its environment-specific validation gap.

## Rollback strategy

- Each of the four files can be reverted independently via `git checkout -- <file>`.
- No database, migration, or write-path code is touched — rollback is a pure, zero-risk code revert.

## Success criteria

- All three `_tab_credit`/PDF files' Amount/Tax/Total cells are driven by a single `TaxCalculationService` call per row, not three independent re-derivations of the same condition.
- The `account_invoice` branch is confirmed unchanged in every file that has one.
- `_additional_charges.blade.php` uses `addTaxToExclusiveAmount()` directly.
- No other part of any of the four files is touched.
- `SalesTaxReportEngine.php` has zero diff.

## Expected pull request size

Small. Four Blade files, each a narrow, localized change (roughly 20-30 lines replaced with 10-15 lines per file, since consolidating three redundant conditionals into one is a net simplification). No new files, no test files (no Blade-rendering test infrastructure exists in this repo to add tests against, consistent with the testing-infrastructure gap already tracked in `FINANCIAL_ENGINE_TODO.md`).
