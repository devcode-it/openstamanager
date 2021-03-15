@extends('modules.base')

@section('module_content')
    @if(!empty($custom) or !empty($tables))
<!-- Personalizzazioni di codice -->
<div class="box box-outline box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">
            <span class="tip" title="{{ tr('Elenco delle personalizzazioni rilevabili dal gestionale') }}.">
				<i class="fa fa-edit"></i> {{ tr('Personalizzazioni') }}
			</span>
        </h3>
    </div>
    <div class="box-body">

        @if(!empty($custom))
        <table class="table table-hover table-striped">
            <tr>
                <th width="10%">{{ tr('Percorso') }}</th>
                <th width="15%">{{ tr('Cartella personalizzata') }}</th>
                <th width="15%">{{ tr('Database personalizzato') }}</th>
            </tr>

            @foreach($custom as $element)
            <tr>
                <td>{{ $element['path'] }}</td>
                <td>{{ $element['directory'] ? tr('Si') : tr('No') }}</td>
                <td>{{ $element['database'] ? tr('Si') : tr('No') }}</td>
            </tr>
            @endforeach
        </table>

        <p><strong>{{ tr("Si sconsiglia l'aggiornamento senza il supporto dell'assistenza ufficiale") }}.</strong></p>
        @else
        <p>{{ tr('Non ci sono strutture personalizzate') }}.</p>
        @endif

        @if(!empty($tables))
        <div class="alert alert-warning">
            <i class="fa fa-warning"></i>
            <b>{{ tr('Attenzione!') }}</b> {{ tr('Ci sono delle tabelle non previste nella versione standard del gestionale: _LIST_', [
                '_LIST_' => implode(', ', $tables),
            ]) }}
        </div>
        @endif

    </div>
</div>
@endif

@if(!empty($alerts))
<div class="alert alert-warning">
    <p>{{ tr('Devi modificare il seguenti parametri del file di configurazione PHP (_FILE_) per poter caricare gli aggiornamenti', ['_FILE_' => '<b>php.ini</b>']) }}:</p>
    <ul>
        @foreach($alerts as $key => $value)
        <li><b>{{ $key }}</b> = {{ $value }}</li>
        @endforeach
    </ul>
</div>
@endif

<script>
    function update() {
        if ($("#blob").val()) {
            swal({
                title: "{{ tr('Avviare la procedura?') }}",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "{{ tr('Sì') }}"
            }).then(function (result) {
                $("#update").submit();
            })
        } else {
            swal({
                title: "{{ tr('Selezionare un file!') }}",
                type: "error",
            })
        }
    }

    function search(button) {
        buttonLoading(button);

        $.ajax({
            url: "{{ route('controllo-versione') }}",
            type: "post",
            success: function(data){
                $("#update-search").addClass("hidden");

                if (data === "none" || !data) {
                    $("#update-none").removeClass("hidden");
                } else {
                    if (data.includes("beta")) {
                        $("#beta-warning").removeClass("hidden");
                    }

                    $("#update-version").text(data);
                    $("#update-download").removeClass("hidden");
                }
            }
        });
    }

    function download(button) {
        buttonLoading(button);
        $("#main_loading").show();

        $.ajax({
            url: "{{ route('scarica-aggiornamento') }}",
            type: "post",
            success: function(){
                window.location.reload();
            }
        });
    }

    function checksum(button) {
        openModal("{{ tr('Controllo dei file') }}", "{{ route('checksum') }}");
    }

    function database(button) {
        openModal("{{ tr('Controllo del database') }}", "{{ route('database') }}");
    }

    function controlli(button) {
        openModal("{{ tr('Controlli del gestionale') }}", "{{ route('controlli') }}");
    }
</script>

<div class="row">
    <div class="col-md-4">
        <div class="box box-outline box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    {{ tr('Carica un aggiornamento') }} <span class="tip" title="{{ tr('Form di caricamento aggiornamenti del gestionale e innesti di moduli e plugin') }}."><i class="fa fa-question-circle-o"></i></span>
                </h3>
            </div>

            <div class="box-body">
                <form action="" method="post" enctype="multipart/form-data" id="update">
                    <input type="hidden" name="op" value="upload">
                    <input type="hidden" name="backto" value="record-list">

                    {[ "type": "file", "name": "blob", "required": 1, "accept": ".zip" ]}

                    <button type="button" class="btn btn-primary float-right" onclick="update()">
                        <i class="fa fa-upload"></i> {{ tr('Carica') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    {{ tr("Verifica l'integrità dell'intallazione") }}
                    <span class="tip" title="{{ tr("Verifica l'integrità della tua installazione attraverso un controllo sui checksum dei file e sulla struttura del database") }}.">
                        <i class="fa fa-question-circle-o"></i>
                    </span>
                </h3>
            </div>
            <div class="box-body">
                <button type="button" class="btn btn-primary btn-block" onclick="checksum(this)">
                    <i class="fa fa-list-alt"></i> {{ tr('Controlla file') }}
                </button>

                <button type="button" class="btn btn-info btn-block" onclick="database(this)">
                    <i class="fa fa-database"></i> {{ tr('Controlla database') }}
                </button>

                <button type="button" class="btn btn-block" onclick="controlli(this)">
                    <i class="fa fa-stethoscope"></i> {{ tr('Controlla gestionale') }}
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-outline box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    {{ tr('Ricerca aggiornamenti') }} <span class="tip" title="{{ tr('Controllo automatico della presenza di aggiornamenti per il gestionale') }}."><i class="fa fa-question-circle-o"></i></span>
                </h3>
            </div>

            <div class="box-body">
                @if(extension_loaded('curl'))
                    <div id="update-search">
                        <button type="button" class="btn btn-info btn-block" onclick="search(this)">
                            <i class="fa fa-search"></i> {{ tr('Ricerca') }}
                        </button>
                    </div>
                @else
                    <button type="button" class="btn btn-warning btn-block disabled" >
                        <i class="fa fa-warning"></i> {{ tr('Estensione curl non supportata') }}.
                    </button>
                @endif

                <div id="update-download" class="hidden">
                    <p>{{ tr("E' stato individuato un nuovo aggiornamento") }}: <b id="update-version"></b>.</p>
                    <p id="beta-warning" class="hidden"><b>{{ tr('Attenzione: la versione individuata è in fase sperimentale, e pertanto può presentare diversi bug e malfunzionamenti') }}.</b></p>

                    <p>{!! tr('Scaricalo manualmente (_LINK_) oppure in automatico', ['_LINK_' => '<a href="https://github.com/devcode-it/openstamanager/releases">https://github.com/devcode-it/openstamanager/releases</a>']) !!}:</p>

                    <button type="button" class="btn btn-success btn-block" onclick="download(this)">
                        <i class="fa fa-download"></i> {{ tr('Scarica') }}
                    </button>
                </div>

                <div id="update-none" class="hidden">
                    <p>{{ tr('Nessun aggiornamento presente') }}.</p>
                </div>

            </div>
        </div>
    </div>
</div>

<hr>
<div id="requirements">
    <h3>{{ tr('Requisiti') }}</h3>

    @include('config.requirements-list')
</div>
@endsection
