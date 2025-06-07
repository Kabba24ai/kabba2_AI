<?php

namespace App\Http\Controllers\Admin\TermsAndCondition\Terms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\TermsAndCondition\Terms;

class EditController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($unique_id, Request $request)
    {
		$terms = Terms::where('unique_id', $unique_id)->first();
		
		return view('admin.terms_and_condition.terms.edit', [
            'terms' => $terms,
        ]);
    }
}
