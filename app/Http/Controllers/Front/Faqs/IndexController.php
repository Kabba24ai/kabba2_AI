<?php

namespace App\Http\Controllers\Front\Faqs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return view('front.faqs.index', ['title' => 'Frequently Asked Questions']);
    }
}
