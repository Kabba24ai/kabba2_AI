<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchIntelligenceRule;

class DestroyController extends Controller
{
    public function __invoke(DispatchIntelligenceRule $rule)
    {
        $rule->delete();

        return redirect()
            ->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'intelligence_rules'])
            ->with('success', 'Rule deleted.');
    }
}
