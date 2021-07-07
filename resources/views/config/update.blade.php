@extends('layouts.guest')

@section('title', $installing ? tr('Installazione') : tr('Aggiornamento'))
@section('box_class', 'box-warning text-center')
@section('box_header')
    <h3 class="box-title">{{ $installing ? tr('Installazione di OpenSTAManager') : tr('Aggiornamento di OpenSTAManager') }}</h3>
@endsection

@section('content')
    @if($installing)
    <p><strong>{{ tr("E' la prima volta che avvii OpenSTAManager e non hai ancora installato il database") }}.</strong></p>
    @else
    <p>{{ tr("E' necessario aggiornare il database a una nuova versione") }}.</p>
    @endif

    @php($button = $installing ? tr('Installa!') : tr('Aggiorna!'))

    <p>{!! tr("Premi il tasto _BUTTON_ per procedere con l'_OP_!", [
        '_BUTTON_' => '<b>'.$button.'</b>',
        '_OP_' => $installing ? tr('installazione') : tr('aggiornamento'),
    ]) !!}</p>
    <button type="button" class="btn btn-primary" id="contine_button">
        {{ $button }}
    </button>

    <div id="update-info" class="hidden">
        <div class="progress">
            <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
                <span>0%</span>
            </div>
        </div>
        <hr>
        <div class="box box-outline box-info text-center collapsed-box">
            <div class="box-header with-border">
                <h3 class="box-title"><a class="clickable" data-widget="collapse">{{ tr('Log') }}</a></h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                </div>
            </div>
            <div class="box-body info text-left"></div>
        </div>
    </div>
    <div id="result"></div>

    <div style="display: none;" id="completed">
        <p><strong>{{ tr('Aggiornamento completato') }}</strong> <i class="fa fa-smile-o"></i></p>

        @if($installing)
            <!-- Istruzioni per la prima installazione -->
            <p class="text-danger">{{ tr("E' fortemente consigliato rimuovere i permessi di scrittura dal file _FILE_", ['_FILE_' => '<b>config.inc.php</b>']) }}.</p>
        @endif

        <a class="btn btn-success btn-block" href="{{ route('login') }}">
            <i class="fa fa-check"></i> {{ tr('Continua') }}
        </a>
    </div>

    <script>
        globals.update = {
            totalNumber: {{ $updates_available }},
            totalCount: {{ $total_weight }},
            legacyNumber: {{ $legacy_number }},
            url: "{{ route('update-execute') }}",
            progress: {
                number: 0,
                count: 0,
                legacyVersions: [],
            },
            translations: {
                title: "{{ tr('Sei sicuro di voler procedere?') }}",
                continue: "{{ tr('Procedi') }}",
                legacyVersion: "{{ tr('Aggiornamento _DONE_ di _TODO_ (_VERSION_)') }}",
                migrationList: "{{ tr('Elenco migrazioni del database') }}",
                migration: "{{ tr('Migrazione _NAME_') }}",
            }
        };

        function executeUpdate() {
            $.ajax({
                url: globals.update.url,
                type: "POST",
                dataType: "JSON",
                cache: false,
                data: {
                    legacy: globals.update.progress.number < globals.update.legacyNumber,
                },
                success: function(response) {
                    addProgress(response['progress']);

                    if (response['legacyVersion']){
                        addVersion(response['legacyVersion']);
                    } else if (response['migration']) {
                        addMigration(response['migration']);
                    }

                    if (!response['completed']) {
                        executeUpdate();
                    }
                },
                error: ajaxError
            });
        }

        function addProgress(rate) {
            globals.update.progress.count += rate;

            let percent = globals.update.progress.count / globals.update.totalCount * 100;
            percent = Math.round(percent);
            percent = percent > 100 ? 100 : percent;

            setPercent(percent);
        }

        function setPercent(percent) {
            $("#update-info .progress-bar").width(percent + "%");
            $("#update-info .progress-bar span").text(percent + "%");

            if (percent >= 100) {
                $("#completed").show();
            }
        }

        function addVersion(version) {
            if (globals.update.progress.legacyVersions.indexOf(version) === -1) {
                globals.update.progress.legacyVersions.push(version);
                globals.update.progress.number += 1;

                let message = globals.update.translations.legacyVersion
                    .replace("_DONE_", globals.update.progress.number)
                    .replace("_TODO_", globals.update.totalNumber)
                    .replace("_VERSION_", version.trim());

                const selector = $("#update-info .info");
                selector.append("<p><strong>" + message + "</strong></p>");
            }
        }

        function addMigration(name) {
            const selector = $("#update-info .info");
            if (selector.html().indexOf(globals.update.translations.migrationList) === -1) {
                selector.append("<br><hr><br><p><strong>" + globals.update.translations.migrationList + "</strong></p>");
            }

            let message = globals.update.translations.migration
                .replace("_NAME_", name);

            selector.append("<p>" + message + "</p>");
        }

        $("#contine_button").click(function () {
            swal({
                title: globals.update.translations.title,
                text: "",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn btn-lg btn-success",
                confirmButtonText: globals.update.translations.continue,
            }).then(function () {
                $("#update-info").removeClass("hidden");
                $("#contine_button").remove();

                executeUpdate();
            }, function () {
            });
        });
    </script>
@endsection
