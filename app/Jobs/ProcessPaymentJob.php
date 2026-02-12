<?php

namespace App\Jobs;

use App\Models\Orders\Order;
use App\Models\Customers\Customer;
use App\Services\AuthorizeNetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPaymentJob implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $customer;
    protected $amount;
    protected $paymentMethod;
    protected $customerProfileId;
    protected $paymentProfileId;
    protected $opaqueDataValue;
    protected $opaqueDataDescriptor;
    protected $cardData;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Order $order,
        Customer $customer,
        float $amount,
        string $paymentMethod = 'card',
        ?string $customerProfileId = null,
        ?string $paymentProfileId = null,
        ?string $opaqueDataValue = null,
        ?string $opaqueDataDescriptor = null,
        ?array $cardData = null
    ) {
        $this->order = $order;
        $this->customer = $customer;
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
        $this->customerProfileId = $customerProfileId;
        $this->paymentProfileId = $paymentProfileId;
        $this->opaqueDataValue = $opaqueDataValue;
        $this->opaqueDataDescriptor = $opaqueDataDescriptor;
        $this->cardData = $cardData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $authorizeNetService = new AuthorizeNetService();
            $paymentResult = null;

            // Process saved card payment
            if ($this->customerProfileId && $this->paymentProfileId) {
                $paymentResult = $authorizeNetService->chargeCustomerProfile(
                    $this->customerProfileId,
                    $this->paymentProfileId,
                    $this->amount,
                    ['order_number' => $this->order->order_number]
                );
            }
            // Process new card with opaque data
            elseif ($this->opaqueDataValue && $this->opaqueDataDescriptor) {
                $paymentResult = $authorizeNetService->createOpaqueDataTransaction(
                    $this->opaqueDataValue,
                    $this->amount,
                    [
                        'order_number' => $this->order->order_number,
                        'customer' => $this->customer->toArray(),
                    ]
                );
            }

            if (!$paymentResult) {
                throw new \Exception('Payment processing failed: no result returned');
            }

            // Create payment record
            $payment = $this->order->payments()->create([
                'payment_datetime' => now(),
                'payment_method' => 'Card',
                'amount' => $this->amount,
                'transaction_id' => $paymentResult['transaction_id'] ?? null,
                'auth_code' => $paymentResult['auth_code'] ?? null,
                'customer_profile_id' => $paymentResult['customer_profile_id'] ?? null,
                'payment_profile_id' => $paymentResult['payment_profile_id'] ?? null,
                'card_number' => $paymentResult['card_number'] ?? null,
                'card_first_name' => $this->cardData['first_name'] ?? null,
                'card_last_name' => $this->cardData['last_name'] ?? null,
                'status' => $paymentResult['payment_status'] ?? 'Pending',
                'created_by_id' => $this->customer->id,
                'created_by_type' => Customer::class,
            ]);

            // Update customer profile if created
            if (empty($this->customer->authorize_profile_id) && !empty($paymentResult['customer_profile_id'])) {
                $this->customer->authorize_profile_id = $paymentResult['customer_profile_id'];
                $this->customer->saveQuietly();
            }

            // Save card to customer
            if (!empty($paymentResult['payment_profile_id'])) {
                $this->customer->cards()->updateOrCreate(
                    ['payment_profile_id' => $paymentResult['payment_profile_id']],
                    [
                        'first_name' => $this->cardData['first_name'] ?? null,
                        'last_name' => $this->cardData['last_name'] ?? null,
                        'card_number' => $paymentResult['card_number'] ?? null,
                        'card_type' => $paymentResult['card_type'] ?? null,
                    ],
                );
            }

            // Dispatch receipt creation if payment was successful
            if ($paymentResult['status'] == 'success') {
                CreateReceiptJob::dispatch($this->order, 'card');
            }

            Log::info('Payment processed successfully for Order', [
                'order_id' => $this->order->id,
                'order_unique_id' => $this->order->unique_id,
                'transaction_id' => $paymentResult['transaction_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Payment processing FAILED for order ' . $this->order->id, [
                'error' => $e->getMessage(),
                'order_unique_id' => $this->order->unique_id,
            ]);
        }
    }
}
