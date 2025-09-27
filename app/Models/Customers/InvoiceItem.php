<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;


class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'type',
        'item_name',
        'item_id',
        'qty',
        'sku',
        'unit',
        'tax',
        'total',
        'extras',
        'notes',
        'reference',
        'responsible_person_id',
    ];

    protected $casts = [
        'extras' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    /**
     * Relationship to Order Products when type = 'order'
     */
    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'item_id', 'unique_id');
    }
}
