<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiTrailer;

class DeleteTrailerController extends Controller
{
    public function __invoke(int $id)
    {
        DispatchAiTrailer::findOrFail($id)->delete();

        return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'trailers'])
            ->with('success', 'Trailer removed.');
    }
}
