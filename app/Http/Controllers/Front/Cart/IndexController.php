<?php

namespace App\Http\Controllers\Front\Cart;

use App\Http\Controllers\Controller;
use App\Services\RentalCartService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    protected $cartService;

    public function __construct(RentalCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function add(Request $request)
    {
        $item = $request->only([
            'product_id',
            'name',
            'image',
            'rental_type',
            'qty',
            'base_price',
            'product_slug',
            'addons',
            'schedule_date',
            'delivery_fee',
        ]);

        $cart = $this->cartService->addItem($item);

        return response()->json([
            'success' => true,
            'cart' => $cart,
            'session_id' => session()->getId(),
        ]);
    }

    public function get()
    {
        $cart = $this->cartService->getCart();

        $taxRate = $this->cartService->getTaxRate(); 

        return response()->json([
            'cart' => [
                'rental_cart' => $cart,
                'tax_rate' => $taxRate,
            ],
        ]);
    }

    public function remove(Request $request)
    {
        $index = (int) $request->input('index');

        $cart = $this->cartService->removeItem($index);

        return response()->json(['cart' => $cart]);
    }

    public function clear()
    {
        $cart = $this->cartService->clearCart();

        return response()->json(['cart' => $cart]);
    }
}
