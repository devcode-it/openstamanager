<?php

include_once __DIR__.'/../core.php';

$paths = App::getPaths();
$user = Auth::user();

$pageTitle = !empty($pageTitle) ? $pageTitle : $structure->title;

$messages = flash()->getMessages();

echo '<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>'.$pageTitle.' - '.tr('OpenSTAManager').'</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

        <meta name="robots" content="noindex,nofollow">

		<link href="'.$paths['img'].'/favicon.png" rel="icon" type="image/x-icon" />';

if (file_exists(DOCROOT.'/manifest.json')) {
    echo '
        <link rel="manifest" href="'.ROOTDIR.'/manifest.json">';
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
    echo '
		<script>
            search = []';

    $array = $_SESSION['module_'.$id_module];
    if (!empty($array)) {
        foreach ($array as $field => $value) {
            if (!empty($value) && starts_with($field, 'search_')) {
                $field_name = str_replace('search_', '', $field);

                echo '
            search.push("search_'.$field_name.'");
            search["search_'.$field_name.'"] = "'.addslashes($value).'";';
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
        'thisYear' => tr("Quest'anno"),
        'lastYear' => tr('Anno scorso'),
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
        'waiting' => tr('Impossibile procedere'),
        'waiting_msg' => tr('Prima di proseguire devi selezionare alcuni elementi!'),
        'hooksExecuting' => tr('Hooks in esecuzione'),
        'hookExecuting' => tr('Hook "_NAME_" in esecuzione'),
        'hookMultiple' => tr('Hai _NUM_ notifiche'),
        'hookSingle' => tr('Hai 1 notifica'),
        'hookNone' => tr('Nessuna notifica'),
        'singleCalendar' => tr("E' presente un solo periodo!"),
    ];
    foreach ($translations as $key => $value) {
        echo '
                '.$key.': "'.addslashes($value).'",';
    }
    echo '
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
                rootdir: "'.$rootdir.'",
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
                locale: "'.(explode('_', $lang)[0]).'",
				full_locale: "'.$lang.'",

                start_date: "'.$_SESSION['period_start'].'",
                start_date_formatted: "'.Translator::dateToLocale($_SESSION['period_start']).'",
                end_date: "'.$_SESSION['period_end'].'",
                end_date_formatted: "'.Translator::dateToLocale($_SESSION['period_end']).'",

                ckeditorToolbar: [
					["Undo","Redo","-","Cut","Copy","Paste","PasteText","PasteFromWord","-","Scayt", "-","Link","Unlink","-","Bold","Italic","Underline","Superscript","SpecialChar","HorizontalRule","-","JustifyLeft","JustifyCenter","JustifyRight","JustifyBlock","-","NumberedList","BulletedList","Outdent","Indent","Blockquote","-","Styles","Format","Image","Table", "TextColor", "BGColor" ],
				],

                order_manager_id: "'.($dbo->isInstalled() ? Modules::get('Stato dei servizi')['id'] : '').'",
                dataload_page_buffer: '.setting('Lunghezza in pagine del buffer Datatables').',
                tempo_attesa_ricerche: '.setting('Tempo di attesa ricerche in secondi').',
                restrict_summables_to_selected: '.setting('Totali delle tabelle ristretti alla selezione').',
            };
		</script>';
} else {
    echo '
        <script>
            globals = {
                rootdir: "'.$rootdir.'",

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

                locale: "'.(explode('_', $lang)[0]).'",
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
    // Barra di debug
    if (App::debug()) {
        $debugbarRenderer = $debugbar->getJavascriptRenderer();
        $debugbarRenderer->setIncludeVendors(false);
        $debugbarRenderer->setBaseUrl($paths['assets'].'/php-debugbar');

        echo $debugbarRenderer->renderHead();
    }

    if (setting('Abilita esportazione Excel e PDF')) {
        echo '
        <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
        <script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>';
    }

    if (setting('Attiva scorciatoie da tastiera')) {
        echo '<script type="text/javascript" charset="utf-8" src="'.App::getPaths()['js'].'/hotkeys-js/hotkeys.min.js"></script>';
        echo '
        <script>
        hotkeys(\'f1\', \'save\', function(event, handler){
            event.preventDefault();
            $( "button[data-toggle]" ).first().trigger( "click" );
        });
        hotkeys.setScope(\'save\');
        </script>';
    }
}

$hide_sidebar = Auth::check() && (setting('Nascondere la barra sinistra di default') or $_SESSION['settings']['sidebar-collapse']);
echo '

    </head>

	<body class="skin-'.$theme.(!empty($hide_sidebar) ? ' sidebar-collapse' : '').(!Auth::check() ? ' hold-transition login-page' : '').'">
		<div class="'.(!Auth::check() ? '' : 'wrapper').'">';

if (Auth::check()) {
    $calendar = ($_SESSION['period_start'] != date('Y').'-01-01' || $_SESSION['period_end'] != date('Y').'-12-31') ? 'red' : 'white';

    echo '
            <!-- Loader principale -->
			<div id="main_loading">
				<div>
					<i class="fa fa-cog fa-spin text-danger"></i>
				</div>
			</div>

            <!-- Loader secondario -->
            <div id="mini-loader" style="display:none;">
                <div></div>
            </div>

			<!-- Loader senza overlay -->
			<div id="tiny-loader" style="display:none;"></div>

			<header class="main-header">
				<a href="'.tr('https://www.openstamanager.com').'" class="logo" title="'.tr("Il gestionale open source per l'assistenza tecnica e la fatturazione").'" target="_blank">
					<!-- mini logo for sidebar mini 50x50 pixels -->
					<span class="logo-mini">'.tr('OSM').'</span>
					<!-- logo for regular state and mobile devices -->
					<span class="logo-lg">'.tr('OpenSTAManager').'</span>
				</a>
				<!-- Header Navbar: style can be found in header.less -->
                <nav class="navbar navbar-static-top" role="navigation">

					<!-- Sidebar toggle button-->
					<a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
						<span class="sr-only">'.tr('Mostra/nascondi menu').'</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>

                    <!-- Navbar Left Menu -->
                     <div class="navbar-left hidden-xs">
                        <ul class="nav navbar-nav hidden-xs">
                            <li><a  href="#" id="daterange" style="color:'.$calendar.';" role="button" >
                                <i class="fa fa-calendar" style="color:inherit"></i> <i class="fa fa-caret-down" style="color:inherit"></i>
                            </a></li>

                            <li><a style="color:'.$calendar.';background:inherit;cursor:default;">
                                '.Translator::dateToLocale($_SESSION['period_start']).' - '.Translator::dateToLocale($_SESSION['period_end']).'
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

                            <li><a href="#" onclick="window.print()" class="tip" title="'.tr('Stampa').'">
                                <i class="fa fa-print"></i>
                            </a></li>

                            <li><a href="'.$rootdir.'/bug.php" class="tip" title="'.tr('Segnalazione bug').'">
                                <i class="fa fa-bug"></i>
                            </a></li>

                            <li><a href="'.$rootdir.'/log.php" class="tip" title="'.tr('Log accessi').'">
                                <i class="fa fa-book"></i>
                            </a></li>

                            <li><a href="'.$rootdir.'/info.php" class="tip" title="'.tr('Informazioni').'">
                                <i class="fa fa-info"></i>
                            </a></li>

                            <li><a href="'.$rootdir.'/index.php?op=logout" onclick="sessionStorage.clear()" class="bg-red tip" title="'.tr('Esci').'">
                                <i class="fa fa-power-off"></i>
                            </a></li>
                        </ul>
                     </div>

				</nav>
			</header>

            <aside class="main-sidebar">
                <section class="sidebar">

                    <!-- Sidebar user panel -->
                    <div class="user-panel text-center info" style="height: 60px">
                        <div class="info">
                            <p><a href="'.$rootdir.'/modules/utenti/info.php">
                                '.$user['username'].'
                            </a></p>
                            <p id="datetime"></p>
                        </div>

                        <a class="image" href="'.$rootdir.'/modules/utenti/info.php">';

    $user_photo = $user->photo;
    if ($user_photo) {
        echo '
                            <img src="'.$user_photo.'" class="img-circle pull-left" alt="'.$user['username'].'" />';
    } else {
        echo '
                            <i class="fa fa-user-circle-o fa-3x pull-left" alt="'.tr('OpenSTAManager').'"></i>';
    }

    echo '
                        </a>
                    </div>

                    <!-- search form -->
                    <div class="sidebar-form">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" id="supersearch" placeholder="'.tr('Cerca').'..."/>
							<span class="input-group-btn">
								<button class="btn btn-flat" id="search-btn" name="search" type="submit" ><i class="fa fa-search"></i>
								</button>
							</span>

                        </div>
                    </div>
                    <!-- /.search form -->

                    <ul class="sidebar-menu">';
    echo Modules::getMainMenu();
    echo '
                    </ul>
                </section>
                <!-- /.sidebar -->
            </aside>

            <!-- Right side column. Contains the navbar and content of the page -->
            <aside class="content-wrapper">

                <!-- Main content -->
                <section class="content">
                    <div class="row">';

    if (str_contains($_SERVER['SCRIPT_FILENAME'], 'editor.php')) {
        $location = 'editor_right';
    } elseif (str_contains($_SERVER['SCRIPT_FILENAME'], 'controller.php')) {
        $location = 'controller_right';
    }

    echo '
                        <div class="col-md-12">';

    // Eventuale messaggio personalizzato per l'installazione corrente
    include_once App::filepath('include/custom/extra', 'extra.php');
} else {
    // Eventuale messaggio personalizzato per l'installazione corrente
    include_once App::filepath('include/custom/extra', 'login.php');

    if (!empty($messages['info']) || !empty($messages['warning']) || !empty($messages['error'])) {
        echo '
            <div class="box box-warning box-center">
                <div class="box-header with-border text-center">
                    <h3 class="box-title">'.tr('Informazioni').'</h3>
                </div>

                <div class="box-body">';
    }
}

// Infomazioni
if (!empty($messages['info'])) {
    foreach ($messages['info'] as $value) {
        echo '
							<div class="alert alert-success push">
                                <i class="fa fa-check"></i> '.$value.'
                            </div>';
    }
}

// Errori
if (!empty($messages['error'])) {
    foreach ($messages['error'] as $value) {
        echo '
							<div class="alert alert-danger push">
                                <i class="fa fa-times"></i> '.$value.'
                            </div>';
    }
}

// Avvisi
if (!empty($messages['warning'])) {
    foreach ($messages['warning'] as $value) {
        echo '
							<div class="alert alert-warning push">
                                <i class="fa fa-warning"></i>
                                '.$value.'
                            </div>';
    }
}

if (!Auth::check() && (!empty($messages['info']) || !empty($messages['warning']) || !empty($messages['error']))) {
    echo '
                </div>
            </div>';
}
