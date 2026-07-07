# PR-A2 — Review Verification

**Date:** 2026-07-07
**Depends on:** `PR-A2_TRANSACTION_WRAPPING.md` (the implementation this verifies).
**Code modified:** Yes — one new test added. No production code changed.
**Verdict:** **PASS — production-safe.** No changes required to the PR-A2 implementation.

---

## 1. Scope of this pass

The reviewer requested confirmation on 5 specific points before merging PR-A2: return values from `DB::transaction()`, nested transaction behavior, event-dispatch-inside-transaction assumptions, media upload side effects, and queued-vs-synchronous listener assumptions. Each is addressed below with either direct evidence already in hand or a new empirical test.

---

## 2. `return DB::transaction(function () { return response()->json(...); });`

**Status: confirmed safe, no further action.**

This is standard, documented Laravel behavior: `DB::transaction()` returns whatever its closure returns, once the transaction commits successfully. The outer `return DB::transaction(...)` in all 4 controllers simply forwards that value.

This isn't just a theoretical confirmation — it's already been exercised directly by the two happy-path tests (`test_happy_path_delivery_still_commits`, `test_happy_path_return_still_commits`), both of which assert `->assertOk()->assertJson(['success' => true])` on the actual `JsonResponse` object returned by the closure. If this pattern didn't work as expected, those tests would fail rather than pass.

---

## 3. Nested transactions — the substantive check

**Status: confirmed safe, backed by a new test.** This was the one item that genuinely needed verification rather than just reading code, so it got one.

**Finding:** `BillingEngine::charge()` (`app/Services/BillingEngine.php:44`) opens its own `DB::transaction()` internally. It's called from inside `SaveReturnController`'s damage-charge and fuel-charge blocks — both already wrapped in their own local `try/catch`. Under PR-A2, this call now executes *inside* the controller's new outer transaction, making it a genuinely nested transaction call.

**Why this matters:** Laravel's query builder is savepoint-aware for nested `transaction()` calls — when `transactionLevel() > 0`, a nested call issues `SAVEPOINT trans{N}` instead of a real `BEGIN`, and its "commit" issues `RELEASE SAVEPOINT trans{N}` instead of a real `COMMIT`. A `RELEASE SAVEPOINT` does not independently persist data — the data stays pending as part of the *outer* transaction. This means a charge created via `BillingEngine::charge()` should now correctly get rolled back if something later in `SaveReturnController` fails, whereas *before* PR-A2 (no outer transaction), `BillingEngine::charge()`'s own transaction would have been a real, standalone commit — a charge created there would have survived even if a later step failed. This is a genuine, beneficial behavior change introduced as a side effect of PR-A2, and it needed empirical proof, not just trust in how Laravel's docs describe savepoints.

**Test added:** `test_outer_rollback_also_undoes_a_billing_charge_created_inside_the_return_flow` in `tests/Feature/CustomerChecklists/ChecklistTransactionTest.php`. It:
1. Builds a real damaged-return scenario (a `CustomerAdminQuestionAnswer` with `is_damaged=true`, a matching order-level checklist question/answer with `user_return_amount=150.00`).
2. Submits `save-return` with that checklist, which causes `SaveReturnController` to successfully call `BillingEngine::charge()` and create a real `BillingCharge` row (inside the nested savepoint).
3. Forces the event listener to throw immediately afterward (same `Event::listen()` technique as the other rollback tests).
4. Asserts `BillingCharge::count() === 0` after the request — i.e., the charge created inside the nested transaction was correctly undone along with everything else, not left as an orphaned charge.

**Result:**
```
php artisan test --env=testing --filter=test_outer_rollback_also_undoes_a_billing_charge_created_inside_the_return_flow
→ 1 passed (4 assertions)
```

**Conclusion:** nesting is correct and safe. No orphaned billing charges are possible from a later failure in the same request. This does **not** touch billing idempotency logic (Issue #1) — the idempotency *keys* and duplicate-guard logic are completely unchanged; this only concerns transactional durability, which is exactly PR-A2's stated purpose.

---

## 4. Media upload side effects

**Status: confirmed, no new risk, no duplicate-on-retry risk.**

Reviewed `MediaHelper::uploadStorageFile()` directly. Two separate effects on every call:
1. A physical file write to storage, with a **randomized filename** (`Str::random(6) . '-media-' . ...`) — this is *not* covered by transaction rollback (transactions don't span the filesystem).
2. A `Media::create([...])` database row — this *is* covered by the transaction and correctly rolls back with everything else.

**On the "duplicate on retry" concern specifically:** because the filename includes a fresh 6-character random prefix on every call, a retried request after a rollback generates an entirely new filename — it cannot collide with or duplicate the orphaned file from the failed attempt. The only residual effect is exactly what `CORRECTION_PHASE1_PLAN.md` already documented and explicitly accepted: an orphaned file with no `Media` row pointing at it, which is inert (never served, never referenced, never counted in storage-usage queries that join through `Media`). No new duplicate-file or duplicate-charge risk exists. Not engineered around, per the existing plan's own rationale — doing so would require a two-phase filesystem/DB commit, disproportionate to the actual risk.

---

## 5. Queued vs. synchronous listener assumption

**Status: confirmed unchanged, documented for future readers.**

Verified directly: neither `OrderCustomerChecklistListener` nor `OrderProductDriverChecklistUpdatedListener` implements `ShouldQueue` — both remain plain, synchronous listener classes, exactly as `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` §5 originally documented. PR-A2's entire transaction-wrapping strategy *depends* on this: a queued listener would run in a separate process, after the HTTP response (and the transaction) had already completed, meaning wrapping the `event()` dispatch in `DB::transaction()` would no longer provide any rollback protection for whatever that listener does.

**This is worth flagging explicitly for future maintainers** (not a defect in this PR, but a load-bearing assumption): if either listener is ever converted to `ShouldQueue` for performance reasons, PR-A2's rollback guarantee silently stops applying to it, with no error or warning — the code would still run, just without the safety net. Recommend adding this exact note as a code comment on both listener classes in a future small PR, or tracking it in `IMPLEMENTATION_ROADMAP.md`'s backlog so it isn't lost. Not doing so now, since it's outside PR-A2's stated scope and doesn't require a code change today.

---

## 6. Final test tally

```
php artisan test --env=testing tests/Feature/CustomerChecklists/ChecklistTransactionTest.php
→ 8 passed (40 assertions)     — was 7 passed (36 assertions) before this verification pass;
                                  the 8th is the new nested-transaction test in §3.
```

All previously-passing PR-A2 and PR-A1 tests remain green; nothing in this verification pass required changing any production code.

---

## 7. Verdict

| Concern raised | Resolution |
|---|---|
| `return DB::transaction(...)` correctness | Confirmed via existing happy-path tests — no issue |
| Nested transactions (BillingEngine) | Confirmed safe via new empirical test — savepoints work correctly, no orphaned charges possible |
| Media upload side effects | Confirmed no duplicate-file risk (randomized filenames); orphaned-file trade-off already accepted in the roadmap |
| Queued vs. synchronous listeners | Confirmed both listeners remain synchronous today; flagged as a load-bearing assumption worth a future comment, not a blocker |

**PR-A2 is production-safe as implemented. No changes required. Recommend merging.**

Next per the roadmap: **PR-A3 — Billing Idempotency Fix**, which is now on a firmer foundation since PR-A2 confirmed the transaction boundary it will build on top of already works correctly, including around the exact `BillingEngine::charge()` call path PR-A3 will modify.
