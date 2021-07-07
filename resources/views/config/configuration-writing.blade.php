@extends('layouts.guest')

@section('title', tr('Errore di configurazione'))
@section('box_class', 'box-danger')
@section('box_header')
<h3 class="box-title">{{ tr('Permessi di scrittura mancanti') }}</h3>
@endsection

@section('content')
<p>{!! tr('Sembra che non ci siano i permessi di scrittura sul file _FILE_', ['_FILE_' => '<b>.env</b>']) !!}. {{ tr('Per completare la configurazione del gestionale, è necessario inserire provare nuovamente attraverso il pulsante "Riprova" oppure inserire manualmente il contenuto sottostante nel file indicato') }}.</p>

<form action="{{ route('configuration-save') }}" method="post">
    @foreach($params as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach

    <a class="btn btn-warning" href="{{ route('login') }}">
        <i class="fa fa-arrow-left"></i> {{ tr('Torna indietro') }}
    </a>

    <button type="submit" class="btn btn-info pull-right">
        <i class="fa fa-repeat"></i> {{ tr('Riprova') }}
    </button>
</form>

<hr>

<div class="box box-outline box-default collapsable collapsed-box">
    <div class="box-header with-border">
        <h4 class="box-title"><a class="clickable" data-widget="collapse">{{ tr('Creazione manuale') }}</a></h4>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>

    <div class="box-body">
        <p>{!! tr('Inserire il seguente testo nel file _FILE_', ['_FILE_' => '<b>.env</b>']) !!}.</p>
        <p><strong>{{ tr('Attenzione!') }}</strong> {!! tr('A seguito del completamento manuale della configurazione, è necessario utilizzare il pulsante dedicato "Completa inserimento" oppure eseguire da riga di comando _CMD_', ['_CMD_' => "<kbd>php artisan config:cache</kbd>"]) !!}.</p>

        <a class="btn btn-success" href="{{ route('configuration-cache') }}">
            <i class="fa fa-download"></i> {{ tr('Completa inserimento') }}
        </a>

        <pre class="text-left">{{ $config }}</pre>
    </div>
</div>
@endsection
