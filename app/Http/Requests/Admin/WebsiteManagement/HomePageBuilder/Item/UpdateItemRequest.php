<?php

namespace App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item;

use App\Helpers\PurifyHelper;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Rules\FlexibleUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function getRedirectUrl(): string
    {
        $tab = $this->getItem()?->section?->section_key ?? 'hero';
        return route('admin.website-management.home-builder.index', ['tab' => $tab]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->except(['image'])));
    }

    public function rules(): array
    {
        $item = $this->getItem();

        return match($item?->section?->section_key) {
            'contact_strip' => $this->contactStripItemRules($item->item_key),
            'feature_strip' => $this->featureStripItemRules(),
            'footer'        => $this->footerItemRules($item->item_key ?? ''),
            default         => $this->defaultRules(),
        };
    }

    public function messages(): array
    {
        $item = $this->getItem();

        return match($item?->section?->section_key) {
            'contact_strip' => $this->contactStripItemMessages($item->item_key),
            'feature_strip' => $this->featureStripItemMessages(),
            'footer'        => $this->footerItemMessages($item->item_key ?? ''),
            default         => [],
        };
    }

    public function withValidator($validator): void
    {
        $item       = $this->getItem();
        $sectionKey = $item?->section?->section_key;

    }

    // ── Contact Strip item rules ──────────────────────────────────────────

    private function contactStripItemRules(string $itemKey): array
    {
        return match(true) {
            $itemKey === 'phone_card'                    => $this->phoneCardRules(),
            in_array($itemKey, ['store_1', 'store_2'])   => $this->storeCardRules(),
            default                                      => $this->searchCardRules(),
        };
    }

    private function phoneCardRules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'subtitle'    => ['required', 'string', 'regex:/^\(\d{3}\) \d{3}-\d{4}$/', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    private function storeCardRules(): array
    {
        return [
            'content'          => ['nullable', 'array'],
            'content.store_id' => ['required', 'integer', Rule::exists('stores', 'id')],
            'status'           => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    private function searchCardRules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'subtitle'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'button_text' => ['required', 'string', 'max:100'],
            'button_url'  => ['nullable', new FlexibleUrl(), 'max:500'],
            'status'      => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    // ── Contact Strip custom messages ─────────────────────────────────────

    private function contactStripItemMessages(string $itemKey): array
    {
        $shared = [
            'title.required'    => 'Card title is required.',
            'title.max'         => 'Card title may not exceed 255 characters.',
            'description.max'   => 'Description may not exceed 500 characters.',
            'status.in'         => 'Status must be Active or Inactive.',
        ];

        return match(true) {
            $itemKey === 'phone_card' => array_merge($shared, [
                'subtitle.required' => 'Phone number is required.',
                'subtitle.regex'    => 'Phone number must be in (555) 555-5555 format.',
                'subtitle.max'      => 'Phone number may not exceed 50 characters.',
            ]),
            in_array($itemKey, ['store_1', 'store_2']) => [
                'content.store_id.required' => 'Please select a store.',
                'content.store_id.integer'  => 'Invalid store selection.',
                'content.store_id.exists'   => 'The selected store no longer exists.',
                'status.in'                 => 'Status must be Active or Inactive.',
            ],
            default => array_merge($shared, [
                'subtitle.max'         => 'Subtitle may not exceed 255 characters.',
                'button_text.required' => 'Button text is required.',
                'button_text.max'      => 'Button text may not exceed 100 characters.',
            ]),
        };
    }

    // ── Feature Strip item rules ──────────────────────────────────────────

    private function featureStripItemRules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'icon'     => ['nullable', 'string', 'max:100', Rule::in(self::featureStripIconSet())],
            'status'   => ['required', 'string', 'in:Active,Inactive'],
        ];
    }

    private function featureStripItemMessages(): array
    {
        return [
            'title.required'  => 'Feature title is required.',
            'title.max'       => 'Feature title may not exceed 255 characters.',
            'subtitle.max'    => 'Subtitle may not exceed 255 characters.',
            'icon.in'         => 'Please select a valid icon.',
            'status.required' => 'Feature status is required.',
            'status.in'       => 'Feature status must be Active or Inactive.',
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

    // ── Footer item rules ─────────────────────────────────────────────────

    private function footerItemRules(string $itemKey): array
    {
        return match(true) {
            $itemKey === 'social_link'                           => $this->footerSocialRules(),
            in_array($itemKey, ['quick_link', 'other_link'])     => $this->footerLinkRules(),
            default                                              => $this->footerLinkRules(),
        };
    }

    private function footerLinkRules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:255'],
            'button_url' => ['required', 'string', new FlexibleUrl(), 'max:500'],
            'status'     => ['required', 'string', 'in:Active,Inactive'],
        ];
    }

    private function footerSocialRules(): array
    {
        return [
            'button_url' => ['required', 'string', new FlexibleUrl(), 'max:500'],
            'icon'       => ['required', 'string', 'max:100', Rule::in(self::socialPlatformIconSet())],
            'title'      => ['nullable', 'string', 'max:255'],
            'status'     => ['required', 'string', 'in:Active,Inactive'],
        ];
    }

    private function footerItemMessages(string $itemKey): array
    {
        $shared = [
            'button_url.required' => 'Please enter a valid footer URL.',
            'button_url.max'      => 'URL may not exceed 500 characters.',
            'status.required'     => 'Link status is required.',
            'status.in'           => 'Status must be Active or Inactive.',
        ];

        if ($itemKey === 'social_link') {
            return array_merge($shared, [
                'icon.required' => 'Please select a valid social platform.',
                'icon.in'       => 'Please select a valid social platform.',
                'icon.max'      => 'Platform identifier may not exceed 100 characters.',
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

    // ── Default rules (all other builder items) ───────────────────────────

    private function defaultRules(): array
    {
        return [
            'item_key'      => ['nullable', 'string', 'max:100'],
            'title'         => ['nullable', 'string', 'max:255'],
            'subtitle'      => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'image'         => ['nullable', 'image', 'max:2048'],
            'icon'          => ['nullable', 'string', 'max:100'],
            'button_text'   => ['nullable', 'string', 'max:100'],
            'button_url'    => ['nullable', new FlexibleUrl(), 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'string', 'in:Active,Inactive'],
            'content'       => ['nullable', 'array'],
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private ?WebsiteSectionItem $_item = null;
    private bool $_itemLoaded = false;

    private function getItem(): ?WebsiteSectionItem
    {
        if (!$this->_itemLoaded) {
            $this->_item = WebsiteSectionItem::with('section')
                ->where('unique_id', $this->route('unique_id'))
                ->first();
            $this->_itemLoaded = true;
        }
        return $this->_item;
    }
}
