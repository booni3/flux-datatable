<?php

namespace Booni3\FluxDatatable\Concerns;

trait WithBulkActions
{
    public array $selected = [];

    public bool $selectAll = false;

    public bool $selectPage = false;

    protected int $maxSelectableRows = 5000;

    public function updatedSelectPage(bool $value): void
    {
        $this->selected = $value
            ? $this->rows->pluck($this->primaryKey)->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    public function updatedSelected(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
    }

    public function selectAllRows(): void
    {
        $this->selectAll = true;
        $this->selected = $this->buildBaseQuery()
            ->limit($this->maxSelectableRows)
            ->pluck($this->getQualifiedPrimaryKey())
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    public function deselectAll(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
        $this->selected = [];
    }

    public function getSelected(): array
    {
        return $this->selected;
    }

    public function hasBulkActions(): bool
    {
        return ! empty($this->bulkActions());
    }

    public function hasSelection(): bool
    {
        return ! empty($this->selected);
    }
}
