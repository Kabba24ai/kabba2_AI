<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Templates;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\Product;

class CreateController extends Controller
{
    public function __invoke()
    {
        $categories = ProductCategory::with('products')->get();
        return view('admin.maintenance_management.parts.templates.create')->with('categories', $categories);
    }
}

