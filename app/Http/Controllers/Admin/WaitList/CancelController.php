<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Http\Controllers\Controller;
use App\Models\WaitList\EquipmentWaitList;

class CancelController extends Controller
{
    /** Manual cancellation only — the record remains searchable. */
    public function __invoke(EquipmentWaitList $waitList)
    {
        $waitList->cancel();

        flash('Wait list cancelled. The record remains searchable in history.')->success();

        return redirect()->route('admin.wait-list.show', $waitList);
    }
}
