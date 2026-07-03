<?php

namespace App\Services\Website;

use App\Models\WebsiteManagement\WebsitePageRevision;

class RevisionCompareService
{
    /**
     * Compare two revisions. Returns a structured diff.
     * Convention: $base = older, $compare = newer.
     */
    public function compare(WebsitePageRevision $base, WebsitePageRevision $compare): array
    {
        $snapA = $base->snapshot    ?? [];
        $snapB = $compare->snapshot ?? [];

        $pageChanges    = $this->diffPageSettings($snapA['page']     ?? [], $snapB['page']     ?? []);
        $sectionChanges = $this->diffSections($snapA['sections']     ?? [], $snapB['sections'] ?? []);

        return [
            'base'    => ['revision' => $base,    'snapshot' => $snapA],
            'compare' => ['revision' => $compare, 'snapshot' => $snapB],
            'page_settings' => $pageChanges,
            'sections'      => $sectionChanges,
            'has_changes'   => !empty($pageChanges) || $this->sectionHasChanges($sectionChanges),
            'summary' => [
                'page_field_changes'  => count($pageChanges),
                'sections_added'      => count($sectionChanges['added']),
                'sections_removed'    => count($sectionChanges['removed']),
                'sections_changed'    => count($sectionChanges['changed']),
            ],
        ];
    }

    // ── Page Settings Diff ───────────────────────────────────────────────

    private function diffPageSettings(array $a, array $b): array
    {
        $fields   = ['title', 'slug', 'meta_title', 'meta_description', 'meta_keywords',
                     'og_title', 'og_description', 'canonical_url', 'publish_status'];
        $changes  = [];

        foreach ($fields as $field) {
            $from = $a[$field] ?? null;
            $to   = $b[$field] ?? null;
            if ($from !== $to) {
                $changes[] = ['field' => $field, 'from' => $from, 'to' => $to];
            }
        }

        // Image change
        if (($a['og_image'] ?? null) !== ($b['og_image'] ?? null)) {
            $changes[] = ['field' => 'og_image', 'from' => $a['og_image'] ?? null, 'to' => $b['og_image'] ?? null];
        }

        return $changes;
    }

    // ── Section Diff ─────────────────────────────────────────────────────

    private function diffSections(array $a, array $b): array
    {
        $aMap = collect($a)->keyBy('unique_id');
        $bMap = collect($b)->keyBy('unique_id');

        $added   = $bMap->diffKeys($aMap)->values()->all();
        $removed = $aMap->diffKeys($bMap)->values()->all();
        $common  = $aMap->intersectByKeys($bMap);

        $changed = [];
        foreach ($common as $uid => $sectionA) {
            $sectionB      = $bMap->get($uid);
            $sectionChanges = $this->diffSectionFields($sectionA, $sectionB);
            $itemChanges    = $this->diffItems($sectionA['items'] ?? [], $sectionB['items'] ?? []);

            if (!empty($sectionChanges) || $this->sectionHasChanges($itemChanges)) {
                $changed[] = [
                    'unique_id'      => $uid,
                    'name'           => $sectionB['section_name'] ?? $sectionB['section_key'] ?? $uid,
                    'field_changes'  => $sectionChanges,
                    'item_changes'   => $itemChanges,
                ];
            }
        }

        return ['added' => $added, 'removed' => $removed, 'changed' => $changed];
    }

    private function diffSectionFields(array $a, array $b): array
    {
        $fields  = ['section_name', 'title', 'subtitle', 'button_text', 'button_url',
                    'display_order', 'status', 'image'];
        $changes = [];
        foreach ($fields as $field) {
            $from = $a[$field] ?? null;
            $to   = $b[$field] ?? null;
            if ($from !== $to) {
                $changes[] = ['field' => $field, 'from' => $from, 'to' => $to];
            }
        }
        if (json_encode($a['content'] ?? null) !== json_encode($b['content'] ?? null)) {
            $changes[] = ['field' => 'content', 'from' => $a['content'] ?? null, 'to' => $b['content'] ?? null];
        }
        return $changes;
    }

    // ── Item Diff ────────────────────────────────────────────────────────

    private function diffItems(array $a, array $b): array
    {
        $aMap = collect($a)->keyBy('unique_id');
        $bMap = collect($b)->keyBy('unique_id');

        $added   = $bMap->diffKeys($aMap)->values()->all();
        $removed = $aMap->diffKeys($bMap)->values()->all();
        $common  = $aMap->intersectByKeys($bMap);

        $changed = [];
        foreach ($common as $uid => $itemA) {
            $itemB       = $bMap->get($uid);
            $itemChanges = $this->diffItemFields($itemA, $itemB);
            if (!empty($itemChanges)) {
                $changed[] = ['unique_id' => $uid, 'title' => $itemB['title'] ?? $uid, 'changes' => $itemChanges];
            }
        }

        return ['added' => $added, 'removed' => $removed, 'changed' => $changed];
    }

    private function diffItemFields(array $a, array $b): array
    {
        $fields  = ['title', 'subtitle', 'description', 'icon', 'button_text', 'button_url', 'display_order', 'status', 'image'];
        $changes = [];
        foreach ($fields as $field) {
            $from = $a[$field] ?? null;
            $to   = $b[$field] ?? null;
            if ($from !== $to) {
                $changes[] = ['field' => $field, 'from' => $from, 'to' => $to];
            }
        }
        if (json_encode($a['content'] ?? null) !== json_encode($b['content'] ?? null)) {
            $changes[] = ['field' => 'content', 'from' => $a['content'] ?? null, 'to' => $b['content'] ?? null];
        }
        return $changes;
    }

    private function sectionHasChanges(array $diff): bool
    {
        return !empty($diff['added']) || !empty($diff['removed']) || !empty($diff['changed']);
    }
}
