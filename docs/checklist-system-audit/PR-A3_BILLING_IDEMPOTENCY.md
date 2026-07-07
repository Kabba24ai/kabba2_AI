# PR-A3 — Billing Idempotency Fix

**Date:** 2026-07-07
**Depends on:** `CORRECTION_PHASE1_PLAN.md` Issue #1, `PHASE3_RESULTS.md` (the runtime test that first reproduced this bug), `PR-A2_TRANSACTION_WRAPPING.md` / `PR-A2_REVIEW_VERIFICATION.md` (the transaction boundary this fix builds on top of).
**Code modified:** Yes.
**Status:** Code/tests complete and verified. **Not cleared for production deploy** — finance reconciliation gate still applies (see §6).

---

## 1. Problem fixed

A rental order product's `OrderProduct` row is reused across multiple delivery→return rental cycles rather than being recreated per cycle. Both of the system's duplicate-charge protections keyed only on `order_product_id`:

- `BillingChargeRequest::mobileReturnDamage()`'s idempotency key: `"mobile_checklist:{orderProductId}:damage"` — no per-cycle disambiguator at all.
- `ChargeService::createFromOrderProduct()`'s legacy `CustomerAccount` duplicate guard: "does a pending/completed charge already exist for this `order_product_id` + reason" — also no per-cycle disambiguator.

**Effect:** a legitimate **second** rental cycle's damage charge and fuel charge on the same order product were **silently dropped** — no error, no charge, no trace anywhere. Confirmed reproduced live in `PHASE3_RESULTS.md` (§1, V8 idempotency test / §2 N3): a full second cycle's $200 damage charge and $15 fuel charge both produced zero new rows in `billing_charges` or `customer_accounts`.

This is real revenue-impacting, not theoretical — `PHASE3_RESULTS.md` classified it **NO-GO on billing changes until finance reconciles historical impact**, which remains true after this fix (see §6).

---

## 2. Files changed

