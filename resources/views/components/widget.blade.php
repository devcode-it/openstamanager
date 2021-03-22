<a class="clickable" {!! $attrs !!}>
    <div class="info-box">
        <button type="button" class="close" onclick="if(confirm('Disabilitare questo widget?')) { $.post('{{ url('/') }}/actions.php?id_module={{ $widget->module->id }}', { op: 'disable_widget', id: '{{ $widget->id }}' }, function(response){ location.reload(); }); }" >
            <span aria-hidden="true">&times;</span><span class="sr-only">{{ tr('Chiudi') }}</span>
        </button>

        <span class="info-box-icon" style="background-color:{{ $widget->bgcolor }}">
        @if(!empty($widget['icon']))
                <i class="{{ $widget->icon }}"></i>
            @endif
        </span>

        <div class="info-box-content">
            <span class="info-box-text {{ !empty($widget['help']) ? ' tip' : '' }}" {{ !empty($widget['help']) ? ' title="'.prepareToField($widget['help']).'" data-position="bottom"' : '' }}>
                {{ $title }}

                {{ !empty($widget['help']) ? '<i class="fa fa-question-circle-o"></i>' : '' }}
            </span>

            @if(isset($content))
                <span class="info-box-number">{{ $content }}</span>
            @endif
        </div>
    </div>
</a>
