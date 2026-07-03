<?php

namespace App\Services\Navigation;

use App\Models\WebsiteManagement\WebsiteMenu;
use App\Models\WebsiteManagement\WebsiteMenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the rendered navigation tree for frontend use.
     * Active items only. Result is cached.
     */
    public function getTree(string $menuKey): array
    {
        return Cache::remember("nav_menu_{$menuKey}", self::CACHE_TTL, function () use ($menuKey) {
            $menu = WebsiteMenu::where('menu_key', $menuKey)
                ->where('status', 'Active')
                ->first();
            if (!$menu) return [];

            $items = $menu->items()
                ->where('status', 'Active')
                ->with('page')
                ->orderBy('display_order')
                ->get();

            return $this->buildTree($items);
        });
    }

    /**
     * Build tree as plain arrays (suitable for caching and frontend API).
     */
    public function buildTree(Collection $items, ?int $parentId = null): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->map(function ($item) use ($items) {
                return [
                    'unique_id'  => $item->unique_id,
                    'title'      => $item->title,
                    'url'        => $item->resolved_url,
                    'type'       => $item->type,
                    'target'     => $item->target,
                    'rel'        => $item->rel,
                    'icon'       => $item->icon,
                    'css_class'  => $item->css_class,
                    'visibility' => $item->visibility,
                    'content'    => $item->content,
                    'children'   => $this->buildTree($items, $item->id),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Build tree of Eloquent models for admin builder (no cache).
     * Sets a dynamic `children` Collection on each item.
     */
    public function buildAdminTree(Collection $items, ?int $parentId = null): Collection
    {
        return $items
            ->where('parent_id', $parentId)
            ->map(function ($item) use ($items) {
                $item->children = $this->buildAdminTree($items, $item->id);
                return $item;
            })
            ->values();
    }

    /**
     * Clear the cache for a specific menu.
     */
    public function clearCache(string $menuKey): void
    {
        Cache::forget("nav_menu_{$menuKey}");
    }

    /**
     * Clear cache for all menus.
     */
    public function clearAllCache(): void
    {
        WebsiteMenu::pluck('menu_key')->each(fn($key) => $this->clearCache($key));
    }
}
