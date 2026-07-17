<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastWizard;

use App\Http\Controllers\Controller;
use App\Services\Crm\SmsAudienceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Step 4 recipient review. The counts come from the SAME canonical
 * resolver that later freezes the package — never a browser calculation.
 */
class PreviewAudienceController extends Controller
{
    public function __invoke(Request $request, SmsAudienceResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'audience_type'  => ['required', 'in:all,tags,saved'],
            'positive_mode'  => ['nullable', 'in:any,all'],
            'include_tags'   => ['nullable', 'array'],
            'include_tags.*' => ['integer'],
            'exclude_tags'   => ['nullable', 'array'],
            'exclude_tags.*' => ['integer'],
        ]);

        $resolution = $resolver->resolve([
            'base'    => $data['audience_type'] === 'all' ? 'all' : 'tags',
            'mode'    => $data['positive_mode'] ?? 'any',
            'include' => $data['include_tags'] ?? [],
            'exclude' => $data['exclude_tags'] ?? [],
        ]);

        return response()->json(['success' => true, 'stats' => $resolution['stats']]);
    }
}
