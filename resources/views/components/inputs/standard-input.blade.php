<input {{ $attributes->merge([
    'type' => isset($type) ? $type : 'text',
    'name' => $name,
    'id' => $id,
    'required' => $required,
    'placeholder' => $placeholder,
    'class' => $class,
    'data-parsley-errors-container' => '#'.$unique_id.'-errors'
 ]) }} autocomplete="{{ $attributes->get('autocomplete', 'off') }}">
