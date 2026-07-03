<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\WebsitePages\StoreRequest;
use App\Models\WebsiteManagement\WebsitePage;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        $validated['slug']     = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['page_key'] = $validated['page_key'] ?? $validated['slug'];
        $validated['status']   = $validated['status'] ?? 'Inactive';

        $page = WebsitePage::create($validated);

        session()->flash('success', 'Page "' . $page->title . '" created successfully.');

        return redirect()->route('admin.website-management.pages.edit', $page->unique_id);
    }
}
