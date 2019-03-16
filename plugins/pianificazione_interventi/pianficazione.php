<?php

$plugin = Plugins::get($id_plugin);

$id_module = Modules::get('Contratti')['id'];
$is_add = filter('add') ? true : false;

// Informazioni contratto
$contratto = $dbo->fetchOne('SELECT * FROM `co_contratti` WHERE `id` = :id', [
    ':id' => $id_parent,
]);
$data_accettazione = $contratto['data_accettazione'];
$data_conclusione = $contratto['data_conclusione'];
$id_anagrafica = $contratto['idanagrafica'];

// Impianti del contratto
$impianti = $dbo->fetchArray('SELECT `idimpianto` FROM `my_impianti_contratti` WHERE `idcontratto` = :id', [
    ':id' => $id_parent,
]);
$id_impianti = array_column($impianti, 'idimpianto');

// solo se ho selezionato un solo impianto nel contratto, altrimenti non so quale sede e tecnico prendere
if (count($id_impianti) == 1) {
    $id_sede = $dbo->fetchOne('SELECT idsede FROM my_impianti WHERE id = '.prepare($id_impianti[0]))['idsede'];
    $id_tecnico = $dbo->fetchOne('SELECT idtecnico FROM my_impianti WHERE id = '.prepare($id_impianti[0]))['idtecnico'];
}

// Informazioni del promemoria
$record = $dbo->fetchOne('SELECT *, (SELECT descrizione FROM in_tipiintervento WHERE id_tipo_intervento=co_promemoria.id_tipo_intervento) AS tipointervento, (SELECT tempo_standard FROM in_tipiintervento WHERE id_tipo_intervento = co_promemoria.id_tipo_intervento) AS tempo_standard FROM co_promemoria WHERE id = :id', [
    ':id' => $id_record,
]);
$data_richiesta = $record['data_richiesta'] ?: date('Y-m-d');
$id_sede = $record['idsede'];
$tempo_standard = $record['tempo_standard'];
$id_tipo_intervento = $record['id_tipo_intervento'];

if (!empty($id_sede)) {
    $id_impianti = explode(',', trim($record['idimpianti']));
}

$pianificazione = [
    [
        'id' => 0,
        'text' => tr('Pianificare a partire da questo promemoria _DATE_', [
            '_DATE_' => $data_richiesta,
        ]),
    ],
    [
        'id' => 1,
        'text' => tr('Pianificare a partire da oggi _DATE_', [
            '_DATE_' => date('Y-m-d'),
        ]),
    ],
];

// orari inizio fine interventi (8h standard)
$orario_inizio = '09:00';
$orario_fine = !empty($tempo_standard) ? date('H:i', strtotime($orario_inizio) + ((60 * 60) * $tempo_standard)) : '17:00';

