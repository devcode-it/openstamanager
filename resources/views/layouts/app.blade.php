@extends('layouts.base')

@section('js')
<script>
    search = [];

    @foreach($search as $key => $value)
    search.push("search_{{ $key }}");
    search["search_{{ $key }}"] = "{{ $value }}";
    @endforeach

    globals = {
        rootdir: '{{ base_url() }}',

        id_module: '{{ id_module }}',
        id_record: '{{ id_record }}',

        is_mobile: {{ intval(isMobile()) }},
        cifre_decimali: '{{ setting('Cifre decimali per importi') }}',

        timestamp_format: "{{ formatter()->getTimestampPattern() }}",
        date_format: "{{ formatter()->getDatePattern() }}",
        time_format: "{{ formatter()->getTimePattern() }}",
        decimals: "{{ formatter()->getNumberSeparators()['decimals'] }}",
        thousands: "{{ formatter()->getNumberSeparators()['thousands'] }}",

        currency: "{{ currency() }}",

        search: search,
        translations: {
            "day": "{{ __('Giorno') }}",
            "week": "{{ __('Settimana') }}",
            "month": "{{ __('Mese') }}",
            "today": "{{ __('Oggi') }}",
            "firstThreemester": "{{ __('I trimestre') }}",
            "secondThreemester": "{{ __('II trimestre') }}",
            "thirdThreemester": "{{ __('III trimestre') }}",
            "fourthThreemester": "{{ __('IV trimestre') }}",
            "firstSemester": "{{ __('I semestre') }}",
            "secondSemester": "{{ __('II semestre') }}",
            "thisMonth": "{{ __('Questo mese') }}",
            "lastMonth": "{{ __('Mese scorso') }}",
            "thisYear": "{{ __("Quest'anno") }}",
            "lastYear": "{{ __('Anno scorso') }}",
            "apply": "{{ __('Applica') }}",
            "cancel": "{{ __('Annulla') }}",
            "from": "{{ __('Da') }}",
            "to": "{{ __('A') }}",
            "custom": "{{ __('Personalizzato') }}",
            "delete": "{{ __('Elimina') }}",
            "deleteTitle": "{{ __('Sei sicuro?') }}",
            "deleteMessage": "{{ __('Eliminare questo elemento?') }}",
            "errorTitle": "{{ __('Errore') }}",
            "errorMessage": "{{ __("Si è verificato un errore nell'esecuzione dell'operazione richiesta") }}",
            "close": "{{ __('Chiudi') }}",
            "filter": "{{ __('Filtra') }}",
            "long": "{{ __('La ricerca potrebbe richiedere del tempo') }}",
            "details": "{{ __('Dettagli') }}",
            "waiting": "{{ __('Impossibile procedere') }}",
            "waiting_msg": "{{ __('Prima di proseguire devi selezionare alcuni elementi!') }}",
            'hooksExecuting': "{{ __('Hooks in esecuzione') }}",
            'hookExecuting': '{{ __('Hook "_NAME_" in esecuzione') }}',
            'hookMultiple': "{{ __('Hai _NUM_ notifiche') }}",
            'hookSingle': "{{ __('Hai 1 notifica') }}",
            'hookNone': "{{ __('Nessuna notifica') }}",
            'singleCalendar': {{ __("E' presente un solo periodo!") }}",
            ajax: {
                "missing": {
                    "title": "{{ __('Errore') }}",
                    "text": "{{ __('Alcuni campi obbligatori non sono stati compilati correttamente') }}",
                },
                "error": {
                    "title": "{{ __('Errore') }}",
                    "text": "{{ __('Errore durante il salvataggio del record') }}",
                }
            },
            password: {
                "wordMinLength": "{{ __('La password è troppo corta') }}",
                "wordMaxLength": "{{ __('La password è troppo lunga') }}",
                "wordInvalidChar": "{{ __('La password contiene un carattere non valido') }}",
                "wordNotEmail": "{{ __('Non usare la tua e-mail come password') }}",
                "wordSimilarToUsername": "{{ __('La password non può contenere il tuo nome') }}",
                "wordTwoCharacterClasses": "{{ __('Usa classi di caratteri diversi') }}",
                "wordRepetitions": "{{ __('La password contiene ripetizioni') }}",
                "wordSequences": "{{ __('La password contiene sequenze') }}",
                "errorList": "{{ __('Attenzione') }}:",
                "veryWeak": "{{ __('Molto debole') }}",
                "weak": "{{ __('Debole') }}",
                "normal": "{{ __('Normale') }}",
                "medium": "{{ __('Media') }}",
                "strong": "{{ __('Forte') }}",
                "veryStrong": "{{ __('Molto forte') }}",
            },
            datatables: {
                "emptyTable": "{{ __('Nessun dato presente nella tabella') }}",
                "info": "{{ __('Vista da _START_ a _END_ di _TOTAL_ elementi') }}",
                "infoEmpty": "{{ __('Vista da 0 a 0 di 0 elementi') }}",
                "infoFiltered": "({{ __('filtrati da _MAX_ elementi totali') }})",
                "infoPostFix": "",
                "lengthMenu": "{{ __('Visualizza _MENU_ elementi') }}",
                "loadingRecords": " ",
                "processing": "{{ __('Elaborazione') }}...",
                "search": "{{ __('Cerca') }}:",
                "zeroRecords": "{{ __('La ricerca non ha portato alcun risultato') }}.",
                "paginate": {
                    "first": "{{ __('Inizio') }}",
                    "previous": "{{ __('Precedente') }}",
                    "next": "{{ __('Successivo') }}",
                    "last": "{{ __('Fine') }}"
                },
            },
        },

        locale: '{{ app()->getLocale() }}',
        full_locale: '{{ app()->getLocale() }}',

        start_date: '{{ session('period_start') }}',
        start_date_formatted: '{{ dateFormat(session('period_start')) }}',
        end_date: '{{ session('period_end') }}',
        end_date_formatted: '{{ dateFormat(session('period_end')) }}',

        ckeditorToolbar: [
            ["Undo","Redo","-","Cut","Copy","Paste","PasteText","PasteFromWord","-","Scayt", "-","Link","Unlink","-","Bold","Italic","Underline","Superscript","SpecialChar","HorizontalRule","-","JustifyLeft","JustifyCenter","JustifyRight","JustifyBlock","-","NumberedList","BulletedList","Outdent","Indent","Blockquote","-","Styles","Format","Image","Table", "TextColor", "BGColor" ],
        ],

        order_manager_id: '{{ module('Stato dei serivizi')['id'] }}',
        tempo_attesa_ricerche: {{ setting('Tempo di attesa ricerche in secondi') }},
        restrict_summables_to_selected: {{ setting('Totali delle tabelle ristretti alla selezione') }},
        select_url: "{{ route('ajax-select') }}",
        dataload_url: "{{ route('ajax-dataload', ['module_id' => '|module_id|']) }}",
        dataload_url_plugin: "{{ route('ajax-dataload', ['module_id' => '|module_id|', 'reference_id' => '|reference_id|']) }}",
        ajax_set_url: "{{ route('ajax-session') }}",
        ajax_array_set_url: "{{ route('ajax-session-array') }}",
        messages_url: "{{ route('messages') }}",
        hooks: {
            list: "{{ route('hooks') }}",
            lock: "{{ route('hook-lock', ['hook_id' => '|id|']) }}",
            execute: "{{ route('hook-execute', ['hook_id' => '|id|', 'token' => '|token|']) }}",
            response: "{{ route('hook-response', ['hook_id' => '|id|']) }}",
        }
    };
