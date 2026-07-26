<?php

namespace App\Http\Controllers\Admin\Crm\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiRule;

class DestroyController extends Controller
{
    public function __invoke(AiRule $aiRule)
    {
        $aiRule->delete(); // soft delete — history preserved

        flash('AI rule removed.')->success();

        return redirect()->route('admin.crm.ai-rules.index');
    }
}
