<?php

include_once __DIR__.'/../../core.php';

$_SESSION['superselect']['id_categoria'] = $records[0]['id_categoria'];

?><form action="" method="post" role="form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI ANAGRAFICI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Articolo'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

			<div class="row">
				<div class="col-md-3">
					<?php
                    $immagine01 = ($records[0]['immagine01'] == '') ? '' : $rootdir.'/files/articoli/'.$records[0]['immagine01'];
                    ?>
					{[ "type": "image", "label": "<?php echo tr('Immagine'); ?>", "name": "immagine01", "class": "img-thumbnail", "value": "<?php echo $immagine01; ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "required": 1, "value": "$codice$" ]}
				</div>

				<div class="col-md-5">
					{[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "categoria", "required": 1, "value": "$id_categoria$", "ajax-source": "categorie" ]}
					<br>
					{[ "type": "select", "label": "<?php echo tr('Subcategoria'); ?>", "name": "subcategoria", "value": "$id_sottocategoria$", "ajax-source": "sottocategorie" ]}
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Quantità'); ?>", "name": "qta", "required": 1, "value": "$qta$", "readonly": 1, "decimals": "qta|undefined" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "checkbox", "label": "<?php echo tr('Modifica manualmente quantità'); ?>", "name": "qta_manuale", "value": 0, "help": "<?php echo tr('Seleziona per modificare manualmente la quantità'); ?>", "placeholder": "<?php echo tr('Quantità manuale'); ?>" ]}

					<script type="text/javascript">

				        $('#qta_manuale').click(function(){
							$("#qta").attr("readonly", !$('#qta_manuale').is(":checked"));
				        });

					</script>

                </div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Unità di misura'); ?>", "name": "um", "value": "$um$", "ajax-source": "misure", "icon-after": "add|<?php echo Modules::get('Unità di misura')['id']; ?>" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo tr('Prezzo di vendita base'); ?>", "name": "prezzo_vendita", "value": "$prezzo_vendita$", "icon-after": "&euro;" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Iva di vendita'); ?>", "name": "idiva_vendita", "values": "query=SELECT * FROM co_iva ORDER BY descrizione ASC", "value": "$idiva_vendita$", "valore_predefinito": "Iva predefinita" ]}
                </div>

				<div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr("Seleziona per rendere visibile l'articolo"); ?>", "name": "attivo", "value": "$attivo$", "help": "", "placeholder": "<?php echo tr('ATTIVO'); ?>" ]}
                </div>

                <div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr('Abilita serial number'); ?>", "name": "abilita_serial", "value": "$abilita_serial$", "help": "", "placeholder": "<?php echo tr('Abilita serial number in fase di aggiunta articolo in fattura o ddt'); ?>" ]}
                </div>
<?php
if (empty($records[0]['abilita_serial'])) {
                        $plugin = $dbo->fetchArray("SELECT id FROM zz_plugins WHERE name='Serial'");

                        echo '
    <script>
        $("#link-tab_'.$plugin[0]['id'].'").addClass("disabled");
    </script>';
                    }
?>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Prezzo di acquisto'); ?>", "name": "prezzo_acquisto", "value": "$prezzo_acquisto$", "icon-after": "&euro;" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Soglia minima quantità'); ?>", "name": "threshold_qta", "value": "$threshold_qta$", "decimals": "qta" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Giorni di garanzia'); ?>", "name": "gg_garanzia", "decimals": 0, "value": "$gg_garanzia$" ]}
				</div>
			</div>

            <div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Peso lordo'); ?>", "name": "peso_lordo", "value": "$peso_lordo$", "icon-after": "KG" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Volume'); ?>", "name": "volume", "value": "$volume$", "icon-after": "M<sup>3</sup>" ]}
				</div>
			</div>


			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>
		</div>
	</div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Aggiungi informazioni componente personalizzato'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

<?php

    /* necesario per funzione \Util\Ini::getList */
    include $docroot.'/modules/my_impianti/modutil.php';

    echo '
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="componente_filename">'.tr('Seleziona un componente').':</label>';
    echo "
                        <select class=\"form-control superselect\" id=\"componente_filename\" name=\"componente_filename\" onchange=\"$.post('".$rootdir."/modules/my_impianti/actions.php', {op: 'load_componente', idarticolo: '".$id_record."', filename: $(this).find('option:selected').val() }, function(response){ $('#info_componente').html( response ); } );\">\n";
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

<script>
$("#categoria").change( function(){
	session_set("superselect,id_categoria", $(this).val(), 0);
	$("#subcategoria").val(null).trigger("change");
});
</script>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php

$count = $dbo->fetchArray('SELECT COUNT(*) AS tot FROM (
    SELECT `idarticolo` FROM `co_righe_documenti` WHERE `idarticolo` = '.prepare($id_record).' UNION
    SELECT `idarticolo` FROM `dt_righe_ddt` WHERE `idarticolo` = '.prepare($id_record).' UNION
    SELECT `idarticolo` FROM `or_righe_ordini` WHERE `idarticolo` = '.prepare($id_record).' UNION
    SELECT `idarticolo` FROM `mg_articoli_interventi` WHERE `idarticolo` = '.prepare($id_record).'
) AS count');

$tot = $count[0]['tot'];
if ($tot > 0) {
    echo '
    <div class="alert alert-danger">
        '.tr('Ci sono _NUM_ righe di documenti collegate', [
            '_NUM_' => $tot,
        ]).'.
        '.tr('Eliminando questo elemento si potrebbero verificare problemi nelle altre sezioni del gestionale!').'
    </div>';
}
