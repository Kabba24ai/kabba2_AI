<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\Audiences;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsAudience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Save a reusable audience definition — rules only, never a customer
 * list. One positive mode per audience, enforced here and by the UI.
 */
class StoreController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:191'],
            'base_all'       => ['required', 'boolean'],
            'positive_mode'  => ['required_if:base_all,false', 'nullable', 'in:any,all'],
            'include_tags'   => ['required_if:base_all,false', 'nullable', 'array'],
            'include_tags.*' => ['integer', 'exists:tags,id'],
            'exclude_tags'   => ['nullable', 'array'],
            'exclude_tags.*' => ['integer', 'exists:tags,id'],
        ]);

        $audience = SmsAudience::create([
            'name'            => $data['name'],
            'base_all'        => (bool) $data['base_all'],
            'positive_mode'   => $data['base_all'] ? null : ($data['positive_mode'] ?? 'any'),
            'include_tag_ids' => $data['base_all'] ? [] : array_values($data['include_tags'] ?? []),
            'exclude_tag_ids' => array_values($data['exclude_tags'] ?? []),
            'created_by'      => auth()->id(),
        ]);

        return response()->json(['success' => true, 'audience' => $audience]);
    }
}
