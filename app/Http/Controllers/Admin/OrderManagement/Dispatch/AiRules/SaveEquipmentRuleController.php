<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiEquipmentRule;
use Illuminate\Http\Request;

class SaveEquipmentRuleController extends Controller
{
    public function __invoke(Request $request)
    {
        // Bulk: array of equipment IDs with shared settings
        if ($request->has('equipment_ids')) {
            $validated = $request->validate([
                'equipment_ids'         => 'required|array|min:1',
                'equipment_ids.*'       => 'integer|exists:equipment,id',
                'min_trailer_capacity'  => 'nullable|numeric|min:0',
                'allowed_trailer_types' => 'nullable|array',
                'allowed_trailer_types.*' => 'string|max:100',
                'allowed_truck_types'   => 'nullable|array',
                'allowed_truck_types.*' => 'string|max:100',
                'cdl_required'          => 'nullable|boolean',
                'can_share_trailer'     => 'nullable|boolean',
                'must_haul_alone'       => 'nullable|boolean',
                'special_notes'         => 'nullable|string|max:2000',
            ]);

            $payload = [
                'min_trailer_capacity'  => $validated['min_trailer_capacity'] ?? null,
                'allowed_trailer_types' => $validated['allowed_trailer_types'] ?? [],
                'allowed_truck_types'   => $validated['allowed_truck_types'] ?? [],
                'cdl_required'          => $request->boolean('cdl_required'),
                'can_share_trailer'     => $request->boolean('can_share_trailer'),
                'must_haul_alone'       => $request->boolean('must_haul_alone'),
                'special_notes'         => $validated['special_notes'] ?? null,
            ];

            foreach ($validated['equipment_ids'] as $equipmentId) {
                DispatchAiEquipmentRule::updateOrCreate(
                    ['equipment_id' => $equipmentId],
                    $payload,
                );
            }

            return response()->json(['success' => true, 'saved' => count($validated['equipment_ids'])]);
        }

        // Single equipment save
        $validated = $request->validate([
            'equipment_id'          => 'required|exists:equipment,id',
            'min_trailer_capacity'  => 'nullable|numeric|min:0',
            'allowed_trailer_types' => 'nullable|array',
            'allowed_trailer_types.*' => 'string|max:100',
            'allowed_truck_types'   => 'nullable|array',
            'allowed_truck_types.*' => 'string|max:100',
            'cdl_required'          => 'nullable|boolean',
            'can_share_trailer'     => 'nullable|boolean',
            'must_haul_alone'       => 'nullable|boolean',
            'special_notes'         => 'nullable|string|max:2000',
        ]);

        $validated['cdl_required']    = $request->boolean('cdl_required');
        $validated['can_share_trailer'] = $request->boolean('can_share_trailer');
        $validated['must_haul_alone'] = $request->boolean('must_haul_alone');
        $validated['allowed_trailer_types'] = $validated['allowed_trailer_types'] ?? [];
        $validated['allowed_truck_types']   = $validated['allowed_truck_types'] ?? [];

        DispatchAiEquipmentRule::updateOrCreate(
            ['equipment_id' => $validated['equipment_id']],
            $validated,
        );

        return response()->json(['success' => true]);
    }
}
