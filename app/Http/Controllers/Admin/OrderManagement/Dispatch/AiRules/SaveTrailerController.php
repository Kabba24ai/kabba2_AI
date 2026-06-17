<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiTrailer;
use Illuminate\Http\Request;

class SaveTrailerController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'id'               => 'nullable|exists:dispatch_ai_trailers,id',
            'trailer_name'     => 'required|string|max:255',
            'trailer_number'   => 'nullable|string|max:100',
            'store_id'         => 'nullable|exists:stores,id',
            'gvwr'             => 'nullable|numeric|min:0',
            'payload_capacity' => 'nullable|numeric|min:0',
            'deck_length'      => 'nullable|numeric|min:0',
            'deck_width'       => 'nullable|numeric|min:0',
            'hitch_type'       => 'nullable|string|max:100',
            'cdl_required'     => 'boolean',
            'notes'            => 'nullable|string|max:2000',
            'is_active'        => 'boolean',
        ]);

        $validated['cdl_required'] = $request->boolean('cdl_required');
        $validated['is_active']    = $request->boolean('is_active', true);

        if (!empty($validated['id'])) {
            DispatchAiTrailer::findOrFail($validated['id'])->update($validated);
        } else {
            DispatchAiTrailer::create($validated);
        }

        return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'trailers'])
            ->with('success', 'Trailer saved.');
    }
}
