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
                    $("#progress .info").html($("#progress .info").html() + "<p><i class=\"fa fa-database text-primary\"></i> '.tr('Database aggiornato: _FILENAME_', [
                        '_FILENAME_' => '<code>'.$update['filename'].'.sql</code>',
                    ]).'</p>");
                </script>';
                }

                $rate = $result[1] - $result[0];
            } elseif (!empty($update['script'])) {
                // Adding a message about the completion of the script
                echo '
                <script>
                    $("#progress .info").html($("#progress .info").html() + "<p><i class=\"fa fa-check\"></i> '.tr('Esecuzione dello script di aggiornamento (_FILENAME_)', [
                    '_FILENAME_' => '<i>'.$update['filename'].'.php</i>',
                ]).'</p>");
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
                    $("#result").load("index.php?action=do_update&firstuse='.$_GET['firstuse'].'");
                </script>';
        } else {
            // Failure
            echo '
                    <div class="alert alert-danger shadow-sm">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-times-circle fa-2x mr-3"></i>
                            <div>
                                <h5 class="alert-heading mb-1">'.tr("Errore durante l'aggiornamento").'</h5>
                                <p class="mb-0">'.tr("Si è verificato un problema durante l'aggiornamento alla versione _VERSION_", [
                    '_VERSION_' => '<strong>'.$update['version'].'</strong>',
                ]).'</p>
                            </div>
                        </div>
                    </div>';
        }
    }
    // Update completed
    elseif (Update::isUpdateCompleted()) {
        Update::updateCleanup();

        echo '
            <div class="alert alert-success shadow">
                <div class="d-flex align-items-center">
                    <i class="fa fa-check-circle fa-3x mr-3"></i>
                    <div>
                        <h4 class="alert-heading mb-1">'.(!$dbo->isInstalled() ? tr('Installazione completata!') : tr('Aggiornamento completato!')).'</h4>
                        <p class="mb-0">'.tr('Tutte le operazioni sono state eseguite correttamente').'.</p>
                    </div>
                </div>
            </div>
            <script>
                setPercent(100);
                // Assicurati che la barra sia verde al completamento
                $("#custom-progress-bar").removeClass("bg-warning").addClass("bg-success");
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

        echo '
            <a class="btn btn-success btn-lg btn-block shadow" href="'.base_path().'">
                <i class="fa fa-check mr-2"></i> '.tr('Configura il gestionale').'
            </a>';
    }

    exit;
} elseif (Update::isUpdateAvailable()) {
    // Check if the update is in progress
    if (Update::isUpdateLocked() && filter('force') != '1') {
        $pageTitle = tr('Aggiornamento in corso!');

        include_once App::filepath('include|custom|', 'top.php');

        echo '
        <div class="card card-danger card-outline card-center-large text-center shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-refresh fa-spin mr-2"></i>'.tr('Aggiornamento in corso').'</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    <span>'.tr('Il sistema sta eseguendo un aggiornamento del database').'</span>
                </div>
                <p>'.tr('Questo processo potrebbe richiedere fino a 10 minuti. Ti preghiamo di attendere il completamento').'.</p>
                <p>'.tr("Se il problema persiste, contatta l'amministratore di sistema").'.</p>
                <a class="btn btn-info btn-lg mt-3" href="'.base_path().'/index.php"><i class="fa fa-refresh mr-2"></i> '.tr('Aggiorna pagina').'</a>
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
    if (!$dbo->isInstalled()) {
        echo '
                <p><strong>'.tr("Benvenuto! Procediamo con l'installazione del database").'.</strong></p>';
    } else {
        echo '
                <p>'.tr("È necessario aggiornare il database alla nuova versione").'.</p>';

        // Lista aggiornamenti da applicare
        $updates = Update::getTodoUpdates();

        if (!empty($updates)) {
            echo '
                <p>'.tr('Verranno applicati i seguenti aggiornamenti').':</p>
                <div class="card card-body bg-light mb-3">
                    <div class="row">';

            foreach ($updates as $update) {
                echo '
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-upload text-primary mr-2"></i>
                                <span class="font-weight-bold">'.$update['version'].'</span>
                            </div>
                        </div>';
            }

            echo '
                    </div>
                </div>';
        }
    }
    echo '
                <p>'.tr("Clicca su _BUTTON_ per avviare l'".(!$dbo->isInstalled() ? tr('installazione') : tr('aggiornamento')), [
        '_BUTTON_' => '<b>"'.$button.'"</b>',
    ]).'</p>
                <input type="button" class="btn btn-primary btn-lg" value="'.$button.'" onclick="continue_update()" id="continue_button">

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
                        $("#progress").fadeIn(300);
                        setPercent(1);

                        $("#result").load("index.php?action=do_update&firstuse='.$firstuse.'");
                        $("#continue_button").remove();
                    }, function(){});
                }
                </script>

                <div id="progress" class="mt-4" style="display: none;">
                    <!-- Progress bar personalizzata senza classe progress -->
                    <div style="height: 30px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); background-color: #f5f5f5; overflow: hidden;">
                        <div id="custom-progress-bar" class="progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%; height: 100%; font-size: 16px; font-weight: bold; display: flex; align-items: center; justify-content: center;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div class="text-center mt-3 mb-3">
                        <span class="text-primary" id="progress-status" style="font-size: 14px;">'.tr('Preparazione aggiornamento...').'</span>
                    </div>
                    <div class="card card-info text-center collapsed-card shadow-sm">
                        <div class="card-header with-border">
                            <h3 class="card-title"><i class="fa fa-list-alt mr-2"></i><a class="clickable" data-card-widget="collapse">'.tr('Dettagli aggiornamento').'</a></h3>
                            <div class="card-tools pull-right">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="card-body info text-left"></div>
                    </div>
                </div>
                <div id="result" class="mt-3"></div>';

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
            $("#custom-progress-bar").text(percent + "%");

            // Aggiorna il testo di stato in base alla percentuale
            if (percent < 25) {
                $("#progress-status").text("'.tr('Inizializzazione aggiornamento...').'");
            } else if (percent < 50) {
                $("#progress-status").text("'.tr('Aggiornamento database in corso...').'");
            } else if (percent < 75) {
                $("#progress-status").text("'.tr('Applicazione modifiche...').'");
            } else if (percent < 100) {
                $("#progress-status").text("'.tr('Completamento aggiornamento...').'");
            } else {
                $("#progress-status").text("'.tr('Aggiornamento completato!').'");
                $("#custom-progress-bar").removeClass("bg-warning").addClass("bg-success");
            }
        }

        function addVersion(version){
            if(versions.indexOf(version) === -1){
                versions.push(version);
                current += 1;

                $("#progress .info").html($("#progress .info").html() + "<p><strong>'.tr('Aggiornamento _DONE_ di _TODO_ (_VERSION_)', [
        '_DONE_' => '" + current + "',
        '_TODO_' => '" + count + "',
        '_VERSION_' => '" + version.trim() + "',
    ]).'</strong></p>");
            }
        }
        </script>
    </div>

    </div>';
}
