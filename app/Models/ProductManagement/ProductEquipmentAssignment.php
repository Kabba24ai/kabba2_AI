<?php

namespace App\Models\ProductManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Iam\Personnel\User;

class ProductEquipmentAssignment extends Model
{
    use HasFactory;

    public const PATH_PRIMARY_POOL = 'primary_pool';
    public const PATH_UPGRADE_PRIMARY = 'upgrade_primary';
    public const PATH_UPGRADE_ALTERNATE_1 = 'upgrade_alternate_1';
    public const PATH_UPGRADE_ALTERNATE_2 = 'upgrade_alternate_2';
    public const PATH_DOWNGRADE_OPTION_1 = 'downgrade_option_1';

    protected $fillable = [
        'product_id',
        'base_product_category_id',
        'assignment_notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'primary_equipment_pool',
        'upgrade_path_primary',
        'upgrade_path_alternate_1',
        'upgrade_path_alternate_2',
        'downgrade_path_option_1',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function baseCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'base_product_category_id');
    }

    public function paths()
    {
        return $this->hasMany(ProductEquipmentAssignmentPath::class, 'assignment_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function getPrimaryEquipmentPoolAttribute(): array
    {
        return $this->equipmentIdsForPath(self::PATH_PRIMARY_POOL);
    }

    public function getUpgradePathPrimaryAttribute(): array
    {
        return $this->equipmentIdsForPath(self::PATH_UPGRADE_PRIMARY);
    }

    public function getUpgradePathAlternate1Attribute(): array
    {
        return $this->equipmentIdsForPath(self::PATH_UPGRADE_ALTERNATE_1);
    }

    public function getUpgradePathAlternate2Attribute(): array
    {
        return $this->equipmentIdsForPath(self::PATH_UPGRADE_ALTERNATE_2);
    }

    public function getDowngradePathOption1Attribute(): array
    {
        return $this->equipmentIdsForPath(self::PATH_DOWNGRADE_OPTION_1);
    }

    public function syncPath(string $pathType, ?int $categoryId, array $equipmentIds = []): void
    {
        $cleanEquipmentIds = collect($equipmentIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($cleanEquipmentIds->isEmpty() && empty($categoryId)) {
            $this->paths()->where('path_type', $pathType)->delete();
            return;
        }

        $path = $this->paths()->updateOrCreate(
            ['path_type' => $pathType],
            ['product_category_id' => $categoryId ?: null]
        );

        $syncPayload = $cleanEquipmentIds
            ->values()
            ->mapWithKeys(fn ($equipmentId, $index) => [$equipmentId => ['sort_order' => $index + 1]])
            ->all();

        $path->equipment()->sync($syncPayload);
    }

    private function equipmentIdsForPath(string $pathType): array
    {
        $path = $this->relationLoaded('paths')
            ? $this->paths->firstWhere('path_type', $pathType)
            : $this->paths()->where('path_type', $pathType)->with('items')->first();

        if (!$path) {
            return [];
        }

        if ($path->relationLoaded('items')) {
            return $path->items->pluck('equipment_id')->map(fn ($id) => (int) $id)->values()->all();
        }

        return $path->items()->pluck('equipment_id')->map(fn ($id) => (int) $id)->values()->all();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
