<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;

class CreateController extends Controller
{
    public function __invoke()
    {
        // Structured selections only — CRM customers, categories, products, stores
        $customers = Customer::where('status', 'Active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'company_name', 'phone', 'email']);

        $categories = ProductCategory::published()->sortOrder()->get(['id', 'title']);
        $stores     = Store::where('status', 'Active')->orderBy('store_name')->get(['id', 'store_name']);

        // Rental products with their category memberships; the form filters
        // this list client-side as the category changes. Employees select
        // acceptable PRODUCT TYPES here — individual inventory units are
        // evaluated later when a return triggers matching.
        $productOptions = Product::where('product_type', 'Rental')
            ->with('categories:product_categories.id')
            ->orderBy('product_name')
            ->get(['id', 'product_name'])
            ->map(fn ($product) => [
                'id'           => $product->id,
                'name'         => $product->product_name,
                'category_ids' => $product->categories->pluck('id')->values(),
            ])
            ->values();

        return view('admin.wait_list.create', compact('customers', 'categories', 'stores', 'productOptions'));
    }
}
