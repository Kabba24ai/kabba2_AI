<?php

namespace App\Services\DocumentGenerator;

use Illuminate\Support\Collection;

/**
 * Resolves the configured industry presets (config/price_list_presets.php)
 * against the categories actually available in this environment. Presets are
 * a selection shortcut only — the generated document remains category-based.
 */
class PriceListPresets
{
    /**
     * Resolve every preset to concrete category ids from the given
     * selectable categories (already filtered to published + has products).
     *
     * @param  Collection  $categories  collection with id + slug
     * @return Collection<string, array{key: string, label: string, description: string, category_ids: int[]}>
     */
    public static function resolve(Collection $categories): Collection
    {
        return collect(config('price_list_presets', []))
            ->map(function (array $preset, string $key) use ($categories) {
                $ids = $categories
                    ->filter(function ($category) use ($preset) {
                        $slug = (string) $category->slug;

                        if (in_array($slug, $preset['slugs'] ?? [], true)) {
                            return true;
                        }

                        foreach ($preset['slug_keywords'] ?? [] as $keyword) {
                            if ($keyword !== '' && str_contains($slug, $keyword)) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->pluck('id')
                    ->values()
                    ->all();

                return [
                    'key'          => $key,
                    'label'        => $preset['label'] ?? $key,
                    'description'  => $preset['description'] ?? '',
                    'category_ids' => $ids,
                ];
            });
    }
}
