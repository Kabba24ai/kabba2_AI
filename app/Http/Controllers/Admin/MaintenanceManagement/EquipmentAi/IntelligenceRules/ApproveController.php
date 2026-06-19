<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\IntelligenceRules;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentIntelligenceRule;
use Illuminate\Http\Request;

class ApproveController extends Controller
{
    public function __invoke(Request $request, EquipmentIntelligenceRule $rule)
    {
        $rule->update([
            'approved_by_admin' => true,
            'approved_by'       => auth()->id(),
            'approved_at'       => now(),
            'last_reviewed_at'  => now(),
            'updated_by'        => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Rule approved.']);
        }

        return back()->with('success', "Rule \"{$rule->rule_name}\" approved.");
    }
}
