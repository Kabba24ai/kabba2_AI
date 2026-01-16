<?php

namespace App\Http\Controllers\Admin\Crm\Tags;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Tag;
use App\Models\Customers\Customer;

class AttachController extends Controller
{
    public function __invoke(Request $request)
    {
        \Log::info('Attach tag payload', $request->all());

        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'tag_name'    => 'required'
        ]);

        //  Force string (prevents array bug)
        $tagName = is_array($request->tag_name)
            ? $request->tag_name[0]
            : $request->tag_name;

        $tagName = trim(strtolower($tagName));

        $tag = Tag::whereRaw('LOWER(name) = ?', [$tagName])->first();

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }

        $customer = Customer::findOrFail($request->customer_id);

        $existingTags = json_decode($customer->tags, true) ?? [];

        if (in_array($tag->id, $existingTags)) {
            return response()->json([
                'success' => false,
                'message' => 'Tag already attached'
            ], 409);
        }

        $existingTags[] = $tag->id;

        $customer->update([
            'tags' => json_encode($existingTags),
        ]);

        return response()->json([
            'success' => true,
            'tag' => $tag,
        ]);
    }

}
