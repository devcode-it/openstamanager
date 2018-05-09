<?php

include_once __DIR__.'/../../../core.php';

//<form action="plugin_editor.php?id_plugin=$id_plugin$&id_module=$id_module$&id_parent=$id_parent$" method="post" role="form">


$data_richiesta = date('d/m/Y');
$disabled = '';
$hide = 'hide';



//mi ricavo informazioni del contratto
$data_conclusione = $dbo->fetchArray('SELECT `data_conclusione` FROM `co_contratti` WHERE `id` = '.prepare($id_record))[0]['data_conclusione'];
$idanagrafica = $dbo->fetchArray('SELECT `idanagrafica` FROM `co_contratti` WHERE `id` = '.prepare($id_record))[0]['idanagrafica'];

$list = '\"1\":\"'.tr('Pianificare a partire da oggi ').date('d/m/Y').'\"';

if (!empty($get['idcontratto_riga'])){
	
	$idcontratto_riga = $get['idcontratto_riga'];
	$qp = 'SELECT *, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_righe_contratti.idtipointervento) AS tipointervento FROM co_righe_contratti WHERE id = '.$idcontratto_riga;
	$rsp = $dbo->fetchArray($qp);

	$data_richiesta = readDate($rsp[0]['data_richiesta']);
	$matricoleimpianti = trim($rsp[0]['idimpianti']);
	$idsede = $rsp[0]['idsede'];
	
	$readonly = 'readonly';
	$hide = '';
	$list .= ', \"0\":\"'.tr('Pianificare a partire da questo promemoria ').$data_richiesta.'\"';
	
	
}

//orari inizio fine interventi
$orario_inizio = '09:00';
$orario_fine = '17:00';

echo '
<form id="add_form" action="'.$rootdir.'/editor.php?id_module='.Modules::get('Contratti')['id'].'&id_record='.$id_record.'&idcontratto_riga='.$idcontratto_riga.'" method="post">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="pianificazione">';

	
	
	echo '
	<!-- DATI PROMEMORIA? -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Dati').'</h3>
		</div>

		<div class="panel-body">
		
		
		
	<div class="row">

		<div class="col-md-6">
			{[ "type": "date",  "label": "'.tr('Entro il').'", "name": "data_richiesta", "required": 1, "value": "'.$data_richiesta.'", "extra":"'.$readonly.'" ]}
		</div>
		
		<div class="col-md-6">
			 {[ "type": "select", "label": "'.tr('Tipo intervento').'", "name": "idtipointervento", "required": 1, "id": "idtipointervento_", "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "'.$rsp[0]['idtipointervento'].'", "extra": "'.$readonly.'" ]}
		</div>

	</div>
	
	
	
	<div class="row">
	
		<div class="col-md-6">
				{[ "type": "select", "multiple": "1", "label": "'.tr('Impianti').'", "name": "matricolaimpianto_[]", "values": "query=SELECT my_impianti.id AS id, my_impianti.nome AS descrizione FROM my_impianti_contratti INNER JOIN my_impianti ON my_impianti_contratti.idimpianto = my_impianti.id  WHERE my_impianti_contratti.idcontratto = '.$id_record.' ORDER BY descrizione", "value": "'.$matricoleimpianti.'", "extra":"'.$readonly.'" ]}
		</div>
		
		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede_c", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT( CONCAT_WS( \' (\', CONCAT_WS(\', \', `nomesede`, `citta`), `indirizzo` ), \')\') AS descrizione FROM an_sedi WHERE idanagrafica='.$idanagrafica.'", "value": "'.$idsede.'", "extra":"'.$readonly.'" ]}
	   </div>
	   
	</div>
	
	
	<div class="row">

		<div class="col-md-12">
			 {[ "type": "textarea", "label": "'.tr('Descrizione').'",  "name": "richiesta", "id": "richiesta_", "extra": "'.$readonly.'", "value": "'.$rsp[0]['richiesta'].'"  ]}
		</div>

	</div>';
	
	
