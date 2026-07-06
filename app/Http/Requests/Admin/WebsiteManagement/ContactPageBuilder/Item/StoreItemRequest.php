<?php

namespace App\Http\Requests\Admin\WebsiteManagement\ContactPageBuilder\Item;

use App\Models\WebsiteManagement\WebsitePageSection;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item\StoreItemRequest as BaseRequest;

class StoreItemRequest extends BaseRequest
{
    protected function getRedirectUrl(): string
    {
        $key = WebsitePageSection::where('unique_id', $this->input('section_unique_id'))
            ->value('section_key') ?? 'contact_strip';
        return route('admin.website-management.contact-builder.index', ['tab' => $key]);
    }
}
