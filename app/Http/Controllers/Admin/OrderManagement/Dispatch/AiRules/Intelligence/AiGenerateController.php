<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence;

use App\Http\Controllers\Controller;
use App\Services\DispatchAI\DispatchIntelligenceRuleGeneratorService;

class AiGenerateController extends Controller
{
    public function __invoke(DispatchIntelligenceRuleGeneratorService $generator)
    {
        try {
            $count = $generator->generate(auth()->id());

            return response()->json([
                'success' => true,
                'count'   => $count,
                'message' => "Generated {$count} intelligence rules. Review and approve before they affect AI drafts.",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
