<?php

namespace Tests\Feature\Jobs;

use App\Enums\Communication\SmsType;
use App\Jobs\SendReturnSameDayRentalReminderJob;
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
 * SMS_AUTOMATION_AUDIT.md F-2 / RC-2 — this job had no sms_logs dedup guard
 * at all. Same fixture/harness pattern as SendDeliverySameDayRentalReminderJobTest
 * / SendReturnDayBeforeRentalReminderJobTest.
 */
class SendReturnSameDayRentalReminderJobTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Reminder', 'last_name' => 'Test',
            'email' => 'return-same-day-job-test@example.com', 'status' => 'Active',
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
            'rental_return_same_day_store_message' => 'RETURN_SAME_DAY_STORE_MSG for {{customer_name}} by {{pickup_time}}',
            'rental_return_same_day_store_message_enabled' => '1',
            'rental_return_same_day_truck_message' => 'RETURN_SAME_DAY_TRUCK_MSG for {{customer_name}} by {{pickup_time}}',
            'rental_return_same_day_truck_message_enabled' => '1',
        ] as $name => $value) {
            Setting::create(['setting_name' => $name, 'setting_type' => 'Default Sales Funnel Settings', 'setting_value' => $value]);
        }
    }

    private function makeRecord(string $orderNumber, string $pickupStatus = 'Pending', ?string $pickupTime = '09:00:00'): array
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
            'pickup_date'            => Carbon::now('America/Chicago')->toDateString(),
            'pickup_status'          => $pickupStatus,
            'pickup_transport_mode'  => 'Store',
            'pickup_time'            => $pickupTime,
        ]);

        return [$order, $orderProduct];
    }

    private function runJob(): void
    {
        (new SendReturnSameDayRentalReminderJob())->handle();
    }

    public function test_sends_when_no_matching_sms_log_exists(): void
    {
        [$order] = $this->makeRecord('RSD-DEDUP-NOLOG');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(SmsType::RETURN_SAME_DAY, $log->sms_type);
        $this->assertStringContainsString('RETURN_SAME_DAY_STORE_MSG', $log->message);
    }

    public function test_does_not_resend_when_a_matching_sms_log_already_exists(): void
    {
        [$order] = $this->makeRecord('RSD-DEDUP-EXISTING');
        SMSLog::create([
            'order_id' => $order->id, 'sms_type' => SmsType::RETURN_SAME_DAY->value,
            'status' => 'sent', 'phone' => '+15555550100', 'message' => 'already sent',
        ]);

        $this->runJob();

        $this->assertSame(1, SMSLog::where('order_id', $order->id)->count(), 'no second SMS should have been sent');
    }

    public function test_a_sms_log_for_a_different_sms_type_does_not_block_the_job(): void
    {
        [$order] = $this->makeRecord('RSD-DEDUP-OTHERTYPE');
        SMSLog::create([
            'order_id' => $order->id, 'sms_type' => SmsType::RETURN_DAY_BEFORE->value,
            'status' => 'sent', 'phone' => '+15555550100', 'message' => 'unrelated log',
        ]);

        $this->runJob();

        $this->assertSame(2, SMSLog::where('order_id', $order->id)->count(), 'the unrelated sms_type log must not block this job\'s own send');
        $this->assertTrue(
            SMSLog::where('order_id', $order->id)->where('sms_type', SmsType::RETURN_SAME_DAY->value)->exists()
        );
    }

    public function test_existing_date_and_pending_status_conditions_still_apply(): void
    {
        [$order] = $this->makeRecord('RSD-DEDUP-NOTPENDING', pickupStatus: 'Completed');

        $this->runJob();

        $this->assertSame(0, SMSLog::where('order_id', $order->id)->count(), 'pickup_status must still gate eligibility regardless of the new dedup guard');
    }

    public function test_pickup_date_not_today_is_still_excluded(): void
    {
        [$order, $orderProduct] = $this->makeRecord('RSD-DEDUP-WRONGDATE');
        $orderProduct->update(['pickup_date' => Carbon::now('America/Chicago')->addDay()->toDateString()]);

        $this->runJob();

        $this->assertSame(0, SMSLog::where('order_id', $order->id)->count(), 'pickup_date must still gate eligibility regardless of the new dedup guard');
    }

    // ── {{pickup_time}} merge field (SMS_AUTOMATION_AUDIT.md — Weekend Special pickup-time correction) ──

    public function test_weekend_special_order_receives_its_own_pickup_time(): void
    {
        [$order] = $this->makeRecord('RSD-WEEKEND', pickupTime: '14:00:00');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('2:00 PM', $log->message);
        $this->assertStringNotContainsString('9:00 AM', $log->message);
    }

    public function test_standard_order_continues_receiving_its_normal_pickup_time(): void
    {
        [$order] = $this->makeRecord('RSD-STANDARD', pickupTime: '09:00:00');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('9:00 AM', $log->message);
    }

    public function test_pickup_time_is_formatted_in_12_hour_form_with_am_pm(): void
    {
        [$order] = $this->makeRecord('RSD-FORMAT', pickupTime: '16:30:00');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertStringContainsString('4:30 PM', $log->message);
    }

    public function test_does_not_fall_back_to_the_previous_hardcoded_0900_value_when_an_actual_schedule_exists(): void
    {
        [$order] = $this->makeRecord('RSD-NOFALLBACK', pickupTime: '11:15:00');

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertStringContainsString('11:15 AM', $log->message);
        $this->assertStringNotContainsString('9:00 AM', $log->message);
    }

    public function test_missing_pickup_time_is_handled_safely(): void
    {
        [$order] = $this->makeRecord('RSD-NOPICKUPTIME', pickupTime: null);

        $this->runJob();

        $log = SMSLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log, 'the job must still send when pickup_time is unset');
        $this->assertStringNotContainsString('{{pickup_time}}', $log->message, 'the raw merge token must never leak into the sent message');
        $this->assertStringNotContainsString('9:00 AM', $log->message, 'a missing pickup_time must not fall back to the old hardcoded value');
    }
}
