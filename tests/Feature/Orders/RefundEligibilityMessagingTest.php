<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Refund release-blocker follow-up: the "Full Amount Less Card Processing
 * Fee" disabled-state message previously collapsed four distinct root
 * causes (no card payment at all, a card payment missing its gateway
 * transaction id, an unconfigured fee percentage, and a fee capacity too
 * small/exhausted to retain) into one generic "no card payment was made"
 * message — misleading whenever a real card payment did exist. Each cause
 * must now report its own accurate reason (edit.blade.php's
 * $rfCcFeeIneligibleReason branches).
 */
class RefundEligibilityMessagingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Elig', 'last_name' => 'Test',
            'email' => 'elig-test@example.com', 'status' => 'Active',
        ]);

        $this->order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Elig Test',
            'subtotal' => 100.0,
            'grand_total' => 100.0,
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-elig-test@example.com', 'status' => 'Active',
        ]));

        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            Setting::create([
                'setting_type' => 'Payment Settings', 'value_type' => 'password',
                'setting_name' => $name, 'setting_title' => $name, 'setting_value' => 'test',
            ]);
        }
    }

    private function setCcFeePercentage(float $pct): void
    {
        Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => $pct]
        );
    }

    private function renderEditPage()
    {
        return $this->get(route('admin.order-management.orders.edit', $this->order->unique_id))->assertOk();
    }

    public function test_no_card_payment_shows_the_generic_no_card_message(): void
    {
        $this->setCcFeePercentage(3.00);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $this->order->grand_total,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->renderEditPage()
            ->assertSee('data-cc-fee-eligible="0"', false)
            ->assertSee('Only available when at least one original payment was made by Credit / Debit Card.');
    }

    public function test_card_payment_missing_transaction_id_shows_the_specific_message(): void
    {
        $this->setCcFeePercentage(3.00);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => $this->order->grand_total,
            'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => null,
        ]);

        $this->renderEditPage()
            ->assertSee('data-cc-fee-eligible="0"', false)
            ->assertSee('The original card payment has no recorded gateway transaction on file and cannot be refunded via card automatically.')
            ->assertDontSee('Only available when at least one original payment was made by Credit / Debit Card.');
    }

    public function test_unconfigured_fee_percentage_shows_the_configure_message_even_with_a_real_card_payment(): void
    {
        $this->setCcFeePercentage(0);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => $this->order->grand_total,
            'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => 'TXN-REAL-1',
        ]);

        $this->renderEditPage()
            ->assertSee('data-cc-fee-eligible="0"', false)
            ->assertSee('Configure a Credit Card Processing Fee in System Settings to enable this option.')
            ->assertDontSee('Only available when at least one original payment was made by Credit / Debit Card.');
    }

    public function test_fee_capacity_rounding_to_zero_on_a_tiny_payment_shows_the_capacity_message(): void
    {
        // A tiny original amount (e.g. manual QA test data) can make
        // round(amount * pct / 100, 2) == 0.00 even with a fully configured
        // fee percentage and a real transaction id — this is not "no card
        // payment," it's "nothing to retain."
        $this->setCcFeePercentage(3.00);
        $this->order->update(['subtotal' => 0.08, 'grand_total' => 0.08]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 0.08,
            'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => 'TXN-TINY-1',
        ]);

        $this->renderEditPage()
            ->assertSee('data-cc-fee-eligible="0"', false)
            ->assertSee('No card processing fee capacity remains to retain for this order\'s original card payment(s).')
            ->assertDontSee('Only available when at least one original payment was made by Credit / Debit Card.');
    }

    public function test_fully_eligible_card_payment_enables_the_option(): void
    {
        $this->setCcFeePercentage(3.00);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => $this->order->grand_total,
            'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => 'TXN-REAL-2',
        ]);

        $this->renderEditPage()->assertSee('data-cc-fee-eligible="1"', false);
    }
}
