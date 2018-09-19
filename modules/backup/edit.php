<?php

include_once __DIR__.'/../../core.php';

echo '<p>'.tr('Il backup è <b>molto importante</b> perché permette di creare una copia della propria installazione e relativi dati per poterla poi ripristinare in seguito a errori, cancellazioni accidentali o guasti hardware').'.</p>';

if (!extension_loaded('zip')) {
    echo "
<div class='alert alert-warning'>
    <i class='fa fa-times'></i> ".tr('Estensione ZIP non supportata!').'
    '.tr('Il backup verrà eseguito, ma non in formato ZIP. Sarà quindi scaricabile solo tramite FTP o con copia-incolla').'.
</div>';
}

if (starts_with($backup_dir, $docroot)) {
    echo '
    <div class="alert alert-warning">
        <i class="fa fa-warning"></i> '.tr('Per motivi di sicurezza si consiglia di modificare il percorso della cartella di backup al di fuori della cartella di OSM, possibilmente in una unità esterna').'.
    </div>';
}

if (!is_writable($backup_dir)) {
    echo '
    <div class="alert alert-warning">
        <i class="fa fa-warning"></i> '.tr('La cartella di backup presente nella configurazione non è utilizzabile dal gestionale').'.
        '.tr('Verificare che la cartella abbia i permessi di scrittura abilitati').'.
    </div>';
}

if (!empty($backup_dir)) {
    $message =  tr('Il percorso di backup è attualmente in').': <b>'.slashes($backup_dir).'</b>';
} else {
    $message =  tr('Sembra che tu non abbia ancora specificato un percorso per il backup').'.';
}

echo '
<div class="row">
    <div class="col-md-8">
        <div class="callout callout-success">
            <p>'.$message.'</p>
            <p><small>'.tr('Puoi modificare il percorso di backup dal tuo file _FILE_', [
                '_FILE_' => '<b>config.inc.php</b>',
            ]).'</small></p>
        </div>
    </div>';

// Ripristino backup
echo '
    <div class="col-md-4">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    '.tr('Ripristina backup').'
                </h3>
            </div>
            <div class="box-body">
                <form action="" method="post" enctype="multipart/form-data" id="restore">
                    <input type="hidden" name="op" value="restore">

                    <label><input type="file" name="blob"></label>

                    <button type="button" class="btn btn-primary pull-right" onclick="if( confirm(\''.tr('Avviare la procedura?').'\') ){ $(\'#restore\').submit(); }">
                        <i class="fa fa-upload"></i> '.tr('Ripristina').'...
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>';

