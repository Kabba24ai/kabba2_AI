<?php

namespace App\Services\MediaLibrary;

use App\Models\Global\Media;
use Illuminate\Support\Collection;

class MediaUsageService
{
    /**
     * Each entry: [ModelClass, field_name, human_label]
     * Add new entries here as more models start storing media IDs.
     */
    protected array $trackedFields = [
        [\App\Models\WebsiteManagement\WebsitePageSection::class, 'image', 'Page Section Image'],
        [\App\Models\WebsiteManagement\WebsiteSectionItem::class, 'image', 'Section Item Image'],
    ];

    public function getUsages(Media $media): Collection
    {
        $usages = collect();

        foreach ($this->trackedFields as [$modelClass, $field, $label]) {
            $records = $modelClass::where($field, $media->id)->get();
            foreach ($records as $record) {
                $usages->push(['label' => $label, 'id' => $record->id]);
            }
        }

        return $usages;
    }

    public function isInUse(Media $media): bool
    {
        foreach ($this->trackedFields as [$modelClass, $field]) {
            if ($modelClass::where($field, $media->id)->exists()) {
                return true;
            }
        }
        return false;
    }

    public function getUsageCount(Media $media): int
    {
        $count = 0;
        foreach ($this->trackedFields as [$modelClass, $field]) {
            $count += $modelClass::where($field, $media->id)->count();
        }
        return $count;
    }
}
