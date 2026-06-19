<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence;

use App\Enums\Dispatch\DispatchIntelligenceRuleType;
use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchIntelligenceRule;
use Illuminate\Validation\Rules\Enum;

class UpdateController extends Controller
{
    public function __invoke(DispatchIntelligenceRule $rule)
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

        // Editing resets approval — must be re-reviewed
        if ($rule->approved_by_admin) {
            $data['approved_by_admin'] = false;
            $data['approved_by']       = null;
            $data['approved_at']       = null;
        }

        $data['updated_by']       = auth()->id();
        $data['last_reviewed_at'] = now();

        $rule->update($data);

        return redirect()
            ->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'intelligence_rules'])
            ->with('success', 'Rule updated. Approval reset — please re-approve before it affects AI drafts.');
    }
}
