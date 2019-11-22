<?php

include_once __DIR__.'/../../core.php';

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
            if (data == "none" || data == "" ) {
                $("#update-search").html("'.tr('Nessun aggiornamento disponibile').'.");
            } else {
                $("#update-search").html("'.tr("E' stato individuato un nuovo aggiornamento").': " + data + ".<br>'.tr('Scaricalo ora: _LINK_', [
                    '_LINK_' => "<a target='_blank' href='https://github.com/devcode-it/openstamanager/releases'>https://github.com/devcode-it/openstamanager/releases</a>",
                ]).'");
            }
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
                <form action="'.ROOTDIR.'/controller.php?id_module='.$id_module.'" method="post" enctype="multipart/form-data" id="update">
                    <input type="hidden" name="op" value="upload">

			        {[ "type": "file", "name": "blob", "required": 1, "accept": ".zip" ]}

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
            <div class="box-body" id="update-search">';
    if (extension_loaded('curl')) {
        echo'		<button type="button" class="btn btn-info btn-block" onclick="search(this)">
                    <i class="fa fa-search"></i> '.tr('Ricerca').'
                </button>';
    } else {
        echo'		<button type="button" class="btn btn-warning btn-block disabled" >
                    <i class="fa fa-warning"></i> '.tr('Estensione curl non supportata').'.
                </button>';
    }

    echo'   </div>
        </div>
    </div>
</div>';
}

// Requisiti
echo '
<hr>
<div>
    <h3>'.tr('Requisiti').'</h3>';

include DOCROOT.'/include/init/requirements.php';

echo '

</div>';
