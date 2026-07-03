<?php

namespace App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item;

use App\Helpers\PurifyHelper;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Rules\FlexibleUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function getRedirectUrl(): string
    {
        $key = $this->getSectionKey() ?? 'hero';
        return route('admin.website-management.home-builder.index', ['tab' => $key]);
    }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->except(['image'])));
    }

    public function rules(): array
    {
        return match($this->getSectionKey()) {
            'featured_rentals' => $this->featuredRentalsItemRules(),
            'feature_strip'    => $this->featureStripItemRules(),
            'footer'           => $this->footerItemRules(),
            default            => $this->defaultRules(),
        };
    }

    public function messages(): array
    {
        return match($this->getSectionKey()) {
            'featured_rentals' => [
                'content.category_id.required' => 'Please select a valid rental category.',
                'content.category_id.integer'  => 'Invalid category ID.',
                'content.category_id.exists'   => 'The selected category no longer exists or is not active.',
            ],
            'feature_strip' => $this->featureStripItemMessages(),
            'footer'        => $this->footerItemMessages(),
            default         => [],
        };
    }

    public function withValidator($validator): void
    {
        $key = $this->getSectionKey();

        if ($key === 'featured_rentals') {
            $validator->after(function ($v) {
                $categoryId = (int) $this->input('content.category_id');
                if (!$categoryId) {
                    return;
                }
                $sectionId = WebsitePageSection::where('unique_id', $this->input('section_unique_id'))
                    ->value('id');
                if (!$sectionId) {
                    return;
                }
                $duplicate = WebsiteSectionItem::where('website_page_section_id', $sectionId)
                    ->where('content->category_id', $categoryId)
                    ->exists();
                if ($duplicate) {
                    $v->errors()->add('content.category_id', 'Duplicate categories are not allowed.');
                }
            });
        }

    }

    // ── Featured Rentals item rules ───────────────────────────────────────────

    private function featuredRentalsItemRules(): array
    {
        return [
            'section_unique_id'   => ['required', 'string', 'exists:website_page_sections,unique_id'],
            'content'             => ['nullable', 'array'],
            'content.category_id' => ['required', 'integer',
                                       Rule::exists('product_categories', 'id')
                                           ->where('status', 'Published')
                                           ->whereNull('parent_id')],
            'display_order'       => ['nullable', 'integer', 'min:0'],
            'status'              => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    // ── Feature Strip item rules ───────────────────────────────────────────

    private function featureStripItemRules(): array
    {
        return [
            'section_unique_id' => ['required', 'string', 'exists:website_page_sections,unique_id'],
            'title'             => ['required', 'string', 'max:255'],
            'subtitle'          => ['nullable', 'string', 'max:255'],
            'icon'              => ['nullable', 'string', 'max:100', Rule::in(self::featureStripIconSet())],
            'status'            => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    private function featureStripItemMessages(): array
    {
        return [
            'title.required' => 'Feature title is required.',
            'title.max'      => 'Feature title may not exceed 255 characters.',
            'subtitle.max'   => 'Subtitle may not exceed 255 characters.',
            'icon.in'        => 'Please select a valid icon.',
        ];
    }

    private static function featureStripIconSet(): array
    {
        return [
            'heroicon-o-shield-check', 'heroicon-o-truck', 'heroicon-o-wrench-screwdriver',
            'heroicon-o-clock', 'heroicon-o-star', 'heroicon-o-phone', 'heroicon-o-map-pin',
            'heroicon-o-calendar-days', 'heroicon-o-check-circle', 'heroicon-o-bolt',
            'heroicon-o-cog-6-tooth', 'heroicon-o-building-office-2', 'heroicon-o-users',
            'heroicon-o-currency-dollar', 'heroicon-o-chat-bubble-left-right', 'icon-headset',
            'heroicon-o-hand-thumb-up', 'heroicon-o-home', 'heroicon-o-fire', 'heroicon-o-gift',
            'heroicon-o-light-bulb', 'heroicon-o-rocket-launch', 'heroicon-o-sparkles',
            'heroicon-o-hand-raised', 'heroicon-o-sun', 'heroicon-o-globe-alt', 'heroicon-o-heart',
            'heroicon-o-face-smile', 'heroicon-o-lifebuoy', 'heroicon-o-trophy', 'heroicon-o-banknotes',
        ];
    }

    // ── Footer item rules ─────────────────────────────────────────────────────

    private function footerItemRules(): array
    {
        $itemKey = $this->input('item_key', '');

        $base = [
            'section_unique_id' => ['required', 'string', 'exists:website_page_sections,unique_id'],
            'item_key'          => ['required', 'string', 'in:quick_link,other_link,social_link'],
            'status'            => ['nullable', 'string', 'in:Active,Inactive'],
        ];

        if ($itemKey === 'social_link') {
            return array_merge($base, [
                'button_url' => ['required', 'string', new FlexibleUrl(), 'max:500'],
                'icon'       => ['required', 'string', 'max:100', Rule::in(self::socialPlatformIconSet())],
                'title'      => ['nullable', 'string', 'max:255'],
            ]);
        }

        return array_merge($base, [
            'title'      => ['required', 'string', 'max:255'],
            'button_url' => ['required', 'string', new FlexibleUrl(), 'max:500'],
        ]);
    }

    private function footerItemMessages(): array
    {
        $itemKey = $this->input('item_key', '');

        $shared = [
            'item_key.required'   => 'Link group is required.',
            'item_key.in'         => 'Invalid link group.',
            'button_url.required' => 'Please enter a valid footer URL.',
            'button_url.max'      => 'URL may not exceed 500 characters.',
        ];

        if ($itemKey === 'social_link') {
            return array_merge($shared, [
                'icon.required' => 'Please select a valid social platform.',
                'icon.in'       => 'Please select a valid social platform.',
            ]);
        }

        return array_merge($shared, [
            'title.required' => 'Footer link title is required.',
            'title.max'      => 'Footer link title may not exceed 255 characters.',
        ]);
    }

    private static function socialPlatformIconSet(): array
    {
        return [
            'fa-facebook-f', 'fa-x-twitter', 'fa-instagram', 'fa-linkedin-in',
            'fa-youtube', 'fa-tiktok', 'fa-pinterest-p', 'fa-snapchat-ghost',
            'fa-whatsapp', 'fa-threads',
        ];
    }

    // ── Default rules (all other section items) ───────────────────────────────

    private function defaultRules(): array
    {
        return [
            'section_unique_id' => ['required', 'string', 'exists:website_page_sections,unique_id'],
            'item_key'          => ['nullable', 'string', 'max:100'],
            'title'             => ['nullable', 'string', 'max:255'],
            'subtitle'          => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'image'             => ['nullable', 'image', 'max:2048'],
            'icon'              => ['nullable', 'string', 'max:100'],
            'button_text'       => ['nullable', 'string', 'max:100'],
            'button_url'        => ['nullable', 'string', 'max:500'],
            'display_order'     => ['nullable', 'integer', 'min:0'],
            'status'            => ['nullable', 'string', 'in:Active,Inactive'],
            'content'           => ['nullable', 'array'],
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private ?string $_sectionKey = null;
    private bool $_sectionKeyLoaded = false;

    private function getSectionKey(): ?string
    {
        if (!$this->_sectionKeyLoaded) {
            $this->_sectionKey = WebsitePageSection::where('unique_id', $this->input('section_unique_id'))
                ->value('section_key');
            $this->_sectionKeyLoaded = true;
        }
        return $this->_sectionKey;
    }
}
