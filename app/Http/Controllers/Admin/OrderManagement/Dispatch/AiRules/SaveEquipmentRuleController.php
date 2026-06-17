<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiEquipmentRule;
use Illuminate\Http\Request;

class SaveEquipmentRuleController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'product_category_id'   => 'required|exists:product_categories,id',
            'min_trailer_capacity'  => 'nullable|numeric|min:0',
            'allowed_trailer_types' => 'nullable|array',
            'allowed_trailer_types.*' => 'string|max:100',
            'allowed_truck_types'   => 'nullable|array',
            'allowed_truck_types.*' => 'string|max:100',
            'cdl_required'          => 'boolean',
            'can_share_trailer'     => 'boolean',
            'must_haul_alone'       => 'boolean',
            'special_notes'         => 'nullable|string|max:2000',
        ]);

        $validated['cdl_required']    = $request->boolean('cdl_required');
        $validated['can_share_trailer'] = $request->boolean('can_share_trailer');
        $validated['must_haul_alone'] = $request->boolean('must_haul_alone');

        DispatchAiEquipmentRule::updateOrCreate(
            ['product_category_id' => $validated['product_category_id']],
            $validated,
        );

        return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'equipment'])
            ->with('success', 'Equipment rule saved.');
    }
}
