# Bug Fix — Remove Live `dd()` from CustomerAdmin\Question\StoreController

**Date:** 2026-07-11
**Type:** Small, standalone bug fix — not part of the PR-B4.2 duplication-reduction refactor.
**Found by:** `PR-B4_2_QUESTION_READINESS.md` §0 (Question CRUD readiness review).

---

## 1. The bug

`app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/StoreController.php`'s `catch (\Throwable $e)` block contained a live `dd($e->getMessage(), $e->getTraceAsString());` call. `dd()` dumps its arguments and then calls `exit()` — it never returns a normal HTTP response. Any exception during Customer Admin question creation (a bad category, a DB constraint violation, anything) did not flash an error or redirect the user; it terminated the request and displayed a raw debug page (exception message + full stack trace) instead.

## 2. The fix

Removed the single `dd($e->getMessage(), $e->getTraceAsString());` line. Nothing else in the catch block changed — it already matched the same rollback/report/flash/redirect shape used by every sibling Customer Admin and Rental Ready CRUD controller in this codebase:

```php
} catch (\Throwable $e) {
    DB::rollBack();
    report($e);

    flash('Something went wrong while creating the question.')->error();

    return redirect()
        ->back()
        ->withInput()
        ->withErrors(['error' => 'An error occurred while creating the question.']);
}
```

- **Rollback:** `DB::rollBack()` — unchanged, was already present.
- **Logging:** `report($e)` — unchanged, was already present, consistent with every other CRUD controller reviewed in PR-B4.1/PR-B4.2.
- **Flash:** `flash('Something went wrong while creating the question.')->error()` — unchanged, was already present.
- **Redirect:** `redirect()->back()->withInput()->withErrors(['error' => ...])` — unchanged, was already present, matching the exact pattern used by `RentalReady\Question\StoreController` and both Category `StoreController`s.

No validation rule, success-path behavior, or Question CRUD structure was touched — this is a one-line removal, not a refactor.

## 3. Files changed

| File | Change |
|---|---|
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/StoreController.php` | Removed the single `dd($e->getMessage(), $e->getTraceAsString());` line from the catch block. No other line changed. |
| `tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php` | Added one new regression test: `test_customer_admin_question_store_rolls_back_and_flashes_error_when_an_answer_creation_fails`. |

**Not touched:** every other Question CRUD controller, both Question `Request` classes, both Question models, validation rules, success-path behavior, routes, views. PR-B4.2's refactor itself has not started.

## 4. Regression test

`test_customer_admin_question_store_rolls_back_and_flashes_error_when_an_answer_creation_fails` (in `QuestionCrudCharacterizationTest.php`) forces the second answer's `creating` event to throw during a Customer Admin question submission, then proves all three required properties:

1. **The request does not terminate with a debug dump.** Before this fix, this exact scenario would have called `exit()` inside the controller, which would have killed the PHPUnit process outright rather than returning a `TestResponse` — the mere fact that this test can make normal `assertRedirect()`/`assertSessionHasErrors()` assertions on a returned response is itself the proof.
2. **The transaction rolls back.** Neither the question nor the first (successfully created before the forced failure) answer was left behind: `CustomerAdminQuestion::count()` and `CustomerAdminQuestionAnswer::count()` are both asserted to be `0` afterward.
3. **The expected redirect/flash behavior occurs.** `$response->assertRedirect()` and `$response->assertSessionHasErrors('error')` confirm the same user-facing error path every other CRUD controller in this codebase already uses.

## 5. Commands run

```bash
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/StoreController.php
php -l tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php

php artisan test --env=testing tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php
```

*(One test run in this session hit a genuine MySQL server outage on the local dev machine — `SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it` — confirmed independently via a direct `mysql -h127.0.0.1 SELECT 1` check, unrelated to this change. The user restarted MySQL and the suite was re-run to a clean pass.)*

## 6. Pass/fail counts

```
php artisan test --env=testing tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php
→ 18 passed (76 assertions)
```

This is the original 17-test PR-B4.2 characterization baseline **plus** the 1 new bug-fix regression test — **all 17 original tests pass with zero assertion changes**, confirming this fix did not alter any other observable behavior (validation, success path, Rental Ready behavior, or any other Customer Admin CRUD flow).

## 7. Confirmation

- The `dd()` call is gone; the catch block's rollback/log/flash/redirect behavior is otherwise byte-for-byte unchanged.
- All 17 pre-existing PR-B4.2 characterization tests pass unmodified.
- 1 new regression test proves the fix concretely (not terminating, rolling back, flashing/redirecting correctly).
- No Question CRUD refactor was started. No other controller, request, model, route, or view was touched.

**Stopping after this bug fix as instructed.** Not beginning PR-B4.2's refactor or PR-B4.3.
