# Phase 2.4 — Validation Addendum: Synthetic Invoice Recompute Test

Addendum date: 2026-07-01
Branch: `raj_development`
Status: **Follow-up to `PHASE_2_4_COMPLETION_REPORT.md` Outstanding Issue #1.** Documents a synthetic, local-only, non-destructive validation of `InvoiceCalculationService`, and a separately-discovered environment/testing-infrastructure issue. **Does not close Outstanding Issue #1** — real production/staging drift detection remains unperformed; see "What was not validated" below.

---

## Why this addendum exists

The Phase 2.4 Completion Report proved `InvoiceCalculationService`'s equivalence to the pre-migration inline arithmetic algebraically, and disclosed that the actual staging drift-detection snapshot recommended before merge could not be run in this environment (no accessible database). This addendum documents an attempt to close as much of that gap as is honestly possible without real invoice data: a synthetic, fully local, rolled-back test of the actual `InvoiceCalculationService` class against a representative scenario. It also documents a genuine, separate discovery made while attempting this — an environment/testing-infrastructure bug unrelated to the Financial Engine — kept clearly out of this initiative's scope per explicit instruction.

## What was validated

**The mechanism.** `InvoiceCalculationService::recomputeSummary()` — the real, shipped class, not a reimplementation — was exercised against a minimal synthetic invoice, invoice item, and payment ledger, inside a database transaction that was rolled back at the end of the run. Nothing was persisted anywhere, including in the local sandbox SQLite database (verified after the fact — see "Non-destructiveness" below).

### Synthetic scenario used

| Field | Value |
|---|---|
| Invoice line item (type `order`) — unit price | $500.00 |
| Invoice line item — tax | $44.35 |
| Invoice subtotal (sum of items) | $500.00 |
| Invoice sales tax (sum of items) | $44.35 |
| Invoice total | $544.35 |
| Prior payment (ledger row, `type=payment`, dated before the test) | $200.00 |
| Invoice's stored state *before* the new payment | `paid_amount=$200.00`, `open_amount=$344.35`, `invoice_status=partial_paid` |
| New payment (ledger row created, simulating a payment controller) | $150.00 |
| Expected `paid_amount` after recompute | $350.00 (200.00 + 150.00) |
| Expected `open_amount` after recompute | $194.35 (544.35 − 350.00) |
| Expected `invoice_status` after recompute | `partial_paid` |

This scenario is deliberately the same shape as the worked example in `PHASE_2_4_COMPLETION_REPORT.md`'s algebraic proof — a consistent invoice (stored total already matches its items) receiving an additional partial payment — chosen to directly test the claim that report made, not a new or different claim.

### Result

```
=== BEFORE (stored, prior to new payment + recompute) ===
subtotal: 500, sales_tax: 44.35, total: 544.35, paid_amount: 200, open_amount: 344.35, invoice_status: partial_paid

=== AFTER (InvoiceCalculationService::recomputeSummary) ===
subtotal: 500, sales_tax: 44.35, total: 544.35, paid_amount: 350, open_amount: 194.35, invoice_status: partial_paid

=== EXPECTED ===
subtotal: 500, sales_tax: 44.35, total: 544.35, paid_amount: 350, open_amount: 194.35, invoice_status: partial_paid

RESULT: MATCH
```

**`InvoiceCalculationService::recomputeSummary()` produced exactly the expected values.** This confirms, against real (if synthetic) database rows and the actual service class, what the algebraic proof already claimed: for a consistent invoice, the recompute-based approach reproduces the same `paid_amount`/`open_amount`/`invoice_status` the old inline arithmetic would have produced.

### Non-destructiveness

The entire fixture (one `users` row, one `customers` row, one `invoices` row, one `invoice_items` row, two `customer_accounts` rows) was created and the recompute was executed **inside a single database transaction that was explicitly rolled back** in a `finally` block, guaranteeing rollback even on an unexpected error. Verified after the run: `SELECT COUNT(*)` against `invoices`, `customer_accounts`, `customers`, and `users` all returned `0`. No data persisted anywhere, including in the local sandbox SQLite file — which itself contains no production or staging data (see below).

## What was not validated

**Real production or staging invoice drift.** This synthetic test proves the *mechanism* is correct for a representative, hand-constructed, already-consistent scenario. It does **not** and **cannot** answer the actual question Outstanding Issue #1 raises: whether any *real* invoice, in actual production or staging data, has already drifted (its stored `total` no longer matching its current line items, or its stored `paid_amount` no longer matching the ledger). That question requires querying real data, and:

