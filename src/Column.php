<?php

namespace Booni3\FluxDatatable;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class Column
{
    public string $label;

    public ?string $field;

    public bool $hidden = false;

    public bool $sortable = false;

    public bool $searchable = false;

    public ?string $viewName = null;

    public ?Closure $formatCallback = null;

    public ?Closure $labelCallback = null;

    public ?Closure $sortCallback = null;

    public ?Closure $searchCallback = null;

    public function __construct(string $label, ?string $field = null)
    {
        $this->label = $label;
        $this->field = $field;
    }

    public static function make(string $label, ?string $field = null): static
    {
        return new static($label, $field); // @phpstan-ignore new.static
    }

    public function hideIf(bool $condition): static
    {
        $this->hidden = $condition;

        return $this;
    }

    public function sortable(Closure|bool $callback = true): static
    {
        if ($callback instanceof Closure) {
            $this->sortCallback = $callback;
            $this->sortable = true;
        } else {
            $this->sortable = $callback;
        }

        return $this;
    }

    public function searchable(Closure|bool $callback = true): static
    {
        if ($callback instanceof Closure) {
            $this->searchCallback = $callback;
            $this->searchable = true;
        } else {
            $this->searchable = $callback;
        }

        return $this;
    }

    public function view(string $viewName): static
    {
        $this->viewName = $viewName;

        return $this;
    }

    public function format(Closure $callback): static
    {
        $this->formatCallback = $callback;

        return $this;
    }

    public function label(Closure $callback): static
    {
        $this->labelCallback = $callback;

        return $this;
    }

    public function isVisible(): bool
    {
        return ! $this->hidden;
    }

    public function resolveValue(mixed $row): mixed
    {
        if ($this->viewName) {
            return null;
        }

        if ($this->labelCallback) {
            return ($this->labelCallback)($row);
        }

        if ($this->formatCallback) {
            $value = data_get($row, $this->field);

            return ($this->formatCallback)($value, $row, $this);
        }

        if ($this->field) {
            return data_get($row, $this->field);
        }

        return null;
    }

    public function applySort(Builder $query, string $direction): Builder
    {
        if ($this->sortCallback) {
            ($this->sortCallback)($query, $direction);

            return $query;
        }

        if ($this->field === null) {
            return $query;
        }

        return $query->orderBy($this->field, $direction);
    }

    public function applySearch(Builder $query, string $searchTerm): void
    {
        if ($this->searchCallback) {
            ($this->searchCallback)($query, $searchTerm);

            return;
        }

        if ($this->field === null) {
            return;
        }

        $query->orWhere($this->field, 'LIKE', "%{$searchTerm}%");
    }
}
