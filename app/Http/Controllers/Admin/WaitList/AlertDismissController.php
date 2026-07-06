<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Http\Controllers\Controller;
use App\Models\WaitList\EquipmentWaitListAlert;

class AlertDismissController extends Controller
{
    public function __invoke(EquipmentWaitListAlert $alert)
    {
        $alert->dismiss();

        flash('Alert dismissed. It remains viewable in alert history.')->success();

        return redirect()->back();
    }
}
