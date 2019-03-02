<?php

use Modules\Aggiornamenti\Aggiornamento;

try {
    $update = new Aggiornamento();

    include $module->filepath('view.php');

    return;
} catch (InvalidArgumentException $e) {
}

// Personalizzazioni di codice
if (function_exists(custom)) {
    $custom = custom();
    $tables = customTables();
    if (!empty($custom) || !empty($tables)) {
        echo '
	<div class="box box-warning">
		<div class="box-header with-border">
			<h3 class="box-title"><span class="tip" title="'.tr('Elenco delle personalizzazioni rilevabili dal gestionale').'.">
				<i class="fa fa-edit"></i> '.tr('Personalizzazioni').'
			</span></h3>
		</div>
		<div class="box-body">';

        if (!empty($custom)) {
            echo '
			<table class="table table-hover table-striped">
				<tr>
					<th width="10%">'.tr('Percorso').'</th>
					<th width="15%">'.tr('Cartella personalizzata').'</th>
					<th width="15%">'.tr('Database personalizzato').'</th>
				</tr>';

            foreach ($custom as $element) {
                echo '
				<tr>
					<td>'.$element['path'].'</td>
					<td>'.($element['directory'] ? 'Si' : 'No').'</td>
					<td>'.($element['database'] ? 'Si' : 'No').'</td>
				</tr>';
            }

            echo '
			</table>

			<p><strong>'.tr("Si sconsiglia l'aggiornamento senza il supporto dell'assistenza ufficiale").'.</strong></p>';
        } else {
            echo '
			<p>'.tr('Non ci sono strutture personalizzate').'.</p>';
        }

        if (!empty($tables)) {
            echo '
			<div class="alert alert-warning">
				<i class="fa fa-warning"></i>
				<b>Attenzione!</b> Ci sono delle tabelle non previste nella versione standard del gestionale: '.implode(', ', $tables).'.
			</div>';
        }

        echo '
		</div>
	</div>';
    }
}

// Aggiornamenti
if (setting('Attiva aggiornamenti')) {
    $alerts = [];

    if (!extension_loaded('zip')) {
        $alerts[tr('Estensione ZIP')] = tr('da abilitare');
    }

    $upload_max_filesize = ini_get('upload_max_filesize');
    $upload_max_filesize = str_replace(['k', 'M'], ['000', '000000'], $upload_max_filesize);
    // Dimensione minima: 16MB
    if ($upload_max_filesize < 16000000) {
        $alerts['upload_max_filesize'] = '16MB';
    }

    $post_max_size = ini_get('post_max_size');
    $post_max_size = str_replace(['k', 'M'], ['000', '000000'], $post_max_size);
    // Dimensione minima: 16MB
    if ($post_max_size < 16000000) {
        $alerts['post_max_size'] = '16MB';
    }

    if (!empty($alerts)) {
        echo '
<div class="alert alert-warning">
    <p>'.tr('Devi modificare il seguenti parametri del file di configurazione PHP (_FILE_) per poter caricare gli aggiornamenti', [
        '_FILE_' => '<b>php.ini</b>',
    ]).':<ul>';
        foreach ($alerts as $key => $value) {
            echo '
        <li><b>'.$key.'</b> = '.$value.'</li>';
        }
        echo '
    </ul></p>
</div>';
    }

    echo '
<script>
function update() {
    if ($("#blob").val()) {
        swal({
            title: "'.tr('Avviare la procedura?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì').'"
        }).then(function (result) {
            $("#update").submit();
        })
    } else {
        swal({
            title: "'.tr('Selezionare un file!').'",
            type: "error",
        })
    }
}

function search(button) {
    buttonLoading(button);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "post",
        data: {
            id_module: globals.id_module,
            op: "check",
        },
        success: function(data){
            $("#update-search").addClass("hide");

            if (data == "none") {
                $("#update-none").removeClass("hide");
            } else {
                $("#update-version").text(data);
                $("#update-download").removeClass("hide");
            }
        }
    });
}

function download(button) {
    buttonLoading(button);
    $("#main_loading").show();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "post",
        data: {
            id_module: globals.id_module,
            op: "download",
        },
        success: function(){
            window.location.reload();
        }
    });
}
</script>';

    echo '
<div class="row">
    <div class="col-md-8">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    '.tr('Carica un aggiornamento').' <span class="tip" title="'.tr('Form di caricamento aggiornamenti del gestionale e innesti di moduli e plugin').'."><i class="fa fa-question-circle-o"></i></span>
                </h3>
            </div>
            <div class="box-body">
                <form action="'.ROOTDIR.'/controller.php?id_module='.$id_module.'" method="post" enctype="multipart/form-data" class="form-inline" id="update">
                    <input type="hidden" name="op" value="upload">
                    <input type="hidden" name="backto" value="record-list">

                    <label><input type="file" name="blob" id="blob"></label>

                    <button type="button" class="btn btn-primary pull-right" onclick="update()">
                        <i class="fa fa-upload"></i> '.tr('Carica').'
                    </button>
                </form>
            </div>
        </div>
    </div>';

    echo '

    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    '.tr('Ricerca aggiornamenti').' <span class="tip" title="'.tr('Controllo automatico della presenza di aggiornamenti per il gestionale').'."><i class="fa fa-question-circle-o"></i></span>
                </h3>
            </div>
            <div class="box-body">';

    if (extension_loaded('curl')) {
        echo '
            <div id="update-search">
                <button type="button" class="btn btn-info btn-block" onclick="search(this)">
                    <i class="fa fa-search"></i> '.tr('Ricerca').'
                </button>
            </div>

            <div id="update-download" class="hide">
                <p>'.tr("E' stato individuato un nuovo aggiornamento").': <b id="update-version"></b>.</p>
                <p>'.tr('Scaricalo manualmente (_LINK_) oppure in automatico', [
                    '_LINK_' => "<a href='https://github.com/devcode-it/openstamanager/releases'>https://github.com/devcode-it/openstamanager/releases</a>",
                ]).':</p>

                <button type="button" class="btn btn-success btn-block" onclick="download(this)">
                    <i class="fa fa-download"></i> '.tr('Scarica').'
                </button>
            </div>

            <div id="update-none" class="hide">
                <p>'.tr('Nessun aggiornamento presente').'.</p>
            </div>';
    } else {
        echo'
        <button type="button" class="btn btn-warning btn-block disabled" >
            <i class="fa fa-warning"></i> '.tr('Estensione curl non supportata').'.
        </button>';
    }

    echo '
            </div>
        </div>
    </div>
