<?php

include_once __DIR__.'/../../core.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

?>
<form action="" method="post" role="form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record ?>">

	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo _('Intestazione'); ?></h3>
		</div>

		<div class="panel-body">
            <div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo _('Salva modifiche'); ?></button>
			</div>
			<div class="clearfix"></div>

			<div class="row">
<?php
if ($dir == 'uscita') {
    echo '
				<div class="col-md-3">
					{[ "type": "span", "label": "'. _('Numero ddt').'", "class": "text-center", "value": "$numero$" ]}
				</div>';
}
?>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('Numero secondario'); ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo _('Data'); ?>", "maxlength": 10, "name": "data", "required": 1, "value": "$data$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Stato'); ?>", "name": "idstatoddt", "required": 1, "values": "query=SELECT * FROM dt_statiddt", "value": "$idstatoddt$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					<?php
                    if ($dir == 'entrata') {
                        ?>
						{[ "type": "select", "label": "<?php echo _('Cliente'); ?>", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti" ]}
					<?php

                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo _('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "value": "$idanagrafica$", "ajax-source": "fornitori" ]}
					<?php

                    }
                    ?>
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Destinazione merce'); ?>", "name": "idsede", "values": "query=SELECT id, CONCAT_WS(', ', nomesede, citta) AS descrizione FROM an_sedi WHERE (idanagrafica='<php echo $idanagrafica; ?>' OR idanagrafica=(SELECT valore FROM zz_settings WHERE nome='Azienda predefinita')) UNION SELECT '0' AS id, 'Sede legale' AS descrizione ORDER BY descrizione", "value": "$idsede$" ]}
				</div>
			</div>

			<hr>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Aspetto beni'); ?>", "name": "idaspettobeni", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_aspettobeni ORDER BY descrizione ASC", "value": "$idaspettobeni$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Causale trasporto'); ?>", "name": "idcausalet", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_causalet ORDER BY descrizione ASC", "value": "$idcausalet$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Porto'); ?>", "name": "idporto", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_porto ORDER BY descrizione ASC", "value": "$idporto$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo _('N<sup>o</sup> colli'); ?>", "name": "n_colli", "value": "$n_colli$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Pagamento'); ?>", "name": "idpagamento", "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Tipo di spedizione'); ?>", "name": "idspedizione", "placeholder": "-", "values": "query=SELECT id, descrizione FROM dt_spedizione ORDER BY descrizione ASC", "value": "$idspedizione$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo _('Vettore'); ?>", "name": "idvettore", "values": "query=SELECT DISTINCT an_anagrafiche.idanagrafica AS id, an_anagrafiche.ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE an_tipianagrafiche_anagrafiche.idtipoanagrafica=(SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Vettore') ORDER BY descrizione ASC", "value": "$idvettore$" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo _('Sconto totale') ?>", "name": "sconto_generico", "value": "$sconto_globale$", "icon-after": "choice|untprc|$tipo_sconto_globale$" ]}
                </div>
            </div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo _('Note'); ?>", "name": "note", "value": "$note$" ]}
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
		<h3 class="panel-title"><?php echo _('Righe'); ?></h3>
	</div>

	<div class="panel-body">
		<div class="pull-left">
<?php

if ($records[0]['stato'] != 'Evaso') {
    ?>
				<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir ?>/modules/ddt/add_articolo.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>&dir=<?php echo $dir ?>" data-toggle="modal" data-title="Aggiungi articolo" data-target="#bs-popup">
                    <i class="fa fa-plus"></i> <?php echo _('Articolo'); ?>
                </a>
				<a class="btn btn-sm btn-primary" data-href="<?php echo $rootdir ?>/modules/ddt/add_riga.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>&dir=<?php echo $dir ?>" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup">
                    <i class="fa fa-plus"></i> <?php echo _('Riga generica'); ?>
                </a>
<?php

}
?>
		</div>

		<div class="pull-right">
            <!-- Stampe -->
<?php

if ($records[0]['stato'] != 'Evaso') {
    ?>
				<a class="btn btn-sm btn-info" data-href="<?php echo $rootdir ?>/modules/ddt/creafattura.php?idddt=<?php echo $id_record ?>&dir=<?php echo $dir ?>" data-toggle="modal" data-title="Crea fattura" data-target="#bs-popup">
                    <i class="fa fa-magic"></i> <?php echo _('Crea fattura da ddt'); ?>...</i>
                </a>
<?php

}
?>

			<a class="btn btn-sm btn-info" target="_blank" href="<?php echo $rootdir ?>/pdfgen.php?ptype=ddt&idddt=<?php echo $id_record ?>" data-title="Stampa ddt"><i class="fa fa-print"></i> <?php echo _('Stampa ddt'); ?></a>
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

<script>
	$('#idanagrafica').change( function(){
		session_set('superselect,idanagrafica', $(this).val(), 0);

		$("#idsede").selectReset();
	});
</script>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo _('Elimina'); ?>
</a>
