<?php

namespace Booni3\FluxDatatable\Tests\Fixtures;

use Booni3\FluxDatatable\Column;

class ConfigurableItemsTable extends ItemsTable
{
    public ?string $toolbarStart = null;

    public ?string $toolbarEnd = null;

    public ?string $pageName = null;

    public bool $paginate = true;

    protected string $emptyMessage = 'Nothing here.';

    protected string $searchPlaceholder = 'Find items';

    public function mount(?string $pageName = null, bool $paginate = true, ?string $toolbarStart = null, ?string $toolbarEnd = null): void
    {
        $this->pageName = $pageName;
        $this->paginate = $paginate;
        $this->toolbarStart = $toolbarStart;
        $this->toolbarEnd = $toolbarEnd;
    }

    public function cellAttributes(Column $column, mixed $row): array
    {
        return $column->field === 'name' ? ['class' => 'cell-extra', 'data-item' => $row->id] : [];
    }
}
