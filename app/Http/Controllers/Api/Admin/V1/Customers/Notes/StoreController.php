<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers\Notes;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Customers\Notes\StoreRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerNotes\ListResource;

// Models
use App\Models\Customers\Customer;

class StoreController extends BaseController
{
    /**
     * Create Customer Note
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(StoreRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $customer = Customer::where('unique_id', $validatedData['customer_unique_id'])->firstOrFail();

            $user = $request->user() ?? auth('api_user')->user();

            $note = $customer->notes()->create([
                'description' => $validatedData['note'],
                'created_by' => $user ? $user->id : null,
                'created_date' => now()->toDateString(),
                'created_time' => now()->format('H:i:s'),
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.customers.notes.note_created'),
                'note' => new ListResource($note->load('user')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.customers.notes.note_creation_failed'),
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
