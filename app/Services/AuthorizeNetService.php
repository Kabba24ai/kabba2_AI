<?php

namespace App\Services;

use App\Models\Customers\Customer;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Illuminate\Support\Facades\Crypt;

class AuthorizeNetService
{
    protected $merchantAuthentication;
    protected bool $supportRefundOnline = true;
    protected $currency;
    protected bool $isTestMode;

    public function __construct(?array $credentials = null)
    {
        // 1) Load defaults
        $paymentSettings = \App\Helpers\ConfigurationHelper::getSettings('Payment Settings');

        $defaultLoginId = Crypt::decryptString($paymentSettings['payment_api_key'] ?? '') ?: '';
        $defaultTxnKey = Crypt::decryptString($paymentSettings['payment_api_secret'] ?? '') ?: '';
        $defaultTestMode = $paymentSettings['payment_test_mode'] ?? false;

        // 2) Override if provided
        $loginId = $credentials['login_id'] ?? $defaultLoginId;
        $transactionKey = $credentials['transaction_key'] ?? $defaultTxnKey;
        $testMode = $credentials['test_mode'] ?? $defaultTestMode;

        if (empty($loginId) || empty($transactionKey)) {
            throw new \Exception('Payment API credentials are not set.');
        }

        $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuthentication->setName($loginId);
        $this->merchantAuthentication->setTransactionKey($transactionKey);

        $this->isTestMode = $testMode === true || $testMode === 'true' || $testMode === 1 || $testMode === '1';

    }

    private function executeWithApiResponseTimed($controller)
    {
        $start = microtime(true);
        $response = $controller->executeWithApiResponse($this->getApiEnvironment());
        $durationMs = round((microtime(true) - $start) * 1000, 2);
        logger()->info("AuthorizeNet API call duration: {$durationMs} ms. seconds: " . round($durationMs / 1000, 2) . 's');
        return $response;
    }

/**
     * Create a new customer profile with payment profile.
     *
     * @param string $uniqueId
     * @param array $customer
     * @param string|null $opaqueDataValue
     * @param array $cardData
     * @return array
     */
    public function createCustomer(string $uniqueId, array $customer, ?string $opaqueDataValue = null, array $cardData = []): array
    {
        // Set up customer profile
        $profile = new AnetAPI\CustomerProfileType();
        $profile->setMerchantCustomerId($uniqueId);
        $profile->setEmail($customer['email'] ?? $uniqueId);
        $profile->setDescription($customer['description'] ?? '');

        // Set up billing address
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
        if (!empty($customer['company_name'])) {
            $customerAddress->setCompany($customer['company_name']);
        }

        if (!empty($customer['billing_address']) && is_array($customer['billing_address'])) {
            // if (!empty($customer['billing_address']['address'])) {
            //     $customerAddress->setAddress($customer['billing_address']['address']);
            // }
            // if (!empty($customer['billing_address']['city'])) {
            //     $customerAddress->setCity($customer['billing_address']['city']);
            // }
            // if (!empty($customer['billing_address']['state_name'])) {
            //     $customerAddress->setState($customer['billing_address']['state_name']);
            // }
            if (!empty($customer['billing_address']['zip_code'])) {
                $customerAddress->setZip($customer['billing_address']['zip_code']);
            }
            if (!empty($customer['billing_address']['country'])) {
                $customerAddress->setCountry($customer['billing_address']['country']);
            }
        }

        // Set up payment profile
        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setBillTo($customerAddress);

        // Handle payment method - prioritize opaqueDataValue over cardData
        if (!empty($opaqueDataValue)) {
            // Set up payment profile with Accept.js opaqueData
            $opaqueData = new AnetAPI\OpaqueDataType();
            $opaqueData->setDataDescriptor('COMMON.ACCEPT.INAPP.PAYMENT');
            $opaqueData->setDataValue($opaqueDataValue);

            $paymentType = new AnetAPI\PaymentType();
            $paymentType->setOpaqueData($opaqueData);
            $paymentProfile->setPayment($paymentType);
        } elseif (!empty($cardData) && !empty($cardData['card_number'])) {
            // Set up payment profile with card data
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber(preg_replace('/\s+/', '', $cardData['card_number']));

            if (!empty($cardData['mm_yy'])) {
                $expiry = $cardData['mm_yy'];
                $expiryData = explode('/', $expiry);
                $expiration_month = str_pad($expiryData[0], 2, '0', STR_PAD_LEFT);
                $expiration_year = $expiryData[1];
                $creditCard->setExpirationDate('20' . $expiration_year . '-' . $expiration_month);
            }

            if (!empty($cardData['card_cvv'])) {
                $creditCard->setCardCode($cardData['card_cvv']);
            }

            $paymentType = new AnetAPI\PaymentType();
            $paymentType->setCreditCard($creditCard);
            $paymentProfile->setPayment($paymentType);
        } else {
            throw new \Exception('Either opaqueDataValue or valid cardData must be provided');
        }

        $profile->setPaymentProfiles([$paymentProfile]);

        // Create the request
        $createRequest = new AnetAPI\CreateCustomerProfileRequest();
        $createRequest->setMerchantAuthentication($this->merchantAuthentication);
        $createRequest->setProfile($profile);
        //$createRequest->setValidationMode($this->isTestMode ? 'none' : 'liveMode');
        $createRequest->setValidationMode('none');

        $controller = new AnetController\CreateCustomerProfileController($createRequest);
        $createResponse = $this->executeWithApiResponseTimed($controller);

        if ($createResponse && $createResponse->getMessages()->getResultCode() === 'Ok') {
            $profileId = $createResponse->getCustomerProfileId();
            $paymentProfileId = $createResponse->getCustomerPaymentProfileIdList()[0] ?? null;

            return [
                'customer_profile_id' => $profileId,
                'payment_profile_id' => $paymentProfileId,
            ];
        } else {
            $error = 'Unknown error';
            if ($createResponse && $createResponse->getMessages() && $createResponse->getMessages()->getMessage()) {
                $msgObj = $createResponse->getMessages()->getMessage()[0];
                $errorCode = method_exists($msgObj, 'getCode') ? $msgObj->getCode() : '';
                $errorText = method_exists($msgObj, 'getText') ? $msgObj->getText() : '';
                $error = "[$errorCode] $errorText";
            }
            if($errorCode === "E00039") {
                // Duplicate profile - try to find existing profile ID
                $existingProfileId = $this->findExistingCustomerProfileId($uniqueId);
                logger()->error("Duplicate customer profile detected for uniqueId {$uniqueId}. Existing profile ID: {$existingProfileId}");
                if ($existingProfileId) {
                    if($objCustomer = Customer::where('unique_id', trim($uniqueId))->first()) {
                        logger()->info("Customer record found for unique_id {$uniqueId}. Current authorize_profile_id: {$objCustomer->authorize_profile_id}");

                        $objCustomer->authorize_profile_id = $existingProfileId;
                        $objCustomer->save();
                    }else {
                        logger()->warning("No customer record found for unique_id {$uniqueId} while handling duplicate profile error.");
                    }

                }
            }
            throw new \Exception('Failed to create customer profile: ' . $error);
        }
    }

