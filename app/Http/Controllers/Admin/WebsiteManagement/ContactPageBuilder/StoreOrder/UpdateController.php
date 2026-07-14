<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\StoreOrder;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Services\Website\ContactPageService;
use Illuminate\Http\Request;

/**
 * Persists the Contact Us page's store DISPLAY order.
 *
 * The order lives as `store_card` items on the contact page's `locations`
 * section (content.store_id + display_order) — completely independent of
 * operational Store Management: it never touches is_primary, dispatch,
 * routing, or the stores table itself.
 */
class UpdateController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'store_ids'   => ['required', 'array', 'min:1'],
            'store_ids.*' => ['integer', 'distinct'],
        ]);

        $page    = WebsitePage::where('page_key', 'contact')->firstOrFail();
        $section = $page->sections()->where('section_key', 'locations')->firstOrFail();

        // Only real stores may be ordered
        $validIds = Store::whereIn('id', $validated['store_ids'])
            ->pluck('id')
            ->all();
        $ordered = array_values(array_intersect($validated['store_ids'], $validIds));

        // Drop store_card items for stores no longer in the submitted order
        // (deleted stores compact away automatically)
        $section->items()
            ->where('item_key', 'store_card')
            ->get()
            ->filter(fn ($item) => !in_array((int) data_get($item->content, 'store_id'), $ordered, true))
            ->each->delete();

        foreach ($ordered as $position => $storeId) {
            $existing = $section->items()
                ->where('item_key', 'store_card')
                ->get()
                ->first(fn ($item) => (int) data_get($item->content, 'store_id') === $storeId);

            if ($existing) {
                $existing->update(['display_order' => $position + 1]);
            } else {
                WebsiteSectionItem::create([
                    'website_page_section_id' => $section->id,
                    'item_key'                => 'store_card',
                    'title'                   => Store::find($storeId)?->store_name ?? "Store {$storeId}",
                    'content'                 => ['store_id' => $storeId],
                    'display_order'           => $position + 1,
                    'status'                  => 'Active',
                ]);
            }
        }

        ContactPageService::clearCache();

        return response()->json(['success' => true]);
    }
}
