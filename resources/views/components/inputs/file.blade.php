<x-input :name="$name" :id="$id" :unique_id="$unique_id" :label="$label">
    @include('components.inputs.standard-input')

    <x-slot name="before">{{ isset($before) ? $before : null }}</x-slot>
    <x-slot name="after">{{ isset($after) ? $after : null }}</x-slot>
</x-input>
