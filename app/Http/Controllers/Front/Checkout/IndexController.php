<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Locations\State;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $states = State::select('id', 'name')->pluck('name', 'id');

        return view('front.checkout.index',[
            'title'=> 'Checkout',
            'states'=> $states
        ]);
    }
}
