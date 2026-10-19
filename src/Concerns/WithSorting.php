<?php

namespace Booni3\FluxDatatable\Concerns;

use Livewire\Attributes\Url;

trait WithSorting
{
    #[Url]
    public string $sortBy = '';

    #[Url]
    public string $sortDirection = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetTablePage();
    }

    public function hasSort(): bool
    {
        return $this->sortBy !== '';
    }
}
