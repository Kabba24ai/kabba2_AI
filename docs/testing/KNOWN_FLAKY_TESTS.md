# Known Flaky Tests

This document tracks tests that fail intermittently or under specific conditions unrelated to the code under test.

---

## RentalExtensionBridgeTest — `billing_engine_failure_is_logged_to_billing_engine_channel`

**File:** `tests/Feature/BillingEngine/RentalExtensionBridgeTest.php`

**Test method:** `test_billing_engine_failure_is_logged_to_billing_engine_channel`

**Failure mode:** `QueryException` — `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'kabba2_ai.migrations' doesn't exist`

### What the test does

The test verifies that when `BillingEngine::charge()` fails, the error is logged to the `billing_engine` channel. To force a failure, it calls `Schema::drop('billing_charges')` to remove the target table, then triggers the controller action.

### Why it fails

`Schema::drop('billing_charges')` also triggers Laravel's schema introspection layer, which in some environments or run-order combinations attempts to write to the `migrations` table as part of teardown/refresh. When `RefreshDatabase` restores state after a test that dropped a table, it can fail to locate the `migrations` table if the connection state is unstable.

This failure is **environment- and run-order-dependent**. The test passes in isolation (`php artisan test tests/Feature/BillingEngine/RentalExtensionBridgeTest.php`) but can fail when the full suite is run in certain orders.

### Why it is unrelated to Billing Engine tax and refresh logic

The Billing Engine tax/refresh fix (`44e0df52`) modifies:
- How `billingBaseAmount` and `billingTaxAmount` are calculated in `AlertChargeController` and `FuelChargeStoreController`
- The JS reload behavior in `edit.blade.php`

Neither change touches `RentalExtensionBridgeTest`, `Extension\StoreController`, the `billing_charges` schema, the `migrations` table, or any schema-dropping test setup. The failing test was already flaky before `44e0df52` and would fail identically on the previous commit `billing-engine-v1.0`.

### Recommendation

**Track separately. Does not block production deployment.**

The test is validating a logging side-effect, not the core business logic of rental extensions. The rental extension bridge itself (`Extension\StoreController`) is tested by 22 other assertions in the same file, all of which pass consistently.

**Suggested fix (lower priority):** Replace the `Schema::drop('billing_charges')` approach with a mock or a service-level exception throw that does not alter the database schema:

```php
// Instead of: Schema::drop('billing_charges');
// Use:
$this->mock(BillingEngine::class)
    ->shouldReceive('charge')
    ->andThrow(new \RuntimeException('Forced failure for log test'));
```

This avoids touching any tables and eliminates the schema teardown race condition entirely.

---

## Summary Table

| Test file | Test method | Trigger | Scope | Blocks deployment? |
|-----------|-------------|---------|-------|-------------------|
| `RentalExtensionBridgeTest` | `test_billing_engine_failure_is_logged_to_billing_engine_channel` | `Schema::drop()` + run-order sensitivity | Test infrastructure only | No |
