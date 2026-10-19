<?php

namespace Booni3\FluxDatatable;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Booni3\FluxDatatable\Concerns\WithBulkActions;
use Booni3\FluxDatatable\Concerns\WithFilters;
use Booni3\FluxDatatable\Concerns\WithSorting;

/** @property-read Paginator|Collection $rows */
abstract class DataTable extends Component
{
    use WithBulkActions, WithFilters, WithPagination, WithSorting;

    #[Url]
    public string $search = '';

    protected int $perPage = 15;

    protected string $primaryKey = 'id';

    protected string $paginationMethod = 'simplePaginate';

    protected ?string $pageName = null;

    protected bool $paginate = true;

    protected string $emptyMessage = 'No records found.';

    protected string $searchPlaceholder = 'Search...';

    protected string $defaultSortBy = 'id';

    protected string $defaultSortDirection = 'desc';

    protected ?int $refreshInterval = null;

    protected ?string $mobileRowView = null;

    abstract public function columns(): array;

    abstract public function builder(): Builder;

    public function filters(): array
    {
        return [];
    }

    public function getResolvedColumns(): array
    {
        return once(fn () => $this->columns());
    }

    public function getResolvedFilters(): array
    {
        return once(fn () => $this->filters());
    }

    public function bulkActions(): array
    {
        return [];
    }

    public function rowAttributes(mixed $row): array
    {
        return [];
    }

    public function rowUrl(mixed $row): ?string
    {
        return null;
    }

    public function rowUrlTarget(mixed $row): string
    {
        return '_self';
    }

    public function cellAttributes(Column $column, mixed $row): array
    {
        return [];
    }

    public function mobileRowView(): ?string
    {
        return $this->mobileRowView;
    }

    public function updatedSearch(): void
    {
        $this->resetTablePage();
    }

    public function resetTablePage(): void
    {
        $this->resetPage($this->pageName ?? 'page');
    }

    public function placeholder()
    {
        return view('datatable.skeleton');
    }

    #[Computed]
    public function rows(): Paginator|Collection
    {
        $query = $this->buildBaseQuery();

        $rows = $this->executePagination($query);

        $this->afterRowsFetched($rows);

        return $rows;
    }

    public function buildBaseQuery(): Builder
    {
        $query = $this->builder();

        $this->applyFilters($query);
        $this->applySearch($query);
        $this->applySorting($query);

        return $query;
    }

    protected function applyFilters(Builder $query): void
    {
        foreach ($this->getResolvedFilters() as $filter) {
            $value = $this->filterValues[$filter->key] ?? '';
            $filter->apply($query, $value);
        }
    }

    protected function applySearch(Builder $query): void
    {
        if (empty($this->search)) {
            return;
        }

        $searchableColumns = collect($this->getResolvedColumns())->filter(fn (Column $col) => $col->searchable);

        if ($searchableColumns->isEmpty()) {
            return;
        }

        $query->where(function (Builder $query) use ($searchableColumns) {
            foreach ($searchableColumns as $column) {
                $column->applySearch($query, $this->search);
            }
        });
    }

    protected function applySorting(Builder $query): void
    {
        if ($this->hasSort()) {
            if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
                $this->sortDirection = 'asc';
            }

            $column = collect($this->getResolvedColumns())->first(fn (Column $col) => $col->field === $this->sortBy && $col->sortable);

            if ($column) {
                $query->reorder();
                $column->applySort($query, $this->sortDirection);

                return;
            }
        }

        if ($this->defaultSortBy) {
            $query->reorder();
            $query->orderBy($this->defaultSortBy, $this->defaultSortDirection);
        }
    }

    protected function executePagination(Builder $query): Paginator|Collection
    {
        if (! $this->paginate) {
            return $query->get();
        }

        $pageName = $this->pageName ?? 'page';

        if ($this->paginationMethod === 'paginate') {
            return $query->paginate($this->perPage, pageName: $pageName);
        }

        return $query->simplePaginate($this->perPage, pageName: $pageName);
    }

    protected function afterRowsFetched(Paginator|Collection $rows): void
    {
        // Hook for traits like NextAndPreviousLinks
    }

    public function getVisibleColumns(): Collection
    {
        return collect($this->getResolvedColumns())->filter(fn (Column $col) => $col->isVisible());
    }

    public function hasSearchableColumns(): bool
    {
        return collect($this->getResolvedColumns())->contains(fn (Column $col) => $col->searchable);
    }

    public function hasSearch(): bool
    {
        return $this->hasSearchableColumns();
    }

    public function clickTableRow(string $route): void
    {
        if (! str_starts_with($route, '/') && ! str_starts_with($route, url('/'))) {
            return;
        }

        $this->redirect($route);
    }

    public function baseQuery(): Builder
    {
        return $this->buildBaseQuery();
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getQualifiedPrimaryKey(): string
    {
        return $this->builder()->getModel()->getTable().'.'.$this->primaryKey;
    }

    public function getRows(): Paginator|Collection
    {
        return $this->rows;
    }

    public function render()
    {
        return view('datatable.data-table');
    }
}
