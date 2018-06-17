<?php

include_once __DIR__.'/../core.php';

$valid_config = isset($db_host) && isset($db_name) && isset($db_username) && isset($db_password);

// Gestione del file di configurazione
if (file_exists('config.inc.php') && $valid_config && $dbo->isConnected()) {
    return;
}

$pageTitle = tr('Configurazione');

if (file_exists($docroot.'/include/custom/top.php')) {
    include_once $docroot.'/include/custom/top.php';
} else {
    include_once $docroot.'/include/top.php';
}

// Controllo sull'esistenza di nuovi parametri di configurazione
if (post('db_host') !== null) {
    $db_host = $_POST['db_host']; // Fix per evitare la conversione in numero
    $db_name = post('db_name');
    $db_username = post('db_username');
    $db_password = post('db_password');

    $valid_config = isset($db_host) && isset($db_name) && isset($db_username) && isset($db_password);

    // Generazione di una nuova connessione al database
    $dbo = Database::getConnection(true, [
        'db_host' => $db_host,
        'db_name' => $db_name,
        'db_username' => $db_username,
        'db_password' => $db_password,
    ]);

    // Test della configurazione
    if (post('test') !== null) {
        ob_end_clean();

        if ($dbo->isConnected()) {
            $requirements = [
                'SELECT',
                'INSERT',
                'UPDATE',
                'CREATE',
                'ALTER',
                'DROP',
            ];

            $db_host = str_replace('_', '\_', $db_name);
            $db_name = str_replace('_', '\_', $db_name);
            $db_username = str_replace('_', '\_', $db_name);

            $results = $dbo->fetchArray('SHOW GRANTS FOR CURRENT_USER');
            foreach ($results as $result) {
                $privileges = current($result);

                if (
                    str_contains($privileges, ' ON `'.$db_name.'`.*') ||
                    str_contains($privileges, ' ON *.*')
                ) {
                    $pieces = explode(', ', explode(' ON ', str_replace('GRANT ', '', $privileges))[0]);

                    // Permessi generici sul database
                    if (in_array('ALL', $pieces) || in_array('ALL PRIVILEGES', $pieces)) {
                        $requirements = [];
                        break;
                    }

                    // Permessi specifici sul database
                    foreach ($requirements as $key => $value) {
                        if (in_array($value, $pieces)) {
                            unset($requirements[$key]);
                        }
                    }
                }
            }

            // Permessi insufficienti
            if (!empty($requirements)) {
                $state = 1;
            }

            // Permessi completi
            else {
                $state = 2;
            }
        }

        // Connessione fallita
        else {
            $state = 0;
        }

        echo $state;
        exit();
    }

    // Salvataggio dei valori da salvare successivamente
    $_SESSION['osm_password'] = post('osm_password');
    $_SESSION['osm_email'] = post('osm_email');

    // Creazione della configurazione
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
				<h3 class="box-title">'.tr('Permessi di scrittura mancanti').'</h3>
			</div>
			<div class="box-body">
				<p>'.tr('Sembra che non ci siano i permessi di scrittura sul file _FILE_', [
                    '_FILE_' => '<b>config.inc.php</b>',
                ]).'</p>
				<form action="'.$rootdir.'/index.php?action=updateconfig&firstuse=true" method="post">
					<div class="hide">
						<input type="hidden" name="db_name" value="'.$db_name.'">
						<input type="hidden" name="db_password" value="'.$db_password.'">
						<input type="hidden" name="db_username" value="'.$db_username.'">;
						<input type="hidden" name="db_host" value="'.$db_host.'">
					</div>
					<a class="btn btn-warning" href="'.$rootdir.'/index.php"><i class="fa fa-arrow-left"></i> '.tr('Torna indietro').'</a>
					<button class="btn btn-info"><i class="fa fa-repeat"></i> '.tr('Riprova').'</button>
				</form>
				<hr>
				<div class="box box-default collapsed-box">
					<div class="box-header with-border">
						<h4 class="box-title"><a class="clickable" data-widget="collapse">'.tr('Creazione manuale').'...</a></h4>
						<div class="box-tools pull-right">
							<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
						</div>
					</div>
					<div class="box-body">
						<p>'.tr('Inserire il seguente testo nel file _FILE_', [
                            '_FILE_' => '<b>config.inc.php</b>',
                        ]).'</p>
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
            <h3 class="box-title">'.tr('Impossibile connettersi al database').'</h3>
        </div>
        <div class="box-body">
            <p>'.tr("Si è verificato un'errore durante la connessione al database").'.</p>
            <p>'.tr('Controllare di aver inserito correttamente i dati di accesso, e che il database atto ad ospitare i dati del gestionale sia esistente').'.</p>
            <a class="btn btn-info" href="'.$rootdir.'/index.php"><i class="fa fa-repeat"></i> '.tr('Riprova').'</a>
            </div>
    </div>';
}

