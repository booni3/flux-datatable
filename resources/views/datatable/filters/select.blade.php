<div class="min-w-[140px] max-w-[200px]">
    <flux:select wire:model.live="{{ $wireModel }}" size="sm" :searchable="$searchable ?? false">
        @foreach($options as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>
</div>
