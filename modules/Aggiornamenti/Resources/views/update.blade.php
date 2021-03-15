@extends('layouts.base')

@section('title', tr('Aggiornamento'))
@section('body_class', 'hold-transition login-page')

@section('body')
<div class="card card-outline card-center-large card-warning">
    <div class="card-header">
        <a class="h5" data-toggle="tab" href="#info">{{ tr("Informazioni sull'aggiornamento") }}</a>

        <ul class="nav nav-tabs float-right" id="tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#changelog">{{ tr('Changelog') }}</a>
            </li>
        </ul>
    </div>

    <div class="card-body tab-content">
        <div id="info" class="tab-pane fade in active">

            @if($update->isCoreUpdate())
            <p>{{ tr("Il pacchetto selezionato contiene un aggiornamento dell'intero gestionale") }}.</p>
            <p>{{ tr("Si consiglia vivamente di effettuare un backup dell'installazione prima di procedere") }}.</p>

            <button type="button" class="btn btn-primary float-right" onclick="backup()">
                <i class="fa fa-database"></i> {{ tr('Crea backup') }}
            </button>

            <div class="clearfix"></div>
            <hr>

            <h3 class="text-center">{{ tr('OpenSTAManager versione _VERSION_')({'_VERSION_': update.getVersion()}) }}</h3>

            {% include 'config/list.twig' with {requirements: update.getRequirements()} only %}

            @else
                @php($elements = $update->componentUpdates())

            <div class="alert alert-warning">
                <i class="fa fa-warning"></i>
                <b>{{ tr('Attenzione!') }}</b> {{ tr('Verranno aggiornate le sole componenti del pacchetto che non sono già installate e aggiornate') }}.
            </div>

            @if(!empty($elements['modules']))
            <p>{{ tr('Il pacchetto selezionato comprende i seguenti moduli') }}:</p>
            <ul class="list-group">

                @foreach($elements['modules'] as $element)
                <li class="list-group-item">
                    <span class="badge">{{ $element['info']['version'] }}</span>

                    @if($element['is_installed'])
                    <span class="badge">{{ tr('Installato') }}</span>
                    @endif

                    {{ $element['info']['name'] }}
                </li>
                @endforeach

            </ul>
            @endif

            @if(!empty($elements['plugins']))
            <p>{{ tr('Il pacchetto selezionato comprende i seguenti plugin') }}:</p>
            <ul class="list-group">

                @foreach($elements['plugins'] as $element)
                <li class="list-group-item">
                    <span class="badge">{{ $element['info']['version'] }}</span>

                    @if($element['is_installed'])
                    <span class="badge">{{ tr('Installato') }}</span>
                    @endif

                    {{ $element['info']['name'] }}
                </li>
                @endforeach

            </ul>
            @endif
        @endif

        </div>

        <div id="changelog" class="tab-pane fade">
            @php($changelog = $update->getChangelog())

            @if(!empty($changelog))
            {!! $changelog !!}
            @else
            <p>{{ tr('Nessuna changelog individuabile') }}.</p>
            @endif

        </div>

        <hr>

        <form action="{{ route('cancella-aggiornamento') }}" method="post" style="display:inline-block">
            <button type="submit" class="btn btn-warning">
                <i class="fa fa-arrow-left"></i> {{ tr('Annulla') }}
            </button>
        </form>

        <form action="{{ route('completa-aggiornamento') }}" method="post" class="float-right" style="display:inline-block">
            <button type="submit" class="btn btn-success">
                <i class="fa fa-arrow-right"></i> {{ tr('Procedi') }}
            </button>
        </form>
    </div>
</div>

<script>
    function backup(){
        swal({
            title: "{{ tr('Nuovo backup') }}",
            text: "{{ tr('Sei sicuro di voler creare un nuovo backup?') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn btn-lg btn-success",
            confirmButtonText: "{{ tr('Crea') }}",
        }).then(function(){
            $("#main_loading").show();

            $.ajax({
                url: "{{ route('module', ['module_id' => module('Backup')->id]) }}",
                type: "post",
                data: {
                    op: "backup",
                },
                success: function(data){
                    $("#main_loading").fadeOut();
                }
            });
        }, function(){});
    }
</script>
@endsection
