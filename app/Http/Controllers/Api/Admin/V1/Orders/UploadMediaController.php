<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders;

use App\Http\Controllers\Controller;

// Helpers
use App\Helpers\MediaHelper;
use Illuminate\Http\JsonResponse;

// Enums
use App\Enums\Orders\OrderMediaType;

// Events
use App\Events\Admin\Orders\OrderMediaUploadedEvent;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\UploadMediaRequest;
use App\Http\Resources\Api\Admin\V1\OrderMedias\ListResource;
// Models
use App\Models\Orders\Order;


class UploadMediaController extends Controller
{
    /**
     * Orders Media Upload
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UploadMediaRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $validated['order_unique_id'])->firstOrFail();

            $typeEnum = OrderMediaType::from($validated['type']); // Will throw if invalid value
            if (in_array($typeEnum, [OrderMediaType::DELIVERY, OrderMediaType::PICKUP])) {
                $orderProduct = $order->products()->where('unique_id', $validated['order_product_unique_id'])->firstOrFail();
            }

            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $mediaPath = match ($typeEnum) {
                        OrderMediaType::LICENSE => 'orders/licenses',
                        OrderMediaType::DELIVERY => 'orders/deliveries',
                        OrderMediaType::PICKUP => 'orders/pickups',
                    };

                    // Upload the media file
                    if(in_array($typeEnum, [OrderMediaType::DELIVERY, OrderMediaType::PICKUP])) {
                        $mediaData = MediaHelper::uploadStorageFile('Public Asset', $file, $mediaPath, $orderProduct);
                    } else {
                        $mediaData = MediaHelper::uploadStorageFile('Public Asset', $file, $mediaPath, $order);
                    }

                    if (!empty($mediaData['mediaObj'])) {
                        $mediaAttributes = [
                            'type' => $typeEnum->value,
                            'order_product_id' => $orderProduct->id ?? null,
                            'media_id' => $mediaData['mediaObj']->id,
                            'created_by' => auth('api_user')->id(),
                        ];
                        // Add 'side' if license
                        if ($typeEnum === OrderMediaType::LICENSE && $request->filled('side')) {
                            $mediaAttributes['side'] = $request->input('side');
                        }
                        $order->media()->create($mediaAttributes);
                    }
                }
            }


            // Fire OrderMediaUploaded event
            event(new OrderMediaUploadedEvent($order, $typeEnum, auth('api_user')->user()));

            // Fetch the latest uploaded media for this order
            $latestMedia = $order->media()->with('media')->latest()->first();

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.orders.media_uploaded'),
                'media' => new ListResource($latestMedia)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => trans('messages.api.admin.v1.orders.media_upload_failed') . ': ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
