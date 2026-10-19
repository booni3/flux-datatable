<div
    @if($this->refreshInterval)
        wire:poll.{{ $this->refreshInterval * 1000 }}ms
    @endif
    class="p-3"
>
    @if(isset($beforeToolbar))
        @include($beforeToolbar, $beforeToolbarData ?? [])
    @endif

    {{-- Card wrapper --}}
    <div class="bg-surface rounded-lg border border-line shadow-sm overflow-hidden">
        {{-- Toolbar --}}
        @php
            $inlineFilters = $this->getInlineFilters();
            $dropdownFilters = $this->getDropdownFilters();
        @endphp
        <div class="px-4 py-3 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-line">
            <div class="flex flex-wrap items-center gap-2">
                @if(isset($toolbarStart))
                    @include($toolbarStart, $toolbarStartData ?? [])
                @endif

                {{-- Search --}}
                @if($this->hasSearch())
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        placeholder="{{ $this->searchPlaceholder }}"
                        size="sm"
                        class="!w-full sm:!w-[220px]"
                        clearable
                        aria-label="Search table"
                    />
                @endif

                {{-- Inline Filters (rendered directly in toolbar) --}}
                @foreach($inlineFilters as $filter)
                    @include($filter->viewName(), $filter->viewData("filterValues.{$filter->key}"))
                @endforeach

                {{-- Filters Dropdown (non-inline filters only) --}}
                @if(count($dropdownFilters) > 0)
                    <flux:dropdown position="bottom" align="start">
                        <flux:button variant="ghost" size="sm" icon-trailing="funnel">
                            Filters
                            @if($this->hasActiveFilters())
                                <flux:badge size="sm" color="zinc" inset="top right">
                                    {{ count($this->getAppliedFiltersWithValues()) }}
                                </flux:badge>
                            @endif
                        </flux:button>

                        <flux:menu>
                            <div class="p-4 space-y-4 min-w-[320px]">
                                @foreach($dropdownFilters as $filter)
                                    <div>
                                        <div class="text-xs font-medium text-muted uppercase tracking-wider mb-1.5">{{ $filter->label }}</div>
                                        @include($filter->viewName(), $filter->viewData("filterValues.{$filter->key}"))
                                    </div>
                                @endforeach

                                @if($this->hasActiveFilters())
                                    <div class="pt-2 border-t border-line">
                                        <flux:button wire:click="resetFilters" size="sm" variant="ghost" class="w-full">
                                            Clear Filters
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        </flux:menu>
                    </flux:dropdown>
                @endif
            </div>

            <div class="flex items-center gap-2">
                {{-- Bulk Actions --}}
                @if($this->hasBulkActions() && $this->hasSelection())
                    <div class="flex items-center gap-1">
                        <span class="text-sm text-muted">{{ count($selected) }} selected:</span>
                        @foreach($this->bulkActions() as $method => $label)
                            <flux:button wire:click="{{ $method }}" size="sm" variant="filled">
                                {{ $label }}
                            </flux:button>
                        @endforeach
                    </div>
                @endif

                @if(isset($toolbarEnd))
                    @include($toolbarEnd, $toolbarEndData ?? [])
                @endif
            </div>
        </div>

        {{-- Table --}}
        @php
            $rows = $this->rows;
            $mobileRowView = $this->mobileRowView();
        @endphp

        @if($mobileRowView && $rows->count() > 0)
            <div class="divide-y divide-line xl:hidden" aria-label="Table results">
                @foreach($rows as $row)
                    @include($mobileRowView, ['row' => $row])
                @endforeach
            </div>
        @endif

        @if(!$mobileRowView || $rows->count() > 0)
            @if($mobileRowView)
                <div class="hidden xl:block">
            @endif
        <flux:table :paginate="$mobileRowView || !$this->paginate ? null : $rows">
            <flux:table.columns>
                {{-- Bulk select checkbox --}}
                @if($this->hasBulkActions())
                    <flux:table.column class="w-10">
                        <flux:checkbox wire:model.live="selectPage" aria-label="Select all on page" />
                    </flux:table.column>
                @endif

                @foreach($this->getVisibleColumns() as $column)
                    @if($column->sortable && $column->field)
                        <flux:table.column sortable :sorted="$sortBy === $column->field" :direction="$sortDirection" wire:click="sortBy('{{ $column->field }}')">
                            {{ $column->label }}
                        </flux:table.column>
                    @else
                        <flux:table.column>{{ $column->label }}</flux:table.column>
                    @endif
                @endforeach
            </flux:table.columns>

            <flux:table.rows>
                {{-- Select all banner --}}
                @if($this->hasBulkActions() && $selectPage && !$selectAll)
                    <flux:table.row>
                        <flux:table.cell :colspan="count($this->getVisibleColumns()) + 1">
                            <div class="text-center text-sm">
                                You have selected <strong>{{ count($selected) }}</strong> rows on this page.
                                <flux:link as="button" wire:click="selectAllRows">Select all rows</flux:link>.
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endif

                @if($rows->count() > 0)
                    @foreach($rows as $row)
                        @php
                            $url = $this->rowUrl($row);
                            $target = $this->rowUrlTarget($row);
                            $attrs = $this->rowAttributes($row);
                            $rowClasses = $attrs['class'] ?? '';
                            $extraAttrs = collect($attrs)->except('class');
                            $rowKey = data_get($row, $this->getPrimaryKey());
                        @endphp
                        <tr
                            wire:key="row-{{ $rowKey }}"
                            class="{{ $loop->even ? 'bg-ground' : 'bg-surface' }} {{ $url ? 'cursor-pointer hover:bg-ground' : '' }} {{ $rowClasses }}"
                            @foreach($extraAttrs as $attrKey => $attrValue)
                                {{ $attrKey }}="{{ $attrValue }}"
                            @endforeach
                            @if($url && !$extraAttrs->has('wire:click'))
                                @if($target === '_blank')
                                    @click="window.open(@js($url), '_blank')"
                                @else
                                    wire:click="clickTableRow(@js($url))"
                                @endif
                            @endif
                        >
                            {{-- Bulk select checkbox --}}
                            @if($this->hasBulkActions())
                                <flux:table.cell class="w-10" @click.stop>
                                    <flux:checkbox wire:model.live="selected" value="{{ $rowKey }}" aria-label="Select row {{ $rowKey }}" />
                                </flux:table.cell>
                            @endif

                            @foreach($this->getVisibleColumns() as $column)
                                <flux:table.cell :attributes="new \Illuminate\View\ComponentAttributeBag($this->cellAttributes($column, $row))">
                                    @if($column->viewName)
                                        @include($column->viewName, ['row' => $row, 'value' => data_get($row, $column->field), 'column' => $column])
                                    @elseif($column->labelCallback || $column->formatCallback)
                                        @php $resolved = $column->resolveValue($row); @endphp
                                        @if($resolved instanceof \Illuminate\Contracts\View\View || $resolved instanceof \Illuminate\Support\HtmlString)
                                            {!! $resolved !!}
                                        @else
                                            {{ $resolved }}
                                        @endif
                                    @else
                                        {{ $column->resolveValue($row) }}
                                    @endif
                                </flux:table.cell>
                            @endforeach
                        </tr>
                    @endforeach
                @else
                    <flux:table.row>
                        <flux:table.cell :colspan="count($this->getVisibleColumns()) + ($this->hasBulkActions() ? 1 : 0)">
                            <div class="py-8 text-center text-muted">
                                {{ $this->emptyMessage }}
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endif
            </flux:table.rows>
        </flux:table>
            @if($mobileRowView)
                </div>
            @endif
        @else
            <div class="py-8 text-center text-muted">
                {{ $this->emptyMessage }}
            </div>
        @endif

        @if($mobileRowView && $this->paginate)
            <flux:pagination :paginator="$rows" class="shrink-0" />
        @endif
    </div>
</div>
