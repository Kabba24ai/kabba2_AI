<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\StorePage;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use Illuminate\Http\Request;

/**
 * Saves a single store's public-facing page settings (heading, intro,
 * description, image, SEO/OG) from the Contact Us → Stores tab. This is
 * the only place these fields are edited — the Store Management form
 * only holds operational data (address, hours, service areas, etc.).
 */
class UpdateController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $store = Store::where('unique_id', $unique_id)->firstOrFail();

        $validated = $request->validate([
            'page_status'            => ['nullable', 'in:Active,Inactive'],
            'show_contact_strip'     => ['nullable', 'boolean'],
            'page_heading'           => ['nullable', 'string', 'max:240'],
            'intro_text'             => ['nullable', 'string', 'max:500'],
            'page_description'       => ['nullable', 'string', 'max:5000'],
            'page_image_media_id'    => ['nullable', 'integer', 'exists:media,id'],
            'seo_title'              => ['nullable', 'string', 'max:240'],
            'page_meta_description'  => ['nullable', 'string', 'max:500'],
            'og_title'               => ['nullable', 'string', 'max:240'],
            'og_description'         => ['nullable', 'string', 'max:500'],
            'page_og_image_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'canonical_url'          => ['nullable', 'url', 'max:500'],
        ]);

        $validated['show_contact_strip'] = $request->boolean('show_contact_strip');

        $store->page()->updateOrCreate([], [
            'status'             => $validated['page_status'] ?? 'Active',
            'show_contact_strip' => $validated['show_contact_strip'],
            'page_heading'       => $validated['page_heading'] ?? null,
            'intro_text'         => $validated['intro_text'] ?? null,
            'description'        => $validated['page_description'] ?? null,
            'image_media_id'     => $validated['page_image_media_id'] ?? null,
            'seo_title'          => $validated['seo_title'] ?? null,
            'meta_description'   => $validated['page_meta_description'] ?? null,
            'og_title'           => $validated['og_title'] ?? null,
            'og_description'     => $validated['og_description'] ?? null,
            'og_image_media_id'  => $validated['page_og_image_media_id'] ?? null,
            'canonical_url'      => $validated['canonical_url'] ?? null,
        ]);

        flash($store->store_name . ' public page updated successfully.')->success();

        return redirect()->route('admin.website-management.contact-builder.index', ['tab' => 'locations']);
    }
}