</script>

    @if (setting('Abilita esportazione Excel e PDF'))
    <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    @endif
@endsection

@section('body')
<div class="wrapper">
    <header class="main-header">
        <a href="https://www.openstamanager.com" class="logo" title="{{ __("Il gestionale open source per l'assistenza tecnica e la fatturazione") }}" target="_blank">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini">{{ __("OSM") }}</span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg">
                {{ __('OpenSTAManager') }}
            </span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                <span class="sr-only">{{ __('Mostra/nascondi menu') }}</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>

            <!-- Navbar Left Menu -->
            @php($not_current_year = session('period_start') != date('Y').'-01-01' || session('period_end') != date('Y').'-12-31')
            <div class="navbar-left hidden-xs">
                <ul class="nav navbar-nav hidden-xs">
                    <li><a href="#" id="daterange" class="{{ $not_current_year ? 'calendar-wrong' : '' }}" role="button">
                        <i class="fa fa-calendar" style="color:inherit"></i> <i class="fa fa-caret-down" style="color:inherit"></i>
                    </a></li>

                    <li><a class="{{ $not_current_year ? 'calendar-wrong' : '' }}" style="background:inherit;cursor:default;">
                            {{ dateFormat(session('period_start')) }} - {{ dateFormat(session('period_end')) }}
                    </a></li>
                </ul>
            </div>

            <!-- Navbar Right Menu -->
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li class="dropdown notifications-menu" >
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-bell-o"></i>
                            <span id="hooks-label" class="label label-warning">
                                <span id="hooks-loading"><i class="fa fa-spinner fa-spin"></i></span>
                                <span id="hooks-notified"></span>
                                <span id="hooks-counter" class="hide">0</span>
                                <span id="hooks-number" class="hide">0</span>
                            </span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="header"><span class="small" id="hooks-header"></span></li>
                            <li><ul class="menu" id="hooks">

                            </ul></li>
                        </ul>
                    </li>

                    <li><a href="#" onclick="window.print()" class="tip" title="{{ __('Stampa') }}">
                        <i class="fa fa-print"></i>
                    </a></li>

                    <li><a href="{{ base_url() }}/{{ record_id ? __('editor.php?id_record=' ~ record_id ~ '&' : 'controller.php?' }}id_module={{ module_id }}" class="tip btn-warning" title="{{ 'Base') }}">
                        <i class="fa fa-fast-backward"></i>
                    </a></li>

                    <li><a href="{{ route('bug') }}" class="tip" title="{{ __('Segnalazione bug') }}">
                        <i class="fa fa-bug"></i>
                    </a></li>

                    <li><a href="{{ route('logs') }}" class="tip" title="{{ __('Log accessi') }}">
                        <i class="fa fa-book"></i>
                    </a></li>

                    <li><a href="{{ route('info') }}" class="tip" title="{{ __('Informazioni') }}">
                        <i class="fa fa-info"></i>
                    </a></li>

                    <li><a href="{{ route('logout') }}" onclick="sessionStorage.clear()" class="bg-red tip" title="{{ __('Esci') }}">
                        <i class="fa fa-power-off"></i>
                    </a></li>
                </ul>
            </div>

        </nav>
    </header>

    <aside class="main-sidebar"><!--  sidebar-dark-primary elevation-4 -->
        <section class="sidebar">
            <!-- Sidebar user panel -->
            <a href="{{ route('user') }}" class="user-panel text-center info" style="height: 60px">
                <div class="text-center mt-2">
                    @if (auth()->user()->photo)
                        <img src="{{ base_url() }}{{ auth()->user()->photo }}" class="profile-user-img img-fluid img-circle" alt="{{ auth()->user()->username }}" />
                    @else
                        <i class="fa fa-user-circle-o fa-5x"></i>
                    @endif
                </div>

                <h3 class="profile-username text-center" style="width: 100%; padding-top: 10px;">
                    {{ auth()->user()->username }}
                </h3>
                <p class="text-center" id="datetime"></p>
            </a>

            <!-- Form di ricerca generale -->
            <div class="sidebar-form">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" id="supersearch" placeholder="{{ __('Cerca') }}..."/>
                    <span class="input-group-btn">
                        <button class="btn btn-flat" id="search-btn" name="search" type="submit" ><i class="fa fa-search"></i>
                        </button>
                    </span>
                </div>
            </div>

            <ul class="sidebar-menu"><!-- class="nav nav-pills nav-sidebar nav-sidebar nav-child-indent flex-column" data-widget="treeview" role="menu" data-accordion="true" -->
                {{ main_menu|raw }}
            </ul>
        </section>
        <!-- /.sidebar -->
    </aside>

    <!-- Right side column. Contains the navbar and content of the page -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @section('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <a class="hidden-xs" href="https://www.openstamanager.com" title="{{ __("Il gestionale open source per l'assistenza tecnica e la fatturazione") }}" target="_blank"><strong>{{ __('OpenSTAManager') }}</strong></a>
        <span class="pull-right hidden-xs">
            <strong>{{ __('Versione') }}:</strong> {{ Update::getVersion() }}
            <small class="text-muted">({{ Update::getRevision() ?: __('In sviluppo') }})</small>
        </span>
    </footer>

    <div id="modals"></div>

    <script>
        function closeModal(element) {
            let modal = $(element).closest(".modal");
            modal.fadeOut("slow", function() {
                if ($("#modals").children().length <= 1){
                    $("body").removeClass("modal-open");
                    $(".modal-backdrop").remove();
                }

                modal.remove();
            });
        }

        $(document).ready(function() {
            // Toast
            alertPush();

            // Hooks
            startHooks();

            // Abilitazione del cron autonoma
            $.get(globals.rootdir + "/cron.php");
        });
    </script>
</div>
@endsection
