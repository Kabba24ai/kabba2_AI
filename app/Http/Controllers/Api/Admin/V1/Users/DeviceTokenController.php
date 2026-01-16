<?php

namespace App\Http\Controllers\Api\Admin\V1\Users;

use App\Http\Controllers\Api\BaseController;

// Requests
use App\Http\Requests\Api\Admin\V1\Users\DeviceTokenRequest;
use App\Models\Iam\Personnel\UserDevice;

class DeviceTokenController extends BaseController
{
    /**
     * Update Device Token
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(DeviceTokenRequest $request)
    {
        $validatedData = $request->validated();

        $deviceToken = $validatedData['device_token'];
        $fcmToken = $validatedData['fcm_token'];

        // Update the user's device tokens in the database
        $user = $request->user();

        UserDevice::updateOrCreate(
            ['device_token' => $deviceToken],
            [
                'user_id' => $user->id,
                'fcm_token' => $fcmToken,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.users.device_tokens_updated'),
        ]);
    }

}
