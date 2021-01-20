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

$backups = Backup::getList();

// Controllo sul requisito ZIP
if (!extension_loaded('zip')) {
    echo "
<div class='alert alert-warning'>
    <i class='fa fa-times'></i> ".tr('Estensione zip non supportata').'.
    '.tr('Il backup verrà eseguito, ma non in formato ZIP. Sarà quindi scaricabile solo tramite FTP o con copia-incolla').'.
</div>';
}

if (!empty($backup_dir)) {
    $message = tr('Il percorso di backup è attualmente in: _PATH_', [
        '_PATH_' => '<b>'.slashes($backup_dir).'</b>',
    ]);
} else {
    $message = tr('Sembra che tu non abbia ancora specificato un percorso per il backup').'.';
}

// Controllo sui permessi di scrittura
if (!is_writable($backup_dir) || !is_readable($backup_dir)) {
    echo '
    <div class="alert alert-danger">
        <i class="fa fa-warning"></i> '.$message.'<br>'.tr('La cartella di backup indicata non è utilizzabile dal gestionale a causa di alcuni permessi di scrittura non impostati correttamente').'.
    </div>';

    return;
}

echo '<p>'.tr('Il backup è molto importante perché permette di creare una copia della propria installazione e relativi dati per poterla poi ripristinare in seguito a errori, cancellazioni accidentali o guasti hardware').'.</p>';

if (string_starts_with($backup_dir, base_dir())) {
    echo '
    <div class="alert alert-warning">
        <i class="fa fa-warning"></i> '.tr('Per motivi di sicurezza si consiglia di modificare il percorso della cartella di backup al di fuori della cartella di OSM, possibilmente in una unità esterna').'.
    </div>';
}

// Operazioni JavaScript
echo '
<script>
// Ripristino backup
function restore() {
    if ($("#blob").val()) {
        swal({
            title: "'.tr('Avviare la procedura?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì').'"
        }).then(function (result) {
            $("#restore").submit();
        })
    } else {
        swal({
            title: "'.tr('Selezionare un file!').'",
            type: "error",
        })
    }
}

// Creazione backup
function backup(){
    swal({
        title: "'.tr('Nuovo backup').'",
        text: "'.tr('Sei sicuro di voler creare un nuovo backup?').'",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-lg btn-success",
        confirmButtonText: "'.tr('Crea').'",
    }).then(
    function() {
        location.href = globals.rootdir + "/editor.php?id_module='.$id_module.'&op=backup";
    }, function() {});
}

// Caricamento
function loadSize(number, id){
    $("#" + id).html("'.tr('Calcolo in corso').'...");

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: globals.id_module,
            op: "size",
            number: number,
        },
        success: function(data) {
            $("#" + id).html(data);
        }
    });
}
</script>';

echo '
<div class="row">
    <div class="col-md-8">
        <div class="callout callout-success">
            <p>'.$message.'</p>
			<p><small>'.tr('Spazio totale occupato dai backup: _SPAZIO_', [
                '_SPAZIO_' => '<i id="total_size"></i>',
            ]).'</small></p>
			<p><small>'.tr('Numero di backup: _NUM_', [
                '_NUM_' => count($backups),
            ]).'</small></p>
            <p><small>'.tr('Puoi modificare il percorso di backup dal tuo file _FILE_', [
                '_FILE_' => '<b>config.inc.php</b>',
            ]).'</small></p>
        </div>
    </div>

    <script>
        loadSize("", "total_size");
    </script>';

$upload_max_filesize = ini_get('upload_max_filesize');
$max_execution_time = ini_get('max_execution_time');

if (setting('Permetti il ripristino di backup da file esterni')) {
    echo '
    <div class="col-md-4">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    '.tr('Ripristina backup').' <small>(upload_max_filesize: '.$upload_max_filesize.')</small> <small>(max_execution_time: '.$max_execution_time.')</small>
                </h3>
            </div>
            <div class="box-body">
                <form action="" method="post" enctype="multipart/form-data" id="restore">
                    <input type="hidden" name="op" value="restore">

			        {[ "type": "file", "name": "blob", "required": 1, "accept": ".zip" ]}

                    <button type="button" class="btn btn-primary pull-right" onclick="restore()">
                        <i class="fa fa-upload"></i> '.tr('Ripristina').'...
                    </button>
                </form>
            </div>
        </div>
    </div>';
}

echo '
</div>';

// Lettura file di backup
if (file_exists($backup_dir)) {
    $backups_zip = [];
    $backups_file = [];

    foreach ($backups as $key => $backup) {
        if (string_ends_with($backup, '.zip')) {
            $backups_zip[$key] = $backup;
        } else {
            $backups_file[$key] = $backup;
        }
    }

    if (empty($backups_zip) && empty($backups_file)) {
        echo '
<div class="alert alert-info">
    <i class="fa fa-warning"></i> '.tr('Non è ancora stato trovato alcun backup!').'
    '.tr('Se hai già inserito dei dati su OSM crealo il prima possibile...').'
</div>';
    } else {
        echo '
<div class="row">
    <div class="col-xs-12 col-md-6">
        <h3>'.tr('Backup compressi').'</h3>';

        if (!empty($backups_zip)) {
            foreach ($backups_zip as $id => $backup) {
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
                '.tr('Dimensione').': <i id="c-'.$id.'"></i>
            </small></p>

            <script>
                loadSize("'.$id.'", "c-'.$id.'");
            </script>

            <a class="btn btn-primary" href="'.base_path().'/modules/backups/actions.php?op=getfile&number='.$id.'" target="_blank"><i class="fa fa-download"></i> '.tr('Scarica').'</a>

            <div class="pull-right">
                <a class="btn btn-warning ask" data-backto="record-edit" data-method="post" data-op="restore" data-number="'.$id.'" data-msg="'.tr('Vuoi ripristinare questo backup?').'" data-button="Ripristina" data-class="btn btn-lg btn-warning">
                    <i class="fa fa-upload"></i>
                </a>

                <a class="btn btn-danger ask" title="'.tr('Elimina backup').'" data-backto="record-list" data-op="del" data-number="'.$id.'">
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
                '.tr('Dimensione').': <i id="n-'.$id.'"></i>
            </small></p>

            <script>
                loadSize("'.$id.'", "n-'.$id.'");
            </script>

            <a class="btn btn-sm btn-warning disabled" href="javascript:;"><i class="fa fa-times"></i> '.tr('Non scaricabile').'</a>

            <div class="pull-right">
                <a class="btn btn-warning ask" data-backto="record-edit" data-method="post" data-op="restore" data-number="'.$id.'" data-msg="'.tr('Vuoi ripristinare questo backup?').'" data-button="Ripristina" data-class="btn btn-lg btn-warning">
                    <i class="fa fa-upload"></i>
                </a>

                <a class="btn btn-danger ask" title="'.tr('Elimina backup').'" data-backto="record-list" data-op="del" data-number="'.$id.'">
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

// Creazione backup
if (!empty($backup_dir)) {
    echo '
<button type="button" class="btn btn-primary pull-right" onclick="backup()"><i class="fa fa-database"></i> '.tr('Crea backup').'...</button>

<div class="clearfix"></div>';
}
