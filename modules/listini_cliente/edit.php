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
				<div class="col-md-4">
					{[ "type":"text", "label":"'.tr('Nome').'", "name":"nome", "value":"$nome$", "required":"1" ]}
				</div>

				<div class="col-md-3">
					{[ "type":"date", "label":"'.tr('Data attivazione').'", "name":"data_attivazione", "value":"$data_attivazione$", "required":"1" ]}
				</div>

				<div class="col-md-3">
					{[ "type":"date", "label":"'.tr('Data scadenza default').'", "name":"data_scadenza_predefinita", "value":"$data_scadenza_predefinita$" ]}
				</div>

				<div class="col-md-2">
					{[ "type":"checkbox", "label":"'.tr('Sempre visibile').'", "name":"is_sempre_visibile", "value":"$is_sempre_visibile$", "help": "'.tr('Se attivo il valore sarà visibile sull\'articolo').'" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type":"textarea", "label":"'.tr('Note').'", "name":"note", "value":"$note$" ]}
				</div>
			</div>
			<hr>
			<div class="row">
				<div class="col-md-offset-7 col-md-3">
					{[ "type":"select", "label":"'.tr('Articolo').'", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1, "id_listino": '.$id_record.'}  ]}
				</div>

				<div class="col-md-2">
					<div class="btn-group btn-group-flex">
						<button type="button" class="btn btn-primary" style="margin-top:25px;" onclick="aggiungiArticolo(this, true)">
							<i class="fa fa-plus"></i> '.tr('Aggiungi').'
						</button>
					</div>
				</div>
			</div>

			<div style="max-height:400px; overflow:auto;">
				<table class="table table-striped table-condensed table-bordered">
					<tr>
						<th class="text-center" width="14%">'.tr('Codice').'</th>
						<th class="text-center">'.tr('Descrizione').'</th>
						<th class="text-center" width="10%">'.tr('Data scadenza').'</th>
						<th class="text-center" width="10%">'.tr('Minimo').'</th>
						<th class="text-center" width="10%">'.tr('Prezzo di listino').'</th>
						<th class="text-center" width="10%">'.tr('Prezzo ivato').'</th>
						<th class="text-center" width="10%">'.tr('Sconto').'</th>
						<th class="text-center" width="7%">#</th>
					</tr>';
			
				foreach ($articoli as $articolo) {
				echo '
					<tr data-id="'.$articolo['id'].'">
						<td class="text-center">
							'.Modules::link('Articoli', $articolo['id_articolo'], $articolo['codice'], null, '').'
						</td>

						<td>
							'.$articolo['descrizione'].'
						</td>

						<td class="text-center">
							'.dateFormat($articolo['data_scadenza']).'
						</td>

						<td class="text-center">
							'.moneyFormat($articolo['minimo_vendita']).'
						</td>

						<td class="text-center">
							'.moneyFormat($articolo['prezzo_unitario']).'
						</td>

						<td class="text-center">
							'.moneyFormat($articolo['prezzo_unitario_ivato']).'
						</td>

						<td class="text-center">
							'.numberFormat($articolo['sconto_percentuale']).' %
						</td>

						<td class="text-center">
							<a class="btn btn-xs btn-warning" title="'.tr('Modifica articolo').'" onclick="modificaArticolo(this)">
								<i class="fa fa-edit"></i>
							</a>

							<a class="btn btn-xs btn-danger" title="'.tr('Rimuovi articolo').'" onclick="rimuoviArticolo($(this).closest(\'tr\').data(\'id\'))">
								<i class="fa fa-trash"></i>
							</a>
						</td>
					</tr>';
				}

				if (empty($articoli)) {
					echo '
					<tr data-id="'.$articolo['id'].'">
						<td colspan="7" class="text-center">
							'.tr('Nessun articolo presente').'
						</td>
					</tr>';
				}

				echo '
				</table>
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

	async function modificaArticolo(button) {
		let riga = $(button).closest("tr");
		let id = riga.data("id");
	
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
</script>';