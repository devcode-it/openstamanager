<?php

ob_end_clean();
ob_start();

use Modules\Aggiornamenti\Aggiornamento;

$pageTitle = '';

$body_class = 'hold-transition login-page';
include App::filepath('resources\views|custom|\layout', 'header.php');
include App::filepath('resources\views|custom|\layout', 'messages.php');

echo '
<div class="box box-center-large box-warning nav nav-tabs">
    <div class="box-header with-border">
        <h3 class="box-title">
            <a data-toggle="tab" href="#info">'.tr("Informazioni sull'aggiornamento").'</a>
        </h3>
        <a data-toggle="tab" href="#changelog" class="pull-right">'.tr('Changelog').'</a>
    </div>
    <div class="box-body tab-content">
        <div id="info" class="tab-pane fade in active">';

    if ($update->isCoreUpdate()) {
        echo '
            <p>'.tr("Il pacchetto selezionato contiene un aggiornamento dell'intero gestionale").'.</p>
            <p>'.tr("Si consiglia vivamente di effettuare un backup dell'installazione prima di procedere").'.</p>

            <button type="button" class="btn btn-primary pull-right" onclick="backup()">
                <i class="fa fa-database"></i> '.tr('Crea backup').'
            </button>

            <div class="clearfix"></div>
            <hr>';

        echo '
            <h3 class="text-center">'.tr('OpenSTAManager versione _VERSION_', [
                '_VERSION_' => Update::getFile($update->getDirectory().'/VERSION'),
            ]).'</h3>';

        include $update->getDirectory().'/include/init/requirements.php';
    } else {
        $elements = $update->componentUpdates();

        echo '
        <div class="alert alert-warning">
            <i class="fa fa-warning"></i>
            <b>'.tr('Attenzione!').'</b> '.tr('Verranno aggiornate le sole componenti del pacchetto che non sono già installate e aggiornate').'.
        </div>';

        if (!empty($elements['modules'])) {
            echo '
            <p>'.tr('Il pacchetto selezionato comprende i seguenti moduli').':</p>
            <ul class="list-group">';

            foreach ($elements['modules'] as $element) {
                echo '
                <li class="list-group-item">
                    <span class="badge">'.$element['info']['version'].'</span>';

                if ($element['is_installed']) {
                    echo '
                    <span class="badge">'.tr('Installato').'</span>';
                }

                echo '
                    '.$element['info']['name'].'
                </li>';
            }

            echo '
                </ul>';
        }

        if (!empty($elements['plugins'])) {
            echo '
            <p>'.tr('Il pacchetto selezionato comprende i seguenti plugin').':</p>
            <ul class="list-group">';

            foreach ($elements['plugins'] as $element) {
                echo '
                <li class="list-group-item">
                    <span class="badge">'.$element['info']['version'].'</span>';

                if ($element['is_installed']) {
                    echo '
                    <span class="badge">'.tr('Installato').'</span>';
                }

                echo '
                    '.$element['info']['name'].'
                </li>';
            }

            echo '
            </ul>';
        }
    }

    echo '
        </div>

        <div id="changelog" class="tab-pane fade">';

    if ($update->isCoreUpdate()) {
        $changelog = Aggiornamento::getChangelog($update->getDirectory(), Update::getVersion());
        echo $changelog;
    } else {
        $changelogs = [];

        $list = array_merge($elements['modules'], $elements['plugins']);
        foreach ($list as $element) {
            $changelog = Aggiornamento::getChangelog($element['path'], $element['version']);

            if (!empty($changelog)) {
                $changelogs[] = '
            <h4 class="text-center">'.$element['info']['name'].'<h4>
            '.$changelog;
            }
        }

        if (!empty($changelogs)) {
            echo implode('<hr>', $changelogs);
        } else {
            echo '
            <p>'.tr('Nessuna componente presenta un changelog individuabile').'.</p>';
        }
    }

    echo '
        </div>

        <hr>

        <form action="'.pathFor('module', ['module_id' => $id_module]).'" method="post" style="display:inline-block">
            <input type="hidden" name="op" value="cancel">
            <input type="hidden" name="backto" value="record-list">

            <button type="submit" class="btn btn-warning">
                <i class="fa fa-arrow-left"></i> '.tr('Annulla').'
            </button>
        </form>

        <form action="'.pathFor('module', ['module_id' => $id_module]).'" method="post" class="pull-right" style="display:inline-block">
            <input type="hidden" name="op" value="execute">
            <input type="hidden" name="backto" value="record-list">

            <button type="submit" class="btn btn-success">
                <i class="fa fa-arrow-right"></i> '.tr('Procedi').'
            </button>
        </form>
    </div>
</div>';

echo '
<script>
    function backup(){
        swal({
            title: "'.tr('Nuovo backup').'",
            text: "'.tr('Sei sicuro di voler creare un nuovo backup?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn btn-lg btn-success",
            confirmButtonText: "'.tr('Crea').'",
        }).then(function(){
            $("#main_loading").show();

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "post",
                data: {
                    id_module: '.Modules::get('Backup')->id.',
                    op: "backup",
                },
                success: function(data){
                    $("#main_loading").fadeOut();
                }
            });
        }, function(){});
    }
</script>';
include App::filepath('resources\views|custom|\layout', 'footer.php');

exit();
