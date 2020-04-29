<?php

include_once __DIR__.'/../../core.php';

$block_edit = $record['is_completato'];

unset($_SESSION['superselect']['idsede_destinazione']);
unset($_SESSION['superselect']['idanagrafica']);
$_SESSION['superselect']['idanagrafica'] = $record['idanagrafica'];

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Intestazione'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$" ]}
				</div>

                <div class="col-md-3">
                    {[ "type": "date", "label": "<?php echo tr('Data bozza'); ?>", "name": "data_bozza", "value": "$data_bozza$" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data accettazione'); ?>", "name": "data_accettazione", "value": "$data_accettazione$" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data conclusione'); ?>", "name": "data_conclusione", "value": "$data_conclusione$" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data rifiuto'); ?>", "name": "data_rifiuto", "value": "$data_rifiuto$" ]}
                </div>
            </div>

			<div class="row">
                <div class="col-md-3">
                    <?php
                    echo Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"');
                    ?>

                    {[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "id": "idanagrafica_c", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "value": "$idsede$", "ajax-source": "sedi", "placeholder": "Sede legale" ]}
                </div>

				<div class="col-md-3">
                    <?php
                    echo Plugins::link('Referenti', $record['idanagrafica'], null, null, 'class="pull-right"');
                    ?>

					{[ "type": "select", "label": "<?php echo tr('Referente'); ?>", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti" ]}
				</div>

				<div class="col-md-3">
                    <?php
                        if ($record['idagente'] != 0) {
                            echo Modules::link('Anagrafiche', $record['idagente'], null, null, 'class="pull-right"');
                        }
                    ?>
					{[ "type": "select", "label": "<?php echo tr('Agente'); ?>", "name": "idagente", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Agente' AND deleted_at IS NULL ORDER BY ragione_sociale", "value": "$idagente$" ]}
				</div>

			</div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione", "value": "$idpagamento$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstato", "required": 1, "values": "query=SELECT id, descrizione FROM co_staticontratti", "value": "$idstato$", "class": "unblockable" ]}
                </div>
            </div>

			<div class="row">

				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo tr('Validità offerta'); ?>", "name": "validita", "decimals": "2", "value": "$validita$", "icon-after": "giorni" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr('Rinnovabile'); ?>", "name": "rinnovabile", "help": "<?php echo tr('Il contratto è rinnovabile?'); ?>", "value": "$rinnovabile$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo tr('Preavviso per rinnovo'); ?>", "name": "giorni_preavviso_rinnovo", "decimals": "2", "value": "$giorni_preavviso_rinnovo$", "icon-after": "giorni", "disabled": <?php echo $record['rinnovabile'] ? 0 : 1; ?> ]}
				</div>

                <div class="col-md-3">
					{[ "type": "number", "label": "<?php echo tr('Ore rimanenti rinnovo'); ?>", "name": "ore_preavviso_rinnovo", "decimals": "2", "value": "$ore_preavviso_rinnovo$", "icon-after": "ore", "disabled": <?php echo $record['rinnovabile'] ? 0 : 1; ?>, "help": "<?php echo tr('Ore residue nel contratto prima di visualizzare una avviso per un eventuale rinnovo anticipato.'); ?>" ]}
				</div>
			</div>

			<div class="row">

			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "select", "multiple": "1", "label": "<?php echo tr('Impianti'); ?>", "name": "matricolaimpianto[]", "values": "query=SELECT idanagrafica, id AS id, IF(nome = '', matricola, CONCAT(matricola, ' - ', nome)) AS descrizione FROM my_impianti WHERE idanagrafica='$idanagrafica$' ORDER BY descrizione", "value": "$idimpianti$", "icon-after": "add|<?php echo Modules::get('MyImpianti')['id']; ?>|||<?php echo (empty($block_edit)) ? '' : 'disabled'; ?>" ]}
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

    <?php
        if (!empty($record['id_documento_fe']) || !empty($record['num_item']) || !empty($record['codice_cig']) || !empty($record['codice_cup'])) {
            $collapsed = 'in';
        } else {
            $collapsed = '';
        }
    ?>

    <!-- Fatturazione Elettronica PA-->
    <div class="panel-group">
        <div class="panel panel-primary <?php echo ($record['tipo_anagrafica'] == 'Ente pubblico' || $record['tipo_anagrafica'] == 'Azienda') ? 'show' : 'hide'; ?>">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <?php echo tr('Dati appalto'); ?>

                    <div class="box-tools pull-right">
                        <a data-toggle="collapse" href="#dati_appalto"><i class="fa fa-plus" style='color:white;margin-top:2px;'></i></a>
                    </div>
                </h4>
            </div>
            <div id="dati_appalto" class="panel-collapse collapse <?php echo $collapsed; ?>">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Identificatore Documento'); ?>", "name": "id_documento_fe", "required": 0, "help": "<?php echo tr('<span>Obbligatorio per valorizzare CIG/CUP. &Egrave; possible inserire: </span><ul><li>N. determina</li><li>RDO</li><li>Ordine MEPA</li></ul>'); ?>", "value": "$id_documento_fe$", "maxlength": 20 ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Numero Riga'); ?>", "name": "num_item", "required": 0, "value": "$num_item$", "maxlength": 15 ]}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Codice CIG'); ?>", "name": "codice_cig", "required": 0, "value": "$codice_cig$", "maxlength": 15 ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Codice CUP'); ?>", "name": "codice_cup", "required": 0, "value": "$codice_cup$", "maxlength": 15 ]}
                        </div>
                    </div>
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

