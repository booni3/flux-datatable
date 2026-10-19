<?php

namespace Booni3\FluxDatatable\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    public string $label;

    public string $key;

    public ?Closure $filterCallback = null;

    public mixed $defaultValue = '';

    protected bool $inline = false;

    public function __construct(string $label, ?string $key = null)
    {
        $this->label = $label;
        $this->key = $key ?? str($label)->snake()->toString();
    }

    public static function make(string $label, ?string $key = null): static
    {
        return new static($label, $key); // @phpstan-ignore new.static
    }

    public function filter(Closure $callback): static
    {
        $this->filterCallback = $callback;

        return $this;
    }

    public function setFilterDefaultValue(mixed $value): static
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        if ($this->filterCallback && $value !== '' && $value !== null) {
            ($this->filterCallback)($query, $value);
        }

        return $query;
    }

    public function inline(): static
    {
        $this->inline = true;

        return $this;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    abstract public function viewName(): string;

    public function viewData(string $wireModel): array
    {
        return [
            'wireModel' => $wireModel,
            'label' => $this->label,
        ];
    }
}
