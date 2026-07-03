<?php

namespace App\Services\Website;

use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageRevision;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use Illuminate\Support\Facades\DB;

class PageRevisionService
{
    /**
     * Build a complete point-in-time snapshot of a page.
     * Captures: page settings, SEO, all sections, all items, component config.
     */
    public function buildSnapshot(WebsitePage $page): array
    {
        $page->loadMissing(['sections' => fn ($q) => $q->with('items')->orderBy('display_order')]);

        return [
            'version' => 1,
            'page' => [
                'title'            => $page->title,
                'slug'             => $page->slug,
                'meta_title'       => $page->meta_title,
                'meta_description' => $page->meta_description,
                'meta_keywords'    => $page->meta_keywords,
                'og_title'         => $page->og_title,
                'og_description'   => $page->og_description,
                'og_image'         => $page->og_image,
                'canonical_url'    => $page->canonical_url,
                'status'           => $page->status,
                'publish_status'   => $page->publish_status,
            ],
            'sections' => $page->sections->map(function (WebsitePageSection $section) {
                return [
                    'unique_id'    => $section->unique_id,
                    'section_key'  => $section->section_key,
                    'section_type' => $section->section_type,
                    'section_name' => $section->section_name,
                    'title'        => $section->title,
                    'subtitle'     => $section->subtitle,
                    'content'      => $section->content,
                    'image'        => $section->image,
                    'button_text'  => $section->button_text,
                    'button_url'   => $section->button_url,
                    'display_order'=> $section->display_order,
                    'status'       => $section->status,
                    'items'        => $section->items->map(function (WebsiteSectionItem $item) {
                        return [
                            'unique_id'    => $item->unique_id,
                            'item_key'     => $item->item_key,
                            'title'        => $item->title,
                            'subtitle'     => $item->subtitle,
                            'description'  => $item->description,
                            'image'        => $item->image,
                            'icon'         => $item->icon,
                            'button_text'  => $item->button_text,
                            'button_url'   => $item->button_url,
                            'content'      => $item->content,
                            'display_order'=> $item->display_order,
                            'status'       => $item->status,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * Create a revision from the current live state of the page.
     */
    public function createRevision(
        WebsitePage $page,
        ?string     $summary  = null,
        bool        $isPublished = false
    ): WebsitePageRevision {
        $revisionNumber = $this->nextRevisionNumber($page);

        return WebsitePageRevision::create([
            'website_page_id'      => $page->id,
            'revision_number'      => $revisionNumber,
            'created_by'           => auth()->id(),
            'change_summary'       => $summary,
            'snapshot'             => $this->buildSnapshot($page),
            'is_published_snapshot'=> $isPublished,
        ]);
    }

    /**
     * Restore a page to the exact state captured in a revision.
     * Always creates a NEW revision representing the restore action.
     * Never overwrites existing history.
     */
    public function restoreRevision(WebsitePageRevision $revision): WebsitePageRevision
    {
        $page     = $revision->page;
        $snapshot = $revision->snapshot;

        return DB::transaction(function () use ($page, $snapshot, $revision) {
            // 1. Restore page-level settings
            $pageData = $snapshot['page'] ?? [];
            $page->fill(array_intersect_key($pageData, array_flip([
                'title', 'slug', 'meta_title', 'meta_description', 'meta_keywords',
                'og_title', 'og_description', 'og_image', 'canonical_url',
            ])));
            $page->saveQuietly(); // avoid triggering boot hooks twice

            // 2. Remove all current sections (cascade removes items)
            $page->sections()->delete();

            // 3. Recreate sections and items from snapshot
            foreach ($snapshot['sections'] ?? [] as $sectionData) {
                $items = $sectionData['items'] ?? [];

                $section = WebsitePageSection::create(array_merge(
                    array_diff_key($sectionData, ['items' => true]),
                    ['website_page_id' => $page->id]
                ));

                foreach ($items as $itemData) {
                    WebsiteSectionItem::create(array_merge(
                        $itemData,
                        ['website_page_section_id' => $section->id]
                    ));
                }
            }

            // 4. Create a new revision marking the restore (preserve audit trail)
            $newRevision = $this->createRevision(
                $page->fresh(),
                "Restored from revision #{$revision->revision_number}"
            );
            $newRevision->restored_from_revision_id = $revision->id;
            $newRevision->saveQuietly();

            return $newRevision;
        });
    }

    public function getRevisions(WebsitePage $page, int $perPage = 20)
    {
        return $page->revisions()
                    ->withTrashed()
                    ->with('author')
                    ->paginate($perPage);
    }

    public function deleteRevision(WebsitePageRevision $revision): void
    {
        // Never delete the published revision
        if ($revision->is_published_snapshot) {
            throw new \RuntimeException('Cannot delete the currently published revision.');
        }
        $revision->delete(); // soft delete
    }

    private function nextRevisionNumber(WebsitePage $page): int
    {
        return (WebsitePageRevision::withTrashed()
                    ->where('website_page_id', $page->id)
                    ->max('revision_number') ?? 0) + 1;
    }
}
