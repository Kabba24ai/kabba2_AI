<?php

namespace App\Http\Requests\Admin\WebsiteManagement\ContactPageBuilder\Item;

use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item\UpdateItemRequest as BaseRequest;

class UpdateItemRequest extends BaseRequest
{
    protected function getRedirectUrl(): string
    {
        $item = WebsiteSectionItem::with('section')
            ->where('unique_id', $this->route('unique_id'))
            ->first();
        $tab = $item?->section?->section_key ?? 'contact_strip';
        return route('admin.website-management.contact-builder.index', ['tab' => $tab]);
    }
}
