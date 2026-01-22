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
        $options = ProductOption::withCount('items')->oldest('name')->get();
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
