<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiTruck;
use Illuminate\Http\Request;

class SaveTruckController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'id'           => 'nullable|exists:dispatch_ai_trucks,id',
            'truck_type'   => 'nullable|string|max:100',
            'truck_name'   => 'required|string|max:255',
            'truck_number' => 'nullable|string|max:100',
            'store_id'     => 'nullable|exists:stores,id',
            'gvwr'         => 'nullable|numeric|min:0',
            'tow_rating'   => 'nullable|numeric|min:0',
            'hitch_types'    => 'nullable|array',
            'hitch_types.*'  => 'string|max:100',
            'cdl_required' => 'boolean',
            'notes'        => 'nullable|string|max:2000',
            'is_active'    => 'boolean',
        ]);

        $validated['hitch_types']  = $validated['hitch_types'] ?? [];
        $validated['cdl_required'] = $request->boolean('cdl_required');
        $validated['is_active']    = $request->boolean('is_active', true);

        if (!empty($validated['id'])) {
            DispatchAiTruck::findOrFail($validated['id'])->update($validated);
        } else {
            DispatchAiTruck::create($validated);
        }

        return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'trucks'])
            ->with('success', 'Truck saved.');
    }
}
