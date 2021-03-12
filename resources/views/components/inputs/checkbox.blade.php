<x-input :name="$name" :id="$id" :unique_id="$unique_id" :label="$label">
    {{-- "+ this.checked" rende il valore booleano un numero --}}
    <div class="form-group checkbox-group">
        <input type="hidden" name="{{ $name }}" value="{{ $value }}" class="openstamanager-input">
        <input type="checkbox" autocomplete="off" class="hidden" {{ $attributes->merge([
    'name' => $name,
    'id' => $id,
    'value' => $value,
    'required' => $required,
    'data-parsley-errors-container' => '#'.$unique_id.'-errors'
 ]) }} onchange="$(this).parent().find(\'[type = hidden]\').val(+this.checked).trigger(\'change\')"/>
        <div class="btn-group checkbox-buttons">
            <label for="{{ $id }}" class="btn btn-default{{ $class }}">
                <span class="fa fa-check text-success"></span>
                <span class="fa fa-close text-danger"></span>
            </label>
            <label for="{{ $id }}" class="btn btn-default active{{ $class }}">
                <span class="text-success">{{ tr('Attivato') }}</span>
                <span class="text-danger">{{ tr('Disattivato') }}</span>
            </label>
        </div>
    </div>

    <x-slot name="before">{{ isset($before) ? $before : null }}</x-slot>
    <x-slot name="after">{{ isset($after) ? $after : null }}</x-slot>
</x-input>
