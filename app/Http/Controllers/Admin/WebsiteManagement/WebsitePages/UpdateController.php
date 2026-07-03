<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\WebsitePages\UpdateRequest;
use App\Models\WebsiteManagement\WebsitePage;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, string $unique_id)
    {
        $page      = WebsitePage::where('unique_id', $unique_id)->firstOrFail();
        $validated = $request->validated();

        $page->update($validated);

        session()->flash('success', 'Page settings updated successfully.');

        return redirect()->route('admin.website-management.pages.edit', $page->unique_id);
    }
}