echo '
<form id="add_form" action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_parent='.$id_parent.'&id_record='.$id_record.'" method="post">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="'.(!empty($is_add) ? 'edit-promemoria' : 'pianificazione').'">';

    echo '
	<!-- DATI PROMEMORIA -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Dati').'</h3>
		</div>

        <div class="panel-body">

			<div class="row">
				<div class="col-md-6">
					{[ "type": "date",  "label": "'.tr('Data promemoria').'", "name": "data_richiesta", "required": 1, "value": "'.$data_accettazione.'", "readonly": '.intval(empty($is_add)).', "min-date": "'.$data_accettazione.'", "max-date": "'.$data_conclusione.'" ]}
				</div>

				<div class="col-md-6">
					 {[ "type": "select", "label": "'.tr('Tipo intervento').'", "name": "id_tipo_intervento", "required": 1, "id": "id_tipo_intervento_", "value": "'.$record['id_tipo_intervento'].'", "readonly": '.intval(empty($is_add)).', "ajax-source": "tipiintervento", "value": "'.$id_tipo_intervento.'"  ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede_c", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT( CONCAT_WS( \' (\', CONCAT_WS(\', \', `nomesede`, `citta`), `indirizzo` ), \')\') AS descrizione FROM an_sedi WHERE idanagrafica='.$id_anagrafica.'", "value": "'.$id_sede.'", "readonly": '.intval(empty($is_add)).', "required" : "1" ]}
			   </div>

				<div class="col-md-6">
                    {[ "type": "select", "multiple": "1", "label": "'.tr('Impianti a contratto').'", "name": "idimpianti[]", "help": "'.tr('Impianti sede selezionata').'", "values": "query=SELECT my_impianti.id AS id, my_impianti.nome AS descrizione FROM my_impianti_contratti INNER JOIN my_impianti ON my_impianti_contratti.idimpianto = my_impianti.id  WHERE my_impianti_contratti.idcontratto = '.$id_parent.' ORDER BY descrizione", "value": "'.implode(',', $id_impianti).'", "readonly": '.intval(empty($is_add)).' ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					 {[ "type": "textarea", "label": "'.tr('Descrizione').'",  "name": "richiesta", "id": "richiesta_", "readonly": '.intval(empty($is_add)).', "value": "'.$record['richiesta'].'" ]}
				</div>
            </div>
        </div>
    </div>';

echo '
        <!-- ARTICOLI -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Materiale da utilizzare').'</h3>
        </div>

        <div class="panel-body">
            <div id="articoli">';

include $plugin->filepath('ajax_articoli.php');

echo '
            </div>';

if (!empty($is_add)) {
    echo '
            <button type="button" class="btn btn-primary" data-title="'.tr('Aggiungi articolo').'" data-target="#bs-popup2" data-toggle="modal" data-href="'.$plugin->fileurl('add_articolo.php').'?id_plugin='.$id_plugin.'&id_record='.$id_record.'&add='.$is_add.'" ><i class="fa fa-plus"></i> '.tr('Aggiungi articolo').'...</button>';
}

echo '
        </div>
    </div>';

echo '
        <!-- SPESE AGGIUNTIVE -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Altre spese previste').'</h3>
            </div>

            <div class="panel-body">
                <div id="righe">';

include $plugin->filepath('ajax_righe.php');

echo '
                </div>';

if (!empty($is_add)) {
    echo '
                <button type="button" class="btn btn-primary"  data-title="'.tr('Aggiungi altre spese').'" data-target="#bs-popup2" data-toggle="modal" data-href="'.$plugin->fileurl('add_righe.php').'?id_plugin='.$id_plugin.'&id_record='.$id_record.'&add='.$is_add.'"><i class="fa fa-plus"></i> '.tr('Aggiungi altre spese').'...</button>';
}

echo '
        </div>
    </div>';

echo '{( "name": "filelist_and_upload", "id_record": "'.$id_record.'", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "readonly": '.intval(empty($is_add)).' )}';

echo '
	<!-- PIANIFICAZIONE CICLICA -->
	<div class="panel panel-primary '.(!empty($is_add) ? 'hide' : '').'">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Promemoria ciclico?').'</h3>
		</div>

		<div class="panel-body">

            <!--div class="col-md-8">
                {[ "type": "checkbox", "label": "'.tr('Pianifica anche date passate').'", "name": "date_passate", "value": "0", "placeholder": "'.tr('Pianificare promemoria anche con date precedenti ad oggi: ').date('d/m/Y').'" ]}
            </div-->

            <div class="row">
			
				<div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Promemoria ciclico').'", "name": "pianifica_promemoria", "value": "0", "placeholder": "'.tr('Pianificare promemoria ciclici').'", "help": "'.tr('Pianificare ciclicamente altri promemoria identici a questo').'" ]}
                </div>
				
                <div class="col-md-2">
                    {[ "type": "number", "label": "'.tr('Intervallo').'", "name": "intervallo", "decimals": 0, "required": 1, "icon-after": "GG",  "min-value": "1", "maxlength": "3", "disabled": "1"  ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "date", "label": "'.tr('Inizio pianificazione').'", "help": "'.tr('Intervallo compreso dalla data accettazione contratto fino alla data di conclusione').'", "name": "data_inizio", "value": "'.$data_accettazione.'", "disabled": "1", "min-date": "'.$data_accettazione.'", "max-date": "'.$data_conclusione.'" ]}
                </div>
				
                <div class="col-md-3">
                    {[ "type": "date", "label": "'.tr('Fine pianificazione').'", "help": "'.tr('Data conclusione contratto').'", "name": "data_conclusione", "extra": "readonly", "value": "'.$data_conclusione.'" ]}
                </div>
            </div>

        </div>
    </div>';

echo '
	<!-- PIANIFICARE INTERVENTI -->
	<div class="panel panel-primary '.(!empty($is_add) ? 'hide' : '').'">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Pianificare interventi?').'</h3>
		</div>

		<div class="panel-body">

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr("Pianifica anche l'intervento").'", "name": "pianifica_intervento", "value": "0", "placeholder": "'.tr("Pianificare già l'intervento").'", "disabled": "1" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Tecnici').'", "multiple": "1",  "name": "idtecnico[]", "ajax-source": "tecnici", "disabled": "1", "value": "'.$id_tecnico.'" ]}
                </div>


                <div class="col-xs-6  col-md-2">
                    {[ "type": "time", "label": "'.tr('Orario inizio').'", "name": "orario_inizio", "value": "'.$orario_inizio.'", "disabled": "1" ]}
                </div>

                <div class="col-xs-6  col-md-2">
                    {[ "type": "time", "label": "'.tr('Orario fine').'", "name": "orario_fine", "value": "'.$orario_fine.'", "disabled": "1" ]}
                </div>
            </div>

        </div>
    </div>';

echo '

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary" '.(empty($is_add) ? 'disabled' : '').' ><i class="fa fa-plus"></i> '.tr('Pianifica').'</button>
		</div>
	</div>
</form>';

echo '
<script src="'.$rootdir.'/assets/js/init.min.js"></script>';

echo '
<script>
    $(document).ready(function() {

        if ($("#id_tipo_intervento_").val()==null){
            $("#add_form .panel-primary .panel-primary").hide();
            $("#bs-popup .btn-primary").hide();
        };

        $("#id_tipo_intervento_").change(function(){
            if (($(this).val()!="")){
                $("#add_form .panel-primary .panel-primary").show();
                $("#bs-popup .btn-primary").show();
            } else {
                $("#add_form .panel-primary .panel-primary").hide();
                $("#bs-popup .btn-primary").hide();
            }
        });
		
		
		$("#pianifica_promemoria").click(function() {
            if ($(this).is(":checked")){
                $("#intervallo").removeAttr("disabled");
                $("#data_inizio").removeAttr("disabled");
				$("#pianifica_intervento").removeAttr("disabled");
				
				$("#bs-popup .btn-primary").removeAttr("disabled");
            } else {
                $("#intervallo").prop("disabled", true);
                $("#data_inizio").prop("disabled", true);
				$("#pianifica_intervento").prop("checked", false);
				$("#pianifica_intervento").prop("disabled", true);
				$("#bs-popup .btn-primary").prop("disabled", true);
				
				$("#idtecnico").prop("disabled", true);
                $("#idtecnico").removeAttr("required");
                $("#orario_inizio").prop("disabled", true);
                $("#orario_fine").prop("disabled", true);
                $("#orario_inizio").removeAttr("required");
                $("#orario_fine").removeAttr("required");
				
            }
        });
		

        $("#pianifica_intervento").click(function() {
            if ($(this).is(":checked")){
                $("#idtecnico").removeAttr("disabled");
                $("#idtecnico").prop("required", true);
                $("#orario_inizio").removeAttr("disabled");
                $("#orario_fine").removeAttr("disabled");
                $("#orario_inizio").prop("required", true);
                $("#orario_fine").prop("required", true);
            } else {
                $("#idtecnico").prop("disabled", true);
                $("#idtecnico").removeAttr("required");
                $("#orario_inizio").prop("disabled", true);
                $("#orario_fine").prop("disabled", true);
                $("#orario_inizio").removeAttr("required");
                $("#orario_fine").removeAttr("required");
            }
        });

    });

    function refreshArticoli(id){
        $("#articoli").load("'.$plugin->fileurl('ajax_articoli.php').'?id_plugin='.$id_plugin.'&id_record=" + id + "&add='.$is_add.'");
    }

    function refreshRighe(id){
        $("#righe").load("'.$plugin->fileurl('ajax_righe.php').'?id_plugin='.$id_plugin.'&id_record=" + id + "&add='.$is_add.'");
    }
</script>';
