<?php

include_once __DIR__.'/../../core.php';

// Necesario per funzione \Util\Ini::getList
include_once Modules::filepath('MyImpianti', 'modutil.php');

$_SESSION['superselect']['id_categoria'] = $records[0]['id_categoria'];

$img = null;
if (!empty($records[0]['immagine'])) {
    $fileinfo = Uploads::fileInfo($records[0]['immagine']);

    $default_img = '/'.Uploads::getUploadDirectory($id_module).'/'.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];

    $img = file_exists(DOCROOT.$default_img) ? ROOTDIR.$default_img : ROOTDIR.'/'.Uploads::getUploadDirectory($id_module).'/'.$records[0]['immagine'];
}

?><form action="" method="post" id="edit-form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI ANAGRAFICI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Articolo'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-3">
					{[ "type": "image", "label": "<?php echo tr('Immagine'); ?>", "name": "immagine", "class": "img-thumbnail", "value": "<?php echo $img; ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "class":"alphanumeric-mask", "required": 1, "value": "$codice$" ]}
					<br>
                    {[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "categoria", "required": 1, "value": "$id_categoria$", "ajax-source": "categorie" ]}
				</div>

				<div class="col-md-5">
                    {[ "type": "checkbox", "label": "<?php echo tr("Seleziona per rendere attivo l'articolo"); ?>", "name": "attivo", "value": "$attivo$", "placeholder": "<?php echo tr('Articolo attivo'); ?>" ]}
				    <br>
                    {[ "type": "select", "label": "<?php echo tr('Sottocategoria'); ?>", "name": "subcategoria", "value": "$id_sottocategoria$", "ajax-source": "sottocategorie" ]}
                </div>
			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo tr('Quantità'); ?>", "name": "qta", "required": 1, "value": "$qta$", "readonly": 1, "decimals": "qta", "min-value": "undefined" ]}
					<input type="hidden" id="old_qta" value="<?php echo $records[0]['qta']; ?>">
				</div>
				<div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr('Modifica quantità manualmente'); ?>", "name": "qta_manuale", "value": 0, "help": "<?php echo tr('Seleziona per modificare manualmente la quantità'); ?>", "placeholder": "<?php echo tr('Quantità manuale'); ?>", "extra": "<?php echo ($records[0]['servizio']) ? 'disabled' : ''; ?>" ]}
					<script type="text/javascript">

                        $(document).ready(function() {

                            $('#servizio').click(function(){
                                $("#qta_manuale").attr("disabled", $('#servizio').is(":checked"));
                            });


    				        $('#qta_manuale').click(function(){
    							$("#qta").attr("readonly", !$('#qta_manuale').is(":checked"));
								if($('#qta_manuale').is(":checked")){
									$("#div_modifica_manuale").show();
									$("#div_modifica_manuale").show();
									$("#descrizione_movimento").attr('required', true);
									$("#data_movimento").attr('required', true);
								}else{
									$("#div_modifica_manuale").hide();
									$('#qta').val($('#old_qta').val());
									$("#descrizione_movimento").attr('required', false);
									$("#data_movimento").attr('required', false);
								}
    				        });

                         });

					</script>

                </div>

				<div class="col-md-2">
					{[ "type": "select", "label": "<?php echo tr('Unità di misura'); ?>", "name": "um", "value": "$um$", "ajax-source": "misure", "icon-after": "add|<?php echo Modules::get('Unità di misura')['id']; ?>" ]}
				</div>

				<?php
                $records[0]['abilita_serial'] = ($records[0]['serial'] > 0) ? 1 : $records[0]['abilita_serial'];
                if (empty($records[0]['abilita_serial'])) {
                    $plugin = $dbo->fetchArray("SELECT id FROM zz_plugins WHERE name='Serial'");
                    echo '<script>$("#link-tab_'.$plugin[0]['id'].'").addClass("disabled");</script>';
                }
                ?>

				  <div class="col-md-4">
					{[ "type": "checkbox", "label": "<?php echo tr('Abilita serial number'); ?>", "name": "abilita_serial", "value": "$abilita_serial$", "help": "<?php echo tr('Abilita serial number in fase di aggiunta articolo in fattura o ddt'); ?>", "placeholder": "<?php echo tr('Serial number'); ?>", "extra": "<?php echo ($records[0]['serial'] > 0) ? 'readonly' : ''; ?>" ]}
                </div>


			</div>

			<div class='row' id="div_modifica_manuale" style="display:none;">
				<div class='col-md-3'>
					{[ "type": "text", "label": "<?php echo tr('Descrizione movimento'); ?>", "name": "descrizione_movimento" ]}
				</div>
				<div class='col-md-3'>
					{[ "type": "date", "label": "<?php echo tr('Data movimento'); ?>", "name": "data_movimento", "value": "-now-" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>
		</div>
	</div>

    <!-- informazioni Acquisto/Vendita -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo tr('Acquisto'); ?></h3>
                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Prezzo di acquisto'); ?>", "name": "prezzo_acquisto", "value": "$prezzo_acquisto$", "icon-after": "&euro;" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Soglia minima quantità'); ?>", "name": "threshold_qta", "value": "$threshold_qta$", "decimals": "qta", "min-value": "undefined" ]}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo tr('Vendita'); ?></h3>
                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                          {[ "type": "number", "label": "<?php echo tr('Prezzo di vendita'); ?>", "name": "prezzo_vendita", "value": "$prezzo_vendita$", "icon-after": "&euro;" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "select", "label": "<?php echo tr('Iva di vendita'); ?>", "name": "idiva_vendita", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC", "value": "$idiva_vendita$", "valore_predefinito": "Iva predefinita" ]}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Garanzia'); ?>", "name": "gg_garanzia", "decimals": 0, "value": "$gg_garanzia$", "icon-after": "GG" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "checkbox", "label": "<?php echo tr('Questo articolo è un servizio'); ?>", "name": "servizio", "value": "$servizio$", "help": "<?php echo tr('Le quantità non saranno considerate'); ?>", "placeholder": "<?php echo tr('Servizio'); ?>" ]}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Peso lordo'); ?>", "name": "peso_lordo", "value": "$peso_lordo$", "icon-after": "KG" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Volume'); ?>", "name": "volume", "value": "$volume$", "icon-after": "M<sup>3</sup>" ]}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Aggiungi informazioni componente personalizzato'); ?></h3>
		</div>

		<div class="panel-body">
<?php

    echo '
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="componente_filename">'.tr('Seleziona un componente').':</label>';
    echo "
                        <select class=\"form-control superselect\" id=\"componente_filename\" name=\"componente_filename\" onchange=\"$.post('".$rootdir."/modules/my_impianti/actions.php', {op: 'load_componente', idarticolo: '".$id_record."', filename: $(this).find('option:selected').val() }, function(response){ $('#info_componente').html( response ); start_superselect();    $('.datepicker').datetimepicker({  locale: globals.locale, format: 'L' } ); } );\">\n";
    echo '
                            <option value="0">- Collega ad un componente -</option>';

    $cmp = \Util\Ini::getList($docroot.'/files/my_impianti/');

    if (count($cmp) > 0) {
        for ($c = 0; $c < count($cmp); ++$c) {
            ($records[0]['componente_filename'] == $cmp[$c][0]) ? $attr = 'selected="selected"' : $attr = '';
            echo '
                            <option value="'.$cmp[$c][0]."\" $attr>".$cmp[$c][1]."</option>\n";
        }
    }

    echo '
                        </select>
                    </div>
                </div>
            </div>';

    echo '
            <div id="info_componente">';

    genera_form_componente($records[0]['contenuto']);

    echo '
            </div>';

echo '
		</div>
	</div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Prezzo articolo per listino').'</h3>
		</div>

		<div class="panel-body">';

        $rsl = $dbo->fetchArray('SELECT * FROM mg_listini ORDER BY id ASC');

        $rsart = $dbo->fetchArray('SELECT id, prezzo_vendita FROM mg_articoli WHERE id='.prepare($id_record));

        if (count($rsl) > 0) {
            echo '
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <table class="table table-striped table-condensed table-bordered">
                        <tr>
                            <th>'.tr('Listino').'</th>
                            <th>'.tr('Prezzo di vendita finale').'</th>
                        </tr>';

            // listino base
            echo '
                        <tr>
                            <td>'.tr('Base').'</td>
                            <td>'.Translator::numberToLocale($rsart[0]['prezzo_vendita']).' &euro;</td>
                        </tr>';

            for ($i = 0; $i < count($rsl); ++$i) {
                echo '
                        <tr>
                            <td>'.$rsl[$i]['nome'].'</td>
                            <td>'.Translator::numberToLocale($rsart[0]['prezzo_vendita'] - $rsart[0]['prezzo_vendita'] / 100 * $rsl[$i]['prc_guadagno']).' &euro;</td>
                        </tr>';
            }

            echo '
                    </table>
                </div>
            </div>';
        } else {
            echo '
            <div class="alert alert-info">
                '.tr('Non ci sono listini caricati').'... '.Modules::link('Listini', null, tr('Crea il primo listino!')).'.
            </div>';
        }
echo '
		</div>
	</div>


	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Questo articolo è presente nei seguenti automezzi').':</h3>
		</div>
		<div class="panel-body">';

        // Quantità nell'automezzo
        $rsa = $dbo->fetchArray('SELECT qta, (SELECT nome FROM dt_automezzi WHERE id=idautomezzo) AS nome, (SELECT targa FROM dt_automezzi WHERE id=idautomezzo) AS targa FROM mg_articoli_automezzi WHERE idarticolo='.prepare($id_record));

        if (count($rsa) > 0) {
            echo '
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <table class="table table-striped table-condensed table-bordered">
                        <tr>
                            <th>'.tr('Nome automezzo').'</th>
                            <th>'.tr('Targa').'</th>
                            <th>'.tr('Q.tà').'</th>
                        </tr>';

            for ($i = 0; $i < count($rsa); ++$i) {
                echo '
                        <tr>
                            <td>'.$rsa[$i]['nome'].'</td>
                            <td>'.$rsa[$i]['targa'].'</td>
                            <td>'.Translator::numberToLocale($rsa[$i]['qta']).' '.$rs[0]['unita_misura'].'</td>
                        </tr>';
            }

            echo '
                    </table>
                </div>
            </div>';
        } else {
            echo '
            <div class="alert alert-info">
                '.tr('Non ci sono automezzi collegati').'... '.Modules::link('Automezzi', null, tr('Collega il primo automezzo!')).'.
            </div>';
        }
?>
		</div>
	</div>
</form>

{( "name": "filelist_and_upload", "id_module": "<?php echo $id_module; ?>", "id_record": "<?php echo $id_record; ?>" )}

<script>
$("#categoria").change( function(){
	session_set("superselect,id_categoria", $(this).val(), 0);
	$("#subcategoria").val(null).trigger("change");
});
</script>


<?php

// Collegamenti diretti
// Fatture, ddt, preventivi collegati a questo articolo
$elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idarticolo` = '.prepare($id_record).')

UNION SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt`.`descrizione` AS tipo_documento, `dt_tipiddt`.`dir` FROM `dt_ddt` JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` WHERE `dt_ddt`.`id` IN (SELECT `idddt` FROM `dt_righe_ddt` WHERE `idarticolo` = '.prepare($id_record).')

UNION SELECT `co_preventivi`.`id`, `co_preventivi`.`data_bozza`, `co_preventivi`.`numero`,  0 AS numero_esterno , "Preventivo" AS tipo_documento, 0 AS dir FROM `co_preventivi` WHERE `co_preventivi`.`id` IN (SELECT `idpreventivo` FROM `co_righe_preventivi` WHERE `idarticolo` = '.prepare($id_record).')  ORDER BY `data`');

if (!empty($elementi)) {
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
            '_NUM_' => count($elementi)
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        //se non è un preventivo è un ddt o una fattura
        //se non è un ddt è una fattura.
        if (in_array($elemento['tipo_documento'], ['Preventivo'])) {
            $modulo = 'Preventivi';
        } elseif (!in_array($elemento['tipo_documento'], ['Ddt di vendita', 'Ddt di acquisto'])) {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
        } else {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';
        }

        $id = $elemento['id'];

        echo '
            <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
}

if (!empty($elementi)) {
    echo '
<div class="alert alert-error">
    '.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale').'.
</div>';
}

?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
