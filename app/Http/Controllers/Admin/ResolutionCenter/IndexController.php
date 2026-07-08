<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Models\Customers\ResolutionCase;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 *
 * The audit history list — every case already carries its full record
 * (User, Date, Customer, Order, Recommendations shown, Decision selected,
 * Manager override, Notes, Outcome), so this is a plain read of the table,
 * no separate audit log to query.
 */
class IndexController extends Controller
{
    public function __invoke()
    {
        $cases = ResolutionCase::with(['customer', 'order', 'responsiblePerson', 'managerOverrideUser'])
            ->latest('id')
            ->paginate(25);

        return view('admin.resolution_center.index', compact('cases'));
    }
}