?>
	
	    <!-- ARTICOLI -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Materiale utilizzato'); ?></h3>
        </div>

        <div class="panel-body">
            <div id="articoli">
                <?php include $docroot.'/modules/contratti/plugins/ajax_articoli.php'; ?>
            </div>

            <?php if (empty($readonly)) {
                        ?>
                <button type="button" class="btn btn-primary" data-target="#bs-popup2" data-toggle="modal" data-href="<?php echo $rootdir; ?>/modules/contratti/plugins/add_articolo.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&idriga=0" ><i class="fa fa-plus"></i> <?php echo tr('Aggiungi articolo'); ?>...</button>
            <?php
                    } ?>
        </div>
    </div>
	
	
	
	
	    <!-- SPESE AGGIUNTIVE -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo tr('Altre spese'); ?></h3>
        </div>

        <div class="panel-body">
            <div id="righe">
                <?php include $docroot.'/modules/contratti/plugins/ajax_righe.php'; ?>
            </div>

            <?php if (empty($readonly)) {
                        ?>
                <button type="button" class="btn btn-primary"  data-target="#bs-popup2" data-toggle="modal" data-href="<?php echo $rootdir; ?>/modules/contratti/plugins/add_righe.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi altre spese'); ?>...</button>
            <?php
                    } ?>
        </div>
    </div>
	
<?php	
	
echo '</div>
</div>






		<!--div class="col-md-8">
			{[ "type": "checkbox", "label": "'.tr('Pianifica anche date passate').'", "name": "date_passate", "value": "0", "help": "", "placeholder": "'.tr('Pianificare promemoria anche con date precedenti ad oggi: ').date('d/m/Y').'" ]}
		</div-->

';


echo '
	<!-- PIANIFICAZIONE CICLICA? -->
	<div class="panel panel-primary '.$hide.'">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Promemoria ciclico?').'</h3>
		</div>

		<div class="panel-body">';
		
		
echo '<div class="row">
				<div class="col-md-2">
					{[ "type": "number", "label": "'.tr('Intervallo').'", "name": "intervallo", "class": "", "decimals": 0, "required": 1, "icon-after": "GG",  "min-value": "1"  ]}
				</div>';
				
?>

		<div class="col-md-7">
			{[ "type": "select", "label": "<?php echo tr('Inizio pianificazione'); ?>", "name": "parti_da_oggi", "values": "list=<?php echo $list ?>", "value": "" ]}
		</div>

<?php

echo '


		<div class="col-md-3">
			 {[ "type": "date", "label": "'.tr('Fine pianificazione').'", "help": "'.tr('Data conclusione contratto').'", "name": "data_conclusione", "id": "data_conclusione_", "extra": "readonly", "value": "'.$data_conclusione.'"  ]}
		</div>


	</div>
	
	
	</div>
</div>';



echo '
	<!-- PIANIFICARE INTERVENTI? -->
	<div class="panel panel-primary '.$hide.'">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Pianificare interventi?').'</h3>
		</div>

		<div class="panel-body">
		

	<div class="row">

		<div class="col-md-4">
			{[ "type": "checkbox", "label": "'.tr('Pianifica anche l\'intervento').'", "name": "pianifica_intervento", "value": "0", "help": "", "placeholder": "'.tr('Pianificare già l\'intervento ').'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Tecnici').'", "multiple": "1",  "name": "idtecnico[]", "required": 0, "ajax-source": "tecnici", "value": "", "extra": "disabled" ]}
		</div>


		<div class="col-xs-6  col-md-2">
			{[ "type": "time", "label": "'.tr('Orario inizio').'", "name": "orario_inizio", "required": 0, "value": "'.$orario_inizio.'", "extra": "disabled" ]}
		</div>

		<div class="col-xs-6  col-md-2">
			{[ "type": "time", "label": "'.tr('Orario fine').'", "name": "orario_fine", "required": 0, "value": "'.$orario_fine.'", "extra": "disabled" ]}
		</div>

	</div>
	
	</div>
</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Pianifica').'</button>
		</div>
	</div>


</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';

echo '
	<script>
		$(document).ready(function() {

			$("#pianifica_intervento").click(function() {

					if ($(this).is(":checked")){
						$("#idtecnico").removeAttr("disabled");
						$("#idtecnico").prop("required", true);
						$("#orario_inizio").removeAttr("disabled");
						$("#orario_fine").removeAttr("disabled");
						$("#orario_inizio").prop("required", true);
						$("#orario_fine").prop("required", true);
					}else{
						$("#idtecnico").prop("disabled", true);
						$("#idtecnico").removeAttr("required");
						$("#orario_inizio").prop("disabled", true);
						$("#orario_fine").prop("disabled", true);
						$("#orario_inizio").removeAttr("required");
						$("#orario_fine").removeAttr("required");
					}

			});

		});
	</script>';