// Loop fra i tipi di attività e i relativi costi del tipo intervento
$rs = $dbo->fetchArray('SELECT co_contratti_tipiintervento.*, in_tipiintervento.descrizione FROM co_contratti_tipiintervento INNER JOIN in_tipiintervento ON in_tipiintervento.idtipointervento = co_contratti_tipiintervento.idtipointervento WHERE idcontratto='.prepare($id_record).' AND (co_contratti_tipiintervento.costo_ore != in_tipiintervento.costo_orario OR co_contratti_tipiintervento.costo_km != in_tipiintervento.costo_km OR co_contratti_tipiintervento.costo_dirittochiamata != in_tipiintervento.costo_diritto_chiamata) ORDER BY in_tipiintervento.descrizione');

if (!empty($rs)) {
    echo '
                    <table class="table table-striped table-condensed table-bordered">
                        <tr>
                            <th width="300">'.tr('Tipo attività').'</th>

                            <th>'.tr('Addebito orario').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
                            <th>'.tr('Addebito km').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
                            <th>'.tr('Addebito diritto ch.').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>

                            <th width="40"></th>
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
                                    <button type="button" class="btn btn-warning" data-toggle="tooltip" title="Importa valori da tariffe standard" onclick="if( confirm(\'Importare i valori dalle tariffe standard?\') ){ $.post( \''.$rootdir.'/modules/contratti/actions.php\', { op: \'import\', idcontratto: \''.$id_record.'\', idtipointervento: \''.$rs[$i]['idtipointervento'].'\' }, function(data){ location.href=\''.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'\'; } ); }">
                                    <i class="fa fa-download"></i>
                                    </button>
                                </td>

                            </tr>';

        $idtipiintervento[] = prepare($rs[$i]['idtipointervento']);
    }
    echo '
                    </table>';
}

echo '
                    <button type="button" onclick="$(this).next().toggleClass(\'hide\');" class="btn btn-info btn-sm"><i class="fa fa-th-list"></i> '.tr('Mostra tipi di attività non modificati').'</button>
					<div class="hide">';

//Loop fra i tipi di attività e i relativi costi del tipo intervento (quelli a 0)
$rs = $dbo->fetchArray('SELECT * FROM co_contratti_tipiintervento INNER JOIN in_tipiintervento ON in_tipiintervento.idtipointervento = co_contratti_tipiintervento.idtipointervento WHERE co_contratti_tipiintervento.idtipointervento NOT IN('.implode(',', $idtipiintervento).') AND idcontratto='.prepare($id_record).' ORDER BY descrizione');

