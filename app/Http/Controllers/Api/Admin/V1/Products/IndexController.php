<?php

namespace App\Http\Controllers\Api\Admin\V1\Products;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Products\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Products\ListResource;

// Model
use App\Models\ProductManagement\Product;

class IndexController extends BaseController
{
    /**
     * Products List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;

        $products = Product::published()->with('categories', 'terms', 'options', 'relatedProducts', 'mediaChildren')->order()->paginate($perPage);

        if ($products->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.products.no_products_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.products.products_found'),
            'products' => ListResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }
}
