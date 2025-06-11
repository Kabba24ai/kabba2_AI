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
    public $options;


    /** @var string  Either 'rental' or 'retail' */
    public $productType;

    /**
     * Create a new component instance.
     *
     * @param  \Illuminate\Support\Collection  $options
     * @return void
     */
    public function __construct($options, $productType)
    {
        $this->options = $options;
        $this->productType = $productType;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.admin.product-management.products.option-preview');
    }
}
