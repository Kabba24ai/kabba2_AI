<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Jobs\BuildDispatchDraftJob;
use App\Models\Dispatch\DispatchAiSettings;
use Illuminate\Http\Request;

class RunDraftController extends Controller
{
    public function __invoke(Request $request)
    {
        $settings = DispatchAiSettings::instance();

        if (!config('services.openai.api_key')) {
            return response()->json(['success' => false, 'message' => 'OpenAI API key is not configured.'], 422);
        }

        BuildDispatchDraftJob::dispatch(
            auth()->id(),
            'manual',
            (int) $settings->look_ahead_days,
        );

        return response()->json([
            'success' => true,
            'message' => 'AI draft generation started. The draft will appear on the Dispatch page shortly.',
        ]);
    }
}
