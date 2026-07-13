<?php

namespace App\Services\ChecklistManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * CategoryCrudService — shared transaction-wrapped CRUD mechanics for the Rental
 * Ready and Customer Admin category controllers (PR-B4.1, Phase 2 Track B).
 *
 * Extracted from 6 controllers whose transaction/lookup/write shape was
 * byte-for-byte identical (see docs/checklist-system-audit/PR-B4_1_CATEGORY_READINESS.md
 * §1/§3). Deliberately does NOT touch anything domain-specific: model class,
 * request validation, mirror-field name/direction, redirect route, and flash/session
 * message choices all stay in each controller. In particular, this service does not
 * attempt to unify soft-delete vs. hard-delete behavior — delete() simply calls
 * $model->delete() and lets each model's own SoftDeletes trait (or lack thereof)
 * determine the real outcome, exactly as before this refactor. See
 * docs/checklist-system-audit/PR-B4_1_CATEGORY_REFACTOR.md for the full rationale,
 * including why RentalReadyChecklistCategory's soft delete and
 * CustomerAdminCategory's hard-delete-with-cascade are both preserved unchanged.
 */
class CategoryCrudService
{
    /**
     * Create a category, optionally mirroring identical attributes into a second
     * model class in one transaction — if the mirror write fails, the primary
     * write is rolled back too.
     *
     * @param  class-string<Model>  $modelClass
     * @param  class-string<Model>|null  $mirrorModelClass
     */
    public function store(string $modelClass, array $attributes, ?string $mirrorModelClass, bool $shouldMirror): Model
    {
        return DB::transaction(function () use ($modelClass, $attributes, $mirrorModelClass, $shouldMirror) {
            $category = $modelClass::create($attributes);

            if ($shouldMirror && $mirrorModelClass) {
                $mirrorModelClass::create($attributes);
            }

            return $category;
        });
    }

    /**
     * Look up a category by unique_id and update it. Mirrors the controllers'
     * existing lookup exactly: a plain ->first() (not ->firstOrFail()) — if no
     * row matches, $category is null and ->update() on null throws, which is
     * today's actual (incidental, pre-existing) behavior for a nonexistent
     * unique_id. Preserved as-is per this PR's scope; not changed to firstOrFail()
     * here, since that would be an unrequested behavior decision, not extraction.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function update(string $modelClass, string $uniqueId, array $attributes): Model
    {
        return DB::transaction(function () use ($modelClass, $uniqueId, $attributes) {
            $category = $modelClass::where('unique_id', $uniqueId)->first();
            $category->update($attributes);

            return $category;
        });
    }

    /**
     * Look up a category by unique_id (firstOrFail — unchanged from today's
     * DeleteControllers) and delete it. Whether that delete is a soft delete or a
     * real, cascading hard delete is entirely determined by $modelClass's own
     * SoftDeletes trait (or absence of it) — this method has no opinion on it.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function delete(string $modelClass, string $uniqueId): void
    {
        DB::transaction(function () use ($modelClass, $uniqueId) {
            $modelClass::where('unique_id', $uniqueId)->firstOrFail()->delete();
        });
    }
}
