<?php

namespace Booni3\FluxDatatable\Filters;

class SelectFilter extends Filter
{
    public array $options = [];

    public bool $searchable = false;

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function viewName(): string
    {
        return 'datatable.filters.select';
    }

    public function viewData(string $wireModel): array
    {
        return [
            ...parent::viewData($wireModel),
            'options' => $this->options,
            'searchable' => $this->searchable,
        ];
    }
}
