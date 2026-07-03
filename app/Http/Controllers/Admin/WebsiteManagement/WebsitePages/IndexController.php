<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $pages = WebsitePage::orderBy('created_at', 'asc')->get();

        return view('admin.website_management.website_pages.index', compact('pages'));
    }
}
