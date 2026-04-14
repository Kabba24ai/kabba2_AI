<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\ProductCategory;

class SubcategorySearchController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(?string $search = null)
    {
        $search = trim((string) $search);

        $subcategories = ProductCategory::query()
            ->when($search !== '', fn($qb) => $qb->where('title', 'like', "%{$search}%"))
            ->with('parentCategory:id,title')
            ->orderBy('sort_order', 'asc')
            ->limit(20)
            ->get()
            ->map(fn($subcategory) => [
                'id'           => $subcategory->id,
                'title'        => $subcategory->title,
                'image_url'    => $subcategory->image_url,
                'parent_title' => $subcategory->parentCategory->title ?? null,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'subcategories' => $subcategories,
        ]);
    }
}
