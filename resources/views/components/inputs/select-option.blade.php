<option value="{{ $option['id'] }}"
        {{ in_array($option['id'], $value) ? 'selected' : '' }}
        {{ !empty($option['_bgcolor_']) ? 'style="background:'.$element['_bgcolor_'].'; color:'.color_inverse($element['_bgcolor_']).';"' : '' }}
        data-select-attributes='{{ replace(json_encode($option), ["'" => "\'"]) }}'>
    {{ empty($option['text']) ? $option['descrizione'] : $option['text'] }}
</option>
