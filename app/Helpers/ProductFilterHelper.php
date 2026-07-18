<?php

namespace App\Helpers;

use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductCategoryChild;

/**
 * Canonical data + validation for the dependent Category → Product filters
 * on the Orders and Schedule pages.
 *
 * The category-to-product relationship is the product_category_children
 * pivot (Product::categories()) — the same relation both pages' queries
 * already filter on — never inferred from names or display values.
 */
class ProductFilterHelper
{
    /**
     * Eligible products for the filter dropdowns, name-ordered.
     * Mirrors the Orders page's long-standing eligibility: every product,
     * regardless of status or type (historical orders reference them all).
     */
    public static function productOptions()
    {
        return Product::order()->pluck('product_name', 'id');
    }

    /** The same list shaped for JS ([{id, name}, ...] — preserves name order). */
    public static function productOptionsForJs(): array
    {
        return self::productOptions()
            ->map(fn ($name, $id) => ['id' => $id, 'name' => $name])
            ->values()
            ->all();
    }

    /**
     * category_id => [product_id, ...] from the canonical pivot.
     * Exact-id membership only — matching the backend queries, a parent
     * category does not inherit its children's products.
     */
    public static function categoryProductMap(): array
    {
        return ProductCategoryChild::query()
            ->whereNotNull('product_id')
            ->get(['product_id', 'product_category_id'])
            ->groupBy('product_category_id')
            ->map(fn ($rows) => $rows->pluck('product_id')->unique()->values()->all())
            ->all();
    }

    /**
     * Server-side validation of the category filter value.
     * Unknown / stale ids are dropped rather than silently matching nothing.
     */
    public static function normalizeCategoryId($category): ?int
    {
        if (!is_numeric($category)) {
            return null;
        }

        return ProductCategory::whereKey((int) $category)->exists() ? (int) $category : null;
    }

    /**
     * Server-side validation of the product filter value, independent of the
     * client-side dropdown reduction. A product that does not exist — or does
     * not belong to the (already normalized) selected category — is dropped,
     * so a stale combination degrades to the wider filter instead of applying
     * a contradictory hidden one.
     */
    public static function normalizeProductId($product, ?int $categoryId): ?int
    {
        if (!is_numeric($product)) {
            return null;
        }

        $query = Product::whereKey((int) $product);

        if ($categoryId !== null) {
            $query->whereHas('categories', fn ($q) => $q->where('product_categories.id', $categoryId));
        }

        return $query->exists() ? (int) $product : null;
    }
}
