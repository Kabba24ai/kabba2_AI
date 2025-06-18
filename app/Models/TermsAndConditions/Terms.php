<?php
namespace App\Models\TermsAndConditions;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Terms extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'unique_id',
        'title',
        'slug',
        'content',
        'signature_block',
        'is_global',
        'status',
        'seo_title',
        'seo_description',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];
    protected $table = 'terms_and_conditions';

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'onUpdate' => true,
            ]
        ];
    }

    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('id', 'ASC');
    }

    public function scopeOrderByTitle($query)
    {
        return $query->orderBy('title', 'ASC');
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', 'Yes');
    }

    public function scopeProduct($query)
    {
        return $query->where('is_global', 'No');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'Published');
    }

   	public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'TERM');

            // If seo_title is not set, use title or slug as fallback
            if (empty($model->seo_title)) {
                $model->seo_title = $model->title ?? $model->slug;
            }

            // Set created_by and updated_by
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // Automatically update updated_by on update
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
