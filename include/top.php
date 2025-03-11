<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Models\Module;
use Models\Plugin;
use Util\FileSystem;

include_once __DIR__.'/../core.php';

$paths = App::getPaths();
$user = Auth::user();

if (empty($pageTitle)) {
    if ($structure instanceof Module) {
        if ($structure->getTranslation('meta_title') && !empty($id_record)) {
            $pageTitle = $structure->replacePlaceholders($id_record, $structure->getTranslation('meta_title'));
        } else {
            $pageTitle = $structure->getTranslation('title');
        }
    } elseif ($structure) {
        $pageTitle = $structure->getTranslation('title');
    } else {
        $pageTitle = tr('OpenSTAManager');
    }
}

$lang = (empty($lang) || $lang == '|lang|') ? 'it_IT' : $lang;

$messages = flash()->getMessages();

echo '<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>'.$pageTitle.'</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

        <meta name="robots" content="noindex,nofollow">
        <meta name="description" content="'.tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica.').'">
        <meta name="author" content="DevCode s.r.l.">

		<link href="'.$paths['img'].'/favicon.png" rel="icon" type="image/x-icon" />';

if (file_exists(base_dir().'/manifest.json')) {
    echo '
        <link rel="manifest" href="'.base_path().'/manifest.json?r='.random_int(0, mt_getrandmax()).'">';
}

// CSS
foreach (App::getAssets()['css'] as $style) {
    echo '
        <link rel="stylesheet" type="text/css" media="all" href="'.$style.'"/>';
}

// Print CSS
foreach (App::getAssets()['print'] as $style) {
    echo '
        <link rel="stylesheet" type="text/css" media="print" href="'.$style.'"/>';
}

