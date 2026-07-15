<?php

namespace App\Http\Controllers\Admin\TermsAndConditions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models
use App\Models\Configurations\Setting;
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
        if($request->ajax()){
            $perPage = $request->input('per_page', 30);
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
            $html = view('admin.terms_and_conditions.partials._table', compact('terms'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        }

        // Rental Agreement Header lines — same settings rows the front
        // signing page reads via CommonFrontDataHelper::brandingSettings()
        $headerSettings = Setting::where('setting_type', 'Website Management Branding')
            ->whereIn('setting_name', [
                'terms_condition_text_1',
                'terms_condition_text_2',
                'terms_condition_text_3',
            ])
            ->pluck('setting_value', 'setting_name')
            ->toArray();

        return view('admin.terms_and_conditions.index', compact('headerSettings'));
    }
}
