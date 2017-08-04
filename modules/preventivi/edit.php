<?php
include_once __DIR__.'/../../core.php';

unset($_SESSION['superselect']['idanagrafica']);
$_SESSION['superselect']['idanagrafica'] = $records[0]['idanagrafica'];

?><form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

	<!-- DATI INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">Intestazione</h3>
		</div>

		<div class="panel-body">
			<div class="pull-right">
				<a class="btn btn-info" href="<?php echo $rootdir ?>/pdfgen.php?ptype=preventivi&idpreventivo=<?php echo $id_record ?>" target="_blank"><i class="fa fa-print"></i> Stampa preventivo</a>
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
				<br/><br/>
			</div>
			<div class="clearfix"></div>

			<div class="row">
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo _('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Cliente'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted=0 ORDER BY ragione_sociale", "value": "$idanagrafica$", "ajax-source": "clienti" ]}
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

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Tempi di consegna'); ?>", "name": "tempi_consegna", "value": "$tempi_consegna$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "number", "label": "<?php echo _('Validità'); ?>", "name": "validita", "decimals": "0", "value": "$validita$", "icon-after": "giorni" ]}

				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo _('Metodo di pagamento'); ?>", "name": "idpagamento", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione", "value": "$idpagamento$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo _('Data bozza'); ?>", "maxlength": 10, "name": "data_bozza", "value": "$data_bozza$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo _('Data accettazione'); ?>", "maxlength": 10, "name": "data_accettazione", "value": "$data_accettazione$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo _('Data conclusione'); ?>", "maxlength": 10, "name": "data_conclusione", "value": "$data_conclusione$" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo _('Data rifiuto'); ?>", "maxlength": 10, "name": "data_rifiuto", "value": "$data_rifiuto$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Stato'); ?>", "name": "idstato", "required": 1, "values": "query=SELECT id, descrizione FROM co_statipreventivi", "value": "$idstato$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Tipo di attività'); ?>", "name": "idtipointervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione", "value": "$idtipointervento$" ]}
				</div>
				<!--div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Iva'); ?>", "name": "idiva", "values": "query=SELECT id, descrizione FROM co_iva ORDER BY descrizione ASC", "value": "$idiva$" ]}
				</div-->

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Resa materiale'); ?>", "name": "idporto", "values": "query=SELECT id, descrizione FROM dt_porto ORDER BY descrizione", "value": "$idporto$" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo _('Sconto totale') ?>", "name": "sconto_generico", "value": "$sconto_globale$", "icon-after": "choice|untprc|$tipo_sconto_globale$" ]}
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

            <div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
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
        <?php if ($records[0]['stato'] != 'Pagato') {
    ?>
        <div class="pull-left">
            <a class="btn btn-primary" data-href="<?php echo $rootdir ?>/modules/preventivi/edit_riga.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup"><i class="fa fa-plus"></i> Riga</a><br>
        </div>
        <?php

} ?>

        <div class="pull-right">
            <a class="btn btn-info" href="<?php echo $rootdir ?>/pdfgen.php?ptype=preventivi&idpreventivo=<?php echo $id_record ?>" target="_blank"><i class="fa fa-print"></i> Stampa preventivo</a>
        </div>
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

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo _('Elimina'); ?>
</a>