| File | Change |
|---|---|
| `app/Http/DataObjects/BillingChargeRequest.php` | `mobileReturnDamage()` gained a `string $cycleKey = 'nocycle'` parameter, folded into the idempotency key: `"mobile_checklist:{orderProductId}:damage:{cycleKey}"`. Also corrected a stale docblock that incorrectly claimed this method was unused/greenfield (it's actively called from `SaveReturnController`). |
| `app/Services/ChargeService.php` | `createFromOrderProduct()` gained an optional trailing `$cycleStartedAt` parameter. The duplicate-guard query now adds `->where('created_at', '>=', $cycleStartedAt)` when provided. `null` (the default) preserves the exact prior behavior — confirmed via grep this is the only caller, so no other call site is affected. |
| `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` | Computes `$cycleKey` and `$cycleStartedAt` once per request (see §3) and wires them into the damage-charge call, the inline fuel-charge idempotency key, and the `ChargeService` call. |
| `tests/Feature/BillingEngine/MobileReturnFuelBridgeTest.php` | Updated 2 pre-existing assertions that hardcoded the *old* idempotency key format (no cycle suffix) to expect the new `:nocycle` fallback — this fixture has no checklist rows, so that's the correct new expected value, not a workaround. |
| `tests/Feature/BillingEngine/MobileReturnCycleIdempotencyTest.php` *(new)* | 3 tests proving the fix — see §4. |

No UI changes. No schema/migration changes — the fix deliberately reuses existing data rather than adding a column, per `CORRECTION_PHASE1_PLAN.md`'s own stated rationale (see §3).

---

## 3. The cycleKey / cycleStartedAt approach

Per `CORRECTION_PHASE1_PLAN.md` Issue #1's recommended fix: **no new schema column.** A cycle-scoped disambiguator is derived from data that already exists and is already guaranteed to change on every delivery: `SaveDeliveryController` unconditionally deletes and recreates every `order_product_checklist_questions` row on every delivery call (confirmed in `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` §3 and re-confirmed live in `PHASE3_RESULTS.md` §2/N1 — this happens even when the same equipment is redelivered). The freshly-generated primary keys of those rows are therefore a reliable, already-present per-cycle nonce.

In `SaveReturnController`, computed once per request from the order product's currently-active (non-deleted) checklist question batch:

```php
$cycleKey       = (string) ($orderProduct->checklistQuestions->min('id') ?? 'nocycle');
$cycleStartedAt = $orderProduct->checklistQuestions->min('created_at');
```

- **`$cycleKey`** (a string, always non-null via the `'nocycle'` fallback) is appended directly into the `billing_charges.idempotency_key` string for both damage and fuel charges — the primary, strongest mechanism, since that column exists specifically to hold compound keys like this.
- **`$cycleStartedAt`** (nullable) is passed to `ChargeService::createFromOrderProduct()` as a `created_at >=` cutoff on its duplicate-guard query — the fallback mechanism, since `customer_accounts` has no `idempotency_key` column and adding one was out of scope (no schema changes).

**Within-cycle duplicate protection is preserved by construction:** because the checklist question batch doesn't change between a return submission and a retry of that same submission (only a new *delivery* recreates it), `$cycleKey`/`$cycleStartedAt` stay identical across retries within the same cycle — the exact same idempotency key / cutoff timestamp is produced, so the existing dedup logic still catches same-cycle duplicates exactly as before.

---

## 4. Tests run and results

### New test file: `tests/Feature/BillingEngine/MobileReturnCycleIdempotencyTest.php`

1. `test_single_cycle_creates_one_damage_and_one_fuel_charge` — baseline sanity.
2. `test_second_rental_cycle_creates_a_second_damage_and_fuel_charge` — simulates a full second delivery→return cycle (fresh checklist batch = new cycleKey) with a new damage amount and new fuel reading; asserts counts go **1 → 2** for both charge types, with the correct new amounts persisted (not just a count bump). This is the direct regression test for the bug — before the fix, both counts would stay at 1.
3. `test_duplicate_retry_within_same_cycle_does_not_double_charge` — exercises `BillingEngine::charge()` and `ChargeService::createFromOrderProduct()` directly, twice, with the same cycle key/timestamp, asserting only one charge/record is created each time.

   **Design note:** a true end-to-end HTTP retry of the same return submission can't be exercised here — `SaveReturnController`'s own pre-existing, out-of-scope equipment-status guard already rejects a second call once the first has transitioned equipment away from `'rented'`, in both the old and new code. Testing at the service layer directly is the correct altitude for what this PR actually changed (the idempotency mechanism itself), rather than forcing an unrealistic HTTP scenario blocked by unrelated logic.

   **Design note 2:** the second-cycle test required `$this->travel(1)->day()` between simulated cycles. `created_at` columns are second-precision; running two entire rental cycles within the same wall-clock second (only possible in a fast automated test — real cycles are hours/days apart) made the `ChargeService` cutoff comparison ambiguous. This isn't a workaround for a bug — it's making the test accurately simulate realistic elapsed time instead of artificially compressing two rental cycles into the same database second, which was never a realistic scenario.

### Full verification (after the MySQL outage described in the prior session was resolved and confirmed restored)

```
php artisan test --env=testing tests/Feature/BillingEngine
→ 206 total, 205 passed, 1 failed, 0 skipped (428 assertions), duration 1097.5s

php artisan test --env=testing tests/Feature/BillingEngine/MobileReturnCycleIdempotencyTest.php
→ 3 total, 3 passed, 0 failed, 0 skipped (20 assertions), duration 83.2s
```

**New failures introduced by PR-A3: 0.**

---

## 5. Known pre-existing test failure (not caused by PR-A3)

`MobileReturnFuelBridgeTest > billing engine failure is logged to billing engine channel` fails with:

```
Method error(<Any Arguments>) from Mockery_2_Illuminate_Log_LogManager should be called
exactly 1 times but called 2 times.
```

This failure has been independently reproduced **four separate times** across this project's review sessions:
1. During the PR-A1 review, re-confirmed by `git stash`-ing all PR-A1 changes and re-running against untouched code.
2. During the PR-A2 review-verification pass.
3. In the first (concurrency-contaminated) PR-A3 regression attempt.
4. In this PR-A3 final clean verification run.

It has no reference to `SaveReturnController`, `ChargeService`, `BillingChargeRequest`, `cycleKey`, or `cycleStartedAt` anywhere in its own test file. It is a pre-existing Mockery call-count flake in an unrelated code path, not a regression from any PR in this series.

---

## 6. Finance reconciliation — production deployment gate

**This fix is not cleared for production deployment.** Per `PHASE3_RESULTS.md` §4's own decision framework: *"NO-GO on billing changes until finance/accounting reconciles historical impact."* That framework was triggered because the idempotency-scope bug was reproduced as a certainty (not just a theoretical risk) during runtime testing.

**What this means concretely:**
- Building, testing, and deploying this fix to **staging** is not blocked.
- Deploying to **production** requires a separate, non-code, finance/accounting task first: a read-only reconciliation query across historical `order_products` with more than one delivery→return cycle, to determine whether any real customer had a legitimate second damage or fuel charge silently dropped in the past. If so, that may require a manual billing correction — a decision for finance, not engineering, and independent of whether this code fix ships.
- This code fix does **not** retroactively re-key or touch any existing `billing_charges.idempotency_key` value. Per `CORRECTION_PHASE1_PLAN.md`'s own guidance: existing charges are already correctly protected against duplicates for charges already created and reconciled — no backfill, no data migration.

This gate is unchanged by anything done in this PR and was not evaluated as part of code/test work — it requires access to real financial records that engineering does not have.

---

## 7. Rollback notes

**Rollback risk: low for the code itself.** The change is additive to key/guard construction logic and does not alter the shape of `billing_charges` or `customer_accounts` (no migration was run). If reverted:
- The system returns to the current (buggy but stable-in-the-sense-of-not-erroring) behavior: second-cycle charges silently drop again, with no error to the caller.
- No cleanup is needed — reverting doesn't leave orphaned data, since the fix doesn't move or delete anything, only changes what counts as "already charged" going forward.

**The real risk being changed is financial, not technical:** once this fix is live, new legitimate second-cycle charges will start being created that were previously silently dropped. If this fix is later reverted after being live for a period, any charges already created during that window remain valid and do not need to be undone. Conversely, be aware the fix narrows the idempotency key's "catch-all" breadth (from "ever, on this order product" to "within this specific cycle") — a bug in the cycle-key derivation could theoretically let a real duplicate slip through where the old, over-broad key would have coincidentally blocked it. This is exactly why `test_duplicate_retry_within_same_cycle_does_not_double_charge` (§4) is a mandatory regression guard, not a nice-to-have.

**No feature flag was added.** Given the low technical rollback risk and the existing finance gate already controlling the production release timing, a flag was judged unnecessary additional complexity for this fix.

---

## 8. Scope discipline

- **Billing idempotency only** — the specific bug named in this PR's scope. No other billing logic (tax calculation, payment recording, resolution/uncollectible flows) was touched.
- **PR-A2's transaction boundary was used, not modified.** `SaveReturnController`'s existing `DB::transaction()` wrap (from PR-A2) was left exactly as-is; this fix only changes what happens *inside* it.
- **No PR-A4 work has been started.** This document, and the code/tests it describes, are the entirety of PR-A3's scope.

No code has been committed as of this document.
