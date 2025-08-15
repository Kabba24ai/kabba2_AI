<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Controller;

use App\Http\Requests\Api\Admin\V1\Orders\Schedules\UpdateRequest;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    /**
     * Orders Schedule Update
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UpdateRequest $request)
    {
        $validated = $request->validated();
        try {
            $user = auth('api_user')->user();
            $schedule = OrderProduct::where('unique_id', $validated['order_product_unique_id'])->firstOrFail();

            if ($validated['schedule_type'] === 'Delivery') {
                $schedule->delivery_status = $validated['schedule_status'];
                $schedule->delivery_by = $user->id;

            }else{
                $schedule->pickup_status = $validated['schedule_status'];
                $schedule->pickup_by = $user->id;
            }

            $schedule->save();
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => false,
                'message' => trans('messages.api.admin.v1.orders.schedule_update_failed'),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status' => true,
            'message' => trans('messages.api.admin.v1.orders.schedule_updated'),
        ]);
    }
}
