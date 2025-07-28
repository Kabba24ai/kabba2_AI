<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use App\Models\Locations\State;
use Illuminate\Http\Request;

// Models
use App\Models\Stores\Store;

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
		$store = Store::where('unique_id', $unique_id)->firstOrFail();
        $states = State::orderBy('name', 'asc')->pluck('name', 'id')->prepend('Select State', '');
		return view('admin.stores.edit', [
            'store' => $store,
            'states' => $states,
        ]);
    }
}
