<?php

namespace App\Support\Tasks;

use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\ProductManagement\ProductCategory;

/**
 * Everything the shared "New Task" modal partial
 * (admin.tasks.partials._unified_task_modal) needs to render. One source
 * for every page that embeds the modal (Task Center, Dashboard, Order
 * Details) so option lists can never drift between entry points.
 */
class UnifiedTaskModalData
{
    /** Keys match the partial's expected variable names — usable directly as @include data. */
    public static function make(): array
    {
        return array_merge([
            'categories' => TaskCategory::cases(),
            'priorities' => TaskPriority::cases(),
            'statuses'   => TaskStatus::cases(),
            'users'      => User::active()->orderBy('first_name')->get(),
            'customers'  => self::customers(),
            'suppliers'  => self::suppliers(),
        ], self::equipmentData());
    }

    public static function customers()
    {
        return Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public static function suppliers()
    {
        return Supplier::active()->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'primary_contact_name', 'primary_contact_phone']);
    }

    public static function equipmentData(): array
    {
        $usedCategoryIds = Equipment::whereNotNull('product_category_id')
            ->pluck('product_category_id')
            ->unique();

        $productCategories = ProductCategory::whereNull('parent_id')
            ->whereIn('id', $usedCategoryIds)
            ->orderBy('title')
            ->get(['id', 'title']);

        $statusLabels = [
            'available'   => 'Available',
            'rented'      => 'Rented',
            'maintenance' => 'Maint. Hold',
            'damaged'     => 'Damaged',
        ];

        $equipmentList = Equipment::whereNotNull('product_category_id')
            ->orderBy('equipment_name')
            ->get(['id', 'equipment_id', 'equipment_name', 'product_category_id', 'current_status', 'serial_number'])
            ->map(fn($e) => [
                'id'          => $e->id,
                'equipment_id' => $e->equipment_id,
                'name'        => $e->equipment_name,
                'category_id' => $e->product_category_id,
                'status'      => $statusLabels[$e->getRawOriginal('current_status')] ?? '',
                'serial'      => $e->serial_number ?? '',
            ]);

        return compact('productCategories', 'equipmentList');
    }
}
