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
            $order = Order::with('customer')->where('unique_id', $validated['order_unique_id'])->firstOrFail();

            $typeEnum = OrderMediaType::from($validated['type']); // Will throw if invalid value
            if (in_array($typeEnum, [OrderMediaType::DELIVERY, OrderMediaType::PICKUP])) {
                $orderProduct = $order->products()->where('unique_id', $validated['order_product_unique_id'])->firstOrFail();
            }

            $customer = $order->customer;

            // When license expiry date is provided, mark order for auto inject and
            // optionally attribute who set it.
            if ($request->filled('license_expiry_date')) {
                $order->auto_inject = true;
                if ($request->filled('auto_inject_by')) {
                    $order->auto_inject_by = $validated['auto_inject_by'];
                }
                $order->save();
            }

            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {

                    if ($typeEnum === OrderMediaType::LICENSE && $request->filled('side')) {
                        // Keep only one license image per side by replacing any existing one.
                        $order->media()
                            ->where('type', $typeEnum->value)
                            ->where('side', $request->input('side'))
                            ->get()
                            ->each
                            ->delete();
                    }

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

                            $side = $mediaAttributes['side'] ;


                            // ============================
                            //  CUSTOMER SEPARATE UPLOAD
                            // ============================
                            if ($customer && $request->filled('license_expiry_date')) {

                                //  Upload AGAIN for customer (NEW media)
                                $customerMediaData = MediaHelper::uploadStorageFile(
                                    'Public Asset',
                                    $file,
                                    'customers', // different path
                                    $customer
                                );

                                if (!empty($customerMediaData['mediaObj'])) {

                                    $customerMediaId = $customerMediaData['mediaObj']->id;

                                    if ($side === 'front') {

                                        if ($customer->licenseFront) {
                                            MediaHelper::removeFile($customer->licenseFront);
                                        }

                                        $customer->license_front_media_id = $customerMediaId;

                                    } elseif ($side === 'back') {

                                        if ($customer->licenseBack) {
                                            MediaHelper::removeFile($customer->licenseBack);
                                        }

                                        $customer->license_back_media_id = $customerMediaId;
                                    }

                                    // Set the auto_inject_by field if applicable
                                    if ($request->filled('license_expiry_date')) {
                                        $customer->license_expiry_date = $validated['license_expiry_date'] ?? null; // Set license expiry date if provided
                                    }


                                    $customer->save();

                                }
                            }

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
