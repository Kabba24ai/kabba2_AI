<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Enums\WaitList\WaitListStatus;
use App\Http\Controllers\Controller;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * Wait list operations + Phase 1 reporting: status views, longest
     * waiting, most requested categories/equipment. Cancelled records stay
     * searchable here forever.
     */
    public function __invoke(Request $request)
    {
        $status = $request->input('status', 'waiting');

        $waitLists = EquipmentWaitList::query()
            ->with(['customer', 'category', 'store', 'items.equipment', 'convertedOrder'])
            ->when($status === 'waiting', fn ($q) => $q->waiting())
            ->when(in_array($status, ['active', 'acknowledged', 'converted', 'cancelled'], true),
                fn ($q) => $q->where('status', $status))
            ->when($request->filled('category_id'), fn ($q) => $q->where('product_category_id', $request->integer('category_id')))
            ->when($request->filled('equipment_id'), fn ($q) => $q->whereHas('items',
                fn ($i) => $i->where('equipment_id', $request->integer('equipment_id'))))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->input('search') . '%';
                $q->where(fn ($w) => $w->where('customer_name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->byUrgency()
            ->paginate(25)
            ->withQueryString();

        // Reporting: most requested categories and equipment across live demand
        $topCategories = EquipmentWaitList::waiting()
            ->whereNotNull('product_category_id')
            ->select('product_category_id', DB::raw('COUNT(*) as demand'))
            ->groupBy('product_category_id')
            ->orderByDesc('demand')->limit(5)
            ->with('category:id,title')->get();

        $topEquipment = DB::table('equipment_wait_list_items')
            ->join('equipment_wait_lists', 'equipment_wait_lists.id', '=', 'equipment_wait_list_items.equipment_wait_list_id')
            ->join('equipment', 'equipment.id', '=', 'equipment_wait_list_items.equipment_id')
            ->whereIn('equipment_wait_lists.status', WaitListStatus::waiting())
            ->select('equipment.id', 'equipment.equipment_name', DB::raw('COUNT(*) as demand'))
            ->groupBy('equipment.id', 'equipment.equipment_name')
            ->orderByDesc('demand')->limit(5)->get();

        $stats = [
            'waiting'     => EquipmentWaitList::waiting()->count(),
            'converted'   => EquipmentWaitList::where('status', WaitListStatus::Converted->value)->count(),
            'cancelled'   => EquipmentWaitList::where('status', WaitListStatus::Cancelled->value)->count(),
            'open_alerts' => EquipmentWaitListAlert::open()->count(),
            'oldest'      => EquipmentWaitList::waiting()->orderBy('created_at')->first(),
        ];

        return view('admin.wait_list.index', compact('waitLists', 'status', 'topCategories', 'topEquipment', 'stats'));
    }
}
