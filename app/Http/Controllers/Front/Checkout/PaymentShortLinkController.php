<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Orders\PaymentShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentShortLinkController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $link = PaymentShortLink::where('token', $token)->first();

        if (!$link) {
            Log::channel('jobs')->warning('[ShortLink] Unknown token requested', [
                'token' => $token,
                'ip'    => $request->ip(),
                'ua'    => $request->userAgent(),
            ]);

            abort(404);
        }

        if (!$link->isActive()) {
            return view('front.checkout.payment-link-expired');
        }

        $link->recordClick($request->ip(), $request->userAgent());

        return redirect()->away($link->original_url);
    }
}
