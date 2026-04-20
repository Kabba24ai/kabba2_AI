<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Collection;

class CandidateEquipmentService
{
    public function getCandidatesForOrderProduct(OrderProduct $orderProduct): Collection
    {
        $productName = $orderProduct->product_name ?? null;
        $categoryId = $orderProduct->category_id
            ?? $orderProduct->product_category_id
            ?? optional($orderProduct->product)->product_category_id;

        return Equipment::query()
            ->where(function ($query) use ($productName, $categoryId) {
                if ($categoryId) {
                    $query->orWhere('product_category_id', $categoryId);
                }

                if ($productName) {
                    $productName = explode(' ', $productName);
                    $query->orWhere(function ($subQuery) use ($productName) {
                        foreach ($productName as $word) {
                            $subQuery->where('equipment_name', 'like', '%' . $word . '%')
                                ->orWhere('equipment_id', 'like', '%' . $word . '%');
                        }
                    });
                }
            })
            ->orderBy('id')
            ->get();
    }
}
