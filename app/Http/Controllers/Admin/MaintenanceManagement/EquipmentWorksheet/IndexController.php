<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentWorksheet;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\PartsList;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Equipment::query()->with(['productCategory', 'store']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = trim((string) $request->input('search'));
            $q->where(function ($sub) use ($search) {
                $sub->where('equipment_name', 'like', '%' . $search . '%')
                    ->orWhere('equipment_id', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%');
            });
        });

        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('product_category_id', $request->input('category'));
        });

        $query->when($request->filled('store'), function ($q) use ($request) {
            $q->where('store_id', $request->input('store'));
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('current_status', $request->input('status'));
        });

        $query->when($request->filled('direct_assignment'), function ($q) use ($request) {
            if ($request->input('direct_assignment') === 'assigned') {
                $q->whereNotNull('assigned_product_id');
            } elseif ($request->input('direct_assignment') === 'not_assigned') {
                $q->whereNull('assigned_product_id');
            }
        });

        $perPage = $request->input('per_page', 30);
        $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;

        $equipment = $query
            ->orderBy(ProductCategory::select('title')->whereColumn('product_categories.id', 'equipment.product_category_id'), 'asc')
            ->orderBy('equipment_name', 'asc')
            ->paginate($perPageVal)
            ->withQueryString();

        $categories = ProductCategory::getHierarchy();
        $stores = Store::active()->orderByAdmin()->pluck('store_name', 'id');
        $checklistMasters = ChecklistMaster::orderBy('checklist_system_name')->pluck('checklist_system_name', 'id');
        $partsLists = PartsList::orderBy('name')->pluck('name', 'id');
        $categoryIds = $equipment->getCollection()
            ->pluck('product_category_id')
            ->filter()
            ->unique()
            ->values();

        $productAssignmentOptionsByCategory = [];

        if ($categoryIds->isNotEmpty()) {
            $products = Product::query()
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('product_categories.id', $categoryIds);
                })
                ->with('categories')
                ->orderBy('product_name', 'asc')
                ->get(['id', 'product_name']);

            foreach ($products as $product) {
                foreach ($product->categories as $category) {
                    $categoryId = (int) $category->id;

                    if (!in_array($categoryId, $categoryIds->all(), true)) {
                        continue;
                    }

                    $productAssignmentOptionsByCategory[$categoryId][] = [
                        'id' => (int) $product->id,
                        'label' => (string) $product->product_name,
                    ];
                }
            }
        }

        if ($request->ajax()) {
            $html = view(
                'admin.maintenance_management.equipment_worksheet.partials._table',
                compact('equipment', 'categories', 'stores', 'checklistMasters', 'partsLists', 'productAssignmentOptionsByCategory')
            )->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        }

        return view(
            'admin.maintenance_management.equipment_worksheet.index',
            compact('equipment', 'categories', 'stores', 'checklistMasters', 'partsLists', 'productAssignmentOptionsByCategory')
        );
    }
}
