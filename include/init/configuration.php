<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

include_once __DIR__.'/../../core.php';

$valid_config = isset($db_host) && isset($db_name) && isset($db_username) && isset($db_password);

// Gestione del file di configurazione
if (file_exists('config.inc.php') && $valid_config && $dbo->isConnected()) {
    return;
}

$pageTitle = tr('Configurazione');

include_once App::filepath('include|custom|', 'top.php');

// Controllo sull'esistenza di nuovi parametri di configurazione
if (post('db_host') !== null) {
    $db_host = $_POST['db_host']; // Fix per evitare la conversione in numero
    $db_name = post('db_name');
    $db_username = post('db_username');
    $db_password = post('db_password');

    $valid_config = isset($db_host) && isset($db_name) && isset($db_username) && isset($db_password);

    // Generazione di una nuova connessione al database
    try {
        $dbo = Database::getConnection(true, [
            'db_host' => $db_host,
            'db_name' => $db_name,
            'db_username' => $db_username,
            'db_password' => $db_password,
        ]);
    } catch (Exception $e) {
    }

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
                    string_contains($privileges, ' ON `'.$db_name.'`.*') ||
                    string_contains($privileges, ' ON *.*')
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

    // Creazione della configurazione
    if ($dbo->isConnected()) {
        $new_config = file_get_contents(base_dir().'/config.example.php');

        $decimals = post('decimal_separator');
        $thousands = post('thousand_separator');
        $decimals = $decimals == 'dot' ? '.' : ',';
        $thousands = $thousands == 'dot' ? '.' : $thousands;
        $thousands = $thousands == 'comma' ? ',' : $thousands;

        $values = [
            '|host|' => $db_host,
            '|username|' => $db_username,
            '|password|' => $db_password,
            '|database|' => $db_name,
            '|lang|' => post('lang'),
            '|timestamp|' => post('timestamp_format'),
            '|date|' => post('date_format'),
            '|time|' => post('time_format'),
            '|decimals|' => $decimals,
            '|thousands|' => $thousands,
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
				<form action="'.base_path().'/index.php?action=updateconfig&firstuse=true" method="post">
					<div class="hide">
						<input type="hidden" name="db_name" value="'.$db_name.'">
						<input type="hidden" name="db_password" value="'.$db_password.'">
						<input type="hidden" name="db_username" value="'.$db_username.'">;
						<input type="hidden" name="db_host" value="'.$db_host.'">
					</div>
					<a class="btn btn-warning" href="'.base_path().'/index.php"><i class="fa fa-arrow-left"></i> '.tr('Torna indietro').'</a>
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
            // Creazione manifest.json
            $manifest = '{
    "dir" : "ltr",
    "lang" : "it-IT",
    "name" : "OpenSTAManager",
    "scope" : "'.base_path().'",
    "display" : "fullscreen",
    "start_url" : "'.base_path().'",
    "short_name" : "OSM",
    "theme_color" : "transparent",
    "description" : "OpenSTAManager",
    "orientation" : "any",
    "background_color" : "transparent",
    "generated" : "true",
    "icons" : [
        {
            "src": "assets/dist/img/logo.png",
            "type": "image/png",
            "sizes": "512x512"
        }
    ]
}';
            file_put_contents('manifest.json', $manifest);

            redirect(base_path().'/index.php');
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
            <a class="btn btn-info" href="'.base_path().'/index.php"><i class="fa fa-repeat"></i> '.tr('Riprova').'</a>
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
				<a class="btn btn-info" href="'.base_path().'/index.php"><i class="fa fa-repeat"></i> '.tr('Riprova').'</a>
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
                    $("#install").html("<i class=\'fa fa-spinner fa-pulse fa-fw\'></i> '.tr('Attendere').'...");
                    $("#install").prop("disabled", true);
                    $("#test").prop("disabled", true);

                    $("#config-form").submit();
                }

            });

            $("#test").on("click", function(){
                if($(this).closest("form").parsley().validate()){
					prev_html = $("#test").html();
					$("#test").html("<i class=\'fa fa-spinner fa-pulse fa-fw\'></i> '.tr('Attendere').'...");
                    $("#test").prop("disabled", true);
                    $("#install").prop("disabled", true);
                    $(this).closest("form").ajaxSubmit({
                        url: "'.base_path().'/index.php",
                        data: {
                            test: 1,
                        },
                        type: "post",
                        success: function(data){
                            data = parseFloat(data.trim());

							$("#test").html(prev_html);
                            $("#test").prop("disabled", false);
                            $("#install").prop("disabled", false);

                            if(data == 0){
                                swal("'.tr('Errore della configurazione').'", "'.tr('La configurazione non è corretta').'.", "error");
                            } else if(data == 1){
                                swal("'.tr('Permessi insufficienti').'", "'.tr("L'utente non possiede permessi sufficienti per il testing della connessione. Potresti rilevare problemi in fase di installazione.").'.", "error");
                            } else {
                                swal("'.tr('Configurazione corretta').'", "'.tr('Ti sei connesso con successo al database').'. '.tr('Clicca su _BTN_ per proseguire', [
        '_BTN_' => "'".tr('Installa')."'",
    ]).'.", "success");
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
                <img src="'.$img.'/logo.png" class="logo-image" alt="'.tr('OSM Logo').'">
                <h3 class="box-title">'.tr('OpenSTAManager').'</h3>
            </div>

            <div class="box-body" id="smartwizard">
                <span class="pull-right col-md-4">
                    <select class="form-control hide" id="language" required="1">';

    $languages = [
        'it_IT' => [
            'title' => tr('Italiano'),
            'flag' => 'IT',
        ],
        'en_GB' => [
            'title' => tr('Inglese'),
            'flag' => 'GB',
        ],
    ];

    $current = trans()->getCurrentLocale();
    foreach ($languages as $code => $language) {
        echo '
                        <option data-country="'.$language['flag'].'" value="'.$code.'" '.($code == $current ? 'selected' : '').'>'.$language['title'].'</option>';
    }

    echo '
                    </select>

                    <script>
                    var flag_link = "https://lipis.github.io/flag-icon-css/flags/4x3/|flag|.svg";

                    $(document).ready(function() {
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
                    });

                    function initLanguage(flag) {
                        $("#language").removeClass("hide");

                        $("#language").select2({
                            theme: "bootstrap",
                            templateResult: function(item) {
                                if (!item.id || !flag) {
                                    return item.text;
                                }

                                var element = $(item.element);
                                var img = $("<img>", {
                                    class: "img-flag",
                                    width: 26,
                                    src: flag_link.replace("|flag|", element.data("country").toLowerCase()),
                                });

                                var span = $("<span>", {
                                    text: " " + item.text
                                });
                                span.prepend(img);

                                return span;
                            }
                        });

                        $("#language").on("change", function(){
                            if ($(this).val()) {
                                var location = window.location;
                                var url = location.protocol + "//" + location.host + "" + location.pathname;

                                var parameters = getUrlVars();
                                parameters.lang = $(this).val();

                                redirect(url, parameters);
                            }
                        });
                    }
                    </script>
                </span>

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

                    <div id="step-1">';

    // Introduzione
    echo '
    <p>'.tr('Benvenuto in _NAME_!', [
        '_NAME_' => '<strong>OpenSTAManager</strong>',
    ]).'</p>
    <p>'.tr("Prima di procedere alla configurazione e all'installazione del software, sono necessari alcuni accorgimenti per garantire il corretto funzionamento del gestionale").'.</p>
    <br>

    <p>'.tr('Le estensioni e impostazioni PHP possono essere personalizzate nel file di configurazione _FILE_', [
        '_FILE_' => '<b>php.ini</b>',
    ]).'.</p>
    <hr>';

    // REQUISITI PER IL CORRETTO FUNZIONAMENTO
    include __DIR__.'/requirements.php';

    echo '
                    </div>';

    // LICENZA
    echo '
                    <div id="step-2">
                        <p>'.tr('OpenSTAManager è tutelato dalla licenza _LICENSE_!', [
                            '_LICENSE_' => 'GPL 3.0',
                        ]).'</p>

                        <div class="row">
                            <div class="col-md-8">
                                <span class="pull-left" title="'.tr('Visiona e accetta la licenza per proseguire').'">'.tr('Accetti la licenza GPLv3 di OpenSTAManager?').'*</span>
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

    // PARAMETRI
    echo '
                    <div id="step-3">
                        <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM" target="_blank" ><img class="pull-right" width="32" src="'.$img.'/help.png" alt="'.tr('Aiuto').'" title="'.tr('Contatta il nostro help-desk').'"/></a>

                        <p>'.tr('Non hai ancora configurato OpenSTAManager').'.</p>
                        <p><small class="help-block">'.tr('Configura correttamente il software con i seguenti parametri (modificabili successivamente dal file _FILE_)', [
                            '_FILE_' => '<b>config.inc.php</b>',
                        ]).'</small></p>

                        <hr>';

    // Form dei parametri
    echo '
                        <form action="?action=updateconfig&firstuse=true" method="post" id="config-form">
                            <input type="hidden" name="lang" value="'.trans()->getCurrentLocale().'">

                            <h4>'.tr('Formato date').'</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    {[ "type": "text", "label": "'.tr('Formato data lunga').'", "name": "timestamp_format", "value": "d/m/Y H:i", "required": 1 ]}
                                </div>

                                <div class="col-md-4">
                                    {[ "type": "text", "label": "'.tr('Formato data corta').'", "name": "date_format", "value": "d/m/Y", "required": 1 ]}
                                </div>

                                <div class="col-md-4">
                                    {[ "type": "text", "label": "'.tr('Formato orario').'", "name": "time_format", "value": "H:i", "required": 1 ]}
                                </div>
                            </div>

                            <small>'.tr('I formati sono impostabili attraverso lo standard previsto da PHP: _LINK_', [
                                '_LINK_' => '<a href="https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters">https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters</a>',
                            ]).'.</small>

                            <hr>';

    if (!extension_loaded('intl')) {
        $list = [
            [
                'id' => 'comma',
                'text' => tr('Virgola'),
            ],
            [
                'id' => 'dot',
                'text' => tr('Punto'),
            ],
        ];

        echo '
                            <h4>'.tr('Formato numeri').'</h4>

                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Separatore dei decimali').'", "name": "decimal_separator", "value": "comma", "values": '.json_encode($list).', "required": 1 ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "select", "label": "'.tr('Separatore delle migliaia').'", "name": "thousand_separator", "value": "dot", "values": '.json_encode($list).' ]}
                                </div>
                            </div>

                            <small>'.tr("Si consiglia l'abilitazione dell'estensione _EXT_ di PHP", [
                                '_EXT_' => 'intl',
                            ]).'.</small>

                            <hr>';
    }

    echo '

                            <h4>'.tr('Database').'</h4>
                            <div class="row">';

    // db_host
    echo '
                                <div class="col-md-12">
                                    {[ "type": "text", "label": "'.tr('Host del database').'", "name": "db_host", "placeholder": "'.tr('Host').'", "value": "'.$host.'", "help": "'.tr('Esempio').': localhost", "show-help": 0, "required": 1 ]}
                                </div>
                            </div>

                            <div class="row">';

    // db_username
    echo '
                                <div class="col-md-4">
                                    {[ "type": "text", "label": "'.tr("Username dell'utente MySQL").'", "name": "db_username", "placeholder": "'.tr('Username').'", "value": "'.$username.'", "help": "'.tr('Esempio').': root", "show-help": 0, "required": 1 ]}
                                </div>';

    // db_password
    echo '
                                <div class="col-md-4">
                                    {[ "type": "password", "label": "'.tr("Password dell'utente MySQL").'", "name": "db_password", "placeholder": "'.tr('Password').'", "value": "'.$password.'", "help": "'.tr('Esempio').': mysql", "show-help": 0 ]}
                                </div>';

    // db_name
    echo '
                                <div class="col-md-4">
                                    {[ "type": "text", "label": "'.tr('Nome del database').'", "name": "db_name", "placeholder": "'.tr('Database').'", "value": "'.$name.'", "help": "'.tr('Esempio').': openstamanager", "show-help": 0, "required": 1 ]}
                                </div>
                            </div>';

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
                                    <button type="submit" id="install" class="btn btn-success btn-block">
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

include_once App::filepath('include|custom|', 'bottom.php');

exit();
