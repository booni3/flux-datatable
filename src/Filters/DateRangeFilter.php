<?php

namespace Booni3\FluxDatatable\Filters;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;

class DateRangeFilter extends Filter
{
    protected ?string $column = null;

    public function __construct(string $label, ?string $key = null)
    {
        parent::__construct($label, $key);
        $this->column = $key;
    }

    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function viewName(): string
    {
        return 'datatable.filters.date-range';
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        if (empty($value)) {
            return $query;
        }

        [$start, $end] = $this->parseDateRange($value);

        if (! $start || ! $end) {
            return $query;
        }

        try {
            if ($this->filterCallback) {
                ($this->filterCallback)($query, $start, $end);
            } elseif ($this->column) {
                $query->whereBetween($this->column, [
                    Carbon::parse($start)->startOfDay(),
                    Carbon::parse($end)->endOfDay(),
                ]);
            }
        } catch (InvalidFormatException) {
            // Ignore malformed date input
        }

        return $query;
    }

    protected function parseDateRange(mixed $value): array
    {
        if (is_string($value) && str_contains($value, '/')) {
            return explode('/', $value, 2);
        }

        if (is_array($value)) {
            return [
                $value['start'] ?? $value['minDate'] ?? null,
                $value['end'] ?? $value['maxDate'] ?? null,
            ];
        }

        return [null, null];
    }
}
