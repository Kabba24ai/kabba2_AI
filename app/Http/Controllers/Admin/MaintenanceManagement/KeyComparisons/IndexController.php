<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $criteriaCountsByCategory = EquipmentCriticalMatchingCriterion::query()
            ->where('is_active', true)
            ->where('is_key_criteria', true)
            ->selectRaw('product_category_id, COUNT(*) as total')
            ->groupBy('product_category_id')
            ->pluck('total', 'product_category_id');

        $categoryHierarchy = ProductCategory::getHierarchy();
        $categories = ProductCategory::query()
            ->select(['id', 'title'])
            ->orderBy('title', 'asc')
            ->get()
            ->map(function ($category) use ($criteriaCountsByCategory, $categoryHierarchy) {
                return [
                    'id' => (int) $category->id,
                    'title' => (string) ($categoryHierarchy[$category->id] ?? $category->title),
                    'criteria_count' => (int) ($criteriaCountsByCategory[$category->id] ?? 0),
                ];
            })
            ->values();

        $activeCategoryId = (int) $request->query('category_id', 0);
        if ($activeCategoryId <= 0 || !$categories->contains(fn ($item) => (int) $item['id'] === $activeCategoryId)) {
            $activeCategoryId = (int) ($categories->first()['id'] ?? 0);
        }

        return view('admin.maintenance_management.key_comparisons.index', [
            'categories' => $categories->toArray(),
            'activeCategoryId' => $activeCategoryId,
        ]);
    }
}
