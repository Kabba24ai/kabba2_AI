<?php

namespace App\Http\Controllers\Admin\TermsAndCondition\Terms;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

// Request
use App\Http\Requests\Admin\TermsAndCondition\StoreRequest;

// Models
use App\Models\TermsAndCondition\Terms;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(StoreRequest $request)
    {
		// Use validated data
        $validatedData = $request->validated();

        // Normalize is_global to 'Yes' or 'No'
        //$validatedData['is_global'] = isset($validatedData['is_global']) ? 'Yes' : 'No';

        $objTerms = Terms::create($validatedData);

        
        flash('Terms created successfully.')->success();

        // Determine the redirection based on the button clicked
        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.terms-and-condition.terms.edit', ['unique_id' => $objTerms->unique_id]),
            'save_new' => redirect()->route('admin.terms-and-condition.terms.create'),
            default => back(), // fallback
        };
    }
}
