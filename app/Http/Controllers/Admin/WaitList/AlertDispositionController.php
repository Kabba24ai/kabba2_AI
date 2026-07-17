<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Enums\WaitList\WaitListAlertDisposition;
use App\Http\Controllers\Controller;
use App\Models\WaitList\EquipmentWaitListAlert;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlertDispositionController extends Controller
{
    /**
     * Record the outcome of working a match alert. Resolving one match
     * never closes the customer's overall request except an explicit
     * Customer No Longer Needs Equipment (see WaitListAlertDisposition).
     */
    public function __invoke(Request $request, EquipmentWaitListAlert $alert)
    {
        $validated = $request->validate([
            'disposition' => ['required', Rule::enum(WaitListAlertDisposition::class)],
        ]);

        $disposition = WaitListAlertDisposition::from($validated['disposition']);

        $alert->dispose($disposition);

        flash('Match recorded as "' . $disposition->label() . '".')->success();

        return redirect()->back();
    }
}
