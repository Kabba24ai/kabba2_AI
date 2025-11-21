<?php

namespace App\Http\Controllers\Admin\Crm\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Tag\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Customers\Tag;
use App\Models\Customers\Customer;



class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
{
    $data = $request->validated();

    // Debug 1 — log the validated request
    \Log::info('Tag Store Request', $data);

    // Create the tag
    $tag = Tag::create([
        'name' => $data['name'],
    ]);

    // Debug 2 — confirm tag creation
    \Log::info('Tag Created', $tag->toArray());

    $customerDebug = null;

    if (!empty($data['unique_id'])) {

        $customer = Customer::where('unique_id', $data['unique_id'])->first();

        // Debug 3 — log whether or not customer was found
        if ($customer) {
            \Log::info('Customer Found', [
                'unique_id' => $data['unique_id'],
                'customer_id' => $customer->id,
            ]);
        } else {
            \Log::warning('Customer NOT Found', [
                'unique_id' => $data['unique_id'],
            ]);
        }

        if ($customer) {
            // Before updating
            $existingTags = json_decode($customer->tags, true) ?? [];

            \Log::info('Customer Existing Tags (before)', [
                'tags' => $existingTags
            ]);

            // Update array
            if (!in_array($tag->id, $existingTags)) {
                $existingTags[] = $tag->id;
            }

            // Save
            $customer->update([
                'tags' => json_encode($existingTags),
            ]);

            // Debug 4 — after update
            \Log::info('Customer Tags (after)', [
                'tags' => $existingTags
            ]);

            $customerDebug = $existingTags;
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Tag created successfully!',
        'tag' => $tag,
    ]);
}
}
