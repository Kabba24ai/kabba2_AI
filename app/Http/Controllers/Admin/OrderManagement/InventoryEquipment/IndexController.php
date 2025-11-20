<?php

namespace App\Http\Controllers\Admin\OrderManagement\InventoryEquipment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Equipment;

// Models
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use App\Models\ProductManagement\ProductCategory;
use App\Enums\Equipments\EquipmentCurrentStatus;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        
        return view('admin.order_management.inventory_equipment.index');
    }
}
