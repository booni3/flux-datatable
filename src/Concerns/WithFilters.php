<?php

namespace Booni3\FluxDatatable\Concerns;

use Livewire\Attributes\Url;

trait WithFilters
{
    #[Url]
    public array $filterValues = [];

    public function mountWithFilters(): void
    {
        $filters = method_exists($this, 'getResolvedFilters') ? $this->getResolvedFilters() : $this->filters();
        foreach ($filters as $filter) {
            if (! isset($this->filterValues[$filter->key]) && $filter->defaultValue !== '') {
                $this->filterValues[$filter->key] = $filter->defaultValue;
            }
        }
    }

    public function updatedFilterValues(): void
    {
        $this->resetTablePage();
    }

    public function resetFilters(): void
    {
        $this->filterValues = [];
        $this->mountWithFilters();
        $this->resetTablePage();
    }

    public function getAppliedFiltersWithValues(): array
    {
        $applied = [];
        $filters = method_exists($this, 'getResolvedFilters') ? $this->getResolvedFilters() : $this->filters();

        foreach ($filters as $filter) {
            $value = $this->filterValues[$filter->key] ?? '';
            if ($value !== '' && $value !== null) {
                $applied[$filter->key] = $value;
            }
        }

        return $applied;
    }

    public function getInlineFilters(): array
    {
        $filters = method_exists($this, 'getResolvedFilters') ? $this->getResolvedFilters() : $this->filters();

        return array_values(array_filter($filters, fn ($filter) => $filter->isInline()));
    }

    public function getDropdownFilters(): array
    {
        $filters = method_exists($this, 'getResolvedFilters') ? $this->getResolvedFilters() : $this->filters();

        return array_values(array_filter($filters, fn ($filter) => ! $filter->isInline()));
    }

    public function hasActiveFilters(): bool
    {
        $filters = method_exists($this, 'getResolvedFilters') ? $this->getResolvedFilters() : $this->filters();

        foreach ($filters as $filter) {
            $value = $this->filterValues[$filter->key] ?? '';
            if ($value !== '' && $value !== null) {
                if ($value !== $filter->defaultValue) {
                    return true;
                }
            }
        }

        return false;
    }
}
