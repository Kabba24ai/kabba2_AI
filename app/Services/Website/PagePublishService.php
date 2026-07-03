<?php

namespace App\Services\Website;

use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageRevision;
use App\Models\WebsiteManagement\WebsiteRevisionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PagePublishService
{
    public function __construct(private PageRevisionService $revisionService) {}

    /**
     * Save a manual draft checkpoint. Does not change publish_status.
     */
    public function saveDraft(WebsitePage $page, ?string $summary = null): WebsitePageRevision
    {
        $revision = $this->revisionService->createRevision(
            $page,
            $summary ?? 'Manual draft save'
        );

        $this->log($page, $revision, 'draft_saved', 'Draft checkpoint created.');

        return $revision;
    }

    /**
     * Publish: mark the page live, create a published snapshot, clear cache.
     */
    public function publish(WebsitePage $page): WebsitePageRevision
    {
        return DB::transaction(function () use ($page) {
            // Mark any previous published snapshot as no longer current
            WebsitePageRevision::where('website_page_id', $page->id)
                ->where('is_published_snapshot', true)
                ->update(['is_published_snapshot' => false]);

            $revision = $this->revisionService->createRevision($page, 'Published', true);

            $page->publish_status        = WebsitePage::PUBLISH_STATUS_PUBLISHED;
            $page->status                = 'Active';     // backward-compat for frontend
            $page->published_at          = now();
            $page->published_revision_id = $revision->id;
            $page->saveQuietly();

            $this->clearPublishedCache($page);
            $this->log($page, $revision, 'published', "Page \"{$page->title}\" published.");

            return $revision;
        });
    }

    /**
     * Unpublish: take page offline (back to draft), clear cache.
     */
    public function unpublish(WebsitePage $page): void
    {
        DB::transaction(function () use ($page) {
            $page->publish_status = WebsitePage::PUBLISH_STATUS_DRAFT;
            $page->status         = 'Inactive';
            $page->saveQuietly();

            $this->clearPublishedCache($page);
            $this->log($page, null, 'unpublished', "Page \"{$page->title}\" unpublished.");
        });
    }

    /**
     * Archive: retire the page, clear cache.
     */
    public function archive(WebsitePage $page): void
    {
        DB::transaction(function () use ($page) {
            $page->publish_status = WebsitePage::PUBLISH_STATUS_ARCHIVED;
            $page->status         = 'Inactive';
            $page->saveQuietly();

            $this->clearPublishedCache($page);
            $this->log($page, null, 'archived', "Page \"{$page->title}\" archived.");
        });
    }

    /**
     * Record an auto-save timestamp (no full revision created, just a heartbeat).
     */
    public function autoSave(WebsitePage $page, ?array $data = null): void
    {
        $page->auto_saved_at  = now();
        $page->auto_save_data = $data;
        $page->saveQuietly();

        $this->log($page, null, 'auto_saved', null);
    }

    public function clearPublishedCache(WebsitePage $page): void
    {
        Cache::forget("website_page_{$page->page_key}");
        Cache::forget("website_page_slug_{$page->slug}");
        Cache::forget("website_page_id_{$page->id}");
    }

    private function log(
        WebsitePage          $page,
        ?WebsitePageRevision $revision,
        string               $action,
        ?string              $description
    ): void {
        $request = app(Request::class);

        WebsiteRevisionLog::create([
            'website_page_id'          => $page->id,
            'website_page_revision_id' => $revision?->id,
            'user_id'                  => auth()->id(),
            'action'                   => $action,
            'description'              => $description,
            'ip_address'               => $request->ip(),
            'user_agent'               => $request->userAgent(),
        ]);
    }
}
