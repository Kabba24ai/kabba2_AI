<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;

// Request
use App\Http\Requests\Admin\Stores\UpdateRequest;

// Models
use App\Models\Stores\Store;

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

        $store = Store::where('unique_id', $unique_id)->firstOrFail();
        // If is_primary is set to true/yes in the request
        if (isset($validatedData['is_primary']) && $validatedData['is_primary']) {
            // Remove primary flag from all other stores
            Store::where('id', '!=', $store->id)
                ->update(['is_primary' => 'No']);
        }

        $store->fill($validatedData);
        $store->save();


        flash('Store updated successfully.')->success();

        // Determine the redirection based on the button clicked
        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.stores.edit', ['unique_id' => $store->unique_id]),
            'save_new' => redirect()->route('admin.stores.create'),
            default => redirect()->route('admin.stores.index'),
        };

    }
}
