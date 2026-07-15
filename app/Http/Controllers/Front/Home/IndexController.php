<?php

namespace App\Http\Controllers\Front\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Configurations\Setting;
use App\Models\Stores\Store;
use App\Services\Website\HomePageService;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $hp = app(HomePageService::class)->getData();

        $branding = Setting::where('setting_type', 'Website Management Branding')
            ->pluck('setting_value', 'setting_name')
            ->toArray();

        $stores = Store::active()
            ->with('state')
            ->orderByAdmin()
            ->get();

        // Load only categories selected in the builder, in builder display order
        $featuredCategories = collect();
        if ($hp->featuredRentals->categoryIds->isNotEmpty()) {
            $indexed = ProductCategory::has('products')
                ->published()
                ->with('media')
                ->whereNull('parent_id')
                ->whereIn('id', $hp->featuredRentals->categoryIds)
                ->get()
                ->keyBy('id');

            $featuredCategories = $hp->featuredRentals->categoryIds
                ->map(fn ($id) => $indexed->get($id))
                ->filter()
                ->values();
        }

        return view('front.home.index', [
            'title'              => 'Home',
            'hp'                 => $hp,
            'branding'           => $branding,
            'stores'             => $stores,
            'featuredCategories' => $featuredCategories,
            // Canonical branding assets (Website Management → Branding);
            // legacy hp_builder slots only as fallback for unmigrated data
            'logo'               => \App\Helpers\ConfigurationHelper::getBrandingLogo()
                                        ?: \App\Helpers\ConfigurationHelper::getHpBuilderLogo(),
            'favicon'            => \App\Helpers\ConfigurationHelper::getBrandingFavicon()
                                        ?: \App\Helpers\ConfigurationHelper::getHpBuilderFavicon(),
        ]);
    }
}
