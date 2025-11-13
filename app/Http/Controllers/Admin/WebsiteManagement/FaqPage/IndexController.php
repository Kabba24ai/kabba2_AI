<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\FaqPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Locations\State;
use App\Models\WebsiteManagement\FaqPage\FaqCategory;
use App\Models\WebsiteManagement\FaqPage\FaqQuestions;



class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

        $categories = FaqCategory::orderBy('category_index_number')->get();
        $relatedCategory = FaqQuestions::orderBy('id')->get();

        $faq = FaqCategory::with(['questions' => function ($query) {
            $query->orderBy('id', 'asc');
        }])->orderBy('category_index_number', 'asc')->get();

        return view('admin.website_management.faq_page.index', compact('categories','relatedCategory'));
    }
}
