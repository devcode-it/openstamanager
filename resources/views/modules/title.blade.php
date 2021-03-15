@if(!empty($module->help))
<span class="tip" title="{{ $module->help }}" data-position="bottom">
    <i class="{{ $module->icon }}"></i> {{ $module->title }}

    <i class="fa fa-question-circle-o" style="font-size:0.75em"></i>
</span>
@else
    <i class="{{ $module->icon }}"></i> {{ $module->title }}
@endif

{{-- $module->hasAddFile() and --}}
@if($module->permission == 'rw')
    <!-- Pulsante "Aggiungi" solo se la struttura lo supporta -->
    <button type="button" class="btn btn-primary" data-toggle="modal" data-title="{{ tr('Aggiungi') }}..." data-href="{{ 'test' }}">
        <i class="fa fa-plus"></i>
    </button>
@endif
