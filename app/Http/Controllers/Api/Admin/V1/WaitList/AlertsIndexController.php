<?php

namespace App\Http\Controllers\Api\Admin\V1\WaitList;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\Admin\V1\WaitList\AlertResource;
use App\Models\WaitList\EquipmentWaitListAlert;
use Illuminate\Http\Request;

class AlertsIndexController extends BaseController
{
    /**
     * Active wait list match alerts for the internal mobile app.
     * ?status=open (default) | all
     */
    public function __invoke(Request $request)
    {
        $alerts = EquipmentWaitListAlert::query()
            ->with(['waitList.customer', 'waitList.store', 'waitList.items.equipment',
                'equipment', 'matchedCategory', 'acknowledgedBy'])
            ->when($request->input('status', 'open') === 'open', fn ($q) => $q->open())
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Wait list alerts fetched successfully',
            'alerts'  => AlertResource::collection($alerts),
        ]);
    }
}
