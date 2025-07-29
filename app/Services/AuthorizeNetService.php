<?php

namespace App\Services;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizeNetService
{
    protected $merchantAuthentication;
    protected bool $supportRefundOnline = true;
    protected $currency;
    protected bool $isTestMode;

    public function __construct()
    {
        $paymentSettings = \App\Helpers\ConfigurationHelper::getSettings('Payment Settings');
        $loginId = $paymentSettings['payment_api_key'] ?? '';
        $transactionKey = $paymentSettings['payment_api_secret'] ?? '';
        $isTestMode = $paymentSettings['payment_test_mode'] ?? false;
        if (empty($loginId) || empty($transactionKey)) {
            throw new \Exception('Payment API credentials are not set.');
        }

        $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuthentication->setName($loginId);
        $this->merchantAuthentication->setTransactionKey($transactionKey);
        $this->isTestMode = $isTestMode === true || $isTestMode === 'true' || $isTestMode === 1 || $isTestMode === '1';
    }

    public function findOrCreateCustomerProfileAndPaymentProfile(string $uniqueId, array $customer, string $opaqueDataValue)
    {
        // 1. Try to find customer profile by merchantCustomerId (your uniqueId)
        $profileId = $this->findExistingCustomerProfileId($uniqueId);

        // 2. If profile does not exist, create it (with payment profile)
        if (!$profileId) {
            // Set up profile
            $profile = new AnetAPI\CustomerProfileType();
            $profile->setMerchantCustomerId($uniqueId);
            $profile->setEmail($customer['email'] ?? $uniqueId);
            $profile->setDescription($customer['description'] ?? '');

            // Set up billing address (optional, for profile)
            $customerAddress = new AnetAPI\CustomerAddressType();
            if (!empty($customer['first_name'])) {
                $customerAddress->setFirstName($customer['first_name']);
            }
            if (!empty($customer['last_name'])) {
                $customerAddress->setLastName($customer['last_name']);
            }
            if (!empty($customer['email'])) {
                $customerAddress->setEmail($customer['email']);
            }
            if (!empty($customer['company'])) {
                $customerAddress->setCompany($customer['company']);
            }

            // Set up payment profile with Accept.js opaqueData
            $opaqueData = new AnetAPI\OpaqueDataType();
            $opaqueData->setDataDescriptor('COMMON.ACCEPT.INAPP.PAYMENT');
            $opaqueData->setDataValue($opaqueDataValue);

            $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
            $paymentProfile->setCustomerType('individual');
            $paymentProfile->setPayment((new AnetAPI\PaymentType())->setOpaqueData($opaqueData));
            // Optionally set billing address on payment profile:
            $paymentProfile->setBillTo($customerAddress);

            $profile->setPaymentProfiles([$paymentProfile]); // Add payment profile

            $createRequest = new AnetAPI\CreateCustomerProfileRequest();
            $createRequest->setMerchantAuthentication($this->merchantAuthentication);
            $createRequest->setProfile($profile);
            $createRequest->setValidationMode($this->isTestMode ? 'none' : 'liveMode');

            $controller = new AnetController\CreateCustomerProfileController($createRequest);
            $createResponse = $controller->executeWithApiResponse($this->getApiEnvironment());

            if ($createResponse && $createResponse->getMessages()->getResultCode() === 'Ok') {
                $profileId = $createResponse->getCustomerProfileId();
                $paymentProfileId = $createResponse->getCustomerPaymentProfileIdList()[0] ?? null;
                return [
                    'customer_profile_id' => $profileId,
                    'payment_profile_id' => $paymentProfileId,
                ];
            } else {
                if ($createResponse && $createResponse->getMessages() && $createResponse->getMessages()->getMessage()) {
                    $msgObj = $createResponse->getMessages()->getMessage()[0];
                    $errorCode = method_exists($msgObj, 'getCode') ? $msgObj->getCode() : '';
                    $errorText = method_exists($msgObj, 'getText') ? $msgObj->getText() : '';
                    $error = "[$errorCode] $errorText";
                } else {
                    $error = 'Unknown error';
                }
                throw new \Exception('Failed to create customer profile: ' . $error);
            }
        } else {
            // Profile exists, but check if payment profile exists
            // You might want to check for existing payment profiles or create a new one
            // For now, let's just return the customerProfileId
            return [
                'customer_profile_id' => $profileId,
                'payment_profile_id' => null, // Or fetch the list if you want
            ];
        }
    }

