<?php

namespace App\View\Components\Admin\OrderManagement\Orders;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class OrderNotesList extends Component
{
    /**
     * The notes collection.
     *
     * @var \Illuminate\Support\Collection|array
     */
    public $notes;

    /**
     * Create a new component instance.
     *
     * @param  \Illuminate\Support\Collection|array  $notes
     * @return void
     */
    public function __construct($notes)
    {
        $this->notes = $notes;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render(): View|string
    {
        return view('components.admin.order-management.orders.order-notes-list');
    }
}
