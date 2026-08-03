<?php

namespace Tests\Feature\Goodwill;

use App\Enums\Goodwill\GoodwillReason;
use App\Enums\Goodwill\GoodwillReasonCategory;
use App\Models\Customers\Customer;
use App\Models\Discounts\ProductDiscount;
use App\Models\Goodwill\OrderGoodwillAdjustment;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Goodwill\GoodwillException;
use App\Services\Goodwill\GoodwillPermissions;
use Database\Seeders\Iam\GoodwillPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Increment G1 — the Goodwill domain and persistence layer.
 *
 * SCOPE. Nothing here applies a concession, prices anything, or touches an
 * order's totals. G1 builds the record of the business DECISION; the money is
 * the shared engine's, and is exercised by its own suites.
 *
 * The two load-bearing tests are
 * `test_direct_spatie_check_refuses_where_the_gate_would_consent` — which
 * proves the authority check survives the application-wide `Gate::before`
 * bypass — and `test_audit_fields_cannot_be_edited_after_the_record_exists`,
 * which proves the record documents the decision rather than the last edit.
 */
class GoodwillDomainTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private User $manager;

    private Order $order;

    private ProductDiscount $discount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Good', 'last_name' => 'Will',
            'email' => 'goodwill-domain@example.com', 'status' => 'Active',
        ]);

        $this->manager = $this->user('manager@test.local');

        $this->order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Good Will',
            'subtotal' => 200.00,
            'tax_amount' => 19.50,
            'special_tax_amount' => 4.00,
            'added_fees_amount' => 5.00,
            'pretax_discount_total' => 0,
            'discount_amount' => 0,
            'grand_total' => 228.50,
        ]);

        $this->discount = $this->productDiscount(50.00);
    }

    // ── 1–3. The reason enum: codes, categories, labels ────────────────────

    public function test_every_reason_exposes_a_stable_uppercase_code(): void
    {
        // The BACKING VALUE is the contract. Reports group on it and rows
        // persist it, so it must never be a display string.
        foreach (GoodwillReason::cases() as $reason) {
            $this->assertSame(
                strtoupper($reason->value),
                $reason->value,
                "Reason code {$reason->value} must be an uppercase stable code."
            );
            $this->assertMatchesRegularExpression('/^[A-Z][A-Z_]*[A-Z]$/', $reason->value);
        }

        // The exact codes are the approved vocabulary. Renaming one is a data
        // migration, not a refactor — this list is what makes that visible.
        $this->assertSame([
            'SERVICE_FAILURE', 'EQUIPMENT_ISSUE', 'DAMAGED_PRODUCT', 'BILLING_ERROR', 'DELIVERY_PICKUP_ISSUE',
            'REPEAT_CUSTOMER', 'CUSTOMER_LOYALTY', 'CUSTOMER_RETENTION', 'MULTIPLE_ITEMS',
            'LARGE_ORDER', 'PRICE_MATCH', 'PROMOTIONAL_COURTESY', 'MANAGER_COURTESY',
            'OTHER',
        ], array_column(GoodwillReason::cases(), 'value'));
    }

    public function test_each_reason_maps_to_exactly_one_category(): void
    {
        $expected = [
            'SERVICE_FAILURE' => GoodwillReasonCategory::ServiceRecovery,
            'EQUIPMENT_ISSUE' => GoodwillReasonCategory::ServiceRecovery,
            'DAMAGED_PRODUCT' => GoodwillReasonCategory::ServiceRecovery,
            'BILLING_ERROR' => GoodwillReasonCategory::ServiceRecovery,
            'DELIVERY_PICKUP_ISSUE' => GoodwillReasonCategory::ServiceRecovery,

            'REPEAT_CUSTOMER' => GoodwillReasonCategory::BusinessCourtesy,
            'CUSTOMER_LOYALTY' => GoodwillReasonCategory::BusinessCourtesy,
            'CUSTOMER_RETENTION' => GoodwillReasonCategory::BusinessCourtesy,
            'MULTIPLE_ITEMS' => GoodwillReasonCategory::BusinessCourtesy,
            'LARGE_ORDER' => GoodwillReasonCategory::BusinessCourtesy,
            'PRICE_MATCH' => GoodwillReasonCategory::BusinessCourtesy,
            'PROMOTIONAL_COURTESY' => GoodwillReasonCategory::BusinessCourtesy,
            'MANAGER_COURTESY' => GoodwillReasonCategory::BusinessCourtesy,

            'OTHER' => GoodwillReasonCategory::Other,
        ];

        foreach (GoodwillReason::cases() as $reason) {
            $this->assertSame($expected[$reason->value], $reason->category(), "Category for {$reason->value}");
        }

        $this->assertCount(5, GoodwillReasonCategory::ServiceRecovery->reasons());
        $this->assertCount(8, GoodwillReasonCategory::BusinessCourtesy->reasons());
        $this->assertCount(1, GoodwillReasonCategory::Other->reasons());
    }

    public function test_labels_are_presentation_only_and_never_equal_the_code(): void
    {
        foreach (GoodwillReason::cases() as $reason) {
            $this->assertNotSame($reason->value, $reason->label());
            $this->assertNotSame('', trim($reason->label()));
        }

        $this->assertSame('Service Failure', GoodwillReason::ServiceFailure->label());
        $this->assertSame('Delivery / Pickup Issue', GoodwillReason::DeliveryPickupIssue->label());
        $this->assertSame('Manager Courtesy', GoodwillReason::ManagerCourtesy->label());

        $this->assertSame('Service Recovery', GoodwillReasonCategory::ServiceRecovery->label());
        $this->assertSame('Business Courtesy', GoodwillReasonCategory::BusinessCourtesy->label());
    }

    public function test_the_category_is_queryable_without_parsing_labels(): void
    {
        // The reporting requirement in one assertion: group by a stored column,
        // never by a string a copy change could alter.
        $this->adjustment(['reason_code' => GoodwillReason::ServiceFailure, 'idempotency_key' => 'gw-cat-1']);
        $this->adjustment([
            'reason_code' => GoodwillReason::LargeOrder,
            'product_discount_id' => $this->productDiscount(10.00)->id,
            'status' => OrderGoodwillAdjustment::STATUS_REVERSED,
            'idempotency_key' => 'gw-cat-2',
        ]);

        $counts = OrderGoodwillAdjustment::query()
            ->selectRaw('reason_category, COUNT(*) AS total')
            ->groupBy('reason_category')
            ->pluck('total', 'reason_category');

        $this->assertSame(1, (int) $counts['service_recovery']);
        $this->assertSame(1, (int) $counts['business_courtesy']);

        $this->assertCount(
            1,
            OrderGoodwillAdjustment::inCategory(GoodwillReasonCategory::ServiceRecovery)->get()
        );
    }

    public function test_the_stored_category_is_derived_and_cannot_be_contradicted(): void
    {
        // A caller supplying a category that disagrees with the reason is
        // overruled, not trusted. Otherwise the reporting dimension could say
        // "Business Courtesy" over a service failure and nothing would notice.
        $adjustment = $this->adjustment([
            'reason_code' => GoodwillReason::ServiceFailure,
            'reason_category' => GoodwillReasonCategory::BusinessCourtesy,
        ]);

        $this->assertSame(GoodwillReasonCategory::ServiceRecovery, $adjustment->fresh()->reason_category);
    }

    // ── 4. OTHER requires a note ───────────────────────────────────────────

    public function test_other_is_the_only_reason_that_requires_a_note(): void
    {
        foreach (GoodwillReason::cases() as $reason) {
            $this->assertSame(
                $reason === GoodwillReason::Other,
                $reason->requiresNote(),
                "requiresNote() for {$reason->value}"
            );
        }
    }

    public function test_an_other_adjustment_without_a_note_is_refused(): void
    {
        $this->expectException(GoodwillException::class);
        $this->expectExceptionMessage('requires a written explanation');

        $this->adjustment(['reason_code' => GoodwillReason::Other, 'note' => null]);
    }

    public function test_an_other_adjustment_with_only_whitespace_is_refused(): void
    {
        $this->expectException(GoodwillException::class);

        $this->adjustment(['reason_code' => GoodwillReason::Other, 'note' => "   \n  "]);
    }

    public function test_an_other_adjustment_with_a_note_persists(): void
    {
        $adjustment = $this->adjustment([
            'reason_code' => GoodwillReason::Other,
            'note' => 'Long-standing account; regional manager approved verbally.',
        ]);

        $this->assertSame(GoodwillReasonCategory::Other, $adjustment->fresh()->reason_category);
    }

    public function test_a_categorised_reason_does_not_require_a_note(): void
    {
        $adjustment = $this->adjustment(['reason_code' => GoodwillReason::PriceMatch, 'note' => null]);

        $this->assertNull($adjustment->fresh()->note);
    }

    // ── 5. accepted_payment_total ──────────────────────────────────────────

    public function test_accepted_payment_total_persists_as_an_immutable_snapshot(): void
    {
        $adjustment = $this->adjustment(['accepted_payment_total' => 175.25]);

        $this->assertSame('175.25', (string) $adjustment->fresh()->accepted_payment_total);

        // It is a snapshot of the moment of approval — later payment activity
        // must never restate it, which is why it is stored rather than derived.
        $this->order->update(['grand_total' => 300.00]);

        $this->assertSame('175.25', (string) $adjustment->fresh()->accepted_payment_total);
    }

    // ── 6–7. rounding_residual ─────────────────────────────────────────────

    public function test_rounding_residual_persists_including_the_signed_boundary(): void
    {
        foreach (['0.00' => 0.00, '0.02' => 0.02, '-0.02' => -0.02, '0.01' => 0.01] as $expected => $value) {
            $adjustment = $this->adjustment([
                'rounding_residual' => $value,
                'product_discount_id' => $this->productDiscount(5.00)->id,
                'status' => OrderGoodwillAdjustment::STATUS_REVERSED, // keep one-active free
                'idempotency_key' => 'gw-res-'.$expected,
            ]);

            $this->assertSame($expected, (string) $adjustment->fresh()->rounding_residual);
        }
    }

    public function test_a_residual_over_two_cents_is_refused(): void
    {
        $this->expectException(GoodwillException::class);
        $this->expectExceptionMessage('exceeds the permitted');

        $this->adjustment(['rounding_residual' => 0.03]);
    }

    public function test_a_negative_residual_beyond_two_cents_is_refused(): void
    {
        $this->expectException(GoodwillException::class);

        $this->adjustment(['rounding_residual' => -0.05]);
    }

    public function test_the_database_refuses_an_out_of_bound_residual_even_without_the_model(): void
    {
        // The model guard gives a readable refusal; this proves the rule also
        // survives a raw insert that never loads the model at all.
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('order_goodwill_adjustments')->insert([
            'order_id' => $this->order->id,
            'product_discount_id' => $this->discount->id,
            'status' => 'applied',
            'active_order_id' => null,
            'reason_code' => 'SERVICE_FAILURE',
            'reason_category' => 'service_recovery',
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
            'accepted_payment_total' => 100.00,
            'rounding_residual' => 1.00,
            'before_snapshot' => json_encode([]),
            'after_snapshot' => json_encode([]),
            'idempotency_key' => 'gw-raw-residual',
            'applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── 8. ProductDiscount linkage ─────────────────────────────────────────

    public function test_the_amount_is_read_through_the_linked_product_discount(): void
    {
        $adjustment = $this->adjustment();

        $this->assertSame($this->discount->id, $adjustment->productDiscount->id);
        $this->assertSame(50.00, $adjustment->concessionAmount());
    }

    public function test_no_financial_figure_is_duplicated_onto_the_goodwill_record(): void
    {
        // The design rule made mechanical: any column here that also exists on
        // product_discounts or the allocation ledger is a figure that can
        // disagree with itself.
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('order_goodwill_adjustments');

        foreach ([
            'calculated_discount_amount', 'source_amount', 'percentage',
            'original_product_value', 'discounted_product_value',
            'taxable_value_before', 'taxable_value_after', 'tax_before', 'tax_after',
            'allocated_amount', 'pretax_discount_total', 'special_tax_amount',
            'added_fees_amount', 'grand_total', 'subtotal',
        ] as $forbidden) {
            $this->assertNotContains(
                $forbidden,
                $columns,
                "order_goodwill_adjustments must not carry '{$forbidden}' — the shared engine owns it."
            );
        }
    }

    public function test_at_most_one_active_adjustment_per_order(): void
    {
        $this->adjustment();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->adjustment([
            'product_discount_id' => $this->productDiscount(25.00)->id,
            'idempotency_key' => 'gw-second-active',
        ]);
    }

    public function test_the_unique_constraint_holds_against_a_direct_insert(): void
    {
        // Proven at the database, bypassing Eloquent entirely: the one-active
        // rule is a storage guarantee, not an application convention a path
        // that forgets the row lock could route around.
        $this->adjustment();

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('order_goodwill_adjustments')->insert([
            'order_id' => $this->order->id,
            'product_discount_id' => $this->productDiscount(25.00)->id,
            'status' => 'applied',
            'active_order_id' => $this->order->id, // the collision
            'reason_code' => 'LARGE_ORDER',
            'reason_category' => 'business_courtesy',
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
            'accepted_payment_total' => 100.00,
            'rounding_residual' => 0.00,
            'before_snapshot' => json_encode([]),
            'after_snapshot' => json_encode([]),
            'idempotency_key' => 'gw-raw-active',
            'applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_a_reversed_adjustment_frees_the_order_for_a_new_one(): void
    {
        $first = $this->adjustment();
        $first->update(['status' => OrderGoodwillAdjustment::STATUS_REVERSED, 'reversed_at' => now()]);

        $this->assertNull($first->fresh()->active_order_id, 'A reversed row must release the slot.');

        $second = $this->adjustment([
            'product_discount_id' => $this->productDiscount(25.00)->id,
            'idempotency_key' => 'gw-after-reversal',
        ]);

        // A NEW row, not the old one revived: the reversal stays in the history
        // beside the re-grant.
        $this->assertNotSame($first->id, $second->id);
        $this->assertTrue($first->fresh()->isReversed(), 'The original must remain reversed.');
        $this->assertSame(2, OrderGoodwillAdjustment::forOrder($this->order->id)->count());

        $this->assertSame($this->order->id, (int) $second->fresh()->active_order_id);
        $this->assertSame($second->id, OrderGoodwillAdjustment::activeFor($this->order->id)?->id);
    }

    public function test_a_reversed_adjustment_cannot_be_reinstated(): void
    {
        // Flipping status back would re-arm active_order_id and reinstate a
        // withdrawn concession under the original approver and timestamp, with
        // no record that it had ever been reversed.
        $adjustment = $this->adjustment();
        $adjustment->update(['status' => OrderGoodwillAdjustment::STATUS_REVERSED, 'reversed_at' => now()]);

        $this->expectException(GoodwillException::class);
        $this->expectExceptionMessage('cannot be reinstated');

        $adjustment->fresh()->update(['status' => OrderGoodwillAdjustment::STATUS_APPLIED]);
    }

    // ── 9. Direct Spatie permission behaviour ──────────────────────────────

    public function test_direct_spatie_check_refuses_where_the_gate_would_consent(): void
    {
        // THE POINT OF THE WHOLE AUTHORIZATION DESIGN.
        //
        // AppServiceProvider registers Gate::before(fn () => true), so can()
        // — and with it @can and Spatie's own permission: middleware, which
        // calls canAny() — consents for every signed-in user. hasPermissionTo()
        // does not consult the Gate, and is therefore the only check that can
        // still refuse.
        $this->seedGoodwillPermissions();
        Gate::before(fn () => true); // mirrors AppServiceProvider

        $unprivileged = $this->user('nobody@test.local');

        $this->assertTrue($unprivileged->can(GoodwillPermissions::APPLY), 'Baseline: the Gate consents.');
        $this->assertFalse(GoodwillPermissions::canApply($unprivileged), 'The direct check must refuse.');
        $this->assertFalse(GoodwillPermissions::canReverse($unprivileged));
        $this->assertFalse(GoodwillPermissions::isValidApprover($unprivileged));
    }

    public function test_a_user_holding_the_permission_is_allowed(): void
    {
        $this->seedGoodwillPermissions();

        $privileged = $this->user('boss@test.local');
        $privileged->givePermissionTo(GoodwillPermissions::APPLY);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(GoodwillPermissions::canApply($privileged));
        $this->assertTrue(GoodwillPermissions::isValidApprover($privileged));

        // Apply does not imply reverse — they are granted separately.
        $this->assertFalse(GoodwillPermissions::canReverse($privileged));
    }

    public function test_an_unregistered_permission_denies_rather_than_throwing(): void
    {
        // No seeder has run: Spatie raises PermissionDoesNotExist. An unhandled
        // throw would turn an unseeded deployment into a 500 on every check.
        $this->assertSame(0, Permission::where('name', GoodwillPermissions::APPLY)->count());

        $this->assertFalse(GoodwillPermissions::canApply($this->user('unseeded@test.local')));
        $this->assertFalse(GoodwillPermissions::canReverse(null));
    }

    public function test_a_guest_is_refused(): void
    {
        $this->assertFalse(GoodwillPermissions::canApply(null));
    }

    public function test_the_seeder_is_additive_and_repeatable(): void
    {
        // Created through the application's Role model — it generates the
        // `unique_id` the roles table requires and that Spatie's own model
        // knows nothing about.
        \App\Models\Iam\AccessControl\Role::create([
            'name' => 'Master Admin', 'guard_name' => 'web',
            'short_name' => 'master_admin', 'status' => 'Active',
        ]);

        $survivor = Permission::create([
            'name' => 'unrelated.permission', 'guard_name' => 'web',
            'title' => 'Unrelated', 'permission_to_all' => 'No',
        ]);

        $this->seedGoodwillPermissions();
        $this->seedGoodwillPermissions(); // twice — it must not duplicate

        $this->assertSame(1, Permission::where('name', GoodwillPermissions::APPLY)->count());
        $this->assertSame(1, Permission::where('name', GoodwillPermissions::REVERSE)->count());

        // Nothing outside its own scope is touched — the failure mode of
        // ModuleSeeder, which deletes any permission absent from its list.
        $this->assertNotNull($survivor->fresh(), 'The seeder must delete nothing.');

        // module_id must be set, or ModuleSeeder's final statement would delete
        // these permissions outright the next time it ran.
        foreach (array_keys(GoodwillPermissions::all()) as $name) {
            $this->assertNotNull(
                Permission::where('name', $name)->first()->module_id,
                "{$name} must be attached to a module."
            );
        }

        $this->assertTrue(
            Role::where('name', 'Master Admin')->first()->hasPermissionTo(GoodwillPermissions::APPLY)
        );
    }

    // ── 10. Immutable audit snapshots ──────────────────────────────────────

    public function test_before_and_after_snapshots_persist_as_structured_data(): void
    {
        $before = [
            'subtotal' => 200.00, 'pretax_discount_total' => 0.00, 'tax_amount' => 19.50,
            'special_tax_amount' => 4.00, 'added_fees_amount' => 5.00, 'grand_total' => 228.50,
            'total_paid' => 175.00, 'balance_due' => 53.50,
        ];
        $after = [
            'subtotal' => 200.00, 'pretax_discount_total' => 48.35, 'tax_amount' => 14.79,
            'special_tax_amount' => 3.03, 'added_fees_amount' => 5.00, 'grand_total' => 175.00,
            'total_paid' => 175.00, 'balance_due' => 0.00,
        ];

        $adjustment = $this->adjustment(['before_snapshot' => $before, 'after_snapshot' => $after])->fresh();

        // assertEquals, not assertSame: a JSON round-trip does not preserve
        // key order, and 200.00 comes back as int 200. Neither matters — the
        // snapshot is read by key, and every consumer casts. What matters is
        // that the values survive.
        $this->assertEquals($before, $adjustment->before_snapshot);
        $this->assertEquals($after, $adjustment->after_snapshot);

        // The snapshot records special tax, added fees and the payment
        // position — none of which product_discounts holds. That is why it is
        // not a duplicate of anything.
        $this->assertArrayHasKey('special_tax_amount', $adjustment->before_snapshot);
        $this->assertArrayHasKey('total_paid', $adjustment->before_snapshot);
    }

    public function test_audit_fields_cannot_be_edited_after_the_record_exists(): void
    {
        $adjustment = $this->adjustment();

        foreach ([
            'reason_code' => GoodwillReason::LargeOrder,
            'note' => 'rewritten after the fact',
            'accepted_payment_total' => 999.99,
            'rounding_residual' => 0.01,
            'approved_by' => $this->user('someone-else@test.local')->id,
            'before_snapshot' => ['tampered' => true],
            'after_snapshot' => ['tampered' => true],
            'idempotency_key' => 'rewritten-key',
            'order_id' => $this->order->id + 1,
            'product_discount_id' => $this->productDiscount(1.00)->id,
        ] as $field => $value) {
            try {
                $adjustment->fresh()->update([$field => $value]);
                $this->fail("Editing '{$field}' on an existing audit record must be refused.");
            } catch (GoodwillException $e) {
                $this->assertStringContainsString($field, $e->getMessage());
            }
        }

        // Untouched on disk despite every attempt.
        $stored = $adjustment->fresh();
        $this->assertSame(GoodwillReason::ServiceFailure, $stored->reason_code);
        $this->assertSame('150.00', (string) $stored->accepted_payment_total);
    }

    public function test_only_the_reversal_fields_remain_writable(): void
    {
        $adjustment = $this->adjustment();
        $reverser = $this->user('reverser@test.local');
        $reversalDiscount = $this->productDiscount(50.00, 'gw-reversal-discount');

        $adjustment->update([
            'status' => OrderGoodwillAdjustment::STATUS_REVERSED,
            'reversal_product_discount_id' => $reversalDiscount->id,
            'reversed_at' => now(),
            'reversed_by' => $reverser->id,
            'reversal_reason' => 'Concession withdrawn — customer disputed the resolution.',
        ]);

        $stored = $adjustment->fresh();

        $this->assertTrue($stored->isReversed());
        $this->assertNull($stored->active_order_id);
        $this->assertSame($reverser->id, $stored->reversedBy->id);
        $this->assertSame($reversalDiscount->id, $stored->reversalProductDiscount->id);

        // The original decision survives the reversal intact.
        $this->assertSame(GoodwillReason::ServiceFailure, $stored->reason_code);
        $this->assertSame($this->manager->id, $stored->approvedBy->id);
    }

    public function test_the_idempotency_key_is_unique(): void
    {
        $this->adjustment(['idempotency_key' => 'gw-dupe']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->adjustment([
            'idempotency_key' => 'gw-dupe',
            'product_discount_id' => $this->productDiscount(15.00)->id,
            'status' => OrderGoodwillAdjustment::STATUS_REVERSED,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function seedGoodwillPermissions(): void
    {
        (new GoodwillPermissionSeeder())->run();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $email): User
    {
        return User::create([
            'first_name' => 'GW', 'last_name' => 'User',
            'email' => $email, 'status' => 'Active',
        ]);
    }

    private function productDiscount(float $amount, ?string $key = null): ProductDiscount
    {
        static $n = 0;
        $n++;

        return ProductDiscount::create([
            'discount_type' => 'goodwill',
            'calculation_type' => 'fixed_amount',
            'source_amount' => $amount,
            'calculated_discount_amount' => $amount,
            'target_type' => 'order',
            'target_id' => $this->order->id,
            'customer_id' => $this->customer->id,
            'original_product_value' => 200.00,
            'discounted_product_value' => 200.00 - $amount,
            'taxable_value_before' => 200.00,
            'taxable_value_after' => 200.00 - $amount,
            'tax_before' => 19.50,
            'tax_after' => 19.50,
            'idempotency_key' => $key ?? 'gw-domain-discount-'.$n,
            'applied_at' => now(),
            'status' => ProductDiscount::STATUS_APPLIED,
        ]);
    }

    private function adjustment(array $overrides = []): OrderGoodwillAdjustment
    {
        return OrderGoodwillAdjustment::create(array_merge([
            'order_id' => $this->order->id,
            'product_discount_id' => $this->discount->id,
            'status' => OrderGoodwillAdjustment::STATUS_APPLIED,
            'reason_code' => GoodwillReason::ServiceFailure,
            'note' => null,
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
            'applied_by' => $this->manager->id,
            'accepted_payment_total' => 150.00,
            'rounding_residual' => 0.00,
            'before_snapshot' => ['grand_total' => 228.50],
            'after_snapshot' => ['grand_total' => 150.00],
            'idempotency_key' => 'gw-domain-'.uniqid('', true),
            'applied_at' => now(),
        ], $overrides));
    }
}
