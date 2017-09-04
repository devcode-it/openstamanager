<?php

include_once __DIR__.'/../core.php';

$updateRate = 20;
$scriptValue = $updateRate * 5;

/*
* Aggiornamento tramite AJAX
*/
if (filter('action') == 'do_update') {
    // Aggiornamento in progresso
    if (Update::isUpdateAvailable()) {
        $update = Update::getUpdate();

        $result = Update::doUpdate($updateRate);

        if (!empty($result)) {
            // Aggiunta del messaggio generico riguardante l'aggiornamento
            echo '
        <script>
            addVersion("'.$update['version'].'");
        </script>';

            if (is_array($result)) {
                // Aggiunta del messaggio riguardante la conclusione dell'aggiornamento del database
                if (!empty($update['sql']) && $result[1] == $result[2]) {
                    echo '
        <script>
            $("#progress .info").html($("#progress .info").html() + "<p>&nbsp;&nbsp;&nbsp;&nbsp;<i class=\"fa fa-check\"></i> '.str_replace('_FILENAME_', '<i>'.$update['filename'].'.sql</i>', tr('Aggiornamento del database (_FILENAME_)')).'</p>");
        </script>';
                }

                $rate = $result[1] - $result[0];
            } elseif (!empty($update['script'])) {
                // Aggiunta del messaggio riguardante la conclusione dello script
                echo '
        <script>
            $("#progress .info").html($("#progress .info").html() + "<p>&nbsp;&nbsp;&nbsp;&nbsp;<i class=\"fa fa-check\"></i> '.str_replace('_FILENAME_', '<i>'.$update['filename'].'.php</i>', tr('Esecuzione dello script di aggiornamento (_FILENAME_)')).'</p>");
        </script>';

                $rate = $scriptValue;
            }

            // Aumento della percentuale di completameno totale
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
            // Fallimento
            echo '
            <div class="alert alert-danger">
                <i class="fa fa-times"></i> '.str_replace('_VERSION_', $update['version'], tr("Errore durante l'esecuzione dell'aggiornamento alla versione _VERSION_")).'
            </div>';
        }
    }
    // Aggiornamento completato
    elseif (Update::isUpdateCompleted()) {
        Update::updateCleanup();

        echo '
        <p><strong>'.tr('Aggiornamento completato!!!').'</strong> <i class="fa fa-smile-o"></i></p>';

        // Rimostro la finestra di login
        echo '
        <script>
            $(".login-box").fadeIn();
        </script>';

        // Istruzioni per la prima installazione
        if ($_GET['firstuse'] == 'true') {
            if (!empty($_SESSION['osm_password'])) {
                $password = $_SESSION['osm_password'];
            } else {
                $password = 'admin';
            }

            echo '
        <p>'.tr('Puoi procedere al login con i seguenti dati').':</p>
        <p>'.tr('Username').': <i>admin</i></p>
        <p>'.tr('Password').': <i> '.$password.'</i></p>
        <p class="text-danger">'.str_replace('_FILE_', '<b>config.inc.php</b>', tr("E' fortemente consigliato rimuovere i permessi di scrittura dal file _FILE_")).'.</p>';

            // Imposto la password di admin che l'utente ha selezionato all'inizio
            if (isset($_SESSION['osm_password'])) {
                $dbo->query('UPDATE `zz_users` SET `password`='.prepare(Auth::hashPassword($password))." WHERE `username`='admin'");

                unset($_SESSION['osm_password']);
            }

            if (isset($_SESSION['osm_email'])) {
                if (!empty($_SESSION['osm_email'])) {
                    $dbo->query('UPDATE `zz_users` SET `email`='.preare($_SESSION['osm_email'])." WHERE `username`='admin' ");
                }
                unset($_SESSION['osm_email']);
            }
        }
    }

    exit();
} elseif (Update::isUpdateAvailable()) {
    // Controllo se l'aggiornamento è in esecuzione
    if (Update::isUpdateLocked() && filter('force') === null) {
        $pageTitle = tr('Aggiornamento in corso!');

        if (file_exists($docroot.'/include/custom/top.php')) {
            include_once $docroot.'/include/custom/top.php';
        } else {
            include_once $docroot.'/include/top.php';
        }

        echo '
        <div class="box box-center box-danger box-solid text-center">
            <div class="box-header with-border">
                <h3 class="box-title">'.tr('Aggiornamento in corso!').'</h3>
            </div>
            <div class="box-body">
                <p>'.tr("E' attualmente in corso la procedura di aggiornamento del software, e pertanto siete pregati di attendere fino alla sua conclusione").'.</p>
                <p>'.tr("Nel caso il problema persista, rivolgersi all'amministratore o all'assistenza ufficiale").'.</p>
                <a class="btn btn-info" href="'.$rootdir.'/index.php"><i class="fa fa-repeat"></i> '.tr('Riprova').'</a>
            </div>
        </div>';

        if (file_exists($docroot.'/include/custom/bottom.php')) {
            include_once $docroot.'/include/custom/bottom.php';
        } else {
            include_once $docroot.'/include/bottom.php';
        }

        exit();
    }

    $firstuse = !$dbo->isInstalled() ? 'true' : 'false';

    $button = !$dbo->isInstalled() ? tr('Installa!') : tr('Aggiorna!');
    $pageTitle = !$dbo->isInstalled() ? tr('Installazione') : tr('Aggiornamento');

    if (file_exists($docroot.'/include/custom/top.php')) {
        include_once $docroot.'/include/custom/top.php';
    } else {
        include_once $docroot.'/include/top.php';
    }

    echo '
        <div class="box box-center-large box-warning text-center">
            <div class="box-header with-border">
                <h3 class="box-title">'.(!$dbo->isInstalled() ? tr('Installazione') : tr('Aggiornamento')).'</h3>
            </div>
            <div class="box-body">';
    if (!$dbo->isInstalled()) {
        echo '
                <p><strong>'.tr("E' la prima volta che avvii OpenSTAManager e non hai ancora installato il database").'.</strong></p>';
    } else {
        echo '
                <p>'.tr("E' necessario aggiornare il database a una nuova versione").'.</p>';
    }
    echo '
                <p>'.str_replace('_BUTTON_', '<b>"'.$button.'"</b>', tr("Premi il tasto _BUTTON_ per procedere con l'aggiornamento!")).'</p>
                <input type="button" class="btn btn-primary" value="'.$button.'" onclick="continue_update()" id="contine_button">

                <script>
                function continue_update(){
                    swal({
                        title: "'.tr('Sei sicuro?').'",
                        text: "'.tr("Continuare con l'aggiornamento?").'",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn btn-lg btn-success",
                        confirmButtonText: "'.tr('Procedi').'",
                    }).then(
                    function(){
                        $("#progress").show();
                        $("#result").load("index.php?action=do_update&firstuse='.$firstuse.'");
                        $("#contine_button").remove();
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
                    <div class="box box-info text-center collapsed-box">
                        <div class="box-header with-border">
                            <h3 class="box-title"><a class="clickable" data-widget="collapse">'.tr('Log').'</a></h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="box-body info text-left"></div>
                    </div>
                </div>
                <div id="result"></div>';

    $total = 0;
    $updates = Update::getTodos();

    foreach ($updates as $update) {
        if ($update['sql'] && (!empty($update['done']) || is_null($update['done']))) {
            $queries = readSQLFile(DOCROOT.$update['directory'].$update['filename'].'.sql', ';');
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

                        $("#progress .progress-bar").width(percent + "%");
                        $("#progress .progress-bar span").text(percent + "%");
                    }

                    function addVersion(version){
                        if(versions.indexOf(version) === -1){
                            versions.push(version);
                            current += 1;

                            $("#progress .info").html($("#progress .info").html() + "<p><strong>'.str_replace(['_DONE_', '_TODO_', '_VERSION_'], ['" + current + "', '" + count + "', '" + version + "'], tr('Aggiornamento _DONE_ di _TODO_ (_VERSION_)')).'</strong></p>");
                        }
                    }
                </script>
            </div>
        </div>';
}
