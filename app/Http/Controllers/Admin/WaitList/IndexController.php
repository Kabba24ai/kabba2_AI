<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Enums\WaitList\WaitListStatus;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * Wait list operations dashboard: stat cards, filterable grid/list of
     * records, and Phase 1 reporting (top categories/equipment, longest
     * waiting). Cancelled records stay searchable here forever.
     */
    public function __invoke(Request $request)
    {
        $statuses = $this->requestedStatuses($request);

        $waitLists = EquipmentWaitList::query()
            ->with(['customer', 'category.media', 'store', 'items.equipment.documentImages.media', 'items.equipment.assignedProduct.media', 'convertedOrder'])
            ->withCount(['alerts as open_alerts_count' => fn ($q) => $q->open()])
            ->when($statuses !== null, fn ($q) => $q->whereIn('status', $statuses))
            ->when($request->filled('category_id'), fn ($q) => $q->where('product_category_id', $request->integer('category_id')))
            ->when($request->filled('equipment_id'), fn ($q) => $q->whereHas('items',
                fn ($i) => $i->where('equipment_id', $request->integer('equipment_id'))))
            ->when($request->filled('store'), function ($q) use ($request) {
                $request->input('store') === 'any_store'
                    ? $q->where('store_preference', 'any_store')
                    : $q->where('store_id', (int) $request->input('store'));
            })
            ->when($request->filled('age'), fn ($q) => $this->applyAgeFilter($q, $request->input('age')))
            ->when($request->filled('priority'), function ($q) use ($request) {
                $request->input('priority') === 'none'
                    ? $q->whereNull('priority_override')
                    : $q->where('priority_override', (int) $request->input('priority'));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->input('search') . '%';
                $q->where(fn ($w) => $w->where('customer_name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->when($request->input('sort') === 'newest',
                fn ($q) => $q->orderByDesc('created_at'),
                fn ($q) => $q->byUrgency())
            ->paginate($this->perPage($request))
            ->withQueryString();

        // Queue position per waiting record (#1, #2, …) under byUrgency order —
        // computed for display only, independent of the filters on screen
        $queuePositions = EquipmentWaitList::waiting()->byUrgency()->pluck('id')
            ->flip()->map(fn ($index) => $index + 1)->all();

        // Reporting: most requested categories and equipment across live demand
        $topCategories = EquipmentWaitList::waiting()
            ->whereNotNull('product_category_id')
            ->select('product_category_id', DB::raw('COUNT(*) as demand'))
            ->groupBy('product_category_id')
            ->orderByDesc('demand')->limit(3)
            ->with('category:id,title')->get();

        $topEquipment = DB::table('equipment_wait_list_items')
            ->join('equipment_wait_lists', 'equipment_wait_lists.id', '=', 'equipment_wait_list_items.equipment_wait_list_id')
            ->join('equipment', 'equipment.id', '=', 'equipment_wait_list_items.equipment_id')
            ->whereIn('equipment_wait_lists.status', WaitListStatus::waiting())
            ->select('equipment.id', 'equipment.equipment_name', DB::raw('COUNT(*) as demand'))
            ->groupBy('equipment.id', 'equipment.equipment_name')
            ->orderByDesc('demand')->limit(3)->get();

        $converted30 = EquipmentWaitList::where('status', WaitListStatus::Converted->value)
            ->where('converted_at', '>=', now()->subDays(30))->count();
        $created30 = EquipmentWaitList::where('created_at', '>=', now()->subDays(30))->count();

        $stats = [
            'waiting'         => EquipmentWaitList::waiting()->count(),
            'oldest'          => EquipmentWaitList::waiting()->orderBy('created_at')->first(),
            'converted_30'    => $converted30,
            'conversion_rate' => $created30 > 0 ? (int) round($converted30 / $created30 * 100) : null,
            'open_alerts'     => EquipmentWaitListAlert::open()->count(),
        ];

        // Filter dropdown sources
        $categories      = ProductCategory::published()->sortOrder()->get(['id', 'title']);
        $stores          = Store::where('status', 'Active')->orderBy('store_name')->get(['id', 'store_name']);
        $filterEquipment = Equipment::whereIn('id', DB::table('equipment_wait_list_items')->distinct()->pluck('equipment_id'))
            ->orderBy('equipment_name')->get(['id', 'equipment_name']);

        $view = $request->input('view') === 'list' ? 'list' : 'grid';

        return view('admin.wait_list.index', [
            'waitLists'       => $waitLists,
            'queuePositions'  => $queuePositions,
            'selectedStatuses'=> $statuses ?? [],
            'statusParam'     => $request->input('status', 'waiting'),
            'topCategories'   => $topCategories,
            'topEquipment'    => $topEquipment,
            'stats'           => $stats,
            'categories'      => $categories,
            'stores'          => $stores,
            'filterEquipment' => $filterEquipment,
            'view'            => $view,
        ]);
    }

    /**
     * Status filter accepts a single value, a comma list, or status[] inputs.
     * 'waiting' expands to Active + Acknowledged; 'all' (or selecting
     * everything) disables the status constraint. Default: waiting.
     */
    private function requestedStatuses(Request $request): ?array
    {
        $raw = $request->input('status', 'waiting');
        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        $statuses = [];
        foreach ($values as $value) {
            $value = trim($value);
            if ($value === 'all') {
                return null;
            }
            if ($value === 'waiting') {
                $statuses = array_merge($statuses, WaitListStatus::waiting());
            } elseif (in_array($value, ['active', 'acknowledged', 'converted', 'cancelled'], true)) {
                $statuses[] = $value;
            }
        }

        $statuses = array_values(array_unique($statuses));

        return $statuses === [] ? WaitListStatus::waiting() : $statuses;
    }

    private function applyAgeFilter($query, string $bucket)
    {
        return match ($bucket) {
            '0-1'   => $query->where('created_at', '>=', now()->startOfDay()->subDay()),
            '2-7'   => $query->where('created_at', '<', now()->startOfDay()->subDay())
                             ->where('created_at', '>=', now()->startOfDay()->subDays(7)),
            '8+'    => $query->where('created_at', '<', now()->startOfDay()->subDays(7)),
            default => $query,
        };
    }

    private function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 12);

        return in_array($perPage, [12, 24, 48], true) ? $perPage : 12;
    }
}
