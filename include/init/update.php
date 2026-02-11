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

include_once __DIR__.'/../../core.php';

$updateRate = 20;
$scriptValue = $updateRate * 5;

/*
* Aggiornamento tramite AJAX
*/
if (filter('action') == 'do_update') {
    // Aggiornamento in progresso
    if (Update::isUpdateAvailable()) {
        $update = Update::getCurrentUpdate();

        $result = Update::doUpdate($updateRate);

        if (!empty($result)) {
            // Adding a generic message regarding the update
            echo '
                <script>
                    addVersion("'.$update['name'].'");
                </script>';

            if (is_array($result)) {
                // Adding a message about the completion of the database update
                if (!empty($update['sql']) && $result[1] == $result[2]) {
                    echo '
                <script>
                    updateCurrentFile("'.tr('Database aggiornato').': '.$update['filename'].'.sql");
                </script>';
                } elseif (!empty($update['sql'])) {
                    echo '
                <script>
                    updateCurrentFile("'.tr('Aggiornamento database').': '.$update['filename'].'.sql");
                </script>';
                }

                $rate = $result[1] - $result[0];
            } elseif (!empty($update['script'])) {
                // Adding a message about the completion of the script
                echo '
                <script>
                    updateCurrentFile("'.tr('Esecuzione dello script').': '.$update['filename'].'.php");
                    // Mostra l\'icona dello script PHP accanto alla versione
                    var version_id = "'.$update['version'].'".trim().replace(/\./g, "_");
                    $("#script-icon-" + version_id).show();
                </script>';

                $rate = $scriptValue;
            }

            // Increasing the total completion percentage
            if (!empty($rate)) {
                echo '
                <script>
                    addProgress('.$rate.');
                </script>';
            }

            echo '
                <script>
                    $("#result").load("index.php?action=do_update&firstuse='.htmlspecialchars($_GET['firstuse'], ENT_QUOTES).'");
                </script>';
        } else {
            // Failure
            $error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error']['message'] : '';
            $error_query = isset($_SESSION['update_error']) ? $_SESSION['update_error']['query'] : '';

            if (!empty($error_message)) {
                echo '
                    <script>
                        showUpdateError();
                    </script>
                    <div class="card mt-4 shadow-sm">
                        <div class="card-header bg-danger text-white">
                            <h3 class="card-title"><i class="fa fa-exclamation-circle mr-2"></i>'.tr("Dettagli dell'errore").'</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light border-left border-danger">
                                <h5 class="text-danger font-weight-bold"><i class="fa fa-info-circle mr-2"></i>'.tr('Messaggio di errore').'</h5>
                                <p class="mb-0 font-weight-bold">'.$error_message.'</p>
                            </div>';

                if (!empty($error_query)) {
                    echo '
                            <div class="mt-4">
                                <div class="card card-outline card-danger">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 font-weight-bold"><i class="fa fa-database mr-2"></i>'.tr("Query SQL che ha causato l'errore").'</h5>
                                            <button type="button" class="btn btn-sm btn-danger copy-query-btn" data-query="'.htmlspecialchars((string) $error_query).'">
                                                <i class="fa fa-copy mr-1"></i>'.tr('Copia query').'
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="p-3 bg-light code-container">
                                            <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;"><code>'.htmlspecialchars((string) $error_query).'</code></pre>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    $(document).ready(function() {
                                        $(".copy-query-btn").click(function() {
                                            var $temp = $("<textarea>");
                                            $("body").append($temp);
                                            $temp.val($(this).data("query")).select();
                                            document.execCommand("copy");
                                            $temp.remove();

                                            // Show feedback
                                            var $btn = $(this);
                                            var originalText = $btn.html();
                                            $btn.html(\'<i class="fa fa-check mr-1"></i>'.tr('Copiato!').'\');
                                            $btn.addClass("btn-success").removeClass("btn-outline-light");

                                            setTimeout(function() {
                                                $btn.html(originalText);
                                                $btn.addClass("btn-outline-light").removeClass("btn-success");
                                            }, 2000);
                                        });
                                    });
                                </script>
                            </div>';
                }

                echo '

                        </div>
                    </div>';

                unset($_SESSION['update_error']);
            }
        }
    }
    // Update completed
    elseif (Update::isUpdateCompleted()) {
        Update::updateCleanup();

        echo '
            <script>
                setPercent(100);
                // Assicurati che la barra sia verde al completamento
                $("#custom-progress-bar").removeClass("bg-warning").addClass("bg-success");
                // Nascondi il testo sotto la barra di progresso
                $("#current-file").hide();
                // Mostra tutti i segni di spunta per gli aggiornamenti completati
                $("#updates-list .fa-check").show();

                $("#versions-details-container").after(\'<div class="mt-4"><a class="btn btn-success btn-lg btn-block shadow" href="'.base_path_osm().'"><i class="fa fa-check mr-2"></i> '.tr('Configura il gestionale').'</a></div>\');
            </script>';

        // Instructions for the first installation
        if ($_GET['firstuse'] == 'true') {
            echo '
            <div class="alert alert-warning mb-4">
                <i class="fa fa-exclamation-triangle mr-2"></i>
                '.tr('Per maggiore sicurezza, rimuovi i permessi di scrittura dal file _FILE_', [
                '_FILE_' => '<b>config.inc.php</b>',
            ]).'
            </div>';
        }
    }

    exit;
} elseif (Update::isUpdateAvailable()) {
    // Check if the update is in progress
    if (Update::isUpdateLocked() && filter('force') != '1') {
        $pageTitle = tr('Aggiornamento in corso!');

        include_once App::filepath('include|custom|', 'top.php');

        echo '
        <div class="card card-danger card-center-large text-center shadow">
            <div class="card-header bg-danger">
                <h3 class="card-title text-white"><i class="fa fa-times-circle mr-2"></i>'.tr('Errore durante l\'aggiornamento').'</h3>
            </div>
            <div class="card-body">

                <script>
                    $(document).ready(function() {
                        $("#progress-status").html("<i class=\"fa fa-times-circle text-danger mr-1\"></i><span class=\"text-danger\">'.tr('Errore durante l\'aggiornamento').'</span>");
                        $("#custom-progress-bar").removeClass("bg-warning").addClass("bg-danger");
                        $("#current-file").hide(); // Nasconde il messaggio di avvio aggiornamento
                    });
                </script>
                <p>'.tr('Questo processo potrebbe richiedere fino a 10 minuti. Ti preghiamo di attendere il completamento').'.</p>
                <p>'.tr("Se il problema persiste, contatta l'amministratore di sistema").'.</p>
                <a class="btn btn-info btn-lg mt-3" href="'.base_path_osm().'/index.php"><i class="fa fa-refresh mr-2"></i> '.tr('Aggiorna pagina').'</a>
            </div>
        </div>';

        include_once App::filepath('include|custom|', 'bottom.php');

        exit;
    }

    $firstuse = !$dbo->isInstalled() ? 'true' : 'false';

    $button = !$dbo->isInstalled() ? tr('Installa!') : tr('Aggiorna!');
    $pageTitle = !$dbo->isInstalled() ? tr('Installazione') : tr('Aggiornamento');

    include_once App::filepath('include|custom|', 'top.php');

    echo '
        <div class="card card-warning card-center-large text-center shadow">
            <div class="card-header bg-warning">
                <h3 class="card-title"><i class="fa fa-refresh mr-2"></i> '.(!$dbo->isInstalled() ? tr('Installazione') : tr('Aggiornamento')).'</h3>
            </div>
            <div class="card-body">';
    // Lista aggiornamenti da applicare
    $updates = Update::getTodoUpdates();

    if (!$dbo->isInstalled()) {
        echo '
                <p><strong>'.tr("Benvenuto! Procediamo con l'installazione del database").'.</strong></p>';
    } else {
        echo '
                <p>'.tr('È necessario aggiornare il database alla nuova versione').'.</p>';
    }

    // Prepara l'HTML per l'elenco degli aggiornamenti, ma non lo mostra ancora
    $updates_html = '';
    if (!empty($updates)) {
        $updates_html .= '
            <div id="updates-container" style="display: none;">
            </div>';

        echo $updates_html;
    }
    echo '
                <!-- Progress bar moved to the top -->
                <div id="progress" class="mb-4" style="display: none;">
                    <!-- Progress bar personalizzata senza classe progress -->
                    <div class="progress-container" data-percentage="0%">
                        <div id="custom-progress-bar" class="progress-bar-striped progress-bar-animated bg-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-center mt-3 mb-3">
                        <span class="text-primary" id="progress-status">'.tr('Inizializzazione aggiornamento...').'</span>
                        <div id="current-file" class="mt-1 text-muted"></div>
                    </div>
                </div>

                <div id="install-instructions">
                    <p>'.tr("Clicca su _BUTTON_ per avviare l'".(!$dbo->isInstalled() ? tr('installazione') : tr('aggiornamento')), [
        '_BUTTON_' => '<b>"'.$button.'"</b>',
    ]).'</p>
                    <input type="button" class="btn btn-primary btn-lg" value="'.$button.'" onclick="continue_update()" id="continue_button">
                </div>

                <script>
                function continue_update(){
                    swal({
                        title: "'.(!$dbo->isInstalled() ? tr('Confermi l\'installazione?') : tr('Confermi l\'aggiornamento?')).'",
                        text: "'.(!$dbo->isInstalled() ? tr('Verrà creato il database e installati i dati iniziali') : tr('Il database verrà aggiornato alla nuova versione')).'",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn btn-lg btn-success",
                        confirmButtonText: "'.tr('Conferma').'",
                    }).then(
                    function(){
                        // Nascondi le istruzioni di installazione
                        $("#install-instructions").hide();

                        // Mostra la barra di progresso
                        $("#progress").fadeIn(300);
                        setPercent(1);
                        updateCurrentFile("'.tr('Avvio aggiornamento...').'");

                        // Mostra il container degli aggiornamenti
                        $("#updates-container").fadeIn(300);

                        // Mostra il container dei dettagli delle versioni
                        $("#versions-details-container").fadeIn(300);

                        // Inizialmente nascondi i dettagli delle versioni
                        $("#updates-details").hide();

                        $("#result").load("index.php?action=do_update&firstuse='.$firstuse.'");
                        $("#continue_button").remove();
                    }, function(){});
                }
                </script>

                <div id="result" class="mt-3"></div>

                <!-- Dettaglio versioni spostato in fondo -->
                <div id="versions-details-container" class="mt-4" style="display: none;">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">'.tr('Dettaglio versioni').'</h5>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-updates">
                                    <i class="fa fa-chevron-down"></i> '.tr('Mostra/Nascondi').'
                                </button>
                            </div>
                        </div>
                        <div class="card-body bg-light" id="updates-details" style="display: none;">
                            <p>'.tr('Verranno applicati i seguenti aggiornamenti').':</p>
                            <div class="row" id="updates-list">';

    // Dividi gli aggiornamenti in 4 colonne
    $total_updates = count($updates);
    $updates_per_column = ceil($total_updates / 4);
    $column_updates = array_chunk($updates, $updates_per_column);

    // Per ogni colonna
    for ($col = 0; $col < count($column_updates); ++$col) {
        echo '
                                <div class="col-md-3">
                                    <ul class="list-unstyled mb-0">';

        // Per ogni aggiornamento nella colonna
        foreach ($column_updates[$col] as $update) {
            $version_id = str_replace('.', '_', $update['version']);
            echo '
                                        <li class="mb-2">
                                            <div class="d-flex align-items-center" id="update-item-'.$version_id.'">
                                                <i class="fa fa-upload text-primary mr-2"></i>
                                                <span class="font-weight-bold">'.$update['version'].'</span>
                                                <i class="fa fa-check text-success ml-2" style="display: none;"></i>
                                                '.($update['script'] ? '<i class="fa fa-check text-info ml-2" id="script-icon-'.$version_id.'" style="display: none;"></i>' : '').'
                                            </div>
                                        </li>';
        }

        echo '
                                    </ul>
                                </div>';
    }

    echo '
                            </div>
                        </div>
                    </div>
                    <script>
                        $(document).ready(function() {
                            $("#toggle-updates").click(function() {
                                $("#updates-details").slideToggle(300);
                                $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
                            });
                        });
                    </script>
                </div>';

    $total = 0;
    $updates = Update::getTodoUpdates();
    foreach ($updates as $update) {
        if ($update['sql'] && (!empty($update['done']) || is_null($update['done']))) {
            $queries = readSQLFile(base_dir().'/'.$update['directory'].$update['filename'].'.sql', ';');
            $total += count($queries);

            if (intval($update['done']) > 1) {
                $total -= intval($update['done']) - 2;
            }
        }

        if ($update['script']) {
            $total += $scriptValue;
        }
    }

    echo '
        <script>
        $(document).ready(function(){

            count = '.count($updates).';
            current = 0;
            versions = [];

            progress = 0;
            total = '.$total.';
        });

        function addProgress(rate){
            progress += rate;
            percent = progress / total * 100;
            percent = Math.round(percent);
            percent = percent > 100 ? 100 : percent;

            setPercent(percent);
        }

        function setPercent(percent){
            // Aggiorna la progress bar personalizzata
            $("#custom-progress-bar").css("width", percent + "%");
            $("#custom-progress-bar").attr("aria-valuenow", percent);

            $(".progress-container").attr("data-percentage", percent + "%");

            // Aggiorna il testo di stato in base alla percentuale
            if (percent < 1) {
                $("#progress-status").text("'.tr('Inizializzazione aggiornamento...').'");
            } else if (percent < 75) {
                $("#progress-status").text("'.tr('Aggiornamento in corso...').'");
            } else if (percent < 100) {
                $("#progress-status").text("'.tr('Completamento aggiornamento...').'");
            } else {
                $("#progress-status").html("<i class=\"fa fa-check-circle text-success mr-1\"></i><span class=\"text-success\">'.tr('Aggiornamento completato!').'</span>");
                $("#custom-progress-bar").removeClass("bg-warning").addClass("bg-success");
            }
        }

        function showUpdateError() {
            $("#progress-status").html("<i class=\"fa fa-times-circle text-danger mr-1\"></i><span class=\"text-danger\">'.tr('Errore durante l\'aggiornamento').'</span>");
            $("#custom-progress-bar").removeClass("bg-warning").addClass("bg-danger");
            $("#current-file").hide(); // Nasconde il messaggio di avvio aggiornamento
        }

        function updateCurrentFile(filename) {
            $("#current-file").text(filename);
        }

        function addVersion(version){
            if(versions.indexOf(version) === -1){
                versions.push(version);
                current += 1;

                // Mostra il segno di spunta accanto all\'aggiornamento completato
                var version_id = version.trim().replace(/\./g, "_");
                $("#update-item-" + version_id + " .fa-check").show();


                // Aggiorna il nome del file corrente
                updateCurrentFile("'.tr('Installazione di').' " + version.trim());
            }
        }
        </script>
    </div>

    </div>';

    if (Update::isUpdateCompleted()) {
        echo '
        <div class="mt-4">
            <a class="btn btn-success btn-lg btn-block shadow" href="'.base_path_osm().'">
                <i class="fa fa-check mr-2"></i> '.tr('Configura il gestionale').'
            </a>
        </div>';
    }
}
