<?php

namespace App\Http\Controllers\Front\HomeV2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('front.home_v2.index', [
            'title' => 'Home V2',
        ]);
    }
}
