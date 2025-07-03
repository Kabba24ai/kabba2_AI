<?php

namespace App\Http\Controllers\Front\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $category_tree = ProductCategory::with('media')->whereNull('parent_id')->limit(8)->get();

        return view('front.home.index', [
            'title' => 'Home',
            'category_tree'=> $category_tree,
        ])->with('success', 'Something went wrong. Please try again.');
    }
}
