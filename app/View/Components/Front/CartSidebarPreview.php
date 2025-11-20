<?php

namespace App\View\Components\Front;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CartSidebarPreview extends Component
{
    /**
     * The collection of Option models.
     *
     * @var \Illuminate\Support\Collection
     */
    public $cart;

    /**
     * Create a new component instance.
     *
     * @param  \Illuminate\Support\Collection  $cart_items
     * @return void
     */
    public function __construct($cart)
    {
        $this->cart = $cart;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.front.cart-sidebar-preview');
    }
}