</div>';
}

// Elenco moduli installati
echo '
<div class="row">
    <div class="col-md-12 col-lg-6">
        <h3>'.tr('Moduli installati').'</h3>
        <table class="table table-hover table-bordered table-condensed">
            <tr>
                <th>'.tr('Nome').'</th>
                <th width="50">'.tr('Versione').'</th>
                <th width="30">'.tr('Stato').'</th>
                <th width="30">'.tr('Compatibilità').'</th>
                <th width="20">'.tr('Opzioni').'</th>
            </tr>';

$modules = Modules::getHierarchy();

$osm_version = Update::getVersion();

echo submodules($modules);

echo '
        </table>
    </div>';

// Widgets
echo '
    <div class="col-md-12 col-lg-6">
        <h3>'.tr('Widgets').'</h3>
        <table class="table table-hover table-bordered table-condensed">
            <tr>
                <th>'.tr('Nome').'</th>
                <th width="200">'.tr('Posizione').'</th>
                <th width="30">'.tr('Stato').'</th>
                <th width="30">'.tr('Posizione').'</th>
            </tr>';

$widgets = $dbo->fetchArray('SELECT zz_widgets.id, zz_widgets.name AS widget_name, zz_modules.name AS module_name, zz_widgets.enabled AS enabled, location FROM zz_widgets INNER JOIN zz_modules ON zz_widgets.id_module=zz_modules.id ORDER BY `id_module` ASC, `zz_widgets`.`order` ASC');

$previous = '';

foreach ($widgets as $widget) {
    // Nome modulo come titolo sezione
    if ($widget['module_name'] != $previous) {
        echo '
            <tr>
                <th colspan="4">'.$widget['module_name'].'</th>
            </tr>';
    }

    // STATO
    if ($widget['enabled']) {
        $stato = '<i class="fa fa-cog fa-spin text-success" data-toggle="tooltip" title="'.tr('Abilitato').'. '.tr('Clicca per disabilitarlo').'..."></i>';
        $class = 'success';
    } else {
        $stato = '<i class="fa fa-cog text-warning" data-toggle="tooltip" title="'.tr('Non abilitato').'"></i>';
        $class = 'warning';
    }

    // Possibilità di disabilitare o abilitare i moduli tranne quello degli aggiornamenti
    if ($widget['enabled']) {
        $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Disabilitare questo widget?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'disable_widget', id: '".$widget['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\">".$stato."</a>\n";
    } else {
        $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Abilitare questo widget?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'enable_widget', id: '".$widget['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\"\">".$stato."</a>\n";
    }

    // POSIZIONE
    if ($widget['location'] == 'controller_top') {
        $location = tr('Schermata modulo in alto');
    } elseif ($widget['location'] == 'controller_right') {
        $location = tr('Schermata modulo a destra');
    }

    if ($widget['location'] == 'controller_right') {
        $posizione = "<i class='fa fa-arrow-up text-warning' data-toggle='tooltip' title=\"".tr('Clicca per cambiare la posizione...')."\"></i>&nbsp;<i class='fa fa-arrow-right text-success' data-toggle='tooltip' title=\"\"></i>";
        $posizione = "<a href='javascript:;' onclick=\"if( confirm('".tr('Cambiare la posizione di questo widget?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'change_position_widget_top', id: '".$widget['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\"\">".$posizione."</a>\n";
    } elseif ($widget['location'] == 'controller_top') {
        $posizione = "<i class='fa fa-arrow-up text-success' data-toggle='tooltip' title=\"\"></i>&nbsp;<i class='fa fa-arrow-right text-warning' data-toggle='tooltip' title=\"".tr('Clicca per cambiare la posizione...').'"></i></i>';
        $posizione = "<a href='javascript:;' onclick=\"if( confirm('".tr('Cambiare la posizione di questo widget?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'change_position_widget_right', id: '".$widget['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\"\">".$posizione."</a>\n";
    }

    echo '
            <tr class="'.$class.'">
                <td>'.$widget['widget_name'].'</td>
                <td align="right"><small>'.$location.'</small></td>
                <td align="center">'.$stato.'</td>
                <td align="center">'.$posizione.'</td>
            </tr>';

    $previous = $widget['module_name'];
}

echo '
        </table>
    </div>
</div>';

// Requisiti
echo '
<hr>
<div>
    <h3>'.tr('Requisiti').'</h3>';

include DOCROOT.'/include/init/requirements.php';

echo '

</div>';
