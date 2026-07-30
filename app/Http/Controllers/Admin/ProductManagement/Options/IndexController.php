<?php

namespace App\Http\Controllers\Admin\ProductManagement\Options;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductOption;

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
        $search = $request->input('search');

        $options = ProductOption::withCount('items')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('label', 'like', "%{$search}%"));
            }))
            ->oldest('name')
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return view('admin.product_management.options.partials._rows', [
                'options' => $options,
            ]);
        }

        $totalOptions = ProductOption::count();
        $activeOptions = ProductOption::active()->count();
        $inactiveOptions = ProductOption::inActive()->count();

        return view('admin.product_management.options.index', [
            'options' => $options,
            'totalOptions' => $totalOptions,
            'activeOptions' => $activeOptions,
            'inactiveOptions' => $inactiveOptions,
        ]);

    }
}
