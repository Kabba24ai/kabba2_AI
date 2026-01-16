<?php

namespace App\Http\Controllers\Admin\Crm\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Tag\StoreRequest;
use App\Models\Customers\Tag;
use App\Models\Customers\Customer;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        \Log::info('Tag Store Request', $data);

        // Normalize tag name (important)
        $tagName = trim(strtolower($data['name']));

        // 🔍 Find existing tag (case-insensitive)
        $tag = Tag::whereRaw('LOWER(name) = ?', [$tagName])->first();

        if ($tag) {
            \Log::info('Tag already exists', $tag->toArray());
        } else {
            // ➕ Create only if it does NOT exist
            $tag = Tag::create([
                'name' => $tagName,
            ]);

            \Log::info('Tag created', $tag->toArray());
        }

        // -------------------------
        // Attach tag to customer
        // -------------------------
        if (!empty($data['unique_id'])) {

            $customer = Customer::where('unique_id', $data['unique_id'])->first();

            if (!$customer) {
                \Log::warning('Customer NOT Found', [
                    'unique_id' => $data['unique_id'],
                ]);
            } else {

                $existingTags = json_decode($customer->tags, true) ?? [];

                \Log::info('Customer Existing Tags (before)', [
                    'tags' => $existingTags
                ]);

                // Attach only if not already attached
                if (!in_array($tag->id, $existingTags)) {
                    $existingTags[] = $tag->id;

                    $customer->update([
                        'tags' => json_encode($existingTags),
                    ]);

                    \Log::info('Customer Tags (after)', [
                        'tags' => $existingTags
                    ]);
                } else {
                    \Log::info('Tag already attached to customer', [
                        'tag_id' => $tag->id,
                        'customer_id' => $customer->id,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tag added to customer successfully!',
            'tag' => $tag,
        ]);
    }
}
