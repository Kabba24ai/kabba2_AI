<?php

namespace App\Http\Controllers\Admin\TermsAndCondition\Terms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\TermsAndCondition\Terms;

class CreateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $categories = Terms::order()->get();
        

        return view('admin.terms_and_condition.terms.create');
    }
}
