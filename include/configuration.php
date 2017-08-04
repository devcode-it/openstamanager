<?php

include_once __DIR__.'/../core.php';

$valid_config = isset($db_host) && isset($db_name) && isset($db_username) && isset($db_password);

// Gestione del file di configurazione
if (file_exists('config.inc.php') && $valid_config && $dbo->isConnected()) {
    return;
}

$pageTitle = _('Configurazione');

if (file_exists($docroot.'/include/custom/top.php')) {
    include_once $docroot.'/include/custom/top.php';
} else {
    include_once $docroot.'/include/top.php';
}

// Controllo sull'esistenza di nuovi parametri di configurazione
if (post('db_host') !== null) {
    $db_host = post('db_host');
    $db_name = post('db_name');
    $db_username = post('db_username');
    $db_password = post('db_password');
    $_SESSION['osm_password'] = post('osm_password');
    $_SESSION['osm_email'] = post('osm_email');

    $valid_config = isset($db_host) && isset($db_name) && isset($db_username) && isset($db_password);

    // Generazione di una nuova connessione al database
    $dbo = Database::getConnection(true);

    if ($dbo->isConnected()) {
        // Impostazioni di configurazione strettamente necessarie al funzionamento del progetto
        $backup_config = '<?php

$backup_dir = __DIR__.\'/backup/\';

$db_host = \'|host|\';
$db_username = \'|username|\';
$db_password = \'|password|\';
$db_name = \'|database|\';

';

        $new_config = (file_exists($docroot.'/config.example.php')) ? file_get_contents($docroot.'/config.example.php') : $backup_config;

        $values = [
            '|host|' => $db_host,
            '|username|' => $db_username,
            '|password|' => $db_password,
            '|database|' => $db_name,
        ];
        $new_config = str_replace(array_keys($values), $values, $new_config);

        // Controlla che la scrittura del file di configurazione sia andata a buon fine
        $creation = file_put_contents('config.inc.php', $new_config);
        if (!$creation) {
            echo '
		<div class="box box-center box-danger box-solid text-center">
			<div class="box-header with-border">
				<h3 class="box-title">'._('Permessi di scrittura mancanti').'</h3>
			</div>
			<div class="box-body">
				<p>'.str_replace('_FILE_', '<b>config.inc.php</b>', _('Sembra che non ci siano i permessi di scrittura sul file _FILE_')).'</p>
				<form action="'.$rootdir.'/index.php?action=updateconfig&firstuse=true" method="post">
					<div class="hide">
						<input type="hidden" name="db_name" value="'.$db_name.'">
						<input type="hidden" name="db_password" value="'.$db_password.'">
						<input type="hidden" name="db_username" value="'.$db_username.'">;
						<input type="hidden" name="db_host" value="'.$db_host.'">
					</div>
					<a class="btn btn-warning" href="'.$rootdir.'/index.php"><i class="fa fa-arrow-left"></i> '._('Torna indietro').'</a>
					<button class="btn btn-info"><i class="fa fa-repeat"></i> '._('Riprova').'</button>
				</form>
				<hr>
				<div class="box box-default collapsed-box">
					<div class="box-header with-border">
						<h4 class="box-title"><a class="clickable" data-widget="collapse">'._('Creazione manuale').'...</a></h4>
						<div class="box-tools pull-right">
							<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
						</div>
					</div>
					<div class="box-body">
						<p>'.str_replace('_FILE_', '<b>config.inc.php</b>', _('Inserire il seguente testo nel file _FILE_')).'</p>
						<pre class="text-left">'.htmlentities($new_config).'</pre>
					</div>
				</div>
			</div>
		</div>';
        }
        // Continua con l'esecuzione delle operazioni previste
        else {
            redirect(ROOTDIR.'/index.php');
            exit();
        }
    }
}

// Controlla che i parametri di configurazione permettano l'accesso al database
if ((file_exists('config.inc.php') || $valid_config) && !$dbo->isConnected()) {
    echo '
    <div class="box box-center box-danger box-solid text-center">
        <div class="box-header with-border">
            <h3 class="box-title">'._('Impossibile connettersi al database').'</h3>
        </div>
        <div class="box-body">
            <p>'._("Si è verificato un'errore durante la connessione al database").'.</p>
            <p>'._('Controllare di aver inserito correttamente i dati di accesso, e che il database atto ad ospitare i dati del gestionale sia esistente').'.</p>
            <a class="btn btn-info" href="'.$rootdir.'/index.php"><i class="fa fa-repeat"></i> '._('Riprova').'</a>
            </div>
    </div>';
}

