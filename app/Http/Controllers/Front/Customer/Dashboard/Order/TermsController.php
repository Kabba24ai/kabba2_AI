<?php

namespace App\Http\Controllers\Front\Customer\Dashboard\Order;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TermsController extends Controller
{
    /**
     * Show the EXACT signed Terms & Conditions for one of the authenticated
     * customer's own orders. Renders the same canonical view the admin order
     * screen opens (accepted_terms_content — the frozen snapshot captured at
     * signing time), never regenerated terms.
     */
    public function __invoke($unique_id)
    {
        // Resolve through the SAME canonical ownership relationship the portal
        // uses to list orders (Customer::orders() — the created_by morph, set
        // by web checkout). An order this relationship doesn't own — another
        // customer's, or one the portal never lists — 404s, so direct URL
        // guessing cannot expose a different customer's agreement.
        $order = Auth::guard('customer')->user()
            ->orders()
            ->with('products')
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        abort_unless($order->hasSignedTerms(), 404);

        return view('front.terms_and_conditions.index', [
            'title'  => 'Terms And Conditions',
            'order'  => $order,
            'device' => null,
        ]);
    }
}
