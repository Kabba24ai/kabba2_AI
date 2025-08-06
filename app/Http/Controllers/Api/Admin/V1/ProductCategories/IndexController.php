<?php

namespace App\Http\Controllers\Api\Admin\V1\ProductCategories;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\ProductCategories\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\ProductCategories\ListResource;

// Model
use App\Models\ProductManagement\ProductCategory;

class IndexController extends BaseController
{
    /**
     * Product Categories List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;

        $productCategories = ProductCategory::with([
            'childCategories' => function ($q) {
                $q->sortOrder();
            },
        ])
            ->whereNull('parent_id')
            ->sortOrder()
            ->paginate($perPage);

        if ($productCategories->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.product_categories.no_categories_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.product_categories.categories_found'),
            'product_categories' => ListResource::collection($productCategories),
            'pagination' => [
                'current_page' => $productCategories->currentPage(),
                'last_page' => $productCategories->lastPage(),
                'per_page' => $productCategories->perPage(),
                'total' => $productCategories->total(),
            ],
        ]);
    }
}
