# booni3/flux-datatable

A Livewire 3/4 data table built on Flux Pro 2. Requires PHP 8.2, Laravel 11 or 12, `livewire/flux` and `livewire/flux-pro`.

## Install

Add the repository and the Flux Pro repository to the app `composer.json`:

```json
"repositories": [
    {"type": "composer", "url": "https://composer.fluxui.dev"},
    {"type": "vcs", "url": "git@github.com:booni3/flux-datatable.git"}
]
```

```bash
composer require booni3/flux-datatable
```

The service provider is auto-discovered. It adds the package `resources/views` as a fallback view location. A file at the same path in the app `resources/views` wins. To copy the views into the app:

```bash
php artisan vendor:publish --tag=flux-datatable-views
```

## Example

```php
use Booni3\FluxDatatable\Column;
use Booni3\FluxDatatable\DataTable;
use Booni3\FluxDatatable\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class SuppliersTable extends DataTable
{
    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->sortable()->searchable(),
            Column::make('Status', 'status')->sortable(),
        ];
    }

    public function builder(): Builder
    {
        return Supplier::query();
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options(['' => 'All', 'active' => 'Active'])
                ->filter(fn (Builder $query, $value) => $query->where('status', $value)),
        ];
    }
}
```

Use it as `<livewire:suppliers-table />`.

## Hooks and properties

Methods to override:

- `columns()` (required) and `builder()` (required)
- `filters()`, `bulkActions()` (method name => label)
- `rowAttributes($row)`, `rowUrl($row)`, `rowUrlTarget($row)`, `cellAttributes($column, $row)` (class merges, other attributes pass through)
- `mobileRowView()`, `hasSearch()`, `placeholder()`
- `afterRowsFetched($rows)`, `applySearch()`, `applySorting()`, `applyFilters()`

Protected properties:

- `$perPage` (15), `$primaryKey` ('id'), `$paginationMethod` ('simplePaginate' or 'paginate')
- `$defaultSortBy` ('id'), `$defaultSortDirection` ('desc')
- `$refreshInterval` (seconds, null for no polling), `$mobileRowView`
- `$pageName` (null for `page`; set it so two tables on one page keep separate pages), `$paginate` (true; false returns all rows with no pagination)
- `$emptyMessage` ('No records found.'), `$searchPlaceholder` ('Search...')
- `$maxSelectableRows` (5000)

URL state: `search`, `sortBy`, `sortDirection` and `filterValues[key]`.

Column: `Column::make($label, $field)` with `sortable()`, `searchable()` (both accept a closure), `hideIf()`, `format()`, `label()`, `view()`.

Filters: `SelectFilter` (`options()`, `searchable()`), `TextFilter`, `DateRangeFilter` (`column()`). All accept `filter()`, `setFilterDefaultValue()` and `inline()`.

Toolbar slots are public properties on the table class. Each holds a Blade view name. The `data-table` view includes the view when the property is set (`isset()`). `beforeToolbar` renders above the card. `toolbarStart` renders at the left start of the toolbar. `toolbarEnd` renders at the right end of the toolbar. The included view is a normal Blade view, so it can call Livewire actions on the table.

```php
public ?string $beforeToolbar = 'tables.suppliers.config-panel';
public ?string $toolbarStart = 'tables.suppliers.toolbar-filters';
public ?string $toolbarEnd = 'tables.suppliers.toolbar-buttons';
```

Optional `$beforeToolbarData`, `$toolbarStartData` and `$toolbarEndData` arrays are passed to the included view when they are set. A table can also pass a slot as view data from `render()`: `return view('datatable.data-table', ['beforeToolbar' => 'tables.suppliers.config-panel']);`.

## App-wide Flux overrides

The package ships `flux/table/column`, `flux/table/columns` and `flux/table/cell`. Because the app view path wins over Flux, these replace the Flux table views for every `flux:table` in the app. They use the shared design tokens (`bg-ground`, `bg-surface`, `border-line`, `text-muted`), so the app needs the shared theme. Cells set no text colour, so body text inherits the page colour (Flux's own cell would make it muted grey). Do not re-publish them. The package registers its path ahead of Flux's stubs, so these views also win over an app's own `resources/views/flux/table` overrides (one table style).

## What stays in the app

- English strings. There is no translation layer.
- App-specific traits such as search engines (for example Scout) and next/previous navigation. Use the `afterRowsFetched` hook.
- Cell, toolbar and mobile row views.

## Tests

```bash
composer install
vendor/bin/pest
```