    public function findOrCreateCustomerProfileAndPaymentProfile(string $uniqueId, array $customer, ?string $opaqueDataValue = null, ?array $cardData = [])
    {
        // 1. Try to find customer profile by merchantCustomerId (your uniqueId)
        //$profileId = $this->findExistingCustomerProfileId($uniqueId);
        $profileId = $customer['authorize_profile_id'] ?? null;
        // 2. If profile does not exist, create it using the new createCustomer method
        if (!$profileId) {
            return $this->createCustomer($uniqueId, $customer, $opaqueDataValue, $cardData);
        }

        // 3. Profile exists - get existing payment profiles
        $getProfileRequest = new AnetAPI\GetCustomerProfileRequest();
        $getProfileRequest->setMerchantAuthentication($this->merchantAuthentication);
        $getProfileRequest->setCustomerProfileId($profileId);

        $getProfileController = new AnetController\GetCustomerProfileController($getProfileRequest);
        $getProfileResponse = $this->executeWithApiResponseTimed($getProfileController);

        if (!$getProfileResponse || $getProfileResponse->getMessages()->getResultCode() !== 'Ok') {
            $msgObj = $getProfileResponse?->getMessages()?->getMessage()[0] ?? null;
            throw new \Exception(sprintf(
                'Failed to retrieve customer profile%s%s',
                $msgObj ? " [{$msgObj->getCode()}]" : '',
                $msgObj ? " {$msgObj->getText()}" : ''
            ));
        }

        $paymentProfiles = $getProfileResponse->getProfile()->getPaymentProfiles();

        // 4. Handle payment profile logic
        if (!empty($opaqueDataValue)) {

            if ($cardData && !empty($cardData['card_number'])) {
                // For opaque data with card data, check if a similar payment profile exists
                $existingPaymentProfileId = $this->findMatchingPaymentProfile($paymentProfiles, $cardData);

                if ($existingPaymentProfileId) {
                    return [
                        'customer_profile_id' => $profileId,
                        'payment_profile_id' => $existingPaymentProfileId,
                    ];
                }

            }

            // For opaque data, create a new payment profile
            return $this->createPaymentProfileForExistingCustomer($profileId, $customer, $opaqueDataValue, null);
        } elseif (!empty($cardData) && !empty($cardData['card_number'])) {
            // For card data, check if a similar payment profile exists
            $existingPaymentProfileId = $this->findMatchingPaymentProfile($paymentProfiles, $cardData);

            if ($existingPaymentProfileId) {
                return [
                    'customer_profile_id' => $profileId,
                    'payment_profile_id' => $existingPaymentProfileId,
                ];
            } else {
                // Create new payment profile with card data
                return $this->createPaymentProfileForExistingCustomer($profileId, $customer, null, $cardData);
            }
        } else {
            throw new \Exception('Failed to create payment profile: No payment method provided');
            // No payment method provided, return existing profile with first payment profile
            // $paymentProfileId = $paymentProfiles && count($paymentProfiles) > 0
            //     ? $paymentProfiles[0]->getCustomerPaymentProfileId()
            //     : null;

            // return [
            //     'customer_profile_id' => $profileId,
            //     'payment_profile_id' => $paymentProfileId,
            // ];
        }
    }

    /**
     * Create a payment profile for an existing customer.
     *
     * @param string $customerProfileId
     * @param array $customer
     * @param string|null $opaqueDataValue
     * @param array|null $cardData
     * @return array
     */
    private function createPaymentProfileForExistingCustomer(string $customerProfileId, array $customer, ?string $opaqueDataValue = null, ?array $cardData = null): array
    {
        // Set up payment profile
        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $paymentProfile->setCustomerType('individual');

        // Set up billing address
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
        if (!empty($customer['address'])) {
            $customerAddress->setAddress($customer['address']);
        }
        if (!empty($customer['billing_address']) && is_array($customer['billing_address'])) {
            // if (!empty($customer['billing_address']['address'])) {
            //     $customerAddress->setAddress($customer['billing_address']['address']);
            // }
            if (!empty($customer['billing_address']['city'])) {
                $customerAddress->setCity($customer['billing_address']['city']);
            }
            if (!empty($customer['billing_address']['state_name'])) {
                $customerAddress->setState($customer['billing_address']['state_name']);
            }
            if (!empty($customer['billing_address']['zip_code'])) {
                $customerAddress->setZip($customer['billing_address']['zip_code']);
            }
            if (!empty($customer['billing_address']['country'])) {
                $customerAddress->setCountry($customer['billing_address']['country']);
            }
        }
        $paymentProfile->setBillTo($customerAddress);

        // Handle payment method
        if (!empty($opaqueDataValue)) {

            $opaqueData = new AnetAPI\OpaqueDataType();
            $opaqueData->setDataDescriptor('COMMON.ACCEPT.INAPP.PAYMENT');
            $opaqueData->setDataValue($opaqueDataValue);

            $paymentType = new AnetAPI\PaymentType();
            $paymentType->setOpaqueData($opaqueData);
            $paymentProfile->setPayment($paymentType);
        } elseif (!empty($cardData) && !empty($cardData['card_number'])) {
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber(preg_replace('/\s+/', '', $cardData['card_number']));

            if (!empty($cardData['mm_yy'])) {
                $expiry = $cardData['mm_yy'];
                $expiryData = explode('/', $expiry);
                $expiration_month = str_pad($expiryData[0], 2, '0', STR_PAD_LEFT);
                $expiration_year = $expiryData[1];
                $creditCard->setExpirationDate('20' . $expiration_year . '-' . $expiration_month);
            }

            if (!empty($cardData['card_cvv'])) {
                $creditCard->setCardCode($cardData['card_cvv']);
            }

            $paymentType = new AnetAPI\PaymentType();
            $paymentType->setCreditCard($creditCard);
            $paymentProfile->setPayment($paymentType);
        } else {
            throw new \Exception('Either opaqueDataValue or valid cardData must be provided');
        }

        // Create the payment profile request
        $createRequest = new AnetAPI\CreateCustomerPaymentProfileRequest();
        $createRequest->setMerchantAuthentication($this->merchantAuthentication);
        $createRequest->setCustomerProfileId($customerProfileId);
        $createRequest->setPaymentProfile($paymentProfile);

        // Validation mode

        /**
         * Checks AVS (Address Verification System) settings.
         * If you want to turn off AVS verification, set it to none.
         */
        //$createRequest->setValidationMode($this->isTestMode ? 'none' : 'liveMode');
        $createRequest->setValidationMode('none');

        $controller = new AnetController\CreateCustomerPaymentProfileController($createRequest);
        $createResponse = $this->executeWithApiResponseTimed($controller);

        if ($createResponse && $createResponse->getMessages()->getResultCode() === 'Ok') {
            return [
                'customer_profile_id' => $customerProfileId,
                'payment_profile_id' => $createResponse->getCustomerPaymentProfileId(),
            ];
        } else {
            // ✅ Handle duplicate as success (re-use existing profile id)
            if ($createResponse
                && $createResponse->getMessages()
                && isset($createResponse->getMessages()->getMessage()[0])
            ) {
                $msg = $createResponse->getMessages()->getMessage()[0];
                $code = $msg->getCode();

                if ($code === 'E00039') {
                    $existingPaymentProfileId = $createResponse->getCustomerPaymentProfileId(); // <-- present in your dump

                    if (!empty($existingPaymentProfileId)) {
                        return [
                            'customer_profile_id' => $customerProfileId,
                            'payment_profile_id'  => $existingPaymentProfileId,
                            'duplicate'           => true,
                        ];
                    }
                }
            }

            $error = 'Unknown error';
            if ($createResponse && $createResponse->getMessages() && $createResponse->getMessages()->getMessage()) {
                $msgObj = $createResponse->getMessages()->getMessage()[0];
                $errorCode = method_exists($msgObj, 'getCode') ? $msgObj->getCode() : '';
                $errorText = method_exists($msgObj, 'getText') ? $msgObj->getText() : '';
                $error = "[$errorCode] $errorText";
            }
            throw new \Exception('Failed to create payment profile: ' . $error);
        }
    }

