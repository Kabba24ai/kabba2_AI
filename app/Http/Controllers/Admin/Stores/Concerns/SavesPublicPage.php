<?php

namespace App\Http\Controllers\Admin\Stores\Concerns;

use App\Models\Stores\Store;

trait SavesPublicPage
{
    /**
     * Persist the Public Website Page settings submitted with the store form.
     * One row per store, created on first save; operational fields are never
     * duplicated here.
     */
    private function savePublicPage(Store $store, array $data): void
    {
        $store->page()->updateOrCreate([], [
            'status'             => $data['page_status'] ?? 'Active',
            'show_contact_strip' => (bool) ($data['show_contact_strip'] ?? true),
            'page_heading'       => $data['page_heading'] ?? null,
            'intro_text'         => $data['intro_text'] ?? null,
            'description'        => $data['page_description'] ?? null,
            'image_media_id'     => $data['page_image_media_id'] ?? null,
            'seo_title'          => $data['seo_title'] ?? null,
            'meta_description'   => $data['page_meta_description'] ?? null,
            'og_title'           => $data['og_title'] ?? null,
            'og_description'     => $data['og_description'] ?? null,
            'og_image_media_id'  => $data['page_og_image_media_id'] ?? null,
            'canonical_url'      => $data['canonical_url'] ?? null,
        ]);
    }
}
