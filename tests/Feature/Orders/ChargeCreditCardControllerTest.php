<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Refund release-blocker follow-up: ChargeCreditCardController's opaque-data
 * (new card) branch had its DB::rollback()/failure-return commented out, so
 * a declined gateway charge still fell through and created a settled Card
 * order_payments row with a null transaction_id — the same live-money defect
 * ReceivePaymentController and PaymentStoreController::handleExtensionPayment
 * were already hardened against ("Phase 3A fix"). A payment row with no
 * transaction_id is exactly what makes the "Full Amount Less Card Processing
 * Fee" refund option unable to recognize an otherwise-legitimate card
 * payment later.
 */
class ChargeCreditCardControllerTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Charge', 'last_name' => 'Test',
            'email' => 'charge-cc-test@example.com', 'status' => 'Active',
        ]);

        $this->order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Charge Test',
            'grand_total' => 100.0,
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-charge-cc-test@example.com', 'status' => 'Active',
        ]));
    }

    public function test_declined_opaque_data_charge_creates_no_payment_row(): void
    {
        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('validateOpaqueData')->once()->andReturn(true);
            $mock->shouldReceive('createOpaqueDataTransaction')->once()->andReturn([
                'status' => 'error', 'message' => 'This transaction has been declined.',
            ]);
        });

        $response = $this->put(route('admin.order-management.orders.charge-credit-card', $this->order->unique_id), [
            'firstName' => 'Charge', 'lastName' => 'Test',
            'opaqueDataValue' => 'opaque-value', 'opaqueDataDescriptor' => 'opaque-descriptor',
        ]);

        $response->assertRedirect()->assertSessionHas('error', 'This transaction has been declined.');

        $this->assertSame(0, $this->order->payments()->count());
        $this->assertSame(
            0,
            $this->order->payments()->where('status', OrderPaymentStatus::Paid)->whereNull('transaction_id')->count()
        );
    }
}