    /**
     * Find a matching payment profile based on card data.
     *
     * @param array $paymentProfiles
     * @param array $cardData
     * @return string|null
     */
    private function findMatchingPaymentProfile(array $paymentProfiles, array $cardData): ?string
    {
        if (empty($paymentProfiles) || empty($cardData['card_number'])) {
            return null;
        }

        $cardNumber = preg_replace('/\s+/', '', $cardData['card_number']);
        $last4 = substr($cardNumber, -4);

        foreach ($paymentProfiles as $profile) {
            $payment = $profile->getPayment();

            if ($payment && $payment->getCreditCard()) {
                $existingCard = $payment->getCreditCard();
                $existingCardNumber = $existingCard->getCardNumber();

                // Compare last 4 digits (Authorize.Net masks the card number)
                if ($existingCardNumber && substr($existingCardNumber, -4) === $last4) {

                    // Additional check for expiration date if available
                    if (!empty($cardData['mm_yy'])) {
                        $expiry = $cardData['mm_yy'];
                        $expiryData = explode('/', $expiry);
                        $expiration_month = str_pad($expiryData[0], 2, '0', STR_PAD_LEFT);
                        $expiration_year = $expiryData[1];
                        $newExpirationDate = '20' . $expiration_year . '-' . $expiration_month;

                        $existingExpirationDate = $existingCard->getExpirationDate();

                        // if ($existingExpirationDate === $newExpirationDate) {
                        //     return $profile->getCustomerPaymentProfileId();
                        // }

                        return $profile->getCustomerPaymentProfileId();
                    } else {
                        // If no expiration date to compare, just match on last 4
                        return $profile->getCustomerPaymentProfileId();
                    }
                }
            }
        }

        return null;
    }

