@extends('layouts.base')

@section('body_class', 'bg-light')
@section('title', tr("Configurazione"))

@section('body')
<div class="container pb-5">
    <form action="{{ route('configuration-save') }}" method="post" id="config-form">
        @csrf

        <div class="py-5 text-center">
            <img class="d-block mx-auto mb-4" src="{{ url('/') }}/assets/img/full_logo.png" alt="{{ tr('Logo OpenSTAManager') }}">
            <h2>{!! tr('Benvenuto in _NAME_!', ['_NAME_' => '<strong>'.tr('OpenSTAManager').'</strong>']) !!}</h2>
            <p class="lead">{!! tr('Puoi procedere alla configurazione tecnica del software attraverso i parametri seguenti, che potranno essere corretti secondo necessità tramite il file _FILE_', ['_FILE_' => '<i>.env</i>']) !!}. </p>

            <p>{!! tr("Se necessiti supporto puoi contattarci tramite l'assistenza ufficiale: _LINK_", ['_LINK_' => '<a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM" target="_blank">https://www.openstamanager.com/contattaci/</a>' ]) !!}</p>
        </div>

        <div class="row">
            <div class="col-md-4 col-md-push-8 mb-4">
                <h4 class="d-flex justify-content-between align-items-center mb-3 text-muted">{{ tr('Lingua') }}</h4>
                <select class="form-control hidden" id="language" name="language" required="1">
                    @foreach($languages as $code => $language)
                        <option data-country="{{ $language['flag'] }}" value="{{ $code }}">{{ $language['title'] }}</option>
                    @endforeach
                </select>
                <p id="language-info">{{ tr('Caricamento lingue in corso') }}...</p>

                <hr class="mb-4">

                <h4 class="d-flex justify-content-between align-items-center mb-3 text-muted">{{ tr('Licenza') }}</h4>
                <p>{{ tr('OpenSTAManager è tutelato dalla licenza _LICENSE_, da accettare obbligatoriamente per poter utilizzare il gestionale', ['_LICENSE_' => 'GPL 3.0']) }}.</p>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="agree" name="agree" required>
                    <label class="custom-control-label" for="agree">{{ tr('Ho visionato e accetto la licenza') }}</label>
                </div>

                <textarea class="form-control" rows="15" readonly>{{ $license }}</textarea><br>
                <a class="float-left" href="https://www.gnu.org/licenses/translations.en.html#GPL" target="_blank">[ {{ tr('Versioni tradotte') }} ]</a><br><br>
            </div>

            <div class="col-md-8 col-md-pull-4">
                <h4 class="mb-3">{{ tr('Formato date') }}</h4>
                <div class="row">
                    <div class="col-md-4">
                        {[ "type": "text", "label": "{{ tr('Formato data lunga') }}", "name": "timestamp_format", "value": "d/m/Y H:i", "required": 1 ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "text", "label": "{{ tr('Formato data corta') }}", "name": "date_format", "value": "d/m/Y", "required": 1 ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "text", "label": "{{ tr('Formato orario') }}", "name": "time_format", "value": "H:i", "required": 1 ]}
                    </div>
                </div>

                <small>{{ tr('I formati sono impostabili attraverso lo standard previsto da PHP: _LINK_', ['_LINK_' => '<a href="https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters">https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters</a>']) }}.</small>
                <hr class="mb-4">

                <h4 class="mb-3">{{ tr('Database') }}</h4>

                <!-- db_host -->
                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "text", "label": "{{ tr('Host del database') }}", "name": "host", "placeholder": "{{ tr("Indirizzo dell'host del database") }}", "value": "{{ $host }}", "help": "{{ tr('Esempio') }}: localhost", "show-help": 0, "required": 1 ]}
                    </div>
                </div>

                <!-- db_username -->
                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "text", "label": "{{ tr("Username dell'utente MySQL") }}", "name": "username", "placeholder": "{{ tr("Username") }}", "value": "{{ $username }}", "help": "{{ tr('Esempio') }}: root", "show-help": 0, "required": 1 ]}
                    </div>
                </div>

                <!-- db_password -->
                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "password", "label": "{{ tr("Password dell'utente MySQL") }}", "name": "password", "placeholder": "{{ tr("Password") }}", "help": "{{ tr('Esempio') }}: mysql", "show-help": 0 ]}
                    </div>
                </div>

                <!-- db_name -->
                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "text", "label": "{{ tr('Nome del database') }}", "name": "database_name", "placeholder": "{{ tr('Database') }}", "value": "{{ $database_name }}", "help": "{{ tr('Esempio') }}: openstamanager", "show-help": 0, "required": 1 ]}
                    </div>
                </div>

                <hr class="mb-4">

                <!-- PULSANTI -->
                <div class="row">
                    <div class="col-md-4">
                        <span>*<small><small>{{ tr('Campi obbligatori') }}</small></small></span>
                    </div>
                    <div class="col-md-4 text-right">
                        <button type="button" id="test" class="btn btn-warning btn-block">
                            <i class="fa fa-file-text"></i> {{ tr('Testa il database') }}
                        </button>
                    </div>
                    <div class="col-md-4 text-right">
                        <button type="submit" id="install" class="btn btn-success btn-block">
                            <i class="fa fa-check"></i> {{ tr('Installa') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js')
<script>
    globals.configuration = {
        test_url: "{{ route('configuration-test') }}",
        translations: {
            error: "{{ tr('Errore della configurazione') }}",
            errorMessage: "{{ tr('La configurazione non è corretta') }}.",

            permissions: "{{ tr('Permessi insufficienti') }}",
            permissionsMessage: "{{ tr("L'utente non possiede permessi sufficienti per il testing della connessione. Potresti rilevare problemi in fae di installazione.") }}",

            success: "{{ tr('Configurazione corretta') }}",
            successMessage: "{{ tr('Ti sei connesso con successo al database') }}. {{ tr("Clicca su 'Installa' per proseguire") }}.",
        }
    };
</script>

<script type="text/javascript">
    var flag_link = "https://lipis.github.io/flag-icon-css/flags/4x3/|flag|.svg";

    $(document).ready(function() {
        init();

        $.ajax({
            url: flag_link.replace("|flag|", "it"),
            success: function(){
                initLanguage(true);
            },
            error: function(){
                initLanguage(false);
            },
            timeout: 500
        });

        $("#install").on("click", function() {
            if ($(this).closest("form").parsley().validate()) {
                let restore = buttonLoading("#install");
                $("#config-form").submit();

                //buttonRestore("#install", restore);
            }
        });

        $("#test").on("click", function() {
            if ($(this).closest("form").parsley().validate()) {
                let restore = buttonLoading("#test");
                $("#install").prop('disabled', true);

                $(this).closest("form").ajaxSubmit({
                    url: globals.configuration.test_url,
                    data: {
                        test: 1,
                    },
                    type: "get",
                    success: function (data) {
                        data = parseFloat(data.trim());

                        buttonRestore("#test", restore);
                        $("#install").prop('disabled', false);

                        if (data === 0) {
                            swal(globals.configuration.translations.error, globals.configuration.translations.errorMessage, "error");
                        } else if (data === 1) {
                            swal(globals.configuration.translations.permissions, globals.configuration.translations.permissionsMessage, "error");
                        } else {
                            swal(globals.configuration.translations.success, globals.configuration.translations.successMessage, "success");
                        }
                    },
                    error: function (xhr, error, thrown) {
                        ajaxError(xhr, error, thrown);
                    }
                });
            }
        });
    });

    function languageFlag(item) {
        if (!item.id) {
            return item.text;
        }

        let element = $(item.element);
        let img = $("<img>", {
            class: "img-flag",
            width: 26,
            src: flag_link.replace("|flag|", element.data("country").toLowerCase()),
        });

        let span = $("<span>", {
            text: " " + item.text,
        });
        span.prepend(img);

        return span;
    }

    function initLanguage() {
        let language = $("#language");
        language.removeClass("hidden");
        $("#language-info").addClass("hidden");

        language.select2({
            theme: "bootstrap",
            templateResult: languageFlag,
            templateSelection:languageFlag,
        });

        // Preselezione lingua
        if (globals.full_locale) {
            language.selectSet(globals.full_locale);
        }

        language.on("change", function(){
            if ($(this).val()) {
                let location = window.location;
                let url = location.protocol + "//" + location.host + "" + location.pathname;

                let parameters = getUrlVars();
                parameters.lang = $(this).val();

                redirect_legacy(url, parameters);
            }
        });
    }
</script>
@endsection
