<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Locations\State;

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
        $states = State::orderBy('name', 'asc')->pluck('name', 'id')->prepend('Select State', '');
        return view('admin.stores.create', compact('states'));
    }
}
