<?php

namespace App\Http\Controllers\Admin\Configurations\NotificationSettings;


use App\Http\Controllers\Controller;
use App\Models\Configurations\UserNotificationSetting;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\Configurations\NotificationSettings\SaveManualRequest;


class SaveManualController extends Controller
{
    public function __invoke(SaveManualRequest $request)
    {
        $data = $request->validated();

        $manual = UserNotificationSetting::create([
            'type'    => $data['type'],
            'user_id' => null,
            'name'    => $data['name'],
            'phone'   => $data['phone'],
            'source'  => 'manual',
        ]);

        return response()->json([
            'success' => true,
            'row'     => $manual,
            'message' => 'Manual recipient added',
        ]);
    }
}