// Visualizzazione dell'interfaccia di impostazione iniziale, nel caso il file di configurazione sia mancante oppure i paramentri non siano sufficienti
if (empty($creation) && (!file_exists('config.inc.php') || !$valid_config)) {
    if (file_exists('config.inc.php')) {
        echo '
		<div class="box box-center box-danger box-solid text-center">
			<div class="box-header with-border">
				<h3 class="box-title">'._('Parametri non sufficienti!').'</h3>
			</div>
			<div class="box-body">
				<p>'._("L'avvio del software è fallito a causa dell'assenza di alcuni paramentri nella configurazione di base").'.</p>
				<p>'.str_replace('_CONFIG_', '<b>config.inc.php</b>', _("Si prega di controllare che il file _CONFIG_ contenga tutti i dati inseriti durante la configurazione iniziale (con l'eccezione di password e indirizzo email amministrativi)")).'.</p>
				<p>'._("Nel caso il problema persista, rivolgersi all'assistenza ufficiale").'.</p>
				<a class="btn btn-info" href="'.$rootdir.'/index.php"><i class="fa fa-repeat"></i> '._('Riprova').'</a>
            </div>
		</div>';
    }

    // Controlli per essere sicuro che l'utente abbia letto la licenza
    echo '
        <script>
        $(document).ready(function(){
            $("#smartwizard").smartWizard({
                useURLhash: false,
                showStepURLhash: false,
                theme: "default",
                transitionEffect: "slideLeft",
                lang : {
                    next: "'._('Successivo').'",
                    previous: "'._('Precedente').'",
                }
            });

            $("#smartwizard").on("leaveStep", function(e, anchorObject, stepNumber, stepDirection) {
                result = true;
                if(stepDirection == "forward" && $("#step-" + (stepNumber + 1) + " form").length){
                    result = $("#step-" + (stepNumber + 1) + " form").parsley().validate();
                }

                if(!result){
                    swal("'._('Impossibile procedere').'", "'._('Prima di proseguire devi completare i campi obbligatori!').'", "error");
                }

                $("html, body").animate({ scrollTop: $("#steps").offset().top }, 500);

                return result;
            });
        });
        </script>';

    echo '
        <div class="box box-center-large box-warning">
            <div class="box-header with-border text-center">
                <img src="'.$img.'/logo.png" alt="'._('OSM Logo').'">
                <h3 class="box-title">'._('OpenSTAManager').'</h3>
            </div>

            <div class="box-body" id="smartwizard">';

    // REQUISITI PER IL CORRETTO FUNZIONAMENTO
    echo '
                <ul>
                    <li><a href="#step-1">
                        <h3>'._('Requisiti').'</h3>
                    </a></li>

                    <li><a href="#step-2">
                        <h3>'._('Licenza').'</h3>
                    </a></li>

                    <li><a href="#step-3">
                        <h3>'._('Configurazione').'</h3>
                    </a></li>
                </ul>

                <div id="steps">

                    <div id="step-1">
                        <p>'._('Un benvenuto da OpenSTAManager!').'</p>
                        <p>'._("Prima di procedere alla configurazione e all'installazione del software, sono necessari alcuni accorgimenti per garantire il corretto funzionamento del gestionale").'.</p>
                        <hr>';

    // Estensioni di PHP
    echo '

                        <div class="row">
                            <div class="col-xs-12 col-md-6">
                                <p>'.str_replace('_FILE_', '<i>php.ini</i>', _('Le seguenti estensioni PHP devono essere abilitate dal file di configurazione _FILE_')).':</p>
                                <div class="list-group">';
    $extensions = [
        'zip' => _("Necessario per l'utilizzo delle funzioni di aggiornamento automatico e backup, oltre che per eventuali moduli aggiuntivi"),
        'pdo_mysql' => _('Necessario per la connessione al database'),
        'openssl' => _('Utile per la generazione di chiavi complesse (non obbligatorio)'),
    ];
    foreach ($extensions as $key => $value) {
        $check = extension_loaded($key);
        echo '
                                    <div class="list-group-item">
                                        <h4 class="list-group-item-heading">
                                            '.$key;
        if ($check) {
            echo '
                                            <span class="label label-success pull-right">
                                                <i class="fa fa-check"></i>
                                            </span>';
        } else {
            echo '
                                            <span class="label label-danger pull-right">
                                                <i class="fa fa-times"></i>
                                            </span>';
        }
        echo '
                                        </h4>
                                        <p class="list-group-item-text">'.$value.'</p>
                                </div>';
    }
    echo '
                                </div>

                                <hr>
                            </div>';

    // Impostazione di valore per PHP
    echo '

                            <div class="col-xs-12 col-md-6">
                                <p>'.str_replace('_FILE_', '<i>php.ini</i>', _('Le seguenti impostazioni PHP devono essere modificate nel file di configurazione _FILE_')).':</p>
                                <div class="list-group">';
    $values = [
        'display_errors' => true,
        'upload_max_filesize' => '>16M',
        'post_max_size' => '>16M',
    ];
    foreach ($values as $key => $value) {
        $ini = str_replace(['k', 'M'], ['000', '000000'], ini_get($key));
        $real = str_replace(['k', 'M'], ['000', '000000'], $value);

        if (starts_with($real, '>')) {
            $check = $ini >= substr($real, 1);
        } elseif (starts_with($real, '<')) {
            $check = $ini <= substr($real, 1);
        } else {
            $check = ($real == $ini);
        }

        if (is_bool($value)) {
            $value = !empty($value) ? 'On' : 'Off';
        } else {
            $value = str_replace(['>', '<'], '', $value);
        }

        echo '
                                    <div class="list-group-item">
                                        <h4 class="list-group-item-heading">
                                            '.$key;
        if ($check) {
            echo '
                                            <span class="label label-success pull-right">
                                                <i class="fa fa-check"></i>
                                            </span>';
        } else {
            echo '
                                            <span class="label label-danger pull-right">
                                                <i class="fa fa-times"></i>
                                            </span>';
        }
        echo '
                                        </h4>
                                        <p class="list-group-item-text">'._('Valore consigliato').': '.$value.'</p>
                                    </div>';
    }
    echo '
                                </div>

                                <hr>
                            </div>
                        </div>';

    // Percorsi necessari
    echo '

                        <div class="row">
                            <div class="col-xs-12">
                                <p>'._('Le seguenti cartelle devono risultare scrivibili da parte del gestionale').':</p>
                                <div class="list-group">';
    $dirs = [
        'backup' => _('Necessario per il salvataggio dei backup'),
        'files' => _('Necessario per il salvataggio di file inseriti dagli utenti'),
        'logs' => _('Necessario per la gestione dei file di log'),
    ];
    foreach ($dirs as $key => $value) {
        $check = is_writable($docroot.DIRECTORY_SEPARATOR.$key);
        echo '
                                    <div class="list-group-item">
                                        <h4 class="list-group-item-heading">
                                            '.$key;
        if ($check) {
            echo '
                                            <span class="label label-success pull-right">
                                                <i class="fa fa-check"></i>
                                            </span>';
        } else {
            echo '
                                            <span class="label label-danger pull-right">
                                                <i class="fa fa-times"></i>
                                            </span>';
        }
        echo '
                                        </h4>
                                        <p class="list-group-item-text">'.$value.'</p>
                                    </div>';
    }
    echo '
                                </div>

                                <hr>
                            </div>
                        </div>
                    </div>';

    // LICENZA
    echo '
                    <div id="step-2">
                        <p>'.str_replace('_LICENSE_', 'GPL 3.0', _('OpenSTAManager è tutelato dalla licenza _LICENSE_!')).'</p>

                        <div class="row">
                            <div class="col-xs-12 col-md-8">
                                <span class="pull-left" title='._('Visiona e accetta la licenza per proseguire').' >'._('Accetti la licenza GPLv3 di OpenSTAManager?').'*</span>
                            </div>

                            <form class="col-xs-12 col-md-4">
                                <input type="checkbox" id="agree" name="agree" data-parsley-required="true">
                                <label for="agree">'._('Ho visionato e accetto').'.</label>
                            </form>
                        </div>
                        <hr>

                        <textarea class="form-control autosize" rows="15" readonly>'.file_get_contents('LICENSE').'</textarea><br>
                        <a class="pull-left" href="https://www.gnu.org/licenses/translations.en.html#GPL" target="_blank">[ '._('Versioni tradotte').' ]</a><br><br>
                    </div>';

    $host = !empty($db_host) ? $db_host : '';
    $username = !empty($db_username) ? $db_username : '';
    $password = !empty($db_password) ? $db_password : '';
    $name = !empty($db_name) ? $db_name : '';
    $osm_password = !empty($_SESSION['osm_password']) ? $_SESSION['osm_password'] : '';
    $osm_email = !empty($_SESSION['osm_email']) ? $_SESSION['osm_email'] : '';

    // PARAMETRI
    echo '
                    <div id="step-3">
                        <a href="http://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM" target="_blank" ><img class="pull-right" width="32" src="'.$img.'/help.png" alt="'._('Aiuto').'" title="'._('Contatta il nostro help-desk').'"/></a>

                        <p>'._('Non hai ancora configurato OpenSTAManager').'.</p>
                        <p><small class="help-block">'.str_replace('_CONFIG_', '<b>config.inc.php</b>', _('Configura correttamente il software con i seguenti parametri (modificabili successivamente dal file _CONFIG_)')).'</small></p>';

    // Form dei parametri
    echo '
                        <form action="?action=updateconfig&firstuse=true" method="post" id="config_form">
                            <div class="row">';

    // db_host
    echo '
                                <div class="col-xs-12">
                                    {[ "type": "text", "label": "'._('Host del database').'", "name": "db_host", "placeholder": "'._("Indirizzo dell'host del database").'", "value": "'.$host.'", "help": "'._('Esempio').': <i>localhost</i>", "show-help": 1, "required": 1 ]}
                                </div>
                            </div>

                            <div class="row">';

    // db_username
    echo '
                                <div class="col-xs-12 col-md-4">
                                    {[ "type": "text", "label": "'._("Username dell'utente MySQL").'", "name": "db_username", "placeholder": "'._("Username dell'utente MySQL").'", "value": "'.$username.'", "help": "'._('Esempio').': <i>root</i>", "show-help": 1, "required": 1 ]}
                                </div>';

    // db_password
    echo '
                                <div class="col-xs-12 col-md-4">
                                    {[ "type": "password", "label": "'._("Password dell'utente MySQL").'", "name": "db_password", "placeholder": "'._("Password dell'utente MySQL").'", "value": "'.$password.'", "help": "'._('Esempio').': <i>mysql</i>", "show-help": 1 ]}
                                </div>';

    // db_name
    echo '
                                <div class="col-xs-12 col-md-4">
                                    {[ "type": "text", "label": "'._('Nome del database').'", "name": "db_name", "placeholder": "'._('Nome del database').'", "value": "'.$name.'", "help": "'._('Esempio').': <i>openstamanager</i>", "show-help": 1, "required": 1 ]}
                                </div>
                            </div>

                            <div class="row">';

    // Password utente admin
    echo '
                                <div class="col-xs-12 col-md-6">
                                    {[ "type": "password", "label": "'._("Password dell'amministratore").'", "name": "osm_password", "placeholder": "'._('Scegli la password di amministratore').'", "value": "'.$osm_password.'", "help": "'._('Valore di default').': <i>admin</i>", "show-help": 1 ]}
                                </div>';

    // Email utente admin
    echo '
                                <div class="col-xs-12 col-md-6">
                                    {[ "type": "email", "label": "'._("Email dell'amministratore").'", "name": "osm_email", "placeholder": "'._("Digita l'indirizzo email dell'amministratore").'", "value": "'.$osm_email.'" ]}
                                </div>
                            </div>';

    echo '
                            ';

    echo '
                            <!-- PULSANTI -->
                            <div class="row">
                                <div class="col-md-4">
                                    <span>*<small><small>'._('Campi obbligatori').'</small></small></span>
                                </div>
                                <div class="col-md-8 text-right">
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fa fa-check"></i> '._('Prosegui').'
                                    </button>
                                </div>
                            </div>


                        </form>
                    </div>

                </div>
            </div>
        </div>';
}

if (file_exists($docroot.'/include/custom/bottom.php')) {
    include_once $docroot.'/include/custom/bottom.php';
} else {
    include_once $docroot.'/include/bottom.php';
}

exit();
