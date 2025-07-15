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
function creaBackup(button){
    swal({
        title: "'.tr('Creare un nuovo backup?').'",
        text: "'.tr('Seleziona cosa escludere dal backup:').'",
        input: "select",
        inputOptions: {
            "exclude_attachments": "📎 '.tr('Allegati').'",
            "only_database": "🗃️ '.tr('Tutto tranne database').'"
        },
        inputAttributes: {
            title: "'.tr('Seleziona cosa escludere dal backup').'"
        },
        inputPlaceholder: " '.tr('Non escludere nulla').'",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-lg btn-success",
        confirmButtonText: "'.tr('Crea').'",
    }).then(function(result) {
        let restore = buttonLoading(button);
        $("#main_loading").show();
        let selectedOption = result;
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "GET",
            data: {
                id_module: globals.id_module,
                op: "backup",
                exclude: selectedOption
            },
            success: function(data) {
                $("#main_loading").fadeOut();
                buttonRestore(button, restore);

                // Ricaricamento della pagina corrente
                window.location.reload();
            },
            error: function() {
                swal("'.tr('Errore').'", "'.tr('Errore durante la creazione del backup').'", "error");
                renderMessages();

                buttonRestore(button, restore);
            }
        });
    }).catch(swal.noop);
}

// Caricamento
function loadSize(number, id){
    $("#" + id).html("'.tr('Calcolo in corso').'...");

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "GET",
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

// Header con informazioni generali
echo '
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-info-circle mr-2"></i>'.tr('Informazioni sul backup').'
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <p>'.tr('Il backup è molto importante perché permette di creare una copia della propria installazione e relativi dati per poterla poi ripristinare in seguito a errori, cancellazioni accidentali o guasti hardware').'.</p>

                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fa fa-folder"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">'.tr('Percorso backup').'</span>
                                            <span class="info-box-number">'.slashes($backup_dir).'</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fa fa-hdd-o"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">'.tr('Spazio occupato').'</span>
                                            <span class="info-box-number" id="total_size">'.tr('Calcolo in corso').'...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon"><i class="fa fa-files-o"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">'.tr('Numero backup').'</span>
                                            <span class="info-box-number">'.count($backups).'</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';

if (string_starts_with($backup_dir, base_dir())) {
    echo '
                        <div class="alert alert-warning mt-3">
                            <i class="fa fa-warning"></i> '.tr('Per motivi di sicurezza si consiglia di modificare il percorso della cartella di backup al di fuori della cartella di OSM, possibilmente in una unità esterna.').'
                            <p class="mt-2">'.tr('Puoi modificare il percorso di backup da:').' <a href="'.base_path().'/controller.php?id_module='.Models\Module::where('name', 'Impostazioni')->first()->id.'&search=Adattatore archiviazione backup#" class="btn btn-sm btn-info"><i class="fa fa-cog"></i> '.tr('Menu <b>Strumenti</b> &rarr; <b>Impostazioni</b> &rarr; sezione <b>Backup</b> &rarr; impostazione <b>Adattatore archiviazione backup</b>').'</a>
                            </p>
                        </div>';
}

echo '
                    </div>

                    <script>
                        loadSize('.count($backups).', "total_size");
                    </script>';

$upload_max_filesize = ini_get('upload_max_filesize');
$max_execution_time = ini_get('max_execution_time');

if (setting('Permetti il ripristino di backup da file esterni')) {
    echo '
                    <div class="col-md-4">
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fa fa-upload mr-2"></i>'.tr('Ripristina backup').'
                                </h3>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    <i class="fa fa-info-circle"></i> '.tr('Limiti di sistema').':
                                    <span class="badge badge-info">upload_max_filesize: '.$upload_max_filesize.'</span>
                                    <span class="badge badge-info">max_execution_time: '.$max_execution_time.'s</span>
                                </p>

                                <form action="" method="post" enctype="multipart/form-data" id="restore">
                                    <input type="hidden" name="op" value="restore">

                                    <div class="row">
                                        <div class="col-md-8">
                                            {[ "type": "file", "name": "blob", "required": 1, "accept": ".zip" ]}
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <button type="button" class="btn btn-success" onclick="restore()">
                                                <i class="fa fa-upload"></i> '.tr('Ripristina').'
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
}
echo'
                </div>
            </div>
        </div>
    </div>
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
<div class="row mt-4">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header text-center">
                <ul class="nav nav-tabs card-header-tabs justify-content-center" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="compressed-tab" data-toggle="tab" href="#compressed" role="tab" aria-controls="compressed" aria-selected="true">
                            <i class="fa fa-file-archive-o mr-2"></i>'.tr('Backup compressi').' <span class="badge badge-pill badge-info">'.count($backups_zip).'</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="uncompressed-tab" data-toggle="tab" href="#uncompressed" role="tab" aria-controls="uncompressed" aria-selected="false">
                            <i class="fa fa-file-text-o mr-2"></i>'.tr('Backup non compressi').' <span class="badge badge-pill badge-warning">'.count($backups_file).'</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="compressed" role="tabpanel" aria-labelledby="compressed-tab">
                        <div class="row">';

        if (!empty($backups_zip)) {
            foreach ($backups_zip as $id => $backup) {
                $name = basename((string) $backup);
                $info = Backup::readName($backup);

                $data = $info['YYYY'].'-'.$info['m'].'-'.$info['d'];
                $ora = $info['H'].':'.$info['i'].':'.$info['s'];
                $tipo = $info['AAAAAAA'];
                echo '
                            <div class="col-md-12 mb-3">
                                <div class="card card-outline card-info">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">
                                            <i class="fa fa-calendar-o mr-2"></i><strong>'.tr('Backup del _DATE_', [
                    '_DATE_' => Translator::dateToLocale($data),
                ]).'</strong>
                                            <span class="text-muted ml-2">'.tr('alle _TIME_', [
                    '_TIME_' => Translator::timeToLocale($ora),
                ]).'</span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1">
                                                            <i class="fa fa-file-o mr-1"></i> <strong>'.tr('Nome').':</strong> '.$name.'
                                                        </p>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <p class="mb-1">
                                                            <i class="fa fa-tag mr-1"></i> <strong>'.tr('Tipo').':</strong> '.(($tipo == 'PARTIAL') ? '<span class="badge badge-warning">🟠 '.tr('Parziale').'</span>' : '<span class="badge badge-success">🟢 '.tr('Completo').'</span>').'
                                                        </p>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <p class="mb-1">
                                                            <i class="fa fa-hdd-o mr-1"></i> <strong>'.tr('Dimensione').':</strong> <span id="c-'.$id.'">'.tr('Calcolo in corso').'...</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-right">
                                                <div class="btn-group">
                                                    <a class="btn btn-sm btn-primary" href="'.base_path().'/modules/backups/actions.php?op=getfile&number='.$id.'" target="_blank">
                                                        <i class="fa fa-download"></i> '.tr('Scarica').'
                                                    </a>
                                                    <a class="btn btn-sm btn-warning ask" data-backto="record-edit" data-method="post" data-op="restore" data-number="'.$id.'" data-msg="'.tr('Clicca su Ripristina per ripristinare questo backup').'" data-button="Ripristina" data-class="btn btn-lg btn-warning">
                                                        <i class="fa fa-upload"></i> '.tr('Ripristina').'
                                                    </a>
                                                    <a class="btn btn-sm btn-danger ask" title="'.tr('Elimina backup').'" data-backto="record-list" data-op="del" data-number="'.$id.'">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    loadSize("'.$id.'", "c-'.$id.'");
                                </script>
                            </div>';
            }
        } else {
            echo '
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> '.tr('Non è stato trovato alcun backup di questa tipologia.').'
                                </div>
                            </div>';
        }

        echo '
                        </div>
                    </div>
                    <div class="tab-pane fade" id="uncompressed" role="tabpanel" aria-labelledby="uncompressed-tab">
                        <div class="row">';

        // Backup non compressi e quindi non scaricabili
        if (!empty($backups_file)) {
            foreach ($backups_file as $id => $backup) {
                $name = basename((string) $backup);
                $info = Backup::readName($backup);

                $data = $info['YYYY'].'-'.$info['m'].'-'.$info['d'];
                $ora = $info['H'].':'.$info['i'].':'.$info['s'];

                echo '
                            <div class="col-md-12 mb-3">
                                <div class="card card-outline card-warning">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">
                                            <i class="fa fa-calendar-o mr-2"></i><strong>'.tr('Backup del _DATE_', [
                    '_DATE_' => Translator::dateToLocale($data),
                ]).'</strong>
                                            <span class="text-muted ml-2">'.tr('alle _TIME_', [
                    '_TIME_' => Translator::timeToLocale($ora),
                ]).'</span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1">
                                                            <i class="fa fa-file-o mr-1"></i> <strong>'.tr('Nome').':</strong> '.$name.'
                                                        </p>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <p class="mb-1">
                                                            <i class="fa fa-hdd-o mr-1"></i> <strong>'.tr('Dimensione').':</strong> <span id="n-'.$id.'">'.tr('Calcolo in corso').'...</span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <p class="mb-1">
                                                            <span class="badge badge-warning"><i class="fa fa-times"></i> '.tr('Non scaricabile').'</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-right">
                                                <div class="btn-group">
                                                    <a class="btn btn-sm btn-warning ask" data-backto="record-edit" data-method="post" data-op="restore" data-number="'.$id.'" data-msg="'.tr('Vuoi ripristinare questo backup?').'" data-button="Ripristina" data-class="btn btn-lg btn-warning">
                                                        <i class="fa fa-upload"></i> '.tr('Ripristina').'
                                                    </a>
                                                    <a class="btn btn-sm btn-danger ask" title="'.tr('Elimina backup').'" data-backto="record-list" data-op="del" data-number="'.$id.'">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    loadSize("'.$id.'", "n-'.$id.'");
                                </script>
                            </div>';
            }
        } else {
            echo '
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> '.tr('Non è stato trovato alcun backup di questa tipologia.').'
                                </div>
                            </div>';
        }

        echo '
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';
    }
} else {
    echo '
    
<div class="alert alert-danger">'.tr('La cartella di backup non esiste!').' '.tr('Non è possibile eseguire i backup!').'</div>';
}

echo '
<div class="row mt-4 mb-4">
    <div class="col-md-8 mx-auto text-center">
        <a class="btn btn-lg btn-success" aria-haspopup="true" aria-expanded="false" onclick="creaBackup(this)">
            <i class="fa fa-archive fa-fw"></i> '.tr('Crea nuovo backup').'
        </a>
    </div>
</div>';
