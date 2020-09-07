<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

$plugin = Plugins::get($id_plugin);

$id_module = Modules::get('Contratti')['id'];
$block_edit = filter('add') ? false : true;

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
$record = $dbo->fetchOne('SELECT *, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_promemoria.idtipointervento) AS tipointervento, (SELECT tempo_standard FROM in_tipiintervento WHERE idtipointervento = co_promemoria.idtipointervento) AS tempo_standard FROM co_promemoria WHERE id = :id', [
    ':id' => $id_record,
]);
$data_richiesta = $record['data_richiesta'] ?: date('Y-m-d');
$id_sede = $record['idsede'];
$tempo_standard = $record['tempo_standard'];
$idtipointervento = $record['idtipointervento'];

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
<form id="add_form" action="" method="post" role="form">
	<input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">

	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="'.(!$block_edit ? 'edit-promemoria' : 'pianificazione').'">';

    echo '
	<!-- DATI PROMEMORIA -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Dati').'</h3>
		</div>

        <div class="panel-body">

			<div class="row">
				<div class="col-md-6">
					{[ "type": "date",  "label": "'.tr('Data promemoria').'", "name": "data_richiesta", "required": 1, "value": "'.$data_accettazione.'", "readonly": '.intval($block_edit).', "min-date": "'.$data_accettazione.'", "max-date": "'.$data_conclusione.'" ]}
				</div>

				<div class="col-md-6">
					 {[ "type": "select", "label": "'.tr('Tipo intervento').'", "name": "idtipointervento", "required": 1, "id": "idtipointervento_", "value": "'.$record['idtipointervento'].'", "readonly": '.intval($block_edit).', "ajax-source": "tipiintervento", "value": "'.$idtipointervento.'"  ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					{[ "type": "select", "label": "'.tr('Sede').'", "name": "idsede_c", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT( CONCAT_WS( \' (\', CONCAT_WS(\', \', `nomesede`, `citta`), `indirizzo` ), \')\') AS descrizione FROM an_sedi WHERE idanagrafica='.$id_anagrafica.'", "value": "'.$id_sede.'", "readonly": '.intval($block_edit).', "required" : "1" ]}
			   </div>

				<div class="col-md-6">
                    {[ "type": "select", "multiple": "1", "label": "'.tr('Impianti a contratto').'", "name": "idimpianti[]", "help": "'.tr('Impianti sede selezionata').'", "values": "query=SELECT my_impianti.id AS id, my_impianti.nome AS descrizione FROM my_impianti_contratti INNER JOIN my_impianti ON my_impianti_contratti.idimpianto = my_impianti.id  WHERE my_impianti_contratti.idcontratto = '.$id_parent.' ORDER BY descrizione", "value": "'.implode(',', $id_impianti).'", "readonly": '.intval($block_edit).' ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					 {[ "type": "textarea", "label": "'.tr('Descrizione').'",  "name": "richiesta", "id": "richiesta_", "readonly": '.intval($block_edit).', "value": "'.$record['richiesta'].'" ]}
				</div>
            </div>
        </div>
    </div>';

echo '
    <!-- RIGHE -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Righe').'</h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">';

            if (!$block_edit) {
                echo '
                    <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&is_articolo" data-toggle="tooltip" data-title="'.tr('Aggiungi articolo').'">
                        <i class="fa fa-plus"></i> '.tr('Articolo').'
                    </a>';

                echo '
                    <a class="btn btn-sm btn-primary" data-href="'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&is_riga" data-toggle="tooltip" data-title="'.tr('Aggiungi riga').'">
                        <i class="fa fa-plus"></i> '.tr('Riga').'
                    </a>';
            }
            echo '
                </div>
            </div>

            <div id="righe">';

include $structure->filepath('row-list.php');

        echo '
            </div>
        </div>
    </div>';

echo '{( "name": "filelist_and_upload", "id_record": "'.$id_record.'", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "readonly": '.intval($block_edit).' )}';

echo '
	<!-- PIANIFICAZIONE CICLICA -->
	<div class="panel panel-primary '.(!$block_edit ? 'hide' : '').'">
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
	<div class="panel panel-primary '.(!$block_edit ? 'hide' : '').'">
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
			<button type="submit" class="btn btn-primary" '.($block_edit ? 'disabled' : '').' ><i class="fa fa-plus"></i> '.tr('Pianifica').'</button>
		</div>
	</div>
</form>';

echo '
<script>$(document).ready(init)</script>';

echo '
<script>
    $(document).ready(function() {

        if ($("#idtipointervento_").val()==null){
            $("#add_form .panel-primary .panel-primary").hide();
            $("#modals > div .btn-primary").hide();
        };

        $("#idtipointervento_").change(function(){
            if (($(this).val()!="")){
                $("#add_form .panel-primary .panel-primary").show();
                $("#modals > div .btn-primary").show();
            } else {
                $("#add_form .panel-primary .panel-primary").hide();
                $("#modals > div .btn-primary").hide();
            }
        });

		$("#pianifica_promemoria").click(function() {
            if ($(this).is(":checked")){
                $("#intervallo").removeAttr("disabled")
                    .prop("disabled", false);
                $("#data_inizio").removeAttr("disabled")
                    .prop("disabled", false);
				input("pianifica_intervento").setDisabled(false);

				$("#modals > div .btn-primary").removeAttr("disabled");
            } else {
                $("#intervallo").prop("disabled", true);
                $("#data_inizio").prop("disabled", true);

                input("pianifica_intervento").setDisabled(true);
				$("#pianifica_intervento").prop("checked", false);

				$("#modals > div .btn-primary").prop("disabled", true);

				$("#idtecnico").prop("disabled", true)
				    .removeAttr("required");
                $("#orario_inizio").prop("disabled", true)
                    .removeAttr("required");
                $("#orario_fine").prop("disabled", true)
                    .removeAttr("required");
            }
        });

        $("#pianifica_intervento").click(function() {
            if ($(this).is(":checked")){
                $("#idtecnico").removeAttr("disabled")
                    .prop("required", true);
                $("#orario_inizio").removeAttr("disabled")
                    .prop("required", true);
                $("#orario_fine").removeAttr("disabled")
                    .prop("required", true);
            } else {
                $("#idtecnico").prop("disabled", true)
                    .removeAttr("required");
                $("#orario_inizio").prop("disabled", true)
                    .removeAttr("required");
                $("#orario_fine").removeAttr("required")
                    .prop("disabled", true);
            }
        });

    });

    function refreshRighe(id){
        $("#righe").load("'.$plugin->fileurl('row-list.php').'?id_plugin='.$id_plugin.'&id_record=" + id + "&add='.$block_edit.'");
    }
</script>';
