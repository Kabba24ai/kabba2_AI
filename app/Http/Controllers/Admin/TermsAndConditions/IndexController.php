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

        $perPage = $request->input('per_page', 10);
        $perPageVal = $perPage === 'all' ? max(1, Terms::count()) : (int) $perPage;

        $terms = Terms::query();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $terms->where(function ($query) use ($searchTerm) {
                $query->where('title', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($request->filled('is_global')) {
            $isGlobal = $request->input('is_global');
            $terms->where('is_global', $isGlobal);
        }


        $terms = $terms->orderBy('is_global','ASC')->paginate($perPageVal)->withQueryString();

        return view('admin.terms_and_conditions.index', [
            'terms' => $terms,
        ]);
    }
}
