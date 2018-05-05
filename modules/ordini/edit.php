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
					{[ "type": "text", "label": "<?php echo tr('Numero ordine'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Numero secondario'); ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "date", "label": "<?php echo tr('Data'); ?>", "maxlength": 10, "name": "data", "required": 1, "value": "$data$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "idstatoordine", "required": 1, "values": "query=SELECT * FROM or_statiordine", "value": "$idstatoordine$" ]}
				</div>

			</div>

			<div class="row">
				<div class="col-md-3">
                    <?php

                    echo Modules::link('Anagrafiche', $records[0]['idanagrafica'], null, null, 'class="pull-right"');

                    if ($dir == 'entrata') {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Cliente' AND deleted=0 ORDER BY ragione_sociale", "value": "$idanagrafica$", "ajax-source": "clienti" ]}
					<?php
                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "values": "query=SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE descrizione='Fornitore' AND deleted=0 ORDER BY ragione_sociale", "value": "$idanagrafica$" ]}
					<?php
                    }
                    ?>
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "required": 1, "values": "query=SELECT id, descrizione FROM co_pagamenti GROUP BY descrizione ORDER BY descrizione ASC", "value": "$idpagamento$" ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Sconto incondizionato'); ?>", "name": "sconto_generico", "value": "$sconto_globale$", "icon-after": "choice|untprc|$tipo_sconto_globale$" ]}
                </div>
            </div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
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
			<?php if ($records[0]['stato'] != 'Evaso') {
                        ?>
            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ordini/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_articolo" data-toggle="modal" data-title="Aggiungi articolo" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo tr('Articolo'); ?></a>

            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ordini/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_riga" data-toggle="modal" data-title="Aggiungi riga" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo tr('Riga'); ?></a>

            <a class="btn btn-primary" data-href="<?php echo $rootdir; ?>/modules/ordini/row-add.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&is_descrizione" data-toggle="modal" data-title="Aggiungi descrizione" data-target="#bs-popup"><i class="fa fa-plus"></i> <?php echo tr('Descrizione'); ?></a>
			<?php
                    } ?>
		</div>

		<div class="pull-right">

			<!-- Stampe -->
			<?php 

				if ($records[0]['stato'] != 'Evaso') {
             
				echo '
					<div class="dropdown">
					  <button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
					    <i class="fa fa-magic"></i>&nbsp;'.tr('Crea subito').'
					    <span class="caret"></span>
					  </button>
					   <ul class="dropdown-menu">
					   		
						    <li>
						        <a data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-toggle="modal" data-title="'.tr('Crea ddt').'" data-target="#bs-popup"><i class="fa fa-file-o"></i>&nbsp;'.tr('ddt').'
						        </a>
						    </li>

						    <li>
						        <a data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'" data-target="#bs-popup"><i class="fa fa-file"></i>&nbsp;'.tr('fattura').'
						        </a>
						    </li>

						</ul>
					</div>';

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

{( "name": "filelist_and_upload", "id_module": "<?php echo $id_module; ?>", "id_record": "<?php echo $id_record; ?>" )}

<?php
//fatture o ddt collegati a questo ordine
$elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idordine` = '.prepare($id_record).') UNION
SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt`.`descrizione` AS tipo_documento, `dt_tipiddt`.`dir` FROM `dt_ddt` JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` WHERE `dt_ddt`.`id` IN (SELECT `idddt` FROM `dt_righe_ddt` WHERE `idordine` = '.prepare($id_record).') ORDER BY `data`');
if (!empty($elementi)) {
    echo '
    <div class="alert alert-warning">
        <p>'.tr('_NUM_ altr_I_ document_I_ collegat_I_', [
            '_NUM_' => count($elementi),
            '_I_' => (count($elementi) > 1) ? tr('i') : tr('o'),
        ]).':</p>
    <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        if (!in_array($elemento['tipo_documento'], ['Ddt di vendita', 'Ddt di acquisto'])) {
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
        <p>'.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale.').'</p>
    </div>';
}

?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
