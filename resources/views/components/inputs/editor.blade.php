<x-input-wrapper :name="$name" :id="$id" :unique_id="$unique_id" :label="$label">
   <textarea {{ $attributes->merge([
        'type' => isset($type) ? $type : 'text',
        'name' => $name,
        'id' => $id,
        'required' => $required,
        'placeholder' => $placeholder,
        'class' => $class,
        'data-parsley-errors-container' => '#'.$unique_id.'-errors'
     ]) }}>{{ $value }}</textarea>
    <script src="{{ base_url() }}/assets/js/ckeditor/ckeditor.js"></script>

    <x-slot name="before">{{ isset($before) ? $before : null }}</x-slot>
    <x-slot name="after">{{ isset($after) ? $after : null }}</x-slot>
</x-input-wrapper>
