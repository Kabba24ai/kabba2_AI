<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence;

use App\Enums\Dispatch\DispatchIntelligenceRuleType;
use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchIntelligenceRule;

class IndexController extends Controller
{
    public function __invoke()
    {
        $filterType   = request('rule_type');
        $filterStatus = request('status');

        $rules = DispatchIntelligenceRule::with(['approver:id,first_name,last_name', 'creator:id,first_name,last_name'])
            ->when($filterType, fn ($q) => $q->where('rule_type', $filterType))
            ->when($filterStatus === 'approved', fn ($q) => $q->where('approved_by_admin', true))
            ->when($filterStatus === 'pending',  fn ($q) => $q->where('approved_by_admin', false))
            ->orderByDesc('priority')
            ->orderBy('rule_type')
            ->get();

        $ruleTypes = DispatchIntelligenceRuleType::cases();

        return view('admin.order_management.dispatch.ai_rules.partials._intelligence_rules', compact(
            'rules',
            'ruleTypes',
            'filterType',
            'filterStatus',
        ));
    }
}
