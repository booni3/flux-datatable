<?php

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
        ->call('sortBy', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Anvil', 'Bolt', 'Chain'])
        ->call('sortBy', 'name')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Chain', 'Bolt', 'Anvil'])
        ->call('sortBy', 'status')
        ->assertSet('sortBy', 'status')
        ->assertSet('sortDirection', 'asc');
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
        ->call('sortBy', 'name')
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