    /**
     * Try to find an existing customer profile by merchantCustomerId.
     */
    public function findExistingCustomerProfileId(string $merchantCustomerId): ?string
    {
        $searchRequest = new AnetAPI\GetCustomerProfileIdsRequest();
        $searchRequest->setMerchantAuthentication($this->merchantAuthentication);
        $searchController = new AnetController\GetCustomerProfileIdsController($searchRequest);
        $searchResponse = $this->executeWithApiResponseTimed($searchController);
        if ($searchResponse && $searchResponse->getMessages()->getResultCode() === 'Ok') {
            $profileIds = $searchResponse->getIds();
            if (empty($profileIds)) {
                return null;
            }
            foreach ($profileIds as $profileId) {
                $getRequest = new AnetAPI\GetCustomerProfileRequest();
                $getRequest->setMerchantAuthentication($this->merchantAuthentication);
                $getRequest->setCustomerProfileId($profileId);
                $getController = new AnetController\GetCustomerProfileController($getRequest);
                $getResponse = $this->executeWithApiResponseTimed($getController);
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
     * Charge an existing Authorize.Net CIM customer/payment profile.
     *
     * @param string $customerProfileId
     * @param string $paymentProfileId
     * @param float  $amount
     * @param array  $options  ['order_number' => string, 'card_code' => string]
     * @return array
     */
    public function chargeCustomerProfile(string $customerProfileId, string $paymentProfileId, float $amount, array $options = []): array
    {
        // 1) Build profile reference
        $profilePayment = new AnetAPI\CustomerProfilePaymentType();
        $profilePayment->setCustomerProfileId($customerProfileId);

        $paymentProfileObj = new AnetAPI\PaymentProfileType();
        $paymentProfileObj->setPaymentProfileId($paymentProfileId);
        $profilePayment->setPaymentProfile($paymentProfileObj);

        // 2) Build transaction request
        $txnRequest = new AnetAPI\TransactionRequestType();
        $txnRequest->setTransactionType('authCaptureTransaction');
        $txnRequest->setAmount($amount);
        $txnRequest->setProfile($profilePayment);

        // Optional invoice / order number
        if (!empty($options['order_number'])) {
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($options['order_number']);
            $txnRequest->setOrder($order);
        }

        // 3) Create & send API request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId('ref' . time());
        $request->setTransactionRequest($txnRequest);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $this->executeWithApiResponseTimed($controller);
        logger()->info('AuthorizeNet profile charge response: ' . json_encode($response));

        // 4) Handle success
        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok' && $response->getTransactionResponse() !== null && $response->getTransactionResponse()->getResponseCode() == '1') {
            $tr = $response->getTransactionResponse();
            logger()->info('AuthorizeNet transaction successful: ' . json_encode($tr));
            // Try to enrich with card info from the payment profile
            $cardInfo = $this->getCardInfoFromPaymentProfile($customerProfileId, $paymentProfileId);
            $cardType = $cardInfo['card_type'] ?? null;

            return [
                'status' => 'success',
                'payment_status' => 'Paid',
                'payment_response' => $tr,
                'message' => 'Payment successful',
                'transaction_id' => $tr && method_exists($tr, 'getTransId') ? $tr->getTransId() : null,
                'auth_code' => $tr && method_exists($tr, 'getAuthCode') ? $tr->getAuthCode() : null,
                'customer_profile_id' => $customerProfileId,
                'payment_profile_id' => $paymentProfileId,
                // Account number and card type may not always be present; fetch type via profile as above
                'card_number' => $tr && method_exists($tr, 'getAccountNumber') ? $tr->getAccountNumber() : null,
                'card_type' => $cardType,
            ];
        }

        // 5) Handle errors
        $errorMessage = 'Payment failed';
        if ($response) {
            // Prefer transactionResponse errors first if present
            $tr = method_exists($response, 'getTransactionResponse') ? $response->getTransactionResponse() : null;
            if ($tr && method_exists($tr, 'getErrors') && $tr->getErrors()) {
                $err = $tr->getErrors()[0];
                $errorMessage .= ': ' . ($err->getErrorText() ?? 'Unknown error');
            } elseif ($response->getMessages() && isset($response->getMessages()->getMessage()[0])) {
                $errorMessage .= ': ' . $response->getMessages()->getMessage()[0]->getText();
            }
        }

        $paymentResponse = $response && method_exists($response, 'getTransactionResponse') ? $response->getTransactionResponse() : null;
        $errorCode = $response && $response->getMessages() && isset($response->getMessages()->getMessage()[0]) ? $response->getMessages()->getMessage()[0]->getCode() : null;

        $errorData = [
            'status' => 'failure',
            'payment_response' => $paymentResponse,
            'error_code' => $errorCode,
            'payment_status' => 'Failed',
            'message' => $errorMessage,
            'customer_profile_id' => $customerProfileId,
            'payment_profile_id' => $paymentProfileId,
        ];

        logger()->error('AuthorizeNet chargeCustomerProfile failed', [
            'customer_profile_id' => $customerProfileId,
            'payment_profile_id'  => $paymentProfileId,
            'message'             => $errorMessage,
            'error_code'          => $errorData['error_code'],
            'payment_response'    => $paymentResponse,
            'result_code'         => $response ? $response->getMessages()->getResultCode() : null,
            'transaction_errors'  => $tr && method_exists($tr, 'getErrors') && $tr->getErrors()
                ? array_map(fn($e) => ['code' => $e->getErrorCode(), 'text' => $e->getErrorText()], $tr->getErrors())
                : [],
            'transaction_id'      => $tr && method_exists($tr, 'getTransId') ? $tr->getTransId() : null,
            'response_code'       => $tr && method_exists($tr, 'getResponseCode') ? $tr->getResponseCode() : null,
        ]);

        return $errorData;
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
                $options['card_data'] ?? [], // Optional card data for matching
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
                return [
                    'status' => 'failure',
                    'payment_status' => 'Failed',
                    'message' => 'Failed to create or retrieve customer/payment profile',
                ];
                // fallback to opaque data if profile/payment profile could not be created
                // $paymentType = new AnetAPI\PaymentType();
                // $paymentType->setOpaqueData($opaqueData);
                // $transactionRequest->setPayment($paymentType);
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
        $response = $this->executeWithApiResponseTimed($controller);
        logger()->info('AuthorizeNet response for order ' . ($options['order_number'] ?? 'N/A') . ': ' . json_encode($response));
        // ---- 5. Handle Response ----
        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok' && $response->getTransactionResponse() && $response->getTransactionResponse()->getResponseCode() == '1') {
            $transactionResponse = $response->getTransactionResponse();
            logger()->info('AuthorizeNet transaction successful for order ' . ($options['order_number'] ?? 'N/A') . ': ' . json_encode($transactionResponse));
            // Return known profile/payment ids or extract from response
            // If payment profile IDs exist, fetch card type & expiry from the profile

            $cardType = null;
            if ($customerProfileId && $paymentProfileId) {
                $cardInfo = $this->getCardInfoFromPaymentProfile($customerProfileId, $paymentProfileId);
                $cardType = $cardInfo['card_type'];
            }
            return [
                'status' => 'success',
                'payment_status' => 'Paid',
                'payment_response' => $transactionResponse,
                'message' => 'Payment successful',
                'transaction_id' => $transactionResponse && method_exists($transactionResponse, 'getTransId') ? $transactionResponse->getTransId() : null,
                'auth_code' => $transactionResponse && method_exists($transactionResponse, 'getAuthCode') ? $transactionResponse->getAuthCode() : null,
                'customer_profile_id' => $customerProfileId,
                'payment_profile_id' => $paymentProfileId,
                'card_number' => $transactionResponse && method_exists($transactionResponse, 'getAccountNumber') ? $transactionResponse->getAccountNumber() : null,
                'card_type' => $cardType,
            ];
        }

        // ---- 6. Handle Error ----
        $errorMessage = 'Payment failed';

        if ($response) {
            // Prefer transactionResponse errors first if present
            $tr = method_exists($response, 'getTransactionResponse') ? $response->getTransactionResponse() : null;
            if ($tr && method_exists($tr, 'getErrors') && $tr->getErrors()) {
                $err = $tr->getErrors()[0];
                $errorMessage .= ': ' . ($err->getErrorText() ?? 'Unknown error');
            } elseif ($response->getMessages() && isset($response->getMessages()->getMessage()[0])) {
                $errorMessage .= ': ' . $response->getMessages()->getMessage()[0]->getText();
            }
        }

        logger()->error('AuthorizeNet transaction failed for order ' . ($options['order_number'] ?? 'N/A') . ': ' . $errorMessage);

        $paymentResponse = $response && method_exists($response, 'getTransactionResponse') ? $response->getTransactionResponse() : null;
        $errorCode = $response && $response->getMessages() && isset($response->getMessages()->getMessage()[0]) ? $response->getMessages()->getMessage()[0]->getCode() : null;

        $errorData = [
            'status' => 'failure',
            'payment_response' => $paymentResponse,
            'error_code' => $errorCode,
            'payment_status' => 'Failed',
            'message' => $errorMessage,
            'customer_profile_id' => $customerProfileId,
            'payment_profile_id' => $paymentProfileId,
        ];

        logger()->error('AuthorizeNet transaction failed', [
            'customer_profile_id' => $customerProfileId,
            'payment_profile_id'  => $paymentProfileId,
            'message'             => $errorMessage,
            'error_code'          => $errorData['error_code'],
            'payment_response'    => $paymentResponse,
            'result_code'         => $response ? $response->getMessages()->getResultCode() : null,
            'transaction_errors'  => $tr && method_exists($tr, 'getErrors') && $tr->getErrors()
                ? array_map(fn($e) => ['code' => $e->getErrorCode(), 'text' => $e->getErrorText()], $tr->getErrors())
                : [],
            'transaction_id'      => $tr && method_exists($tr, 'getTransId') ? $tr->getTransId() : null,
            'response_code'       => $tr && method_exists($tr, 'getResponseCode') ? $tr->getResponseCode() : null,
        ]);

        return $errorData;
    }

    /**
     * Create a payment transaction using card data, creating/finding both
     * customer and payment profiles if customer data is present.
     *
     * @param array $cardData ['card_number' => string, 'mm_yy' => string, 'card_cvv' => string]
     * @param float $amount
     * @param array $options ['customer' => [...], 'order_number' => ...]
     * @return array
     */
    public function createCardDataTransaction(array $cardData, float $amount, array $options = []): array
    {
        // Validate card data
        if (empty($cardData['card_number']) || empty($cardData['mm_yy']) || empty($cardData['card_cvv'])) {
            return [
                'status' => 'failure',
                'payment_status' => 'Failed',
                'message' => 'Card number, expiration date, and CVV are required',
            ];
        }

        $cardNumber = preg_replace('/\s+/', '', $cardData['card_number']);
        $expiry = $cardData['mm_yy'];
        $expiryData = explode('/', $expiry);

        if (count($expiryData) !== 2) {
            return [
                'status' => 'failure',
                'payment_status' => 'Failed',
                'message' => 'Invalid expiration date format. Use MM/YY format',
            ];
        }

        $expiration_month = str_pad($expiryData[0], 2, '0', STR_PAD_LEFT);
        $expiration_year = $expiryData[1];

        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType('authCaptureTransaction');
        $transactionRequest->setAmount($amount);

        $customerProfileId = null;
        $paymentProfileId = null;

        // ---- 1. Use Customer & Payment Profile if Customer Info is Provided ----
        if (!empty($options['customer']['unique_id']) && !empty($options['customer'])) {
            try {
                // Try to find or create both customer & payment profile using card data
                $profileResult = $this->findOrCreateCustomerProfileAndPaymentProfile(
                    $options['customer']['unique_id'],
                    $options['customer'],
                    null, // No opaque data
                    $cardData, // Card data for payment profile
                );
                $customerProfileId = $profileResult['customer_profile_id'];
                $paymentProfileId = $profileResult['payment_profile_id'];

                if ($customerProfileId && $paymentProfileId) {
                    // Use the profile and payment profile for the transaction
                    $profilePayment = new AnetAPI\CustomerProfilePaymentType();
                    $profilePayment->setCustomerProfileId($customerProfileId);

                    $paymentProfileObj = new AnetAPI\PaymentProfileType();
                    $paymentProfileObj->setPaymentProfileId($paymentProfileId);
                    $profilePayment->setPaymentProfile($paymentProfileObj);

                    $transactionRequest->setProfile($profilePayment);
                } else {
                    throw new \Exception('Failed to create or retrieve customer/payment profile');
                    // fallback to direct card data if profile/payment profile could not be created
                    //$this->setDirectCardPayment($transactionRequest, $cardNumber, $expiration_month, $expiration_year, $cardData['card_cvv']);
                }
            } catch (\Exception $e) {
                // If profile creation fails, fallback to direct card payment
                logger()->warning('Profile creation failed, using direct card payment: ' . $e->getMessage());
                return [
                    'status' => 'failure',
                    'payment_status' => 'Failed',
                    'message' => 'Failed to create or retrieve customer/payment profile: ' . $e->getMessage(),
                ];
                //$this->setDirectCardPayment($transactionRequest, $cardNumber, $expiration_month, $expiration_year, $cardData['card_cvv']);
            }
        } else {
            // ---- 2. Fallback: Guest Transaction with Direct Card Data (no profile) ----
            $this->setDirectCardPayment($transactionRequest, $cardNumber, $expiration_month, $expiration_year, $cardData['card_cvv']);
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
        $response = $this->executeWithApiResponseTimed($controller);
        logger()->info('AuthorizeNet card transaction response: ' . json_encode($response));

        // ---- 5. Handle Response ----
        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok' && $response->getTransactionResponse() && $response->getTransactionResponse()->getResponseCode() == '1') {
            $transactionResponse = $response->getTransactionResponse();
            logger()->info('AuthorizeNet card transaction successful: ' . json_encode($transactionResponse));
            // If payment profile IDs exist, fetch card type from the profile
            $cardType = null;
            if ($customerProfileId && $paymentProfileId) {
                $cardInfo = $this->getCardInfoFromPaymentProfile($customerProfileId, $paymentProfileId);
                $cardType = $cardInfo['card_type'];
            }

            return [
                'status' => 'success',
                'payment_status' => 'Paid',
                'payment_response' => $transactionResponse,
                'message' => 'Payment successful',
                'transaction_id' => $transactionResponse && method_exists($transactionResponse, 'getTransId') ? $transactionResponse->getTransId() : null,
                'auth_code' => $transactionResponse && method_exists($transactionResponse, 'getAuthCode') ? $transactionResponse->getAuthCode() : null,
                'customer_profile_id' => $customerProfileId,
                'payment_profile_id' => $paymentProfileId,
                'card_number' => $transactionResponse && method_exists($transactionResponse, 'getAccountNumber') ? $transactionResponse->getAccountNumber() : null,
                'card_type' => $cardType,
            ];
        }

        // ---- 6. Handle Error ----
        $errorMessage = 'Payment failed';
        if ($response) {
            // Prefer transactionResponse errors first if present
            $tr = method_exists($response, 'getTransactionResponse') ? $response->getTransactionResponse() : null;
            if ($tr && method_exists($tr, 'getErrors') && $tr->getErrors()) {
                $err = $tr->getErrors()[0];
                $errorMessage .= ': ' . ($err->getErrorText() ?? 'Unknown error');
            } elseif ($response->getMessages() && isset($response->getMessages()->getMessage()[0])) {
                $errorMessage .= ': ' . $response->getMessages()->getMessage()[0]->getText();
            }
        }
        logger()->error('AuthorizeNet card transaction failed: ' . $errorMessage);

        $paymentResponse = $response && method_exists($response, 'getTransactionResponse') ? $response->getTransactionResponse() : null;
        $errorCode = $response && $response->getMessages() && isset($response->getMessages()->getMessage()[0]) ? $response->getMessages()->getMessage()[0]->getCode() : null;

        $errorData = [
            'status' => 'failure',
            'payment_response' => $paymentResponse,
            'error_code' => $errorCode,
            'payment_status' => 'Failed',
            'message' => $errorMessage,
            'customer_profile_id' => $customerProfileId,
            'payment_profile_id' => $paymentProfileId,
        ];

        logger()->error('AuthorizeNet card transaction failed', [
            'customer_profile_id' => $customerProfileId,
            'payment_profile_id'  => $paymentProfileId,
            'message'             => $errorMessage,
            'error_code'          => $errorData['error_code'],
            'payment_response'    => $paymentResponse,
            'result_code'         => $response ? $response->getMessages()->getResultCode() : null,
            'transaction_errors'  => $tr && method_exists($tr, 'getErrors') && $tr->getErrors()
                ? array_map(fn($e) => ['code' => $e->getErrorCode(), 'text' => $e->getErrorText()], $tr->getErrors())
                : [],
            'transaction_id'      => $tr && method_exists($tr, 'getTransId') ? $tr->getTransId() : null,
            'response_code'       => $tr && method_exists($tr, 'getResponseCode') ? $tr->getResponseCode() : null,
        ]);

        return $errorData;
    }

    /**
     * Helper method to set direct card payment on transaction request.
     *
     * @param AnetAPI\TransactionRequestType $transactionRequest
     * @param string $cardNumber
     * @param string $expirationMonth
     * @param string $expirationYear
     * @param string $cardCvv
     * @return void
     */
    private function setDirectCardPayment(AnetAPI\TransactionRequestType $transactionRequest, string $cardNumber, string $expirationMonth, string $expirationYear, string $cardCvv): void
    {
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($cardNumber);
        $creditCard->setExpirationDate('20' . $expirationYear . '-' . $expirationMonth);
        $creditCard->setCardCode($cardCvv);

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setCreditCard($creditCard);
        $transactionRequest->setPayment($paymentType);
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

        if (!in_array($transactionDetails->status, ['settledSuccessfully', 'refundSettledSuccessfully'])) {
            return ['status' => 'failure', 'message' => 'Transaction not settled; try void instead'];
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
        $response = $this->executeWithApiResponseTimed($controller);

        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok' && $response->getTransactionResponse() && in_array($response->getTransactionResponse()->getResponseCode(), ['1', '4'])) {
            $transactionResponse = $response->getTransactionResponse();

            return [
                'status' => 'success',
                'message' => 'Refund successful',
                'transaction_id' => $transactionResponse && method_exists($transactionResponse, 'getTransId') ? $transactionResponse->getTransId() : null,
                'auth_code' => $transactionResponse && method_exists($transactionResponse, 'getAuthCode') ? $transactionResponse->getAuthCode() : null,
                'card_number' => $transactionResponse && method_exists($transactionResponse, 'getAccountNumber') ? $transactionResponse->getAccountNumber() : null,
                'card_type' => $transactionResponse && method_exists($transactionResponse, 'getAccountType') ? $transactionResponse->getAccountType() : null,
            ];
        }

        $errorMessage = 'Refund failed';
        if ($response && $response->getMessages() && isset($response->getMessages()->getMessage()[0])) {
            $errorMessage .= ': ' . $response->getMessages()->getMessage()[0]->getText();
        }

        return [
            'status' => 'failure',
            'error_code' => $response->getMessages()->getMessage()[0]->getCode() ?? null,
            'message' => $errorMessage,
        ];
    }

    /**
     * Void an order based on payment ID.
     *
     * @param string $paymentId
     * @param array $options
     * @return array
     */
    public function voidOrder(string $paymentId, array $options = []): array
    {
        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType('voidTransaction');
        $transactionRequest->setRefTransId($paymentId);

        if (!empty($options['order_number'])) {
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($options['order_number']);
            $transactionRequest->setOrder($order);
        }

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId('ref' . time());
        $request->setTransactionRequest($transactionRequest);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $this->executeWithApiResponseTimed($controller);

        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok' && $response->getTransactionResponse() && in_array($response->getTransactionResponse()->getResponseCode(), ['1', '4'])) {
            $transactionResponse = $response->getTransactionResponse();

            return [
                'status' => 'success',
                'message' => 'Void successful',
                'transaction_id' => $transactionResponse && method_exists($transactionResponse, 'getTransId') ? $transactionResponse->getTransId() : null,
                'auth_code' => $transactionResponse && method_exists($transactionResponse, 'getAuthCode') ? $transactionResponse->getAuthCode() : null,
            ];
        }

        $errorMessage = 'Void failed';
        if ($response && $response->getMessages() && isset($response->getMessages()->getMessage()[0])) {
            $errorMessage .= ': ' . $response->getMessages()->getMessage()[0]->getText();
        }
        return [
            'status' => 'failure',
            'error_code' => $response->getMessages()->getMessage()[0]->getCode() ?? null,
            'message' => $errorMessage,
        ];
    }

