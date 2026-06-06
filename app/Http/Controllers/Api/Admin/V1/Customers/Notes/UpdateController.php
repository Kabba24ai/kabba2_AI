<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers\Notes;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerNotes\ListResource;

// Requests
use App\Http\Requests\Api\Admin\V1\Customers\Notes\UpdateRequest;

// Models
use App\Models\Customers\CustomerNote;

class UpdateController extends BaseController
{
    /**
     * Update Customer Note
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UpdateRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $note = CustomerNote::where('unique_id', $validatedData['customer_note_unique_id'])->firstOrFail();

            $note->update([
                'description' => $validatedData['note'],
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.customers.notes.note_updated'),
                'note' => new ListResource($note->refresh()->load('user')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.customers.notes.note_not_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
