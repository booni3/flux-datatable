<?php

namespace Booni3\FluxDatatable\Tests\Fixtures;

use Booni3\FluxDatatable\Column;
use Booni3\FluxDatatable\DataTable;
use Booni3\FluxDatatable\Filters\DateRangeFilter;
use Booni3\FluxDatatable\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ItemsTable extends DataTable
{
    public bool $withDefaultStatus = false;

    public bool $withBulk = false;

    protected int $perPage = 3;

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->sortable()->searchable(),
            Column::make('Status', 'status')->sortable(),
        ];
    }

    public function builder(): Builder
    {
        return Item::query();
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')->options(['' => 'All', 'active' => 'Active', 'archived' => 'Archived'])
                ->filter(fn (Builder $query, $value) => $query->where('status', $value))
                ->setFilterDefaultValue($this->withDefaultStatus ? 'active' : ''),
            DateRangeFilter::make('Received', 'received_at'),
        ];
    }

    public function bulkActions(): array
    {
        return $this->withBulk ? ['archive' => 'Archive'] : [];
    }

    public function rowUrl(mixed $row): ?string
    {
        return '/items/'.$row->id;
    }

    public function archive(): void {}
}
