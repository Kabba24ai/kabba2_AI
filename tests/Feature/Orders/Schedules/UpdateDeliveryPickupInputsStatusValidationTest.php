<?php

namespace Tests\Feature\Orders\Schedules;

use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P3-2 / SEC-1: `tnc_status`, `drivers_license_status`, `video_status`, and
 * `checklist_status` on UpdateDeliveryPickupInputsRequest previously accepted
 * any arbitrary string with no Rule::in()/enum constraint. This adds enum
 * validation for all four fields, reusing the existing OrderTermsStatus enum
 * for tnc_status and new sibling enums for the other three.
 * See docs/checklist-system-audit/P3_2_SEC1_STATUS_VALIDATION.md.
 */
class UpdateDeliveryPickupInputsStatusValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private OrderProduct $orderProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Sec1',
            'last_name'  => 'Tester',
            'email'      => 'sec1-tester@example.com',
            'password'   => bcrypt('password'),
        ]);

        $customer = Customer::create(['first_name' => 'Test', 'last_name' => 'Customer', 'email' => 'sec1-customer@example.com']);
        $order = Order::create(['order_date' => now()->format('Y-m-d'), 'customer_name' => 'Test Customer', 'customer_id' => $customer->id]);
        $product = Product::create(['product_name' => 'Test Product', 'slug' => 'sec1-product-' . uniqid(), 'product_type' => 'Rental']);

        // delivery_by intentionally left null: setting it makes the controller also
        // flip delivery_status to 'Completed', which fires OrderProduct's unrelated
        // static::updated funnel-stopping listener (FunnelLifecycleService) — that
        // listener reads $model->product_id from a model instance the controller loads
        // via a narrow ->select() that excludes product_id, so it crashes on a pre-existing,
        // out-of-scope bug unrelated to SEC-1's status-enum validation. Leaving delivery_by
        // null keeps the fixture on the "record inputs without confirming completion" path,
        // which is enough to exercise the validation rules under test in isolation.
        $this->orderProduct = OrderProduct::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
        ]);
    }

    private function apiUrl(string $path): string
    {
        return 'http://' . config('app.domains.api') . '/api/admin/v1/' . ltrim($path, '/');
    }

    private function callAs(string $method, string $path, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->employee, 'api_user')
            ->json($method, $this->apiUrl($path), $payload);
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'order_product_unique_id' => $this->orderProduct->unique_id,
            'type'                    => 'delivery',
        ], $overrides);
    }

    // ── Valid values — one per field, each accepted and persisted ──────────

    public function test_valid_tnc_status_is_accepted(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'tnc_status' => 'Accepted',
        ]));

        $response->assertOk()->assertJson(['status' => true]);
        $this->assertSame('Accepted', $this->orderProduct->fresh()->delivery_tnc_status);
    }

    public function test_valid_drivers_license_status_is_accepted(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'drivers_license_status' => 'Verified',
        ]));

        $response->assertOk()->assertJson(['status' => true]);
        $this->assertSame('Verified', $this->orderProduct->fresh()->delivery_drivers_license_status);
    }

    public function test_valid_video_status_is_accepted(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'video_status' => 'Completed',
        ]));

        $response->assertOk()->assertJson(['status' => true]);
        $this->assertSame('Completed', $this->orderProduct->fresh()->delivery_video_status);
    }

    public function test_valid_checklist_status_is_accepted(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'checklist_status' => 'Completed',
        ]));

        $response->assertOk()->assertJson(['status' => true]);
        $this->assertSame('Completed', $this->orderProduct->fresh()->delivery_checklist_status);
    }

    public function test_all_four_valid_statuses_together_still_succeed_and_preserve_response_envelope(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'tnc_status'             => 'Accepted',
            'drivers_license_status' => 'Verified',
            'video_status'           => 'Completed',
            'checklist_status'       => 'Completed',
        ]));

        // Response envelope unchanged: {status, message} — same shape as before this change.
        $response->assertOk()->assertExactJson([
            'status'  => true,
            'message' => 'Delivery successfully.',
        ]);
    }

    // ── Invalid arbitrary strings — each rejected with 422 ──────────────────

    public function test_invalid_tnc_status_is_rejected(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'tnc_status' => 'yes-please',
        ]));

        $response->assertStatus(422)->assertJson(['success' => false]);
        $response->assertJsonValidationErrors(['tnc_status']);
        $this->assertNull($this->orderProduct->fresh()->delivery_tnc_status);
    }

    public function test_invalid_drivers_license_status_is_rejected(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'drivers_license_status' => 'looks-fake',
        ]));

        $response->assertStatus(422)->assertJson(['success' => false]);
        $response->assertJsonValidationErrors(['drivers_license_status']);
    }

    public function test_invalid_video_status_is_rejected(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'video_status' => 'uploaded-maybe',
        ]));

        $response->assertStatus(422)->assertJson(['success' => false]);
        $response->assertJsonValidationErrors(['video_status']);
    }

    public function test_invalid_checklist_status_is_rejected(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload([
            'checklist_status' => 'kinda-done',
        ]));

        $response->assertStatus(422)->assertJson(['success' => false]);
        $response->assertJsonValidationErrors(['checklist_status']);
    }

    // ── Nulls still allowed (nullable) — unrelated fields not sent ──────────

    public function test_omitting_all_four_status_fields_still_succeeds(): void
    {
        $response = $this->callAs('POST', 'orders/schedules/update-delivery-pickup-inputs', $this->basePayload());

        $response->assertOk()->assertJson(['status' => true]);
    }
}
