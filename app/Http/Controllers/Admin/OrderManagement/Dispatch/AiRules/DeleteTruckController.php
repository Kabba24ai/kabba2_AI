<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiTruck;

class DeleteTruckController extends Controller
{
    public function __invoke(int $id)
    {
        DispatchAiTruck::findOrFail($id)->delete();

        return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'trucks'])
            ->with('success', 'Truck removed.');
    }
}
