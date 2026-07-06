<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Enums\WaitList\WaitListCommunicationType;
use App\Http\Controllers\Controller;
use App\Models\WaitList\EquipmentWaitList;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationStoreController extends Controller
{
    public function __invoke(Request $request, EquipmentWaitList $waitList)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(WaitListCommunicationType::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $waitList->communications()->create([
            'user_id' => auth()->id(),
            'type'    => $validated['type'],
            'note'    => $validated['note'] ?? null,
        ]);

        flash('Communication logged.')->success();

        return redirect()->back();
    }
}
