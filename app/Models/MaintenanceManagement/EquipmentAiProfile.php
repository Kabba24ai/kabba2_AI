<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ModelHelper;
use App\Models\ProductManagement\ProductCategory;

class EquipmentAiProfile extends Model
{
    use HasFactory;

    protected $table = 'equipment_ai_profiles';

    protected $fillable = [
        'unique_id',
        'category_id',
        'make',
        'model',
        'normalized_make',
        'normalized_model',
        'ai_status',
        'last_ai_update_at',
        'source_notes',
    ];

    protected $casts = [
        'last_ai_update_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------
     | Boot — auto-generate unique_id on create
     ------------------------------------------------------------------ */

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'EQAI');
            }
        });
    }

    /* ------------------------------------------------------------------
     | Relationships
     ------------------------------------------------------------------ */

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function specifications()
    {
        return $this->hasMany(EquipmentAiSpecification::class, 'equipment_ai_profile_id');
    }

    /* ------------------------------------------------------------------
     | Helpers
     ------------------------------------------------------------------ */

    /**
     * Normalise a make or model string for dedup matching.
     * Lowercases, collapses whitespace, strips common punctuation.
     */
    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value);          // collapse spaces
        $value = preg_replace('/[^a-z0-9 ]/', '', $value);   // strip punctuation
        return trim($value);
    }

    /**
     * Badge colour for ai_status — returns a Tailwind class string.
     */
    public function statusBadgeClass(): string
    {
        return match ($this->ai_status) {
            'pending'       => 'bg-gray-100 text-gray-600',
            'ready_for_ai'  => 'bg-blue-100 text-blue-700',
            'processing'    => 'bg-yellow-100 text-yellow-700',
            'completed'     => 'bg-green-100 text-green-700',
            'needs_review'  => 'bg-orange-100 text-orange-700',
            'failed'        => 'bg-red-100 text-red-700',
            default         => 'bg-gray-100 text-gray-600',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->ai_status) {
            'pending'       => 'Pending',
            'ready_for_ai'  => 'Ready for AI',
            'processing'    => 'Processing',
            'completed'     => 'Completed',
            'needs_review'  => 'Needs Review',
            'failed'        => 'Failed',
            default         => ucfirst($this->ai_status),
        };
    }
}
