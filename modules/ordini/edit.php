<?php
include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

?><form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">
	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">Intestazione</h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr("Numero ordine"); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr("Numero secondario"); ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr("Data"); ?>", "maxlength": 10, "name": "data", "required": 1, "value": "$data$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr("Stato"); ?>", "name": "idstatoordine", "required": 1, "values": "query=SELECT * FROM or_statiordine", "value": "$idstatoordine$" ]}
				</div>

			</div>

			<div class="row">
				<div class="col-md-3">
					<?php
					if( $dir == 'entrata' ){
					?>
						{[ "type": "select", "label": "<?php echo tr("Cliente"); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted=0 ORDER BY ragione_sociale", "value": "$idanagrafica$", "ajax-source": "clienti" ]}
					<?php
					}

					else{
					?>
						{[ "type": "select", "label": "<?php echo tr("Fornitore"); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Fornitore' AND deleted=0 ORDER BY ragione_sociale", "value": "$idanagrafica$" ]}
					<?php
					}
					?>
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr("Pagamento"); ?>", "name": "idpagamento", "required": 1, "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento$" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Sconto totale') ?>", "name": "sconto_generico", "value": "$sconto_globale$", "icon-after": "choice|untprc|$tipo_sconto_globale$" ]}
                </div>
            </div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr("Note"); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>

			<div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
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
			<?php if( $records[0]['stato'] != 'Evaso' ){ ?>
				<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir ?>/modules/ordini/add_articolo.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>" data-toggle="modal" data-title="Aggiungi articolo" data-target="#bs-popup"><i class="fa fa-plus"></i> Articolo</a>
				<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir ?>/modules/ordini/add_riga.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup"><i class="fa fa-plus"></i> Riga generica</a>
			<?php } ?>
		</div>

		<div class="pull-right">
			<!-- Stampe -->
			<?php if( $records[0]['stato'] != 'Evaso' ){ ?>
				<a  class="btn btn-sm btn-info" data-href="<?php echo $rootdir ?>/modules/ordini/creaddt.php?id_module=<?php echo $id_module ?>&&id_record=<?php echo $id_record ?>" data-toggle="modal" data-title="Crea ddt" data-target="#bs-popup" ><i class="fa fa-magic"></i> Crea ddt da ordine...</i></a>
				<a  class="btn btn-sm btn-info" data-href="<?php echo $rootdir ?>/modules/ordini/creafattura.php?id_module=<?php echo $id_module ?>&&id_record=<?php echo $id_record ?>" data-toggle="modal" data-title="Crea fattura" data-target="#bs-popup" ><i class="fa fa-magic"></i> Crea fattura da ordine...</i></a>
			<?php } ?>

			<a  class="btn btn-sm btn-info" target="_blank" href="<?php echo $rootdir ?>/pdfgen.php?ptype=ordini&idordine=<?php echo $id_record ?>" data-title="Stampa ordine"><i class="fa fa-print"></i> Stampa ordine</a>
		</div>
		<div class="clearfix"></div>
		<br>


		<div class="row">
			<div class="col-md-12">
				<?php include($docroot."/modules/ordini/row-list.php"); ?>
			</div>
		</div>
	</div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
