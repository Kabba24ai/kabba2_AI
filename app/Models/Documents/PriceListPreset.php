<?php

namespace App\Models\Documents;

use App\Models\Global\Media;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Admin-managed Industry Preset for the Customer Price List generator.
 * A preset is a selection shortcut only — it pre-checks its assigned
 * categories on the generator form. Generation is always driven by the
 * final category ids submitted, never by preset ids.
 */
class PriceListPreset extends Model
{
    use SoftDeletes;

    /** In-memory default matching the DB default. */
    protected $attributes = ['is_active' => true];

    protected $fillable = ['name', 'description', 'is_active', 'sort_order', 'media_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'price_list_preset_product_category',
            'price_list_preset_id',
            'product_category_id'
        )->withTimestamps();
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail?->getUrl();
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Manual sort order first (nulls last), then alphabetical. */
    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderByRaw('sort_order IS NULL')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
