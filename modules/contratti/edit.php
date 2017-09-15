<?php

include_once __DIR__.'/../../core.php';

unset($_SESSION['superselect']['idanagrafica']);
$_SESSION['superselect']['idanagrafica'] = $records[0]['idanagrafica'];

?><script src="<?php echo $rootdir ?>/modules/contratti/js/contratti_helper.js"></script>

<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

	<!-- DATI INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Intestazione') ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<?php
                if ($records[0]['rinnovabile']) {
                    echo "
                <button type=\"button\" class=\"btn btn-warning\" onclick=\"if( confirm('Rinnovare questo contratto?') ){ location.href='".$rootdir.'/editor.php?op=renew&id_module='.$id_module.'&id_record='.$id_record."'; }\">
                    <i class=\"fa fa-refresh\"></i> ".tr('Rinnova').'...
                </button>';
                }
                ?>
				<a class="btn btn-info" href="<?php echo $rootdir ?>/pdfgen.php?ptype=contratti&idcontratto=<?php echo $id_record ?>" target="_blank"><i class="fa fa-print"></i> <?php echo tr('Stampa contratto') ?></a>
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
				<br><br>
			</div>
			<div class="clearfix"></div><br>

			<div class="row">
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "id": "idanagrafica_c", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Agente'); ?>", "name": "idagente", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Agente' AND deleted=0 ORDER BY ragione_sociale", "value": "$idagente$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Referente'); ?>", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "number", "label": "<?php echo tr('Validità'); ?>", "name": "validita", "decimals": "0", "value": "$validita$", "icon-after": "giorni" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "checkbox", "label": "<?php echo tr('Rinnovabile'); ?>", "name": "rinnovabile", "value": "$rinnovabile$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "number", "label": "<?php echo tr('Preavviso per rinnovo'); ?>", "name": "giorni_preavviso_rinnovo", "decimals": "0", "value": "$giorni_preavviso_rinnovo$", "icon-after": "giorni" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data bozza'); ?>", "maxlength": 10, "name": "data_bozza", "value": "$data_bozza$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data accettazione'); ?>", "maxlength": 10, "name": "data_accettazione", "value": "$data_accettazione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data conclusione'); ?>", "maxlength": 10, "name": "data_conclusione", "value": "$data_conclusione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data rifiuto'); ?>", "maxlength": 10, "name": "data_rifiuto", "value": "$data_rifiuto$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Metodo di pagamento'); ?>", "name": "idpagamento", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione", "value": "$idpagamento$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstato", "required": 1, "values": "query=SELECT id, descrizione FROM co_staticontratti", "value": "$idstato$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "multiple": "1", "label": "<?php echo tr('Impianti'); ?>", "name": "matricolaimpianto[]", "values": "query=SELECT idanagrafica, id AS id, nome AS descrizione FROM my_impianti WHERE idanagrafica='$idanagrafica$' ORDER BY descrizione", "value": "$matricoleimpianti$" ]}
				</div>

			</div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Sconto totale') ?>", "name": "sconto_generico", "value": "$sconto_globale$", "help": "<?php echo tr('Sconto complessivo del contratto'); ?>", "icon-after": "choice|untprc|$tipo_sconto_globale$"<?php
if ($records[0]['stato'] == 'Emessa') {
                    echo ', "disabled" : 1';
                }
?> ]}
                </div>
            </div>


			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Esclusioni'); ?>", "name": "esclusioni", "class": "autosize", "value": "$esclusioni$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "class": "autosize", "value": "$descrizione$" ]}
				</div>
			</div>
		</div>
	</div>

	<!-- COSTI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Costi unitari'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-12 col-lg-12">
<?php

$idtipiintervento = ['-1'];

//Loop fra i tipi di attività e i relativi costi del tipo intervento
$rs = $dbo->fetchArray('SELECT co_contratti_tipiintervento.*, in_tipiintervento.descrizione FROM in_tipiintervento INNER JOIN co_contratti_tipiintervento ON in_tipiintervento.idtipointervento=co_contratti_tipiintervento.idtipointervento WHERE idcontratto='.prepare($id_record).' AND (co_contratti_tipiintervento.costo_ore!=0 OR co_contratti_tipiintervento.costo_km!=0 OR co_contratti_tipiintervento.costo_dirittochiamata!=0) ORDER BY in_tipiintervento.descrizione');

if (sizeof($rs) > 0) {
    echo '
                    <table class="table table-striped table-condensed table-bordered">
                        <tr>
                            <th width="300">'.tr('Tipo attività').'</th>

                            <th>'.tr('Costo orario').'</th>
                            <th>'.tr('Costo al km').'</th>
                            <th>'.tr('Diritto di chiamata').'</th>

                            <th>'.tr('Costo orario (tecnico)').'</th>
                            <th>'.tr('Costo al km (tecnico)').'</th>
                            <th>'.tr('Diritto di chiamata (tecnico)').'</th>
                        </tr>';

    for ($i = 0; $i < sizeof($rs); ++$i) {
        echo '
                            <tr>
                                <td>'.$rs[$i]['descrizione'].'</td>

                                <td>
                                    {[ "type": "number", "name": "costo_ore['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_ore'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_km['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_km'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_dirittochiamata['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_dirittochiamata'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_ore_tecnico['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_ore_tecnico'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_km_tecnico['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_km_tecnico'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_dirittochiamata_tecnico['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_dirittochiamata_tecnico'].'" ]}
                                </td>
                            </tr>';

        $idtipiintervento[] = prepare($rs[$i]['idtipointervento']);
    }
    echo '
                    </table>';
}

