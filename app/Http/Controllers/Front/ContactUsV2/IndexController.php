<?php

namespace App\Http\Controllers\Front\ContactUsV2;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\WebsitePageBuilderService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(private WebsitePageBuilderService $builder) {}

    public function __invoke(Request $request)
    {
        $page = WebsitePage::where('page_key', 'contact_v2')->firstOrFail();

        $context = $this->builder->getBuilderContext($page);

        // Builder only loads stores when a locations/contact_strip section exists in DB.
        // Always ensure stores are available on the contact page regardless.
        if ($context['stores']->isEmpty()) {
            $context['stores'] = Store::with(['hoursOfOperation', 'state'])
                ->active()
                ->orderByAdmin()
                ->get(['id', 'unique_id', 'store_name', 'address', 'city', 'zip_code',
                       'phone', 'details', 'latitude', 'longitude', 'state_id']);
        }

        return view('front.website_pages.contact_us.index', array_merge($context, [
            'currentPage' => $page,
            'logo'        => \App\Helpers\ConfigurationHelper::getHpBuilderLogo(),
            'favicon'     => \App\Helpers\ConfigurationHelper::getHpBuilderFavicon(),
        ]));
    }
}