    public function getCardInfoFromPaymentProfile($customerProfileId, $paymentProfileId): array
    {
        $cardType = null;
        try {
            $request = new AnetAPI\GetCustomerPaymentProfileRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setCustomerProfileId($customerProfileId);
            $request->setCustomerPaymentProfileId($paymentProfileId);

            $controller = new AnetController\GetCustomerPaymentProfileController($request);
            $response = $this->executeWithApiResponseTimed($controller);

            if ($response && $response->getMessages()->getResultCode() === 'Ok') {
                $card = $response->getPaymentProfile()->getPayment()->getCreditCard();
                if ($card) {
                    $cardType = $card->getCardType();
                }
            }
        } catch (\Exception $e) {
            // Optionally log error
        }
        return [
            'card_type' => $cardType,
        ];
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
        $response = $this->executeWithApiResponseTimed($controller);

        if ($response !== null && $response->getMessages()->getResultCode() === 'Ok' && $response->getTransactionResponse() && in_array($response->getTransactionResponse()->getResponseCode(), ['1', '4'])) {
            $transaction = $response->getTransaction();
            $cardDetails = new \stdClass();
            $payment = $transaction->getPayment();
            $creditCard = $payment ? $payment->getCreditCard() : null;
            $cardDetails->cardNumber = $creditCard ? $creditCard->getCardNumber() : null;
            $cardDetails->expirationDate = $creditCard ? $creditCard->getExpirationDate() : null;
            $cardDetails->status = $transaction ? $transaction->getTransactionStatus() : null;
            return $cardDetails;
        }
        return null;
    }