if (Auth::check()) {
    if ($id_record) {
        $position = 'tab';
    } else {
        $position = 'tab_main';
    }

    $has_plugins = Plugin::where('idmodule_to', $id_module)->where('position', $position)->where('enabled', 1)->count();

    echo '
		<script>
            search = []';

    $array = $_SESSION['module_'.$id_module];
    if (!empty($array)) {
        foreach ($array as $field => $value) {
            if (!empty($value) && string_starts_with($field, 'search_')) {
                $field_name = str_replace('search_', '', $field);

                echo '
            search.push("search_'.$field_name.'");
            search["search_'.$field_name.'"] = "'.addslashes((string) $value).'";';
            }
        }
    }

    echo '
            translations = {';
    $translations = [
        'day' => tr('Giorno'),
        'week' => tr('Settimana'),
        'month' => tr('Mese'),
        'today' => tr('Oggi'),
        'firstThreemester' => tr('I trimestre'),
        'secondThreemester' => tr('II trimestre'),
        'thirdThreemester' => tr('III trimestre'),
        'fourthThreemester' => tr('IV trimestre'),
        'firstSemester' => tr('I semestre'),
        'secondSemester' => tr('II semestre'),
        'thisMonth' => tr('Questo mese'),
        'lastMonth' => tr('Mese scorso'),
        'nextMonth' => tr('Mese prossimo'),
        'thisYear' => tr("Quest'anno"),
        'lastYear' => tr('Anno scorso'),
        'lastYear_thisYear' => tr("Quest'anno + prec."),
        'apply' => tr('Applica'),
        'cancel' => tr('Annulla'),
        'from' => tr('Da'),
        'to' => tr('A'),
        'custom' => tr('Personalizzato'),
        'delete' => tr('Elimina'),
        'deleteTitle' => tr('Sei sicuro?'),
        'deleteMessage' => tr('Eliminare questo elemento?'),
        'errorTitle' => tr('Errore'),
        'errorMessage' => tr("Si è verificato un errore nell'esecuzione dell'operazione richiesta"),
        'close' => tr('Chiudi'),
        'filter' => tr('Filtra'),
        'long' => tr('La ricerca potrebbe richiedere del tempo'),
        'details' => tr('Dettagli'),
        'loading' => tr('Caricamento'),
        'waiting' => tr('Impossibile procedere'),
        'waitingMessage' => tr('Prima di proseguire devi selezionare alcuni elementi.'),
        'hooksExecuting' => tr('Hooks in esecuzione'),
        'hookExecuting' => tr('Hook "_NAME_" in esecuzione'),
        'hookMultiple' => tr('Hai _NUM_ notifiche'),
        'hookSingle' => tr('Hai 1 notifica'),
        'hookNone' => tr('Nessuna notifica'),
        'singleCalendar' => tr("E' presente un solo periodo."),
        'noResults' => tr('Nessun elemento trovato'),
        'signatureMissing' => tr('Firma mancante.'),
        'readmore' => tr('Mostra tutto'),
        'readless' => tr('Mostra meno'),
    ];
    foreach ($translations as $key => $value) {
        echo '
                '.$key.': "'.addslashes($value).'",';
    }
    echo '
                allegati: {
                    messaggio: "'.tr('Clicca o trascina qui per caricare uno o più file').'",
                    maxFilesize: "'.tr('Dimensione massima: _SIZE_ MB').'",
                    errore: "'.tr('Errore').'",
                    modifica: "'.tr('Modifica allegato').'",
                    elimina: "'.tr('Vuoi eliminare questo file?').'",
                    procedi: "'.tr('Procedi').'",
                },
                ajax: {
                    "missing": {
                        "title": "'.tr('Errore').'",
                        "text": "'.tr('Alcuni campi obbligatori non sono stati compilati correttamente').'",
                    },
                    "error": {
                        "title": "'.tr('Errore').'",
                        "text": "'.tr('Errore durante il salvataggio del record').'",
                    }
                },
                password: {
                    "wordMinLength": "'.tr('La password è troppo corta').'",
                    "wordMaxLength": "'.tr('La password è troppo lunga').'",
                    "wordInvalidChar": "'.tr('La password contiene un carattere non valido').'",
                    "wordNotEmail": "'.tr('Non usare la tua e-mail come password').'",
                    "wordSimilarToUsername": "'.tr('La password non può contenere il tuo nome').'",
                    "wordTwoCharacterClasses": "'.tr('Usa classi di caratteri diversi').'",
                    "wordRepetitions": "'.tr('La password contiene ripetizioni').'",
                    "wordSequences": "'.tr('La password contiene sequenze').'",
                    "errorList": "'.tr('Attenzione').':",
                    "veryWeak": "'.tr('Molto debole').'",
                    "weak": "'.tr('Debole').'",
                    "normal": "'.tr('Normale').'",
                    "medium": "'.tr('Media').'",
                    "strong": "'.tr('Forte').'",
                    "veryStrong": "'.tr('Molto forte').'",
                },
                datatables: {
                    "emptyTable": "'.tr('Nessun dato presente nella tabella').'",
                    "info": "'.tr('Vista da _START_ a _END_ di _TOTAL_ elementi').'",
                    "infoEmpty": "'.tr('Vista da 0 a 0 di 0 elementi').'",
                    "infoFiltered": "('.tr('filtrati da _MAX_ elementi totali').')",
                    "infoPostFix": "",
                    "lengthMenu": "'.tr('Visualizza _MENU_ elementi').'",
                    "loadingRecords": " ",
                    "processing": "'.tr('Elaborazione').'...",
                    "search": "'.tr('Cerca').':",
                    "zeroRecords": "'.tr('La ricerca non ha portato alcun risultato').'.",
                    "paginate": {
                        "first": "'.tr('Inizio').'",
                        "previous": "'.tr('Precedente').'",
                        "next": "'.tr('Successivo').'",
                        "last": "'.tr('Fine').'"
                    },
                },
            };
			globals = {
                rootdir: "'.base_path().'",
                js: "'.$paths['js'].'",
                css: "'.$paths['css'].'",
                img: "'.$paths['img'].'",

                id_module: "'.$id_module.'",
                id_record: "'.$id_record.'",

                is_mobile: '.isMobile().',

                cifre_decimali: '.setting('Cifre decimali per importi').',

                timestamp_format: "'.formatter()->getTimestampPattern().'",
                date_format: "'.formatter()->getDatePattern().'",
                time_format: "'.formatter()->getTimePattern().'",
                decimals: "'.formatter()->getNumberSeparators()['decimals'].'",
                thousands: "'.formatter()->getNumberSeparators()['thousands'].'",
                currency: "'.currency().'",

                search: search,
                translations: translations,
                locale: "'.explode('_', (string) $lang)[0].'",
				full_locale: "'.$lang.'",

                start_date: "'.$_SESSION['period_start'].'",
                start_date_formatted: "'.Translator::dateToLocale($_SESSION['period_start']).'",
                end_date: "'.$_SESSION['period_end'].'",
                end_date_formatted: "'.Translator::dateToLocale($_SESSION['period_end']).'",
                minute_stepping: '.setting('Numero di minuti di avanzamento delle sessioni delle attività').',

                collapse_plugin_sidebar: '.($has_plugins ? intval(setting('Nascondere la barra dei plugin di default')) : 1).',

                ckeditorToolbar: [
					["Undo","Redo","-","Cut","Copy","Paste","PasteText","PasteFromWord","-","SpellChecker", "Scayt", "-","Link","Unlink","-","Bold","Italic","Underline","Superscript","SpecialChar","HorizontalRule","-","JustifyLeft","JustifyCenter","JustifyRight","JustifyBlock","-","NumberedList","BulletedList","Outdent","Indent","Blockquote","-","Styles","Format","Image","Table", "TextColor", "BGColor", "EmojiPanel" ],
				],
                ckeditorToolbar_Full: [
                    { name: "document", items : [ "Source", "ExportPdf", "Preview", "Print", "-", "Templates" ] },
                    { name: "clipboard", items : [ "Cut","Copy","Paste","PasteText","PasteFromWord","-","Undo","Redo" ] },
                    { name: "editing", items : [ "Find","Replace","-","SelectAll","-","SpellChecker", "Scayt" ] },
                    { name: "forms", items : [ "Form", "Checkbox", "Radio", "TextField", "Textarea", "Select", "Button", "ImageButton",
                        "HiddenField" ] },
                    "/",
                    { name: "basicstyles", items : [ "Bold","Italic","Underline","Strike","Subscript","Superscript","-","CopyFormatting","RemoveFormat" ] },
                    { name: "paragraph", items : [ "NumberedList","BulletedList","-","Outdent","Indent","-","Blockquote","CreateDiv",
                        "-","JustifyLeft","JustifyCenter","JustifyRight","JustifyBlock","-","BidiLtr","BidiRtl","Language" ] },
                    { name: "links", items : [ "Link","Unlink","Anchor" ] },
                    { name: "insert", items : [ "Image","Flash","Table","HorizontalRule","EmojiPanel","SpecialChar","PageBreak","Iframe" ] },
                    "/",
                    { name: "styles", items : [ "Styles","Format","Font","FontSize" ] },
                    { name: "colors", items : [ "TextColor","BGColor" ] },
                    { name: "tools", items : [ "Maximize", "ShowBlocks" ] },
                    { name: "about", items: [ "About" ] }
                ],
                order_manager_id: "'.($dbo->isInstalled() ? Module::where('name', 'Stato dei servizi')->first()->id : '').'",
                dataload_page_buffer: '.setting('Lunghezza in pagine del buffer Datatables').',
                tempo_attesa_ricerche: '.setting('Tempo di attesa ricerche in secondi').',
                restrict_summables_to_selected: '.setting('Totali delle tabelle ristretti alla selezione').',
                snapDuration: "'.setting('Tempo predefinito di snap attività sul calendario').'"
            };
		</script>';
} else {
    echo '
        <script>
            globals = {
                rootdir: "'.base_path().'",

                search: {},
                translations: {
                    password: {
                        "wordMinLength": "'.tr('La tua password è troppo corta').'",
                        "wordMaxLength": "'.tr('La tua password è troppo lunga').'",
                        "wordInvalidChar": "'.tr('La tua password contiene un carattere non valido').'",
                        "wordNotEmail": "'.tr('Non usare la tua e-mail come password').'",
                        "wordSimilarToUsername": "'.tr('La tua password non può contenere il tuo nome').'",
                        "wordTwoCharacterClasses": "'.tr('Usa classi di caratteri diversi').'",
                        "wordRepetitions": "'.tr('Troppe ripetizioni').'",
                        "wordSequences": "'.tr('La tua password contiene sequenze').'",
                        "errorList": "'.tr('Errori').':",
                        "veryWeak": "'.tr('Molto debole').'",
                        "weak": "'.tr('Debole').'",
                        "normal": "'.tr('Normale').'",
                        "medium": "'.tr('Media').'",
                        "strong": "'.tr('Forte').'",
                        "veryStrong": "'.tr('Molto forte').'",
                    },
                },

                timestamp_format: "'.formatter()->getTimestampPattern().'",
                date_format: "'.formatter()->getDatePattern().'",
                time_format: "'.formatter()->getTimePattern().'",

                locale: "'.explode('_', (string) $lang)[0].'",
				full_locale: "'.$lang.'",
            };
        </script>';
}

