<?php

namespace App\Models\MaintenanceManagement;

use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentSoftAssign extends Model
{
    use SoftDeletes;

    protected $table = 'equipment_soft_assigns';

    protected $fillable = [
        'equipment_id',
        'order_id',
        'order_product_id',
        'assigned_by',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }
}
