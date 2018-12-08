<?php

include_once __DIR__.'/../core.php';

$paths = App::getPaths();
$user = Auth::user();

$pageTitle = $pageTitle ?: $structure->title;

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
            search["search_'.$field_name.'"] = "'.$value.'";';
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
    ];
    foreach ($translations as $key => $value) {
        echo '
                '.$key.': \''.addslashes($value).'\',';
    }
    echo '
            };
			globals = {
                rootdir: \''.$rootdir.'\',
                js: \''.$paths['js'].'\',
                css: \''.$paths['css'].'\',
                img: \''.$paths['img'].'\',

                id_module: \''.$id_module.'\',
                id_record: \''.$id_record.'\',

                aggiornamenti_id: \''.($dbo->isInstalled() ? Modules::get('Aggiornamenti')['id'] : '').'\',

                cifre_decimali: '.setting('Cifre decimali per importi').',

                decimals: "'.formatter()->getNumberSeparators()['decimals'].'",
                thousands: "'.formatter()->getNumberSeparators()['thousands'].'",

                search: search,
                translations: translations,
                locale: \''.$lang.'\',
				full_locale: \''.$lang.'_'.strtoupper($lang).'\',

                start_date: \''.Translator::dateToLocale($_SESSION['period_start']).'\',
                end_date: \''.Translator::dateToLocale($_SESSION['period_end']).'\',

                ckeditorToolbar: [
					["Undo","Redo","-","Cut","Copy","Paste","PasteText","PasteFromWord","-","Scayt", "-","Link","Unlink","-","Bold","Italic","Underline","Superscript","SpecialChar","HorizontalRule","-","JustifyLeft","JustifyCenter","JustifyRight","JustifyBlock","-","NumberedList","BulletedList","Outdent","Indent","Blockquote","-","Styles","Format","Image","Table", "TextColor", "BGColor" ],
				],

                tempo_attesa_ricerche: '.setting('Tempo di attesa ricerche in secondi').',
            };
		</script>';
} else {
    echo '
        <script>
            globals = {
                locale: \''.$lang.'\',
                full_locale: \''.$lang.'_'.strtoupper($lang).'\',
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
}

$hide_sidebar = Auth::check() && setting('Nascondere la barra sinistra di default');
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
				<a href="https://www.openstamanager.com" class="logo" title="'.tr('Il gestionale open source per l\'assistenza tecnica e la fatturazione').'" target="_blank">
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

					<div class="input-group btn-calendar pull-left">
                        <button id="daterange" class="btn"><i class="fa fa-calendar" style="color:'.$calendar.'"></i> <i class="fa fa-caret-down" style="color:'.$calendar.';" ></i></button>
                        <span class="hidden-xs" style="vertical-align:middle; color:'.$calendar.';">
                            '.Translator::dateToLocale($_SESSION['period_start']).' - '.Translator::dateToLocale($_SESSION['period_end']).'
                        </span>
                    </div>


					<div id="right-menu" class="pull-right">
                        <button onclick="window.print()" class="btn btn-sm btn-info tip" title="'.tr('Stampa').'">
                            <i class="fa fa-print"></i>
                        </button>
						<a href="'.$rootdir.'/bug.php" class="btn btn-sm btn-github tip" title="'.tr('Segnalazione bug').'">
                            <i class="fa fa-bug"></i>
                        </a>
						<a href="'.$rootdir.'/log.php" class="btn btn-sm btn-github tip" title="'.tr('Log accessi').'">
                            <i class="fa fa-book"></i>
                        </a>
						<a href="'.$rootdir.'/info.php" class="btn btn-sm btn-github tip" title="'.tr('Informazioni').'">
                            <i class="fa fa-info"></i>
                        </a>
						<a href="'.$rootdir.'/index.php?op=logout" class="btn btn-sm btn-danger tip" title="'.tr('Esci').'">
                            <i class="fa fa-power-off"></i>
                        </a>
					</div>
				</nav>
			</header>

            <aside class="main-sidebar">
                <section class="sidebar">

                    <!-- Sidebar user panel -->
                    <div class="user-panel text-center info">
                        <div class="info">
                            <p><a href="'.$rootdir.'/modules/utenti/info.php">
                                <i class="fa fa-user"></i>
                                '.$user['username'].'
                            </a></p>
                            <p id="datetime"></p>
                        </div>

                        <div class="image">
                            <img src="'.$paths['img'].'/logo.png" class="img-circle img-responsive" alt="'.tr('OpenSTAManager').'" />
                        </div>
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
