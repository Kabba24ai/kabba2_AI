<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\RemoveMediaRequest;

// Models
use App\Models\Orders\OrderMedia;

class RemoveMediaController extends Controller
{
    /**
     * Orders Media Remove
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(RemoveMediaRequest $request)
    {
        $validated = $request->validated();

        try {
            $orderMedia = OrderMedia::where('unique_id', $validated['order_media_unique_id'])->first();

            // Delete the media record
            $orderMedia->delete();

            return response()->json(['message' => trans('messages.api.admin.v1.orders.media_remove_success')]);

        } catch (\Exception $e) {
            return response()->json(['error' => trans('messages.api.admin.v1.orders.media_remove_failed') . ': ' . $e->getMessage()], 500);
        }
    }
}
