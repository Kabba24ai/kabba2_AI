<?php

namespace App\Models\WebsiteManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class WebsiteMenu extends Model
{
    protected $table = 'website_menus';

    protected $fillable = [
        'unique_id',
        'name',
        'menu_key',
        'description',
        'status',
        'display_order',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'WMN');
        });
    }

    public function items()
    {
        return $this->hasMany(WebsiteMenuItem::class)->orderBy('display_order');
    }

    public function rootItems()
    {
        return $this->items()->whereNull('parent_id');
    }
}