// Lettura file di backup
if (file_exists($backup_dir)) {
    $backups_zip = [];
    $backups_file = [];

    $backups = Backup::getList();
    foreach ($backups as $backup) {
        if (ends_with($backup, '.zip')) {
            $backups_zip[] = $backup;
        } else {
            $backups_file[] = $backup;
        }
    }

    if (empty($backups_zip) && empty($backups_file)) {
        echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr('Non è ancora stato trovato alcun backup!').'
    '.tr('Se hai già inserito dei dati su OSM crealo il prima possibile...').'
</div>';
    } else {
        echo '
<div class="row">
    <div class="col-xs-12 col-md-6">
        <h3>'.tr('Backup compressi').'</h3>';

        if (!empty($backups_zip)) {
            foreach ($backups_zip as $backup) {
                $name = basename($backup);
                $info = Backup::readName($backup);

                $data = $info['YYYY'].'-'.$info['m'].'-'.$info['d'];
                $ora = $info['H'].':'.$info['i'].':'.$info['s'];

                echo '
        <div class="callout callout-info">
            <h4>'.tr('Backup del _DATE_ alle _TIME_', [
                '_DATE_' => Translator::dateToLocale($data),
                '_TIME_' => Translator::timeToLocale($ora),
            ]).'</h4>
            <p><small>
                '.tr('Nome del file').': '.$name.'<br>
                '.tr('Dimensione').': '.Translator::numberToLocale(filesize($backup) / 1024 / 1024).'MB
            </small></p>

            <a class="btn btn-primary" href="'.$rootdir.'/modules/backup/actions.php?op=getfile&file='.$name.'" target="_blank"><i class="fa fa-download"></i> '.tr('Scarica').'</a>

            <div class="pull-right">
                <a class="btn btn-warning ask" data-backto="record-edit" data-method="post" data-op="restore" data-zip="'.$name.'" data-msg="'.tr('Vuoi ripristinare questo backup?').'" data-button="Ripristina" data-class="btn btn-lg btn-warning">
                    <i class="fa fa-upload"></i>
                </a>

                <a class="btn btn-danger ask" title="'.tr('Elimina backup').'" data-backto="record-list" data-op="del" data-file="'.$name.'">
                    <i class="fa fa-trash"></i>
                </a>
            </div>
        </div>';
            }
        } else {
            echo '
        <div class="alert alert-warning">
            <i class="fa fa-warning"></i> '.tr('Non è stato trovato alcun backup di questa tipologia!').'
        </div>';
        }

        echo '
    </div>

    <div class="col-xs-12 col-md-6">
        <h3>'.tr('Backup non compressi').'</h3>';

        // Backup non compressi e quindi non scaricabili
        if (!empty($backups_file)) {
            foreach ($backups_file as $backup) {
                $name = basename($backup);
                $info = Backup::readName($backup);

                $data = $info['YYYY'].'-'.$info['m'].'-'.$info['d'];
                $ora = $info['H'].':'.$info['i'].':'.$info['s'];

                echo '
        <div class="callout callout-warning">
            <h4>'.tr('Backup del _DATE_ alle _TIME_', [
                '_DATE_' => Translator::dateToLocale($data),
                '_TIME_' => Translator::timeToLocale($ora),
            ]).'</h4>
            <p><small>
                '.tr('Nome del file').': '.$name.'<br>
                '.tr('Dimensione').': '.Translator::numberToLocale(filesize($backup) / 1024 / 1024).'MB
            </small></p>

            <a class="btn btn-sm btn-warning disabled" href="javascript:;"><i class="fa fa-times"></i> '.tr('Non scaricabile').'</a>

            <div class="pull-right">
                <a class="btn btn-warning ask" data-backto="record-edit" data-method="post" data-op="restore" data-folder="'.$name.'" data-msg="'.tr('Vuoi ripristinare questo backup?').'" data-button="Ripristina" data-class="btn btn-lg btn-warning">
                    <i class="fa fa-upload"></i>
                </a>

                <a class="btn btn-danger ask" title="'.tr('Elimina backup').'" data-backto="record-list" data-op="del" data-file="'.$name.'">
                    <i class="fa fa-trash"></i>
                </a>
            </div>
        </div>';
            }
        } else {
            echo '
        <div class="alert alert-warning">
            <i class="fa fa-warning"></i> '.tr('Non è stato trovato alcun backup di questa tipologia!').'
        </div>';
        }

        echo '
    </div>
</div>';
    }
} else {
    echo '
<div class="alert alert-danger">'.tr('La cartella di backup non esiste!').' '.tr('Non è possibile eseguire i backup!').'</div>';
}

if (!empty($backup_dir)) {
    // Creazione backup
    echo '
<button type="button" class="btn btn-primary pull-right" onclick="continue_backup()"><i class="fa fa-database"></i> '.tr('Crea backup').'...</button>

<div class="clearfix"></div>

<script>
    function continue_backup(){
        swal({
            title: "'.tr('Nuovo backup').'",
            text: "'.tr('Sei sicuro di voler creare un nuovo backup?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn btn-lg btn-success",
            confirmButtonText: "'.tr('Crea').'",
        }).then(
        function(){
            location.href = globals.rootdir + "/editor.php?id_module='.$id_module.'&op=backup";
        }, function(){});
    }
</script>';
}