// JS
foreach (App::getAssets()['js'] as $js) {
    echo '
        <script type="text/javascript" charset="utf-8" src="'.$js.'"></script>';
}

// Impostazioni di default per gli alert
echo '
        <script>
            swal.setDefaults({
                buttonsStyling: false,
                confirmButtonClass: "btn btn-lg btn-primary",
                cancelButtonClass: "btn btn-lg",
                cancelButtonText: "'.tr('Annulla').'",
            });
        </script>';

if (Auth::check()) {
    if (setting('Abilita esportazione Excel e PDF')) {
        echo '
        <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.9/pdfmake.min.js"></script>
        <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.9/vfs_fonts.min.js"></script>';
    }

    if (setting('Attiva scorciatoie da tastiera')) {
        echo '<script type="text/javascript" charset="utf-8" src="'.App::getPaths()['js'].'/hotkeys-js/hotkeys.min.js?v='.$version.'"></script>';
        echo '
        <script>

        hotkeys("f1,f2,f3,f4", function(event, handler) {
            switch (handler.key) {
                case "f1":
                    event.preventDefault();
                    $("button[data-toggle]").first().trigger("click");
                  break;
                case "f2":
                    event.preventDefault();
                    $("#save").first().trigger("click");
                  break;
                case "f3":
                    event.preventDefault();
                    window.open($("#print-button_p").first().attr("href"));
                  break;
                case "f4":
                    event.preventDefault();
                    $("#email-button_p").first().trigger("click");
                  break;
                default: alert(event);
              }
        });

        </script>';
    }
}

