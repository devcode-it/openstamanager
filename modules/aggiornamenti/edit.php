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

// Personalizzazioni di codice
if (function_exists('customComponents')) {
    $custom = customComponents();
    $tables = customTables();
    if (!empty($custom) || !empty($tables)) {
        echo '
	<div class="card card-warning">
		<div class="card-header with-border">
			<h3 class="card-title"><span class="tip" title="'.tr('Elenco delle personalizzazioni rilevabili dal gestionale').'.">
				<i class="fa fa-edit"></i> '.tr('Personalizzazioni').'
			</span></h3>
		</div>
		<div class="card-body">';

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

			<div class="alert alert-warning" role="alert"> <i class="fa fa-exclamation-triangle"></i> '.tr("Attenzione, il gestionale presenta delle personalizzazioni: si sconsiglia l'aggiornamento senza il supporto dell'assistenza ufficiale").'.</div>';
        } else {
            echo '
			<p>'.tr('Non ci sono strutture personalizzate').'.</p>';
        }

        if (!empty($tables)) {
            echo '
			<div class="alert alert-warning">
				<i class="fa fa-warning"></i>
				<b>'.tr('Attenzione!').'</b> '.tr('Ci sono delle tabelle non previste nella versione standard del gestionale: _LIST_', [
                '_LIST_' => implode(', ', $tables),
            ]).'.
			</div>';
        }

        echo '
		</div>
	</div>';
    }
}

// Fix per funzione base_path non trovata in fase di aggiornamento da versione < 2.4.19
if (!function_exists('base_path')) {
    function base_path()
    {
        return ROOTDIR;
    }
}

if (!function_exists('base_dir')) {
    function base_dir()
    {
        return DOCROOT;
    }
}

// Aggiornamenti
$alerts = [];

if (!extension_loaded('zip')) {
    $alerts[tr('Estensione ZIP')] = tr('da abilitare');
}

$upload_max_filesize = ini_get('upload_max_filesize');
$upload_max_filesize = str_replace(['k', 'M'], ['000', '000000'], $upload_max_filesize);
// Dimensione minima: 32MB
if ($upload_max_filesize < 32000000) {
    $alerts['upload_max_filesize'] = '32MB';
}

$post_max_size = ini_get('post_max_size');
$post_max_size = str_replace(['k', 'M'], ['000', '000000'], $post_max_size);
// Dimensione minima: 32MB
if ($post_max_size < 32000000) {
    $alerts['post_max_size'] = '32MB';
}

if (!empty($alerts)) {
    echo '
<div class="alert alert-info">
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

function checksum(button) {
    openModal("'.tr('Controllo dei file').'", "'.$module->fileurl('checksum.php').'?id_module='.$id_module.'");
}

function database(button) {
    openModal("'.tr('Controllo del database').'", "'.$module->fileurl('database.php').'?id_module='.$id_module.'");
}

function controlli(button) {
    openModal("'.tr('Controlli del gestionale').'", "'.$module->fileurl('controlli.php').'?id_module='.$id_module.'");
}

function search(button) {
    let restore = buttonLoading(button);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "post",
        data: {
            id_module: globals.id_module,
            op: "check",
        },
        success: function(data){
            if (data === "none" || !data) {
                $("#update-search").html("<i class=\"fa fa-check-circle text-success\" aria-hidden=\"true\"></i> '.tr('Nessun aggiornamento disponibile').'.");
            } else {
                let beta_warning = data.includes("beta") ? "<br><i class=\"fa-exclamation-triangle text-danger\" aria-hidden=\"true\"></i> <b class=\"text-danger\">'.tr('Attenzione: la versione individuata è in fase sperimentale e potrebbe pertanto presentare diversi malfunzionamenti. Se ne sconsiglia l\'aggiornamento in installazioni di produzione').'.</b>" : "";
                $("#update-search").html("'.tr("E' stato individuato un nuovo aggiornamento").': " + data + "." + beta_warning + "<br>'.tr('Scaricalo ora: _LINK_', [
    '_LINK_' => "<a target='_blank' href='https://github.com/devcode-it/openstamanager/releases'>https://github.com/devcode-it/openstamanager/releases</a>",
]).'");
            }
        }
    });
}
</script>

