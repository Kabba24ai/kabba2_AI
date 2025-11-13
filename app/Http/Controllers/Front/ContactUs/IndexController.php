<?php

namespace App\Http\Controllers\Front\ContactUs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stores\Store;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $stores = Store::with('state')->active()->get();
        return view('front.contact_us.index',[
            'title'=> 'Contact Us',
            'stores' => $stores
        ]);
    }
}
