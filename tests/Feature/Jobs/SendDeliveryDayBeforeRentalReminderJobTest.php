<?php

namespace Tests\Feature\Jobs;

use App\Enums\Communication\SmsType;
use App\Jobs\SendDeliveryDayBeforeRentalReminderJob;
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
 * {{delivery_time}} merge field (SMS_AUTOMATION_AUDIT.md — Weekend Special
 * pickup/delivery-time correction). Same fixture/harness pattern as the
 * other rental reminder job tests: TwilioService is constructed directly
 * inside the job, not container-resolved, so fake-but-well-formed encrypted
 * credentials are seeded to let construction succeed; the real Twilio API
 * call then fails authentication (caught internally) so no real SMS is
 * ever sent. Assertions read the `sms_logs` row TwilioService::logSmsAttempt()
 * writes unconditionally after each attempt.
 */
class SendDeliveryDayBeforeRentalReminderJobTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Reminder', 'last_name' => 'Test',
            'email' => 'delivery-day-before-job-test@example.com', 'status' => 'Active',
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
            'rental_delivery_day_before_store_message' => 'DELIVERY_DAY_BEFORE_STORE_MSG for {{customer_name}} at {{delivery_time}}',
            'rental_delivery_day_before_store_message_enabled' => '1',
            'rental_delivery_day_before_truck_message' => 'DELIVERY_DAY_BEFORE_TRUCK_MSG for {{customer_name}} at {{delivery_time}}',
            'rental_delivery_day_before_truck_message_enabled' => '1',
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
            'delivery_date'          => Carbon::now('America/Chicago')->addDay()->toDateString(),
            'delivery_status'        => 'Pending',
            'delivery_transport_mode' => 'Store',
            'delivery_time'          => $deliveryTime,
        ]);

        return [$order, $orderProduct];
    }

    private function runJob(): void
    {
        (new SendDeliveryDayBeforeRentalReminderJob())->handle();
    }

    public function test_sends_when_no_matching_sms_log_exists(): void
    {
        [$order] = $this->makeRecord('DDB-DEDUP-NOLOG');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(SmsType::DELIVERY_DAY_BEFORE, $log->sms_type);
        $this->assertStringContainsString('DELIVERY_DAY_BEFORE_STORE_MSG', $log->message);
    }

    public function test_does_not_resend_when_a_matching_sms_log_already_exists(): void
    {
        [$order] = $this->makeRecord('DDB-DEDUP-EXISTING');
        SMSLog::create([
            'order_id' => $order->id, 'sms_type' => SmsType::DELIVERY_DAY_BEFORE->value,
            'status' => 'sent', 'phone' => '+15555550100', 'message' => 'already sent',
        ]);

        $this->runJob();

        $this->assertSame(1, SMSLog::where('order_id', $order->id)->count(), 'no second SMS should have been sent');
    }

    public function test_existing_date_and_pending_status_conditions_still_apply(): void
    {
        [$order, $orderProduct] = $this->makeRecord('DDB-DEDUP-NOTPENDING');
        $orderProduct->update(['delivery_status' => 'Completed']);

        $this->runJob();

        $this->assertSame(0, SMSLog::where('order_id', $order->id)->count(), 'delivery_status must still gate eligibility regardless of the new merge field');
    }

    // ── {{delivery_time}} merge field ────────────────────────────────────

    public function test_weekend_special_order_receives_its_own_delivery_time(): void
    {
        [$order] = $this->makeRecord('DDB-DELTIME-WEEKEND', deliveryTime: '14:00:00');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('2:00 PM', $log->message);
        $this->assertStringNotContainsString('9:00 AM', $log->message);
    }

    public function test_standard_order_continues_receiving_its_normal_delivery_time(): void
    {
        [$order] = $this->makeRecord('DDB-DELTIME-STANDARD', deliveryTime: '09:00:00');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertStringContainsString('9:00 AM', $log->message);
    }

    public function test_does_not_fall_back_to_the_previous_hardcoded_0900_delivery_time_when_an_actual_schedule_exists(): void
    {
        [$order] = $this->makeRecord('DDB-DELTIME-NOFALLBACK', deliveryTime: '11:15:00');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertStringContainsString('11:15 AM', $log->message);
        $this->assertStringNotContainsString('9:00 AM', $log->message);
    }

    public function test_missing_delivery_time_is_handled_safely(): void
    {
        [$order] = $this->makeRecord('DDB-DELTIME-MISSING', deliveryTime: null);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log, 'the job must still send when delivery_time is unset');
        $this->assertStringNotContainsString('{{delivery_time}}', $log->message, 'the raw merge token must never leak into the sent message');
        $this->assertStringNotContainsString('9:00 AM', $log->message, 'a missing delivery_time must not fall back to the old hardcoded value');
    }
}
