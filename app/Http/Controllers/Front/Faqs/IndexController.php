<?php

namespace App\Http\Controllers\Front\Faqs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebsiteManagement\FaqPage\FaqCategory;
use App\Models\WebsiteManagement\FaqPage\FaqQuestions;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $search = $request->input('q');

        // Fetch all categories with their related questions
        $categories = FaqCategory::with(['questions' => function ($query) use ($search) {
            $query->when($search, function ($q) use ($search) {
                $q->where('question_name', 'LIKE', "%{$search}%")
                  ->orWhere('answer', 'LIKE', "%{$search}%");
            })
            ->orderBy('id', 'asc');
        }])
        ->orderBy('category_index_number', 'asc')
        ->get();
        return view('front.faqs.index', [
            'title' => 'Frequently Asked Questions',
            'categories' => $categories,
            'search' => $search
        ]);
        //return view('front.faqs.index', ['title' => 'Frequently Asked Questions']);
    }
}
