<?php

namespace App\Http\Controllers\Admin\TermsAndConditions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Models
use App\Models\TermsAndConditions\Terms;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($unique_id, Request $request)
    {
        $objProductCategory = Terms::where('unique_id', $unique_id)->firstOrFail();
        $objProductCategory->delete();

        flash('Terms deleted successfully.')->success();
        return redirect()->route('admin.terms-and-conditions.index');
    }
}
