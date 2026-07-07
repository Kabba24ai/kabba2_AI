<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\AnswerRequest;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

class AnswerController extends Controller
{
    public function __invoke(string $uniqueId, AnswerRequest $request)
    {
        $validated = $request->validated();

        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            // Phase 3.5: recordAnswers() now takes a generic array — this
            // controller still only ever sends Cancellation/Refund's own
            // three answer keys, so its behavior is unchanged.
            ResolutionCenterService::recordAnswers($case->id, [
                'can_reschedule' => (bool) $validated['can_reschedule'],
                'credit_would_satisfy' => array_key_exists('credit_would_satisfy', $validated) ? (bool) $validated['credit_would_satisfy'] : null,
                'payment_method' => $validated['payment_method'] ?? null,
            ]);

            flash('Answers recorded.')->success();

            return redirect()->route('admin.resolution-center.show', $uniqueId);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center answer error for case '.$uniqueId.': '.$e->getMessage());

            flash('Something went wrong while recording the answer.')->error();

            return redirect()->back()->withInput();
        }
    }
}
