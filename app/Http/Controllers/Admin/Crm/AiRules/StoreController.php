<?php

namespace App\Http\Controllers\Admin\Crm\AiRules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\AiRules\StoreAiRuleRequest;
use App\Models\Ai\AiRule;

class StoreController extends Controller
{
    public function __invoke(StoreAiRuleRequest $request)
    {
        $rule = new AiRule($request->payload());
        $rule->version = 1;
        $rule->approved_by_admin = false;
        $rule->created_by = auth()->id();
        $rule->pushChangeLog('created', auth()->id());
        $rule->save();

        flash('AI rule created. It is pending approval before it is treated as canonical.')->success();

        return redirect()->route('admin.crm.ai-rules.index');
    }
}
