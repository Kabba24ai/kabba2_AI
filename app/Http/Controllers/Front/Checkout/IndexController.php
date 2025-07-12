<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Locations\State;
use Illuminate\Http\Request;

use App\Helpers\ConfigurationHelper;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $states = State::select('id', 'name')->pluck('name', 'id');

        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');

        return view('front.checkout.index',[
            'title'=> 'Checkout',
            'states'=> $states,
            'paymentSetting' => $paymentSetting
        ]);
    }
}
