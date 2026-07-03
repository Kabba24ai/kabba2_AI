<?php

namespace App\Services\Website\Components;

use App\Contracts\Website\SectionComponentInterface;

abstract class AbstractSectionComponent implements SectionComponentInterface
{
    public function icon(): string        { return 'heroicon-o-squares-2x2'; }
    public function description(): string { return ''; }
    public function previewImage(): ?string { return null; }
    public function defaultConfig(): array  { return []; }
    public function configSchema(): array   { return []; }
    public function validationRules(): array { return []; }
    public function sortableIds(): array    { return []; }
    public function defaultData(): array    { return []; }
    public function showInPicker(): bool    { return true; }

    protected function section(array $context): mixed
    {
        return $context['sections']->get($this->key());
    }

    protected function items(array $context): mixed
    {
        return $context['itemsByKey']->get($this->key(), collect());
    }
}
