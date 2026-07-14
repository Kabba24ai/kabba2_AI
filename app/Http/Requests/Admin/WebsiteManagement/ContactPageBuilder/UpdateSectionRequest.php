<?php

namespace App\Http\Requests\Admin\WebsiteManagement\ContactPageBuilder;

use App\Helpers\PurifyHelper;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Rules\FlexibleUrl;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function getRedirectUrl(): string
    {
        $tab = $this->getSection()?->section_key ?? 'hero';
        return route('admin.website-management.contact-builder.index', ['tab' => $tab]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->except(['image'])));
    }

    public function rules(): array
    {
        return match($this->getSection()?->section_key) {
            'hero'          => $this->heroRules(),
            'contact_strip' => $this->contactStripRules(),
            'locations'     => $this->locationsRules(),
            'question_cta'  => $this->questionCtaRules(),
            'feature_strip' => $this->featureStripRules(),
            default         => $this->defaultRules(),
        };
    }

    public function messages(): array
    {
        return match($this->getSection()?->section_key) {
            'hero'          => $this->heroMessages(),
            'contact_strip' => $this->contactStripMessages(),
            'locations'     => $this->locationsMessages(),
            'question_cta'  => $this->questionCtaMessages(),
            'feature_strip' => $this->featureStripMessages(),
            default         => [],
        };
    }

    // ── Page Header (section_key `hero`) ─────────────────────────────────
    // The hero IMAGE is homepage-only — interior pages get a simple title
    // band, so no image/overlay/color/position fields are accepted here.

    private function heroRules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'subtitle'            => ['nullable', 'string', 'max:500'],
            'status'              => ['required', 'string', 'in:Active,Inactive'],
            'content'             => ['nullable', 'array'],
            'content.description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function heroMessages(): array
    {
        return [
            'title.required'  => 'Page header title is required.',
            'title.max'       => 'Page header title may not exceed 255 characters.',
            'subtitle.max'    => 'Subtitle may not exceed 500 characters.',
            'status.required' => 'Section status is required.',
            'status.in'       => 'Section status must be Active or Inactive.',
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
            'title.required'  => 'Section title is required.',
            'title.max'       => 'Section title may not exceed 255 characters.',
            'subtitle.max'    => 'Section subtitle may not exceed 500 characters.',
            'status.required' => 'Section status is required.',
            'status.in'       => 'Section status must be Active or Inactive.',
        ];
    }

    // ── Locations ─────────────────────────────────────────────────────────

    private function locationsRules(): array
    {
        return [
            'title'     => ['nullable', 'string', 'max:255'],
            'subtitle'  => ['nullable', 'string', 'max:500'],
            'status'    => ['required', 'string', 'in:Active,Inactive'],
            'content'   => ['nullable', 'array'],
            'content.*' => ['nullable'],
        ];
    }

    private function locationsMessages(): array
    {
        return [
            'title.max'       => 'Section heading may not exceed 255 characters.',
            'subtitle.max'    => 'Section subtitle may not exceed 500 characters.',
            'status.required' => 'Section status is required.',
            'status.in'       => 'Section status must be Active or Inactive.',
        ];
    }

    // ── Question CTA ──────────────────────────────────────────────────────

    private function questionCtaRules(): array
    {
        return [
            'title'                => ['required_if:status,Active', 'nullable', 'string', 'max:255'],
            'subtitle'             => ['nullable', 'string', 'max:500'],
            'button_text'          => ['nullable', 'string', 'max:100'],
            'status'               => ['required', 'string', 'in:Active,Inactive'],
            'content'              => ['nullable', 'array'],
            'content.phone_number' => ['required_if:status,Active', 'nullable', 'string', 'regex:/^\(\d{3}\) \d{3}-\d{4}$/'],
            'content.icon'         => ['nullable', 'string', 'max:100'],
        ];
    }

    private function questionCtaMessages(): array
    {
        return [
            'title.required_if'                    => 'CTA heading is required when the section is Active.',
            'title.max'                            => 'CTA heading may not exceed 255 characters.',
            'subtitle.max'                         => 'Description may not exceed 500 characters.',
            'button_text.max'                      => 'Button text may not exceed 100 characters.',
            'status.required'                      => 'Section status is required.',
            'status.in'                            => 'Section status must be Active or Inactive.',
            'content.phone_number.required_if'     => 'A phone number is required when the section is Active.',
            'content.phone_number.regex'           => 'Phone number must be in (555) 555-5555 format.',
        ];
    }

    // ── Feature Strip ────────────────────────────────────────────────────

    private function featureStripRules(): array
    {
        return [
            'title'     => ['nullable', 'string', 'max:255'],
            'subtitle'  => ['nullable', 'string', 'max:500'],
            'status'    => ['required', 'string', 'in:Active,Inactive'],
            'content'   => ['nullable', 'array'],
            'content.*' => ['nullable'],
        ];
    }

    private function featureStripMessages(): array
    {
        return [
            'title.max'       => 'Section title may not exceed 255 characters.',
            'subtitle.max'    => 'Section subtitle may not exceed 500 characters.',
            'status.required' => 'Section status is required.',
            'status.in'       => 'Section status must be Active or Inactive.',
        ];
    }

    // ── Default ───────────────────────────────────────────────────────────

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
