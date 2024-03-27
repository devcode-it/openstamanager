<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

use Models\Module;

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Listino').'</h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type":"text", "label":"'.tr('Nome').'", "name":"nome", "value":"$nome$", "required":"1" ]}
				</div>

				<div class="col-md-3">
					{[ "type":"date", "label":"'.tr('Data attivazione').'", "name":"data_attivazione", "value":"$data_attivazione$", "required":"1" ]}
				</div>

				<div class="col-md-3">
					{[ "type":"date", "label":"'.tr('Data scadenza predefinita').'", "name":"data_scadenza_predefinita", "value":"$data_scadenza_predefinita$", "required":"1" ]}
				</div>
			</div>
	
			<div class="row">
				<div class="col-md-6">
					{[ "type":"checkbox", "label":"'.tr('Sempre visibile').'", "name":"is_sempre_visibile", "value":"$is_sempre_visibile$", "help": "'.tr('Se impostato il valore sarà sempre visibile sull\'articolo se il listino è attivo e la data di scadenza è ancora valida').'" ]}
				</div>

				<div class="col-md-6">
					{[ "type":"checkbox", "label":"'.tr('Attivo').'", "name":"attivo", "value": "$attivo$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type":"textarea", "label":"'.tr('Note').'", "name":"note", "value":"$note$" ]}
				</div>
			</div>
			<hr>
			<div class="row">
				<div class="col-md-5">
					{[ "type":"select", "label":"'.tr('Articolo').'", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1, "id_listino": '.$id_record.'}  ]}
				</div>

				<div class="col-md-1">
					<div class="btn-group btn-group-flex">
						<button type="button" class="btn btn-primary" style="margin-top:25px;" onclick="aggiungiArticolo(this, true)">
							<i class="fa fa-plus"></i> '.tr('Aggiungi').'
						</button>
					</div>
				</div>
			</div>

			<table class="table table-hover table-condensed table-bordered" id="tablelistini" width="100%">
				<thead>
					<tr>
						<th class="text-center">
							<br><input id="check_all" type="checkbox"/>
						</th>
						<th class="text-center">'.tr('Codice').'</th>
						<th class="text-center">'.tr('Descrizione').'</th>
						<th class="text-center">'.tr('Data scadenza').'</th>
						<th class="text-center">'.tr('Minimo').'</th>
						<th class="text-center">'.tr('Prezzo di listino').'</th>
						<th class="text-center">'.tr('Prezzo ivato').'</th>
						<th class="text-center">'.tr('Sconto').'</th>
						<th class="text-center"></th>
					</tr>
				</thead>
			</table>

			<div class="btn-group">
				<button type="button" class="btn btn-xs btn-default disabled" id="elimina_righe" onclick="rimuoviArticolo(getSelectData());">
					<i class="fa fa-trash"></i>
				</button>
			</div>
		</div>
	</div>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i>'.tr('Elimina').'
</a>

<script>
	function aggiungiArticolo(button) {
		let panel = $(button).closest(".panel");
		let id_articolo = panel.find("select").val();

		if (id_articolo) {
			openModal("'.tr('Listino articolo').'", "'.$structure->fileurl('modals/manage_articolo.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id_articolo=" + id_articolo);
		} else {
			swal("'.tr('Attenzione').'", "'.tr('Inserire un articolo').'", "warning");
		}
	}

	async function modificaArticolo(button, id) {
		let riga = $(button).closest("tr");
	
		// Chiusura tooltip
		if ($(button).hasClass("tooltipstered"))
			$(button).tooltipster("close");
	
		// Apertura modal
		openModal("'.tr('Listino articolo').'", "'.$structure->fileurl('modals/manage_articolo.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id=" + id);
	}
	
	function rimuoviArticolo(id) {
		swal({
			title: "'.tr('Rimuovere questo articolo?').'",
			html: "'.tr('Sei sicuro di volere rimuovere questo articolo dal listino?').' '.tr("L'operazione è irreversibile").'.",
			type: "warning",
			showCancelButton: true,
			confirmButtonText: "'.tr('Sì').'"
		}).then(function () {
			$.ajax({
				url: globals.rootdir + "/actions.php",
				type: "POST",
				dataType: "json",
				data: {
					id_module: globals.id_module,
					id_record: globals.id_record,
					op: "delete_articolo",
					id: id,
				},
				success: function (response) {
					location.reload();
				},
				error: function() {
					location.reload();
				}
			});
		}).catch(swal.noop);
	}

	$(document).ready(function(){
		const table = $("#tablelistini").DataTable({
			language: globals.translations.datatables,
			retrieve: true,
			ordering: false,
			searching: true,
			paging: true,
			order: [],
			lengthChange: false,
			processing: true,
			serverSide: true,
			ajax: {
				url: "'.Module::find((new Module())->getByField('name', 'Listini cliente', \Models\Locale::where('predefined', true)->first()->id))->fileurl('ajax/table.php').'?id_listino='.$id_record.'",
				type: "GET",
				dataSrc: "data",
			},
			searchDelay: 500,
			pageLength: 15,
		});
	
		table.on("processing.dt", function (e, settings, processing) {
			if (processing) {
				$("#mini-loader").show();
			} else {
				$("#mini-loader").hide();
			}
		});
	});

	// Estraggo le righe spuntate
	function getSelectData() {
		let data=new Array();
		$(\'#tablelistini\').find(\'.check:checked\').each(function (){ 
			data.push($(this).attr(\'id\'));
		});

		return data;
	}

	setTimeout(function () {
		$(".check").on("change", function() {
			let checked = 0;
			$(".check").each(function() {
				if ($(this).is(":checked")) {
					checked = 1;
				}
			});
		
			if (checked) {
				$("#elimina_righe").removeClass("disabled");
			} else {
				$("#elimina_righe").addClass("disabled");
			}
		});
	}, 1000);
	
	$("#check_all").click(function(){    
		if( $(this).is(":checked") ){
			$(".check").each(function(){
				if( !$(this).is(":checked") ){
					$(this).trigger("click");
				}
			});
		}else{
			$(".check").each(function(){
				if( $(this).is(":checked") ){
					$(this).trigger("click");
				}
			});
		}
	});
</script>';
