<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence;

use App\Enums\Dispatch\DispatchIntelligenceRuleType;
use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchIntelligenceRule;
use Illuminate\Validation\Rules\Enum;

class StoreController extends Controller
{
    public function __invoke()
    {
        $data = request()->validate([
            'rule_type'        => ['required', new Enum(DispatchIntelligenceRuleType::class)],
            'rule_name'        => ['required', 'string', 'max:255'],
            'condition'        => ['required', 'string'],
            'recommendation'   => ['required', 'string'],
            'reason'           => ['required', 'string'],
            'priority'         => ['required', 'integer', 'min:1', 'max:100'],
            'confidence_score' => ['required', 'numeric', 'min:0', 'max:1'],
            'source_type'      => ['required', 'string'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['string'],
        ]);

        DispatchIntelligenceRule::create([
            ...$data,
            'approved_by_admin' => false,
            'is_active'         => true,
            'created_by'        => auth()->id(),
        ]);

        return redirect()
            ->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'intelligence_rules'])
            ->with('success', 'Rule created. It will appear in AI drafts once approved.');
    }
}
