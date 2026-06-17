<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiSettings;
use App\Services\DispatchAI\DispatchAIService;
use Illuminate\Http\Request;

class SavePolicyController extends Controller
{
    public function __invoke(Request $request)
    {
        $settings = DispatchAiSettings::instance();

        if ($request->boolean('reset_policy')) {
            $settings->update(['policy_overrides' => null]);
            return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'policy'])
                ->with('success', 'Policy reset to defaults.');
        }

        $raw      = $request->input('policy_overrides', []);
        $defaults = DispatchAIService::defaultPolicy();
        $overrides = [];

        foreach ($defaults as $key => $defaultRule) {
            if ($key === 'HARD_CONSTRAINTS') {
                foreach (['driver_lock', 'priority_lock', 'no_double_book', 'fabrication'] as $sub) {
                    $value = trim($raw[$key][$sub] ?? '');
                    if ($value !== '' && $value !== ($defaultRule[$sub] ?? '')) {
                        $overrides[$key][$sub] = $value;
                    }
                }
            } else {
                $value = trim($raw[$key]['detail'] ?? '');
                if ($value !== '' && $value !== ($defaultRule['detail'] ?? '')) {
                    $overrides[$key]['detail'] = $value;
                }
            }
        }

        $settings->update(['policy_overrides' => empty($overrides) ? null : $overrides]);

        return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'policy'])
            ->with('success', 'AI policy saved.');
    }
}
