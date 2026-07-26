<?php

namespace App\Services\ServiceManagement;

use App\Enums\Service\ServiceSymptomProfileSymptomMode;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceSymptom;
use App\Models\Service\ServiceSymptomCategory;
use App\Models\Service\ServiceSymptomProfile;
use Illuminate\Support\Collection;

/**
 * THE canonical Service Problem Engine data source — one symptom repository,
 * one category grouping, one equipment-profile applicability model — shared by
 * every intake context (Standard Service, Field Service, and future consumers).
 *
 * A problem is the same problem wherever it is reported; this presenter is the
 * single place that shapes the library + profiles for the client. Selection UX
 * may differ per context, but the repository, profiles, and (via
 * ServiceTicketIntakeService) the complaint persistence are common.
 */
class ServiceProblemLibrary
{
    /** Active symptom categories, ordered. @return Collection<int,array{id:int,name:string,display_order:int}> */
    public static function categories(): Collection
    {
        return ServiceSymptomCategory::active()
            ->orderBy('display_order')
            ->get(['id', 'name', 'display_order'])
            ->map(fn (ServiceSymptomCategory $c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'display_order' => $c->display_order,
            ])->values();
    }

    /** Active symptoms, ordered. @return Collection<int,array{id:int,name:string,category_id:int,display_order:int}> */
    public static function symptoms(): Collection
    {
        return ServiceSymptom::active()
            ->orderBy('display_order')
            ->get(['id', 'name', 'service_symptom_category_id', 'display_order'])
            ->map(fn (ServiceSymptom $s) => [
                'id'            => $s->id,
                'name'          => $s->name,
                'category_id'   => $s->service_symptom_category_id,
                'display_order' => $s->display_order,
            ])->values();
    }

    /**
     * Active equipment symptom profiles with their resolution payload — the
     * client picks the applicable profile from the selected unit (attached
     * profile → product → product category) and assembles the applicable
     * symptoms (included categories + Include additions − Exclude exclusions,
     * or an explicit ordered `items` list).
     */
    public static function profiles(): Collection
    {
        return ServiceSymptomProfile::active()
            ->with(['profileCategories', 'profileSymptoms'])
            ->orderBy('display_order')
            ->get()
            ->map(fn (ServiceSymptomProfile $profile) => [
                'id'                  => $profile->id,
                'name'                => $profile->name,
                'product_id'          => $profile->product_id,
                'product_category_id' => $profile->product_category_id,
                'category_ids'        => $profile->profileCategories->pluck('service_symptom_category_id')->values(),
                'additions'           => $profile->profileSymptoms
                    ->where('mode', ServiceSymptomProfileSymptomMode::Include)
                    ->pluck('service_symptom_id')->values(),
                'exclusions'          => $profile->profileSymptoms
                    ->where('mode', ServiceSymptomProfileSymptomMode::Exclude)
                    ->pluck('service_symptom_id')->values(),
                'items'               => $profile->profileSymptoms
                    ->where('mode', ServiceSymptomProfileSymptomMode::Include)
                    ->sortBy('sort_order')
                    ->pluck('service_symptom_id')->values(),
            ])->values();
    }

    /** Symptom ids in the additive "Field Conditions & Recovery" category. */
    public static function fieldConditionSymptomIds(): Collection
    {
        $category = ServiceSymptomCategory::where('name', 'Field Conditions & Recovery')->first();

        if (!$category) {
            return collect();
        }

        return ServiceSymptom::active()
            ->where('service_symptom_category_id', $category->id)
            ->pluck('id')->map(fn ($id) => (int) $id)->values();
    }

    /**
     * The canonical server-side applicability set for an equipment unit — the
     * SAME resolution the intake JS performs (attached profile → product →
     * product category; assembled categories + additions − exclusions), ALWAYS
     * additive with Field Conditions & Recovery. No profile resolves → the full
     * active library (the "hide nothing until scoped" fallback).
     *
     * This is the server-authoritative guard: Field Service payload
     * construction and request validation both use it, so a manipulated request
     * cannot attach a symptom the selected equipment's profile excludes.
     * Standard Service does not use it (its behavior is unchanged).
     */
    public static function applicableSymptomIdsForEquipment(?int $equipmentId): Collection
    {
        $fullLibrary = fn () => ServiceSymptom::active()->pluck('id')->map(fn ($id) => (int) $id)->values();

        if (!$equipmentId) {
            return $fullLibrary();
        }

        $equipment = Equipment::with(['assignedProduct.categories:product_categories.id'])->find($equipmentId);
        if (!$equipment) {
            return $fullLibrary();
        }

        $profile = self::resolveProfileForEquipment($equipment);
        if (!$profile) {
            return $fullLibrary();
        }

        return $profile->resolvedSymptomIds()
            ->map(fn ($id) => (int) $id)
            ->merge(self::fieldConditionSymptomIds())
            ->unique()->values();
    }

    /** Equipment → applicable symptom profile (attached → product → category). */
    private static function resolveProfileForEquipment(Equipment $equipment): ?ServiceSymptomProfile
    {
        if ($equipment->service_symptom_profile_id) {
            $attached = ServiceSymptomProfile::active()->find($equipment->service_symptom_profile_id);
            if ($attached) {
                return $attached;
            }
        }

        if ($equipment->assigned_product_id) {
            $byProduct = ServiceSymptomProfile::active()
                ->where('product_id', $equipment->assigned_product_id)
                ->orderBy('display_order')->first();
            if ($byProduct) {
                return $byProduct;
            }
        }

        $categoryIds = collect();
        if ($equipment->assignedProduct) {
            $categoryIds = $equipment->assignedProduct->categories->pluck('id');
        }
        if ($equipment->product_category_id) {
            $categoryIds = $categoryIds->push($equipment->product_category_id);
        }
        $categoryIds = $categoryIds->filter()->unique();

        if ($categoryIds->isEmpty()) {
            return null;
        }

        return ServiceSymptomProfile::active()
            ->whereIn('product_category_id', $categoryIds)
            ->orderBy('display_order')->first();
    }
}
