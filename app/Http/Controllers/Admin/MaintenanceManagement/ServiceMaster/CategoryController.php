<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:service_categories,name',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/'
        ]);
        
        $category = ServiceCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'category' => $category
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $category = ServiceCategory::findOrFail($id);
        
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('service_categories')->ignore($category->id)],
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/'
        ]);
        
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }
    
    public function destroy($id)
    {
        $category = ServiceCategory::findOrFail($id);
        $category->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}