<?php

namespace App\Http\Controllers\Admin\TermsAndCondition\Terms;

use App\Http\Controllers\Controller;

// Request
use App\Http\Requests\Admin\TermsAndCondition\UpdateRequest;

// Helpers
use App\Helpers\MediaHelper;

// Models
use App\Models\TermsAndCondition\Terms;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($unique_id, UpdateRequest $request)
    {

        $validatedData = $request->validated();

        $objTerms = Terms::where('unique_id', $unique_id)->firstOrFail();
        $objTerms->fill($validatedData);

        $objTerms->save();

        flash('Terms updated successfully.')->success();

        // Determine the redirection based on the button clicked
        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.terms-and-condition.terms.edit', ['unique_id' => $objTerms->unique_id]),
            'save_new' => redirect()->route('admin.terms-and-condition.terms.create'),
            default => back(), // fallback
        };

    }
}
