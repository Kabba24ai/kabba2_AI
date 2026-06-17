<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiDriverCapability;
use Illuminate\Http\Request;

class SaveDriverCapabilityController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'user_id'                   => 'required|exists:users,id',
            'cdl_license'               => 'boolean',
            'max_gvwr'                  => 'nullable|numeric|min:0',
            'max_trailer_weight'        => 'nullable|numeric|min:0',
            'can_tow_equipment_trailer' => 'boolean',
            'can_tow_gooseneck'         => 'boolean',
            'can_operate_cdl_truck'     => 'boolean',
            'home_store_id'             => 'nullable|exists:stores,id',
            'skill_rating'              => 'required|integer|min:1|max:5',
            'notes'                     => 'nullable|string|max:1000',
            'is_active'                 => 'boolean',
        ]);

        $booleans = [
            'cdl_license', 'can_tow_equipment_trailer',
            'can_tow_gooseneck', 'can_operate_cdl_truck', 'is_active',
        ];
        foreach ($booleans as $key) {
            $validated[$key] = $request->boolean($key);
        }

        DispatchAiDriverCapability::updateOrCreate(
            ['user_id' => $validated['user_id']],
            $validated,
        );

        return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'drivers'])
            ->with('success', 'Driver capabilities saved.');
    }
}
