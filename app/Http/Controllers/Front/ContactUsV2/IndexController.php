<?php

namespace App\Http\Controllers\Front\ContactUsV2;

use App\Http\Controllers\Controller;
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

        return view('front.website_pages.contact_us.index', array_merge($context, [
            'currentPage' => $page,
        ]));
    }
}
