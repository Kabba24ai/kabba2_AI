<?php

namespace App\View\Components\Admin\ProductManagement\Products;

use Illuminate\View\Component;

class OptionPreview extends Component
{
    /**
     * The collection of Option models.
     *
     * @var \Illuminate\Support\Collection
     */
    public $objProductOption;

    /**
     * Create a new component instance.
     *
     * @param  \Illuminate\Support\Collection  $objProductOption
     * @return void
     */
    public function __construct($objProductOption)
    {
        $this->objProductOption = $objProductOption;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.admin.product-management.products.option-preview');
    }
}
