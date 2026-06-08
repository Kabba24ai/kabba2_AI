<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers\Notes;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\RemoveMediaRequest;

// Models
use App\Models\Customers\CustomerNote;

class RemoveController extends BaseController
{
    /**
     * Remove Customer Note
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(RemoveMediaRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $note = CustomerNote::where('unique_id', $validatedData['customer_note_unique_id'])->firstOrFail();
            $note->delete();

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.customers.notes.note_deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.customers.notes.note_not_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