echo '
                    <button type="button" onclick="$(this).next().toggleClass(\'hide\');" class="btn btn-info btn-sm"><i class="fa fa-th-list"></i> '.tr('Mostra tipi di attività non utilizzate').'</button>
					<div class="hide">';

//Loop fra i tipi di attività e i relativi costi del tipo intervento (quelli a 0)
$rs = $dbo->fetchArray('SELECT * FROM in_tipiintervento WHERE idtipointervento NOT IN('.implode(',', $idtipiintervento).') ORDER BY descrizione');

if (sizeof($rs) > 0) {
    echo '
                        <table class="table table-striped table-condensed table-bordered">
							<tr>
								<th width="300">'.tr('Tipo attività').'</th>

								<th>'.tr('Costo orario').'</th>
								<th>'.tr('Costo al km').'</th>
								<th>'.tr('Diritto di chiamata').'</th>

								<th>'.tr('Costo orario (tecnico)').'</th>
								<th>'.tr('Costo al km (tecnico)').'</th>
								<th>'.tr('Diritto di chiamata (tecnico)').'</th>
							</tr>';

    for ($i = 0; $i < sizeof($rs); ++$i) {
        echo '
                            <tr>
                                <td>'.$rs[$i]['descrizione'].'</td>

                                <td>
                                    {[ "type": "number", "name": "costo_ore['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_orario'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_km['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_km'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_dirittochiamata['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_diritto_chiamata'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_ore_tecnico['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_orario_tecnico'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_km_tecnico['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_km_tecnico'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_dirittochiamata_tecnico['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_diritto_chiamata_tecnico'].'" ]}
                                </TD>
                            </tr>';
    }
    echo '
                        </table>';
}
?>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>


<!-- RIGHE -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo tr('Righe'); ?></h3>
    </div>

    <div class="panel-body">
<?php
if ($records[0]['stato'] != 'Pagato') {
    ?>
        <a class="btn btn-primary" data-href="<?php echo $rootdir ?>/modules/contratti/add_riga.php?idcontratto=<?php echo $id_record ?>" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo tr('Riga'); ?></a><br>
    <?php

}
?>
        <div class="clearfix"></div>
        <br>

        <div class="row">
            <div class="col-md-12">
<?php

include $docroot.'/modules/contratti/row-list.php';

?>
            </div>
        </div>
    </div>
</div>


<?php
if (!empty($records[0]['idcontratto_prev'])) {
    echo '
<!-- RIGHE -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Rinnovi precedenti').'</h3>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">';

    $idcontratto_prev = $records[0]['idcontratto_prev'];

    echo '
                <table class="table table-hover table-condensed table-bordered table-striped">
                    <tr>
                        <th>'.tr('Descrizione').'</th>
                        <th width="100">'.tr('Totale').'</th>
                        <th width="150">'.tr('Data inizio').'</th>
                        <th width="150">'.tr('Data conclusione').'</th>
                    </tr>';

    while (!empty($idcontratto_prev)) {
        $rs = $dbo->fetchArray('SELECT nome, numero, data_accettazione, data_conclusione, budget, idcontratto_prev FROM co_contratti WHERE id='.prepare($idcontratto_prev));

        echo '
                    <tr>
                        <td>
                            '.Modules::link($id_module, $idcontratto_prev, tr('Contratto num. _NUM_', [
                                '_NUM_' => $rs[0]['numero'],
                            ]).'<br><small class="text-muted">'.$rs[0]['nome'].'</small>').'
                        </td>
                        <td align="right">'.Translator::numberToLocale($rs[0]['budget']).' &euro;</td>
                        <td align="center">'.Translator::dateToLocale($rs[0]['data_accettazione']).'</td>
                        <td align="center">'.Translator::dateToLocale($rs[0]['data_conclusione']).'</td>
                    </tr>';

        $idcontratto_prev = $rs[0]['idcontratto_prev'];
    }

    echo '
                </table>
            </div>
        </div>
    </div>
</div>';
}
?>

<script type="text/javascript">
    $(document).ready(function(){
        $('#data_accettazione').on("dp.change", function(){
            if($(this).val()){
                $('#data_rifiuto').attr('disabled', true);
            }else{
                $('#data_rifiuto').attr('disabled', false);
            }
        });

        $('#data_rifiuto').on("dp.change", function(){
            console.log($(this).val());
            if($(this).val()){
                $('#data_accettazione').attr('disabled', true);
            }else{
                $('#data_accettazione').attr('disabled', false);
            }
        });

        $("#data_accettazione").trigger("dp.change");
        $("#data_rifiuto").trigger("dp.change");
    });
</script>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php

$fatture = $dbo->fetchArray('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idcontratto` = '.prepare($id_record).') ORDER BY `data`');
if (!empty($fatture)) {
    echo '
    <div class="alert alert-danger">
        <p>'.tr('Ci sono _NUM_ documenti collegate a questo elemento', [
            '_NUM_' => count($fatture),
        ]).'.</p>
    <ul>';

    foreach ($fatture as $fattura) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $fattura['tipo_documento'],
            '_NUM_' => !empty($fattura['numero_esterno']) ? $fattura['numero_esterno'] : $fattura['numero'],
            '_DATE_' => Translator::dateToLocale($fattura['data']),
        ]);

        $modulo = ($fattura['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
        $id = $fattura['id'];

        echo '
        <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
    }

    echo '
        </ul>
        <p>'.tr('Eliminando questo elemento si potrebbero verificare problemi nelle altre sezioni del gestionale!').'</p>
    </div>';
}
