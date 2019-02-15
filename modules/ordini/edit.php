<?php
include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">Intestazione</h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Numero ordine'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Numero secondario'); ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "$data$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
                    <?php
                    if (setting('Cambia automaticamente stato ordini fatturati')) {
                        if ($record['stato'] == 'Evaso' || $record['stato'] == 'Parzialmente evaso' || $record['stato'] == 'Fatturato' || $record['stato'] == 'Parzialmente fatturato') {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "id_stato", "required": 1, "values": "query=SELECT * FROM or_statiordine", "value": "$id_stato$", "extra": "readonly" ]}
                    <?php
                        } else {
                            ?>
                            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "id_stato", "required": 1, "values": "query=SELECT * FROM or_statiordine WHERE descrizione IN('Bozza')", "value": "$id_stato$" ]}
                    <?php
                        }
                    } else {
                        ?>
                    {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "id_stato", "required": 1, "values": "query=SELECT * FROM or_statiordine", "value": "$id_stato$" ]}
                    <?php
                    }
                    ?>
				</div>

			</div>

			<div class="row">
				<div class="col-md-3">
                    <?php

                    echo Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"');

                    if ($dir == 'entrata') {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.id_tipo_anagrafica=an_tipianagrafiche.id) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted_at IS NULL ORDER BY ragione_sociale", "value": "$idanagrafica$", "ajax-source": "clienti", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
					<?php
                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.id_tipo_anagrafica=an_tipianagrafiche.id) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Fornitore' AND deleted_at IS NULL ORDER BY ragione_sociale", "value": "$idanagrafica$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
					<?php
                    }
                    ?>
				</div>
                
                <div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Sede'); ?>", "name": "idsede", "required": 1, "ajax-source": "sedi", "value": "<?php echo $record['idsede']; ?>", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "required": 1, "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento$", "readonly": "<?php echo $record['flag_completato']; ?>" ]}
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
					{[ "type": "textarea", "label": "<?php echo tr('Note aggiuntive'); ?>", "name": "note_aggiuntive", "help": "<?php echo tr('Note interne'); ?>", "value": "$note_aggiuntive$" ]}
				</div>
			</div>
		</div>
	</div>

    <!-- Fatturazione Elettronica PA-->
    <div class="panel panel-primary <?php echo ($record['tipo_anagrafica'] == 'Ente pubblico' || $record['tipo_anagrafica'] == 'Azienda') ? 'show' : 'hide'; ?>" >
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Dati appalto'); ?></h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Identificatore Documento'); ?>", "name": "id_documento_fe", "required": 0, "value": "$id_documento_fe$", "maxlength": 20, "readonly": "<?php echo $record['flag_completato']; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Codice CIG'); ?>", "name": "codice_cig", "required": 0, "value": "$codice_cig$", "maxlength": 15, "readonly": "<?php echo $record['flag_completato']; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Codice CUP'); ?>", "name": "codice_cup", "required": 0, "value": "$codice_cup$", "maxlength": 15, "readonly": "<?php echo $record['flag_completato']; ?>" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<!-- RIGHE -->
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">Righe</h3>
	</div>

	<div class="panel-body">
		<div class="pull-left">
			<?php

                if ($record['flag_completato'] == 0) {
                    ?>
            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ordini/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_articolo" data-toggle="modal" data-title="Aggiungi articolo"><i class="fa fa-plus"></i> <?php echo tr('Articolo'); ?></a>

            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ordini/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_riga" data-toggle="modal" data-title="Aggiungi riga"><i class="fa fa-plus"></i> <?php echo tr('Riga'); ?></a>

            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ordini/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_descrizione" data-toggle="modal" data-title="Aggiungi descrizione"><i class="fa fa-plus"></i> <?php echo tr('Descrizione'); ?></a>
			<?php
                }

            ?>
		</div>
		<div class="clearfix"></div>
		<br>


		<div class="row">
			<div class="col-md-12">
				<?php include $docroot.'/modules/ordini/row-list.php'; ?>
			</div>
		</div>
	</div>
</div>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
// Collegamenti diretti
// Fatture o ddt collegati a questo ordine
$elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`id_tipo_documento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idordine` = '.prepare($id_record).')

UNION SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt`.`descrizione` AS tipo_documento, `dt_tipiddt`.`dir` FROM `dt_ddt` JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`id_tipo_ddt` WHERE `dt_ddt`.`id` IN (SELECT `idddt` FROM `dt_righe_ddt` WHERE `idordine` = '.prepare($id_record).') ORDER BY `data`');

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

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        if (!in_array($elemento['tipo_documento'], ['Ddt in uscita', 'Ddt in entrata'])) {
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

<script>
<?php
if ($record['flag_completato']) {
    ?>
    $('#tipo_sconto_generico').prop('disabled', true);
<?php
}
?>

$('#idanagrafica').change( function(){
	session_set('superselect,idanagrafica', $(this).val(), 0);

	$("#idsede").selectReset();
});
</script>
