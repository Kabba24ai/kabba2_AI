<?php

namespace App\Http\Controllers\Admin\TermsAndConditions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models
use App\Models\TermsAndConditions\Terms;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Create an empty collection
        $items = Terms::orderBy('is_global','ASC')->get();

        // Set pagination parameters
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        // Slice the empty collection (though it's empty)
        $currentItems = $items->slice($offset, $perPage)->values();

        // Create paginator
        $terms = new LengthAwarePaginator($currentItems, $items->count(), $perPage, $currentPage, ['path' => request()->url(), 'query' => request()->query()]);

        $categories = Collection::make([]);
        return view('admin.terms_and_conditions.terms.index', [
            'terms' => $terms,
        ]);
    }
}
