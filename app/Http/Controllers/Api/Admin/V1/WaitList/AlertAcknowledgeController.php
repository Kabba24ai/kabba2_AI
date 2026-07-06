<?php

namespace App\Http\Controllers\Api\Admin\V1\WaitList;

use App\Http\Controllers\Api\BaseController;
use App\Models\WaitList\EquipmentWaitListAlert;

class AlertAcknowledgeController extends BaseController
{
    /** Acknowledge from the mobile app — records user + timestamp. */
    public function __invoke(EquipmentWaitListAlert $alert)
    {
        $alert->acknowledge(auth('api_user')->id());

        return response()->json([
            'success' => true,
            'message' => 'Wait list alert acknowledged',
        ]);
    }
}