    /**
     * Get transaction details summary by transaction id.
     *
     * @param string $paymentId
     * @return array
     */
    public function getTransactionDetailsSummary(string $paymentId): array
    {
        if ($paymentId === '') {
            return [
                'status' => 'failure',
                'message' => 'Transaction id is required',
            ];
        }

        $request = new AnetAPI\GetTransactionDetailsRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setTransId($paymentId);

        $controller = new AnetController\GetTransactionDetailsController($request);
        $response = $this->executeWithApiResponseTimed($controller);

        if ($response && $response->getMessages()->getResultCode() === 'Ok') {
            $transaction = method_exists($response, 'getTransaction') ? $response->getTransaction() : null;
            $transactionResponse = method_exists($response, 'getTransactionResponse') ? $response->getTransactionResponse() : null;

            $amount = null;
            if ($transaction && method_exists($transaction, 'getSettleAmount')) {
                $amount = $transaction->getSettleAmount();
            }
            if ($amount === null && $transaction && method_exists($transaction, 'getAuthAmount')) {
                $amount = $transaction->getAuthAmount();
            }

            $submitTime = $transaction && method_exists($transaction, 'getSubmitTimeUTC') ? $transaction->getSubmitTimeUTC() : null;
            $submitTimeStr = $submitTime instanceof \DateTime ? $submitTime->format('Y-m-d H:i:s') : ($submitTime !== null ? (string) $submitTime : null);

            return [
                'status' => 'success',
                'transaction_id' => $paymentId,
                'amount' => $amount !== null ? (float) $amount : null,
                'transaction_status' => $transaction && method_exists($transaction, 'getTransactionStatus') ? $transaction->getTransactionStatus() : null,
                'response_code' => $transactionResponse && method_exists($transactionResponse, 'getResponseCode') ? $transactionResponse->getResponseCode() : null,
                'auth_code' => $transactionResponse && method_exists($transactionResponse, 'getAuthCode') ? $transactionResponse->getAuthCode() : null,
                'invoice_number' => $transaction && method_exists($transaction, 'getInvoiceNumber') ? $transaction->getInvoiceNumber() : null,
                'account_number' => $transactionResponse && method_exists($transactionResponse, 'getAccountNumber') ? $transactionResponse->getAccountNumber() : null,
                'account_type' => $transactionResponse && method_exists($transactionResponse, 'getAccountType') ? $transactionResponse->getAccountType() : null,
                'submit_time' => $submitTimeStr,
            ];
        }

        $errorMessage = 'Transaction not found';
        if ($response && $response->getMessages() && isset($response->getMessages()->getMessage()[0])) {
            $errorMessage = $response->getMessages()->getMessage()[0]->getText() ?? $errorMessage;
        }

        return [
            'status' => 'failure',
            'message' => $errorMessage,
        ];
    }

