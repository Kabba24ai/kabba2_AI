<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchIntelligenceRule;

class ApproveController extends Controller
{
    public function __invoke(DispatchIntelligenceRule $rule)
    {
        $rule->update([
            'approved_by_admin' => true,
            'approved_by'       => auth()->id(),
            'approved_at'       => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Rule approved.']);
    }
}
