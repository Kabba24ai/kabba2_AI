<?php

namespace App\Http\Controllers\Admin\Crm\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiRule;

class IndexController extends Controller
{
    public function __invoke()
    {
        $rules = AiRule::with(['approver', 'creator', 'updater'])
            ->orderByDesc('id')
            ->get();

        return view('admin.crm.ai_rules.index', compact('rules'));
    }
}
