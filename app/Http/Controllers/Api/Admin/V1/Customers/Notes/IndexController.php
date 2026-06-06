<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers\Notes;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Customers\Notes\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerNotes\ListResource;

// Models
use App\Models\Customers\Customer;

class IndexController extends BaseController
{
    /**
     * Customer Notes List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $customer = Customer::where('unique_id', $validatedData['customer_unique_id'])->first();

        $notes = $customer->notes()->with('user')->latest('id')->get();

        if ($notes->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.customers.notes.no_notes_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.customers.notes.notes_found'),
            'notes' => ListResource::collection($notes),
        ]);
    }
}
