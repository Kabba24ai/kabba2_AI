<?php

namespace App\Models\AIVisibility;

use Illuminate\Database\Eloquent\Model;

class AiPageMetadata extends Model
{
    protected $table = 'ai_page_metadata';

    protected $fillable = [
        'page_type',
        'page_id',
        'url',
        'ai_title',
        'ai_summary',
        'ai_keywords',
        'ai_service_type',
        'ai_use_cases',
        'ai_area_served',
        'ai_related_categories',
        'ai_related_products',
        'schema_json',
        'generated_from_hash',
        'last_generated_at',
    ];

    protected $casts = [
        'ai_keywords'          => 'array',
        'ai_use_cases'         => 'array',
        'ai_area_served'       => 'array',
        'ai_related_categories'=> 'array',
        'ai_related_products'  => 'array',
        'schema_json'          => 'array',
        'last_generated_at'    => 'datetime',
    ];

    public static function findForProduct(int $productId): ?self
    {
        return static::where('page_type', 'product')->where('page_id', $productId)->first();
    }

    public static function findForCategory(int $categoryId): ?self
    {
        return static::where('page_type', 'category')->where('page_id', $categoryId)->first();
    }

    public static function findForHome(): ?self
    {
        return static::where('page_type', 'home')->whereNull('page_id')->first();
    }
}
