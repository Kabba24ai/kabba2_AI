<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\ShowRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Orders\ListResource;

// Model
use App\Models\Orders\Order;

class ShowController extends BaseController
{
    /**
     * Order Details
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(ShowRequest $request)
    {
        $validatedData = $request->validated();

        $uniqueId = $validatedData['unique_id'];

        $order = Order::query()->with('shippingAddress', 'billingAddress', 'licenseMedia', 'products.product', 'lastPayment', 'notes', 'products.deliveryMedia', 'products.pickupMedia', 'products.deliverySignatureMedia', 'products.returnSignatureMedia', 'products.deliveryStore','products.pickupStore', 'products.softEquipment')
            ->where('unique_id', $uniqueId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.orders.order_not_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $order->products->map(function ($orderProduct) {
            $orderProduct->load('checklistQuestions.answers', 'checklistQuestions.deliverySelectedAnswer', 'checklistQuestions.returnSelectedAnswer', 'equipment.checklistMaster.customerAdminTemplate.templateQuestions.question.answers','equipment.checklistMaster.customerAdminTemplate.templateQuestions.question.category','equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.answers','equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.category','equipmentRentalReadyTemplate.checklistQuestions');

            $item = $orderProduct->equipment;


            $isRentedAndDelivered = $item?->current_status->isRented()
                && $orderProduct
                && ($orderProduct->is_delivered == 1);

            if ($isRentedAndDelivered || !$item) {
                $questions = optional($orderProduct->checklistQuestions) ?? collect();
                $rentalReadyQuestions = collect(
                                            $orderProduct->equipmentRentalReadyTemplate?->checklistQuestions
                                        )
                                            ->pluck('rental_ready_qa_json')
                                            ->filter()
                                            ->map(fn ($item) => is_string($item) ? json_decode($item, true) : $item)
                                            ->values();

            } else {
                $templateQuestions = $item->checklistMaster?->customerAdminTemplate?->templateQuestions;
                $questions = collect($templateQuestions)
                    ->pluck('question')
                    ->filter()
                    ->values();

                $rentalReadyQuestions = $item->checklistMaster?->rentalReadyTemplate?->templateQuestions;
                $rentalReadyQuestions = collect($rentalReadyQuestions)
                    ->pluck('question')   // same as map->question but clearer
                    ->filter()            // remove nulls
                    ->values() ?? collect();
            }

            $orderProduct->setRelation('checklistQA', $questions);
            $orderProduct->setRelation('rentalReadyQA', $rentalReadyQuestions);

            return $orderProduct;
        });

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.order_found'),
            'order' => new ListResource($order),
        ]);
    }
}
