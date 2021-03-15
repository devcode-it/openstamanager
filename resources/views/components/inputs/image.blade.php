<x-input-wrapper :name="$name" :id="$id" :unique_id="$unique_id" :label="$label">
    @if(empty($value))
        @php($type = 'file')
        @include('components.inputs.standard-input')
    @else
        <img src="{{ $value }}" class="img-thumbnail img-responsive"><br>
        <label>
            <input type="checkbox" name="delete_{{ $name }}" id="delete_{{ $id }}"> '.tr('Elimina').'
        </label>
        <input type="hidden" name="{{ $name }}" value="{{ $value }}" id="{{ $id }}">
    @endif

    <x-slot name="before">{{ isset($before) ? $before : null }}</x-slot>
    <x-slot name="after">{{ isset($after) ? $after : null }}</x-slot>
</x-input-wrapper>
