<?php

namespace App\Http\Controllers\Api\Admin\V1\Authorize;

use App\Enums\Communication\SmsType;
use App\Http\Controllers\Api\BaseController;
use App\Models\Authrise\Submission;
use App\Services\AuthorizeNetService;
use App\Services\TwilioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Throwable;

use App\Http\Requests\Api\Admin\V1\Authorize\ScheduleRequest;

class ScheduleController extends BaseController
{

    /**
     * Schedule Auhorize
     * @group Kabba Sales Site
     */
    public function __invoke(ScheduleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $submission = Submission::where('unique_id', $validated['unique_id'])->firstOrFail();

            $submission->fill([
                'schedule_datetime' => $validated['schedule_datetime'] ?? null,
            ])->save();

            // Send SMS to admin about scheduled meeting
            $twilio = new TwilioService();

            $phoneNumbers = [
                '+16158156734',
                '+918000912126',
                '+919824096016',
                '+919725252582',
            ];

            foreach ($phoneNumbers as $to) {
                $adminMessage = sprintf(
                    "Meeting Scheduled: %s %s | Email: %s | Phone: %s | DateTime: %s | Ref: %s",
                    $submission->first_name,
                    $submission->last_name,
                    $submission->email,
                    $submission->phone_number ?? 'N/A',
                    $submission->schedule_datetime?->format('M d, Y H:i') ?? 'N/A',
                    $submission->unique_id
                );

                $twilio->sendSms($to, $adminMessage, [], [
                    'sms_type' => SmsType::NEW_CUSTOMER_SIGNUP_NOTIFICATION,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'schedule slot successfully set'
            ], 201);
        } catch (Throwable $exception) {

            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }
}
