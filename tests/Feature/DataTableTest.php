<?php

use Booni3\FluxDatatable\Tests\Fixtures\ConfigurableItemsTable;
use Booni3\FluxDatatable\Tests\Fixtures\Item;
use Booni3\FluxDatatable\Tests\Fixtures\ItemsTable;
use Livewire\Livewire;

beforeEach(function () {
    Item::create(['name' => 'Anvil', 'status' => 'active', 'received_at' => '2026-01-10']);
    Item::create(['name' => 'Bolt', 'status' => 'archived', 'received_at' => '2026-02-10']);
    Item::create(['name' => 'Chain', 'status' => 'active', 'received_at' => '2026-03-10']);
});

it('renders the table', function () {
    Livewire::test(ItemsTable::class)
        ->assertSee('Anvil')
        ->assertSee('Bolt')
        ->assertSee('Chain')
        ->assertSee('Name')
        ->assertSee('Search table', false)
        ->assertSee('Filters');
});

it('renders the skeleton placeholder', function () {
    expect(view('datatable.skeleton')->render())->toContain('svg');
});

it('toggles sort direction and resets on a new column', function () {
    Livewire::test(ItemsTable::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Anvil', 'Bolt', 'Chain'])
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Chain', 'Bolt', 'Anvil'])
        ->call('sort', 'status')
        ->assertSet('sortBy', 'status')
        ->assertSet('sortDirection', 'asc');
});

it('gives the sort action a name that no public property shadows on $wire', function () {
    $component = Livewire::test(ItemsTable::class)->assertSeeHtml('wire:click="sort(\'name\')"');

    expect(array_keys(get_object_vars($component->instance())))->not->toContain('sort');
});

it('searches a searchable column', function () {
    Livewire::test(ItemsTable::class)
        ->set('search', 'Bol')
        ->assertSee('Bolt')
        ->assertDontSee('Anvil')
        ->assertDontSee('Chain');
});

it('applies a select filter', function () {
    Livewire::test(ItemsTable::class)
        ->set('filterValues.status', 'archived')
        ->assertSee('Bolt')
        ->assertDontSee('Anvil');
});

it('applies the select filter default and resets to it', function () {
    Livewire::test(ItemsTable::class, ['withDefaultStatus' => true])
        ->assertSet('filterValues.status', 'active')
        ->set('filterValues.status', 'archived')
        ->call('resetFilters')
        ->assertSet('filterValues.status', 'active')
        ->assertSee('Anvil')
        ->assertDontSee('Bolt');
});

it('applies a date range filter', function () {
    Livewire::test(ItemsTable::class)
        ->set('filterValues.received_at', '2026-02-01/2026-02-28')
        ->assertSee('Bolt')
        ->assertDontSee('Anvil')
        ->assertDontSee('Chain');
});

it('reads search, sort and filters from the URL', function () {
    Livewire::withQueryParams([
        'search' => 'n',
        'sortBy' => 'name',
        'sortDirection' => 'desc',
        'filterValues' => ['status' => 'active'],
    ])->test(ItemsTable::class)
        ->assertSet('search', 'n')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'desc')
        ->assertSet('filterValues.status', 'active')
        ->assertSeeInOrder(['Chain', 'Anvil'])
        ->assertDontSee('Bolt');
});

it('writes search, sort and filters to the URL', function () {
    Livewire::test(ItemsTable::class)
        ->set('search', 'An')
        ->call('sort', 'name')
        ->set('filterValues.status', 'active')
        ->assertSet('search', 'An')
        ->assertSet('sortBy', 'name')
        ->assertSet('filterValues', ['status' => 'active']);
});

it('selects the page and all rows', function () {
    $component = Livewire::test(ItemsTable::class)
        ->set('withBulk', true)
        ->set('selectPage', true);

    expect($component->get('selected'))->toHaveCount(3);

    Item::create(['name' => 'Dowel', 'status' => 'active']);
    Item::create(['name' => 'Eye', 'status' => 'active']);

    $component->call('selectAllRows')->assertSet('selectAll', true);

    expect($component->get('selected'))->toHaveCount(5);

    $component->call('deselectAll')->assertSet('selected', []);
});

it('redirects a row click only for same-origin urls', function () {
    Livewire::test(ItemsTable::class)
        ->call('clickTableRow', '/items/1')
        ->assertRedirect('/items/1');

    Livewire::test(ItemsTable::class)
        ->call('clickTableRow', 'https://evil.example/items/1')
        ->assertNoRedirect();

    Livewire::test(ItemsTable::class)
        ->call('clickTableRow', url('/items/2'))
        ->assertRedirect(url('/items/2'));
});