<div class="row">';

// Verifiche di integrità
echo '
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header with-border">
                <h3 class="card-title">
                    '.tr("Verifica l'integrità dell'installazione").' <span class="tip" title="'.tr("Verifica l'integrità della tua installazione attraverso un controllo sui checksum dei file e sulla struttura del database").'."><i class="fa fa-question-circle-o"></i></span>
                </h3>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-primary btn-block" onclick="checksum(this)">
                    <i class="fa fa-list-alt"></i> '.tr('Controlla file').'
                </button>

                <button type="button" class="btn btn-info btn-block" onclick="database(this)">
                    <i class="fa fa-database"></i> '.tr('Controlla database').'
                </button>

                <button type="button" class="btn btn-block" onclick="controlli(this)">
                    <i class="fa fa-stethoscope"></i> '.tr('Controlla gestionale').'
                </button>
            </div>
        </div>
    </div>';

// Controllo automatico della presenza di aggiornamenti per il gestionale
echo '

    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header with-border">
                <h3 class="card-title">
                    '.tr('Ricerca aggiornamenti').' <span class="tip" title="'.tr('Controllo automatico della presenza di aggiornamenti per il gestionale').'."><i class="fa fa-question-circle-o"></i></span>
                </h3>
            </div>
            <div class="card-body" id="update-search">';
if (extension_loaded('curl')) {
    echo '		<button type="button" class="btn btn-info btn-block" onclick="search(this)">
                    <i class="fa fa-search"></i> '.tr('Ricerca').'
                </button>';
} else {
    echo '		<button type="button" class="btn btn-warning btn-block disabled" >
                    <i class="fa fa-warning"></i> '.tr('Estensione curl non supportata').'.
                </button>';
}

echo '   </div>
        </div>
    </div>';

// Form di caricamento aggiornamenti gestionale o moduli
echo '
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header with-border">
                <h3 class="card-title">
                    '.tr('Carica aggiornamenti o nuovi moduli').' <span class="tip" title="'.tr('Form di caricamento aggiornamenti del gestionale e innesti di moduli e plugin').'."><i class="fa fa-question-circle-o"></i></span>
                </h3>
            </div>
            <div class="card-body">
                <form action="'.base_path().'/controller.php?id_module='.$id_module.'" method="post" enctype="multipart/form-data" id="update">
                    <input type="hidden" name="op" value="upload">

			        {[ "type": "file", "name": "blob", "required": 1, "accept": ".zip", "disabled": '.(setting('Attiva aggiornamenti') ? 0 : 1).' ]}

                    ';

if (!empty($custom) || !empty($tables)) {
    $disabled = 'disabled';
    echo '                  <input type="checkbox" id="aggiorna_custom" class="pull-left" style="margin-top:10px;"  value="1" >&nbsp;
                        <label for="aggiorna_custom" style="margin-top:7px;" >'.tr("Desidero comunque procedere all'aggiornamento").'.</label>
                        <script>
                            $("#aggiorna_custom").change(function() {
                                if(this.checked) {
                                    $("#aggiorna").removeClass("disabled");
                                }else{
                                    $("#aggiorna").addClass("disabled");
                                }
                            });
                        </script>';
}
echo '

                    <button type="button" class="btn btn-primary pull-right '.$disabled.'" id="aggiorna" onclick="update()">
                        <i class="fa fa-upload"></i> '.tr('Carica').'
                    </button>';

echo '
                </form>
            </div>
        </div>
    </div>';

echo '
</div>';

// Requisiti
echo '
<hr>
<div>
    <h3>'.tr('Requisiti').'</h3>';

include base_dir().'/include/init/requirements.php';

echo '

</div>';
