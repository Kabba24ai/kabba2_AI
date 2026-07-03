<?php

namespace App\Contracts\Website;

interface SectionComponentInterface
{
    /** Unique snake_case key matching the section_key column */
    public function key(): string;

    /** Human-readable name shown in builder tabs and future Add-Section UI */
    public function displayName(): string;

    /** Heroicon name (e.g. 'heroicon-o-photo') used in future picker UI */
    public function icon(): string;

    /** One-line description for the future Add-Section picker */
    public function description(): string;

    /** Blade view path for the admin edit partial */
    public function adminView(): string;

    /** Path to a static preview image (null until images are added) */
    public function previewImage(): ?string;

    /** Default JSON config values written when the section is first created */
    public function defaultConfig(): array;

    /** JSON Schema-style definition of every configurable field */
    public function configSchema(): array;

    /** Laravel validation rules for this component's input fields */
    public function validationRules(): array;

    /**
     * Extract the variables this component's admin partial needs.
     *
     * $context keys: page, sections, itemsByKey, categories,
     *                selectedCategoryIds, stores, allSectionsOrdered
     */
    public function viewData(array $context): array;

    /** SortableJS container element IDs that belong to this component */
    public function sortableIds(): array;

    /**
     * Default seed data when a new section is created via the Add-Section picker.
     *
     * Return format:
     *   'section' => ['title' => ..., 'subtitle' => ..., 'content' => [...]]
     *   'items'   => [['item_key' => ..., 'title' => ..., 'display_order' => 1, ...], ...]
     */
    public function defaultData(): array;

    /** Whether this component appears in the Add-Section picker (false for internal/meta components). */
    public function showInPicker(): bool;
}
