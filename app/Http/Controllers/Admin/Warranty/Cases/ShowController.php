<?php

namespace App\Http\Controllers\Admin\Warranty\Cases;

use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;

/** The Warranty Case page — Phase 1 shell of the Warranty Workbench. */
class ShowController extends Controller
{
    public function __invoke(WarrantyCase $case)
    {
        $case->load([
            'customer',
            'equipment',
            'serviceTicket',
            'createdBy',
            'feeWaivedBy',
            'events.user',
        ]);

        return view('admin.warranty.show', ['case' => $case]);
    }
}