    /**
     * Get total debited amount for a specific order number.
     *
     * @param string $orderNumber
     * @param int $daysBack Number of days to search back for settled batches
     * @return array
     */
    public function getDebitedAmountByOrderNumber(string $orderNumber, int $daysBack = 365): array
    {
        if ($orderNumber === '') {
            return [
                'status' => 'failure',
                'message' => 'Order number is required',
            ];
        }

        $totalAmount = 0.0;
        $transactions = [];
        $seenIds = [];

        // 1) Check unsettled transactions (recent)
        try {
            $unsettledRequest = new AnetAPI\GetUnsettledTransactionListRequest();
            $unsettledRequest->setMerchantAuthentication($this->merchantAuthentication);

            $unsettledController = new AnetController\GetUnsettledTransactionListController($unsettledRequest);
            $unsettledResponse = $this->executeWithApiResponseTimed($unsettledController);

            if ($unsettledResponse && $unsettledResponse->getMessages()->getResultCode() === 'Ok') {
                $unsettledList = $unsettledResponse->getTransactions() ?? [];
                foreach ($unsettledList as $transaction) {
                    $invoiceNumber = $transaction->getInvoiceNumber() ?? '';
                    if ($invoiceNumber !== $orderNumber) {
                        continue;
                    }

                    $status = strtolower((string) $transaction->getTransactionStatus());
                    if (in_array($status, ['declined', 'failed', 'error', 'voided', 'returneditem', 'failedreview'])) {
                        continue;
                    }

                    $transactionId = $transaction->getTransId();
                    if (!$transactionId || in_array($transactionId, $seenIds)) {
                        continue;
                    }

                    $amount = null;
                    if (method_exists($transaction, 'getSettleAmount')) {
                        $amount = $transaction->getSettleAmount();
                    }
                    if ($amount === null && method_exists($transaction, 'getAuthAmount')) {
                        $amount = $transaction->getAuthAmount();
                    }
                    $amount = $amount !== null ? (float) $amount : 0.0;

                    $totalAmount += $amount;
                    $seenIds[] = $transactionId;
                    $transactions[] = [
                        'transaction_id' => $transactionId,
                        'amount' => $amount,
                        'status' => $transaction->getTransactionStatus(),
                        'source' => 'unsettled',
                    ];
                }
            }
        } catch (\Exception $e) {
            logger()->warning('Error checking unsettled transactions: ' . $e->getMessage());
        }

        // 2) Check settled batches
        try {
            $firstSettlementDate = new \DateTime();
            $firstSettlementDate->modify("-{$daysBack} days");
            $lastSettlementDate = new \DateTime();

            $batchRequest = new AnetAPI\GetSettledBatchListRequest();
            $batchRequest->setMerchantAuthentication($this->merchantAuthentication);
            $batchRequest->setIncludeStatistics(true);
            $batchRequest->setFirstSettlementDate($firstSettlementDate);
            $batchRequest->setLastSettlementDate($lastSettlementDate);

            $batchController = new AnetController\GetSettledBatchListController($batchRequest);
            $batchResponse = $this->executeWithApiResponseTimed($batchController);

            if ($batchResponse && $batchResponse->getMessages()->getResultCode() === 'Ok') {
                $batchList = $batchResponse->getBatchList() ?? [];
                foreach ($batchList as $batch) {
                    $batchId = $batch->getBatchId();
                    if (!$batchId) {
                        continue;
                    }

                    $batchTransactions = $this->getTransactionsFromBatchByOrderNumber($batchId, $orderNumber);
                    foreach ($batchTransactions as $entry) {
                        if (in_array($entry['transaction_id'], $seenIds)) {
                            continue;
                        }

                        $totalAmount += (float) $entry['amount'];
                        $seenIds[] = $entry['transaction_id'];
                        $transactions[] = $entry;
                    }
                }
            }
        } catch (\Exception $e) {
            logger()->warning('Error checking settled batches: ' . $e->getMessage());
        }

        return [
            'status' => 'success',
            'order_number' => $orderNumber,
            'total_amount' => round($totalAmount, 2),
            'transactions' => $transactions,
        ];
    }

    /**
     * Get declined/failed transactions from Authorize.Net (unsettled + settled batches)
     *
     * @param int $daysBack Number of days to search back (default 30)
     * @return array
     */
    public function getDeclinedTransactions(int $daysBack = 365): array
    {
        try {
            logger()->info("========== Starting getDeclinedTransactions for {$daysBack} days ==========");
            $allDeclinedTransactions = [];

            // 1. Get unsettled (recent) declined transactions
            logger()->info('Step 1: Fetching unsettled declined transactions');
            $unsettledResult = $this->getUnsettledDeclinedTransactions();
            if ($unsettledResult['success']) {
                $unsettledCount = count($unsettledResult['transactions']);
                logger()->info("Found {$unsettledCount} unsettled declined transactions");
                $allDeclinedTransactions = array_merge($allDeclinedTransactions, $unsettledResult['transactions']);
            } else {
                logger()->warning('Failed to fetch unsettled transactions');
            }

            // 2. Get settled declined transactions from batches
            logger()->info('Step 2: Fetching settled declined transactions from batches');
            $settledResult = $this->getSettledDeclinedTransactions($daysBack);
            if ($settledResult['success']) {
                $settledCount = count($settledResult['transactions']);
                logger()->info("Found {$settledCount} settled declined transactions");
                $allDeclinedTransactions = array_merge($allDeclinedTransactions, $settledResult['transactions']);
            } else {
                logger()->warning('Failed to fetch settled transactions');
            }

            logger()->info('Step 3: Removing duplicates from total ' . count($allDeclinedTransactions) . ' transactions');

            // Remove duplicates based on transaction_id
            $uniqueTransactions = [];
            $seenIds = [];
            foreach ($allDeclinedTransactions as $transaction) {
                $txnId = $transaction['transaction_id'];
                if (!in_array($txnId, $seenIds)) {
                    $uniqueTransactions[] = $transaction;
                    $seenIds[] = $txnId;
                }
            }

            logger()->info('After deduplication: ' . count($uniqueTransactions) . ' unique transactions');

            // Sort by submit time (newest first)
            usort($uniqueTransactions, function ($a, $b) {
                $timeA = strtotime($a['submit_time']);
                $timeB = strtotime($b['submit_time']);
                return $timeB - $timeA;
            });

            logger()->info('========== Completed: Returning ' . count($uniqueTransactions) . ' declined transactions ==========');

            return [
                'success' => true,
                'message' => 'Declined transactions retrieved successfully',
                'transactions' => $uniqueTransactions,
                'total_count' => count($uniqueTransactions),
            ];
        } catch (\Exception $e) {
            logger()->error('Error fetching declined transactions from Authorize.Net: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'transactions' => [],
            ];
        }
    }

