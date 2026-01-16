<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Orders\ListResource;

// Model
use App\Models\Orders\Order;

class IndexController extends BaseController
{
    /**
     * Orders List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;
        $status = $validatedData['status'] ?? null;
        $paymentMethod = $validatedData['payment_method'] ?? null;
        $search = $validatedData['search'] ?? null;
        $categoryId = $validatedData['category_id'] ?? null;

        $orders = Order::query()
            ->with('shippingAddress', 'billingAddress', 'licenseMedia', 'products.product', 'lastPayment', 'notes', 'products.deliveryMedia', 'products.pickupMedia', 'products.deliverySignatureMedia', 'products.returnSignatureMedia', 'products.deliveryStore', 'products.pickupStore', 'products.softEquipment')
            ->when(
                $status && $status !== 'All',
                fn($query) => $query->whereHas('lastPayment', function ($q) use ($status) {
                    $q->where('status', $status);
                }),
            )
            ->when($search, function ($query) use ($search) {
                $query->whereHas('shippingAddress', function ($q) use ($search) {
                    $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->when(
                $categoryId,
                fn($query) => $query->whereHas('products.product.categories', function ($q) use ($categoryId) {
                    $q->where('product_categories.id', $categoryId);
                }),
            )
            ->when($paymentMethod && $paymentMethod !== 'All', fn($query) => $query->whereHas('lastPayment', fn($q) => $q->where('payment_method', $paymentMethod)))
            ->orderByDesc('id')
            ->paginate($perPage);

        if ($orders->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.no_orders_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $orders->getCollection()->transform(function ($order) {
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

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.orders_found'),
            'orders' => ListResource::collection($orders),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }
}
