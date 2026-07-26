<?php

namespace App\Http\Controllers\Admin\Crm\AiRules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\AiRules\UpdateAiRuleRequest;
use App\Models\Ai\AiRule;

class UpdateController extends Controller
{
    public function __invoke(UpdateAiRuleRequest $request, AiRule $aiRule)
    {
        $aiRule->fill($request->payload());

        // Anti-drift: editing an approved rule resets its approval and bumps the
        // version so a changed rule is never silently treated as still-approved.
        $aiRule->approved_by_admin = false;
        $aiRule->approved_by = null;
        $aiRule->approved_at = null;
        $aiRule->updated_by = auth()->id();
        $aiRule->last_reviewed_at = now();
        $aiRule->version = (int) $aiRule->version + 1;
        $aiRule->pushChangeLog('updated', auth()->id());
        $aiRule->save();

        flash('AI rule updated. Approval was reset — re-approve to make it canonical again.')->success();

        return redirect()->route('admin.crm.ai-rules.index');
    }
}
