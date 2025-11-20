<?php

namespace App\Http\Controllers\Admin\TermsAndConditions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models
use App\Models\TermsAndConditions\Terms;

class GlobalTermsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

 // Get the first global term
        $term = Terms::where('is_global', 'Yes')->orderBy('id', 'ASC')->first();

       
            // Redirect to the edit page and add a query parameter
            return redirect()->route('admin.terms-and-conditions.edit', [
                'unique_id' => $term->unique_id,
                'global' => true 
            ]);
        

    }
}