it('uses the configured empty message and search placeholder', function () {
    Livewire::test(ConfigurableItemsTable::class)
        ->set('search', 'zzz')
        ->assertSee('Nothing here.')
        ->assertDontSee('No records found.')
        ->assertSee('Find items', false);

    Livewire::test(ItemsTable::class)
        ->set('search', 'zzz')
        ->assertSee('No records found.')
        ->assertSee('Search...', false);
});

it('returns all rows without pagination when paginate is false', function () {
    Item::create(['name' => 'Dowel', 'status' => 'active']);
    Item::create(['name' => 'Eye', 'status' => 'active']);

    $component = Livewire::test(ConfigurableItemsTable::class, ['paginate' => false])
        ->assertSee('Anvil')
        ->assertSee('Eye')
        ->assertDontSee('nextPage', false);

    expect($component->instance()->rows)->toHaveCount(5)->toBeInstanceOf(\Illuminate\Support\Collection::class);

    Livewire::test(ItemsTable::class)->assertSee('nextPage', false);
});

it('keeps separate pages for tables with different page names', function () {
    Item::create(['name' => 'Dowel', 'status' => 'active']);
    Item::create(['name' => 'Eye', 'status' => 'active']);

    $first = Livewire::test(ConfigurableItemsTable::class, ['pageName' => 'itemsPage'])
        ->call('setPage', 2, 'itemsPage')
        ->assertSet('paginators.itemsPage', 2);

    $second = Livewire::test(ConfigurableItemsTable::class, ['pageName' => 'otherPage'])
        ->assertSet('paginators.otherPage', 1);

    expect($first->get('paginators'))->not->toHaveKey('page');
    expect($second->get('paginators'))->not->toHaveKey('itemsPage');
});

it('reads each table page from its own query string key', function () {
    Item::create(['name' => 'Dowel', 'status' => 'active']);
    Item::create(['name' => 'Eye', 'status' => 'active']);

    Livewire::withQueryParams(['itemsPage' => 2])
        ->test(ConfigurableItemsTable::class, ['pageName' => 'itemsPage'])
        ->assertSet('paginators.itemsPage', 2);

    Livewire::withQueryParams(['itemsPage' => 2])
        ->test(ConfigurableItemsTable::class, ['pageName' => 'otherPage'])
        ->assertSet('paginators.otherPage', 1);
});

it('resets the named page on search, filter and sort changes', function () {
    Item::create(['name' => 'Dowel', 'status' => 'active']);
    Item::create(['name' => 'Eye', 'status' => 'active']);

    Livewire::test(ConfigurableItemsTable::class, ['pageName' => 'itemsPage'])
        ->call('setPage', 2, 'itemsPage')
        ->set('search', 'o')
        ->assertSet('paginators.itemsPage', 1)
        ->call('setPage', 2, 'itemsPage')
        ->call('sort', 'name')
        ->assertSet('paginators.itemsPage', 1)
        ->call('setPage', 2, 'itemsPage')
        ->set('filterValues.status', 'active')
        ->assertSet('paginators.itemsPage', 1);
});

it('merges cell attributes onto the cell', function () {
    Livewire::test(ConfigurableItemsTable::class)
        ->assertSeeHtml('cell-extra')
        ->assertSeeHtml('data-item="1"');
});

it('renders the toolbarStart slot', function () {
    Livewire::test(ConfigurableItemsTable::class, ['toolbarStart' => 'toolbar-start'])
        ->assertSee('start-slot-marker');

    Livewire::test(ConfigurableItemsTable::class)->assertDontSee('start-slot-marker');
});

it('renders the same toolbar row with and without slot content', function () {
    $toolbarClass = function (string $html): string {
        preg_match('/<div data-datatable-toolbar class="([^"]*)"/', $html, $matches);

        return $matches[1] ?? '';
    };

    $empty = Livewire::test(ConfigurableItemsTable::class)->html();
    $filled = Livewire::test(ConfigurableItemsTable::class, ['toolbarStart' => 'toolbar-start', 'toolbarEnd' => 'toolbar-end'])->html();

    expect($toolbarClass($empty))->toBe('flex min-h-14 flex-wrap items-center gap-2 border-b border-line px-4 py-3')
        ->and($toolbarClass($filled))->toBe($toolbarClass($empty))
        ->and($filled)->toContain('start-slot-marker')->toContain('end-slot-marker');
});

it('renders the package table view overrides instead of the Flux stubs', function () {
    Livewire::test(ConfigurableItemsTable::class)
        ->assertSeeHtml('not-in-[tr:first-child]:border-t border-line');
});

it('renders the bulk checkbox cell click guard as a valid Alpine attribute', function () {
    Livewire::test(ItemsTable::class)
        ->set('withBulk', true)
        ->assertDontSee('@click.stop="@click.stop"', false)
        ->assertSee('x-on:click.stop=""', false);
});
