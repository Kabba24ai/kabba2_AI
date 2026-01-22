<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Stores\Store;

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
        $objRecord = Store::where('unique_id', $unique_id)->firstOrFail();
        $objRecord->delete();

        flash('Store deleted successfully.')->success();
        return redirect()->route('admin.stores.index');
    }
}