if (!empty($rs)) {
    echo '
                        <div class="clearfix">&nbsp;</div>
						<table class="table table-striped table-condensed table-bordered">
							<tr>
								<th width="300">'.tr('Tipo attività').'</th>

								<th>'.tr('Addebito orario').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
								<th>'.tr('Addebito km').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
								<th>'.tr('Addebito diritto ch.').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>

                                <th width="40"></th>
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
                                <button type="button" class="btn btn-warning" data-toggle="tooltip" title="Importa valori da tariffe standard" onclick="if( confirm(\'Importare i valori dalle tariffe standard?\') ){ $.post( \''.$rootdir.'/modules/contratti/actions.php\', { op: \'import\', idcontratto: \''.$id_record.'\', idtipointervento: \''.$rs[$i]['idtipointervento'].'\' }, function(data){ location.href=\''.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'\'; } ); }">
                                    <i class="fa fa-download"></i>
                                </button>
                                </td>

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
if (!$block_edit) {
    echo '
            <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_articolo" data-toggle="tooltip" data-title="'.tr('Aggiungi articolo').'">
                <i class="fa fa-plus"></i> '.tr('Articolo').'
            </a>';

    echo '
            <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_riga" data-toggle="tooltip" data-title="'.tr('Aggiungi riga').'">
                <i class="fa fa-plus"></i> '.tr('Riga').'
            </a>';

    echo '
            <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_descrizione" data-toggle="tooltip" data-title="'.tr('Aggiungi descrizione').'">
                <i class="fa fa-plus"></i> '.tr('Descrizione').'
            </a>';

    echo '
            <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_sconto" data-toggle="tooltip" data-title="'.tr('Aggiungi sconto/maggiorazione').'">
                <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
            </a>';
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

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

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
            if($(this).val()){
                $('#data_accettazione').attr('disabled', true);
            }else{
                $('#data_accettazione').attr('disabled', false);
            }
        });

        $("#data_accettazione").trigger("dp.change");
        $("#data_rifiuto").trigger("dp.change");
    });

	$('#idanagrafica_c').change( function(){
        session_set('superselect,idanagrafica', $(this).val(), 0);

		$("#idsede").selectReset();
	});

	$('#codice_cig, #codice_cup').bind("keyup change", function(e) {

		if ($('#codice_cig').val() == '' && $('#codice_cup').val() == '' ){
			$('#id_documento_fe').prop('required', false);
		}else{
			$('#id_documento_fe').prop('required', true);
		}

	});
</script>

<?php
// Collegamenti diretti
// Fatture o interventi collegati a questo contratto
$elementi = $dbo->fetchArray('SELECT 0 AS `codice`, `co_documenti`.`id` AS `id`, `co_documenti`.`numero` AS `numero`, `co_documenti`.`numero_esterno` AS `numero_esterno`,  `co_documenti`.`data`, `co_tipidocumento`.`descrizione` AS `tipo_documento`, `co_tipidocumento`.`dir` AS `dir`  FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idcontratto` = '.prepare($id_record).')'.'
UNION
SELECT  `in_interventi`.`codice` AS `codice`, `in_interventi`.`id` AS `id`, 0 AS `numero`, 0 AS `numero_esterno`, `in_interventi`.`data_richiesta` AS `data`, 0 AS `tipo_documento`, 0 AS `dir` FROM `in_interventi` WHERE `in_interventi`.`id_contratto` = '.prepare($id_record).' ORDER BY `data` ');

if (!empty($elementi)) {
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
            '_NUM_' => count($elementi),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

    // Elenco attività o contratti collegati
    foreach ($elementi as $riga) {
        if (!empty($riga['dir'])) {
            $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
                '_DOC_' => $riga['tipo_documento'],
                '_NUM_' => !empty($riga['numero_esterno']) ? $riga['numero_esterno'] : $riga['numero'],
                '_DATE_' => Translator::dateToLocale($riga['data']),
            ]);

            $modulo = ($riga['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
            $id = $riga['id'];

            echo '
            <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
        } else {
            $descrizione = tr('Intervento num. _NUM_ del _DATE_', [
                '_NUM_' => $riga['codice'],
                '_DATE_' => Translator::dateToLocale($riga['data']),
            ]);

            $modulo = 'Interventi';
            $id = $riga['id'];

            echo '
            <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
        }
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
} else {
    ?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php
}
?>

<script>
$('#rinnovabile').on('change', function(){
    if ($(this).is(':checked')) {
        $('#giorni_preavviso_rinnovo').prop('disabled', false);
    } else {
        $('#giorni_preavviso_rinnovo').prop('disabled', true);
    }
});
</script>