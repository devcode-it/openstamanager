<?php
include_once __DIR__.'/../../core.php';

unset($_SESSION['superselect']['idanagrafica']);
$_SESSION['superselect']['idanagrafica'] = $records[0]['idanagrafica'];

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">Intestazione</h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$" ]}
				</div>

				<div class="col-md-4">
                    <?php
                        echo Modules::link('Anagrafiche', $records[0]['idanagrafica'], null, null, 'class="pull-right"');
                    ?>
					{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted=0 ORDER BY ragione_sociale", "value": "$idanagrafica$", "ajax-source": "clienti" ]}
				</div>

				<div class="col-md-3">
                    <?php
                        if ($records[0]['idagente'] != 0) {
                            echo Modules::link('Anagrafiche', $records[0]['idagente'], null, null, 'class="pull-right"');
                        }
                    ?>
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

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Tempi di consegna'); ?>", "name": "tempi_consegna", "value": "$tempi_consegna$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo tr('Validità'); ?>", "name": "validita", "decimals": "0", "value": "$validita$", "icon-after": "giorni" ]}

				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Metodo di pagamento'); ?>", "name": "idpagamento", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione", "value": "$idpagamento$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo tr('Data bozza'); ?>", "maxlength": 10, "name": "data_bozza", "value": "$data_bozza$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo tr('Data accettazione'); ?>", "maxlength": 10, "name": "data_accettazione", "value": "$data_accettazione$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo tr('Data conclusione'); ?>", "maxlength": 10, "name": "data_conclusione", "value": "$data_conclusione$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo tr('Data rifiuto'); ?>", "maxlength": 10, "name": "data_rifiuto", "value": "$data_rifiuto$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstato", "required": 1, "values": "query=SELECT id, descrizione FROM co_statipreventivi", "value": "$idstato$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Tipo di attività'); ?>", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione", "value": "$idtipointervento$" ]}
				</div>
				<!--div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Iva'); ?>", "name": "idiva", "values": "query=SELECT id, descrizione FROM co_iva ORDER BY descrizione ASC", "value": "$idiva$" ]}
				</div-->

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Resa materiale'); ?>", "name": "idporto", "values": "query=SELECT id, descrizione FROM dt_porto ORDER BY descrizione", "value": "$idporto$" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Sconto incondizionato'); ?>", "name": "sconto_generico", "value": "$sconto_globale$", "icon-after": "choice|untprc|$tipo_sconto_globale$" ]}
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

            <!--div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
			</div-->

		</div>
	</div>
</form>



<!-- RIGHE -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Righe</h3>
    </div>

    <div class="panel-body">
        <?php if ($records[0]['stato'] != 'Pagato') {
                        ?>

        <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/contratti/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_articolo" data-toggle="modal" data-title="Aggiungi articolo" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo tr('Articolo'); ?></a>

        <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/contratti/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_riga" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo tr('Riga'); ?></a>

        <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/contratti/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_descrizione" data-toggle="modal" data-title="Aggiungi descrizione" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo tr('Descrizione'); ?></a>

        <?php
                    } ?>

        <!--div class="pull-right">
            {( "name": "button", "type": "print", "id_module": "<?php echo $id_module; ?>", "id_record": "<?php echo $id_record; ?>" )}
        </div-->

        <div class="clearfix"></div>
        <br>

        <div class="row">
            <div class="col-md-12">
<?php

include $docroot.'/modules/preventivi/row-list.php';

?>
            </div>
        </div>

    </div>
</div>



<?php
//fatture collegate a questo preventivo
$fatture = $dbo->fetchArray('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idpreventivo` = '.prepare($id_record).') ORDER BY `data`');
if (!empty($fatture)) {
    echo '
    <div class="alert alert-warning">
        <p>'.tr('_NUM_ altr_I_ document_I_ collegat_I_', [
            '_NUM_' => count($fatture),
            '_I_' => (count($fatture) > 1) ? tr('i') : tr('o'),
        ]).':</p>
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
         <p>'.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale.').'</p>
    </div>';
}

?>


<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
