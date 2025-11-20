<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Request
use App\Http\Requests\Api\Admin\V1\Customers\StoreRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Customers\ListResource;

// Model
use App\Models\Customers\Customer;
use App\Models\Customers\Tag;

class StoreController extends BaseController
{
    /**
     * Create Customer
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();
        try {
            $tagIds = [];
            $tags = explode(',', $validated['tags'] ?? '');
            foreach ($tags as $tagId) {
                $tagId = trim($tagId);
                if (empty($tagId)) {
                    continue;
                }
                if($rowId = Tag::where('id', $tagId)->first()->id){
                    $tagIds[] = $rowId;
                }
            }

            $customer = Customer::create([
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'tags' => !empty($tagIds) ? json_encode($tagIds) : null,
            ]);

            if($validated['note'] ?? false){
                $customer->notes()->create([
                    'description' => $validated['note'],
                    'created_by' => $user->id,
                    'created_date'  => now()->toDateString(),
                    'created_time'  => now()->format('H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.customers.customer_creation_failed'),
                'error' => $e->getMessage(),
            ]);
        }
        // Process tags

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.customers.customer_created_successfully'),
            'customer' => new ListResource($customer),
        ]);
    }
}
