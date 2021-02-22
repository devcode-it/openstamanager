@extends('layouts.app')

@section('title', tr("Bug"))

@section('content')
    @if(empty($mail['from_address']) or empty($mail['server']))
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    <b>{{ tr('Attenzione!') }}</b> {{ tr('Per utilizzare correttamente il modulo di segnalazione bug devi configurare alcuni parametri riguardanti le impostazione delle email') }}.

    {!! module('Account email')->link($mail['id'], tr('Correggi account'), null, 'class="btn btn-warning pull-right"') !!}
    <div class="clearfix"></div>
</div>
    @endif

<div class="box box-outline box-primary">
    <div class="box-header">
        <h3 class="box-title"><i class="fa fa-bug"></i> {{ tr('Segnalazione bug') }}</h3>
    </div>

    <div class="box-body">
        <form method="post" action="{{ route('bug-send') }}">
            @csrf

            <table class="table table-bordered table-condensed table-striped table-hover">
                <tr>
                    <th width="150" class="text-right">{{ tr('Da') }}:</th>
                    <td>{{ $mail['from_address'] }}</td>
                </tr>

                <!-- A -->
                <tr>
                    <th class="text-right">{{ tr('A') }}:</th>
                    <td>{{ $bug_email }}</td>
                </tr>

                <!-- Versione -->
                <tr>
                    <th class="text-right">{{ tr('Versione OSM') }}:</th>
                    <td>{{ Update::getVersion() }} ({{  Update::getRevision() ?: tr('In sviluppo') }})</td>
                </tr>
            </table>

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "{{ tr('Allega file di log') }}", "name": "log", "value": "1" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "{{ tr('Allega copia del database') }}", "name": "sql" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "{{ tr('Allega informazioni sul PC') }}", "name": "info", "value": "1" ]}
                </div>
            </div>

            <div class="clearfix"></div>
            <br>

            {[ "type": "ckeditor", "label": "{{ tr('Descrizione del bug') }}", "name": "body" ]}

            <!-- PULSANTI -->
            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary" id="send" disabled>
                        <i class="fa fa-envelope"></i> {{ tr('Invia segnalazione') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    var firstFocus = true;

    $(document).ready(function() {
        init();

        // Impostazione del messaggio
        var html = `<p>{{ tr('Se hai riscontrato un bug ricordati di specificare') }}:</p>
            <ul>
                <li>{{ tr('Modulo esatto (o pagina relativa) in cui questi si è verificato') }};</li>
                <li>{{ tr('Dopo quali specifiche operazioni hai notato il malfunzionamento') }}.</li>
            </ul>
            <p>{{ tr("Assicurati inoltre di controllare che il checkbox relativo ai file di log sia contrassegnato, oppure riporta qui l'errore visualizzato") }}.</p>
            <p>{{ tr('Ti ringraziamo per il tuo contributo') }},<br>
            {{ tr('Lo staff di OSM') }}</p>`;

        let editor = input("body");
        editor.set(html);

        editor.on("change", function() {
            setTimeout(function() {
                $("#send").prop("disabled", editor.get() === "");
            }, 10);
        });

        editor.on("focus", function() {
            if (firstFocus) {
                editor.set("");
                firstFocus = false;
            }
        });
    });
</script>
@endsection