    /**
     * Get unsettled declined transactions (last 24 hours typically)
     *
     * @return array
     */
    private function getUnsettledDeclinedTransactions(): array
    {
        try {
            $request = new AnetAPI\GetUnsettledTransactionListRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);

            $controller = new AnetController\GetUnsettledTransactionListController($request);
            $response = $this->executeWithApiResponseTimed($controller);

            if ($response === null || $response->getMessages()->getResultCode() !== 'Ok') {
                return ['success' => false, 'transactions' => []];
            }

            $transactions = $response->getTransactions() ?? [];
            $declinedTransactions = [];

            foreach ($transactions as $transaction) {
                $transactionStatus = $transaction->getTransactionStatus();

                if (in_array(strtolower($transactionStatus), ['declined', 'failed', 'error', 'failedreview', 'returneditem'])) {
                    $submitTime = $transaction->getSubmitTimeUTC();
                    $submitTimeStr = $submitTime instanceof \DateTime ? $submitTime->format('Y-m-d H:i:s') : (string) $submitTime;

                    $declinedTransactions[] = [
                        'transaction_id' => $transaction->getTransId(),
                        'submit_time' => $submitTimeStr,
                        'transaction_status' => $transactionStatus,
                        'invoice_number' => $transaction->getInvoiceNumber(),
                        'first_name' => $transaction->getFirstName(),
                        'last_name' => $transaction->getLastName(),
                        'account_number' => $transaction->getAccountNumber(),
                        'account_type' => $transaction->getAccountType(),
                        'settle_amount' => $transaction->getSettleAmount(),
                    ];
                }
            }

            return [
                'success' => true,
                'transactions' => $declinedTransactions,
            ];
        } catch (\Exception $e) {
            logger()->error('Error fetching unsettled declined transactions: ' . $e->getMessage());
            return ['success' => false, 'transactions' => []];
        }
    }

    /**
     * Get declined transactions from settled batches
     *
     * @param int $daysBack Number of days to search back
     * @return array
     */
    private function getSettledDeclinedTransactions(int $daysBack): array
    {
        try {
            // Get settled batches for the date range
            $firstSettlementDate = new \DateTime();
            $firstSettlementDate->modify("-{$daysBack} days");
            $lastSettlementDate = new \DateTime();

            logger()->info("Fetching settled batches from {$firstSettlementDate->format('Y-m-d')} to {$lastSettlementDate->format('Y-m-d')} ({$daysBack} days)");

            $request = new AnetAPI\GetSettledBatchListRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setIncludeStatistics(true);
            $request->setFirstSettlementDate($firstSettlementDate);
            $request->setLastSettlementDate($lastSettlementDate);

            $controller = new AnetController\GetSettledBatchListController($request);
            $response = $this->executeWithApiResponseTimed($controller);

            if ($response === null || $response->getMessages()->getResultCode() !== 'Ok') {
                $errorMessage = 'Failed to get settled batch list';
                if ($response !== null) {
                    $messages = $response->getMessages()->getMessage();
                    if (!empty($messages)) {
                        $errorMessage .= ': ' . $messages[0]->getText();
                    }
                }
                logger()->warning($errorMessage);
                return ['success' => false, 'transactions' => []];
            }

            $batchList = $response->getBatchList() ?? [];
            $batchCount = count($batchList);
            logger()->info("Found {$batchCount} settled batches for {$daysBack} days");

            if ($batchCount === 0) {
                logger()->info('No batches found for the date range');
                return ['success' => true, 'transactions' => []];
            }

            // Sort batches by settlement date (newest first)
            usort($batchList, function ($a, $b) {
                $dateA = $a->getSettlementTimeUTC();
                $dateB = $b->getSettlementTimeUTC();
                if ($dateA instanceof \DateTime && $dateB instanceof \DateTime) {
                    return $dateB->getTimestamp() - $dateA->getTimestamp();
                }
                return 0;
            });

            $declinedTransactions = [];
            $processedBatches = 0;

            logger()->info("Will process ALL {$batchCount} batches (no limits)");

            // Get transactions from EVERY single batch
            foreach ($batchList as $index => $batch) {
                $batchId = $batch->getBatchId();
                $settlementDate = $batch->getSettlementTimeUTC();
                $settlementDateStr = $settlementDate instanceof \DateTime ? $settlementDate->format('Y-m-d H:i:s') : 'unknown';

                $currentProgress = $processedBatches + 1;
                logger()->info("[{$currentProgress}/{$batchCount}] Processing batch {$batchId} (settled: {$settlementDateStr})");

                $batchTransactions = $this->getTransactionsFromBatch($batchId);

                if (!empty($batchTransactions)) {
                    $declinedTransactions = array_merge($declinedTransactions, $batchTransactions);
                    logger()->info("[{$currentProgress}/{$batchCount}] Batch {$batchId} added " . count($batchTransactions) . ' declined transactions. Running total: ' . count($declinedTransactions));
                } else {
                    logger()->info("[{$currentProgress}/{$batchCount}] Batch {$batchId} has no declined transactions");
                }

                $processedBatches++;

                // Add small delay to avoid API rate limits
                if ($processedBatches % 20 === 0) {
                    logger()->info("Progress: Processed {$processedBatches}/{$batchCount} batches, pausing briefly...");
                    usleep(200000); // 0.2 second delay every 20 batches
                }
            }

            logger()->info('========== BATCH PROCESSING COMPLETE ==========');
            logger()->info("Total batches processed: {$processedBatches}/{$batchCount}");
            logger()->info('Total declined transactions found: ' . count($declinedTransactions));
            logger()->info('===============================================');

            return [
                'success' => true,
                'transactions' => $declinedTransactions,
            ];
        } catch (\Exception $e) {
            logger()->error('Error fetching settled declined transactions: ' . $e->getMessage());
            return ['success' => false, 'transactions' => []];
        }
    }

    /**
     * Get declined transactions from a specific batch
     *
     * @param string $batchId
     * @return array
     */
    private function getTransactionsFromBatch(string $batchId): array
    {
        try {
            $request = new AnetAPI\GetTransactionListRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setBatchId($batchId);

            $controller = new AnetController\GetTransactionListController($request);
            $response = $this->executeWithApiResponseTimed($controller);

            if ($response === null || $response->getMessages()->getResultCode() !== 'Ok') {
                logger()->warning("Failed to get transactions for batch {$batchId}");
                return [];
            }

            $transactions = $response->getTransactions() ?? [];
            $declinedTransactions = [];

            foreach ($transactions as $transaction) {
                $transactionStatus = $transaction->getTransactionStatus();

                // Filter for declined/failed statuses
                if (in_array(strtolower($transactionStatus), ['declined', 'failed', 'error', 'voided'])) {
                    $submitTime = $transaction->getSubmitTimeUTC();
                    $submitTimeStr = $submitTime instanceof \DateTime ? $submitTime->format('Y-m-d H:i:s') : (string) $submitTime;

                    $declinedTransactions[] = [
                        'transaction_id' => $transaction->getTransId(),
                        'submit_time' => $submitTimeStr,
                        'transaction_status' => $transactionStatus,
                        'invoice_number' => $transaction->getInvoiceNumber() ?? null,
                        'first_name' => $transaction->getFirstName() ?? null,
                        'last_name' => $transaction->getLastName() ?? null,
                        'account_number' => $transaction->getAccountNumber() ?? null,
                        'account_type' => $transaction->getAccountType() ?? null,
                        'settle_amount' => $transaction->getSettleAmount() ?? 0,
                        'batch_id' => $batchId,
                    ];
                }
            }

            if (count($declinedTransactions) > 0) {
                logger()->info("Batch {$batchId}: Found " . count($declinedTransactions) . ' declined transactions');
            }

            return $declinedTransactions;
        } catch (\Exception $e) {
            logger()->error('Error fetching transactions from batch ' . $batchId . ': ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get transactions from a specific batch that match the invoice/order number.
     *
     * @param string $batchId
     * @param string $orderNumber
     * @return array
     */
    private function getTransactionsFromBatchByOrderNumber(string $batchId, string $orderNumber): array
    {
        try {
            $request = new AnetAPI\GetTransactionListRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setBatchId($batchId);

            $controller = new AnetController\GetTransactionListController($request);
            $response = $this->executeWithApiResponseTimed($controller);

            if ($response === null || $response->getMessages()->getResultCode() !== 'Ok') {
                return [];
            }

            $transactions = $response->getTransactions() ?? [];
            $matched = [];

            foreach ($transactions as $transaction) {
                $invoiceNumber = $transaction->getInvoiceNumber() ?? '';
                if ($invoiceNumber !== $orderNumber) {
                    continue;
                }

                $status = strtolower((string) $transaction->getTransactionStatus());
                if (in_array($status, ['declined', 'failed', 'error', 'voided', 'returneditem', 'failedreview'])) {
                    continue;
                }

                $amount = null;
                if (method_exists($transaction, 'getSettleAmount')) {
                    $amount = $transaction->getSettleAmount();
                }
                if ($amount === null && method_exists($transaction, 'getAuthAmount')) {
                    $amount = $transaction->getAuthAmount();
                }
                $amount = $amount !== null ? (float) $amount : 0.0;

                $matched[] = [
                    'transaction_id' => $transaction->getTransId(),
                    'amount' => $amount,
                    'status' => $transaction->getTransactionStatus(),
                    'batch_id' => $batchId,
                    'source' => 'settled',
                ];
            }

            return $matched;
        } catch (\Exception $e) {
            logger()->error('Error fetching transactions from batch ' . $batchId . ': ' . $e->getMessage());
            return [];
        }
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
     * Get the proper Authorize.net environment based on app settings.
     *
     * @return string
     */
    protected function getApiEnvironment(): string
    {
        return $this->isTestMode ? \net\authorize\api\constants\ANetEnvironment::SANDBOX : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
    }
}
