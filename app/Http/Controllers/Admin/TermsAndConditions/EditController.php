<?php

namespace App\Http\Controllers\Admin\TermsAndConditions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\TermsAndConditions\Terms;

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

		return view('admin.terms_and_conditions.edit', [
            'terms' => $terms,
        ]);
    }
}
