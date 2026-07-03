<?php

namespace App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder;

use App\Helpers\PurifyHelper;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Rules\FlexibleUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function getRedirectUrl(): string
    {
        $tab = $this->getSection()?->section_key ?? 'hero';
        return route('admin.website-management.home-builder.index', ['tab' => $tab]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->except(['image'])));
    }

    public function rules(): array
    {
        return match($this->getSection()?->section_key) {
            'hero'              => $this->heroRules(),
            'contact_strip'     => $this->contactStripRules(),
            'featured_rentals'  => $this->featuredRentalsRules(),
            'feature_strip'     => $this->featureStripRules(),
            'footer'            => $this->footerRules(),
            default             => $this->defaultRules(),
        };
    }

    public function messages(): array
    {
        return match($this->getSection()?->section_key) {
            'hero'              => $this->heroMessages(),
            'contact_strip'     => $this->contactStripMessages(),
            'featured_rentals'  => $this->featuredRentalsMessages(),
            'feature_strip'     => $this->featureStripMessages(),
            'footer'            => $this->footerMessages(),
            default             => [],
        };
    }

    // ── Hero ──────────────────────────────────────────────────────────────

    private function heroRules(): array
    {
        return [
            'title'                       => ['required', 'string', 'max:255'],
            'subtitle'                    => ['nullable', 'string', 'max:500'],
            'image'                       => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'media_id'                    => ['nullable', 'integer', Rule::exists('media', 'id')],
            'status'                      => ['nullable', 'string', 'in:Active,Inactive'],
            'content'                     => ['nullable', 'array'],
            'content.overlay_enabled'     => ['nullable', 'boolean'],
            'content.overlay_opacity'     => [
                'required_if:content.overlay_enabled,1',
                'nullable', 'numeric', 'min:0', 'max:100',
            ],
            'content.title_position'      => ['nullable', 'string', 'in:left,center,right'],
            'content.title_color'         => ['nullable', 'string', 'max:20'],
            'content.subtitle_position'   => ['nullable', 'string', 'in:left,center,right'],
            'content.subtitle_color'      => ['nullable', 'string', 'max:20'],
        ];
    }

    private function heroMessages(): array
    {
        return [
            'title.required'                      => 'Hero title is required.',
            'title.max'                           => 'Hero title may not exceed 255 characters.',
            'subtitle.max'                        => 'Subtitle may not exceed 500 characters.',
            'image.mimes'                         => 'Background image must be a JPG, PNG, WebP, or SVG file.',
            'image.max'                           => 'Background image may not exceed 4 MB.',
            'media_id.integer'                    => 'Invalid media selection — please choose again.',
            'media_id.exists'                     => 'The selected image no longer exists in the media library.',
            'content.overlay_opacity.required_if' => 'Overlay opacity is required when the overlay is enabled.',
            'content.overlay_opacity.numeric'     => 'Overlay opacity must be a number.',
            'content.overlay_opacity.min'         => 'Overlay opacity must be between 0 and 100.',
            'content.overlay_opacity.max'         => 'Overlay opacity must be between 0 and 100.',
        ];
    }

    // ── Contact Strip ─────────────────────────────────────────────────────

    private function contactStripRules(): array
    {
        return [
            'title'     => ['required', 'string', 'max:255'],
            'subtitle'  => ['nullable', 'string', 'max:500'],
            'status'    => ['required', 'string', 'in:Active,Inactive'],
            'content'   => ['nullable', 'array'],
            'content.*' => ['nullable'],
        ];
    }

    private function contactStripMessages(): array
    {
        return [
            'title.required' => 'Section title is required.',
            'title.max'      => 'Section title may not exceed 255 characters.',
            'subtitle.max'   => 'Section subtitle may not exceed 500 characters.',
            'status.required'=> 'Section status is required.',
            'status.in'      => 'Section status must be Active or Inactive.',
        ];
    }

    // ── Feature Strip ────────────────────────────────────────────────────────

    private function featureStripRules(): array
    {
        return [
            'title'     => ['nullable', 'string', 'max:255'],
            'subtitle'  => ['nullable', 'string', 'max:500'],
            'status'    => ['nullable', 'string', 'in:Active,Inactive'],
            'content'   => ['nullable', 'array'],
            'content.*' => ['nullable'],
        ];
    }

    private function featureStripMessages(): array
    {
        return [
            'title.max'    => 'Section title may not exceed 255 characters.',
            'subtitle.max' => 'Section subtitle may not exceed 500 characters.',
            'status.in'    => 'Section status must be Active or Inactive.',
        ];
    }

    // ── Footer ────────────────────────────────────────────────────────────────

    private function footerRules(): array
    {
        return [
            'status'                  => ['nullable', 'string', 'in:Active,Inactive'],
            'content'                 => ['nullable', 'array'],
            'content.*'               => ['nullable'],
            'content.copyright_text'  => ['nullable', 'string', 'max:500'],
            'content.powered_by_text' => ['nullable', 'string', 'max:255'],
            'content.powered_by_url'  => ['nullable', new FlexibleUrl(), 'max:500'],
        ];
    }

    private function footerMessages(): array
    {
        return [
            'content.copyright_text.max'  => 'Copyright text may not exceed 500 characters.',
            'content.powered_by_text.max' => 'Powered by text may not exceed 255 characters.',
            'status.in'                   => 'Section status must be Active or Inactive.',
        ];
    }

    // ── Featured Rentals ──────────────────────────────────────────────────────

    private function featuredRentalsRules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'subtitle'     => ['nullable', 'string', 'max:500'],
            'status'       => ['nullable', 'string', 'in:Active,Inactive'],
            'content'      => ['nullable', 'array'],
            'content.*'    => ['nullable'],
            'categories'   => ['nullable', 'array'],
            'categories.*' => ['required', 'integer', 'distinct',
                               Rule::exists('product_categories', 'id')
                                   ->where('status', 'Published')
                                   ->whereNull('parent_id')],
        ];
    }

    private function featuredRentalsMessages(): array
    {
        return [
            'title.required'        => 'Section title is required.',
            'title.max'             => 'Section title may not exceed 255 characters.',
            'subtitle.max'          => 'Section subtitle may not exceed 500 characters.',
            'categories.array'      => 'Invalid category selection.',
            'categories.*.required' => 'Please select a valid rental category.',
            'categories.*.integer'  => 'Invalid category ID.',
            'categories.*.distinct' => 'Duplicate categories are not allowed.',
            'categories.*.exists'   => 'The selected category no longer exists or is not active.',
        ];
    }

    public function withValidator($validator): void
    {
        if ($this->getSection()?->section_key !== 'featured_rentals') {
            return;
        }
        $validator->after(function ($v) {
            $cats = $this->input('categories');
            if (is_array($cats) && count($cats) !== count(array_unique(array_map('intval', $cats)))) {
                $v->errors()->add('categories', 'Duplicate categories are not allowed.');
            }
        });
    }

    // ── Default (all other sections) ──────────────────────────────────────

    private function defaultRules(): array
    {
        return [
            'title'       => ['nullable', 'string', 'max:255'],
            'subtitle'    => ['nullable', 'string', 'max:500'],
            'image'       => ['nullable', 'image', 'max:4096'],
            'media_id'    => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url'  => ['nullable', new FlexibleUrl(), 'max:500'],
            'status'      => ['nullable', 'string', 'in:Active,Inactive'],
            'content'     => ['nullable', 'array'],
            'content.*'   => ['nullable'],
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private ?WebsitePageSection $_section = null;
    private bool $_sectionLoaded = false;

    private function getSection(): ?WebsitePageSection
    {
        if (!$this->_sectionLoaded) {
            $this->_section = WebsitePageSection::where('unique_id', $this->route('unique_id'))->first();
            $this->_sectionLoaded = true;
        }
        return $this->_section;
    }
}
