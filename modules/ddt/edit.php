<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Intestazione'); ?></h3>
		</div>

		<div class="panel-body">


			<?php
                if ($dir == 'entrata') {
                    $rs2 = $dbo->fetchArray('SELECT piva, codice_fiscale, citta, indirizzo, cap, provincia FROM an_anagrafiche WHERE idanagrafica='.prepare($record['idanagrafica']));
                    $campi_mancanti = [];

                    if ($rs2[0]['piva'] == '') {
                        if ($rs2[0]['codice_fiscale'] == '') {
                            array_push($campi_mancanti, 'codice fiscale');
                        }
                    }
                    if ($rs2[0]['citta'] == '') {
                        array_push($campi_mancanti, 'citta');
                    }
                    if ($rs2[0]['indirizzo'] == '') {
                        array_push($campi_mancanti, 'indirizzo');
                    }
                    if ($rs2[0]['cap'] == '') {
                        array_push($campi_mancanti, 'C.A.P.');
                    }

                    if (sizeof($campi_mancanti) > 0) {
                        echo "<div class='alert alert-warning'><i class='fa fa-warning'></i> Prima di procedere alla stampa completa i seguenti campi dell'anagrafica:<br/><b>".implode(', ', $campi_mancanti).'</b><br/>
						'.Modules::link('Anagrafiche', $record['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</div>';
                    }
                }
            ?>


			<div class="row">
				<?php
                    if ($dir == 'uscita') {
                        echo '
							<div class="col-md-3">
								{[ "type": "span", "label": "'.tr('Numero ddt').'", "class": "text-center", "value": "$numero$" ]}
							</div>';
                    }
                ?>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Numero secondario'); ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data'); ?>", "maxlength": 10, "name": "data", "required": 1, "value": "$data$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
                    <?php
                    if (setting('Cambia automaticamente stato ddt fatturati')) {
                        if ($record['stato'] == 'Fatturato' || $record['stato'] == 'Parzialmente fatturato') {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoddt", "required": 1, "values": "query=SELECT * FROM dt_statiddt", "value": "$idstatoddt$", "extra": "readonly" ]}
                    <?php
                        } else {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoddt", "required": 1, "values": "query=SELECT * FROM dt_statiddt WHERE descrizione IN('Bozza', 'Evaso', 'Parzialmente evaso')", "value": "$idstatoddt$" ]}
                    <?php
                        }
                    } else {
                        ?>
                    {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoddt", "required": 1, "values": "query=SELECT * FROM dt_statiddt", "value": "$idstatoddt$" ]}
                    <?php
                    }
                    ?>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					<?php
                    if ($dir == 'entrata') {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
					<?php
                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "ajax-source": "fornitori", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
					<?php
                    }
                    ?>
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Destinazione merce'); ?>", "name": "idsede", "values": "query=SELECT id, CONCAT_WS(', ', nomesede, citta) AS descrizione FROM an_sedi WHERE (idanagrafica='$idanagrafica$' OR idanagrafica=(SELECT valore FROM zz_settings WHERE nome='Azienda predefinita')) UNION SELECT '0' AS id, 'Sede legale' AS descrizione ORDER BY descrizione", "value": "$idsede$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>
			</div>

			<hr>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Aspetto beni'); ?>", "name": "idaspettobeni", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_aspettobeni ORDER BY descrizione ASC", "value": "$idaspettobeni$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Causale trasporto'); ?>", "name": "idcausalet", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_causalet ORDER BY descrizione ASC", "value": "$idcausalet$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Porto'); ?>", "name": "idporto", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_porto ORDER BY descrizione ASC", "value": "$idporto$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Num. colli'); ?>", "name": "n_colli", "value": "$n_colli$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Tipo di spedizione'); ?>", "name": "idspedizione", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_spedizione ORDER BY descrizione ASC", "value": "$idspedizione$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Vettore'); ?>", "name": "idvettore", "values": "query=SELECT DISTINCT an_anagrafiche.idanagrafica AS id, an_anagrafiche.ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE an_tipianagrafiche_anagrafiche.idtipoanagrafica=(SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Vettore') ORDER BY descrizione ASC", "value": "$idvettore$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Sconto incondizionato'); ?>", "name": "sconto_generico", "value": "$sconto_globale$", "icon-after": "choice|untprc|$tipo_sconto_globale$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
                </div>
            </div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "<?php echo tr('Note aggiuntive'); ?>", "name": "note_aggiuntive", "help": "<?php echo tr('Note interne.'); ?>", "value": "$note_aggiuntive$" ]}
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
		<div class="pull-left">
<?php

if ($record['flag_completato'] == 0) {
    // Lettura ordini
    $ordini_query = 'SELECT COUNT(*) AS tot FROM or_ordini WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstatoordine IN (SELECT id FROM or_statiordine WHERE descrizione IN(\'Bozza\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoordine=(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).') AND or_ordini.id IN (SELECT idordine FROM or_righe_ordini WHERE or_righe_ordini.idordine = or_ordini.id AND (qta - qta_evasa) > 0)';
    $ordini = $dbo->fetchArray($ordini_query)[0]['tot'];
    echo '
            <a class="btn btn-primary'.(!empty($ordini) ? '' : ' disabled').'" data-href="'.$rootdir.'/modules/ddt/add_ordine.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="modal" data-title="Aggiungi ordine" data-target="#bs-popup">
                <i class="fa fa-plus"></i> '.tr('Ordine').'
            </a>'; ?>
            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ddt/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_articolo" data-toggle="modal" data-title="Aggiungi articolo" data-target="#bs-popup">
                <i class="fa fa-plus"></i> <?php echo tr('Articolo'); ?>
            </a>

            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ddt/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_riga" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup">
                <i class="fa fa-plus"></i> <?php echo tr('Riga'); ?>
            </a>

            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ddt/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_descrizione" data-toggle="modal" data-title="Aggiungi descrizione" data-target="#bs-popup">
                <i class="fa fa-plus"></i> <?php echo tr('Descrizione'); ?>
            </a>
<?php
}
?>
		</div>
		<div class="clearfix"></div>
		<br>

		<div class="row">
			<div class="col-md-12">

<?php
include $docroot.'/modules/ddt/row-list.php';
?>
			</div>
		</div>
	</div>
</div>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<script>
	$('#idanagrafica').change( function(){
		session_set('superselect,idanagrafica', $(this).val(), 0);

		$("#idsede").selectReset();
	});
</script>

<?php
// Collegamenti diretti
// Fatture collegate a questo ddt
$elementi = $dbo->fetchArray('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idddt` = '.prepare($id_record).') ORDER BY `data`');

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

    foreach ($elementi as $fattura) {
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

<script>
<?php
if ($record['flag_completato']) {
    ?>
    $('#tipo_sconto_generico').prop('disabled', true);
<?php
}
?>
</script>
