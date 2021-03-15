<x-input-wrapper :name="$name" :id="$id" :unique_id="$unique_id" :label="$label">
    <select {{ $attributes->merge([
        'name' => $name,
        'id' => $id,
        'required' => $required,
        'placeholder' => $placeholder,
        'data-placeholder' => $placeholder,
        'class' => $class,
        'data-parsley-errors-container' => '#'.$unique_id.'-errors',
        'multiple' => $multiple,
        'data-select2-id' => $id.'_'.rand(0, 999),
        'data-source' => $source,
        'data-select-options' => json_encode($options),
        'data-maximum-selection-length' => $attributes->get('maximum-selection-length', null),
     ]) }}>
        @if(!$is_grouped)
            @foreach($values as $option)
                @include('components.inputs.select-option')
            @endforeach
        @else
            @foreach($values as $group => $elements)
                <optgroup label="{{ $group }}"></optgroup>
                @foreach($elements as $option)
                    @include('components.inputs.select-option')
                @endforeach
            @endforeach
        @endif
    </select>

    @if($attributes->get('disabled') || $attributes->get('readonly'))
        <script>input("{{ $name }}").disable();</script>
    @endif

    <x-slot name="before">{{ isset($before) ? $before : null }}</x-slot>
    <x-slot name="after">{{ isset($after) ? $after : null }}</x-slot>
</x-input-wrapper>

