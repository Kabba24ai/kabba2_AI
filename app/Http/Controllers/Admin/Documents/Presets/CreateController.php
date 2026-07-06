<?php

namespace App\Http\Controllers\Admin\Documents\Presets;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;

class CreateController extends Controller
{
    public function __invoke()
    {
        // Alphabetical for easy finding — admin selection UI only.
        $categories = ProductCategory::query()
            ->published()
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.documents.presets.create', compact('categories'));
    }
}
