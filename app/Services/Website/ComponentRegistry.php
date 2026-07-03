<?php

namespace App\Services\Website;

use App\Contracts\Website\SectionComponentInterface;

class ComponentRegistry
{
    protected array $components = [];

    public function register(SectionComponentInterface $component): static
    {
        $this->components[$component->key()] = $component;
        return $this;
    }

    /** @return array<string, SectionComponentInterface> */
    public function all(): array
    {
        return $this->components;
    }

    public function find(string $key): ?SectionComponentInterface
    {
        return $this->components[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->components[$key]);
    }

    /** @return string[] */
    public function keys(): array
    {
        return array_keys($this->components);
    }

    /** All SortableJS container IDs declared across every registered component */
    public function allSortableIds(): array
    {
        return collect($this->components)
            ->flatMap(fn ($c) => $c->sortableIds())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Components that should appear in the Add-Section picker.
     * Excludes internal/meta components (Branding, SEO, etc.).
     *
     * @return array<string, SectionComponentInterface>
     */
    public function forPicker(): array
    {
        return array_filter($this->components, fn ($c) => $c->showInPicker());
    }
}
