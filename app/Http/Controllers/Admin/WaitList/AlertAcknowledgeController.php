<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Http\Controllers\Controller;
use App\Models\WaitList\EquipmentWaitListAlert;

class AlertAcknowledgeController extends Controller
{
    public function __invoke(EquipmentWaitListAlert $alert)
    {
        $alert->acknowledge();

        flash('Alert acknowledged.')->success();

        return redirect()->back();
    }
}
