<?php

namespace App\Observers;

use App\Models\ProductManagement\ProductCategory;
use App\Services\AIVisibility\AIPageMetadataGenerator;

class ProductCategoryObserver
{
    public function __construct(protected AIPageMetadataGenerator $generator) {}

    public function saved(ProductCategory $category): void
    {
        if ($category->status !== 'Published') {
            return;
        }

        $this->generator->generateForCategory($category);
    }

    public function deleted(ProductCategory $category): void
    {
        $this->generator->deleteForCategory($category->id);
    }
}
