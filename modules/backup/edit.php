<?php

include_once __DIR__.'/../../core.php';

echo '<p>'.tr('Il backup è molto importante perchè permette di creare una copia della propria installazione con relativi dati per poterla ripristinare in seguito a errori, cancellazione di dati accidentale o guasti hardware').'.</p>

<div class="row">
	<div class="col-md-12 col-lg-6">
		<div class="callout callout-success">
            <p>';
if (!empty($backup_dir)) {
    echo tr('Il percorso di backup è attualmente in').': <b>'.slashes($backup_dir).'</b>';
} else {
    echo tr('Sembra che tu non abbia ancora specificato un percorso per il backup').'.';
}
echo '
			</p>
			<p><small>'.str_replace('_CONFIG_', '<b>config.inc.php</b>', tr('Puoi modificare il percorso di backup dal tuo file _CONFIG_')).'</small></p>';
if (strstr($backup_dir, $docroot)) {
    echo '
            <div class="alert alert-warning">
                <i class="fa fa-warning"></i> '.tr('Per motivi di sicurezza si consiglia di modificare il percorso della cartella di backup al di fuori delle cartelle di OSM, possibilmente in una unità esterna').'.
            </div>';
}
if (!is_writable($backup_dir)) {
    echo '
            <div class="alert alert-warning">
                <i class="fa fa-warning"></i> '.tr('La cartella di backup presente nella configurazione non è utilizzabile dal gestionale!').'. '.tr('Verificare che la cartella abbia i permessi di scrittura abilitati').'.
            </div>';
}
echo '
		</div>';

echo '
	</div>

	<div class="col-md-12 col-lg-6">';

// Se la cartella di backup non esiste provo a crearla
if (!file_exists($backup_dir)) {
    create_dir($backup_dir);
}

//Lettura file di backup
if (file_exists($backup_dir)) {
    $backups_zip = [];
    $backups_file = [];

    $files = glob($backup_dir.'*');
    foreach ($files as $file) {
        //I nomi dei file di backup hanno questa forma:
        // OSM backup yyyy-mm-dd HH_ii_ss.zip (oppure solo cartella senza zip)
        if (preg_match('/^OSM backup ([0-9\-]{10}) ([0-9_]{8})\.zip$/', basename($file), $m)) {
            $backups_zip[] = $file;
        } elseif (preg_match('/^OSM backup ([0-9\-]{10}) ([0-9_]{8})$/', basename($file), $m)) {
            $backups_file[] = $file;
        }
    }

    if (empty($backups_zip) && empty($backups_file)) {
        echo '
        <div class="alert alert-warning"><i class="fa fa-warning"></i> '.tr('Non è ancora stato trovato alcun backup!').' '.tr('Se hai già inserito dei dati su OSM crealo il prima possibile...')."</div>\n";
    } else {
        // Ordino i backup dal più recente al più vecchio
        arsort($backups_zip);
        arsort($backups_file);

        if (!empty($backups_zip)) {
            foreach ($backups_zip as $backup) {
                $name = basename($backup);
                preg_match('/^OSM backup ([0-9\-]{10}) ([0-9_]{8})\.zip$/', $name, $m);

                echo '
            <div class="callout callout-info">
                <h4>'.str_replace(['_DATE_', '_TIME_'], [Translator::dateToLocale($m[1]), date('H:i', strtotime(str_replace('_', ':', $m[2])))], tr('Backup del _DATE_ alle _TIME_')).'</h4>
                <p><small>
                    '.tr('Nome del file').': '.$name.'<br>
                    '.tr('Dimensione').': '.Translator::numberToLocale(filesize($backup) / 1024 / 1024).'MB
                </small></p>

                <a class="btn btn-sm btn-primary" href="'.$rootdir.'/modules/backup/actions.php?op=getfile&file='.$name.'" target="_blank"><i class="fa fa-download"></i> '.tr('Scarica').'</a>

                <a class="btn btn-danger ask pull-right" title="'.tr('Elimina backup').'" data-backto="record-list" data-op="del" data-file="'.$name.'">
                    <i class="fa fa-trash"></i>
                </a>
            </div>';
            }
        }

        // Backup non compressi e quindi non scaricabili
        if (!empty($backups_file)) {
            echo '<hr><b>'.tr('Backup non compressi')."</b>\n";

            foreach ($backups_file as $backup) {
                $name = basename($backup);
                preg_match('/^OSM backup ([0-9\-]{10}) ([0-9_]{8})$/', $name, $m);

                echo '
            <div class="callout callout-warning">
                <h4>'.str_replace(['_DATE_', '_TIME_'], [Translator::dateToLocale($m[1]), date('H:i', strtotime(str_replace('_', ':', $m[2])))], tr('Backup del _DATE_ alle _TIME_')).'</h4>
                <p><small>
                    '.tr('Nome del file').': '.$name.'<br>
                    '.tr('Dimensione').': '.Translator::numberToLocale(filesize($backup) / 1024 / 1024).'MB
                </small></p>

                <a class="btn btn-sm btn-warning disabled" href="javascript:;"><i class="fa fa-times"></i> '.tr('Non scaricabile').'</a>

                <a class="btn btn-danger ask pull-right" title="'.tr('Elimina backup').'" data-backto="record-list" data-op="del" data-file="'.$name.'">
                    <i class="fa fa-trash"></i>
                </a>
            </div>';
            }
        }
    }
} else {
    echo '
        <div class="alert alert-danger">'.tr('La cartella di backup non esiste!').' '.tr('Non è possibile eseguire i backup!').'</div>';
}
echo '
	</div>
</div>';

if (!extension_loaded('zip')) {
    echo "<div class='alert alert-warning'><i class='fa fa-times'></i> ".tr('Estensione zip non supportata!').' '.tr('Il backup verrà eseguito ma non in formato zip e quindi scaricabile solo tramite ftp o con copia-incolla').".</div>\n";
}

if ($backup_dir != '') {
    echo '
    <button type="button" class="btn btn-primary" onclick="continue_backup()"><i class="fa fa-database"></i> '.tr('Crea backup').'...</button>

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
