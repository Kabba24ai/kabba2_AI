<?php

namespace App\Http\Controllers\Front\Stores;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $store = Store::where('unique_id', $unique_id)
            ->active()
            ->with(['state', 'hoursOfOperation', 'page.image', 'page.ogImage'])
            ->firstOrFail();

        abort_unless($store->isPubliclyVisible(), 404);

        $page    = $store->page;
        $heading = $page?->page_heading ?: $store->store_name;

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $hoursMap = $store->hoursOfOperation->keyBy('day_name');

        // Shared Contact Strip (global component, homepage-owned content) —
        // the store page only toggles visibility. The strip's store cards
        // need the active store list, same as the contact page.
        $showContactStrip = $page?->show_contact_strip ?? true;
        $stores = $showContactStrip
            ? Store::with('state')->active()->orderByAdmin()
                ->get(['id', 'unique_id', 'store_name', 'address', 'city', 'zip_code',
                       'phone', 'details', 'latitude', 'longitude', 'state_id'])
            : collect();

        $storeImageUrl = $page?->image?->url;

        // SEO raw values — blanks fall back at render time (page_meta + SeoResolver):
        //   meta description: custom → intro text → generated location summary
        //   og image: custom → store image → site default OG image → logo
        $metaDescription = $page?->meta_description
            ?: $page?->intro_text
            ?: trim(sprintf(
                '%s — equipment rentals at %s.%s',
                $store->store_name,
                $store->full_address,
                $store->phone ? ' Call ' . $store->phone . ' for availability, hours, and directions.' : ''
            ));

        $seoRaw = [
            // Always resolved (custom → store name + site name) so og:title is never blank
            'meta_title'       => $page?->seo_title ?: $heading . ' - ' . config('app.name'),
            'meta_description' => $metaDescription,
            'og_title'         => $page?->og_title,
            'og_description'   => $page?->og_description,
            'og_image_url'     => $page?->ogImage?->url,
            'canonical_url'    => $page?->canonical_url ?: route('front.stores.show', $store->unique_id),
        ];

        return view('front.stores.show', [
            'title'            => $heading,
            'store'            => $store,
            'page'             => $page,
            'heading'          => $heading,
            'days'             => $days,
            'hoursMap'         => $hoursMap,
            'showContactStrip' => $showContactStrip,
            'stores'           => $stores,
            'storeImageUrl'    => $storeImageUrl,
            'seoRaw'           => $seoRaw,
        ]);
    }
}
