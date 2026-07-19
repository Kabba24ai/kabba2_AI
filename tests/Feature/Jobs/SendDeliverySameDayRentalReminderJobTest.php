<?php

namespace Tests\Feature\Jobs;

use App\Enums\Communication\SmsType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Jobs\SendDeliverySameDayRentalReminderJob;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Global\SMSLog;
use App\Models\Orders\Order;
use App\Models\Orders\OrderAddress;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Payment Architecture Finalization (Phase 4B) — the job previously decided
 * COD-vs-paid routing from Order::lastPayment (the single highest-id
 * order_payments row), which could hide a genuinely still-outstanding
 * COD/Pending placeholder behind a later, unrelated row. It also compared
 * a hydrated enum instance to a raw string ('COD'/'Pending'), which was
 * always false — COD detection never actually fired at all. Now sourced
 * from OrderPaymentSummary::unresolvedPaymentAttempts (Tier 2's
 * precedence-safe field): present only while the order is genuinely not
 * yet fully paid, regardless of row insertion order.
 *
 * TwilioService is constructed directly inside the job (`new
 * TwilioService()`), not resolved from the container, so it can't be
 * container-mocked — same constraint already documented in
 * FunnelLifecycleSchedulerSafetyTest.php. Fake, well-formed-but-invalid
 * encrypted credentials are seeded so construction succeeds; the real
 * Twilio API call then fails authentication (caught internally, never
 * throws) so no real SMS is ever sent. Every assertion here reads the
 * `sms_logs` row TwilioService::logSmsAttempt() writes unconditionally
 * after each attempt — order_id/sms_type/message are populated
 * regardless of whether the live Twilio call itself succeeded or failed,
 * so these tests prove routing/template selection without depending on
 * network reachability.
 */
class SendDeliverySameDayRentalReminderJobTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Reminder', 'last_name' => 'Test',
            'email' => 'reminder-job-test@example.com', 'status' => 'Active',
        ]);

        $this->product = Product::create([
            'product_name' => 'Reminder Test Product',
            'slug'         => 'reminder-test-product-' . uniqid(),
            'product_type' => 'Rental',
            'is_default_funnel' => true,
        ]);

        Setting::create(['setting_name' => 'twilio_timezone', 'setting_type' => 'Communication Settings', 'setting_value' => 'America/Chicago']);
        $fakeEncrypted = Crypt::encryptString('FAKE_TEST_VALUE');
        foreach (['twilio_sid', 'twilio_auth_token', 'twilio_from_number', 'twilio_messaging_service_sid'] as $key) {
            Setting::create(['setting_name' => $key, 'setting_type' => 'Communication Settings', 'setting_value' => $fakeEncrypted]);
        }

        foreach ([
            'rental_delivery_same_day_store_message' => 'STANDARD_STORE_MSG for {{customer_name}}',
            'rental_delivery_same_day_store_message_enabled' => '1',
            'rental_delivery_same_day_truck_message' => 'STANDARD_TRUCK_MSG for {{customer_name}}',
            'rental_delivery_same_day_truck_message_enabled' => '1',
            'store_delivery_same_day_cod_order_message' => 'COD_STORE_MSG for {{customer_name}}',
            'store_delivery_same_day_cod_message_enabled' => '1',
            'truck_delivery_same_day_cod_order_message' => 'COD_TRUCK_MSG for {{customer_name}}',
            'truck_delivery_same_day_cod_message_enabled' => '1',
        ] as $name => $value) {
            Setting::create(['setting_name' => $name, 'setting_type' => 'Default Sales Funnel Settings', 'setting_value' => $value]);
        }
    }

    private function makeRecord(string $orderNumber): array
    {
        $order = Order::create([
            'order_number'  => $orderNumber,
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Reminder Test',
            'subtotal'      => 500, 'tax_amount' => 0, 'grand_total' => 500,
        ]);

        OrderAddress::create([
            'order_id' => $order->id, 'type' => 'Shipping',
            'first_name' => 'Reminder', 'address' => '1 Test St', 'city' => 'Testville',
            'phone' => '+15555550100',
        ]);

        $orderProduct = OrderProduct::create([
            'order_id'               => $order->id,
            'product_id'             => $this->product->id,
            'product_name'           => 'Reminder Test Product',
            'price'                  => 500, 'quantity' => 1,
            'sub_total'              => 500, 'tax' => 0, 'total' => 500,
            'product_data'           => ['product_type' => 'Rental'],
            'delivery_date'          => Carbon::now('America/Chicago')->toDateString(),
            'delivery_status'        => 'Pending',
            'delivery_transport_mode' => 'Store',
        ]);

        return [$order, $orderProduct];
    }

    private function runJob(): void
    {
        (new SendDeliverySameDayRentalReminderJob())->handle();
    }

    // ── 1. Unpaid COD order ──────────────────────────────────────────────

    public function test_unpaid_cod_order_receives_the_cod_reminder(): void
    {
        [$order] = $this->makeRecord('SDR-COD');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(SmsType::DELIVERY_SAME_DAY_COD, $log->sms_type);
        $this->assertStringContainsString('COD_STORE_MSG', $log->message);
    }

    // ── 2. Fully paid order ──────────────────────────────────────────────

    public function test_fully_paid_order_receives_the_standard_reminder(): void
    {
        [$order] = $this->makeRecord('SDR-PAID');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(SmsType::DELIVERY_SAME_DAY, $log->sms_type);
        $this->assertStringContainsString('STANDARD_STORE_MSG', $log->message);
    }

    // ── 3. Split-payment fully paid order ────────────────────────────────

    public function test_split_payment_fully_paid_order_receives_the_standard_reminder(): void
    {
        [$order] = $this->makeRecord('SDR-SPLIT');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 200, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 300, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertSame(SmsType::DELIVERY_SAME_DAY, $log->sms_type);
    }

    // ── 4. Failed attempt followed by successful payment ─────────────────

    public function test_failed_attempt_followed_by_successful_payment_receives_the_standard_reminder(): void
    {
        [$order] = $this->makeRecord('SDR-FAILTHENPAID');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertSame(SmsType::DELIVERY_SAME_DAY, $log->sms_type, 'a resolved earlier failure must not suppress the standard reminder or misroute to COD');
    }

    // ── 5. Pending placeholder followed by completed payment ─────────────

    public function test_cod_placeholder_superseded_by_full_payment_receives_the_standard_reminder(): void
    {
        // The exact bug class this fix targets: the COD/Pending row is
        // NOT the highest-id row (a later, unrelated-looking Paid row for
        // the full amount supersedes it) — a lastPayment-only check would
        // have gotten this right by accident (lastPayment = the Paid row),
        // but a naive any-row COD+Pending check (matching the OTHER
        // reminder jobs' simpler pattern) would get it WRONG, since the
        // COD row still literally exists. unresolvedPaymentAttempts is the
        // only approach that is correct here AND safe against row-order
        // tricks in the other direction (test 6 below).
        [$order] = $this->makeRecord('SDR-CODSUPERSEDED');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 500, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertSame(SmsType::DELIVERY_SAME_DAY, $log->sms_type, 'a historical COD/Pending row must not override a completed payment');
    }

    // ── 6. Genuinely outstanding COD balance hidden behind an unrelated later row ─

    public function test_cod_balance_not_hidden_by_a_later_unrelated_partial_row(): void
    {
        // The OLD lastPayment-only bug, in its actual failure direction:
        // the COD/Pending row (the real outstanding balance) is created
        // FIRST; a later, insufficient partial payment for something else
        // is recorded afterward. lastPayment would resolve to the second
        // row (not COD/Pending) and wrongly exclude this order from the
        // COD reminder even though $200 of the $500 total is still
        // genuinely due at delivery.
        [$order] = $this->makeRecord('SDR-CODHIDDEN');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 500, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 300, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertSame(SmsType::DELIVERY_SAME_DAY_COD, $log->sms_type, 'order is only partially paid — the COD balance is still genuinely outstanding');
    }

    // ── 7. Unresolved COD balance with a partial payment (balance due) ──

    public function test_partial_payment_with_balance_due_receives_the_cod_reminder(): void
    {
        [$order] = $this->makeRecord('SDR-PARTIALDUE');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 100, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertSame(SmsType::DELIVERY_SAME_DAY_COD, $log->sms_type);
    }
}