- This environment's local SQLite database file was, before this session, genuinely empty (0 bytes) — no schema, no data of any kind, real or synthetic.
- Migrations were run locally, against this same empty, non-production file, solely to create the schema needed for the synthetic test above. This did not involve, touch, or require any production or staging system.
- No staging or production database connection is available in this environment, and none was used at any point in this validation.

**Outstanding Issue #1 from `PHASE_2_4_COMPLETION_REPORT.md` remains open.** The specific recommended action is unchanged: run the drift-detection snapshot (before/after `recomputeSummary()` diff, for every open/partially-paid invoice) against a real copy of production or staging data, in an environment with access to it.

## A separately-discovered issue: console/test bootstrap crash (not a Financial Engine defect)

While attempting to run migrations and this validation script, every `php artisan` command (and, by the same mechanism, any Laravel Feature test, since both fully bootstrap the framework) initially produced **zero output and exited silently**. Root cause, isolated by direct inspection: `App\Providers\AppServiceProvider::enableHttps()` runs unconditionally on every application boot and attempts an HTTP redirect (`header('Location: ...')` + `exit`) whenever `config('app.vite_origin_protocol') === 'https'` — which this environment's `.env` (`VITE_ORIGIN_PROTOCOL=https`) sets unconditionally, with **no `runningInConsole()` guard**. In a console context, this either exits before any output is flushed or throws an uncaught `ErrorException` ("headers already sent").

**This explains, more precisely, the "Feature suite cannot be run in this environment" limitation documented in every phase's completion report since Phase 2.1** — it was not solely a missing test-database configuration (though that is also true); it was this unconditional, unguarded HTTPS-redirect bootstrap crash. Both facts are now recorded.

**This is an environment/testing-infrastructure issue, not a Financial Engine implementation defect**, and per explicit instruction it has **not** been fixed as part of this or any Financial Engine phase. It is recorded in `FINANCIAL_ENGINE_TODO.md`'s Open Technical Decisions as a discovered issue and a proposed (not-yet-authorized) future fix, kept clearly separate from this initiative's scope.

**Workaround used for this validation only:** a runtime environment variable override, `VITE_ORIGIN_PROTOCOL=http php ...`, applied only to the specific `artisan migrate` and validation-script invocations in this session. This changes nothing on disk — no `.env` file, no application code, no configuration file was modified. The override exists only for the lifetime of each individual command it was prefixed to.

## The temporary validation script

Location: `/private/tmp/claude-501/.../scratchpad/invoice_calc_synthetic_validation.php` (session scratchpad — outside the repository, not committed, not tracked by git). It is not clean or generalized enough to be a permanent addition as-is (hardcoded scenario, manual field-by-field fixture construction, no assertion framework) — if the team wants a permanent, reusable version of this test, it should be rebuilt as a proper Feature test using model factories under `tests/Feature/`, which would also require creating the currently-missing `Invoice`/`InvoiceItem`/`CustomerAccount` factories (see `FINANCIAL_ENGINE_TODO.md` Open Architectural Tasks). This addendum's script has not been committed and will not be, absent explicit approval to formalize it as reusable test infrastructure.

## Answers to the validation questions

- **Does `InvoiceCalculationService` behave correctly against synthetic invoice/payment data?** Yes — exact match against the expected values, for the scenario tested.
- **Is Phase 2.4 safe to merge?** The mechanism is now validated two ways — algebraically (Completion Report) and against a real (synthetic) database execution of the actual service class (this addendum) — both in agreement. Combined with the earlier unit-test suite (21/21 passing) and the discovery/correction of the front-end controller ordering bug, this is a reasonably strong basis for merging. **The one caveat is unchanged and still open**: whether *real* production data already contains drifted invoices is still unknown and unverifiable in this environment. This is a judgment call for the team — merge now and monitor (`InvoiceCalculationService` will surface/self-correct any real drift the first time a payment triggers it, which may itself be an acceptable, even desirable, outcome), or run the staging snapshot first. Both are reasonable; this addendum does not make that call, only supplies the evidence for it.
- **Is a separate invoice-drift repair plan needed before or after merge?** Not necessarily a *repair* plan — nothing here indicates drift definitely exists. What's needed is the **detection** step (the staging snapshot from Outstanding Issue #1), which should determine whether a repair plan is warranted at all. Recommend treating "run the detection snapshot" as a fast-follow task, not a merge blocker, given the synthetic validation now provides reasonable confidence in the mechanism itself.

## Phase 2.5

**Not started.** This addendum is validation-only, per instruction.
