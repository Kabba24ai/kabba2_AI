<?php

namespace App\Http\Controllers\Front\HomeV2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Configurations\Setting;
use App\Models\Stores\Store;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $category_tree = ProductCategory::has('products')
            ->published()
            ->with('media')
            ->whereNull('parent_id')
            ->sortOrder()
            ->get();

        $branding = Setting::where('setting_type', 'Website Management Branding')
            ->pluck('setting_value', 'setting_name')
            ->toArray();

        $stores = Store::active()
            ->with('state')
            ->orderByAdmin()
            ->take(2)
            ->get();

        return view('front.home_v2.index', [
            'title'         => 'Home V2',
            'category_tree' => $category_tree,
            'branding'      => $branding,
            'stores'        => $stores,
        ]);
    }
}