$img = App::getPaths()['img'];

// Visualizzazione dell'interfaccia di impostazione iniziale, nel caso il file di configurazione sia mancante oppure i paramentri non siano sufficienti
if (empty($creation) && (!file_exists('config.inc.php') || !$valid_config)) {
    if (file_exists('config.inc.php')) {
        echo '
		<div class="box box-center box-danger box-solid text-center">
			<div class="box-header with-border">
				<h3 class="box-title">'.tr('Parametri non sufficienti!').'</h3>
			</div>
			<div class="box-body">
				<p>'.tr("L'avvio del software è fallito a causa dell'assenza di alcuni paramentri nella configurazione di base").'.</p>
				<p>'.tr("Si prega di controllare che il file _FILE_ contenga tutti i dati inseriti durante la configurazione iniziale (con l'eccezione di password e indirizzo email amministrativi)", [
                    '_FILE_' => '<b>config.inc.php</b>',
                ]).'.</p>
				<p>'.tr("Nel caso il problema persista, rivolgersi all'assistenza ufficiale").'.</p>
				<a class="btn btn-info" href="'.$rootdir.'/index.php"><i class="fa fa-repeat"></i> '.tr('Riprova').'</a>
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
                    next: "'.tr('Successivo').'",
                    previous: "'.tr('Precedente').'",
                }
            });

            $("#smartwizard").on("leaveStep", function(e, anchorObject, stepNumber, stepDirection) {
                result = true;
                if(stepDirection == "forward" && $("#step-" + (stepNumber + 1) + " form").length){
                    result = $("#step-" + (stepNumber + 1) + " form").parsley().validate();
                }

                if(!result){
                    swal("'.tr('Impossibile procedere').'", "'.tr('Prima di proseguire devi completare i campi obbligatori!').'", "error");
                }

                $("html, body").animate({ scrollTop: $("#steps").offset().top }, 500);

                return result;
            });
            
            $("#install").on("click", function(){

                if($(this).closest("form").parsley().validate()){
                    prev_html = $("#install").html();
                    $("#install").html("<i class=\'fa fa-spinner fa-pulse  fa-fw\'></i> '.tr("Attendere").'...");
                    $("#install").prop(\'disabled\', true);
                    $("#test").prop(\'disabled\', true);
                    
                    $("#config_form").submit();
                }

            });

            $("#test").on("click", function(){
                if($(this).closest("form").parsley().validate()){
					prev_html = $("#test").html();
					$("#test").html("<i class=\'fa fa-spinner fa-pulse  fa-fw\'></i> '.tr("Attendere").'...");
                    $("#test").prop(\'disabled\', true);
                    $("#install").prop(\'disabled\', true);
                    $(this).closest("form").ajaxSubmit({
                        url: "'.$rootdir.'/index.php",
                        data: {
                            test: 1,
                        },
                        type: "post",	 
                        success: function(data){
                            data = parseFloat(data.trim());
							
							$("#test").html(prev_html);
                            $("#test").prop(\'disabled\', false);
                            $("#install").prop(\'disabled\', false);
						
                            if(data == 0){
                                swal("'.tr('Errore della configurazione').'", "'.tr('La configurazione non è corretta').'.", "error");
                            } else if(data == 1){
                                swal("'.tr('Permessi insufficienti').'", "'.tr("L'utente non possiede permessi sufficienti per il testing della connessione. Potresti rilevare problemi in fase di installazione.").'.", "error");
                            } else {
                                swal("'.tr('Configurazione corretta').'", "'.tr('Ti sei connesso con successo al database').'. '.tr("Clicca su 'Installa' per proseguire").'.", "success");
                            }
                        },
                        error: function(data) {
                            alert("'.tr('Errore').': " + data);
                        }
                    });
                }
            });
        });
        </script>';

    echo '
        <div class="box box-center-large box-warning">
            <div class="box-header with-border text-center">
                <img src="'.$img.'/logo.png" alt="'.tr('OSM Logo').'">
                <h3 class="box-title">'.tr('OpenSTAManager').'</h3>
            </div>

            <div class="box-body" id="smartwizard">';

    // REQUISITI PER IL CORRETTO FUNZIONAMENTO
    echo '
                <ul>
                    <li><a href="#step-1">
                        <h3>'.tr('Requisiti').'</h3>
                    </a></li>

                    <li><a href="#step-2">
                        <h3>'.tr('Licenza').'</h3>
                    </a></li>

                    <li><a href="#step-3">
                        <h3>'.tr('Configurazione').'</h3>
                    </a></li>
                </ul>

                <div id="steps">

                    <div id="step-1">
                        <p>'.tr('Benvenuto in <strong>OpenSTAManager</strong>!').'</p>
                        <p>'.tr("Prima di procedere alla configurazione e all'installazione del software, sono necessari alcuni accorgimenti per garantire il corretto funzionamento del gestionale").'. '.tr('Stai utilizzando la versione PHP _PHP_', [
                            '_PHP_' => phpversion(),
                        ]).'.</p>
                        <hr>';

    // Estensioni di PHP
    echo '

                        <div class="row">
                            <div class="col-md-6">
                                <p>'.tr('Le seguenti estensioni PHP devono essere abilitate dal file di configurazione _FILE_', [
                                    '_FILE_' => '<b>php.ini</b>',
                                ]).':</p>
                                <div class="list-group">';
    $extensions = [
        'zip' => tr("Necessario per l'utilizzo delle funzioni di aggiornamento automatico e backup, oltre che per eventuali moduli aggiuntivi"),
        'pdo_mysql' => tr('Necessario per la connessione al database'),
        'openssl' => tr('Utile per la generazione di chiavi complesse (facoltativo)'),
        'intl' => tr('Utile per la gestione automatizzata della conversione numerica (facoltativo)'),
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

                            <div class="col-md-6">
                                <p>'.tr('Le seguenti impostazioni PHP devono essere modificate nel file di configurazione _FILE_', [
                                    '_FILE_' => '<b>php.ini</b>',
                                ]).':</p>
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
                                        <p class="list-group-item-text">'.tr('Valore consigliato').': '.$value.'</p>
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
                            <div class="col-md-12">
                                <p>'.tr('Le seguenti cartelle devono risultare scrivibili da parte del gestionale').':</p>
                                <div class="list-group">';
    $dirs = [
        'backup' => tr('Necessario per il salvataggio dei backup'),
        'files' => tr('Necessario per il salvataggio di file inseriti dagli utenti'),
        'logs' => tr('Necessario per la gestione dei file di log'),
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
                        <p>'.tr('OpenSTAManager è tutelato dalla licenza _LICENSE_!', [
                            '_LICENSE_' => 'GPL 3.0',
                        ]).'</p>

                        <div class="row">
                            <div class="col-md-8">
                                <span class="pull-left" title='.tr('Visiona e accetta la licenza per proseguire').' >'.tr('Accetti la licenza GPLv3 di OpenSTAManager?').'*</span>
                            </div>

                            <form class="col-md-4">
                                <input type="checkbox" id="agree" name="agree" data-parsley-required="true">
                                <label for="agree">'.tr('Ho visionato e accetto').'.</label>
                            </form>
                        </div>
                        <hr>

                        <textarea class="form-control autosize" rows="15" readonly>'.file_get_contents('LICENSE').'</textarea><br>
                        <a class="pull-left" href="https://www.gnu.org/licenses/translations.en.html#GPL" target="_blank">[ '.tr('Versioni tradotte').' ]</a><br><br>
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
                        <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM" target="_blank" ><img class="pull-right" width="32" src="'.$img.'/help.png" alt="'.tr('Aiuto').'" title="'.tr('Contatta il nostro help-desk').'"/></a>

                        <p>'.tr('Non hai ancora configurato OpenSTAManager').'.</p>
                        <p><small class="help-block">'.tr('Configura correttamente il software con i seguenti parametri (modificabili successivamente dal file _FILE_)', [
                            '_FILE_' => '<b>config.inc.php</b>',
                        ]).'</small></p>';

    // Form dei parametri
    echo '
                        <form action="?action=updateconfig&firstuse=true" method="post" id="config_form">
                            <div class="row">';

    // db_host
    echo '
                                <div class="col-md-12">
                                    {[ "type": "text", "label": "'.tr('Host del database').'", "name": "db_host", "placeholder": "'.tr("Indirizzo dell'host del database").'", "value": "'.$host.'", "help": "'.tr('Esempio').': localhost", "show-help": 0, "required": 1 ]}
                                </div>
                            </div>

                            <div class="row">';

    // db_username
    echo '
                                <div class="col-md-4">
                                    {[ "type": "text", "label": "'.tr("Username dell'utente MySQL").'", "name": "db_username", "placeholder": "'.tr("Username dell'utente MySQL").'", "value": "'.$username.'", "help": "'.tr('Esempio').': root", "show-help": 0, "required": 1 ]}
                                </div>';

    // db_password
    echo '
                                <div class="col-md-4">
                                    {[ "type": "password", "label": "'.tr("Password dell'utente MySQL").'", "name": "db_password", "placeholder": "'.tr("Password dell'utente MySQL").'", "value": "'.$password.'", "help": "'.tr('Esempio').': mysql", "show-help": 0 ]}
                                </div>';

    // db_name
    echo '
                                <div class="col-md-4">
                                    {[ "type": "text", "label": "'.tr('Nome del database').'", "name": "db_name", "placeholder": "'.tr('Nome del database').'", "value": "'.$name.'", "help": "'.tr('Esempio').': openstamanager", "show-help": 0, "required": 1 ]}
                                </div>
                            </div>

                            <div class="row">';

    // Password utente admin
    echo '
                                <div class="col-md-6">
                                    {[ "type": "password", "label": "'.tr("Password dell'amministratore").'", "name": "osm_password", "placeholder": "'.tr('Scegli la password di amministratore').'", "value": "'.$osm_password.'", "help": "'.tr('Valore di default').': admin", "show-help": 1 ]}
                                </div>';

    // Email utente admin
    echo '
                                <div class="col-md-6">
                                    {[ "type": "email", "label": "'.tr("Email dell'amministratore").'", "name": "osm_email", "placeholder": "'.tr("Digita l'indirizzo email dell'amministratore").'", "value": "'.$osm_email.'" ]}
                                </div>
                            </div>';

    echo '
                            ';

    echo '
                            <!-- PULSANTI -->
                            <div class="row">
                                <div class="col-md-4">
                                    <span>*<small><small>'.tr('Campi obbligatori').'</small></small></span>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button type="button" id="test" class="btn btn-warning btn-block">
                                        <i class="fa fa-file-text"></i> '.tr('Testa il database').'
                                    </button>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button type="button" id="install" class="btn btn-success btn-block">
                                        <i class="fa fa-check"></i> '.tr('Installa').'
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
