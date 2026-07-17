<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Http\Controllers\Controller;
use App\Models\WaitList\EquipmentWaitListAlert;
use Illuminate\Http\Request;

class AlertsController extends Controller
{
    /** High-priority internal alert feed; acknowledged/dismissed stay in history. */
    public function __invoke(Request $request)
    {
        $view = $request->input('view', 'open');

        $alerts = EquipmentWaitListAlert::query()
            ->with(['waitList.customer', 'waitList.category', 'waitList.store', 'waitList.items.equipment',
                'waitList.selectedProducts', 'equipment.store', 'matchedCategory', 'matchedProduct', 'acknowledgedBy'])
            ->when($view === 'open', fn ($q) => $q->open())
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.wait_list.alerts', compact('alerts', 'view'));
    }
}
