<?php

namespace App\Http\Controllers\Admin\Crm\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiRule;

class ApproveController extends Controller
{
    public function __invoke(AiRule $aiRule)
    {
        $aiRule->approved_by_admin = true;
        $aiRule->approved_by = auth()->id();
        $aiRule->approved_at = now();
        $aiRule->last_reviewed_at = now();
        $aiRule->pushChangeLog('approved', auth()->id());
        $aiRule->save();

        flash('AI rule approved. It is now a canonical governed rule.')->success();

        return redirect()->route('admin.crm.ai-rules.index');
    }
}
