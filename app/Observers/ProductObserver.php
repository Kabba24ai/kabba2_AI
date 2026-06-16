<?php

namespace App\Observers;

use App\Models\ProductManagement\Product;
use App\Services\AIVisibility\AIPageMetadataGenerator;

class ProductObserver
{
    public function __construct(protected AIPageMetadataGenerator $generator) {}

    public function saved(Product $product): void
    {
        if ($product->status !== 'Published') {
            return;
        }

        $this->generator->generateForProduct($product);
    }

    public function deleted(Product $product): void
    {
        $this->generator->deleteForProduct($product->id);
    }
}
