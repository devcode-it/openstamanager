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
                    $("#progress .info").html($("#progress .info").html() + "<p><i class=\"fa fa-check\"></i> '.tr('Aggiornamento del database (_FILENAME_)', [
                        '_FILENAME_' => '<i>'.$update['filename'].'.sql</i>',
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
                    <div class="alert alert-danger">
                        <i class="fa fa-times"></i> '.tr("Errore durante l'esecuzione dell'aggiornamento alla versione _VERSION_", [
                '_VERSION_' => $update['version'],
            ]).'
                    </div>';
        }
    }
    // Update completed
    elseif (Update::isUpdateCompleted()) {
        Update::updateCleanup();

        echo '
            <p><strong>'.tr('Aggiornamento completato').'</strong> 😃</p>
            <script>
                setPercent(100);
            </script>';

        // Instructions for the first installation
        if ($_GET['firstuse'] == 'true') {
            echo '
            <p class="text-danger">'.tr('Si consiglia di rimuovere i permessi di scrittura dal file _FILE_', [
                '_FILE_' => '<b>config.inc.php</b>',
            ]).'.</p>';
        }

        echo '
            <a class="btn btn-success btn-block" href="'.base_path().'">
                <i class="fa fa-check"></i> '.tr('Continua').'
            </a>';
    }

    exit;
} elseif (Update::isUpdateAvailable()) {
    // Check if the update is in progress
    if (Update::isUpdateLocked() && filter('force') != '1') {
        $pageTitle = tr('Aggiornamento in corso!');

        include_once App::filepath('include|custom|', 'top.php');

        echo '
        <div class="card card-danger card-outline card-center-large text-center">
            <div class="card-header">
                <h3 class="card-title">'.tr('Aggiornamento in corso!').'</h3>
            </div>
            <div class="card-body">
                <p>'.tr('Il software si trova attualmente nella fase di aggiornamento, potrebbero volerci fino a 10 minuti, siete pregati di attendere sino alla sua conclusione').'.</p>
                <p>'.tr("In caso di problemi rivolgersi all'amministratore di sistema o all'assistenza del gestionale").'.</p>
                <a class="btn btn-info" href="'.base_path().'/index.php"><i class="fa fa-redo"></i> '.tr('Riprova').'</a>
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
        <div class="card card-warning card-center-large text-center">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-refresh"></i> '.(!$dbo->isInstalled() ? tr('Installazione') : tr('Aggiornamento')).'</h3>
            </div>
            <div class="card-body">';
    if (!$dbo->isInstalled()) {
        echo '
                <p><strong>'.tr("E' la prima volta che avvii OpenSTAManager e non hai ancora installato il database").'.</strong></p>';
    } else {
        echo '
                <p>'.tr("E' necessario aggiornare il database a una nuova versione").'.</p>';

        // Lista aggiornamenti da applicare
        $updates = Update::getTodoUpdates();

        if (!empty($updates)) {
            echo '
                <p>'.tr('Verranno applicati i seguenti aggiornamenti').':</p>
            <ul>';

            foreach ($updates as $update) {
                echo '<i class="fa fa-upload text-primary"></i> <span>'.$update['version'].'</span><br>';
            }

            echo '
                </ul>';
        }
    }
    echo '
                <p>'.tr("Premi il tasto _BUTTON_ per procedere con l'".(!$dbo->isInstalled() ? tr('installazione') : tr('aggiornamento')).'!', [
        '_BUTTON_' => '<b>"'.$button.'"</b>',
    ]).'</p>
                <input type="button" class="btn btn-primary" value="'.$button.'" onclick="continue_update()" id="continue_button">

                <script>
                function continue_update(){
                    swal({
                        title: "'.(!$dbo->isInstalled() ? tr('Procedere con l\'installazione?') : tr('Procedere l\'aggiornamento?')).'",
                        text: "",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn btn-lg btn-success",
                        confirmButtonText: "'.tr('Procedi').'",
                    }).then(
                    function(){
                        $("#progress").show();
                        $("#result").load("index.php?action=do_update&firstuse='.$firstuse.'");
                        $("#continue_button").remove();
                    }, function(){});
                }
                </script>

                <div id="progress">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
                            <span>0%</span>
                        </div>
                    </div>
                    <hr>
                    <div class="card card-info text-center collapsed-card">
                        <div class="card-header with-border">
                            <h3 class="card-title"><a class="clickable" data-card-widget="collapse">'.tr('Log').'</a></h3>
                            <div class="card-tools pull-right">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="card-body info text-left"></div>
                    </div>
                </div>
                <div id="result"></div>';

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
            $(".login-box").fadeOut();

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
            $("#progress .progress-bar").width(percent + "%");
            $("#progress .progress-bar span").text(percent + "%");
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
