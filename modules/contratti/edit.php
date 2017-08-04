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
			<h3 class="panel-title"><?php echo _('Intestazione') ?></h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<?php
                if ($records[0]['rinnovabile']) {
                    echo "
                <button type=\"button\" class=\"btn btn-warning\" onclick=\"if( confirm('Rinnovare questo contratto?') ){ location.href='".$rootdir.'/editor.php?op=renew&id_module='.$id_module.'&id_record='.$id_record."'; }\">
                    <i class=\"fa fa-refresh\"></i> "._('Rinnova')."...
                </button>";
                }
                ?>
				<a class="btn btn-info" href="<?php echo $rootdir ?>/pdfgen.php?ptype=contratti&idcontratto=<?php echo $id_record ?>" target="_blank"><i class="fa fa-print"></i> <?php echo _('Stampa contratto') ?></a>
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
				<br><br>
			</div>
			<div class="clearfix"></div><br>

			<div class="row">
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo _('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Cliente'); ?>", "name": "idanagrafica", "id": "idanagrafica_c", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Agente'); ?>", "name": "idagente", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Agente' AND deleted=0 ORDER BY ragione_sociale", "value": "$idagente$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Referente'); ?>", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo _('Nome'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "number", "label": "<?php echo _('Validità'); ?>", "name": "validita", "decimals": "0", "value": "$validita$", "icon-after": "giorni" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "checkbox", "label": "<?php echo _('Rinnovabile'); ?>", "name": "rinnovabile", "value": "$rinnovabile$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "number", "label": "<?php echo _('Preavviso per rinnovo'); ?>", "name": "giorni_preavviso_rinnovo", "decimals": "0", "value": "$giorni_preavviso_rinnovo$", "icon-after": "giorni" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo _('Data bozza'); ?>", "maxlength": 10, "name": "data_bozza", "value": "$data_bozza$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo _('Data accettazione'); ?>", "maxlength": 10, "name": "data_accettazione", "value": "$data_accettazione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo _('Data conclusione'); ?>", "maxlength": 10, "name": "data_conclusione", "value": "$data_conclusione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo _('Data rifiuto'); ?>", "maxlength": 10, "name": "data_rifiuto", "value": "$data_rifiuto$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Metodo di pagamento'); ?>", "name": "idpagamento", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione", "value": "$idpagamento$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Stato'); ?>", "name": "idstato", "required": 1, "values": "query=SELECT id, descrizione FROM co_staticontratti", "value": "$idstato$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "multiple": "1", "label": "<?php echo _('Impianti'); ?>", "name": "matricolaimpianto[]", "values": "query=SELECT idanagrafica, id AS id, nome AS descrizione FROM my_impianti WHERE idanagrafica='$idanagrafica$' ORDER BY descrizione", "value": "$matricoleimpianti$" ]}
				</div>

			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo _('Esclusioni'); ?>", "name": "esclusioni", "class": "autosize", "value": "$esclusioni$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo _('Descrizione'); ?>", "name": "descrizione", "class": "autosize", "value": "$descrizione$" ]}
				</div>
			</div>
		</div>
	</div>

	<!-- COSTI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Costi unitari'); ?></h3>
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
                            <th width="300">'._('Tipo attività').'</th>

                            <th>'._('Costo orario').'</th>
                            <th>'._('Costo al km').'</th>
                            <th>'._('Diritto di chiamata').'</th>

                            <th>'._('Costo orario (tecnico)').'</th>
                            <th>'._('Costo al km (tecnico)').'</th>
                            <th>'._('Diritto di chiamata (tecnico)').'</th>
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
                    <button type="button" onclick="$(this).next().toggleClass(\'hide\');" class="btn btn-info btn-sm"><i class="fa fa-th-list"></i> '._('Mostra tipi di attività non utilizzate').'</button>
					<div class="hide">';

//Loop fra i tipi di attività e i relativi costi del tipo intervento (quelli a 0)
$rs = $dbo->fetchArray('SELECT * FROM in_tipiintervento WHERE idtipointervento NOT IN('.implode(',', $idtipiintervento).') ORDER BY descrizione');

if (sizeof($rs) > 0) {
    echo '
                        <table class="table table-striped table-condensed table-bordered">
							<tr>
								<th width="300">'._('Tipo attività').'</th>

								<th>'._('Costo orario').'</th>
								<th>'._('Costo al km').'</th>
								<th>'._('Diritto di chiamata').'</th>

								<th>'._('Costo orario (tecnico)').'</th>
								<th>'._('Costo al km (tecnico)').'</th>
								<th>'._('Diritto di chiamata (tecnico)').'</th>
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
        <h3 class="panel-title"><?php echo _('Righe'); ?></h3>
    </div>

    <div class="panel-body">
<?php
if ($records[0]['stato'] != 'Pagato') {
    ?>
        <a class="btn btn-primary" data-href="<?php echo $rootdir ?>/modules/contratti/add_riga.php?idcontratto=<?php echo $id_record ?>" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo _('Riga'); ?></a><br>
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
        <h3 class="panel-title">'._('Rinnovi precedenti').'</h3>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">';

    $idcontratto_prev = $records[0]['idcontratto_prev'];

    echo '
                <table class="table table-hover table-condensed table-bordered table-striped">
                    <tr>
                        <th>'._('Descrizione').'</th>
                        <th width="100">'._('Totale').'</th>
                        <th width="150">'._('Data inizio').'</th>
                        <th width="150">'._('Data conclusione').'</th>
                    </tr>';

    while (!empty($idcontratto_prev)) {
        $rs = $dbo->fetchArray('SELECT nome, numero, data_accettazione, data_conclusione, budget, idcontratto_prev FROM co_contratti WHERE id='.prepare($idcontratto_prev));

        echo '
                    <tr>
                        <td>
                            '.Modules::link($id_module, $idcontratto_prev, str_replace('_NUM_', $rs[0]['numero'], _('Contratto n<sup>o</sup> _NUM_')).'<br><small class="text-muted">'.$rs[0]['nome'].'</small>').'
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

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo _('Elimina'); ?>
</a>
