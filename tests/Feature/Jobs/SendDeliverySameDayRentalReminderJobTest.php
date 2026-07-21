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

        Setting::updateOrCreate(
            ['setting_name' => 'twilio_timezone'],
            ['setting_type' => 'Communication Settings', 'setting_value' => 'America/Chicago']
        );
        $fakeEncrypted = Crypt::encryptString('FAKE_TEST_VALUE');
        foreach (['twilio_sid', 'twilio_auth_token', 'twilio_from_number', 'twilio_messaging_service_sid'] as $key) {
            Setting::create(['setting_name' => $key, 'setting_type' => 'Communication Settings', 'setting_value' => $fakeEncrypted]);
        }

        foreach ([
            'rental_delivery_same_day_store_message' => 'STANDARD_STORE_MSG for {{customer_name}} at {{delivery_time}}',
            'rental_delivery_same_day_store_message_enabled' => '1',
            'rental_delivery_same_day_truck_message' => 'STANDARD_TRUCK_MSG for {{customer_name}} at {{delivery_time}}',
            'rental_delivery_same_day_truck_message_enabled' => '1',
            'store_delivery_same_day_cod_order_message' => 'COD_STORE_MSG for {{customer_name}} at {{delivery_time}}',
            'store_delivery_same_day_cod_message_enabled' => '1',
            'truck_delivery_same_day_cod_order_message' => 'COD_TRUCK_MSG for {{customer_name}} at {{delivery_time}}',
            'truck_delivery_same_day_cod_message_enabled' => '1',
        ] as $name => $value) {
            Setting::create(['setting_name' => $name, 'setting_type' => 'Default Sales Funnel Settings', 'setting_value' => $value]);
        }
    }

    private function makeRecord(string $orderNumber, ?string $deliveryTime = '09:00:00'): array
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
            'delivery_time'          => $deliveryTime,
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

    // ── Dedup guard (SMS_AUTOMATION_AUDIT.md F-2 / RC-2) ─────────────────
    // Same pattern as SendDeliveryDayBeforeRentalReminderJob's existing
    // sms_logs whereNotExists guard. This job sends one of two sms_types
    // per order (standard vs. COD), so the guard must exclude an order
    // that already has EITHER type logged.

    public function test_sends_when_no_matching_sms_log_exists(): void
    {
        [$order] = $this->makeRecord('SDR-DEDUP-NOLOG');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $this->assertSame(1, SMSLog::where('order_id', $order->id)->count());
    }

    public function test_does_not_resend_when_a_matching_sms_log_already_exists(): void
    {
        [$order] = $this->makeRecord('SDR-DEDUP-EXISTING');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        SMSLog::create([
            'order_id' => $order->id, 'sms_type' => SmsType::DELIVERY_SAME_DAY->value,
            'status' => 'sent', 'phone' => '+15555550100', 'message' => 'already sent',
        ]);

        $this->runJob();

        $this->assertSame(1, SMSLog::where('order_id', $order->id)->count(), 'no second SMS should have been sent');
    }

    public function test_a_sms_log_for_a_different_sms_type_does_not_block_the_job(): void
    {
        [$order] = $this->makeRecord('SDR-DEDUP-OTHERTYPE');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        SMSLog::create([
            'order_id' => $order->id, 'sms_type' => SmsType::RETURN_SAME_DAY->value,
            'status' => 'sent', 'phone' => '+15555550100', 'message' => 'unrelated log',
        ]);

        $this->runJob();

        $this->assertSame(2, SMSLog::where('order_id', $order->id)->count(), 'the unrelated sms_type log must not block this job\'s own send');
        $this->assertTrue(
            SMSLog::where('order_id', $order->id)->where('sms_type', SmsType::DELIVERY_SAME_DAY->value)->exists()
        );
    }

    public function test_existing_date_and_pending_status_conditions_still_apply(): void
    {
        [$order, $orderProduct] = $this->makeRecord('SDR-DEDUP-NOTPENDING');
        $orderProduct->update(['delivery_status' => 'Completed']);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $this->assertSame(0, SMSLog::where('order_id', $order->id)->count(), 'delivery_status must still gate eligibility regardless of the new dedup guard');
    }

    // ── {{delivery_time}} merge field (SMS_AUTOMATION_AUDIT.md — Weekend Special pickup-time correction) ──

    public function test_weekend_special_order_receives_its_own_delivery_time_standard_bucket(): void
    {
        [$order] = $this->makeRecord('SDR-DELTIME-WEEKEND', deliveryTime: '14:00:00');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('2:00 PM', $log->message);
        $this->assertStringNotContainsString('9:00 AM', $log->message);
    }

    public function test_weekend_special_order_receives_its_own_delivery_time_cod_bucket(): void
    {
        [$order] = $this->makeRecord('SDR-DELTIME-WEEKEND-COD', deliveryTime: '14:00:00');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('2:00 PM', $log->message);
        $this->assertStringNotContainsString('9:00 AM', $log->message);
    }

    public function test_standard_order_continues_receiving_its_normal_delivery_time(): void
    {
        [$order] = $this->makeRecord('SDR-DELTIME-STANDARD', deliveryTime: '09:00:00');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertStringContainsString('9:00 AM', $log->message);
    }

    public function test_does_not_fall_back_to_the_previous_hardcoded_0900_delivery_time_when_an_actual_schedule_exists(): void
    {
        [$order] = $this->makeRecord('SDR-DELTIME-NOFALLBACK', deliveryTime: '11:15:00');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertStringContainsString('11:15 AM', $log->message);
        $this->assertStringNotContainsString('9:00 AM', $log->message);
    }

    public function test_missing_delivery_time_is_handled_safely(): void
    {
        [$order] = $this->makeRecord('SDR-DELTIME-MISSING', deliveryTime: null);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log, 'the job must still send when delivery_time is unset');
        $this->assertStringNotContainsString('{{delivery_time}}', $log->message, 'the raw merge token must never leak into the sent message');
        $this->assertStringNotContainsString('9:00 AM', $log->message, 'a missing delivery_time must not fall back to the old hardcoded value');
    }
}
