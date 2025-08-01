<?php

namespace App\Http\Controllers\Front\TermsAndConditions;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\TermsAndConditions\Terms;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($orderUniqueId)
    {
        $order = Order::query()->with('products')->where('unique_id', $orderUniqueId)->firstOrFail();

        return view('front.terms_and_conditions.index', [
            'title' => 'Terms And Conditions',
            'order' => $order,
        ]);
    }
}
