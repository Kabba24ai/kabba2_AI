<?php

namespace App\Http\Controllers\Front\Faqs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebsiteManagement\FaqPage\FaqCategory;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = (string) $request->input('q', '');

        // Eager load questions; if $search is provided, constrain questions to matches
        $categories = FaqCategory::with([
            'iconMedia',
            'questions' => function ($query) use ($search) {
                $query->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('question_name', 'LIKE', "%{$search}%")
                            ->orWhere('answer', 'LIKE', "%{$search}%");
                    });
                })
                    ->orderBy('id', 'asc');
            },
        ])
            ->orderBy('category_index_number', 'asc')
            ->get();

        // Collect matched unique_ids (only questions that are present in the eager-loaded collection)
        $matchedFaqIds = [];
        if ($search !== '') {
            foreach ($categories as $category) {
                foreach ($category->questions as $question) {
                    // Because we constrained questions to matches, these are matched items
                    $matchedFaqIds[] = $question->unique_id;
                }
            }
        }

        return view('front.faqs.index', [
            'title' => 'FAQs',
            'categories' => $categories,
            'search' => $search,
            'matchedFaqIds' => $matchedFaqIds,
        ]);
    }
}
