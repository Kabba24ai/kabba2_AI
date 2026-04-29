<?php

namespace App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductEquipmentAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Request $request): View|string
    {
        $query = ProductEquipmentAssignment::query()
            ->with([
                'product',
                'baseCategory',
                'paths.items',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('product', function ($subQuery) use ($search) {
                $subQuery->where('product_name', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('base_product_category_id', (int) $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $assignments = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        if ($request->ajax()) {
            return view('admin.product_management.equipment_assignments.partials._table', [
                'assignments' => $assignments,
            ])->render();
        }

        $categories = ProductCategory::getHierarchy();

        return view('admin.product_management.equipment_assignments.index', [
            'assignments' => $assignments,
            'categories' => $categories,
        ]);
    }
}
