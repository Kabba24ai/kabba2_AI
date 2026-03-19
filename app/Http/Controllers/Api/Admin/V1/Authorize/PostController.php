<?php

namespace App\Http\Controllers\Api\Admin\V1\Authorize;

use App\Enums\Communication\SmsType;
use App\Http\Controllers\Api\BaseController;
use App\Models\Authrise\Submission;
use App\Services\AuthorizeNetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Throwable;

use App\Http\Requests\Api\Admin\V1\Authorize\PostRequest;
use App\Services\TwilioService;

class PostController extends BaseController
{

    /**
     * Authorize Payment
     * @group Kabba Sales Site
     */
    public function __invoke(PostRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $cardLastFour = substr($validated['card_number'], -4);

        $submission = Submission::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'business_name' => $validated['business_name'] ?? null,
            'street_address' => $validated['street_address'],
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'card_name' => $validated['card_name'],
            'card_last_four' => $cardLastFour,
            'card_expiry' => $validated['expiry_date'],
            'status' => 'pending',
            'amount' => $validated['amount'],
            'schedule_datetime' => $validated['schedule_datetime'] ?? null,
            'meta' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        try {
            $authorizeService = new AuthorizeNetService([
                'login_id' => "8E695xRcs",
                'transaction_key' => "85Xb468m38RQh3uC",
                'test_mode' => false,
            ]);

            $customerPayload = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'company' => $validated['business_name'] ?? null,
                'address' => trim($validated['street_address']),
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'country' => "US",
                'phone' => $validated['phone_number'] ?? null,
                'description' => 'Authrise onboarding ' . $submission->unique_id,
                'billing_address' => [
                    'zip_code' => $validated['zip_code'] ?? null,
                    'country' => "US",
                ],
            ];

            $cardData = [
                'card_number' => $validated['card_number'],
                'mm_yy' => $validated['expiry_date'],
                'card_cvv' => $validated['cvc'],
            ];

            $profileResult = $authorizeService->createCustomer($submission->unique_id, $customerPayload, null, $cardData);

            $cardInfo = [];
            if (!empty($profileResult['customer_profile_id']) && !empty($profileResult['payment_profile_id'])) {
                $cardInfo = $authorizeService->getCardInfoFromPaymentProfile(
                    $profileResult['customer_profile_id'],
                    $profileResult['payment_profile_id'],
                );
            }

            $chargeResponse =   $authorizeService->chargeCustomerProfile(
                                    $profileResult['customer_profile_id'],
                                    $profileResult['payment_profile_id'],
                                    $validated['amount'],
                                );

            $isChargeSuccessful = ($chargeResponse['status'] ?? null) === 'success';

            $submission->fill([
                'status' => $isChargeSuccessful ? 'completed' : 'failed',
                'customer_profile_id' => $profileResult['customer_profile_id'] ?? null,
                'payment_profile_id' => $profileResult['payment_profile_id'] ?? null,
                'card_brand' => $cardInfo['card_type'] ?? ($chargeResponse['card_type'] ?? null),
                'response_message' => $chargeResponse['message'] ?? ($isChargeSuccessful
                    ? 'Authorize.Net payment completed successfully.'
                    : 'Authorize.Net payment failed.'),
                'meta' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'charge_response' => $chargeResponse,
                ],
            ])->save();

            if (!$isChargeSuccessful) {
                return response()->json([
                    'success' => false,
                    'message' => $chargeResponse['message'] ?? 'Payment could not be completed.',
                    'data' => [
                        'reference' => $submission->unique_id,
                        'customer_profile_id' => $submission->customer_profile_id,
                        'payment_profile_id' => $submission->payment_profile_id,
                    ],
                ], 422);
            }

            $twilio = new TwilioService();

            $phoneNumbers = [
                '+16158156734',
                '+918000912126',
                '+919824096016',
                '+919725252582',
            ];

            foreach ($phoneNumbers as $to) {
                $twilio->sendSms($to, 'Hello, A New Customer has just signed up for a Kabba account', [], [
                    'sms_type' => SmsType::NEW_CUSTOMER_SIGNUP_NOTIFICATION,
                ]);
            }

            if (! $isChargeSuccessful) {
                return response()->json([
                    'success' => false,
                    'message' => $chargeResponse['message'] ?? 'Payment could not be completed.',
                    'data' => [
                        'reference' => $submission->unique_id,
                        'customer_profile_id' => $submission->customer_profile_id,
                        'payment_profile_id' => $submission->payment_profile_id,
                    ],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $chargeResponse['message'] ?? 'Submission saved and Authorize.Net profile created.',
                'data' => [
                    'reference' => $submission->unique_id,
                    'customer_profile_id' => $submission->customer_profile_id,
                    'payment_profile_id' => $submission->payment_profile_id,
                    'card_last_four' => $submission->card_last_four,
                    'card_brand' => $submission->card_brand,
                ],
            ], 201);
        } catch (Throwable $exception) {
            logger()->error('Authorize action failed', [
                'submission_id' => $submission->id,
                'error' => $exception->getMessage(),
            ]);

            $submission->fill([
                'status' => 'failed',
                'response_message' => $exception->getMessage(),
            ])->save();

            return response()->json([
                'success' => false,
                'message' => 'We could not complete the Authorize.Net operation. Please verify the details and try again.',
            ], 422);
        }
    }
}