// Set the group theme
if (isset($user)) {
    if ($user->getThemeAttribute()) {
        $theme = $user->getThemeAttribute();
    }
}

if ($theme == 'default') {
    $theme = 'sidebar-dark-secondary';
} else {
    $theme = 'bg-'.$theme;
}

$settings_collapse = session_get('settings.sidebar-collapse') ? 1 : 0;
$hide_sidebar = Auth::check() && (setting('Nascondere la barra sinistra di default') || $settings_collapse);
echo '
    </head>

	<body class="sidebar-mini layout-fixed '.(!empty($hide_sidebar) ? ' sidebar-collapse' : '').(!Auth::check() ? ' hold-transition login-page' : '').'">
		<div class="'.(!Auth::check() ? '' : 'wrapper').'">';

if (Auth::check()) {
    $calendar_color_label = ($_SESSION['period_start'] != date('Y').'-01-01' || $_SESSION['period_end'] != date('Y').'-12-31') ? 'danger' : 'secondary';

    echo '
            <!-- Loader principale -->
			<!-- Preloader -->
            <div id="main_loading" class="flex-column justify-content-center align-items-center">
                <img class="animation__shake" src="'.$rootdir.'/assets/dist/img/logo.png" alt="OSM" height="60" width="60">
            </div>

            <!-- Loader secondario -->
            <div id="mini-loader" style="display:none;">
                <div></div>
            </div>

			<!-- Loader senza overlay -->
			<div id="tiny-loader" style="display:none;"></div>

            <!-- Navbar -->
            <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fa fa-bars"></i></a>
                    </li>

                    <li class="nav-item">
                        <a href="#" id="daterange" class="nav-link" role="button">
                            <i class="fa fa-calendar"></i> <i class="fa fa-caret-down"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-sm-inline-block">
                        <a class="nav-link text-'.$calendar_color_label.'">
                            '.Translator::dateToLocale($_SESSION['period_start']).' - '.Translator::dateToLocale($_SESSION['period_end']).'
                        </a>
                    </li>
                </ul>
            

                <!-- Navbar Right Menu -->
                <ul class="navbar-nav ml-auto">';
    // Visualizzo gli hooks solo se non sono stati disabilitati
    if (!$config['disable_hooks']) {
        echo '
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-bell-o"></i>
                            <span id="hooks-badge" class="badge badge-warning">
                                <span id="hooks-loading"><i class="fa fa-spinner fa-spin"></i></span>
                                <span id="hooks-notified"></span>
                                <span id="hooks-counter" class="d-none">0</span>
                                <span id="hooks-number" class="d-none">0</span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                            <a href="#" class="btn dropdown-item">
                                <span class="small" id="hooks-header"></span>
                            </a>
                            <div id="hooks"></div>
                        </div>
                    </div>';
    }

    echo '
                    <li class="nav-item">
                        <a href="#" onclick="window.print()" class="nav-link" title="'.tr('Stampa').'">
                            <i class="fa fa-print nav-icon"></i>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="'.base_path().'/log.php" class="nav-link" title="'.tr('Log accessi').'">
                            <i class="fa fa-book nav-icon"></i>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="'.base_path().'/shortcuts.php" class="nav-link" title="'.tr('Scorciatoie').'">
                            <i class="fa fa-keyboard-o nav-icon"></i>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="'.base_path().'/info.php" class="nav-link" title="'.tr('Informazioni').'">
                            <i class="fa fa-info nav-icon"></i>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="'.base_path().'/index.php?op=logout" onclick="sessionStorage.clear()" class="nav-link bg-danger" title="'.tr('Esci').'">
                            <i class="fa fa-power-off nav-icon"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.navbar -->

            <!-- Main Sidebar Container -->
            <aside class="main-sidebar '.$theme.' elevation-4">
                <a href="'.tr('https://www.openstamanager.com').'" class="brand-link" title="'.tr("Il gestionale open source per l'assistenza tecnica e la fatturazione elettronica").'" target="_blank">
                    <img src="'.$rootdir.'/assets/dist/img/logo_completo.png" class="brand-image" alt="'.tr("Il gestionale open source per l'assistenza tecnica e la fatturazione elettronica").'">
                    <span class="brand-text font-weight-light">&nbsp;</span>
                   
                </a>

                <!-- Sidebar -->
                <div class="sidebar">

                <!-- Sidebar user panel (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">';

    $user_photo = $user->photo ?: $rootdir.'/assets/dist/img/user.png';

    echo '
                        <img src="'.$user_photo.'" class="img-circle elevation-2" alt="'.$user['username'].'" />
                    </div>

                    <div class="info">
                    <a href="'.base_path().'/modules/utenti/info.php" class="d-block">
                            '.$user['username'].'
                        </a>
                    </div>
                </div>

                        
                <!-- SidebarSearch Form -->
                <div class="form-inline">
                    <div class="input-group" data-widget="sidebar-search">
                        <input class="form-control form-control-sidebar" id="supersearch" type="search" placeholder="'.tr('Cerca').'" aria-label="'.tr('Cerca').'">
                        <div class="input-group-append">
                            <button class="btn btn-sidebar">
                                <i class="fa fa-search fa-fw"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">';
    echo Modules::getMainMenu();
    echo '
                    </ul>
                </nav>
                <!-- / Sidebar Menu -->
            </div>
        </aside>';

    $in_editor = string_contains($_SERVER['SCRIPT_FILENAME'], 'editor.php');
    $in_controller = string_contains($_SERVER['SCRIPT_FILENAME'], 'controller.php');
    if ($in_editor || $in_controller) {
        // Menu laterale per la visualizzazione dei plugin
        echo '
        <div class="control-sidebar-button"><i class="fa fa-chevron-left"></i></div>
        <aside class="control-sidebar control-sidebar-light">
            <h4><i class="fa fa-plug"></i> '.tr('Plugin').'</h4>
            <ul class="nav nav-tabs nav-pills nav-stacked">
                <li data-toggle="control-sidebar" class="active btn-default nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab_0">
                        '.$structure->getTranslation('title').'
                    </a>
                </li>';

        // Tab dei plugin
        $plugins = $dbo->fetchArray('SELECT `zz_plugins`.`id`, `title`, `options`, `options2` FROM `zz_plugins` LEFT JOIN `zz_plugins_lang` ON (`zz_plugins`.`id` = `zz_plugins_lang`.`id_record` AND `zz_plugins_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `idmodule_to`='.prepare($id_module)." AND `position`='".($in_editor ? 'tab' : 'tab_main')."' AND `enabled` = 1 ORDER BY `zz_plugins`.`order` DESC");
        foreach ($plugins as $plugin) {
            // Badge count per record plugin
            $count = 0;
            $opt = '';
            if (!empty($plugin['options2'])) {
                $opt = json_decode((string) $plugin['options2'], true);
            } elseif (!empty($plugin['options'])) {
                $opt = json_decode((string) $plugin['options'], true);
            }

            if (!empty($opt)) {
                $q = str_replace('|id_parent|', $id_record ?: $id_parent, $opt['main_query'][0]['query']);
                $count = $dbo->fetchNum($q);
            }

            echo '
                        <li data-toggle="control-sidebar" class="btn-default nav-item" >
                            <a class="nav-link" data-toggle="tab" href="#tab_'.$plugin['id'].'" id="link-tab_'.$plugin['id'].'">
                                '.$plugin['title'].'
                                <span class="right badge badge-danger">'.($count > 0 ? $count : '').'</span>
                            </a>
                        </li>';
        }

        // Tab per le note interne
        if ($in_editor) {
            if ($structure->permission != '-' && $structure->use_notes) {
                $notes = $structure->recordNotes($id_record);

                echo '
                            <li data-toggle="control-sidebar" class="btn-default nav-item">
                                <a class="bg-info nav-link" data-toggle="tab" href="#tab_note" id="link-tab_note">
                                    '.tr('Note interne').'
                                    <span class="badge pull-right">'.($notes->count() ?: '').'</span>
                                </a>
                            </li>';
            }

            // Tab per le checklist
            if ($structure->permission != '-' && $structure->use_checklists) {
                $checklists_unchecked = $structure->recordChecks($id_record)->where('checked_at', null)->where('is_titolo', 0);
                $checklists_total = $structure->recordChecks($id_record)->where('is_titolo', 0);

                echo '
                    <li data-toggle="control-sidebar" class="btn-default nav-item">
                        <a class="bg-info nav-link" data-toggle="tab" href="#tab_checks" id="link-tab_checks">
                            '.tr('Checklist')
                            .($checklists_total ? ' <span class="badge pull-right">'.$checklists_unchecked->count().tr(' / ').$checklists_total->count().'</span>' : '')
                            .'
                        </a>
                    </li>';
            }

            // Tab per le informazioni sulle operazioni
            if (Auth::admin()) {
                echo '
                            <li data-toggle="control-sidebar" class="btn-default nav-item">
                                <a class="bg-info nav-link" data-toggle="tab" href="#tab_info" id="link-tab_info">
                                    '.tr('Info').'
                                </a>
                            </li>';
            }
        }
        echo '
            </ul>
        </aside>

        <div class="control-sidebar-bg"></div>';
    }

    echo '
    <!-- Main content -->
    <div class="content-wrapper">
        <section class="content">';

    if (string_contains($_SERVER['SCRIPT_FILENAME'], 'editor.php')) {
        $location = 'editor_right';
    } elseif (string_contains($_SERVER['SCRIPT_FILENAME'], 'controller.php')) {
        $location = 'controller_right';
    }

    // Eventuale messaggio personalizzato per l'installazione corrente
    $extra_file = App::filepath('include/custom/extra', 'extra.php');
    if ($extra_file) {
        include_once $extra_file;
    }
} else {
    // Eventuale messaggio personalizzato per l'installazione corrente
    $extra_file = App::filepath('include/custom/extra', 'login.php');
    if ($extra_file) {
        include_once $extra_file;
    }

    if (!empty($messages['warning']) || !empty($messages['error'])) {
        echo '
            <div class="card card-warning card-center card-center-large">
                <div class="card-header with-border text-center">
                    <h3 class="card-title">'.tr('Informazioni').'</h3>
                </div>

                <div class="card-body">';
    }
}

// Infomazioni
if (!empty($messages['info'])) {
    foreach ($messages['info'] as $value) {
        echo ' 
            <script>
                $(document).ready( function(){
                    window.parent.toastr.success("'.$value.'", toastr.options);
                });
            </script>';
    }
}

// Errori
if (!empty($messages['error'])) {
    foreach ($messages['error'] as $value) {
        echo '
							<div class="alert alert-danger push">
                                <h4><i class="fa fa fa-ban"></i> '.tr('Errore').'</h4>
                                '.$value.'
                            </div>';
    }
}

// Avvisi
if (!empty($messages['warning'])) {
    foreach ($messages['warning'] as $value) {
        echo '
							<div class="alert alert-warning push">
                                <h4><i class="fa fa-warning"></i> '.tr('Attenzione').'</h4>
                                '.$value.'
                            </div>';
    }
}

if (!Auth::check() && (!empty($messages['info']) || !empty($messages['warning']) || !empty($messages['error']))) {
    echo '
                </div>
            </div>';
}

// Messaggio informativo per l'esaurimento dello spazio totale disponibile nel server
$free_space = disk_free_space('.');
$space_limit = 1; // GB
if ($free_space < ($space_limit * (1024 ** 3))) {
    echo '
    <div class="callout callout-warning">
        <h4>
            <i class="fa fa-warning"></i> '.tr('Spazio in esaurimento').'
        </h4>
         <p>'.tr('Lo spazio a disposizione del gestionale è in esaurimento: sono al momento disponibili _TOT_', [
        '_TOT_' => FileSystem::formatBytes($free_space),
    ]).'.</p>
         <p>'.tr('Questo può risultare un serio problema per la continuità di funzionamento del software, poiché le operazioni più espansive che richiedono spazio di archiviazione possono causare malfunzionamenti imprevisti').'. '.tr('Ad esempio, le attività di backup, caricamento di allegati o anche l\'utilizzo normale del gestionale potrebbero rendere i dati inaffidabili, provocando pertanto una perdita delle informazioni salvate').'.</p>
        <p>'.tr('Contatta gli amministratori di sistema per risolvere al più presto il problema').'.</p>
    </div>';
}
