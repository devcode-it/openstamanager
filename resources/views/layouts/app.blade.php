@extends('layouts.base')

@php
    // Fix per inizializzazione variabili comuni
    $module = \Models\Module::getCurrent();
    $id_module = $module ? $module->id : null;
    $id_record = isset($id_record) ? $id_record : null;

    $user = auth()->user();
@endphp

@section('js')
<script>
    search = [];

    @foreach(getSessionSearch($id_module) as $key => $value)
    search.push("search_{{ $key }}");
    search["search_{{ $key }}"] = "{{ $value }}";
    @endforeach

    globals = {
        rootdir: '{{ base_url() }}',

        id_module: '{{ $id_module }}',
        id_record: '{{ $id_record }}',

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
            "day": "{{ tr('Giorno') }}",
            "week": "{{ tr('Settimana') }}",
            "month": "{{ tr('Mese') }}",
            "today": "{{ tr('Oggi') }}",
            "firstThreemester": "{{ tr('I trimestre') }}",
            "secondThreemester": "{{ tr('II trimestre') }}",
            "thirdThreemester": "{{ tr('III trimestre') }}",
            "fourthThreemester": "{{ tr('IV trimestre') }}",
            "firstSemester": "{{ tr('I semestre') }}",
            "secondSemester": "{{ tr('II semestre') }}",
            "thisMonth": "{{ tr('Questo mese') }}",
            "lastMonth": "{{ tr('Mese scorso') }}",
            "thisYear": "{{ tr("Quest'anno") }}",
            "lastYear": "{{ tr('Anno scorso') }}",
            "apply": "{{ tr('Applica') }}",
            "cancel": "{{ tr('Annulla') }}",
            "from": "{{ tr('Da') }}",
            "to": "{{ tr('A') }}",
            "custom": "{{ tr('Personalizzato') }}",
            "delete": "{{ tr('Elimina') }}",
            "deleteTitle": "{{ tr('Sei sicuro?') }}",
            "deleteMessage": "{{ tr('Eliminare questo elemento?') }}",
            "errorTitle": "{{ tr('Errore') }}",
            "errorMessage": "{{ tr("Si è verificato un errore nell'esecuzione dell'operazione richiesta") }}",
            "close": "{{ tr('Chiudi') }}",
            "filter": "{{ tr('Filtra') }}",
            "long": "{{ tr('La ricerca potrebbe richiedere del tempo') }}",
            "details": "{{ tr('Dettagli') }}",
            "waiting": "{{ tr('Impossibile procedere') }}",
            "waiting_msg": "{{ tr('Prima di proseguire devi selezionare alcuni elementi!') }}",
            'hooksExecuting': "{{ tr('Hooks in esecuzione') }}",
            'hookExecuting': '{{ tr('Hook "_NAME_" in esecuzione') }}',
            'hookMultiple': "{{ tr('Hai _NUM_ notifiche') }}",
            'hookSingle': "{{ tr('Hai 1 notifica') }}",
            'hookNone': "{{ tr('Nessuna notifica') }}",
            'singleCalendar': "{{ tr("E' presente un solo periodo!") }}",
            ajax: {
                "missing": {
                    "title": "{{ tr('Errore') }}",
                    "text": "{{ tr('Alcuni campi obbligatori non sono stati compilati correttamente') }}",
                },
                "error": {
                    "title": "{{ tr('Errore') }}",
                    "text": "{{ tr('Errore durante il salvataggio del record') }}",
                }
            },
            password: {
                "wordMinLength": "{{ tr('La password è troppo corta') }}",
                "wordMaxLength": "{{ tr('La password è troppo lunga') }}",
                "wordInvalidChar": "{{ tr('La password contiene un carattere non valido') }}",
                "wordNotEmail": "{{ tr('Non usare la tua e-mail come password') }}",
                "wordSimilarToUsername": "{{ tr('La password non può contenere il tuo nome') }}",
                "wordTwoCharacterClasses": "{{ tr('Usa classi di caratteri diversi') }}",
                "wordRepetitions": "{{ tr('La password contiene ripetizioni') }}",
                "wordSequences": "{{ tr('La password contiene sequenze') }}",
                "errorList": "{{ tr('Attenzione') }}:",
                "veryWeak": "{{ tr('Molto debole') }}",
                "weak": "{{ tr('Debole') }}",
                "normal": "{{ tr('Normale') }}",
                "medium": "{{ tr('Media') }}",
                "strong": "{{ tr('Forte') }}",
                "veryStrong": "{{ tr('Molto forte') }}",
            },
            datatables: {
                "emptyTable": "{{ tr('Nessun dato presente nella tabella') }}",
                "info": "{{ tr('Vista da _START_ a _END_ di _TOTAL_ elementi') }}",
                "infoEmpty": "{{ tr('Vista da 0 a 0 di 0 elementi') }}",
                "infoFiltered": "({{ tr('filtrati da _MAX_ elementi totali') }})",
                "infoPostFix": "",
                "lengthMenu": "{{ tr('Visualizza _MENU_ elementi') }}",
                "loadingRecords": " ",
                "processing": "{{ tr('Elaborazione') }}...",
                "search": "{{ tr('Cerca') }}:",
                "zeroRecords": "{{ tr('La ricerca non ha portato alcun risultato') }}.",
                "paginate": {
                    "first": "{{ tr('Inizio') }}",
                    "previous": "{{ tr('Precedente') }}",
                    "next": "{{ tr('Successivo') }}",
                    "last": "{{ tr('Fine') }}"
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

        order_manager_id: '{{ module('Stato dei servizi')['id'] }}',
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
        <a href="https://www.openstamanager.com" class="logo" title="{{ tr("Il gestionale open source per l'assistenza tecnica e la fatturazione") }}" target="_blank">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini">{{ tr("OSM") }}</span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg">
                {{ tr('OpenSTAManager') }}
            </span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                <span class="sr-only">{{ tr('Mostra/nascondi menu') }}</span>
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

                    <li><a href="#" onclick="window.print()" class="tip" title="{{ tr('Stampa') }}">
                        <i class="fa fa-print"></i>
                    </a></li>

                    <li><a href="{{ route('bug') }}" class="tip" title="{{ tr('Segnalazione bug') }}">
                        <i class="fa fa-bug"></i>
                    </a></li>

                    <li><a href="{{ route('logs') }}" class="tip" title="{{ tr('Log accessi') }}">
                        <i class="fa fa-book"></i>
                    </a></li>

                    <li><a href="{{ route('info') }}" class="tip" title="{{ tr('Informazioni') }}">
                        <i class="fa fa-info"></i>
                    </a></li>

                    <li><a href="{{ route('logout') }}" onclick="sessionStorage.clear()" class="bg-red tip" title="{{ tr('Esci') }}">
                        <i class="fa fa-power-off"></i>
                    </a></li>
                </ul>
            </div>

        </nav>
    </header>

    <aside class="main-sidebar"><!--  sidebar-dark-primary elevation-4 -->
        <section class="sidebar">
            <!-- Sidebar user panel -->
            <a href="{{ route('user-info') }}" class="user-panel text-center info" style="height: 60px">
                <div class="text-center mt-2">
                    @if ($user->photo)
                        <img src="{{ $user->photo }}" class="profile-user-img img-fluid img-circle" alt="{{ $user->username }}" />
                    @else
                        <i class="fa fa-user-circle-o fa-5x"></i>
                    @endif
                </div>

                <h3 class="profile-username text-center" style="width: 100%; padding-top: 10px;">
                    {{ $user->username }}
                </h3>
                <p class="text-center" id="datetime"></p>
            </a>

            <!-- Form di ricerca generale -->
            <div class="sidebar-form">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" id="supersearch" placeholder="{{ tr('Cerca') }}..."/>
                    <span class="input-group-btn">
                        <button class="btn btn-flat" id="search-btn" name="search" type="submit" ><i class="fa fa-search"></i>
                        </button>
                    </span>
                </div>
            </div>

            <ul class="sidebar-menu"><!-- class="nav nav-pills nav-sidebar nav-sidebar nav-child-indent flex-column" data-widget="treeview" role="menu" data-accordion="true"  isset($main_menu) ? $main_menu : null -->
                {!! Modules::getMainMenu() !!}
            </ul>
        </section>
        <!-- /.sidebar -->
    </aside>

    <!-- Right side column. Contains the navbar and content of the page -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <a class="hidden-xs" href="https://www.openstamanager.com" title="{{ tr("Il gestionale open source per l'assistenza tecnica e la fatturazione") }}" target="_blank"><strong>{{ tr('OpenSTAManager') }}</strong></a>
        <span class="pull-right hidden-xs">
            <strong>{{ tr('Versione') }}:</strong> {{ Update::getVersion() }}
            <small class="text-muted">({{ Update::getRevision() ?: tr('In sviluppo') }})</small>
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