    /**
     * Try to find an existing customer profile by merchantCustomerId.
     */
    public function findExistingCustomerProfileId(string $merchantCustomerId): ?string
    {
        $searchRequest = new AnetAPI\GetCustomerProfileIdsRequest();
        $searchRequest->setMerchantAuthentication($this->merchantAuthentication);
        $searchController = new AnetController\GetCustomerProfileIdsController($searchRequest);
        $searchResponse = $searchController->executeWithApiResponse($this->getApiEnvironment());
        if ($searchResponse && $searchResponse->getMessages()->getResultCode() === 'Ok') {
            $profileIds = $searchResponse->getIds();
            foreach ($profileIds as $profileId) {
                $getRequest = new AnetAPI\GetCustomerProfileRequest();
                $getRequest->setMerchantAuthentication($this->merchantAuthentication);
                $getRequest->setCustomerProfileId($profileId);
                $getController = new AnetController\GetCustomerProfileController($getRequest);
                $getResponse = $getController->executeWithApiResponse($this->getApiEnvironment());
                if ($getResponse && $getResponse->getMessages()->getResultCode() === 'Ok') {
                    $profile = $getResponse->getProfile();
                    if ($profile && $profile->getMerchantCustomerId() === $merchantCustomerId) {
                        return $profileId;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Create a payment transaction using Accept.js opaque data, creating/finding both
     * customer and payment profiles if customer data is present.
     *
     * @param string $opaqueDataValue
     * @param float $amount
     * @param array $options ['customer' => [...], 'order_number' => ...]
     * @return array
     */
    public function createOpaqueDataTransaction(string $opaqueDataValue, float $amount, array $options = []): array
    {
        $opaqueData = new AnetAPI\OpaqueDataType();
        $opaqueData->setDataDescriptor('COMMON.ACCEPT.INAPP.PAYMENT');
        $opaqueData->setDataValue($opaqueDataValue);

        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType('authCaptureTransaction');
        $transactionRequest->setAmount($amount);

        $customerProfileId = null;
        $paymentProfileId = null;

        // ---- 1. Use Customer & Payment Profile if Customer Info is Provided ----
        if (!empty($options['customer']['unique_id']) && !empty($options['customer'])) {
            // Try to find or create both customer & payment profile using Accept.js
            $profileResult = $this->findOrCreateCustomerProfileAndPaymentProfile(
                $options['customer']['unique_id'],
                $options['customer'],
                $opaqueDataValue, // Accept.js data for card
            );
            $customerProfileId = $profileResult['customer_profile_id'];
            $paymentProfileId = $profileResult['payment_profile_id'];

            if ($customerProfileId && $paymentProfileId) {
                // Use the profile and payment profile for the transaction
                $profilePayment = new AnetAPI\CustomerProfilePaymentType();
                $profilePayment->setCustomerProfileId($customerProfileId);

                // Fix: Set PaymentProfileType object, not just ID
                $paymentProfileObj = new AnetAPI\PaymentProfileType();
                $paymentProfileObj->setPaymentProfileId($paymentProfileId);
                $profilePayment->setPaymentProfile($paymentProfileObj);

                $transactionRequest->setProfile($profilePayment);
            } else {
                // fallback to opaque data if profile/payment profile could not be created
                $paymentType = new AnetAPI\PaymentType();
                $paymentType->setOpaqueData($opaqueData);
                $transactionRequest->setPayment($paymentType);
            }
        } else {
            // ---- 2. Fallback: Guest Transaction with OpaqueData (no profile) ----
            $paymentType = new AnetAPI\PaymentType();
            $paymentType->setOpaqueData($opaqueData);
            $transactionRequest->setPayment($paymentType);
        }

        // ---- 3. Optional: Set Order/Invoice Number ----
        if (!empty($options['order_number'])) {
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($options['order_number']);
            $transactionRequest->setOrder($order);
        }

        // ---- 4. Create Transaction Request ----
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId('ref' . time());
        $request->setTransactionRequest($transactionRequest);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse($this->getApiEnvironment());
        logger()->info('AuthorizeNet response: ' . json_encode($response));
        // ---- 5. Handle Response ----
        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok') {
            $transactionResponse = $response->getTransactionResponse();
            // Return known profile/payment ids or extract from response
            return [
                'status' => 'success',
                'payment_status' => 'Paid',
                'message' => 'Payment successful',
                'transaction_id' => $transactionResponse && method_exists($transactionResponse, 'getTransId') ? $transactionResponse->getTransId() : null,
                'auth_code' => $transactionResponse && method_exists($transactionResponse, 'getAuthCode') ? $transactionResponse->getAuthCode() : null,
                'customer_profile_id' => $customerProfileId,
                'payment_profile_id' => $paymentProfileId,
                'card_number' => $transactionResponse && method_exists($transactionResponse, 'getAccountNumber') ? $transactionResponse->getAccountNumber() : null,
            ];
        }

        // ---- 6. Handle Error ----
        $errorMessage = 'Payment failed';
        if ($response && $response->getMessages() && isset($response->getMessages()->getMessage()[0])) {
            $errorMessage .= ': ' . $response->getMessages()->getMessage()[0]->getText();
        }
        return [
            'status' => 'failure',
            'payment_status' => 'Failed',
            'message' => $errorMessage,
            'customer_profile_id' => $customerProfileId,
            'payment_profile_id' => $paymentProfileId,
        ];
    }

    /**
     * Validate Accept.js opaque data response.
     *
     * @param array $opaqueData
     * @return bool
     */
    public function validateOpaqueData(array $opaqueData): bool
    {
        return isset($opaqueData['dataDescriptor'], $opaqueData['dataValue']) && $opaqueData['dataDescriptor'] === 'COMMON.ACCEPT.INAPP.PAYMENT' && !empty($opaqueData['dataValue']);
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    public function setCurrency($currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Refund the order based on payment ID and amount.
     *
     * @param string $paymentId
     * @param float $totalAmount
     * @param array $options
     * @return array
     */
    public function refundOrder(string $paymentId, float $totalAmount, array $options = []): array
    {
        $transactionDetails = $this->getTransactionDetails($paymentId);

        if ($transactionDetails === null) {
            return ['status' => 'failure', 'message' => 'Transaction not found'];
        }

        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($transactionDetails->cardNumber);
        $creditCard->setExpirationDate($transactionDetails->expirationDate);

        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType('refundTransaction');
        $transactionRequest->setAmount($totalAmount);
        $transactionRequest->setPayment($paymentOne);
        $transactionRequest->setRefTransId($paymentId);

        if (!empty($options['order_number'])) {
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($options['order_number']);
            $transactionRequest->setOrder($order);
        }

        if (!empty($options['refund_note'])) {
            $transactionRequest->setMerchantDescriptor($options['refund_note']);
        }

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId('ref' . time());
        $request->setTransactionRequest($transactionRequest);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse($this->getApiEnvironment());

        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok') {
            return ['status' => 'success', 'message' => 'Refund successful'];
        }

        $errorMessage = 'Refund failed';
        if ($response && $response->getMessages() && isset($response->getMessages()->getMessage()[0])) {
            $errorMessage .= ': ' . $response->getMessages()->getMessage()[0]->getText();
        }
        return ['status' => 'failure', 'message' => $errorMessage];
    }

    /**
     * Get transaction details based on the payment ID.
     *
     * @param string $paymentId
     * @return object|null
     */
    public function getTransactionDetails(string $paymentId): ?object
    {
        $request = new AnetAPI\GetTransactionDetailsRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setTransId($paymentId);

        $controller = new AnetController\GetTransactionDetailsController($request);
        $response = $controller->executeWithApiResponse($this->getApiEnvironment());

        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok') {
            $transaction = $response->getTransaction();
            $cardDetails = new \stdClass();
            $payment = $transaction->getPayment();
            $creditCard = $payment ? $payment->getCreditCard() : null;
            $cardDetails->cardNumber = $creditCard ? $creditCard->getCardNumber() : null;
            $cardDetails->expirationDate = $creditCard ? $creditCard->getExpirationDate() : null;
            return $cardDetails;
        }
        return null;
    }

    /**
     * Get the proper Authorize.net environment based on app settings.
     *
     * @return string
     */
    protected function getApiEnvironment(): string
    {
        return $this->isTestMode ? \net\authorize\api\constants\ANetEnvironment::SANDBOX : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
    }
}
