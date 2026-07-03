<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreateController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('admin.website_management.website_pages.create');
    }
}
